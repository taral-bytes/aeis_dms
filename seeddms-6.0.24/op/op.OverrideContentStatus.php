<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
//$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if(!$accessop->check_controller_access($tmp[1] /*$controller*/)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("access_denied"));
}

/* Check if the form data comes from a trusted request */
if(!checkFormKey('overridecontentstatus')) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}
$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);
if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if ($document->getAccessMode($user) < M_ALL) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if (!isset($_POST["version"]) || !is_numeric($_POST["version"]) || intval($_POST["version"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$version = $_POST["version"];
$content = $document->getContentByVersion($version);

if (!is_object($content)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

if (!isset($_POST["overrideStatus"]) || !is_numeric($_POST["overrideStatus"]) ||
		(intval($_POST["overrideStatus"]) != S_RELEASED && intval($_POST["overrideStatus"]) != S_OBSOLETE && intval($_POST["overrideStatus"]) != S_DRAFT && intval($_POST["overrideStatus"]) != S_NEEDS_CORRECTION)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_status"));
}

$overallStatus = $content->getStatus();

// status change control, setting a status to obsolete is alwasy allowed
if(intval($_POST["overrideStatus"]) != S_OBSOLETE)
if ($overallStatus["status"] == S_REJECTED || $overallStatus["status"] == S_EXPIRED || $overallStatus["status"] == S_DRAFT_REV || $overallStatus["status"] == S_DRAFT_APP || $overallStatus["status"] == S_IN_WORKFLOW) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("cannot_change_final_states"));
}

$overrideStatus = $_POST["overrideStatus"];
$comment = $_POST["comment"];

if ($overrideStatus != $overallStatus["status"]) {

	if (!$content->setStatus($overrideStatus, $comment, $user)) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
	} else {
		// Send notification to subscribers.
		if($notifier) {
			$notifier->sendChangedDocumentStatusMail($content, $user, $overallStatus["status"]);
		}
	}
}
add_log_line("?documentid=".$documentid);
header("Location:../out/out.DocumentVersionDetail.php?documentid=".$documentid."&version=".$version);

?>
