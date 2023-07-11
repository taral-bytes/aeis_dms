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

if(!checkFormKey('foldernotify')) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_request_token"));
}

if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderid = $_POST["folderid"];
$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

if (!isset($_POST["action"]) || (strcasecmp($_POST["action"], "delnotify") && strcasecmp($_POST["action"], "addnotify"))) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_action"));
}
$action = $_POST["action"];

if (isset($_POST["userid"]) && (!is_numeric($_POST["userid"]) || $_POST["userid"]<-1)) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
}
$userid = isset($_POST["userid"]) ? $_POST["userid"] : -1;

if (isset($_POST["groupid"]) && (!is_numeric($_POST["groupid"]) || $_POST["groupid"]<-1)) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_group"));
}
$groupid = isset($_POST["groupid"]) ? $_POST["groupid"] : -1;

if (isset($_POST["groupid"])&&$_POST["groupid"]!=-1){
	$group=$dms->getGroup($groupid);
	if (!$group->isMember($user,true) && !$user->isAdmin())
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

// Delete notification -------------------------------------------------------
if ($action == "delnotify") {

	if ($userid > 0) {
		$res = $folder->removeNotify($userid, true);
		$obj = $dms->getUser($userid);
	}
	elseif ($groupid > 0) {
		$res = $folder->removeNotify($groupid, false);
		$obj = $dms->getGroup($groupid);
	}
	switch ($res) {
		case -1:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),isset($userid) ? getMLText("unknown_user") : getMLText("unknown_group"));
			break;
		case -2:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
			break;
		case -3:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("not_subscribed"));
			break;
		case -4:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("internal_error"));
			break;
		case 0:
			$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_rm_notify')));
			if($notifier) {
				$notifier->sendDeleteFolderNotifyMail($folder, $user, $obj);
			}
			break;
	}
}

// Add notification ----------------------------------------------------------
else if ($action == "addnotify") {

	if ($userid != -1) {
		$res = $folder->addNotify($userid, true);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
				break;
			case -2:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("internal_error"));
				break;
			case 0:
				$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_add_notify')));
				if($notifier) {
					$obj = $dms->getUser($userid);
					$notifier->sendNewFolderNotifyMail($folder, $user, $obj);
				}

				break;
		}
	}
	if ($groupid != -1) {
		$res = $folder->addNotify($groupid, false);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_group"));
				break;
			case -2:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("internal_error"));
				break;
			case 0:
				$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_add_notify')));
				if($notifier) {
					$obj = $dms->getGroup($groupid);
					$notifier->sendNewFolderNotifyMail($folder, $user, $obj);
				}
				break;
		}
	}
}
	
header("Location:../out/out.FolderNotify.php?folderid=".$folderid);

?>
