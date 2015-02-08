<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "OAuth extension\n";
	exit( 1 ) ;
}

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'OAuthAuthentication',
	'descriptionmsg' => 'oauthauth-desc',
	'author'         => array( 'Chris Steipp' ),
	'url'            => 'https://www.mediawiki.org/wiki/Extension:OAuthAuthentication',
	'version'        => '0.1.0'
);

/**
 * Must be configured in LocalSettings.php!
 * The OAuth special page on the wiki. Passing the title as a parameter
 * is usually more reliable E.g., http://en.wikipedia.org/w/index.php?title=Special:OAuth
 */
$wgOAuthAuthenticationUrl = null;

/**
 * Must be configured in LocalSettings.php!
 * The Key and Secret that were generated for you when you registered
 * your consumer. RSA private key isn't currently supported.
 */
$wgOAuthAuthenticationConsumerKey = null;
$wgOAuthAuthenticationConsumerSecret = null;

/**
 * Optionally set the Canonical url that the server will return,
 * if it's different from the OAuth endpoint. OAuth will use
 * wgCannonicalServer when generating the identity JWT, and this
 * code will compare the iss to this value, or $wgOAuthAuthenticationUrl
 * if this isn't set.
 */
$wgOAuthAuthenticationCanonicalUrl = null;

/**
 * Allow usurpation of accounts. If accounts on the OAuth provider have the same
 * name as an already created local account, this flag decides if the user is allowed
 * to login, or if the login will fail with an error message.
 */
$wgOAuthAuthenticationAccountUsurpation = false;

/**
 * Only allow creation/login of usernames that are on a whitelist. Setting this to
 * false allows any username to register and login.
 */
$wgOAuthAuthenticationUsernameWhitelist = false;

/**
 * Only allow creation/login of users who are in groups on the remote wiki. Setting
 * this to false allows any username to register and login.
 */
$wgOAuthAuthenticationGroupWhitelist = false;

/**
 * Allow local account creation. Set this to false if you only want
 * to use remote accounts.
 * Note: Once local accounts exist, this extension will not prevent
 * them from logging in.
 */
$wgOAuthAuthenticationAllowLocalUsers = true;

/**
 * A simple text string, naming the remote wiki (used for text like, "Login on <wikiname>". If
 * this is false, a generic "Remote OAuth Wiki" is used, which users may not understand.
 */
$wgOAuthAuthenticationRemoteName = false;

/**
 * Max age that a session can go without re-validating the user's identity.
 */
$wgOAuthAuthenticationMaxIdentityAge = 3600;

/**
 * If $wgOAuthAuthenticationUrl uses https, do we validate the certificate?
 * This should always be true in production, but sometimes useful to disable
 * while testing.
 */
$wgOAuthAuthenticationValidateSSL = true;

$dir = __DIR__;
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\SpecialOAuthLogin'] = "$dir/specials/SpecialOAuthLogin.php";
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\Config'] = "$dir/utils/Config.php";
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\Exception'] = "$dir/utils/Exception.php";
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\Hooks'] = "$dir/utils/Hooks.php";
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\Policy'] = "$dir/utils/Policy.php";
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\OAuthExternalUser'] = "$dir/utils/OAuthExternalUser.php";
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\AuthenticationHandler'] = "$dir/handlers/AuthenticationHandler.php";
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\OAuth1Handler'] = "$dir/handlers/OAuth1Handler.php";
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\SessionStore'] = "$dir/store/SessionStore.php";
$wgAutoloadClasses['MediaWiki\Extensions\OAuthAuthentication\PhpSessionStore'] = "$dir/store/PhpSessionStore.php";

## i18n
$wgMessagesDirs['OAuthAuthentication'] = "$dir/i18n";
#$messagesFiles['OAuthAuthentication'] = "$langDir/OAuthAuthentication.alias.php";
$wgExtensionMessagesFiles['SpecialOAuthLoginNoTranslate'] = "$dir/OAuthAuthentication.notranslate-alias.php";



## Use mwoauth-php. Cool Kids can use composer to do this.
$wgAutoloadClasses['MWOAuthClientConfig'] = "$dir/libs/mwoauth-php/MWOAuthClient.php";
$wgAutoloadClasses['MWOAuthClient'] = "$dir/libs/mwoauth-php/MWOAuthClient.php";
$wgAutoloadClasses['OAuthToken'] = "$dir/libs/mwoauth-php/OAuth.php";


$wgSpecialPages['OAuthLogin'] = 'MediaWiki\Extensions\OAuthAuthentication\SpecialOAuthLogin';

$wgHooks['PersonalUrls'][] = 'MediaWiki\Extensions\OAuthAuthentication\Hooks::onPersonalUrls';
$wgHooks['PostLoginRedirect'][] = 'MediaWiki\Extensions\OAuthAuthentication\Hooks::onPostLoginRedirect';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'MediaWiki\Extensions\OAuthAuthentication\Hooks::onLoadExtensionSchemaUpdates';
$wgHooks['GetPreferences'][] = 'MediaWiki\Extensions\OAuthAuthentication\Hooks::onGetPreferences';
$wgHooks['AbortNewAccount'][] = 'MediaWiki\Extensions\OAuthAuthentication\Hooks::onAbortNewAccount';
$wgHooks['UserLoadAfterLoadFromSession'][] = 'MediaWiki\Extensions\OAuthAuthentication\Hooks::onUserLoadAfterLoadFromSession';

$wgHooks['UnitTestsList'][] = function( array &$files ) {
	$directoryIterator = new \RecursiveDirectoryIterator( __DIR__ . '/tests/' );
	foreach ( new \RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
		if ( substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
			$files[] = $fileInfo->getPathname();
		}
	}
	return true;
};




