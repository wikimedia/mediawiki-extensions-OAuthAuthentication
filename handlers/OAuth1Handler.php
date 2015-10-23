<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\Token;

class OAuth1Handler {


	public function init( SessionStore $session, Client $client ) {
		// Step 1 - Get a request token
		list( $redir, $requestToken ) = $client->initiate();
		$session->set( 'oauthreqtoken', "{$requestToken->key}:{$requestToken->secret}" );
		return $redir;
	}

	public function authorize( \WebResponse $response, $url ) {
		$response->header( "Location: $url", true );
	}


	public function finish( \WebRequest $request, SessionStore $session, Client $client ) {
		$verifyCode = $request->getVal( 'oauth_verifier', false );
		$recKey = $request->getVal( 'oauth_token', false );

		if ( !$verifyCode || ! $recKey ) {
			throw new Exception( 'oauthauth-failed-handshake' );
		}

		list( $requestKey, $requestSecret ) = explode( ':', $session->get( 'oauthreqtoken' ) );
		$requestToken = new Token( $requestKey, $requestSecret );

		$session->delete( 'oauthreqtoken' );

		// check for csrf
		if ( $requestKey !== $recKey ) {
			throw new Exception( "oauthauth-csrf-detected" );
		}

		// Step 3 - Get access token
		$accessToken = $client->complete( $requestToken,  $verifyCode );

		return $accessToken;
	}


	public function identify( Token $accessToken, Client $client ) {
		// Get Identity
		$identity = $client->identify( $accessToken );

		return $identity;
	}


}
