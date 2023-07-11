<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2012 Uwe Steinmann
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

require_once("../inc/inc.Settings.php");
require_once("../inc/inc.Utils.php");
require_once("../inc/inc.LogInit.php");
require_once("../inc/inc.Language.php");
require_once("../inc/inc.Init.php");
require_once("../inc/inc.Extension.php");
require_once("../inc/inc.DBInit.php");
require_once("../inc/inc.ClassUI.php");
require_once("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
	echo json_encode($result);
	exit;
}

if (isset($_GET["action"])) $action=$_GET["action"];
else $action=NULL;

if($action == 'add_aro') {
	if (isset($_GET["roleid"])) {
		if(!($role = SeedDMS_Core_Role::getInstance((int) $_GET["roleid"], $dms))) {
			$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
			echo json_encode($result);
			exit;
		}
	} else {
		$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
		echo json_encode($result);
		exit;
	}

} else {
	if (isset($_GET["aroid"])) {
		if(!($aro = SeedDMS_Aro::getInstance((int) $_GET["aroid"], $dms))) {
			$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
			echo json_encode($result);
			exit;
		}
	} else {
		$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
		echo json_encode($result);
		exit;
	}

	if (isset($_GET["acoid"])) {
		if(!($aco = SeedDMS_Aco::getInstance((int) $_GET["acoid"], $dms))) {
			$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
			echo json_encode($result);
			exit;
		}
	} else {
		$result = array('type'=>'error', 'msg'=>getMLText("access_denied"));
		echo json_encode($result);
		exit;
	}
}

switch($action) {
case "toggle_permission":
	$acl = new SeedDMS_Acl($dms);
	if($acl->toggle($aro, $aco))
		$result = array('type'=>'success', 'msg'=>getMLText('success_toogle_permission'));
	else
		$result = array('type'=>'error', 'msg'=>getMLText('error_toogle_permission'));
	header('Content-Type: application/json');
	echo json_encode($result);
	break;
case "add_permission":
	$acl = new SeedDMS_Acl($dms);
	if($acl->add($aro, $aco))
		$result = array('type'=>'success', 'msg'=>getMLText('success_add_permission'));
	else
		$result = array('type'=>'error', 'msg'=>getMLText('error_add_permission'));
	header('Content-Type: application/json');
	echo json_encode($result);
	break;
case "remove_permission":
	$acl = new SeedDMS_Acl($dms);
	if($acl->remove($aro, $aco))
		$result = array('type'=>'success', 'msg'=>getMLText('success_remove_permission'));
	else
		$result = array('type'=>'error', 'msg'=>getMLText('error_remove_permission'));
	header('Content-Type: application/json');
	echo json_encode($result);
	break;
case "add_aro":
	if(SeedDMS_Aro::getInstance($role, $dms)) {
		$result = array('type'=>'success', 'msg'=>getMLText('success_add_aro'));
	} else {
		$result = array('type'=>'error', 'msg'=>getMLText('error_add_aro'));
	}
	header('Content-Type: application/json');
	echo json_encode($result);
	break;
}

