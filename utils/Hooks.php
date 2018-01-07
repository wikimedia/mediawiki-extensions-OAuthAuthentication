<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\OAuthClient\Client;

class Hooks {
	public static function onPersonalUrls( &$personal_urls, \Title &$title ) {
		global $wgUser, $wgRequest,
			$wgOAuthAuthenticationAllowLocalUsers, $wgOAuthAuthenticationRemoteName;

		if ( $wgUser->getID() == 0 ) {
			$query = [];
			if ( $title->isSpecial( 'Userlogout' ) ) {
				$query['returnto'] = $wgRequest->getVal( 'returnto', 'Main_Page' );
				$query['returntoquery'] = $wgRequest->getVal( 'returntoquery' );
			} else {
				$query['returnto'] = $title->getPrefixedText();
				$returntoquery = $wgRequest->getValues();
				unset( $returntoquery['title'] );
				unset( $returntoquery['returnto'] );
				unset( $returntoquery['returntoquery'] );
				$query['returntoquery'] = wfArrayToCgi( $returntoquery );
			}
			$personal_urls['login']['href'] =
				\SpecialPage::getTitleFor( 'OAuthLogin', 'init' )->getFullURL( $query );
			if ( $wgOAuthAuthenticationRemoteName ) {
				$personal_urls['login']['text'] = wfMessage( 'oauthauth-login',
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

		$resetlink = \Linker::link(
			\SpecialPage::getTitleFor( 'PasswordReset' ),
			wfMessage( 'passwordreset' )->escaped(),
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
			if ( !isset( $user->extAuthObj ) ) {
				$user->extAuthObj = OAuthExternalUser::newFromUser( $user, wfGetDB( DB_MASTER ) );
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
					$user->extAuthObj->updateInDatabase( wfGetDB( DB_MASTER ) );
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
			$loginlink = \Linker::Link(
				$loginTitle,
				wfMessage( 'login' )->escaped(),
				[],
				$query
			);
			$msg = wfMessage( 'oauthauth-localuser-not-allowed' )->rawParams( $loginlink );
			$abortError = $msg->escaped();
			return false;
		}
	}

	public static function onUnitTestsList( array &$files ) {
		$directoryIterator = new \RecursiveDirectoryIterator( __DIR__ . '/../tests/' );
		foreach ( new \RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
				$files[] = $fileInfo->getPathname();
			}
		}
		return true;
	}
}
