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

require_once('inc.ClassSettings.php');
if(defined("SEEDDMS_CONFIG_FILE"))
	$settings = new Settings(SEEDDMS_CONFIG_FILE);
elseif(getenv("SEEDDMS_CONFIG_FILE"))
	$settings = new Settings(getenv("SEEDDMS_CONFIG_FILE"));
else
	$settings = new Settings();
if(!defined("SEEDDMS_INSTALL") && file_exists($settings->_configFileDir."/ENABLE_INSTALL_TOOL")) {
	die("SeedDMS won't run unless your remove the file ENABLE_INSTALL_TOOL from your configuration directory.");
}

/* Set an encryption key if is not set */
if(!trim($settings->_encryptionKey)) {
	$settings->_encryptionKey = bin2hex(openssl_random_pseudo_bytes(16));
	$settings->save();
}

/* Set some directories if not set in the configuration file */
$__basedir = dirname(dirname(__DIR__));
$__datadir = dirname(dirname(__DIR__))."/data";;
if(empty($settings->_rootDir)) {
	$settings->_rootDir = $__basedir."/www/";
}
if(empty($settings->_contentDir)) {
	$settings->_contentDir = $__datadir;
}
if(empty($settings->_cacheDir)) {
	$settings->_cacheDir = $__datadir."/cache/";
}
if(empty($settings->_backupDir)) {
	$settings->_backupDir = $__datadir."/backup/";
}
if(empty($settings->_luceneDir)) {
	$settings->_luceneDir = $__datadir."/lucene/";
}
if(empty($settings->_stagingDir)) {
	$settings->_stagingDir = $__datadir."/lucene/";
}
if($settings->_dbDriver == 'sqlite' && empty($settings->_dbDatabase)) {
	$settings->_dbDatabase = $__datadir."/content.db";
}

ini_set('include_path', $settings->_rootDir.'pear'. PATH_SEPARATOR .ini_get('include_path'));
if(!empty($settings->_extraPath)) {
	ini_set('include_path', $settings->_extraPath. PATH_SEPARATOR .ini_get('include_path'));
}

if(isset($settings->_maxExecutionTime)) {
	if (php_sapi_name() !== "cli") {
		ini_set('max_execution_time', $settings->_maxExecutionTime);
	}
}

/* Add root Dir. Needed because the view classes are included
 * relative to it.
 */
ini_set('include_path', $settings->_rootDir. PATH_SEPARATOR .ini_get('include_path'));
/* Add root Dir.'../pear'. Needed because the SeedDMS_Core, etc. are included
 * relative to it.
 */
ini_set('include_path', $settings->_rootDir.'../pear'. PATH_SEPARATOR .ini_get('include_path'));

/* composer is installed in pear directory, but install tool does not need it */
if(!defined("SEEDDMS_INSTALL"))
	require_once 'vendor/autoload.php';

require_once "inc.Version.php";
