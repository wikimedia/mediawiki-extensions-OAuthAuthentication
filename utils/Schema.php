<?php

namespace MediaWiki\Extension\OAuthAuthentication;

class Schema implements
	\MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook
{
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable( 'oauthauth_user', __DIR__ . '/../store/oauthauth.sql' );
	}
}
