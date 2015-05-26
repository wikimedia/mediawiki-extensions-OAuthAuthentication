<?php
namespace MediaWiki\Extensions\OAuthAuthentication;

/**
 * @group OAuthAuthentication
 */
class OAuthAuthConfigTest extends OAuthAuthDBTest {

	public function testGetDefaultConfigAndToken() {
		list( $config, $token ) = Config::getDefaultConfigAndToken();
		$this->assertInstanceOf( 'MWOAuthClientConfig', $config );
		$this->assertInstanceOf( 'OAuthToken', $token );
	}

}
