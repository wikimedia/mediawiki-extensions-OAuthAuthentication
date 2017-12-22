<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

use MediaWiki\OAuthClient\Token;
use Wikimedia\Rdbms\IDatabase;

class OAuthExternalUser {

	// Local user_id
	private $userId;

	// Remote Username
	private $username;

	// Remote Realname
	private $realname;

	// Remote Email
	private $email;

	// Remote unique id
	private $remoteId;

	// OAuth Access Token
	private $accessToken = null;

	// Timestamp of last identity validation
	private $identifyTS = null;

	public function __construct(
		$rid, $uid, $name, $accessKey = '', $accessSecret = '', $idts = null
	) {
		$this->remoteId = $rid;
		$this->userId = $uid; // OIDC specifies this is unique for the IdP
		$this->username = $name;

		if ( $accessKey && $accessSecret ) {
			$this->accessToken = new Token( $accessKey, $accessSecret );
		}

		$this->identifyTS = $idts;
	}

	public static function newFromRemoteId( $rid, $username, IDatabase $db ) {
		$row = $db->selectRow(
			'oauthauth_user',
			[ 'oaau_rid', 'oaau_uid', 'oaau_username', 'oaau_access_token',
				'oaau_access_secret', 'oaau_identify_timestamp' ],
			[ 'oaau_rid' => $rid ],
			__METHOD__
		);

		if ( !$row ) {
			return new self( $rid, 0, $username );
		} else {
			return new self( $rid, $row->oaau_uid, $row->oaau_username,
				$row->oaau_access_token, $row->oaau_access_secret,
				$row->oaau_identify_timestamp );
		}
	}

	public static function newFromUser( \User $user, IDatabase $db ) {
		$row = $db->selectRow(
			'oauthauth_user',
			[ 'oaau_rid', 'oaau_uid', 'oaau_username', 'oaau_access_token',
				'oaau_access_secret', 'oaau_identify_timestamp' ],
			[ 'oaau_username' => $user->getName() ],
			__METHOD__
		);

		if ( !$row ) {
			return false;
		} else {
			return new self( $row->oaau_rid, $row->oaau_uid, $row->oaau_username,
				$row->oaau_access_token, $row->oaau_access_secret,
				$row->oaau_identify_timestamp );
		}
	}

	public function addToDatabase( IDatabase $db ) {
		$row = [
			'oaau_rid' => $this->remoteId,
			'oaau_uid' => $this->userId,
			'oaau_username' => $this->username,
		];

		if ( $this->accessToken ) {
			$row += [
				'oaau_access_token' => $this->accessToken->key,
				'oaau_access_secret' => $this->accessToken->secret,
			];
		}

		if ( $this->identifyTS ) {
			$row += [
				'oaau_identify_timestamp' => $db->timestampOrNull( (string)$this->identifyTS ),
			];
		}

		$db->insert(
			'oauthauth_user',
			$row,
			__METHOD__
		);
	}

	public function updateInDatabase( IDatabase $db ) {
		if ( !$this->userId > 0 ) {
			throw new Exception( 'Error updating External User that isn\'t in the DB' );
		}
		$row = [
			'oaau_rid' => $this->remoteId,
			'oaau_username' => $this->username,
		];

		if ( $this->accessToken ) {
			$row += [
				'oaau_access_token' => $this->accessToken->key,
				'oaau_access_secret' => $this->accessToken->secret,
			];
		}

		if ( $this->identifyTS ) {
			$row += [
				'oaau_identify_timestamp' => $db->timestampOrNull( (string)$this->identifyTS ),
			];
		}

		$db->update(
			'oauthauth_user',
			/* SET */ $row,
			/* WHERE */ [ 'oaau_uid' => $this->userId ],
			__METHOD__
		);
	}

	public function removeAccessTokens( IDatabase $db ) {
		if ( !$this->userId > 0 ) {
			throw new Exception( 'Error updating External User that isn\'t in the DB' );
		}
		$db->update(
			'oauthauth_user',
			[
				'oaau_access_token' => '',
				'oaau_access_secret' => '',
			],
			[ 'oaau_uid' => $this->userId ],
			__METHOD__
		);
	}

	public function getName() {
		return $this->username;
	}

	public function getLocalId() {
		return $this->userId;
	}

	public function setLocalId( $uid ) {
		$this->userId = $uid;
	}

	public function attached() {
		return ( $this->userId !== 0 );
	}

	public function setAccessToken( Token $accessToken ) {
		$this->accessToken = $accessToken;
	}

	public function getAccessToken() {
		return $this->accessToken;
	}

	public function setIdentifyTS( \MWTimestamp $ts ) {
		$this->identifyTS = $ts;
	}

	public function getIdentifyTS() {
		return $this->identifyTS;
	}

	public function setRealname( $realname ) {
		$this->realname = $realname;
	}

	public function getRealname() {
		return $this->realname;
	}

	public function setEmail( $email ) {
		$this->email = $email;
	}

	public function getEmail() {
		return $this->email;
	}

}
