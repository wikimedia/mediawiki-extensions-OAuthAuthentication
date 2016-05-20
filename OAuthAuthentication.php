<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'OAuthAuthentication' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['OAuthAuthentication'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['SpecialOAuthLoginNoTranslate'] =
		__DIR__ . "/OAuthAuthentication.notranslate-alias.php";
	/* wfWarn(
		'Deprecated PHP entry point used for OAuthAuthentication extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return;
} else {
	die( 'This version of the OAuthAuthentication extension requires MediaWiki 1.25+' );
}

// Global declarations and documentation kept for IDEs and PHP documentors.
// This code is never executed.

/**
 * Must be configured in LocalSettings.php!
 * The OAuth special page on the wiki. Passing the title as a parameter
 * is usually more reliable E.g., http://en.wikipedia.org/w/index.php?title=Special:OAuth
 */
$wgOAuthAuthenticationUrl = null;

/**
 * Must be configured!
 * The key that was generated for you when you registered
 * your consumer. RSA private key isn't currently supported.
 */
$wgOAuthAuthenticationConsumerKey = null;

/**
 * Must be configured!
 * The secret that was generated for you when you registered
 * your consumer. RSA private key isn't currently supported.
 */
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

/**
 * Callback URL (should point to Special:OAuthLogin/finish). If it is empty, the callback is
 * assumed to be non-dynamic (and thus read from the consumer record of the target wiki).
 * Under normal circumstances this should not be changed.
 */
$wgOAuthAuthenticationCallbackUrl = null;
