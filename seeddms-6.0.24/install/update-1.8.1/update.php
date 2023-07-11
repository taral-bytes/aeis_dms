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

include("../../inc/inc.Settings.php");
include("../../inc/inc.Utils.php");
include("../../inc/inc.LogInit.php");
include("../../inc/inc.Language.php");
include("../../inc/inc.Init.php");
include("../../inc/inc.DBInit.php");

print "<html></body>";

if (!$user->isAdmin()) {
	print "<b>ERROR: You must be administrator to execute the update</b>";
	die;
}

function update_content()
{

	GLOBAL $db,$settings;
	
	// create temp folder
	if (!SeedDMS_Core_File::makeDir($settings->_contentDir."/temp")) return false;
	
	// for all contents
	$queryStr = "SELECT * FROM tblDocumentContent";
	$contents = $db->getResultArray($queryStr);
	
	if (is_bool($contents)&&!$contents) return false;
	
	for ($i=0;$i<count($contents);$i++){
	
		// create temp/documentID folder
		if (!SeedDMS_Core_File::makeDir($settings->_contentDir."/temp/".$contents[$i]["document"])) return false;
		
		// move every content in temp/documentID/version.fileType
		$source = $settings->_contentDir."/".$contents[$i]["dir"]."/data".$contents[$i]["fileType"];

		$target = $settings->_contentDir."/temp/".$contents[$i]["document"]."/".$contents[$i]["version"].$contents[$i]["fileType"];		
		if (!SeedDMS_Core_File::copyFile($source, $target)) return false;
	}
	
	
	// change directory
	if (!SeedDMS_Core_File::renameDir($settings->_contentDir."/".$settings->_contentOffsetDir,$settings->_contentDir."/old")) return false;
	if (!SeedDMS_Core_File::renameDir($settings->_contentDir."/temp",$settings->_contentDir."/".$settings->_contentOffsetDir)) return false;
	
	return true;
}

function update_db()
{
	GLOBAL $db,$settings;

	// for all contents
	$queryStr = "SELECT * FROM tblDocumentContent";
	$contents = $db->getResultArray($queryStr);
	
	if (is_bool($contents)&&!$contents) return false;
	
	for ($i=0;$i<count($contents);$i++){
	
		$queryStr = "UPDATE tblDocumentContent set dir = '". $settings->_contentOffsetDir."/".$contents[$i]["document"]."/' WHERE document = ".$contents[$i]["document"];
		if (!$db->getResult($queryStr)) return false;
	
	}

	// run the update-2.0.sql
	$fd = fopen ("update.sql", "r");
	
	if (is_bool($fd)&&!$fd) return false;
	
	$queryStr = fread($fd, filesize("update.sql"));
	
	if (is_bool($queryStr)&&!$queryStr) return false;
	
	fclose ($fd);
	if (!$db->getResult($queryStr)) return false;
	
	return true;
}


print "<b>Updating ...please wait</b><br>";


if (!update_content()) {
	print "<b>ERROR: An error occurred during the directory reordering</b>";
	die;
}

if (!update_db()) {
	print "<b>ERROR: An error occurred during the DB update</b>";
	print "<br><b>Please try to execute the update.sql manually</b>";
	die;
}

print "<b>Update done</b><br>";

print "</body></html>";

