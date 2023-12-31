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

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

/* Check if the form data comes from a trusted request */
if(!checkFormKey('removeworkflowaction', 'GET')) {
	UI::exitError(getMLText("workflow_editor"), getMLText("invalid_request_token"));
}

if (!isset($_GET["workflowactionid"]) || !is_numeric($_GET["workflowactionid"]) || intval($_GET["workflowactionid"])<1) {
	UI::exitError(getMLText("workflow_editor"), getMLText("invalid_version"));
}

$workflowaction = $dms->getWorkflowAction($_GET["workflowactionid"]);
if (!is_object($workflowaction)) {
	UI::exitError(getMLText("workflow_editor"), getMLText("invalid_workflow_action"));
}

if($workflowaction->remove()) {
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_rm_workflow_action')));
} else {
	$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('error_rm_workflow_action')));
}

add_log_line("?workflowactionid=".$_GET["workflowactionid"]);

header("Location:../out/out.WorkflowActionsMgr.php");
?>
