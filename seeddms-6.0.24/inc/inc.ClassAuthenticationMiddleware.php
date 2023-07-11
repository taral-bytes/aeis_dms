<?php
/* Middleware for authentication based on session */
class SeedDMS_Auth_Middleware_Session { /* {{{ */

	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	/**
	 * Example middleware invokable class
	 *
	 * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
	 * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
	 * @param  callable                                 $next     Next middleware
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function __invoke($request, $response, $next) {
		// $this->container has the DI
		$dms = $this->container->dms;
		$settings = $this->container->config;
		$logger = $this->container->logger;
		$userobj = null;
		if($this->container->has('userobj'))
				$userobj = $this->container->userobj;

		if($userobj) {
				$response = $next($request, $response);
				return $response;
		}

		$logger->log("Invoke middleware for method ".$request->getMethod()." on '".$request->getUri()->getPath()."'", PEAR_LOG_INFO);
		require_once("inc/inc.ClassSession.php");
		$session = new SeedDMS_Session($dms->getDb());
		if (isset($_COOKIE["mydms_session"])) {
			$dms_session = $_COOKIE["mydms_session"];
			$logger->log("Session key: ".$dms_session, PEAR_LOG_DEBUG);
			if(!$resArr = $session->load($dms_session)) {
				/* Delete Cookie */
				setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot);
				$logger->log("Session for id '".$dms_session."' has gone", PEAR_LOG_ERR);
				return $response->withStatus(403);
			}

			/* Load user data */
			$userobj = $dms->getUser($resArr["userID"]);
			if (!is_object($userobj)) {
				/* Delete Cookie */
				setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot);
				if($settings->_enableGuestLogin) {
					if(!($userobj = $dms->getUser($settings->_guestID)))
						return $response->withStatus(403);
				} else
					return $response->withStatus(403);
			}
			if($userobj->isAdmin()) {
				if($resArr["su"]) {
					if(!($userobj = $dms->getUser($resArr["su"])))
						return $response->withStatus(403);
				}
			}
			$dms->setUser($userobj);
		} else {
			return $response->withStatus(403);
		}
		$this->container['userobj'] = $userobj;

		$response = $next($request, $response);
		return $response;
	}
} /* }}} */
