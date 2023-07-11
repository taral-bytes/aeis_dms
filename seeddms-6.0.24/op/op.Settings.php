<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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


function getBoolValue($post_name)
{
  $out = false;
  if (isset($_POST[$post_name]))
    if ($_POST[$post_name]=="on")
      $out = true;

  return $out;
}

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

/* Check if the form data comes from a trusted request */
if(!checkFormKey('savesettings')) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (isset($_POST["action"])) $action=$_POST["action"];
else if (isset($_GET["action"])) $action=$_GET["action"];
else $action=NULL;

// --------------------------------------------------------------------------
if ($action == "saveSettings")
{
	/**
	 * First check if config var is actually set in POST request. Hidden conf
	 * vars will not be included and may not override existing conf vars.
	 */
	function setStrValue($name) {
		global $_POST, $settings;
		if(isset($_POST[$name]) && !in_array($name, $settings->_hiddenConfFields))
			$settings->{"_".$name} = $_POST[$name];
	}
	function setBoolValue($name) {
		global $_POST, $settings;
		if(!in_array($name, $settings->_hiddenConfFields)) {
			if (isset($_POST[$name]) && $_POST[$name]=="on")
				$settings->{"_".$name} = true;
			else
				$settings->{"_".$name} = false;
		}
	}
	function setIntValue($name) {
		global $_POST, $settings;
		if(isset($_POST[$name]) && !in_array($name, $settings->_hiddenConfFields))
			$settings->{"_".$name} = intval($_POST[$name]);
	}
	function setArrayValue($name) {
		global $_POST, $settings;
		if(!in_array($name, $settings->_hiddenConfFields)) {
			if(isset($_POST[$name]) && $_POST[$name])
				$settings->{"_".$name} = $_POST[$name];
			else
				$settings->{"_".$name} = array();
		}
	}
	function setDirValue($name) {
		global $_POST, $settings;
		if(isset($_POST[$name]) && !in_array($name, $settings->_hiddenConfFields))
			$settings->{"_".$name} = addDirSep($_POST[$name]);
	}
  // -------------------------------------------------------------------------
  // get values
  // -------------------------------------------------------------------------
  // SETTINGS - SITE - DISPLAY
	setStrValue('siteName');
	setStrValue('footNote');
	setBoolValue('printDisclaimer');
	setStrValue('language');
	setStrValue('dateformat');
	setStrValue('datetimeformat');
	setArrayValue('availablelanguages');
	setStrValue('theme');
	setBoolValue('overrideTheme');
	setBoolValue('onePageMode');
	setIntValue('previewWidthList');
	setIntValue('previewWidthMenuList');
	setIntValue('previewWidthDropFolderList');
	setIntValue('previewWidthDetail');
	setBoolValue('showFullPreview');
	setBoolValue('convertToPdf');
	setIntValue('maxItemsPerPage');
	setIntValue('incItemsPerPage');
	setBoolValue('markdownComments');

  // SETTINGS - SITE - EDITION
	setBoolValue('strictFormCheck');
	setBoolValue('inlineEditing');
	setArrayValue('noDocumentFormFields');
	setArrayValue('noFolderFormFields');
	if(isset($_POST['viewOnlineFileTypes']) && !in_array('viewOnlineFileTypes', $settings->_hiddenConfFields))
		$settings->setViewOnlineFileTypesFromString($_POST["viewOnlineFileTypes"]);
	if(isset($_POST['editOnlineFileTypes']) && !in_array('editOnlineFileTypes', $settings->_hiddenConfFields))
		$settings->setEditOnlineFileTypesFromString($_POST["editOnlineFileTypes"]);
	setBoolValue('enableConverting');
	setBoolValue('enableEmail');
	setBoolValue('enableUsersView');
	setBoolValue('enableFullSearch');
	setIntValue('maxSizeForFullText');
	setStrValue('fullSearchEngine');
	setStrValue('defaultSearchMethod');
	setStrValue('suggestTerms');
  setBoolValue("showSingleSearchHit");
  setBoolValue("enableSessionList");
  setBoolValue("enableClipboard");
  setBoolValue("enableMenuTasks");
  $settings->_tasksInMenu = isset($_POST["tasksInMenu"]) ? $_POST["tasksInMenu"] : array();
  setBoolValue("enableDropFolderList");
  setBoolValue("enableDropUpload");
  setBoolValue("enableMultiUpload");
  setBoolValue("enableFolderTree");
  setBoolValue("enableRecursiveCount");
  setIntValue("maxRecursiveCount");
  setBoolValue("enableLanguageSelector");
  setBoolValue("enableHelp");
  setBoolValue("enableThemeSelector");
  setIntValue("expandFolderTree");
  setStrValue("stopWordsFile");
  setStrValue("sortUsersInList");
  setStrValue("sortFoldersDefault");
  setStrValue("defaultDocPosition");
  setStrValue("defaultFolderPosition");
  setIntValue("libraryFolder");

  // SETTINGS - SITE - WEBDAV
	setBoolValue("enableWebdavReplaceDoc");

  // SETTINGS - SITE - CALENDAR
	setBoolValue("enableCalendar");
	setStrValue("calendarDefaultView");
  setIntValue("firstDayOfWeek");

  // SETTINGS - SITE - EXTENSIONMGR
	setBoolValue("enableExtensionDownload");
	setBoolValue("enableExtensionImport");
	setBoolValue("enableExtensionImportFromRepository");

  // SETTINGS - SYSTEM - SERVER
	setDirValue("rootDir");
	setStrValue("baseUrl");
	setStrValue("httpRoot");
  setDirValue("contentDir");
  setDirValue("cacheDir");
  setDirValue("stagingDir");
  setDirValue("luceneDir");
  setDirValue("extraPath");
  setDirValue("dropFolderDir");
  setDirValue("backupDir");
  setDirValue("checkOutDir");
  setBoolValue("createCheckOutDir");
  setDirValue("repositoryUrl");
  setDirValue("proxyUrl");
  setDirValue("proxyUser");
  setDirValue("proxyPassword");
	setBoolValue("logFileEnable");
	setStrValue("logFileRotation");
	setBoolValue("enableLargeFileUpload");
	setStrValue("partitionSize"); // TODO: check if valid value, e.g. 1M or 5K
	setStrValue("maxUploadSize"); // TODO: check if valid value, e.g. 1M or 5K
  setBoolValue("enableXsendfile");

  // SETTINGS - SYSTEM - AUTHENTICATION
  setBoolValue("enableGuestLogin");
  setBoolValue("enableGuestAutoLogin");
  setBoolValue("enable2FactorAuthentication");
  setBoolValue("restricted");
  setBoolValue("enableUserImage");
  setBoolValue("disableSelfEdit");
  setBoolValue("enablePasswordForgotten");
	setIntValue("passwordStrength");
	setStrValue("passwordStrengthAlgorithm");
  setIntValue("passwordExpiration");
  setIntValue("passwordHistory");
  setIntValue("loginFailure");
  setIntValue("autoLoginUser");
  setIntValue("quota");
  setArrayValue("undelUserIds");
	setStrValue("encryptionKey");
  setIntValue("cookieLifetime");
  setIntValue("defaultAccessDocs");

  // TODO Connectors

	// SETTINGS - SYSTEM - DATABASE
	setStrValue('dbDriver');
	setStrValue('dbHostname');
	setStrValue('dbDatabase');
	setStrValue('dbUser');
	setStrValue('dbPass');

  // SETTINGS - SYSTEM - SMTP
  setStrValue("smtpServer");
  setIntValue("smtpPort");
  setStrValue("smtpSendFrom");
  setStrValue("smtpUser");
  setStrValue("smtpPassword");

  // SETTINGS -ADVANCED - DISPLAY
  setStrValue("siteDefaultPage");
  setIntValue("rootFolderID");
  setBoolValue("useHomeAsRootFolder");
  setBoolValue("showMissingTranslations");

  // SETTINGS - ADVANCED - AUTHENTICATION
  setIntValue("guestID");
  setStrValue("adminIP");
  setStrValue("apiKey");
  setIntValue("apiUserId");
  setStrValue("apiOrigin");

  // SETTINGS - ADVANCED - EDITION
  setStrValue("versioningFileName");
  setStrValue("presetExpirationDate");
  setStrValue("initialDocumentStatus");
  setStrValue("workflowMode");
  setBoolValue("enableReceiptWorkflow");
  setBoolValue("enableReceiptReject");
  setBoolValue("enableRevisionWorkflow");
  setBoolValue("enableRevisionOnVoteReject");
  setBoolValue("allowReviewerOnly");
  setBoolValue("allowChangeRevAppInProcess");
  setBoolValue("enableAdminRevApp");
  setBoolValue("enableOwnerRevApp");
  setBoolValue("enableSelfRevApp");
  setBoolValue("enableSelfReceipt");
  setBoolValue("enableUpdateRevApp");
  setBoolValue("enableRemoveRevApp");
  setBoolValue("enableAdminReceipt");
  setBoolValue("enableOwnerReceipt");
  setBoolValue("enableUpdateReceipt");
  setBoolValue("enableFilterReceipt");
  setBoolValue("enableVersionDeletion");
  setBoolValue("enableVersionModification");
  setBoolValue("enableDuplicateDocNames");
  setBoolValue("enableDuplicateSubFolderNames");
  setBoolValue("enableCancelCheckout");
  setBoolValue("overrideMimeType");
  setBoolValue("advancedAcl");
  setBoolValue("removeFromDropFolder");
  setBoolValue("uploadedAttachmentIsPublic");

  // SETTINGS - ADVANCED - NOTIFICATION
  setBoolValue("enableOwnerNotification");
  setBoolValue("enableNotificationAppRev");
  setBoolValue("enableNotificationWorkflow");

  // SETTINGS - ADVANCED - SERVER
  setStrValue("coreDir");
  setStrValue("luceneClassDir");
  setIntValue("contentOffsetDir");
  setIntValue("maxDirID");
  setIntValue("updateNotifyTime");
  setIntValue("maxExecutionTime");
	if(isset($_POST['cmdTimeout']) && !in_array('cmdTimeout', $settings->_hiddenConfFields))
		$settings->_cmdTimeout = (intval($_POST["cmdTimeout"]) > 0) ?intval($_POST["cmdTimeout"]) : 5;
  setBoolValue("enableDebugMode");

	// SETTINGS - ADVANCED - INDEX CMD
	if(isset($_POST['converters']) && !in_array('converters', $settings->_hiddenConfFields)) {
	if(isset($_POST["converters"]["fulltext"]))
		$settings->_converters['fulltext'] = $_POST["converters"]["fulltext"];
	else
		$settings->_converters['fulltext'] = $_POST["converters"];
	$newmimetype = preg_replace('#[^A-Za-z0-9_/+.*-]+#', '', $settings->_converters["fulltext"]["newmimetype"]);
	if($newmimetype && trim($settings->_converters['fulltext']['newcmd']))
		$settings->_converters['fulltext'][$newmimetype] = trim($settings->_converters['fulltext']['newcmd']);
	unset($settings->_converters['fulltext']['newmimetype']);
	unset($settings->_converters['fulltext']['newcmd']);

	foreach(array('preview', 'pdf') as $target) {
		if(isset($_POST["converters"][$target])) {
			$settings->_converters[$target] = $_POST["converters"][$target];
			$newmimetype = preg_replace('#[^A-Za-z0-9_/+.*-]+#', '', $settings->_converters[$target]["newmimetype"]);
			if($newmimetype && trim($settings->_converters[$target]['newcmd']))
				$settings->_converters[$target][$newmimetype] = trim($settings->_converters[$target]['newcmd']);
			unset($settings->_converters[$target]['newmimetype']);
			unset($settings->_converters[$target]['newcmd']);
		}
	}
	}

	// SETTINGS - EXTENSIONS
	if(isset($_POST['extensions'])) {
		foreach($_POST['extensions'] as $extname=>$conf) {
			if(!in_array($extname.'|', $settings->_hiddenConfFields)) {
				foreach($conf as $confname=>$confval) {
					if(!in_array($extname.'|'.$confname, $settings->_hiddenConfFields)) {
						$settings->_extensions[$extname][$confname] = $confval;
					}
				}
			}
		}
	}

  // -------------------------------------------------------------------------
  // save
  // -------------------------------------------------------------------------
  if (!$settings->save())
    UI::exitError(getMLText("admin_tools"),getMLText("settings_SaveError"));

	add_log_line(".php&action=savesettings");
}

$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_settings_saved')));


header("Location:../out/out.Settings.php?currenttab=".$_POST['currenttab']);

?>
