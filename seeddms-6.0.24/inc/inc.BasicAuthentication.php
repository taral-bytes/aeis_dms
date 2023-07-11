<?php
/**
 * Do authentication of users and session management
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once("inc.Utils.php");
require_once("inc.ClassNotificationService.php");
require_once("inc.ClassEmailNotify.php");
require_once("inc.ClassSession.php");
require_once("inc.ClassAccessOperation.php");

if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="'.$settings->_siteName.'"');
	header('HTTP/1.0 401 Unauthorized');
	echo getMLText('cancel_basic_authentication');
	exit;
} else {
	if(!($user = $authenticator->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))) {
		header('WWW-Authenticate: Basic realm="'.$settings->_siteName.'"');
		header('HTTP/1.0 401 Unauthorized');
		echo getMLText('cancel_basic_authentication');
		exit;
	}
}

/* Clear login failures if login was successful */
$user->clearLoginFailures();

$dms->setUser($user);

require_once('inc/inc.Notification.php');

