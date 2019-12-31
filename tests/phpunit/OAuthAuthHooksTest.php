<?php
namespace MediaWiki\Extensions\OAuthAuthentication;

/**
 * @coversDefaultClass \MediaWiki\Extensions\OAuthAuthentication\Hooks
 *
 * @group OAuthAuthentication
 * @group Database
 */
class OAuthAuthHooksTest extends OAuthAuthDBTest {

	/**
	 * @covers ::onPersonalUrls
	 */
	public function testOnPersonalUrls() {
		$personal_urls = [ 'login' => [ 'href' => 'fail' ] ];

		$title = $this->createMock( \Title::class );
		$user = $this->getMockBuilder( \User::class )
			->setMethods( [ 'getId' ] )
			->getMock();
		$user->method( 'getId' )->willReturn( 0 );

		$skinTemplate = $this->getMockBuilder( \SkinTemplate::class )
			->setMethods( [ 'getUser' ] )
			->getMock();
		$skinTemplate->method( 'getUser' )->willReturn( $user );

		Hooks::onPersonalUrls( $personal_urls, $title, $skinTemplate );

		$this->assertStringContainsString(
			'Special:OAuthLogin/init',
			$personal_urls['login']['href'],
			'Personal urls should include OAuthLogin link'
		);
	}

}
