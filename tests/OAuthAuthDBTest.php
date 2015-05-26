<?php
namespace MediaWiki\Extensions\OAuthAuthentication;
/**
 * @group OAuthAuthentication
 */
class OAuthAuthDBTest extends \MediaWikiTestCase {

	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );
	}

	protected function setUp() {
		parent::setUp();
		if ( $this->db->tableExists( 'oauthauth_user' ) ) {
			$this->db->dropTable( 'oauthauth_user' );
		}
		$this->db->sourceFile( __DIR__ . '/../store/oauthauth.sql' );

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

	protected function tearDown() {
		$this->db->dropTable( 'oauthauth_user' );
		parent::tearDown();
	}

	public function needsDB() {
		return true;
	}

	// Stub to make sure db handling is working
	public function testInit() {
		$this->assertSame( true, true );
	}

}
