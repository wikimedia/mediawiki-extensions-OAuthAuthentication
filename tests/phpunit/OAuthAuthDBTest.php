<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

/**
 * @covers \MediaWiki\Extensions\OAuthAuthentication\OAuthExternalUser
 *
 * @group OAuthAuthentication
 * @group Database
 */
class OAuthAuthDBTest extends \MediaWikiTestCase {

	/**
	 * @var string
	 */
	protected $userName;

	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );
		$this->tablesUsed[] = 'oauthauth_user';
	}

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
