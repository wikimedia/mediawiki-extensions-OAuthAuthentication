<?php

namespace MediaWiki\Extension\OAuthAuthentication;

use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;

class Config {

	public static function getDefaultConfig() {
		global $wgOAuthAuthenticationConsumerKey,
			$wgOAuthAuthenticationConsumerSecret,
			$wgOAuthAuthenticationUrl,
			$wgOAuthAuthenticationCanonicalUrl,
			$wgOAuthAuthenticationValidateSSL;

		$validateSSL = false;

		if ( preg_match( '!^https://!i', $wgOAuthAuthenticationUrl ) ) {
			$validateSSL = $wgOAuthAuthenticationValidateSSL;
		}

		$config = new ClientConfig(
			$wgOAuthAuthenticationUrl, // url to use
			$validateSSL // do we validate the SSL certificate? Always use 'true' in production.
		);

		if ( $wgOAuthAuthenticationCanonicalUrl ) {
			$config->canonicalServerUrl = $wgOAuthAuthenticationCanonicalUrl;
		}
		// Optional clean url here (i.e., to work with mobile), otherwise the
		// base url just has /authorize& added
		# $config->redirURL = 'http://en.wikipedia.beta.wmflabs.org/wiki/Special:OAuth/authorize?';

		$consumer = new Consumer( $wgOAuthAuthenticationConsumerKey,
			$wgOAuthAuthenticationConsumerSecret );
		$config->setConsumer( $consumer );

		return $config;
	}

}
