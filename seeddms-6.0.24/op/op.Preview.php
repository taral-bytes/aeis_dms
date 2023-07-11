<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
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

include("../inc/inc.Settings.php");
include("../inc/inc.Utils.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");
include("../inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));

$documentid = $_GET["documentid"];
if (!isset($documentid) || !is_numeric($documentid) || intval($documentid)<1) {
	exit;
}

$document = $dms->getDocument($documentid);
if (!is_object($document)) {
	exit;
}

if ($document->getAccessMode($user) < M_READ) {
	header('Content-Type: image/svg+xml');
	readfile('../views/'.$theme.'/images/empty.svg');
	exit;
}

$controller->setParam('conversionmgr', $conversionmgr);
$controller->setParam('width', !empty($_GET["width"]) ? $_GET["width"] : null);
$controller->setParam('document', $document);
if(isset($_GET['version'])) {
	$version = $_GET["version"];
	if (!is_numeric($version))
		exit;

	$controller->setParam('action', 'version');
	$controller->setParam('version', $version);
	if(!$controller()) {
		header('Content-Type: image/svg+xml');
		readfile('../views/'.$theme.'/images/empty.svg');
		exit;
	}
	exit;
} elseif(isset($_GET['file'])) {
	$file = $_GET['file'];
	if (!is_numeric($file) || intval($file)<1)
		exit;
	$object = $document->getDocumentFile($file);
	$controller->setParam('action', 'file');
	$controller->setParam('object', $object);
	if(!$controller()) {
		header('Content-Type: image/svg+xml');
		readfile('../views/'.$theme.'/images/empty.svg');
		exit;
	}
	exit;
} else {
	exit;
}
