<?php
/**
 * Create notifcation service
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

global $logger;
$notifier = new SeedDMS_NotificationService($logger, $settings);

if(isset($GLOBALS['SEEDDMS_HOOKS']['notification'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['notification'] as $notificationObj) {
		if(method_exists($notificationObj, 'preAddService')) {
			$notificationObj->preAddService($dms, $notifier);
		}
	}
}

if($settings->_enableEmail) {
	$notifier->addService(new SeedDMS_EmailNotify($dms, $settings->_smtpSendFrom, $settings->_smtpServer, $settings->_smtpPort, $settings->_smtpUser, $settings->_smtpPassword), 'email');
}

if(isset($GLOBALS['SEEDDMS_HOOKS']['notification'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['notification'] as $notificationObj) {
		if(method_exists($notificationObj, 'postAddService')) {
			$notificationObj->postAddService($dms, $notifier);
		}
	}
}
