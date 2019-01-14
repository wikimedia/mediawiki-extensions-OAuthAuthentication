<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

/**
 * @coversDefaultClass \MediaWiki\Extensions\OAuthAuthentication\OAuthExternalUser
 *
 * @group OAuthAuthentication
 * @group Database
 */
class OAuthAuthExternalUserTest extends OAuthAuthDBTest {

	/**
	 * @covers ::__construct
	 * @covers ::getName
	 * @covers ::getLocalId
	 */
	public function testExternalUser() {
		$exUser = new OAuthExternalUser( 20, 30, 'ExUser' );
		$this->assertEquals( 'ExUser', $exUser->getName() );
		$this->assertEquals( 30, $exUser->getLocalId() );
	}

	/**
	 * @covers ::newFromRemoteId
	 * @covers ::__construct
	 * @covers ::getName
	 */
	public function testNewFromRemoteId() {
		// We added remoteId 120 in parent class
		$exUser = OAuthExternalUser::newFromRemoteId( 120, $this->userName, $this->db );
		$this->assertInstanceOf( 'MediaWiki\Extensions\OAuthAuthentication\OAuthExternalUser', $exUser );
		$this->assertEquals( $this->userName, $exUser->getName() );
	}

}
