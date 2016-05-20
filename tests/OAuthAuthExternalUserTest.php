<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

/**
 * @group OAuthAuthentication
 */
class OAuthAuthExternalUserTest extends OAuthAuthDBTest {

	public function testExternalUser() {
		$exUser = new OAuthExternalUser( 20, 30, 'ExUser' );
		$this->assertEquals( 'ExUser', $exUser->getName() );
		$this->assertEquals( 30, $exUser->getLocalId() );
	}

	public function testNewFromRemoteId() {
		// We added remoteId 120 in parent class
		$exUser = OAuthExternalUser::newFromRemoteId( 120, 'OAuthUser', $this->db );
		$this->assertInstanceOf( 'MediaWiki\Extensions\OAuthAuthentication\OAuthExternalUser', $exUser );
		$this->assertEquals( 'OAuthUser', $exUser->getName() );
	}

}
