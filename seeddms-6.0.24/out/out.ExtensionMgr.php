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
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$reposurl = $settings->_repositoryUrl;

$v = new SeedDMS_Version;
$extmgr = new SeedDMS_Extension_Mgr($settings->_rootDir."/ext", $settings->_cacheDir, $reposurl);
if(isset($_GET['currenttab']))
	$currenttab = $_GET['currenttab'];
else
	$currenttab = 'installed';
if(isset($_GET['extensionname']))
	$extname = $_GET['extensionname'];
else
	$extname = '';

if($view) {
	$view->setParam('httproot', $settings->_httpRoot);
	$view->setParam('extdir', $settings->_rootDir."/ext");
	$view->setParam('version', $v);
	$view->setParam('accessobject', $accessop);
	$view->setParam('extmgr', $extmgr);
	$view->setParam('currenttab', $currenttab);
	$view->setParam('extname', $extname);
	$view->setParam('reposurl', $reposurl);
	$view($_GET);
	exit;
}
