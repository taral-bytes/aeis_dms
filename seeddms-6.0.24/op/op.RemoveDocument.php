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
include("../inc/inc.ClassController.php");
include("../inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if (!$accessop->check_controller_access($controller, $_POST)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("access_denied"));
}

/* Check if the form data comes from a trusted request */
if(!checkFormKey('removedocument')) {
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

if ($document->getAccessMode($user, 'removeDocument') < M_ALL) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("access_denied"));
}

/* FIXME: whether a document is locked or not, doesn't make a difference,
 * because M_ALL access right is used in any case.
 */
if($document->isLocked()) {
	$lockingUser = $document->getLockingUser();
	if (($lockingUser->getID() != $user->getID()) && ($document->getAccessMode($user, 'removeDocument') != M_ALL)) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("lock_message", array("email" => $lockingUser->getEmail(), "username" => htmlspecialchars($lockingUser->getFullName()))));
	}
}

$folder = $document->getFolder();

/* Remove all preview images. */
$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir);
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

$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_rm_document')));

add_log_line("?documentid=".$documentid);

header("Location:../out/out.ViewFolder.php?folderid=".$folder->getID());

?>
