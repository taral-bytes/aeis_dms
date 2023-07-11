<?php
//    SeedDMS. Document Management System
//    Copyright (C) 2013 Uwe Steinmann
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

include("../inc/inc.Settings.php");
include("../inc/inc.Utils.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Extension.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");
include("../inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));
if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

/* Check if the form data comes for a trusted request */
if(!checkFormKey('extensionmgr')) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_request_token"));
}

if (isset($_POST["action"])) $action=$_POST["action"];
else $action=NULL;

if (isset($_POST["currenttab"])) $currenttab=$_POST["currenttab"];
else $currenttab=NULL;

// Download extension -------------------------------------------------------
if ($action == "download") { /* {{{ */
	if(!$settings->_enableExtensionDownload) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}
	if (!isset($_POST["extname"])) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_id"));
	}
	$extname = trim($_POST["extname"]);
	if (!file_exists($settings->_rootDir.'/ext/'.$extname) ) {
		UI::exitError(getMLText("admin_tools"),getMLText("missing_extension"));
	}
//	$extMgr = new SeedDMS_Extension_Mgr($settings->_rootDir."/ext", $settings->_cacheDir);
	$controller->setParam('extmgr', $extMgr);
	$controller->setParam('extname', $extname);
	if (!$controller()) {
		echo json_encode(array('success'=>false, 'msg'=>'Could not download extension'));
	}
	add_log_line();
} /* }}} */
elseif ($action == "refresh") { /* {{{ */
//	$extMgr = new SeedDMS_Extension_Mgr($settings->_rootDir."/ext", $settings->_cacheDir);
	$extMgr->createExtensionConf();
	$controller->setParam('extmgr', $extMgr);
	if (!$controller()) {
		UI::exitError(getMLText("admin_tools"),$extMgr->getErrorMsg());
	}
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_extension_refresh')));
	add_log_line();
	header("Location:../out/out.ExtensionMgr.php?currenttab=".$currenttab);
} /* }}} */
elseif ($action == "upload") { /* {{{ */
	if(!$settings->_enableExtensionImport) {
		UI::exitError(getMLText("admin_tools"),getMLText("extension_mgr_upload_disabled"));
	}
	if(!$extMgr->isWritableExtDir()) {
		UI::exitError(getMLText("admin_tools"),getMLText("extension_mgr_no_upload"));
	}
	if($_FILES['userfile']['error']) {
		UI::exitError(getMLText("admin_tools"),getMLText("extension_mgr_error_upload"));
	}
	if(!in_array($_FILES['userfile']['type'], array('application/zip', 'application/x-zip-compressed'))) {
		UI::exitError(getMLText("admin_tools"),getMLText("extension_mgr_no_zipfile"));
	}
//	$extMgr = new SeedDMS_Extension_Mgr($settings->_rootDir."/ext", $settings->_cacheDir);
	$controller->setParam('extmgr', $extMgr);
	$controller->setParam('file', $_FILES['userfile']['tmp_name']);
	if (!$controller()) {
		UI::exitError(getMLText("admin_tools"),$controller->getErrorMsg());
	}
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_extension_import')));
	add_log_line();
	header("Location:../out/out.ExtensionMgr.php?currenttab=".$currenttab);
} /* }}} */
elseif ($action == "import") { /* {{{ */
	if(!$settings->_enableExtensionImportFromRepository) {
		UI::exitError(getMLText("admin_tools"),getMLText("extension_mgr_upload_disabled"));
	}
	if(!$_POST['url']) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
	$file = $extMgr->getExtensionFromRepository($_POST['url']);
	/*
	$reposurl = $settings->_repositoryUrl;
	$content = file_get_contents($reposurl."/".$_POST['url']);
	$file = tempnam(sys_get_temp_dir(), '');
	file_put_contents($file, $content);
	 */

//	$extMgr = new SeedDMS_Extension_Mgr($settings->_rootDir."/ext", $settings->_cacheDir);
	$controller->setParam('extmgr', $extMgr);
	$controller->setParam('file', $file);
	$controller->setParam('action', 'upload');
	if (!$controller()) {
		unlink($file);
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
	unlink($file);
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_extension_upload')));
	add_log_line();
	header("Location:../out/out.ExtensionMgr.php?currenttab=".$currenttab);
} /* }}} */
elseif ($action == "getlist") { /* {{{ */
	$v = new SeedDMS_Version();
	$controller->setParam('extmgr', $extMgr);
	$controller->setParam('forceupdate', (isset($_POST['forceupdate']) && $_POST['forceupdate']) ? true : false);
	$controller->setParam('version', $v->version());
	if (!$controller()) {
		$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('error_extension_getlist').":".$controller->getErrorMsg(), 'timeout'=>5000));
	} else {
		$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_extension_getlist')));
	}
	add_log_line();
	header("Location:../out/out.ExtensionMgr.php?currenttab=".$currenttab);
} /* }}} */
elseif ($action == "toggle") { /* {{{ */
	if (!isset($_POST["extname"])) {
		echo json_encode(array('success'=>false, 'msg'=>getMLText('extension_missing_name')));
	}
	$extname = trim($_POST["extname"]);
	if (!file_exists($settings->_rootDir.'/ext/'.$extname) ) {
		UI::exitError(getMLText("admin_tools"),getMLText("missing_extension"));
	}
	$controller->setParam('extmgr', $extMgr);
	$controller->setParam('extname', $extname);
	if (!$controller()) {
		echo json_encode(array('success'=>false, 'msg'=>getMLText('extinsion_toggle_error')));
	} else {
		if($settings->extensionIsDisabled($extname))
			echo json_encode(array('success'=>true, 'msg'=>getMLText('extension_is_off_now')));
		else {
			$ret = $extMgr->migrate($extname, $settings, $dms, $logger);
			if($ret !== null) {
				if($ret === true)
					echo json_encode(array('success'=>true, 'msg'=>getMLText('extension_migration_success')));
				else
					echo json_encode(array('success'=>true, 'msg'=>getMLText('extension_migration_error')));
			} else {
				echo json_encode(array('success'=>true, 'msg'=>getMLText('extension_is_on_now')));
			}
		}
	}
	add_log_line();
} /* }}} */


