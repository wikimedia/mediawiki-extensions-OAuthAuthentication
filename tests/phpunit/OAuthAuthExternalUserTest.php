<?php

namespace MediaWiki\Extension\OAuthAuthentication;

/**
 * @coversDefaultClass \MediaWiki\Extension\OAuthAuthentication\OAuthExternalUser
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
		$this->assertSame( 'ExUser', $exUser->getName() );
		$this->assertSame( 30, $exUser->getLocalId() );
	}

	/**
	 * @covers ::newFromRemoteId
	 * @covers ::__construct
	 * @covers ::getName
	 */
	public function testNewFromRemoteId() {
		// We added remoteId 120 in parent class
		$exUser = OAuthExternalUser::newFromRemoteId( 120, $this->userName, $this->db );
		$this->assertInstanceOf( OAuthExternalUser::class, $exUser );
		$this->assertSame( $this->userName, $exUser->getName() );
	}

}
