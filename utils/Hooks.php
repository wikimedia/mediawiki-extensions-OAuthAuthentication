<?php
namespace MediaWiki\Extensions\OAuthAuthentication;

class Hooks {

	public static function onPersonalUrls( &$personal_urls, &$title ) {
		global $wgUser, $wgRequest,
			$wgOAuthAuthenticationAllowLocalUsers, $wgOAuthAuthenticationRemoteName;

		if ( $wgUser->getID() == 0 ) {
			$query = array();
			$query['returnto'] = $title->getPrefixedText();
			$returntoquery = $wgRequest->getValues();
			unset( $returntoquery['title'] );
			unset( $returntoquery['returnto'] );
			unset( $returntoquery['returntoquery'] );
			$query['returntoquery'] = wfArrayToCgi( $returntoquery );
			$personal_urls['login']['href'] = \SpecialPage::getTitleFor( 'OAuthLogin', 'init' )->getFullURL( $query );
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

	public static function onLoadExtensionSchemaUpdates( $updater = null ) {
		$updater->addExtensionTable( 'oauthauth_user', __DIR__ . '/../store/oauthauth.sql' );
	}

	public static function onGetPreferences( $user, &$preferences ) {
		global $wgRequirePasswordforEmailChange;

		$resetlink = \Linker::link(
			\SpecialPage::getTitleFor( 'PasswordReset' ),
			wfMessage( 'passwordreset' )->escaped(),
			array(),
			array( 'returnto' => \SpecialPage::getTitleFor( 'Preferences' ) )
		);

		if ( empty( $user->mPassword ) && empty( $user->mNewpassword ) ) {

			if ( $user->isEmailConfirmed() ) {
				$preferences['password'] = array(
					'section' => 'personal/info',
					'type' => 'info',
					'raw' => true,
					'default' => $resetlink,
					'label-message' => 'yourpassword',
				);
			} else {
				unset( $preferences['password'] );
			}

			if ( $wgRequirePasswordforEmailChange ) {
				$preferences['emailaddress'] = array(
					'type' => 'info',
					'raw' => 1,
					'default' => wfMessage( 'oauthauth-set-email' )->escaped(),
					'section' => 'personal/email',
					'label-message' => 'youremail',
					'cssclass' => 'mw-email-none',
				);
			}

		} else {
			$preferences['resetpassword'] = array(
				'section' => 'personal/info',
				'type' => 'info',
				'raw' => true,
				'default' => $resetlink,
				'label-message' => null,
			);
		}
	}

	/**
	 * Check that the identity complies with the site policy
	 *
	 */
	public static function onUserLoadAfterLoadFromSession( $user ) {
		global $wgOAuthAuthenticationMaxIdentityAge;

		if ( Policy::policyToEnforce() ) {
			if ( !isset( $user->extAuthObj ) ) {
				$user->extAuthObj = OAuthExternalUser::newFromUser( $user, wfGetDB( DB_MASTER ) );
			}

			if ( $user->extAuthObj ) {
				$lastVerify = new \MWTimestamp( $user->extAuthObj->getIdentifyTS() );
				$minVerify = new \MWTimestamp( time() - $wgOAuthAuthenticationMaxIdentityAge );

				if ( $lastVerify->getTimestamp() <= $minVerify->getTimestamp() ) {
					list( $config, $cmrToken ) = Config::getDefaultConfigAndToken();
					$client = new \MWOAuthClient( $config, $cmrToken );
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
	 * @param $user User
	 * @param $abortError
	 * @return bool
	 */
	static function onAbortNewAccount( $user, &$abortError ) {
		global $wgOAuthAuthenticationAllowLocalUsers, $wgRequest;

		if ( $wgOAuthAuthenticationAllowLocalUsers === false ) {
			$query = array();
			$query['returnto'] = $wgRequest->getVal( 'returnto' );
			$query['returntoquery'] = $wgRequest->getVal( 'returntoquery' );
			$loginTitle = \SpecialPage::getTitleFor( 'OAuthLogin', 'init' );
			$loginlink = \Linker::Link(
				$loginTitle,
				wfMessage( 'login' )->escaped(),
				array(),
				$query
			);
			$msg = wfMessage( 'oauthauth-localuser-not-allowed' )->rawParams( $loginlink );
			$abortError = $msg->escaped();
			return false;
		}
	}

}
