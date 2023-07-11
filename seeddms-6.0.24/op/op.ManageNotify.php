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

include("../inc/inc.Settings.php");
include("../inc/inc.Utils.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.Language.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if ($user->isGuest()) {
	UI::exitError(getMLText("my_account"),getMLText("access_denied"));
}

function add_folder_notify($folder,$userid,$recursefolder,$recursedoc) { /* {{{ */
	global $dms;

	$folder->addNotify($userid, true);
	
	if ($recursedoc){
	
		// include all folder's document
		
		$documents = $folder->getDocuments();
		$documents = SeedDMS_Core_DMS::filterAccess($documents, $dms->getUser($userid), M_READ);

		foreach($documents as $document)
			$document->addNotify($userid, true);
	}
	
	if ($recursefolder){
	
		// recurse all folder's folders
		
		$subFolders = $folder->getSubFolders();
		$subFolders = SeedDMS_Core_DMS::filterAccess($subFolders, $dms->getUser($userid), M_READ);

		foreach($subFolders as $subFolder)
			add_folder_notify($subFolder,$userid,$recursefolder,$recursedoc);
	}
} /* }}} */

if (!isset($_GET["type"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
if (!isset($_GET["action"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));

$userid=$user->getID();
	
if ($_GET["type"]=="document"){

	if ($_GET["action"]=="add"){
		if (!isset($_POST["docid"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$documentid = $_POST["docid"];
	}else if ($_GET["action"]=="del"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$documentid = $_GET["id"];
	
	}else UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if(!$documentid || !($document = $dms->getDocument($documentid))) {
		UI::exitError(getMLText("my_account"),getMLText("error_no_document_selected"));
	}
	
	if ($document->getAccessMode($user) < M_READ) 
		UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if ($_GET["action"]=="add") {
		$res = $document->addNotify($userid, true);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("my_account"), getMLText("unknown_user"));
				break;
			case -2:
				UI::exitError(getMLText("my_account"), getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("my_account"), getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("my_account"), getMLText("internal_error"));
				break;
			case 0:
				$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_add_notify')));
				// Email user / group, informing them of subscription.
				if ($notifier){
					$obj = $dms->getUser($userid);
					$notifier->sendNewDocumentNotifyMail($document, $user, $obj);
				}
				break;
		}
	} elseif ($_GET["action"]=="del") {
		$res = $document->removeNotify($userid, true);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("my_account"), getMLText("unknown_user"));
				break;
			case -2:
				UI::exitError(getMLText("my_account"), getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("my_account"), getMLText("not_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("my_account"), getMLText("internal_error"));
				break;
			case 0:
				$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_rm_notify')));
				// Email user / group, informing them of subscription change.
				if($notifier) {
					$obj = $dms->getUser($userid);
					$notifier->sendDeleteDocumentNotifyMail($document, $user, $obj);
				}
				break;
		}
	}
	
} else if ($_GET["type"]=="folder") {

	if ($_GET["action"]=="add"){
		if (!isset($_POST["targetid"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$folderid = $_POST["targetid"];
	}else if ($_GET["action"]=="del"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$folderid = $_GET["id"];
	
	}else UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if(!$folderid || !($folder = $dms->getFolder($folderid))) {
		UI::exitError(getMLText("my_account"),getMLText("error_no_folder_selected"));
	}
	
	if ($folder->getAccessMode($user) < M_READ) 
		UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if ($_GET["action"]=="add"){
	
		$recursefolder = isset($_POST["recursefolder"]);
		$recursedoc = isset($_POST["recursedoc"]);
	
		add_folder_notify($folder,$userid,$recursefolder,$recursedoc);
		
	} elseif ($_GET["action"]=="del") {
		if(0 == $folder->removeNotify($userid, true)) {
			if($notifier) {
				$obj = $dms->getUser($userid);
				$notifier->sendDeleteFolderNotifyMail($folder, $user, $obj);
			}
		}
	}
}

header("Location:../out/out.ManageNotify.php");

?>
