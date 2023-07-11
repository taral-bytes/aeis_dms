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

if(!isset($settings))
	require_once("../inc/inc.Settings.php");
require_once("inc/inc.Utils.php");
require_once("inc/inc.LogInit.php");
require_once("inc/inc.ClassCalendar.php");
require_once("inc/inc.Language.php");
require_once("inc/inc.Init.php");
require_once("inc/inc.Extension.php");
require_once("inc/inc.DBInit.php");
require_once("inc/inc.ClassUI.php");
require_once("inc/inc.ClassAccessOperation.php");
require_once("inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if (!$accessop->check_view_access($view, $_GET)) {
	UI::exitError(getMLText("calendar"),getMLText("access_denied"));
}

if (isset($_GET["start"])) $start=$_GET["start"];
else $start = '';
if (isset($_GET["end"])) $end=$_GET["end"];
else $end = '';
if (isset($_GET["day"])) $day=$_GET["day"];
else $day = '';
if (isset($_GET["year"])) $year=$_GET["year"];
else $year = '';
if (isset($_GET["month"])) $month=$_GET["month"];
else $month = '';

if(isset($_GET['documentid']) && $_GET['documentid'] && is_numeric($_GET['documentid'])) {
	$document = $dms->getDocument($_GET["documentid"]);
	if (!is_object($document)) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
	}
} else
	$document = null;

$calendar = new SeedDMS_Calendar($dms->getDB(), $user);

if(isset($_GET['eventid']) && $_GET['eventid'] && is_numeric($_GET['eventid'])) {
	$event = $calendar->getEvent($_GET["eventid"]);
} else
	$event = null;

if(isset($_GET['version']) && $_GET['version'] && is_numeric($_GET['version'])) {
	$content = $document->getContentByVersion($_GET['version']);
} else
	$content = null;

if(isset($_GET['eventtype']) && $_GET['eventtype']) {
	$eventtype = $_GET['eventtype'];
} else
	$eventtype = 'regular';

if($view) {
	$view->setParam('accessobject', $accessop);
	$view->setParam('conversionmgr', $conversionmgr);
	$view->setParam('onepage', $settings->_onePageMode); // do most navigation by reloading areas of pages with ajax
	$view->setParam('calendar', $calendar);
	$view->setParam('start', $start);
	$view->setParam('end', $end);
	$view->setParam('day', $day);
	$view->setParam('year', $year);
	$view->setParam('month', $month);
	$view->setParam('document', $document);
	$view->setParam('version', $content);
	$view->setParam('event', $event);
	$view->setParam('showtree', showtree());
	$view->setParam('strictformcheck', $settings->_strictFormCheck);
	$view->setParam('eventtype', $eventtype);
	$view->setParam('cachedir', $settings->_cacheDir);
	$view->setParam('previewWidthList', $settings->_previewWidthList);
	$view->setParam('previewWidthDetail', $settings->_previewWidthDetail);
	$view->setParam('convertToPdf', $settings->_convertToPdf);
	$view->setParam('previewConverters', isset($settings->_converters['preview']) ? $settings->_converters['preview'] : array());
	$view->setParam('timeout', $settings->_cmdTimeout);
	$view->setParam('accessobject', $accessop);
	$view->setParam('xsendfile', $settings->_enableXsendfile);
	$view($_GET);
	exit;
}
