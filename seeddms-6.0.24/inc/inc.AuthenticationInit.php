<?php
/**
 * Create authentication service
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010-2022 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once('inc.ClassAuthenticationService.php');
require_once('inc.ClassDbAuthentication.php');
require_once('inc.ClassLdapAuthentication.php');

global $logger;
$authenticator = new SeedDMS_AuthenticationService($logger, $settings);

if(isset($GLOBALS['SEEDDMS_HOOKS']['authentication'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['authentication'] as $authenticationObj) {
		if(method_exists($authenticationObj, 'preAddService')) {
			$authenticationObj->preAddService($dms, $authenticator);
		}
	}
}

$authenticator->addService(new SeedDMS_DbAuthentication($dms, $settings), 'db');
if(isset($settings->_ldapHost) && strlen($settings->_ldapHost)>0) {
	$authenticator->addService(new SeedDMS_LdapAuthentication($dms, $settings), 'ldap');
}

if(isset($GLOBALS['SEEDDMS_HOOKS']['authentication'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['authentication'] as $authenticationObj) {
		if(method_exists($authenticationObj, 'postAddService')) {
			$authenticationObj->postAddService($dms, $authenticator);
		}
	}
}

