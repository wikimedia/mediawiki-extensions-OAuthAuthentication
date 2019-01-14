<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

use MediaWiki\OAuthClient\ClientConfig;

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
		$this->assertInstanceOf( ClientConfig::class, $config );
	}

}
