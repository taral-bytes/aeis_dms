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

if(isset($_GET['action']) && $_GET['action'] == 'subtree') {
	if (!isset($_GET["node"]) || !is_numeric($_GET["node"]) || intval($_GET["node"])<1) {
		$node = $dms->getRootFolder();
	} else {
		$node = $dms->getFolder(intval($_GET["node"]));
	}

	if (!is_object($node)) {
		UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))), getMLText("invalid_folder_id"));
	}
} else {
	$folderid = intval($_GET["folderid"]);
	$folder = $dms->getFolder($folderid);
	$form = preg_replace('/[^A-Za-z0-9_]+/', '', $_GET["form"]);
	if(isset($_GET['partialtree'])) {
		$partialtree = intval($_GET['partialtree']);
	} else {
		$partialtree = 0;
	}
}

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
if($view) {
	$view->setParam('orderby', $settings->_sortFoldersDefault);
	if(isset($_GET['action']) && $_GET['action'] == 'subtree') {
		$view->setParam('node', $node);
	} else {
		$view->setParam('folder', $folder);
		$view->setParam('form', $form);
		$view->setParam('partialtree', $partialtree);
	}
	$view($_GET);
	exit;
}
