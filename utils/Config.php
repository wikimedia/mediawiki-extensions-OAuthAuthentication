<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

class Config {

	public static function getDefaultConfigAndToken() {
		global $wgOAuthAuthenticationConsumerKey,
			$wgOAuthAuthenticationConsumerSecret,
			$wgOAuthAuthenticationUrl,
			$wgOAuthAuthenticationCanonicalUrl,
			$wgOAuthAuthenticationValidateSSL;

		$validateSSL = false;
		$useSSL = false;

		if ( preg_match( '!^https://!i', $wgOAuthAuthenticationUrl ) ) {
			$validateSSL = $wgOAuthAuthenticationValidateSSL;
			$useSSL = true;
		}

		$config = new \MWOAuthClientConfig(
			$wgOAuthAuthenticationUrl, // url to use
			$useSSL, // do we use SSL? (we should probably detect that from the url)
			$validateSSL // do we validate the SSL certificate? Always use 'true' in production.
		);

		if ( $wgOAuthAuthenticationCanonicalUrl ) {
			$config->canonicalServerUrl = $wgOAuthAuthenticationCanonicalUrl;
		}
		// Optional clean url here (i.e., to work with mobile), otherwise the
		// base url just has /authorize& added
		#$config->redirURL = 'http://en.wikipedia.beta.wmflabs.org/wiki/Special:OAuth/authorize?';

		$cmrToken = new \OAuthToken( $wgOAuthAuthenticationConsumerKey, $wgOAuthAuthenticationConsumerSecret );

		return array( $config, $cmrToken );
	}

}
