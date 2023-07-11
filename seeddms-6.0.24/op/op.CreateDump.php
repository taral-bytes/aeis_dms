<?php
//    MyDMS. Document Management System
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
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (!$settings->_backupDir) {
	UI::exitError(getMLText("admin_tools"),getMLText("no_backup_dir"));
}

$v = new SeedDMS_Version;
$dump_name = addDirSep($settings->_backupDir).date('Y-m-d\TH-i-s')."_".$v->version().".sql";
$fp = fopen($dump_name, "w");
if(!$fp)
	UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
if(!$dms->getDb()->createDump($fp)) {
	fclose($fp);
	UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
}
fclose($fp);

if (SeedDMS_Core_File::gzcompressfile($dump_name,9)) unlink($dump_name);
else UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));

add_log_line();

header("Location:../out/out.BackupTools.php");

?>
