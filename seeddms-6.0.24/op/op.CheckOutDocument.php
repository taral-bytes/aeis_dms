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

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_GET["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if(!$settings->_checkOutDir) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("checkout_is_disabled"));
}

if ($document->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if ($document->isLocked()) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("document_already_locked"));
}

if ($document->isCheckedOut()) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("document_already_checkedout"));
}

if($session->getSu()) {
	$origuser = $dms->getUser($session->getUser());
	$checkoutpath = sprintf($settings->_checkOutDir.'/', preg_replace('/[^A-Za-z0-9_-]/', '', $origuser->getLogin()));
} else {
	$checkoutpath = sprintf($settings->_checkOutDir.'/', preg_replace('/[^A-Za-z0-9_-]/', '', $user->getLogin()));
}
if(!file_exists($checkoutpath) && $settings->_createCheckOutDir) {
	SeedDMS_Core_File::makeDir($checkoutpath);
}
if(!file_exists($checkoutpath)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("checkoutpath_does_not_exist"));
}

if (!$document->checkOut($user, $checkoutpath)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
}

$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_document_checkedout')));

add_log_line();
header("Location:../out/out.ViewDocument.php?documentid=".$documentid);

?>

