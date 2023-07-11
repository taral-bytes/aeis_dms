<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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

require_once("../inc/inc.Utils.php");
require_once('../inc/inc.ClassSettings.php');

$configDir = Settings::getConfigDir();
$settings = new Settings();
$settings->load($configDir."/settings.xml");

/**
 * Check if ENABLE_INSTALL_TOOL exists in config dir
 */
if (!file_exists($configDir."/ENABLE_INSTALL_TOOL")) {
	echo "For installation of SeedDMS, you must create the file conf/ENABLE_INSTALL_TOOL";
	exit;
}

$theme = "bootstrap";
require_once("../inc/inc.Language.php");
include "../languages/en_GB/lang.inc";
require_once("../inc/inc.ClassUI.php");
include("class.Install.php");

$view = new SeedDMS_View_Install(array('settings'=>$settings, 'session'=>null, 'sitename'=>'SeedDMS', 'printdisclaimer'=>0, 'showmissingtranslations'=>0, 'absbaseprefix'=>'/', 'enabledropfolderlist'=>0, 'enablemenutasks'=>0));
$view->update();
