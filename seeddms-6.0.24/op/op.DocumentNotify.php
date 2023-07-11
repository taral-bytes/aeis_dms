<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
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

if(!checkFormKey('documentnotify')) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_request_token"));
}

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if (!isset($_POST["action"]) || (strcasecmp($_POST["action"], "delnotify") && strcasecmp($_POST["action"],"addnotify"))) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_action"));
}

$action = $_POST["action"];

if (isset($_POST["userid"]) && (!is_numeric($_POST["userid"]) || $_POST["userid"]<-1)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));
}

$userid = 0;
if(isset($_POST["userid"]))
	$userid = $_POST["userid"];

if (isset($_POST["groupid"]) && (!is_numeric($_POST["groupid"]) || $_POST["groupid"]<-1)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
}

if(isset($_POST["groupid"]))
	$groupid = $_POST["groupid"];

if (isset($_POST["groupid"])&&$_POST["groupid"]!=-1){
	$group=$dms->getGroup($groupid);
	if (!$group->isMember($user,true) && !$user->isAdmin())
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

if ($document->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

// delete notification
if ($action == "delnotify"){
	if ($userid) {
		$obj = $dms->getUser($userid);
		$res = $document->removeNotify($userid, true);
	} elseif (isset($groupid)) {
		$obj = $dms->getGroup($groupid);
		$res = $document->removeNotify($groupid, false);
	}
	switch ($res) {
		case -1:
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),isset($userid) ? getMLText("unknown_user") : getMLText("unknown_group"));
			break;
		case -2:
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
			break;
		case -3:
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("not_subscribed"));
			break;
		case -4:
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
			break;
		case 0:
			$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_rm_notify')));
			// Email user / group, informing them of subscription change.
			if($notifier) {
				$notifier->sendDeleteFolderNotifyMail($folder, $user, $obj);
			}
			break;
	}
}

// add notification
else if ($action == "addnotify") {

	/* Both $userid and $groupid can be set */
	if ($userid > 0) {
		$res = $document->addNotify($userid, true);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));
				break;
			case -2:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
				break;
			case 0:
				$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_add_notify')));
				if ($notifier){
					$obj = $dms->getUser($userid);
					$notifier->sendNewDocumentNotifyMail($document, $user, $obj);
				}
				break;
		}
	}
	if ($groupid != -1) {
		$res = $document->addNotify($groupid, false);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
				break;
			case -2:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
				break;
			case 0:
				$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_add_notify')));
				if ($notifier){
					$obj = $dms->getGroup($groupid);
					$notifier->sendNewDocumentNotifyMail($document, $user, $obj);
				}
				break;
		}
	}

}

header("Location:../out/out.DocumentNotify.php?documentid=".$documentid);

?>
