<?php
/**
 * @group OAuthAuthentication
 */
class OAuthAuthHooksTest extends OAuthAuthDBTest {

	public function testOnPersonalUrls() {

		$this->setMwGlobals( array(
			'wgUser' => \User::newFromName( '127.0.0.1', false ),
		) );

		$personal_urls = array( 'login' => array( 'href' => 'fail' ) );
		$title = new Title();

		\MediaWiki\Extensions\OAuthAuthentication\Hooks::onPersonalUrls( $personal_urls, $title );

		$this->assertSame(
			true,
			strpos( $personal_urls['login']['href'], 'Special:OAuthLogin/init' ) !== false
		);
	}

}
