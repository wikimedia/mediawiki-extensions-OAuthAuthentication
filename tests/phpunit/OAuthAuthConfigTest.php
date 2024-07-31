<?php

namespace MediaWiki\Extension\OAuthAuthentication;

use MediaWiki\OAuthClient\ClientConfig;

/**
 * @covers \MediaWiki\Extension\OAuthAuthentication\Config
 *
 * @group OAuthAuthentication
 * @group Database
 */
class OAuthAuthConfigTest extends OAuthAuthDBTest {

	public function testGetDefaultConfig() {
		$this->overrideConfigValue( 'OAuthAuthenticationUrl', 'https://example.com/' );
		$config = Config::getDefaultConfig();
		$this->assertInstanceOf( ClientConfig::class, $config );
	}

}
