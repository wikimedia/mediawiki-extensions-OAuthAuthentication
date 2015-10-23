<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

class Policy {

	public static function policyToEnforce() {
		global $wgOAuthAuthenticationUsernameWhitelist,
			$wgOAuthAuthenticationGroupWhitelist;

		return ( $wgOAuthAuthenticationUsernameWhitelist !== false
			|| $wgOAuthAuthenticationGroupWhitelist !== false
		);
	}

	/**
	 * @param object $identity jwt identity object
	 * @return bool true if the user should be allowed according to whitelists. False otherwise.
	 */
	public static function checkWhitelists( $identity ) {
		global $wgOAuthAuthenticationUsernameWhitelist,
			$wgOAuthAuthenticationGroupWhitelist;

		return self::checkUserWhitelist( $identity->username, $wgOAuthAuthenticationUsernameWhitelist )
			&& self::checkGroupWhitelist( $identity->groups, $wgOAuthAuthenticationGroupWhitelist );
	}


	private static function checkUserWhitelist( $username, $whitelist ) {
		return !$whitelist || in_array( $username, $whitelist );
	}

	private static function checkGroupWhitelist( $groups, $whitelist ) {
		return !$whitelist || count( array_intersect( $groups, $whitelist ) ) > 0;
	}

}
