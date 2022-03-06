<?php

namespace MediaWiki\Extension\OAuthAuthentication;

abstract class SessionStore {

	abstract public function get( $key );

	abstract public function set( $key, $value );

	abstract public function delete( $key );
}
