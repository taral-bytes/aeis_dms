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

if (!isset($_GET["targetid"]) || !is_numeric($_GET["targetid"]) || $_GET["targetid"]<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$targetid = $_GET["targetid"];
$targetFolder = $dms->getFolder($targetid);

if (!is_object($targetFolder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

if ($targetFolder->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

$clipboard = $session->getClipboard();
foreach($clipboard['docs'] as $documentid) {
	$document = $dms->getDocument($documentid);
	if($document) {
		$oldFolder = $document->getFolder();

		if ($document->getAccessMode($user) < M_READWRITE) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
		}

		if ($targetid != $oldFolder->getID()) {
			if ($document->setFolder($targetFolder)) {
				// Send notification to subscribers.
				if($notifier) {
					$notifier->sendMovedDocumentMail($document, $user, $oldFolder);
				}
				$session->removeFromClipboard($document);

			} else {
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
			}
		} else {
			$session->removeFromClipboard($document);
		}
	}
}

foreach($clipboard['folders'] as $folderid) {
	$folder = $dms->getFolder($folderid);
	if($folder) {
		if ($folder->getAccessMode($user) < M_READWRITE) {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
		}

		$oldFolder = $folder->getParent();
		if ($folder->setParent($targetFolder)) {
			// Send notification to subscribers.
			if($notifier) {
				$notifier->sendMovedFolderMail($folder, $user, $oldFolder);
			}
			$session->removeFromClipboard($folder);
		} else {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));
		}
	}
}

$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_moved_clipboard')));

add_log_line();

if($_GET['refferer'])
	header("Location:".urldecode($_GET['refferer']));
else
	header("Location:../out/out.ViewFolder.php?folderid=".$targetid);

?>
