<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

/**
 * @covers \MediaWiki\Extensions\OAuthAuthentication\Config
 *
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
