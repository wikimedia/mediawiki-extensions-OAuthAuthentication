<?php
namespace MediaWiki\Extension\OAuthAuthentication;

use MediaWiki\Request\FauxRequest;

/**
 * @coversDefaultClass \MediaWiki\Extension\OAuthAuthentication\Hooks
 *
 * @group OAuthAuthentication
 * @group Database
 */
class OAuthAuthHooksTest extends OAuthAuthDBTest {

	/**
	 * @covers ::onSkinTemplateNavigation__Universal
	 */
	public function testOnSkinTemplateNavigation__Universal() {
		$links = [ 'user-menu' => [ 'login' => [ 'href' => 'fail' ] ] ];

		$title = $this->createMock( \Title::class );
		$user = $this->getMockBuilder( \User::class )
			->onlyMethods( [ 'getId' ] )
			->getMock();
		$user->method( 'getId' )->willReturn( 0 );

		$skinTemplate = $this->getMockBuilder( \SkinTemplate::class )
			->onlyMethods( [ 'getUser', 'getTitle', 'getRequest', 'msg' ] )
			->getMock();
		$skinTemplate->method( 'getUser' )->willReturn( $user );
		$skinTemplate->method( 'getTitle' )->willReturn( $title );
		$skinTemplate->method( 'getRequest' )->willReturn( new FauxRequest() );
		$skinTemplate->method( 'msg' )->willReturnCallback( static function ( $msg, $param ) {
			return wfMessage( $msg, $param );
		} );

		$services = $this->getServiceContainer();
		( new Hooks(
			$services->getDBLoadBalancer(),
			$services->getLinkRenderer()
		) )->onSkinTemplateNavigation__Universal( $skinTemplate, $links );

		$this->assertStringContainsString(
			'Special:OAuthLogin/init',
			$links['user-menu']['login']['href'],
			'Personal urls should include OAuthLogin link'
		);
	}

}
