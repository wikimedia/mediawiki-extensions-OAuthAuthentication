<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

class OAuthExternalUser {

	// Local user_id
	private $userId;

	// Remote Username
	private $username;

	// Remote unique id
	private $remoteId;

	// OAuth Access Token
	private $accessToken = null;

	// Timestamp of last identity validation
	private $identifyTS = null;

	public function __construct( $rid, $uid, $name, $accessKey = '', $accessSecret = '', $idts = null ) {
		$this->remoteId = $rid;
		$this->userId = $uid; // OIDC specifies this is unique for the IdP
		$this->username = $name;

		if ( $accessKey && $accessSecret ) {
			$this->accessToken = new \OAuthToken( $accessKey, $accessSecret );
		}

		$this->identifyTS = $idts;
	}

	public static function newFromRemoteId( $rid, $username, \DatabaseBase $db ) {
		$row = $db->selectRow(
			'oauthauth_user',
			array( 'oaau_rid', 'oaau_uid', 'oaau_username', 'oaau_access_token',
				'oaau_access_secret', 'oaau_identify_timestamp' ),
			array( 'oaau_rid' => $rid ),
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

	public static function newFromUser( \User $user, \DatabaseBase $db ) {
		$row = $db->selectRow(
			'oauthauth_user',
			array( 'oaau_rid', 'oaau_uid', 'oaau_username', 'oaau_access_token',
				'oaau_access_secret', 'oaau_identify_timestamp' ),
			array( 'oaau_username' => $user->getName() ),
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

	public function addToDatabase( \DatabaseBase $db ) {
		$row = array(
			'oaau_rid' => $this->remoteId,
			'oaau_uid' => $this->userId,
			'oaau_username' => $this->username,
		);

		if ( $this->accessToken ) {
			$row += array(
				'oaau_access_token' => $this->accessToken->key,
				'oaau_access_secret' => $this->accessToken->secret,
			);
		}

		if ( $this->identifyTS ) {
			$row += array(
				'oaau_identify_timestamp' => $db->timestampOrNull( (string)$this->identifyTS ),
			);
		}

		$db->insert(
			'oauthauth_user',
			$row,
			__METHOD__
		);
	}

	public function updateInDatabase( \DatabaseBase $db ) {
		if ( !$this->userId > 0 ) {
			throw new Exception( 'Error updating External User that isn\'t in the DB' );
		}
		$row = array(
			'oaau_rid' => $this->remoteId,
			'oaau_username' => $this->username,
		);

		if ( $this->accessToken ) {
			$row += array(
				'oaau_access_token' => $this->accessToken->key,
				'oaau_access_secret' => $this->accessToken->secret,
			);
		}

		if ( $this->identifyTS ) {
			$row += array(
				'oaau_identify_timestamp' => $db->timestampOrNull( (string)$this->identifyTS ),
			);
		}

		$db->update(
			'oauthauth_user',
			/* SET */ $row,
			/* WHERE */ array( 'oaau_uid' => $this->userId ),
			__METHOD__
		);

	}

	public function removeAccessTokens( \DatabaseBase $db ) {
		if ( !$this->userId > 0 ) {
			throw new Exception( 'Error updating External User that isn\'t in the DB' );
		}
		$db->update(
			'oauthauth_user',
			array(
				'oaau_access_token' => '',
				'oaau_access_secret' => '',
			),
			array( 'oaau_uid' => $this->userId ),
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

	public function setAccessToken( \OAuthToken $accessToken ) {
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
}
