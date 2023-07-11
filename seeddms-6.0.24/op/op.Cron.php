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

require_once("../inc/inc.Settings.php");
require_once("../inc/inc.Utils.php");
require_once("../inc/inc.LogInit.php");
require_once("../inc/inc.Language.php");
require_once("../inc/inc.Init.php");
require_once("../inc/inc.Extension.php");
require_once("../inc/inc.DBInit.php");
require_once("../inc/inc.ClassController.php");
require_once("../inc/inc.Scheduler.php");
require_once("../inc/inc.BasicAuthentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));

if($user->getLogin() != 'cli_scheduler') {
	header('WWW-Authenticate: Basic realm="'.$settings->_siteName.'"');
	header('HTTP/1.0 401 Unauthorized');
	echo "Wrong user";
	exit;
}
$mode = 'list';
if(!empty($_GET['mode']) && in_array($_GET['mode'], array('list', 'run', 'dryrun', 'check')))
	$mode = $_GET['mode'];

$controller->setParam('settings', $settings);
$controller->setParam('logger', $logger);
$controller->setParam('mode', $mode);
if(!$controller->run()) {
	echo getMLText("error_occured");
	exit;
}

add_log_line();
exit();

