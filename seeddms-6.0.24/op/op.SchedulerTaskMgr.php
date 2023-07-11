<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2012 Uwe Steinmann
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
include("../inc/inc.Scheduler.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if ($user->isGuest()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (isset($_POST["action"])) $action=$_POST["action"];
else $action=NULL;

$scheduler = new SeedDMS_Scheduler($dms->getDB());

// add new task ---------------------------------------------------
if ($action == "addtask") { /* {{{ */
	
	/* Check if the form data comes for a trusted request */
	if(!checkFormKey('addtask')) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_request_token"));
	}

	$extension = $_POST["extension"];
	$task    = $_POST["task"];
	$name    = $_POST["name"];
	$description = $_POST["description"];
	$frequency = $_POST["frequency"];
	$disabled = isset($_POST["disabled"]) ? $_POST["disabled"] : 0;
	$params = isset($_POST["params"]) ? $_POST["params"] : null;

	$newtask = $scheduler->addTask($extension, $task, $name, $description, $frequency, $disabled, $params);
	if ($newtask) {
	}
	else UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	
	$taskid=$newtask->getID();
	
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_add_task')));

	add_log_line(".php&action=addtask&name=".$name);
} /* }}} */

// modify task ----------------------------------------------------
else if ($action == "edittask") { /* {{{ */

	/* Check if the form data comes for a trusted request */
	if(!checkFormKey('edittask')) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_request_token"));
	}

	if (!isset($_POST["taskid"]) || !is_numeric($_POST["taskid"]) || intval($_POST["taskid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_task"));
	}
	
	$taskid=$_POST["taskid"];
	$editedtask = $scheduler->getTask($taskid);
	
	if (!is_object($editedtask)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_task"));
	}

	$name = $_POST["name"];
	$description = $_POST["description"];
	$frequency = $_POST["frequency"];
	$disabled = isset($_POST["disabled"]) ? $_POST["disabled"] : 0;
	$params = isset($_POST["params"]) ? $_POST["params"] : null;

	if ($editedtask->getName() != $name)
		$editedtask->setName($name);
	if ($editedtask->getDescription() != $description)
		$editedtask->setDescription($description);
	$editedtask->setDisabled($disabled);
	$editedtask->setParameter($params);
	if($editedtask->setFrequency($frequency))
		$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_edit_task')));
	else
		$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('error_edit_task')));
	add_log_line(".php&action=edittask&taskid=".$taskid);
} /* }}} */

// delete task -------------------------------------------------------------
else if ($action == "removetask") { /* {{{ */
	header('Content-Type: application/json');
	
	/* Check if the form data comes from a trusted request */
	if(!checkFormKey('removetask')) {
		echo json_encode(array('success'=>false, 'message'=>getMLText("invalid_request_token")));
		exit;
	}

	if (!isset($_POST["taskid"]) || !is_numeric($_POST["taskid"]) || intval($_POST["taskid"])<1) {
		echo json_encode(array('success'=>false, 'message'=>getMLText("invalid_task")));
		exit;
	}
	
	$taskid=$_POST["taskid"];
	$task = $scheduler->getTask($taskid);
	
	if (!is_object($task)) {
		echo json_encode(array('success'=>false, 'message'=>getMLText("invalid_task")));
		exit;
	}

	if (!$task->remove()) {
		echo json_encode(array('success'=>false, 'message'=>getMLText("error_occured")));
		exit;
	}
	
	add_log_line("?taskid=".$_POST["taskid"]."&action=removetask");

	echo json_encode(array('success'=>true, 'message'=>getMLText("task_removed")));
	exit;
} /* }}} */


header("Location:../out/out.SchedulerTaskMgr.php");

