<?php
/**
 * Implementation of user authentication
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2016 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once "inc.ClassAuthentication.php";

/**
 * Abstract class to authenticate user against ѕeeddms database
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_DbAuthentication extends SeedDMS_Authentication {

  var $dms;

  var $settings;

  public function __construct($dms, $settings) { /* {{{ */
    $this->dms = $dms;
    $this->settings = $settings;
  } /* }}} */

	/**
	 * Do Authentication
	 *
	 * @param string $username
	 * @param string $password
	 * @return object|boolean user object if authentication was successful otherwise false
	 */
	public function authenticate($username, $password) { /* {{{ */
		$dms = $this->dms;

		// Try to find user with given login.
		if($user = $dms->getUserByLogin($username)) {
			$userid = $user->getID();

			// Check if password matches
			if (!seed_pass_verify($password, $user->getPwd())) {
				$user = null;
			}
		}

		return $user;
	} /* }}} */
}
