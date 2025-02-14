<?php

namespace MediaWiki\Extension\OAuthAuthentication;

/**
 * @covers \MediaWiki\Extension\OAuthAuthentication\OAuthExternalUser
 *
 * @group OAuthAuthentication
 * @group Database
 */
class OAuthAuthDBTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @var string
	 */
	protected $userName;

	protected function setUp(): void {
		parent::setUp();

		$user = $this->getTestUser()->getUser();
		$this->userName = $user->getName();
		$exUser = new OAuthExternalUser( 100, $user->getId(), $this->userName );
		$exUser->addToDatabase( $this->db );
	}

	/**
	 * Stub to make sure db handling is working
	 * @coversNothing
	 */
	public function testInit() {
		$this->assertSame( true, true );
	}

}
