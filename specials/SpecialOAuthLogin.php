<?php

namespace MediaWiki\Extensions\OAuthAuthentication;

class SpecialOAuthLogin extends \UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'OAuthLogin' );
	}


	public function execute( $subpage ) {
		global $wgUser;
		$request = $this->getRequest();

		$this->setHeaders();

		if ( !$this->getUser()->isAnon() ) {
			throw new \ErrorPageError( 'oauthauth-error', 'oauthauth-already-logged-in' );
		}

		$handler = false;
		$session = new PhpSessionStore( $request );

		list( $config, $cmrToken ) = Config::getDefaultConfigAndToken();
		$client = new \MWOAuthClient( $config, $cmrToken );
		$handler = new OAuth1Handler();

		switch ( trim( $subpage ) ) {
			case 'init':

				// Keep around returnto/returntoquery and set with PostLoginRedirect hook
				$session->set(
					'oauth-init-returnto',
					$request->getVal( 'returnto', 'Main_Page' )
				);
				$session->set(
					'oauth-init-returntoquery',
					$request->getVal( 'returntoquery' )
				);

				try {
					$redir = $handler->init(
						$session,
						$client
					);

					$handler->authorize( $this->getRequest()->response(), $redir );

				} catch ( Exception $e ) {
					throw new \ErrorPageError( 'oauthauth-error', $e->getMessage() );
				}
				if ( !$status->isGood() ) {
					throw new \ErrorPageError( 'oauthauth-error', $status->getMessage() );
				}

				break;
			case 'finish':
				try {
					$accessToken = $handler->finish(
						$this->getRequest(),
						$session,
						$client
					);

					$identity = $handler->identify(
						$accessToken,
						$client
					);

					if ( !Policy::checkWhitelists( $identity ) ) {
						throw new \ErrorPageError( 'oauthauth-error', 'oauthauth-nologin-policy' );
					}

					$status = AuthenticationHandler::handleIdentity(
						$this->getRequest(),
						$identity,
						$accessToken
					);

					if ( !$status->isGood() ) {
						throw new \ErrorPageError( 'oauthauth-error', $status->getMessage() );
					}
				} catch ( \ErrorPageError $epe ) {
					throw $epe;
				} catch ( Exception $e ) {
					throw new \ErrorPageError( 'oauthauth-error', $e->getMessage() );
				}

				list( $method, $u ) = $status->getValue();

				$this->getContext()->setUser( $u );
				$wgUser = $u;

				$lp = new \LoginForm();

				// Call LoginForm::successfulCreation() on create, or successfulLogin()
				$lp->$method();
						break;
			default:
				throw new \ErrorPageError( 'oauthauth-error', 'oauthauth-invalid-subpage' );
		}

	}

}
