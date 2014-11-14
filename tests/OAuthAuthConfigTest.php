<?php
/**
 * @group OAuthAuthentication
 */
class OAuthAuthConfigTest extends OAuthAuthDBTest {

	public function testGetDefaultConfigAndToken() {
		list( $config, $token ) = \MediaWiki\Extensions\OAuthAuthentication\Config::getDefaultConfigAndToken();
		$this->assertInstanceOf( 'MWOAuthClientConfig', $config );
		$this->assertInstanceOf( 'OAuthToken', $token );
	}

}
