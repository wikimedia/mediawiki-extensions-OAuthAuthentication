<?php
namespace MediaWiki\Extensions\OAuthAuthentication;

/**
 * @group OAuthAuthentication
 */
class OAuthAuthHooksTest extends OAuthAuthDBTest {

	public function testOnPersonalUrls() {
		$this->setMwGlobals( [
			'wgUser' => \User::newFromName( '127.0.0.1', false ),
		] );

		$personal_urls = [ 'login' => [ 'href' => 'fail' ] ];
		$title = new \Title();

		Hooks::onPersonalUrls( $personal_urls, $title );

		$this->assertSame(
			true,
			strpos( $personal_urls['login']['href'], 'Special:OAuthLogin/init' ) !== false
		);
	}

}
