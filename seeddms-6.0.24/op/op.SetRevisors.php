<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2015 Uwe Steinmann
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

if (isset($_POST["startdate"])) {
	$ts = makeTsFromDate($_POST["startdate"]);
} else {
	$ts = time();
}
$startdate = date('Y-m-d', $ts);

if(!$content->setRevisionDate($startdate)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
}

$folder = $document->getFolder();

// Retrieve a list of all users and groups that have read rights.
// Afterwards, reorganize them in two arrays with its key being the
// userid or groupid
$docAccess = $document->getReadAccessList($settings->_enableAdminRevApp, $settings->_enableOwnerRevApp);
$accessIndex = array("i"=>array(), "g"=>array());
foreach ($docAccess["users"] as $i=>$da) {
	$accessIndex["i"][$da->getID()] = $da;
}
foreach ($docAccess["groups"] as $i=>$da) {
	$accessIndex["g"][$da->getID()] = $da;
}

// Retrieve list of currently assigned recipients, along with
// their latest status.
$revisionStatus = $content->getRevisionStatus();
// Index the revision results for easy cross-reference with the Approvers List.
$revisionIndex = array("i"=>array(), "g"=>array());
foreach ($revisionStatus as $i=>$rs) {
	if ($rs["status"]!=S_LOG_USER_REMOVED) {
		if ($rs["type"]==0) {
			$revisionIndex["i"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
		}
		else if ($rs["type"]==1) {
			$revisionIndex["g"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
		}
	}
}

// Get the list of proposed revisors, stripping out any duplicates.
$pIndRev = (isset($_POST["indRevisors"]) ? array_values(array_unique($_POST["indRevisors"])) : array());
// Retrieve the list of revisor groups whose members become individual revisors
if (isset($_POST["grpIndRevisors"])) {
	foreach ($_POST["grpIndRevisors"] as $grp) {
		if($group = $dms->getGroup($grp)) {
			$members = $group->getUsers();
			foreach($members as $member) {
				if(!in_array($member->getID(), $pIndRev))
					$pIndRev[] = $member->getID();
			}
		}
	}
}
$pGrpRev = (isset($_POST["grpRevisors"]) ? array_values(array_unique($_POST["grpRevisors"])) : array());
foreach ($pIndRev as $p) {
	if (is_numeric($p)) {
		if (isset($accessIndex["i"][$p])) {
			// Proposed recipient is on the list of possible recipients.
			if (!isset($revisionIndex["i"][$p])) {
				// Proposed recipient is not a current recipient, so add as a new
				// recipient.
				$res = $content->addIndRevisor($accessIndex["i"][$p], $user);
				$unm = $accessIndex["i"][$p]->getFullName();
				$uml = $accessIndex["i"][$p]->getEmail();
				
				switch ($res) {
					case 0:
						// Send an email notification to the new recipient.
						if($settings->_enableNotificationAppRev) {
							if ($notifier) {
								$subject = "revision_request_email_subject";
								$message = "revision_request_email_body";
								$params = array();
								$params['name'] = $document->getName();
								$params['folder_path'] = $folder->getFolderPathPlain();
								$params['version'] = $content->getVersion();
								$params['comment'] = $content->getComment();
								$params['revisor'] = $accessIndex["i"][$p]->getFullName();
								$params['username'] = $user->getFullName();
								$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
								$params['sitename'] = $settings->_siteName;
								$params['http_root'] = $settings->_httpRoot;
								
								$notifier->toIndividual($user, $accessIndex["i"][$p], $subject, $message, $params, SeedDMS_NotificationService::RECV_REVISOR);
							}
						}
						break;
					case -1:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
						break;
					case -2:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
						break;
					case -3:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("revisor_already_assigned"));
						break;
					case -4:
						// email error
						break;
				}
			}
			else {
				// Proposed recipient is already in the list of recipients.
				// Remove revisor from the index of possible revisors. If there are
				// any revisors left over in the list of possible revisors, they
				// will be removed from the revision process for this document revision.
				unset($revisionIndex["i"][$p]);
			}
		}
	}
}
if (count($revisionIndex["i"]) > 0) {
	foreach ($revisionIndex["i"] as $rx=>$rv) {
	//	if ($rv["status"] == 0) {
			// User is to be removed from the recipients list.
			if (!isset($accessIndex["i"][$rx])) {
				// User does not have any revision privileges for this document
				// revision or does not exist.
				$res = $content->delIndRevisor($dms->getUser($rx), $user, getMLText("removed_revisor"));
			}
			else {
				$res = $content->delIndRevisor($accessIndex["i"][$rx], $user);
				$unm = $accessIndex["i"][$rx]->getFullName();
				$uml = $accessIndex["i"][$rx]->getEmail();
				switch ($res) {
					case 0:
						// Send an email notification to the recipients.
						if($settings->_enableNotificationAppRev) {
							if ($notifier) {
								$subject = "revision_deletion_email_subject";
								$message = "revision_deletion_email_body";
								$params = array();
								$params['name'] = $document->getName();
								$params['folder_path'] = $folder->getFolderPathPlain();
								$params['version'] = $content->getVersion();
								$params['comment'] = $content->getComment();
								$params['revisor'] = $accessIndex["i"][$rx]->getFullName();
								$params['username'] = $user->getFullName();
								$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
								$params['sitename'] = $settings->_siteName;
								$params['http_root'] = $settings->_httpRoot;
								
								$notifier->toIndividual($user, $accessIndex["i"][$rx], $subject, $message, $params, SeedDMS_NotificationService::RECV_REVISOR);
							}
						}
						break;
					case -1:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
						break;
					case -2:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
						break;
					case -3:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("recipient_already_removed"));
						break;
					case -4:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("recipient_was_active"));
						break;
				}
			}
//		}
	}
}
foreach ($pGrpRev as $p) {
	if (is_numeric($p)) {
		if (isset($accessIndex["g"][$p])) {
			// Proposed recipient is on the list of possible recipients.
			if (!isset($revisionIndex["g"][$p])) {
				// Proposed recipient is not a current recipient, so add as a new
				// recipient.
				$res = $content->addGrpRevisor($accessIndex["g"][$p], $user);
				$gnm = $accessIndex["g"][$p]->getName();
				switch ($res) {
					case 0:
						// Send an email notification to the new recipient.
						if($settings->_enableNotificationAppRev) {
							if ($notifier) {
								$subject = "revision_request_email_subject";
								$message = "revision_request_email_body";
								$params = array();
								$params['name'] = $document->getName();
								$params['folder_path'] = $folder->getFolderPathPlain();
								$params['version'] = $content->getVersion();
								$params['comment'] = $content->getComment();
								$params['revisor'] = $accessIndex["g"][$p]->getName();
								$params['username'] = $user->getFullName();
								$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
								$params['sitename'] = $settings->_siteName;
								$params['http_root'] = $settings->_httpRoot;
							
								$notifier->toGroup($user, $accessIndex["g"][$p], $subject, $message, $params, SeedDMS_NotificationService::RECV_REVISOR);
							}
						}
						break;
					case -1:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
						break;
					case -2:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
						break;
					case -3:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("recipient_already_assigned"));
						break;
					case -4:
						// email error
						break;
				}
			}
			else {
				// Remove recipient from the index of possible revisors.
				unset($revisionIndex["g"][$p]);
			}
		}
	}
}
if (count($revisionIndex["g"]) > 0) {
	foreach ($revisionIndex["g"] as $rx=>$rv) {
//		if ($rv["status"] == 0) {
			// Group is to be removed from the recipientist.
			if (!isset($accessIndex["g"][$rx])) {
				// Group does not have any revision privileges for this document
				// revision or does not exist.
				$res = $content->delGrpRevisor($dms->getGroup($rx), $user, getMLText("removed_revisor"));
			}
			else {
				$res = $content->delGrpRevisor($accessIndex["g"][$rx], $user);
				$gnm = $accessIndex["g"][$rx]->getName();
				switch ($res) {
					case 0:
						// Send an email notification to the recipients group.
						if($settings->_enableNotificationAppRev) {
							if ($notifier) {
								$subject = "revision_deletion_email_subject";
								$message = "revision_deletion_email_body";
								$params = array();
								$params['name'] = $document->getName();
								$params['folder_path'] = $folder->getFolderPathPlain();
								$params['version'] = $content->getVersion();
								$params['comment'] = $content->getComment();
								$params['revisor'] = $accessIndex["g"][$rx]->getName();
								$params['username'] = $user->getFullName();
								$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
								$params['sitename'] = $settings->_siteName;
								$params['http_root'] = $settings->_httpRoot;
							
								$notifier->toGroup($user, $accessIndex["g"][$rx], $subject, $message, $params, SeedDMS_NotificationService::RECV_REVISOR);
							}
						}
						break;
					case -1:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
						break;
					case -2:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
						break;
					case -3:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("recipient_already_removed"));
						break;
					case -4:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("recipient_was_active"));
						break;
				}
			}
//		}
	}
}

/* If all revisors has been removed, then clear the next revision date */
if(!$pIndRev && !$pGrpRev) {
	$content->setRevisionDate(false);
}

/* Recheck status, because all revisors could have been removed */
$content->verifyStatus(false, $user, getMLText('automatic_status_update'), $settings->_initialDocumentStatus);

add_log_line("?documentid=".$documentid);
header("Location:../out/out.DocumentVersionDetail.php?documentid=".$documentid."&version=".$version);

?>
