<?php
//    MyDMS. Document Management System
//    Copyright (C) 2010 Matteo Lucarelli, 2011 Uwe Steinmann
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


/**
 * Check Update file
 */
if (file_exists("../inc/inc.Settings.old.php")) {
  echo "You can't install SeedDMS, unless you delete " . realpath("../inc/inc.Settings.old.php") . ".";
  exit;
}


/**
 * Check file for installation
 */
if (!file_exists("create_tables-innodb.sql")) {
  echo "Can't install SeedDMS, 'create_tables-innodb.sql' missing";
  exit;
}
if (!file_exists("create_tables-sqlite3.sql")) {
  echo "Can't install SeedDMS, 'create_tables-sqlite3.sql' missing";
  exit;
}
if (!file_exists("create_tables-postgres.sql")) {
  echo "Can't install SeedDMS, 'create_tables-postgres.sql' missing";
  exit;
}
if (!file_exists("settings.xml.template_install")) {
  echo "Can't install SeedDMS, 'settings.xml.template_install' missing";
  exit;
}

function fileExistsInIncludePath($file) { /* {{{ */
	$paths = explode(PATH_SEPARATOR, get_include_path());
	$found = false;
	foreach($paths as $p) {
		$fullname = $p.DIRECTORY_SEPARATOR.$file;
		if(is_file($fullname)) {
			$found = $fullname;
			break;
		}
	}
	return $found;
} /* }}} */

/**
 * Load default settings + set
 */
require_once('../inc/inc.Version.php');
$ver = new SeedDMS_Version();
define("SEEDDMS_INSTALL", "on");
define("SEEDDMS_VERSION", $ver->version());

require_once('../inc/inc.ClassSettings.php');

$configDir = Settings::getConfigDir();

/**
 * Check if ENABLE_INSTALL_TOOL exists in config dir
 */
if (!$configDir) {
	echo "Fatal error! I could not even find a configuration directory.";
	exit;
}

if (!file_exists($configDir."/ENABLE_INSTALL_TOOL")) {
	echo "For installation of SeedDMS, you must create the file ".$configDir."/ENABLE_INSTALL_TOOL";
	exit;
}

if (!file_exists($configDir."/settings.xml")) {
	if(!copy("settings.xml.template_install", $configDir."/settings.xml")) {
		echo "Could not create initial configuration file from template. Check directory permission of conf/.";
		exit;
	}
}

// Set folders settings
$settings = new Settings();
$settings->load($configDir."/settings.xml");

$rootDir = realpath ("..");
$installPath = realpath ("install.php");
$installPath = str_replace ("\\", "/" , $installPath);
$tmpToDel = str_replace ($rootDir, "" , $installPath);
$httpRoot = str_replace ($tmpToDel, "" , $_SERVER["SCRIPT_NAME"]).'/';
/* Correct rootDir to ensure it points to 'www' instead of the versioned
 * seeddms dir.
 */
if(file_exists($rootDir.'/../www'))
	$rootDir = realpath($rootDir.'/..').'/www';
$rootDir = str_replace ("\\", "/" , $rootDir) . "/";
do {
	$httpRoot = str_replace ("//", "/" , $httpRoot, $count);
} while ($count<>0);

$msg = '';
if($rootDir != $settings->_rootDir) {
	$msg = "Your Root directory has been modified to fit your installation path!";
}
$settings->_rootDir = $rootDir;

if(!$settings->_contentDir || !is_dir($settings->_contentDir)) {
	$settings->_contentDir = realpath($settings->_rootDir."..") . '/data/';
	$settings->_luceneDir = $settings->_contentDir . 'lucene/';
	$settings->_stagingDir = $settings->_contentDir . 'staging/';
	$settings->_cacheDir = $settings->_contentDir . 'cache/';
	$settings->_backupDir = $settings->_contentDir . 'backup/';
} else {
	if(!$settings->_cacheDir) {
		$settings->_cacheDir = $settings->_contentDir . 'cache/';
	}
}
if($settings->_dbDriver == 'sqlite') {
	if(!$settings->_dbDatabase || !file_exists($settings->_dbDatabase)) {
		$settings->_dbDatabase = $settings->_contentDir.'content.db';
	}
}
$settings->_httpRoot = $httpRoot;

if(isset($settings->_extraPath))
	ini_set('include_path', $settings->_extraPath. PATH_SEPARATOR .ini_get('include_path'));

/**
 * Include GUI + Language
 */
$theme = "bootstrap";
include("../inc/inc.Language.php");
include "../languages/en_GB/lang.inc";
include("../inc/inc.ClassUI.php");
include("class.Install.php");

$view = new SeedDMS_View_Install(array('settings'=>$settings, 'session'=>null, 'sitename'=>'SeedDMS', 'printdisclaimer'=>0, 'showmissingtranslations'=>0, 'absbaseprefix'=>'/', 'enabledropfolderlist'=>0, 'enablemenutasks'=>0, 'configdir'=>$configDir));
$view->install($msg);

