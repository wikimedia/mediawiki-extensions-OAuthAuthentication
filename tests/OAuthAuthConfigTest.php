<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

/**
 * @group OAuthAuthentication
 * @group Database
 */
class OAuthAuthConfigTest extends OAuthAuthDBTest {

	public function testGetDefaultConfig() {
		$this->setMwGlobals( 'wgOAuthAuthenticationUrl', 'https://example.com/' );
		$config = Config::getDefaultConfig();
		$this->assertInstanceOf( 'MediaWiki\\OAuthClient\\ClientConfig', $config );
	}

}
