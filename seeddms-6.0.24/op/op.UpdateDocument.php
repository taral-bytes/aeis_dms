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
include("../inc/inc.Authentication.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if (!$accessop->check_controller_access($controller, $_POST)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("access_denied"));
}

/* if post_max_size is to small, then $_POST will not be set and the content
 * lenght will exceed post_max_size
 */
if(empty($_POST) && $_SERVER['CONTENT_LENGTH'] > SeedDMS_Core_File::parse_filesize(ini_get('post_max_size'))) {
	UI::exitError(getMLText("folder_title", array("foldername" => '')),getMLText("uploading_postmaxsize"));
}

/* Check if the form data comes from a trusted request */
if(!checkFormKey('updatedocument')) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);
$folder = $document->getFolder();

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if ($document->getAccessMode($user, 'updateDocument') < M_READWRITE) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("access_denied"));
}

if($settings->_quota > 0) {
	$remain = checkQuota($user);
	if ($remain < 0) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("quota_exceeded", array('bytes'=>SeedDMS_Core_File::format_filesize(abs($remain)))));
	}
}

if ($document->isLocked()) {
	$lockingUser = $document->getLockingUser();
	if (($lockingUser->getID() != $user->getID()) && ($document->getAccessMode($user) != M_ALL)) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("no_update_cause_locked"));
	}
	else $document->setLocked(false);
}

function reArrayFiles(&$file_post) {
	$file_post['source'] = 'upload';
	if($file_post['error'] != 4) // no file uploaded
		return array($file_post);
	else
		return array();
}

if ($_FILES['userfile']) {
	$file_ary = reArrayFiles($_FILES['userfile']);
} else {
	$file_ary = array();
}

if($settings->_dropFolderDir) {
	if(isset($_POST["dropfolderfileform1"]) && $_POST["dropfolderfileform1"]) {
		$fullfile = $settings->_dropFolderDir.'/'.$user->getLogin().'/'.$_POST["dropfolderfileform1"];
		if(file_exists($fullfile)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimetype = finfo_file($finfo, $fullfile);
			$file_ary[] = array(
				'tmp_name' => $fullfile,
				'type' => $mimetype,
				'name' => $_POST["dropfolderfileform1"],
				'size' => filesize($fullfile),
				'error' => 0,
				'source' => 'dropfolder'
			);
		}
	}
}

$prefix = 'userfile';
if(isset($_POST[$prefix.'-fine-uploader-uuids']) && $_POST[$prefix.'-fine-uploader-uuids']) {
	$uuids = explode(';', $_POST[$prefix.'-fine-uploader-uuids']);
	$names = explode(';', $_POST[$prefix.'-fine-uploader-names']);
	$uuid = $uuids[0];
	$fullfile = $settings->_stagingDir.'/'.utf8_basename($uuid);
	if(file_exists($fullfile)) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimetype = finfo_file($finfo, $fullfile);
		$file_ary[] = array(
			'tmp_name' => $fullfile,
			'type' => $mimetype,
			'name' => isset($names[0]) ? $names[0] : $uuid,
			'size' => filesize($fullfile),
			'error' => 0,
			'source' => 'upload',
		);
	}
}

if($controller->hasHook('getDocument')) {
	$file_ary = array_merge($file_ary, $controller->callHook('getDocument', $_POST));
}

if(!$file_ary) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_failed"));
}

$file = $file_ary[0];
if ($file['error'] == 0) {
	if($file['error']==1) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_maxsize"));
	}
	if($file['error']!=0) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_failed"));
	}
	if ($file["size"]==0) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("uploading_zerosize"));
	}
	$maxuploadsize = SeedDMS_Core_File::parse_filesize($settings->_maxUploadSize);
	if ($maxuploadsize && $file["size"] > $maxuploadsize) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("uploading_maxsize"));
	}

	$userfiletmp = $file["tmp_name"];
	$userfiletype = $file["type"];
	$userfilename = $file["name"];

	if($settings->_overrideMimeType) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$tmpfiletype = finfo_file($finfo, $userfiletmp);
		if($tmpfiletype != 'application/octet-stream')
			$userfiletype = $tmpfiletype;
	}
}

/* Check if the uploaded file is identical to last version */
$lc = $document->getLatestContent();
if($lc->getChecksum() == SeedDMS_Core_File::checksum($userfiletmp)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("identical_version"));
}

$fileType = ".".pathinfo($userfilename, PATHINFO_EXTENSION);

if(isset($_POST["comment"]))
	$comment  = $_POST["comment"];
else
	$comment = "";

$oldexpires = $document->getExpires();
switch($_POST["presetexpdate"]) {
case "date":
	$expires = makeTsFromDate($_POST["expdate"]);
//	$tmp = explode('-', $_POST["expdate"]);
//	$expires = mktime(0,0,0, $tmp[1], $tmp[2], $tmp[0]);
	break;
case "1w":
	$tmp = explode('-', date('Y-m-d'));
	$expires = mktime(0,0,0, $tmp[1], $tmp[2]+7, $tmp[0]);
	break;
case "1m":
	$tmp = explode('-', date('Y-m-d'));
	$expires = mktime(0,0,0, $tmp[1]+1, $tmp[2], $tmp[0]);
	break;
case "1y":
	$tmp = explode('-', date('Y-m-d'));
	$expires = mktime(0,0,0, $tmp[1], $tmp[2], $tmp[0]+1);
	break;
case "2y":
	$tmp = explode('-', date('Y-m-d'));
	$expires = mktime(0,0,0, $tmp[1], $tmp[2], $tmp[0]+2);
	break;
case "never":
default:
	$expires = null;
	break;
}

	// Get the list of reviewers and approvers for this document.
	$reviewers = array();
	$approvers = array();
	$recipients = array();
	$reviewers["i"] = array();
	$reviewers["g"] = array();
	$approvers["i"] = array();
	$approvers["g"] = array();
	$recipients["i"] = array();
	$recipients["g"] = array();
	$workflow = null;

	if($settings->_workflowMode == 'traditional' || $settings->_workflowMode == 'traditional_only_approval') {
		if($settings->_workflowMode == 'traditional') {
			// Retrieve the list of individual reviewers from the form.
			$reviewers["i"] = array();
			if (isset($_POST["indReviewers"])) {
				foreach ($_POST["indReviewers"] as $ind) {
					$reviewers["i"][] = $ind;
				}
			}
			// Retrieve the list of reviewer groups from the form.
			$reviewers["g"] = array();
			if (isset($_POST["grpReviewers"])) {
				foreach ($_POST["grpReviewers"] as $grp) {
					$reviewers["g"][] = $grp;
				}
			}
			// Retrieve the list of reviewer groups whose members become individual reviewers
			if (isset($_POST["grpIndReviewers"])) {
				foreach ($_POST["grpIndReviewers"] as $grp) {
					if($group = $dms->getGroup($grp)) {
						$members = $group->getUsers();
						foreach($members as $member)
							$reviewers["i"][] = $member->getID();
					}
				}
			}
		}

		// Retrieve the list of individual approvers from the form.
		$approvers["i"] = array();
		if (isset($_POST["indApprovers"])) {
			foreach ($_POST["indApprovers"] as $ind) {
				$approvers["i"][] = $ind;
			}
		}
		// Retrieve the list of approver groups from the form.
		$approvers["g"] = array();
		if (isset($_POST["grpApprovers"])) {
			foreach ($_POST["grpApprovers"] as $grp) {
				$approvers["g"][] = $grp;
			}
		}
		// Retrieve the list of reviewer groups whose members become individual approvers
		if (isset($_POST["grpIndApprovers"])) {
			foreach ($_POST["grpIndApprovers"] as $grp) {
				if($group = $dms->getGroup($grp)) {
					$members = $group->getUsers();
					foreach($members as $member)
						$approvers["i"][] = $member->getID();
				}
			}
		}

		// add mandatory reviewers/approvers
		if($settings->_workflowMode == 'traditional') {
			$mreviewers = getMandatoryReviewers($folder, $user);
			if($mreviewers['i'])
				$reviewers['i'] = array_merge($reviewers['i'], $mreviewers['i']);
			if($mreviewers['g'])
				$reviewers['g'] = array_merge($reviewers['g'], $mreviewers['g']);
		}
		$mapprovers = getMandatoryApprovers($folder, $user);
		if($mapprovers['i'])
			$approvers['i'] = array_merge($approvers['i'], $mapprovers['i']);
		if($mapprovers['g'])
			$approvers['g'] = array_merge($approvers['g'], $mapprovers['g']);

		if($settings->_workflowMode == 'traditional' && !$settings->_allowReviewerOnly) {
			/* Check if reviewers are send but no approvers */
			if(($reviewers["i"] || $reviewers["g"]) && !$approvers["i"] && !$approvers["g"]) {
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_uploading_reviewer_only"));
			}
		}
	} elseif($settings->_workflowMode == 'advanced') {
		if(!$workflows = $user->getMandatoryWorkflows()) {
			if(isset($_POST["workflow"]))
				$workflow = $dms->getWorkflow($_POST["workflow"]);
			else
				$workflow = null;
		} else {
			/* If there is excactly 1 mandatory workflow, then set no matter what has
			 * been posted in 'workflow', otherwise check if the posted workflow is in the
			 * list of mandatory workflows. If not, then take the first one.
			 */
			$workflow = array_shift($workflows);
			foreach($workflows as $mw)
				if($mw->getID() == $_POST['workflow']) {$workflow = $mw; break;}
		}
	}

	// Retrieve the list of individual recipients from the form.
	$recipients["i"] = array();
	if (isset($_POST["indRecipients"])) {
		foreach ($_POST["indRecipients"] as $ind) {
			$recipients["i"][] = $ind;
		}
	}
	// Retrieve the list of recipient groups from the form.
	$recipients["g"] = array();
	if (isset($_POST["grpRecipients"])) {
		foreach ($_POST["grpRecipients"] as $grp) {
			$recipients["g"][] = $grp;
		}
	}
	// Retrieve the list of recipient groups whose members become individual recipients
	if (isset($_POST["grpIndRecipients"])) {
		foreach ($_POST["grpIndRecipients"] as $grp) {
			if($group = $dms->getGroup($grp)) {
				$members = $group->getUsers();
				foreach($members as $member) {
					/* Do not add the uploader itself and approvers */
					if(!$settings->_enableFilterReceipt || ($member->getID() != $user->getID() && !in_array($member->getID(), $reviewers['i'])))
						if(!in_array($member->getID(), $recipients["i"]))
							$recipients["i"][] = $member->getID();
				}
			}
		}
	}

	if(isset($_POST["attributes_version"]) && $_POST["attributes_version"]) {
		$attributes = $_POST["attributes_version"];
		foreach($attributes as $attrdefid=>$attribute) {
			$attrdef = $dms->getAttributeDefinition($attrdefid);
			if($attribute) {
				switch($attrdef->getType()) {
				case SeedDMS_Core_AttributeDefinition::type_date:
					$attribute = date('Y-m-d', makeTsFromDate($attribute));
					break;
				}
				if(!$attrdef->validate($attribute, null, true)) {
					$errmsg = getAttributeValidationText($attrdef->getValidationError(), $attrdef->getName(), $attribute);
					UI::exitError(getMLText("document_title", array("documentname" => $document->getName())), $errmsg);
				}
			} elseif($attrdef->getMinValues() > 0) {
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("attr_min_values", array("attrname"=>$attrdef->getName())));
			}
		}
	} else {
		$attributes = array();
	}

	$controller->setParam('documentsource', $file['source']);
	$controller->setParam('documentsourcedetails', !empty($file['source_details']) ? $file['source_details'] : null);
	$controller->setParam('folder', $folder);
	$controller->setParam('document', $document);
	$controller->setParam('fulltextservice', $fulltextservice);
	$controller->setParam('comment', $comment);
	if($oldexpires != $expires)
		$controller->setParam('expires', $expires);
	$controller->setParam('userfiletmp', $userfiletmp);
	$controller->setParam('userfilename', $userfilename);
	$controller->setParam('filetype', $fileType);
	$controller->setParam('userfiletype', $userfiletype);
	$controller->setParam('reviewers', $reviewers);
	$controller->setParam('approvers', $approvers);
	$controller->setParam('recipients', $recipients);
	$controller->setParam('attributes', $attributes);
	$controller->setParam('workflow', $workflow);
	$controller->setParam('initialdocumentstatus', $settings->_initialDocumentStatus);

	if(!$content = $controller()) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText($controller->getErrorMsg()));
	} else {
		if($controller->hasHook('cleanUpDocument')) {
			$controller->callHook('cleanUpDocument', $document, $file);
		}
		// Send notification to subscribers.
		if($notifier) {
			$notifier->sendNewDocumentVersionMail($document, $user);

			$notifier->sendChangedExpiryMail($document, $user, $oldexpires);
		}

		if($settings->_removeFromDropFolder) {
			if(file_exists($userfiletmp)) {
				unlink($userfiletmp);
			}
		}
	}

add_log_line("?documentid=".$documentid);
header("Location:../out/out.ViewDocument.php?documentid=".$documentid);

?>
