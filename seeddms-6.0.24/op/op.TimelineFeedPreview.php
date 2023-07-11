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
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");
//include("../inc/inc.BasicAuthentication.php");

if(empty($_GET['hash']))
	exit;

$token = new SeedDMS_JwtToken($settings->_encryptionKey);
if(!($tokenstr = $token->jwtDecode($_GET['hash'])))
	exit;

$tokendata = json_decode($tokenstr, true);

if (!isset($tokendata['d']) || !is_numeric($tokendata['d'])) {
	exit;
}

$document = $dms->getDocument($tokendata['d']);
if (!is_object($document)) {
	exit;
}

if (!isset($tokendata['u']) || !is_numeric($tokendata['u'])) {
	exit;
}

$user = $dms->getUser($tokendata['u']);
if (!is_object($user)) {
	exit;
}

if ($document->getAccessMode($user) < M_READ) {
	exit;
}

if (!isset($tokendata['v']) || !is_numeric($tokendata['v'])) {
	exit;
}

$controller = Controller::factory('Preview', array('dms'=>$dms, 'user'=>$user));
$controller->setParam('width', !empty($tokendata["w"]) ? $tokendata["w"] : null);
$controller->setParam('document', $document);
$controller->setParam('version', $tokendata['v']);
$controller->setParam('type', 'version');
if(!$controller->run()) {
	header('Content-Type: image/svg+xml');
	readfile('../views/'.$theme.'/images/empty.svg');
	exit;
}
