<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2016 Uwe Steinmann
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

if(!isset($settings))
	require_once("../inc/inc.Settings.php");
require_once("inc/inc.Utils.php");
require_once("inc/inc.LogInit.php");
require_once("inc/inc.Language.php");
require_once("inc/inc.Init.php");
require_once("inc/inc.Extension.php");
require_once("inc/inc.DBInit.php");
require_once("inc/inc.ClassUI.php");
require_once("inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if (!$accessop->check_view_access($view, $_GET)) {
	UI::exitError(getMLText("folder_title", array("foldername" => '')),getMLText("access_denied"));
}

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}
$folderid = $_GET["folderid"];
$folder = $dms->getFolder($folderid);
if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

if ($folder->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))),getMLText("access_denied"));
}

if($settings->_quota > 0) {
	$remain = checkQuota($user);
	if ($remain < 0) {
		UI::exitError(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))),getMLText("quota_exceeded", array('bytes'=>SeedDMS_Core_File::format_filesize(abs($remain)))));
	}
}

if($settings->_libraryFolder) {
	$libfolder = $dms->getFolder($settings->_libraryFolder);
	if (!is_object($libfolder) || $libfolder->getAccessMode($user) < M_READ) {
		$libfolder = null;
	}
} else {
	$libfolder = null;
}

if($view) {
	$view->setParam('folder', $folder);
	$view->setParam('strictformcheck', $settings->_strictFormCheck);
	$view->setParam('nodocumentformfields', $settings->_noDocumentFormFields);
	$view->setParam('enablelargefileupload', $settings->_enableLargeFileUpload);
	$view->setParam('enablemultiupload', $settings->_enableMultiUpload);
	$view->setParam('enableadminrevapp', $settings->_enableAdminRevApp);
	$view->setParam('enableownerrevapp', $settings->_enableOwnerRevApp);
	$view->setParam('enableselfrevapp', $settings->_enableSelfRevApp);
	$view->setParam('enablereceiptworkflow', $settings->_enableReceiptWorkflow);
	$view->setParam('enableadminreceipt', $settings->_enableAdminReceipt);
	$view->setParam('enableownerreceipt', $settings->_enableOwnerReceipt);
	$view->setParam('enableselfreceipt', $settings->_enableSelfReceipt);
	$view->setParam('libraryfolder', $libfolder);
	$view->setParam('dropfolderdir', $settings->_dropFolderDir);
	$view->setParam('dropfolderfile', isset($_REQUEST["dropfolderfileform1"]) ?$_REQUEST["dropfolderfileform1"] : '');
	$view->setParam('workflowmode', $settings->_workflowMode);
	$view->setParam('presetexpiration', $settings->_presetExpirationDate);
	$view->setParam('sortusersinlist', $settings->_sortUsersInList);
	$view->setParam('defaultposition', $settings->_defaultDocPosition);
	$view->setParam('orderby', $settings->_sortFoldersDefault);
	$view->setParam('accessobject', $accessop);
	$view($_GET);
	exit;
}
