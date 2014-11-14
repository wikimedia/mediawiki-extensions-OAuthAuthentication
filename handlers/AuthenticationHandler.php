<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

class AuthenticationHandler {


	public static function handleIdentity( \WebRequest $request, $identity, \OAuthToken $accessToken ) {
		$exUser = OAuthExternalUser::newFromRemoteId(
			$identity->sub,
			$identity->username,
			wfGetDB( DB_MASTER )  #TODO: don't do this
		);
		$exUser->setAccessToken( $accessToken );
		$exUser->setIdentifyTS( new \MWTimestamp() );

		if ( $exUser->attached() ) {
			$status = AuthenticationHandler::doLogin( $exUser, $request );
			$s = \Status::newGood( array( 'successfulLogin', $status->getValue() ) );
			$s->merge( $status );
		} else {
			$status = AuthenticationHandler::doCreateAndLogin( $exUser, $request );
			$s = \Status::newGood( array( 'successfulCreation', $status->getValue() ) );
			$s->merge( $status );
		}

		wfDebugLog( "OAuthAuth", __METHOD__ . " returning Status: " . (int) $s->isGood() );
		return $s;
	}

	public static function doCreateAndLogin( OAuthExternalUser $exUser ) {
		global $wgAuth, $wgOAuthAuthenticationAccountUsurpation;
		wfDebugLog( "OAuthAuth", "Doing create & login for user " . $exUser->getName() );

		$u = \User::newFromName( $exUser->getName(), 'creatable' );

		if ( !is_object( $u ) ) {
			wfDebugLog( "OAuthAuth",
				__METHOD__ . ": Bad username '{$exUser->getName()}'" );
			return Status::newFatal( 'oauthauth-create-noname' );
		} elseif ( 0 !== $u->idForName() ) {
			wfDebugLog( "OAuthAuth",
				__METHOD__ . ": User already exists, but no usurpation. Aborting." );
			if ( !$wgOAuthAuthenticationAccountUsurpation ) {
				return \Status::newFatal( 'oauthauth-create-userexists' );
			}
			$exUser->setLocalId( $u->idForName() );
		} else {
			wfDebugLog( "OAuthAuth",
				__METHOD__ . ": Creating user '{$exUser->getName()}'" );

			# TODO: Does this need to call $wgAuth->addUser? This could potentially coexist
			# with another auth plugin.

			$status = $u->addToDatabase();
			if ( !$status->isOK() ) {
				return $status;
			}

			/* TODO: Set email, realname, and language, once we can get them via /identify
			$u->setEmail( $exUser->getEmail() );
			$u->setRealName( $exUser->getRealName() );
			$u->setOption( 'language', $exUser->getLanguage() );
			*/

			$u->setToken();
			\DeferredUpdates::addUpdate( new \SiteStatsUpdate( 0, 0, 0, 0, 1 ) );
			$u->addWatch( $u->getUserPage(), \WatchedItem::IGNORE_USER_RIGHTS );
			$u->saveSettings();

			wfRunHooks( 'AddNewAccount', array( $u, false ) );

			$exUser->setLocalId( $u->getId() );
		}

		$exUser->addToDatabase( wfGetDB( DB_MASTER ) ); //TODO: di
		$u->setCookies();
		$u->addNewUserLogEntry( 'create' );

		wfResetSessionID();

		return \Status::newGood( $u );
	}


	public static function doLogin( OAuthExternalUser $exUser, \WebRequest $request ) {
		global $wgSecureLogin, $wgCookieSecure;

		wfDebugLog( "OAuthAuth",
			__METHOD__ . ": Logging in associated user '{$exUser->getName()}'" );

		$u = \User::newFromId( $exUser->getLocalId() );

		if ( !is_object( $u ) ) {
			wfDebugLog( "OAuthAuth",
				__METHOD__ . ": Associated user doesn't exist. Aborting." );
			return Status::newFatal( 'oauthauth-login-noname' );
		} elseif ( $u->isAnon() ) {
			wfDebugLog( "OAuthAuth",
				__METHOD__ . ": Associated user is Anon. Aborting." );
			return \Status::newFatal( 'oauthauth-login-usernotexists' );
		}
wfDebugLog( "OAA", __METHOD__ . " updating exuser: " . print_r( $exUser, true ) );
		$exUser->updateInDatabase( wfGetDB( DB_MASTER ) );

		$u->invalidateCache();

		if ( !$wgSecureLogin ) {
			$u->setCookies( $request, null );
		} elseif ( $u->requiresHTTPS() ) {
			$u->setCookies( $request, true );
		} else {
			$u->setCookies( $request, false );
			$wgCookieSecure = false;
		}

		wfResetSessionID();

		return \Status::newGood( $u );
	}
}
