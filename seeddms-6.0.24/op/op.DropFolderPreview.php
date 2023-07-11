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
include("../inc/inc.Authentication.php");

if (!isset($_GET["filename"])) {
	exit;
}
$filename = $_GET["filename"];

$dir = rtrim($settings->_dropFolderDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$user->getLogin();

if(!file_exists($dir.DIRECTORY_SEPARATOR.$filename))
	exit;

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimetype = finfo_file($finfo, $dir.'/'.$filename);

if(!empty($_GET["width"]))
	$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir, $_GET["width"], $settings->_cmdTimeout, $settings->_enableXsendfile);
else
	$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir, $settings->_previewWidthList, $settings->_cmdTimeout, $settings->_enableXsendfile);
if($conversionmgr)
	$previewer->setConversionMgr($conversionmgr);
else
	$previewer->setConverters($previewconverters);
if(!$previewer->hasRawPreview($dir.'/'.$filename, 'dropfolder/'))
	$previewer->createRawPreview($dir.'/'.$filename, 'dropfolder/', $mimetype);
header('Content-Type: image/png');
$previewer->getRawPreview($dir.'/'.$filename, 'dropfolder/');

