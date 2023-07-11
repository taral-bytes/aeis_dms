<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010-2013 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

if(isset($GLOBALS['SEEDDMS_HOOKS']['initDB'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['initDB'] as $hookObj) {
		if (method_exists($hookObj, 'pretInitDB')) {
			$hookObj->preInitDB(array('settings'=>$settings, 'logger'=>$logger));
		}
	}
}

$db = new SeedDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");

if(isset($GLOBALS['SEEDDMS_HOOKS']['initDB'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['initDB'] as $hookObj) {
		if (method_exists($hookObj, 'postInitDB')) {
			$hookObj->postInitDB(array('db'=>$db, 'settings'=>$settings, 'logger'=>$logger));
		}
	}
}

if(isset($GLOBALS['SEEDDMS_HOOKS']['initDMS'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['initDMS'] as $hookObj) {
		if (method_exists($hookObj, 'pretInitDMS')) {
			$hookObj->preInitDMS(array('db'=>$db, 'settings'=>$settings, 'logger'=>$logger));
		}
	}
}

$dms = new SeedDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);

if(!$settings->_doNotCheckDBVersion && !$dms->checkVersion()) {
	echo "Database update needed.";
	if($v = $dms->getDBVersion()) {
		echo " Database has version ".$v['major'].".".$v['minor'].".".$v['subminor']." but this is SeedDMS ".$dms->version.".";
	}
	exit;
}

$dms->setRootFolderID($settings->_rootFolderID);
$dms->setMaxDirID($settings->_maxDirID);

if(isset($GLOBALS['SEEDDMS_HOOKS']['initDMS'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['initDMS'] as $hookObj) {
		if (method_exists($hookObj, 'postInitDMS')) {
			$ret = $hookObj->postInitDMS(array('dms'=>$dms, 'settings'=>$settings, 'logger'=>$logger));
			if($ret === false) {
				echo "Fatal error in postInitDMS Hook. No way to recover.";
				exit;
			}
		}
	}
}

require_once('inc/inc.Tasks.php');
require_once("inc.ConversionInit.php");
require_once('inc.FulltextInit.php');
require_once('inc.AuthenticationInit.php');
require_once("inc.ClassNotificationService.php");
require_once("inc.ClassEmailNotify.php");
require_once('inc.Notification.php');

