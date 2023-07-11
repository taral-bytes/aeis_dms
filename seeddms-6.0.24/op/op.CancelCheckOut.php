<?php
//    SeedDMS. Document Management System
//    Copyright (C) 2015 Uwe Steinmann
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
if(!checkFormKey('cancelcheckout')) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);

$checkoutstatus = $document->checkOutStatus();
/* Check out of files which has been changed, can only be canceled if allowed in the configuration */
if($checkoutstatus == 0 && empty($settings->_enableCancelCheckout)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("operation_disallowed"));
}

if(empty($_POST['confirm'])) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("operation_disallowed"));
}

if(!$document->cancelCheckOut()) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_cancel_checkout"));
}
$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_cancel_checkout')));
add_log_line("?documentid=".$documentid);
header("Location:../out/out.ViewDocument.php?documentid=".$documentid);
