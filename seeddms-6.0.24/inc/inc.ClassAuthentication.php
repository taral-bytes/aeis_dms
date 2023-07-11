<?php
/**
 * Implementation of user authentication
 *
 * @category  DMS
 * @package   SeedDMS
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2010-2016 Uwe Steinmann
 * @license   GPL 2
 * @version   @package_version@
 * @link      https://www.seeddms.org
 */

/**
 * Abstract class to authenticate user
 *
 * @category  DMS
 * @package   SeedDMS
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2010-2016 Uwe Steinmann
 * @license   GPL 2
 * @version   Release: @package_version@
 * @link      https://www.seeddms.org
 */
abstract class SeedDMS_Authentication
{
	/**
	 * Do Authentication
	 *
	 * This function must check the username and login. If authentication succeeds
	 * the user object otherwise false must be returned. If authentication fails
	 * the number of failed logins should be incremented and account disabled.
	 *
	 * @param string $username name of user to authenticate
	 * @param string $password password of user to authenticate
	 *
	 * @return object|false user object if authentication was successful
	 * otherwise false
	 */
	abstract function authenticate($username, $password);
}
