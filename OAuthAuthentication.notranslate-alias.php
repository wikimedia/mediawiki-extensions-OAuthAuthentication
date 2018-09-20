<?php
/**
 * Aliases for special page which the user shouldn't access directly, so
 * no need to translate (and translation will hurt the cache).
 *
 * Do not add this file to translatewiki.
 *
 * @file
 * @ingroup Extensions
 */

$specialPageAliases = [];

/** English (English) */
$specialPageAliases['en'] = [
	// Localizing Special:CentralAutoLogin causes issues (bug 54195) and is of
	// miniscule benefit to users, so don't do so.
	'OAuthLogin' => [ 'OAuthLogin' ],
];
