<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2021 Uwe Steinmann
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
include("../inc/inc.Authentication.php");
include("../inc/inc.ClassUI.php");

/* Check if the form data comes from a trusted request */
if(!checkFormKey('removereviewlog')) {
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

if (!$user->isAdmin() || $document->getAccessMode($user) < M_ALL) {
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

// operation is only allowed for the last document version
$latestContent = $document->getLatestContent();
if ($latestContent->getVersion()!=$version) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

if (!isset($_POST["reviewid"]) || !is_numeric($_POST["reviewid"]) || intval($_POST["reviewid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("invalid_reviewid"));
}
$reviewid = $_POST['reviewid'];
$reviews = $latestContent->getReviewStatus();
$reviewStatus = null;
foreach($reviews as $review) {
	if($review['reviewID'] == $reviewid) {
		$reviewStatus = $review;
		break;
	}
}
if(!$reviewStatus) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("invalid_reviewid"));
}

if($reviewStatus['type'] == 0) {
	$ruser = $dms->getUser($reviewStatus['required']);
	$msg = getMLText('ind_review_removed', array('name'=>$ruser->getFullName()));
} elseif($reviewStatus['type'] == 1) {
	$rgroup = $dms->getGroup($reviewStatus['required']);
	$msg = getMLText('group_review_removed', array('name'=>$rgroup->getName()));
} else
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("invalid_reviewid"));

$comment = $_POST["comment"];
$overallStatus = $latestContent->getStatus();
if(true === $latestContent->removeReview($reviewid, $user, $comment)) {
	$latestContent->verifyStatus(true, $user, $msg);
	if($notifier) {
		$notifier->sendReviewRequestMail($latestContent, $user);
		if($overallStatus['status'] != $latestContent->getStatus()['status'])
			$notifier->sendChangedDocumentStatusMail($latestContent, $user, $overallStatus["status"]);
	}
}
header("Location:../out/out.ViewDocument.php?documentid=".$documentid."&currenttab=revapp");
