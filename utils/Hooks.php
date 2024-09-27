<?php
// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace MediaWiki\Extension\OAuthAuthentication;

use Config;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\OAuthClient\Client;
use Wikimedia\Rdbms\ILoadBalancer;

class Hooks implements
	\MediaWiki\Hook\PostLoginRedirectHook,
	\MediaWiki\Hook\SkinTemplateNavigation__UniversalHook,
	\MediaWiki\Preferences\Hook\GetPreferencesHook,
	\MediaWiki\User\Hook\UserLoadAfterLoadFromSessionHook
{
	private Config $config;
	private ILoadBalancer $loadBalancer;
	private LinkRenderer $linkRenderer;

	public function __construct(
		Config $config,
		ILoadBalancer $loadBalancer,
		LinkRenderer $linkRenderer
	) {
		$this->config = $config;
		$this->loadBalancer = $loadBalancer;
		$this->linkRenderer = $linkRenderer;
	}

	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( $sktemplate->getUser()->getID() == 0 ) {
			$personal_urls = &$links['user-menu'];
			$title = $sktemplate->getTitle();
			$request = $sktemplate->getRequest();
			$query = [];
			if ( $title->isSpecial( 'Userlogout' ) ) {
				$query['returnto'] = $request->getVal( 'returnto', 'Main_Page' );
				$query['returntoquery'] = $request->getVal( 'returntoquery' );
			} else {
				$query['returnto'] = $title->getPrefixedText();
				$returntoquery = $request->getValues();
				unset( $returntoquery['title'] );
				unset( $returntoquery['returnto'] );
				unset( $returntoquery['returntoquery'] );
				$query['returntoquery'] = wfArrayToCgi( $returntoquery );
			}
			$personal_urls['login']['href'] =
				\SpecialPage::getTitleFor( 'OAuthLogin', 'init' )->getFullURL( $query );
			$remoteName = $this->config->get( 'OAuthAuthenticationRemoteName' );
			if ( $remoteName ) {
				$personal_urls['login']['text'] = $sktemplate->msg( 'oauthauth-login',
					$remoteName )->text();
			}

			if ( $this->config->get( 'OAuthAuthenticationAllowLocalUsers' ) === false ) {
				unset( $personal_urls['createaccount'] );
			}
		}
	}

	public function onPostLoginRedirect( &$returnTo, &$returnToQuery, &$type ) {
		global $wgRequest;
		$session = new PhpSessionStore( $wgRequest );

		$title = $session->get( 'oauth-init-returnto' );
		$query = $session->get( 'oauth-init-returntoquery' );

		if ( $title ) {
			$returnTo = $title;
		}

		if ( $query ) {
			$returnToQuery = $query;
		}
	}

	public function onGetPreferences( $user, &$preferences ) {
		$resetlink = $this->linkRenderer->makeLink(
			\SpecialPage::getTitleFor( 'PasswordReset' ),
			wfMessage( 'passwordreset' )->text(),
			[],
			[ 'returnto' => \SpecialPage::getTitleFor( 'Preferences' ) ]
		);

		if ( empty( $user->mPassword ) && empty( $user->mNewpassword ) ) {
			if ( $user->isEmailConfirmed() ) {
				$preferences['password'] = [
					'section' => 'personal/info',
					'type' => 'info',
					'raw' => true,
					'default' => $resetlink,
					'label-message' => 'yourpassword',
				];
			} else {
				unset( $preferences['password'] );
			}

			$remoteName = $this->config->get( 'OAuthAuthenticationRemoteName' );
			$emailMsg = wfMessage(
				'oauthauth-set-email',
				$remoteName
			)->escaped();
			$emailCss = 'mw-email-none';
			if ( $user->getEmail() ) {
				$emailMsg = wfMessage(
					'oauthauth-email-set',
					$user->getEmail(),
					$remoteName
				)->escaped();
				$emailCss = 'mw-email-authenticated';
			}
			$preferences['emailaddress'] = [
				'type' => 'info',
				'raw' => 1,
				'default' => $emailMsg,
				'section' => 'personal/email',
				'label-message' => 'youremail',
				'cssclass' => $emailCss,
			];

		} else {
			$preferences['resetpassword'] = [
				'section' => 'personal/info',
				'type' => 'info',
				'raw' => true,
				'default' => $resetlink,
				'label-message' => null,
			];
		}
	}

	/**
	 * Check that the identity complies with the site policy
	 * @param \User $user
	 * @return true
	 */
	public function onUserLoadAfterLoadFromSession( $user ) {
		if ( Policy::policyToEnforce() ) {
			$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
			if ( !isset( $user->extAuthObj ) ) {
				$user->extAuthObj = OAuthExternalUser::newFromUser( $user, $dbw );
			}

			if ( $user->extAuthObj ) {
				$maxIdentityAge = $this->config->get( 'OAuthAuthenticationMaxIdentityAge' );
				$lastVerify = new \MWTimestamp( $user->extAuthObj->getIdentifyTS() );
				$minVerify = new \MWTimestamp( time() - $maxIdentityAge );

				if ( $lastVerify->getTimestamp() <= $minVerify->getTimestamp() ) {
					$config = Config::getDefaultConfig();
					$client = new Client( $config, LoggerFactory::getInstance( 'OAuthAuthentication' ) );
					$handler = new OAuth1Handler();
					$identity = $handler->identify( $user->extAuthObj->getAccessToken(), $client );
					$user->extAuthObj->setIdentifyTS( new \MWTimestamp() );
					$user->extAuthObj->updateInDatabase( $dbw );
					if ( !Policy::checkWhitelists( $identity ) ) {
						$user->logout();
						throw new \ErrorPageError( 'oauthauth-error', 'oauthauth-loggout-policy' );
					}
				}
			}
		}

		return true;
	}

}
