<?php
// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace MediaWiki\Extension\OAuthAuthentication;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\OAuthClient\Client;

class Hooks {
	public static function onSkinTemplateNavigation__Universal( $sktemplate, &$links ) {
		global $wgOAuthAuthenticationAllowLocalUsers, $wgOAuthAuthenticationRemoteName;

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
			if ( $wgOAuthAuthenticationRemoteName ) {
				$personal_urls['login']['text'] = $sktemplate->msg( 'oauthauth-login',
					$wgOAuthAuthenticationRemoteName )->text();
			}

			if ( $wgOAuthAuthenticationAllowLocalUsers === false ) {
				unset( $personal_urls['createaccount'] );
			}
		}
		return true;
	}

	public static function onPostLoginRedirect( &$returnTo, &$returnToQuery, &$type ) {
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

	public static function onLoadExtensionSchemaUpdates( \DatabaseUpdater $updater = null ) {
		$updater->addExtensionTable( 'oauthauth_user', __DIR__ . '/../store/oauthauth.sql' );
	}

	public static function onGetPreferences( \User $user, &$preferences ) {
		global $wgRequirePasswordforEmailChange, $wgOAuthAuthenticationRemoteName;

		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$resetlink = $linkRenderer->makeLink(
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

			if ( $wgRequirePasswordforEmailChange ) {
				$emailMsg = wfMessage(
					'oauthauth-set-email',
					$wgOAuthAuthenticationRemoteName
				)->escaped();
				$emailCss = 'mw-email-none';
				if ( $user->getEmail() ) {
					$emailMsg = wfMessage(
						'oauthauth-email-set',
						$user->getEmail(),
						$wgOAuthAuthenticationRemoteName
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
			}

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
	public static function onUserLoadAfterLoadFromSession( \User $user ) {
		global $wgOAuthAuthenticationMaxIdentityAge;

		if ( Policy::policyToEnforce() ) {
			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
			if ( !isset( $user->extAuthObj ) ) {
				$user->extAuthObj = OAuthExternalUser::newFromUser( $user, $dbw );
			}

			if ( $user->extAuthObj ) {
				$lastVerify = new \MWTimestamp( $user->extAuthObj->getIdentifyTS() );
				$minVerify = new \MWTimestamp( time() - $wgOAuthAuthenticationMaxIdentityAge );

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

	/**
	 * @param \User $user
	 * @param string &$abortError
	 * @return bool
	 */
	public static function onAbortNewAccount( $user, &$abortError ) {
		global $wgOAuthAuthenticationAllowLocalUsers, $wgRequest;

		if ( $wgOAuthAuthenticationAllowLocalUsers === false ) {
			$query = [];
			$query['returnto'] = $wgRequest->getVal( 'returnto' );
			$query['returntoquery'] = $wgRequest->getVal( 'returntoquery' );
			$loginTitle = \SpecialPage::getTitleFor( 'OAuthLogin', 'init' );
			$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
			$loginlink = $linkRenderer->makeLink(
				$loginTitle,
				wfMessage( 'login' )->text(),
				[],
				$query
			);
			$msg = wfMessage( 'oauthauth-localuser-not-allowed' )->rawParams( $loginlink );
			$abortError = $msg->escaped();
			return false;
		}
	}

}
