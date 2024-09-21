<?php

namespace MediaWiki\Extension\OAuthAuthentication;

use MediaWiki\MediaWikiServices;
use MediaWiki\OAuthClient\Token;

class AuthenticationHandler {

	public static function handleIdentity(
		\WebRequest $request,
		$identity,
		Token $accessToken
	) {
		$exUser = OAuthExternalUser::newFromRemoteId(
			$identity->sub,
			$identity->username,
			MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY )  # TODO: don't do this
		);
		$exUser->setAccessToken( $accessToken );
		if ( isset( $identity->realname ) ) {
			$exUser->setRealname( $identity->realname );
		}
		if ( isset( $identity->email ) ) {
			$exUser->setEmail( $identity->email );
		}
		$exUser->setIdentifyTS( new \MWTimestamp() );

		if ( $exUser->attached() ) {
			$status = self::doLogin( $exUser, $request );
			$s = \Status::newGood( [ 'successfulLogin', $status->getValue() ] );
			$s->merge( $status );
		} else {
			$status = self::doCreateAndLogin( $exUser, $request );
			$s = \Status::newGood( [ 'successfulCreation', $status->getValue() ] );
			$s->merge( $status );
		}

		wfDebugLog( "OAuthAuth", __METHOD__ . " returning Status: " . (int)$s->isGood() );
		return $s;
	}

	public static function doCreateAndLogin( OAuthExternalUser $exUser ) {
		global $wgOAuthAuthenticationAccountUsurpation;
		wfDebugLog( "OAuthAuth", "Doing create & login for user " . $exUser->getName() );

		$u = \User::newFromName( $exUser->getName(), 'creatable' );

		if ( !is_object( $u ) ) {
			wfDebugLog( "OAuthAuth",
				__METHOD__ . ": Bad username '{$exUser->getName()}'" );
			return \Status::newFatal( 'oauthauth-create-noname' );
		} elseif ( $u->idForName() !== 0 ) {
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

			$u->setEmail( $exUser->getEmail() );
			$u->setRealName( $exUser->getRealname() );
			/*
			$u->setOption( 'language', $exUser->getLanguage() );
			*/

			$u->setToken();
			\DeferredUpdates::addUpdate( \SiteStatsUpdate::factory( [ 'users' => 1 ] ) );
			$u->addWatch( $u->getUserPage(), \User::IGNORE_USER_RIGHTS );
			$u->saveSettings();

			MediaWikiServices::getInstance()->getHookContainer()->run( 'AddNewAccount', [ $u, false ] );

			$exUser->setLocalId( $u->getId() );
		}

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY ); // TODO: di
		$exUser->addToDatabase( $dbw );
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
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$exUser->updateInDatabase( $dbw );

		$changed = false;
		// update private data if needed
		if ( $u->getEmail() !== $exUser->getEmail() ) {
			if ( $exUser->getEmail() ) {
				$u->setEmail( $exUser->getEmail() );
				$u->confirmEmail();
			} else {
				$u->invalidateEmail();
			}
			$changed = true;
		}
		if ( $u->getRealName() !== $exUser->getRealname() ) {
			$u->setRealName( $exUser->getRealname() );
			$changed = true;
		}

		if ( $changed ) {
			$u->saveSettings();
		}
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
