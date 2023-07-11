<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010-2011 Matteo Lucarelli
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

if(!isset($settings))
	require_once("../inc/inc.Settings.php");
require_once("inc/inc.Utils.php");
require_once("inc/inc.LogInit.php");
require_once("inc/inc.Language.php");
require_once("inc/inc.Init.php");
require_once("inc/inc.Extension.php");
require_once("inc/inc.DBInit.php");
require_once("inc/inc.ClassUI.php");
require_once("inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if (!$accessop->check_view_access($view, $_GET)) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$listtype = 'listRepair';
if (isset($_GET["list"])) {
	$listtype = $_GET['list'];
}

if(isset($_GET['repair']) && $_GET['repair'] == 1) {
	$repair = 1;
} else {
	$repair = 0;
}

if(isset($_GET['unlink']) && $_GET['unlink'] == 1) {
	$unlink = 1;
} else {
	$unlink = 0;
}

if(isset($_GET['setfilesize']) && $_GET['setfilesize'] == 1) {
	$setfilesize = 1;
} else {
	$setfilesize = 0;
}

if(isset($_GET['setchecksum']) && $_GET['setchecksum'] == 1) {
	$setchecksum = 1;
} else {
	$setchecksum = 0;
}

if(isset($_GET['setfiletype']) && $_GET['setfiletype'] == 1) {
	$setfiletype = 1;
} else {
	$setfiletype = 0;
}

$folder = $dms->getRootFolder(); //getFolder($settings->_rootFolderID);
$unlinkedversions = $dms->getUnlinkedDocumentContent();
if(!isset($_GET['action']) || $_GET['action'] == 'listUnlinkedFolders')
	$unlinkedfolders = $dms->checkFolders();
else
	$unlinkedfolders = null;
if(!isset($_GET['action']) || $_GET['action'] == 'listUnlinkedDocuments')
	$unlinkeddocuments = $dms->checkDocuments();
else
	$unlinkeddocuments = null;
if(!isset($_GET['action']) || $_GET['action'] == 'listMissingFileSize')
	$nofilesizeversions = $dms->getNoFileSizeDocumentContent();
else
	$nofilesizeversions = null;
if(!isset($_GET['action']) || $_GET['action'] == 'listMissingChecksum')
	$nochecksumversions = $dms->getNoChecksumDocumentContent();
else
	$nochecksumversions = null;
if(!isset($_GET['action']) || $_GET['action'] == 'listWrongFiletype')
	$wrongfiletypeversions = $dms->getWrongFiletypeDocumentContent();
else
	$wrongfiletypeversions = null;
if(!isset($_GET['action']) || $_GET['action'] == 'listDuplicateContent')
	$duplicateversions = $dms->getDuplicateDocumentContent();
else
	$duplicateversions = null;
if(!isset($_GET['action']) || $_GET['action'] == 'listDuplicateSequence')
	$duplicatesequences = $dms->getDuplicateSequenceNo();
else
	$duplicatesequences = null;
$processwithoutusergroup = array();
foreach(array('review', 'approval', 'receipt', 'revision') as $process) {
	foreach(array('user', 'group') as $ug) {
		if(!isset($_GET['action']) || $_GET['action'] == 'list'.ucfirst($process).'Without'.ucfirst($ug)) {
			if(isset($_GET['repair']) && $_GET['repair'])
				$dms->removeProcessWithoutUserGroup($process, $ug, isset($_GET['required']) ? $_GET['required'] : '');
			$processwithoutusergroup[$process][$ug] = $dms->getProcessWithoutUserGroup($process, $ug);
		}
	}
}
$docsinrevision = array();
$docsmissingrevsiondate = array();
if(!isset($_GET['action']) || $_GET['action'] == 'listDocsWithMissingRevisionDate') {
$tmprevs = $dms->getDocumentsInRevision();
foreach($tmprevs as $rev) {
	if($doc = $dms->getDocument($rev['documentID'])) {
		$content = $doc->getContentByVersion($rev['version']);
		$isdisabled = false;
		if($rev['type'] == 0) {
			$ruser = $dms->getUser($rev['required']);
			$isdisabled = $ruser->isDisabled();
			$mode = $doc->getAccessMode($ruser);
			$cmode = $content->getAccessMode($ruser);
		} elseif($rev['type'] == 1) {
			$rgroup = $dms->getGroup($rev['required']);
			$mode = $doc->getGroupAccessMode($rgroup);
			$cmode = M_READ;
		}
		/* Caution: $content->getAccessMode($ruser) doesn't work as it uses the role
		 * restrictions of the currently logged in user
		 */
		if($mode < M_READ || $cmode < M_READ || $isdisabled)
			$docsinrevision[] = $doc;

		/* If a document has a sleeping revisor then it must have a
		 * revision date, otherwise the revision will never be started.
		 */
		if($rev['status'] == S_LOG_SLEEPING) {
			if(!$content->getRevisionDate())
				$docsmissingrevsiondate[] = $doc;
		}
	}
}
}

$docsinreception = array();
if(!isset($_GET['action']) || $_GET['action'] == 'listDocsInReceptionNoAccess') {
$tmprevs = $dms->getDocumentsInReception();
foreach($tmprevs as $rev) {
	if($doc = $dms->getDocument($rev['documentID'])) {
		$isdisabled = false;
		if($rev['type'] == 0) {
			$ruser = $dms->getUser($rev['required']);
			$isdisabled = $ruser->isDisabled();
			$mode = $doc->getAccessMode($ruser);
			$content = $doc->getContentByVersion($rev['version']);
			$cmode = $content->getAccessMode($ruser);
		} elseif($rev['type'] == 1) {
			$rgroup = $dms->getGroup($rev['required']);
			$mode = $doc->getGroupAccessMode($rgroup);
			$cmode = M_READ;
		}
		/* Caution: $content->getAccessMode($ruser) doesn't work as it uses the role
		 * restrictions of the currently logged in user
		 */
		if($mode < M_READ || $cmode < M_READ || $isdisabled)
			$docsinreception[] = $doc;
	}
}
}

$rootfolder = $dms->getRootFolder(); //getFolder($settings->_rootFolderID);

function repair_tree($dms, $user, $folder, $path=':') { /* {{{ */
	$objects = array();

	/* Don't do folderlist check for root folder */
	if($path != ':') {
		/* If the path contains a folder id twice, the a cyclic relation
		 * exists.
		 */
		$tmparr = explode(':', $path);
		array_shift($tmparr);
		if(count($tmparr) != count(array_unique($tmparr))) {
			$objects[] = array('object'=>$folder, 'msg'=>'Folder path contains cyclic relation');
		}
		$folderList = $folder->getFolderList();
		/* Check the folder */
		if($folderList != $path) {
			$objects[] = array('object'=>$folder, 'msg'=>"Folderlist is '".$folderList."', should be '".$path);
		}
	}

	$subfolders = $folder->getSubFolders();
	foreach($subfolders as $subfolder) {
		$objects = array_merge($objects, repair_tree($dms, $user, $subfolder, $path.$folder->getId().':'));
	}
	$path .= $folder->getId().':';
	$documents = $folder->getDocuments();
	foreach($documents as $document) {
		/* Check the folder list of the document */
		$folderList = $document->getFolderList();
		if($folderList != $path) {
			$objects[] = array('object'=>$document, 'msg'=>"Folderlist is '".$folderList."', should be '".$path);
		}

		/* Check if the content is available */
		$versions = $document->getContent();
		if($versions) {
			foreach($versions as $version) {
				$filepath = $dms->contentDir . $version->getPath();
				if(!file_exists($filepath)) {
					$objects[] = array('object'=>$version, 'msg'=>'Document content is missing');
				}
			}
		} else {
			$objects[] = array('object'=>$version, 'msg'=>'Document has no content at all');
		}
	}

	return $objects;
} /* }}} */
if(!isset($_GET['action']) || $_GET['action'] == 'listRepair')
	$repairobjects = repair_tree($dms, $user, $folder);
else
	$repairobjects = null;

if(isset($_GET['repairfolderid']) && is_numeric($_GET['repairfolderid']))
	$repairfolder = $dms->getFolder($_GET['repairfolderid']);
else
	$repairfolder = null;

if($view) {
	$view->setParam('folder', $folder);
	$view->setParam('showtree', showtree());
	$view->setParam('listtype', $listtype);
	$view->setParam('unlinkedcontent', $unlinkedversions);
	$view->setParam('unlinkedfolders', $unlinkedfolders);
	$view->setParam('unlinkeddocuments', $unlinkeddocuments);
	$view->setParam('nofilesizeversions', $nofilesizeversions);
	$view->setParam('nochecksumversions', $nochecksumversions);
	$view->setParam('wrongfiletypeversions', $wrongfiletypeversions);
	$view->setParam('duplicateversions', $duplicateversions);
	$view->setParam('duplicatesequences', $duplicatesequences);
	$view->setParam('docsinrevision', $docsinrevision);
	$view->setParam('docsmissingrevsiondate', $docsmissingrevsiondate);
	$view->setParam('docsinreception', $docsinreception);
	$view->setParam('processwithoutusergroup', $processwithoutusergroup);
	$view->setParam('unlink', $unlink);
	$view->setParam('setfilesize', $setfilesize);
	$view->setParam('setchecksum', $setchecksum);
	$view->setParam('setfiletype', $setfiletype);
	$view->setParam('repair', $repair);
	$view->setParam('repairfolder', $repairfolder);
	$view->setParam('showtree', showtree());
	$view->setParam('rootfolder', $rootfolder);
	$view->setParam('repairobjects', $repairobjects);
	$view->setParam('cachedir', $settings->_cacheDir);
	$view->setParam('timeout', $settings->_cmdTimeout);
	$view->setParam('enableRecursiveCount', $settings->_enableRecursiveCount);
	$view->setParam('maxRecursiveCount', $settings->_maxRecursiveCount);
	$view->setParam('accessobject', $accessop);
	$view->setParam('conversionmgr', $conversionmgr);
	$view->setParam('previewWidthList', $settings->_previewWidthList);
	$view->setParam('convertToPdf', $settings->_convertToPdf);
	$view->setParam('previewConverters', isset($settings->_converters['preview']) ? $settings->_converters['preview'] : array());
	$view->setParam('timeout', $settings->_cmdTimeout);
	$view->setParam('xsendfile', $settings->_enableXsendfile);
	$view($_GET);
	exit;
}
