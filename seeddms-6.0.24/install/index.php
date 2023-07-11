<?php
require_once('../inc/inc.Version.php');
$ver = new SeedDMS_Version();
define("SEEDDMS_INSTALL", "on");
define("SEEDDMS_VERSION", $ver->version());

include("../inc/inc.Settings.php");
$settings = new Settings();
$rootDir = realpath ("..");
if(file_exists($rootDir.'/../www'))
	$rootDir = realpath($rootDir.'/..').'/www';
$settings->_rootDir = str_replace("\\", "/" , $rootDir).'/';
$settings->_language = 'en_GB';

$theme = "bootstrap";
include("../inc/inc.Language.php");
include "../languages/en_GB/lang.inc";
include("../inc/inc.ClassUI.php");
include("class.Install.php");

$view = new SeedDMS_View_Install(array('settings'=>$settings, 'session'=>null, 'sitename'=>'SeedDMS', 'printdisclaimer'=>0, 'showmissingtranslations'=>0, 'absbaseprefix'=>'/', 'enabledropfolderlist'=>0, 'enablemenutasks'=>0));
$view->intro();
