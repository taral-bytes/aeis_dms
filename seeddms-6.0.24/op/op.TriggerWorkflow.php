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
if(!checkFormKey('triggerworkflow')) {
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

if (!isset($_POST["version"]) || !is_numeric($_POST["version"]) || intval($_POST["version"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$version_num = $_POST["version"];
$version = $document->getContentByVersion($version_num);
if (!is_object($version)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$workflow = $version->getWorkflow();
if (!is_object($workflow)) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("document_has_no_workflow"));
}

$transition = $dms->getWorkflowTransition($_POST["transition"]);
if (!is_object($transition)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_workflow_transition"));
}

if(!$version->triggerWorkflowTransitionIsAllowed($user, $transition)) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("access_denied"));
}

$workflow = $transition->getWorkflow();

if(isset($GLOBALS['SEEDDMS_HOOKS']['triggerWorkflowTransition'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['triggerWorkflowTransition'] as $hookObj) {
		if (method_exists($hookObj, 'preTriggerWorkflowTransition')) {
			$hookObj->preTriggerWorkflowTransition(null, array('version'=>$version, 'transition'=>$transition, 'comment'=>$_POST["comment"]));
		}
	}
}

$overallStatus = $version->getStatus();
if($ret = $version->triggerWorkflowTransition($user, $transition, $_POST["comment"])) {
	/* $ret is the next state if it was entered otherwise it is just true */
	if ($notifier) {
		$wkflog = $version->getLastWorkflowLog();
		$notifier->sendTriggerWorkflowTransitionMail($version, $user, $wkflog);

		if(is_object($ret)) {
			$notifier->sendRequestWorkflowActionMail($version, $user, $transition);
			if($overallStatus['status'] != $version->getStatus()['status']) {
				$notifier->sendChangedDocumentStatusMail($version, $user, $overallStatus["status"]);
			}
		}
	}

	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_trigger_workflow')));

	if(isset($GLOBALS['SEEDDMS_HOOKS']['triggerWorkflowTransition'])) {
		foreach($GLOBALS['SEEDDMS_HOOKS']['triggerWorkflowTransition'] as $hookObj) {
			if (method_exists($hookObj, 'postTriggerWorkflowTransition')) {
				$hookObj->postTriggerWorkflowTransition(null, array('version'=>$version, 'transition'=>$transition, 'comment'=>$_POST["comment"]));
			}
		}
	}
} else {
	$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('error_trigger_workflow')));
}

add_log_line("?documentid=".$documentid."&version".$version_num);

if($version->getStatus()['status'] == S_IN_WORKFLOW)
	header("Location:../out/out.ViewDocument.php?documentid=".$documentid."&currenttab=workflow");
else
	header("Location:../out/out.ViewDocument.php?documentid=".$documentid);
