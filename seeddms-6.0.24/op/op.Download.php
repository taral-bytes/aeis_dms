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
include("../inc/inc.ClassController.php");
include("../inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if (!$accessop->check_controller_access($controller, $_POST)) {
	UI::exitError(getMLText("document_title", array("documentname" => "")),getMLText("access_denied"));
}

if (isset($_GET["version"])) { /* {{{ */

	// document download
	if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
	}

	$documentid = $_GET["documentid"];
	$document = $dms->getDocument($documentid);

	if (!is_object($document)) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));

	}
	$folder = $document->getFolder();
	$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

	if ($document->getAccessMode($user) < M_READ) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}

	if (!is_numeric($_GET["version"]) || intval($_GET["version"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
	}
	$version = $_GET["version"];

	$controller->setParam('document', $document);
	$controller->setParam('version', $version);
	$controller->setParam('type', 'version');
	if(!$controller()) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
	}
} /* }}} */
elseif (isset($_GET["file"])) { /* {{{ */

	// file download
	
	if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
	}

	$documentid = $_GET["documentid"];
	$document = $dms->getDocument($documentid);

	if (!is_object($document)) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));

	}
	$folder = $document->getFolder();
	$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

	if ($document->getAccessMode($user) < M_READ) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}

	if (!is_numeric($_GET["file"]) || intval($_GET["file"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_file_id"));
	}
	$fileid = $_GET["file"];
	$file = $document->getDocumentFile($fileid);

	if (!is_object($file)) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_file_id"));
	}

	$controller->setParam('file', $file);
	$controller->setParam('type', 'file');
	$controller->run();
} /* }}} */
elseif (isset($_GET["arkname"])) { /* {{{ */
	$filename = basename($_GET["arkname"]);

	// backup download
	
	if (!$user->isAdmin()) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}

	if (!isset($filename)) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_id"));
	}

	$backupdir = addDirSep($settings->_backupDir);
	if (!file_exists($backupdir.$filename) ) {
		UI::exitError(getMLText("admin_tools"),getMLText("missing_file"));
	}

	$controller->setParam('basedir', $backupdir);
	$controller->setParam('file', $filename);
	$controller->archive();
} /* }}} */
elseif (isset($_GET["logname"])) { /* {{{ */
	$filename = basename($_GET["logname"], '.log').'.log';

	// log download
	
	if (!$user->isAdmin()) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}

	if (!isset($filename)) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_id"));
	}

	if (!file_exists($settings->_contentDir.'log/'.$filename) ) {
		UI::exitError(getMLText("admin_tools"),getMLText("missing_file"));
	}

	$controller->setParam('file', $filename);
	$controller->setParam('basedir', $settings->_contentDir . 'log/');
	$controller->log();

} /* }}} */
elseif (isset($_GET["vfile"])) { /* {{{ */

	// versioning info download
	
	$documentid = $_GET["documentid"];
	$document = $dms->getDocument($documentid);

	if (!is_object($document)) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));

	}	
	
	// update infos
	createVersionigFile($document);
	
	header("Content-Type: text/plain");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . filesize($dms->contentDir.$document->getDir().$settings->_versioningFileName )."\"");
	$efilename = rawurlencode($settings->_versioningFileName);
	header("Content-Disposition: attachment; filename=\"". $efilename . "\"");
	header("Cache-Control: must-revalidate");
	
	sendFile($dms->contentDir . $document->getDir() .$settings->_versioningFileName);
	
} /* }}} */
elseif (isset($_GET["dumpname"])) { /* {{{ */
	$filename = basename($_GET["dumpname"]);

	// dump file download
	
	if (!$user->isAdmin()) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}

	if (!isset($filename)) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_id"));
	}

	$backupdir = addDirSep($settings->_backupDir);
	if (!$backupdir) {
		UI::exitError(getMLText("admin_tools"),getMLText("no_backup_dir"));
	}

	if (!file_exists($backupdir.$filename) ) {
		UI::exitError(getMLText("admin_tools"),getMLText("missing_file"));
	}

	$controller->setParam('basedir', $backupdir);
	$controller->setParam('file', $filename);
	$controller->sqldump();
} /* }}} */
elseif (isset($_GET["reviewlogid"])) { /* {{{ */
	if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
	}

	if (!isset($_GET["reviewlogid"]) || !is_numeric($_GET["reviewlogid"]) || intval($_GET["reviewlogid"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("invalid_reviewlog_id"));
	}

	$documentid = $_GET["documentid"];
	$document = $dms->getDocument($documentid);
	if (!is_object($document)) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("invalid_doc_id"));
	}

	if ($document->getAccessMode($user) < M_READ) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("access_denied"));
	}

	$controller->setParam('document', $document);
	$controller->setParam('reviewlogid', (int) $_GET['reviewlogid']);
	$controller->setParam('type', 'review');
	$controller->run();
	switch($controller->getErrorNo()) {
	case 1:
		UI::exitError(getMLText("admin_tools"),getMLText("missing_file"));
		break;
	}
} /* }}} */
elseif (isset($_GET["approvelogid"])) { /* {{{ */
	if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
	}

	if (!isset($_GET["approvelogid"]) || !is_numeric($_GET["approvelogid"]) || intval($_GET["approvelogid"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("invalid_approvelog_id"));
	}

	$documentid = $_GET["documentid"];
	$document = $dms->getDocument($documentid);
	if (!is_object($document)) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("invalid_doc_id"));
	}

	if ($document->getAccessMode($user) < M_READ) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("access_denied"));
	}

	$controller->setParam('document', $document);
	$controller->setParam('approvelogid', (int) $_GET['approvelogid']);
	$controller->setParam('type', 'approval');
	$controller->run();
	switch($controller->getErrorNo()) {
	case 1:
		UI::exitError(getMLText("admin_tools"),getMLText("missing_file"));
		break;
	}
} /* }}} */

add_log_line();
exit();
