<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

/**
 * @group OAuthAuthentication
 * @group Database
 */
class OAuthAuthDBTest extends \MediaWikiTestCase {

	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );
		$this->tablesUsed[] = 'oauthauth_user';
	}

	protected function setUp() {
		parent::setUp();

		// TODO: Setup some test data
		$user = \User::newFromName( 'OAuthUser' );
		if ( $user->idForName() == 0 ) {
			$user->addToDatabase();
			$user->setPassword( 'OAUP@ssword' );
			$user->saveSettings();
		}
		$exUser = new OAuthExternalUser( 100, $user->getId(), 'OAuthUser' );
		$exUser->addToDatabase( $this->db );
	}

	// Stub to make sure db handling is working
	public function testInit() {
		$this->assertSame( true, true );
	}

}
