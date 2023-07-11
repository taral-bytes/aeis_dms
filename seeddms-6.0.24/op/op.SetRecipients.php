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

if ($document->getAccessMode($user) < M_READWRITE) {
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

$folder = $document->getFolder();

// Retrieve a list of all users and groups that have read rights.
// Afterwards, reorganize them in two arrays with its key being the
// userid or groupid
$docAccess = $document->getReadAccessList($settings->_enableAdminReceipt, $settings->_enableOwnerReceipt);
$accessIndex = array("i"=>array(), "g"=>array());
foreach ($docAccess["users"] as $i=>$da) {
	$accessIndex["i"][$da->getID()] = $da;
}
foreach ($docAccess["groups"] as $i=>$da) {
	$accessIndex["g"][$da->getID()] = $da;
}

// Retrieve list of currently assigned recipients, along with
// their latest status.
$receiptStatus = $content->getReceiptStatus();
// Index the receipt results for easy cross-reference with the Approvers List.
$receiptIndex = array("i"=>array(), "g"=>array());
foreach ($receiptStatus as $i=>$rs) {
	if ($rs["status"]!=S_LOG_USER_REMOVED) {
		if ($rs["type"]==0) {
			$receiptIndex["i"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
		}
		else if ($rs["type"]==1) {
			$receiptIndex["g"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
		}
	}
}

/* Get List of ind. reviewers, because they are taken out from the receivers
 * if added as group.
 */
$reviewStatus = $content->getReviewStatus();
$reviewerids = [];
foreach ($reviewStatus as $r) {
	if($r["type"] == 0 && $r["status"] > -2) {
		$reviewerids[] = $r['required'];
	}
}
// Get the list of proposed recipients, stripping out any duplicates.
$pIndRev = (isset($_POST["indRecipients"]) ? array_values(array_unique($_POST["indRecipients"])) : array());
// Retrieve the list of recipient groups whose members become individual recipients
if (isset($_POST["grpIndRecipients"])) {
	foreach ($_POST["grpIndRecipients"] as $grp) {
		if($group = $dms->getGroup($grp)) {
			$members = $group->getUsers();
			foreach($members as $member) {
				/* Do not add the uploader itself and reviewers */
				if(!$settings->_enableFilterReceipt || ($member->getID() != $content->getUser()->getID() && !in_array($member->getID(), $reviewerids)))
					if(!in_array($member->getID(), $pIndRev))
						$pIndRev[] = $member->getID();
			}
		}
	}
}
$pGrpRev = (isset($_POST["grpRecipients"]) ? array_values(array_unique($_POST["grpRecipients"])) : array());
foreach ($pIndRev as $p) {
	if (is_numeric($p)) {
		if (isset($accessIndex["i"][$p])) {
			// Proposed recipient is on the list of possible recipients.
			if (!isset($receiptIndex["i"][$p])) {
				// Proposed recipient is not a current recipient, so add as a new
				// recipient.
				$res = $content->addIndRecipient($accessIndex["i"][$p], $user);
				$unm = $accessIndex["i"][$p]->getFullName();
				$uml = $accessIndex["i"][$p]->getEmail();
				
				switch ($res) {
					case 0:
						// Send an email notification to the new recipient.
						if($settings->_enableNotificationAppRev) {
							if ($notifier) {
								$subject = "receipt_request_email_subject";
								$message = "receipt_request_email_body";
								$params = array();
								$params['name'] = $document->getName();
								$params['folder_path'] = $folder->getFolderPathPlain();
								$params['version'] = $content->getVersion();
								$params['comment'] = $content->getComment();
								$params['recipient'] = $accessIndex["i"][$p]->getFullName();
								$params['username'] = $user->getFullName();
								$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
								$params['sitename'] = $settings->_siteName;
								$params['http_root'] = $settings->_httpRoot;
								
								$notifier->toIndividual($user, $accessIndex["i"][$p], $subject, $message, $params, SeedDMS_NotificationService::RECV_RECIPIENT);
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
				// Proposed recipient is already in the list of recipients.
				// Remove recipient from the index of possible recipients. If there are
				// any recipients left over in the list of possible recipients, they
				// will be removed from the receipt process for this document revision.
				unset($receiptIndex["i"][$p]);
			}
		}
	}
}
if (count($receiptIndex["i"]) > 0) {
	foreach ($receiptIndex["i"] as $rx=>$rv) {
		if ($rv["status"] == 0) {
			// User is to be removed from the recipients list.
			if (!isset($accessIndex["i"][$rx])) {
				// User does not have any receipt privileges for this document
				// revision or does not exist.
				$res = $content->delIndRecipient($dms->getUser($rx), $user, getMLText("removed_recipient"));
			}
			else {
				$res = $content->delIndRecipient($accessIndex["i"][$rx], $user);
				$unm = $accessIndex["i"][$rx]->getFullName();
				$uml = $accessIndex["i"][$rx]->getEmail();
				switch ($res) {
					case 0:
						// Send an email notification to the recipients.
						if($settings->_enableNotificationAppRev) {
							if ($notifier) {
								$subject = "receipt_deletion_email_subject";
								$message = "receipt_deletion_email_body";
								$params = array();
								$params['name'] = $document->getName();
								$params['folder_path'] = $folder->getFolderPathPlain();
								$params['version'] = $content->getVersion();
								$params['comment'] = $content->getComment();
								$params['recipient'] = $accessIndex["i"][$rx]->getFullName();
								$params['username'] = $user->getFullName();
								$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
								$params['sitename'] = $settings->_siteName;
								$params['http_root'] = $settings->_httpRoot;
								
								$notifier->toIndividual($user, $accessIndex["i"][$rx], $subject, $message, $params, SeedDMS_NotificationService::RECV_RECIPIENT);
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
						// email error
						break;
				}
			}
		}
	}
}
foreach ($pGrpRev as $p) {
	if (is_numeric($p)) {
		if (isset($accessIndex["g"][$p])) {
			// Proposed recipient is on the list of possible recipients.
			if (!isset($receiptIndex["g"][$p])) {
				// Proposed recipient is not a current recipient, so add as a new
				// recipient.
				$res = $content->addGrpRecipient($accessIndex["g"][$p], $user);
				$gnm = $accessIndex["g"][$p]->getName();
				switch ($res) {
					case 0:
						// Send an email notification to the new recipient.
						if($settings->_enableNotificationAppRev) {
							if ($notifier) {
								$subject = "receipt_request_email_subject";
								$message = "receipt_request_email_body";
								$params = array();
								$params['name'] = $document->getName();
								$params['folder_path'] = $folder->getFolderPathPlain();
								$params['version'] = $content->getVersion();
								$params['comment'] = $content->getComment();
								$params['recipient'] = $accessIndex["g"][$p]->getName();
								$params['username'] = $user->getFullName();
								$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
								$params['sitename'] = $settings->_siteName;
								$params['http_root'] = $settings->_httpRoot;
							
								$notifier->toGroup($user, $accessIndex["g"][$p], $subject, $message, $params, SeedDMS_NotificationService::RECV_RECIPIENT);
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
				// Remove recipient from the index of possible recipients.
				unset($receiptIndex["g"][$p]);
			}
		}
	}
}
if (count($receiptIndex["g"]) > 0) {
	foreach ($receiptIndex["g"] as $rx=>$rv) {
		if ($rv["status"] == 0) {
			// Group is to be removed from the recipientist.
			if (!isset($accessIndex["g"][$rx])) {
				// Group does not have any receipt privileges for this document
				// revision or does not exist.
				$res = $content->delGrpRecipient($dms->getGroup($rx), $user, getMLText("removed_recipient"));
			}
			else {
				$res = $content->delGrpRecipient($accessIndex["g"][$rx], $user);
				$gnm = $accessIndex["g"][$rx]->getName();
				switch ($res) {
					case 0:
						// Send an email notification to the recipients group.
						if($settings->_enableNotificationAppRev) {
							if ($notifier) {
								$subject = "receipt_deletion_email_subject";
								$message = "receipt_deletion_email_body";
								$params = array();
								$params['name'] = $document->getName();
								$params['folder_path'] = $folder->getFolderPathPlain();
								$params['version'] = $content->getVersion();
								$params['comment'] = $content->getComment();
								$params['recipient'] = $accessIndex["g"][$rx]->getName();
								$params['username'] = $user->getFullName();
								$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
								$params['sitename'] = $settings->_siteName;
								$params['http_root'] = $settings->_httpRoot;
							
								$notifier->toGroup($user, $accessIndex["g"][$rx], $subject, $message, $params, SeedDMS_NotificationService::RECV_RECIPIENT);
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
						// email error
						break;
				}
			}
		}
	}
}

add_log_line("?documentid=".$documentid);
header("Location:../out/out.DocumentVersionDetail.php?documentid=".$documentid."&version=".$version);

?>
