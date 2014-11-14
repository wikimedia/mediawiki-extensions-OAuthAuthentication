<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

class PhpSessionStore extends SessionStore {

	private $request;

	public function __construct( \WebRequest $request ) {
		wfSetupSession();
		$this->request = $request;
	}

	public function get( $key ) {
		return $this->request->getSessionData( $key );
	}

	public function set( $key, $value ) {
		$this->request->setSessionData( $key, $value );
	}

	public function delete( $key ) {
		$this->request->setSessionData( $key, null );
	}
}
