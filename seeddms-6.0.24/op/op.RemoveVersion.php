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

/* Check if the form data comes from a trusted request */
if(!checkFormKey('removeversion')) {
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

if (!$settings->_enableVersionDeletion && !$user->isAdmin()) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("access_denied"));
}

if ($document->getAccessMode($user, 'removeVersion') < M_ALL) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if (!isset($_POST["version"]) || !is_numeric($_POST["version"]) || intval($_POST["version"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$version_num = $_POST["version"];
$version = $document->getContentByVersion($version_num);

if (!is_object($version)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir);
$folder = $document->getFolder();
/* Check if there is just one version. In that case remove the document */
if (count($document->getContent())==1) {
	$previewer->deleteDocumentPreviews($document);

	/* Get the notify list before removing the document
	 * Also inform the users/groups of the parent folder
	 * Getting the list now will keep them in the document object
	 * even after the document has been deleted.
	 */
	$dnl =	$document->getNotifyList();
	$fnl =	$folder->getNotifyList();
	$docname = $document->getName();

	$controller->setParam('document', $document);
	$controller->setParam('fulltextservice', $fulltextservice);
	if(!$controller()) {
		if ($controller->getErrorMsg() != '')
			$errormsg = $controller->getErrorMsg();
		else
			$errormsg = "error_remove_document";
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($docname))),getMLText($errormsg));
	}

	if ($notifier){
		/* $document still has the data from the just deleted document,
		 * which is just enough to send the email.
		 */
		$notifier->sendDeleteDocumentMail($document, $user);
	}
	$nexturl = "../out/out.ViewFolder.php?folderid=".$folder->getId();
}
else {
	/* Before deleting the content get a list of all users that should
	 * be informed about the removal.
	 */
	$emailUserListR = array();
	$emailUserListA = array();
	$oldowner = $version->getUser();
	$emailGroupListR = array();
	$emailGroupListA = array();
	$status = $version->getReviewStatus();
	foreach ($status as $st) {
		if ($st["status"]==0) {
			if($st['type'] == 0 && !in_array($st["required"], $emailUserListR))
				$emailUserListR[] = $st["required"];
			elseif(!in_array($st["required"], $emailGroupListR))
				$emailGroupListR[] = $st["required"];
		}
	}
	$status = $version->getApprovalStatus();
	foreach ($status as $st) {
		if ($st["status"]==0) {
			if($st['type'] == 0 && !in_array($st["required"], $emailUserListA))
				$emailUserListA[] = $st["required"];
			elseif(!in_array($st["required"], $emailGroupListA))
				$emailGroupListA[] = $st["required"];
		}
	}

	$previewer->deletePreview($version, $settings->_previewWidthDetail);
	$previewer->deletePreview($version, $settings->_previewWidthList);
	/* Check if the version to be deleted is the latest version. This is
	 * later used to set the redirect url.
	 */
	$islatest = $version->getVersion() == $document->getLatestContent()->getVersion();
	if (!$document->removeContent($version)) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
	} else {
		if($islatest || count($document->getContent()) == 1)
			$nexturl = "../out/out.ViewDocument.php?documentid=".$documentid;
		else
			$nexturl = "../out/out.ViewDocument.php?documentid=".$documentid."&currenttab=previous";
		/* Remove the document from the fulltext index and reindex latest version */
		if($fulltextservice && ($index = $fulltextservice->Indexer())) {
			$lucenesearch = $fulltextservice->Search();
			if($hit = $lucenesearch->getDocument($document->getID())) {
				$index->delete($hit->id);
			}
			$index->addDocument($fulltextservice->IndexedDocument($document));
			$index->commit();
		}

		// Notify affected users.
		if ($notifier){
			$notifier->sendDeleteDocumentVersionMail($document, $version, $user);
			/*
			$nl=$document->getNotifyList();
			$userrecipientsR = array();
			foreach ($emailUserListR as $eID) {
				$eU = $version->getDMS()->getUser($eID);
				$userrecipientsR[] = $eU;
			}
			$grouprecipientsR = array();
			foreach ($emailGroupListR as $eID) {
				$eU = $version->getDMS()->getGroup($eID);
				$grouprecipientsR[] = $eU;
			}
			$userrecipientsA = array();
			foreach ($emailUserListA as $eID) {
				$eU = $version->getDMS()->getUser($eID);
				$userrecipientsA[] = $eU;
			}
			$grouprecipientsA = array();
			foreach ($emailGroupListA as $eID) {
				$eU = $version->getDMS()->getGroup($eID);
				$grouprecipientsA[] = $eU;
			}

			$subject = "version_deleted_email_subject";
			$message = "version_deleted_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['version'] = $version->getVersion();
			$params['folder_path'] = $document->getFolder()->getFolderPathPlain();
			$params['username'] = $user->getFullName();
			$params['sitename'] = $settings->_siteName;
			$params['http_root'] = $settings->_httpRoot;
			$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			if($user->getId() != $oldowner->getId())
				$notifier->toIndividual($user, $oldowner, $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
			$notifier->toList($user, $userrecipientsR, $subject, $message, $params, SeedDMS_NotificationService::RECV_REVIEWER);
			$notifier->toList($user, $userrecipientsA, $subject, $message, $params, SeedDMS_NotificationService::RECV_APPROVER);
			$notifier->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach($grouprecipientsR as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_REVIEWER);
			}
			foreach($grouprecipientsA as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_APPROVER);
			}
			foreach ($nl["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}
			 */
		}
	}
}

add_log_line("?documentid=".$documentid."&version".$version_num);

header("Location:".$nexturl);
