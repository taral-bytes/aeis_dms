<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2106 Uwe Steinmann
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

/* if post_max_size is to small, then $_POST will not be set and the content
 * lenght will exceed post_max_size
 */
if(empty($_POST) && $_SERVER['CONTENT_LENGTH'] > SeedDMS_Core_File::parse_filesize(ini_get('post_max_size'))) {
	UI::exitError(getMLText("folder_title", array("foldername" => '')),getMLText("uploading_postmaxsize"));
}

/* Check if the form data comes from a trusted request */
if(!checkFormKey('adddocument')) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderid = $_POST["folderid"];
$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user, 'addDocument') < M_READWRITE) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

if($settings->_quota > 0) {
	$remain = checkQuota($user);
	if ($remain < 0) {
		UI::exitError(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))),getMLText("quota_exceeded", array('bytes'=>SeedDMS_Core_File::format_filesize(abs($remain)))));
	}
}

$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if ($accessop->check_controller_access($controller, array('action'=>'setOwner'))) {
	$ownerid = (int) $_POST["ownerid"];
	if($ownerid) {
		if(!($owner = $dms->getUser($ownerid))) {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));
		}
	} else {
		$owner = $user;
	}
} else {
	$owner = $user;
}
$comment  = trim($_POST["comment"]);
$version_comment = !empty($_POST["version_comment"]) ? trim($_POST["version_comment"]) : '';
if($version_comment == "" && isset($_POST["use_comment"]))
	$version_comment = $comment;

$keywords = isset($_POST["keywords"]) ? trim($_POST["keywords"]) : '';
$categories = isset($_POST["categories"]) ? $_POST["categories"] : null;
$cats = array();
if($categories) {
	foreach($categories as $catid) {
		if($cat = $dms->getDocumentCategory($catid))
			$cats[] = $cat;
	}
}

if(isset($_POST["attributes"]))
	$attributes = $_POST["attributes"];
else
	$attributes = array();
/* Has been moved to controller
foreach($attributes as $attrdefid=>$attribute) {
	if($attrdef = $dms->getAttributeDefinition($attrdefid)) {
		if($attribute) {
			if(!$attrdef->validate($attribute)) {
				$errmsg = getAttributeValidationText($attrdef->getValidationError(), $attrdef->getName(), $attribute);
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())), $errmsg);
			}
		} elseif($attrdef->getMinValues() > 0) {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("attr_min_values", array("attrname"=>$attrdef->getName())));
		}
	}
}
 */

if(isset($_POST["attributes_version"]))
	$attributes_version = $_POST["attributes_version"];
else
	$attributes_version = array();
/* Has been moved to controller
foreach($attributes_version as $attrdefid=>$attribute) {
	$attrdef = $dms->getAttributeDefinition($attrdefid);
	if($attribute) {
		if(!$attrdef->validate($attribute)) {
			$errmsg = getAttributeValidationText($attrdef->getValidationError(), $attrdef->getName(), $attribute);
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),$errmsg);
		}
	}
}
 */

$reqversion = !empty($_POST['reqversion']) ? (int)$_POST["reqversion"] : 0;
if ($reqversion<1) $reqversion=1;

$sequence = $_POST["sequence"];
$sequence = str_replace(',', '.', $_POST["sequence"]);
if (!is_numeric($sequence)) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_sequence"));
}

switch($_POST["presetexpdate"]) {
case "date":
	$expires = makeTsFromDate($_POST["expdate"]);
//	$tmp = explode('-', $_POST["expdate"]);
//	if(count($tmp) != 3)
//		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("malformed_expiration_date"));
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
		if (isset($_POST["indReviewers"])) {
			foreach ($_POST["indReviewers"] as $ind) {
				$reviewers["i"][] = $ind;
			}
		}
		// Retrieve the list of reviewer groups from the form.
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
	if (isset($_POST["indApprovers"])) {
		foreach ($_POST["indApprovers"] as $ind) {
			$approvers["i"][] = $ind;
		}
	}
	// Retrieve the list of approver groups from the form.
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
		/* Check if reviewers are set but no approvers */
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
				/* Do not add the uploader itself and reviewers */
				if(!$settings->_enableFilterReceipt || ($member->getID() != $user->getID() && !in_array($member->getID(), $reviewers['i'])))
					if(!in_array($member->getID(), $recipients["i"]))
						$recipients["i"][] = $member->getID();
			}
		}
	}
}

function reArrayFiles(&$file_post) {
	$file_ary = array();
	$file_count = count($file_post['name']);
	$file_keys = array_keys($file_post);

	for ($i=0; $i<$file_count; $i++) {
		if($file_post['error'][$i] != 4) { // no file uploaded
		foreach ($file_keys as $key) {
			$file_ary[$i][$key] = $file_post[$key][$i];
		}
		$file_ary[$i]['source'] = 'upload';
		}
	}

	return $file_ary;
}

if(!empty($_FILES['userfile'])) {
	$file_ary = reArrayFiles($_FILES['userfile']);
} else {
	$file_ary = array();
}

if($settings->_dropFolderDir) {
	if(isset($_POST["dropfolderfileadddocform"]) && $_POST["dropfolderfileadddocform"]) {
		$fullfile = $settings->_dropFolderDir.'/'.$user->getLogin().'/'.$_POST["dropfolderfileadddocform"];
		if(file_exists($fullfile)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimetype = finfo_file($finfo, $fullfile);
			$file_ary[] = array(
				'tmp_name' => $fullfile,
				'type' => $mimetype,
				'name' => $_POST["dropfolderfileadddocform"],
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
	foreach($uuids as $i=>$uuid) {
		$fullfile = $settings->_stagingDir.'/'.utf8_basename($uuid);
		if(file_exists($fullfile)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimetype = finfo_file($finfo, $fullfile);
			$file_ary[] = array(
				'tmp_name' => $fullfile,
				'type' => $mimetype,
				'name' => isset($names[$i]) ? $names[$i] : $uuid,
				'size' => filesize($fullfile),
				'error' => 0,
				'source' => 'upload',
			);
		}
	}
}

if($settings->_libraryFolder) {
	if(isset($_POST["librarydoc"]) && $_POST["librarydoc"]) {
		if($clonedoc = $dms->getDocument($_POST["librarydoc"])) {
			if($content = $clonedoc->getLatestContent()) {
				$docsource = 'library';
				$fullfile = tempnam(sys_get_temp_dir(), '');
				if(SeedDMS_Core_File::copyFile($dms->contentDir . $content->getPath(), $fullfile)) {
					if($_POST["name"]!="") {
						$oext = pathinfo($content->getOriginalFileName(), PATHINFO_EXTENSION);
						$origfilename = getFilenameByDocname(trim($_POST['name'])).".".$oext;
					} else
						$origfilename = $content->getOriginalFileName();
					$file_ary[] = array(
						'tmp_name' => $fullfile,
						'type' => $content->getMimeType(),
						'name' => $origfilename,
						'size' => $content->getFileSize(),
						'error' => 0,
						'source' => 'library',
					);
				}
			}
		}
	}
}
if($controller->hasHook('getDocument')) {
	$file_ary = array_merge($file_ary, $controller->callHook('getDocument', $_POST));
}

if(!$file_ary) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_failed"));
}

/* Check if additional notification shall be added */
$notusers = array();
if(!empty($_POST['notification_users'])) {
	foreach($_POST['notification_users'] as $notuserid) {
		$notuser = $dms->getUser($notuserid);
		if($notuser) {
			$notusers[] = $notuser;
		}
	}
}
$notgroups = array();
if(!empty($_POST['notification_groups'])) {
	foreach($_POST['notification_groups'] as $notgroupid) {
		$notgroup = $dms->getGroup($notgroupid);
		if($notgroup) {
			$notgroups[] = $notgroup;
		}
	}
}

/* Check files for Errors first */
$maxuploadsize = SeedDMS_Core_File::parse_filesize($settings->_maxUploadSize);
foreach($file_ary as $file) {
	if($file['error']==1) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_maxsize"));
	}
	if($file['error']!=0) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_failed"));
	}
	if ($file["size"]==0) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_zerosize"));
	}
	if ($maxuploadsize && $file["size"] > $maxuploadsize) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_maxsize"));
	}
}

foreach($file_ary as $file) {
	$userfiletmp = $file["tmp_name"];
	$userfiletype = $file["type"];
	$userfilename = $file["name"];
	
	$fileType = ".".pathinfo($userfilename, PATHINFO_EXTENSION);

	if($settings->_overrideMimeType) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$tmpfiletype = finfo_file($finfo, $userfiletmp);
		if($tmpfiletype != 'application/octet-stream')
			$userfiletype = $tmpfiletype;
	}

	if ($_POST["name"]!="")
		$name = trim($_POST["name"]);
	else $name = utf8_basename($userfilename);

	/* Check if name already exists in the folder */
	if(!$settings->_enableDuplicateDocNames) {
		if($folder->hasDocumentByName($name)) {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("document_duplicate_name"));
		}
	}

	$controller->setParam('documentsource', $file['source']);
	$controller->setParam('documentsourcedetails', !empty($file['source_details']) ? $file['source_details'] : null);
	$controller->setParam('folder', $folder);
	$controller->setParam('fulltextservice', $fulltextservice);
	$controller->setParam('name', $name);
	$controller->setParam('comment', $comment);
	$controller->setParam('expires', $expires);
	$controller->setParam('keywords', $keywords);
	$controller->setParam('categories', $cats);
	$controller->setParam('owner', $owner);
	$controller->setParam('userfiletmp', $userfiletmp);
	$controller->setParam('userfilename', $userfilename);
	$controller->setParam('filetype', $fileType);
	$controller->setParam('userfiletype', $userfiletype);
	$controller->setParam('sequence', $sequence);
	$controller->setParam('reviewers', $reviewers);
	$controller->setParam('approvers', $approvers);
	$controller->setParam('recipients', $recipients);
	$controller->setParam('reqversion', $reqversion);
	$controller->setParam('versioncomment', $version_comment);
	$controller->setParam('attributes', $attributes);
	$controller->setParam('attributesversion', $attributes_version);
	$controller->setParam('workflow', $workflow);
	$controller->setParam('notificationgroups', $notgroups);
	$controller->setParam('notificationusers', $notusers);
	$controller->setParam('initialdocumentstatus', $settings->_initialDocumentStatus);
	$controller->setParam('maxsizeforfulltext', $settings->_maxSizeForFullText);
	$controller->setParam('defaultaccessdocs', $settings->_defaultAccessDocs);

	if(!$document = $controller()) {
		$err = $controller->getErrorMsg();
		if(is_string($err))
			$errmsg = getMLText($err);
		elseif(is_array($err)) {
			$errmsg = getMLText($err[0], $err[1]);
		} else {
			$errmsg = $err;
		}
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),$errmsg);
	} else {
		if($controller->hasHook('cleanUpDocument')) {
			$controller->callHook('cleanUpDocument', $document, $file);
		}
		// Send notification to subscribers of folder.
		if($notifier) {
			$notifier->sendNewDocumentMail($document, $user);
		}
		if($settings->_removeFromDropFolder) {
			if(file_exists($userfiletmp)) {
				unlink($userfiletmp);
			}
		}
	}

	add_log_line("?name=".$name."&folderid=".$folderid);
}

header("Location:../out/out.ViewFolder.php?folderid=".$folderid."&showtree=".$_POST["showtree"]);
?>
