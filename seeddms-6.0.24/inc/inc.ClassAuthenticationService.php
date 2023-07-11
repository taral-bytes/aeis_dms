<?php
/**
 * Implementation of authentication service
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Implementation of authentication service
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_AuthenticationService {
	/**
	 * List of services for authenticating user
	 */
	protected $services;

	/*
	 * List of servives with errors
	 */
	protected $errors;

	/*
	 * Service for logging
	 */
	protected $logger;

	/*
	 * Configuration
	 */
	protected $settings;

	public function __construct($logger = null, $settings = null) { /* {{{ */
		$this->services = array();
		$this->errors = array();
		$this->logger = $logger;
		$this->settings = $settings;
	} /* }}} */

	public function addService($service, $name='') { /* {{{ */
		if(!$name)
			$name = md5(uniqid());
		$this->services[$name] = $service;
		$this->errors[$name] = true;
	} /* }}} */

	public function getServices() { /* {{{ */
		return $this->services;
	} /* }}} */

	public function getErrors() { /* {{{ */
		return $this->errors;
	} /* }}} */

	public function authenticate($username, $password) { /* {{{ */
		$user = null;
		foreach($this->services as $name => $service) {
			if($this->logger)
				$this->logger->log('Authentication service \''.$name.'\'', PEAR_LOG_INFO);
			$user = $service->authenticate($username, $password);
			if($user === false) {
				$this->errors[$name] = false;
				if($this->logger)
					$this->logger->log('Authentication service \''.$name.'\': Authentication of user \''.$username.'\' failed.', PEAR_LOG_ERR);
				return false;
			}	elseif($user === null) {
				if($this->logger)
					$this->logger->log('Authentication service \''.$name.'\': Authentication of user \''.$username.'\' disregarded.', PEAR_LOG_ERR);
			} else {
				if($this->logger)
					$this->logger->log('Authentication service \''.$name.'\': Authentication of user \''.$username.'\' successful.', PEAR_LOG_INFO);
				$this->errors[$name] = true;
				return $user;
			}
		}
		return $user;
	} /* }}} */
}
