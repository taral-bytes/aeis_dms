<?php
//    SeedDMS. Document Management System
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

if (!isset($_GET["targetid"]) || !is_numeric($_GET["targetid"]) || $_GET["targetid"]<1) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_target_folder"));
}
$targetid = $_GET["targetid"];
$folder = $dms->getFolder($targetid);
if (!is_object($folder)) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_target_folder"));
}

if ($folder->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (empty($_GET["dropfolderfileform1"])) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_target_folder"));
}

$dirname = realpath($settings->_dropFolderDir.'/'.$user->getLogin()."/".$_GET["dropfolderfileform1"]);
if(strpos($dirname, realpath($settings->_dropFolderDir.'/'.$user->getLogin().'/')) !== 0 || !is_dir($dirname)) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_dropfolder_folder"));
}

function getBaseData($colname, $coldata, $objdata) { /* {{{ */
	$objdata[$colname] = $coldata;
	return $objdata;
} /* }}} */

function getAttributeData($attrdef, $coldata, $objdata) { /* {{{ */
	$objdata['attributes'][$attrdef->getID()] = $coldata;
	return $objdata;
} /* }}} */

function getCategoryData($colname, $coldata, $objdata) { /* {{{ */
	global $catids;
	$kk = explode(',', $coldata);
	$objdata['category'][] = array();
	foreach($kk as $k) {
		if(isset($catids[$k]))
			$objdata['category'][] = $catids[$k];
	}
	return $objdata;
} /* }}} */

function getUserData($colname, $coldata, $objdata) { /* {{{ */
	global $userids;
	if(isset($userids[$coldata]))
		$objdata['owner'] = $userids[$coldata];
	return $objdata;
} /* }}} */

$metadata = array();
if(!empty($_GET["dropfolderfileform2"])) {
	$metadatafile = realpath($settings->_dropFolderDir.'/'.$user->getLogin()."/".$_GET["dropfolderfileform2"]);
	$csvdelim = ';';
	$csvencl = '"';
	if($fp = fopen($metadatafile, 'r')) {
		$colmap = array();
		if($header = fgetcsv($fp, 0, $csvdelim, $csvencl)) {
			foreach($header as $i=>$colname) {
				$colname = trim($colname);
				if(in_array($colname, array('category'))) {
					$colmap[$i] = array("getCategoryData", $colname);
				} elseif(in_array($colname, array('owner'))) {
					$colmap[$i] = array("getUserData", $colname);
				} elseif(in_array($colname, array('filename', 'category', 'name', 'comment'))) {
					$colmap[$i] = array("getBaseData", $colname);
				} elseif(substr($colname, 0, 5) == 'attr:') {
					$kk = explode(':', $colname, 2);
					if(($attrdef = $dms->getAttributeDefinitionByName($kk[1])) || ($attrdef = $dms->getAttributeDefinition((int) $kk[1]))) {
						$colmap[$i] = array("getAttributeData", $attrdef);
					}
				}
			}
		}
//		echo "<pre>";print_r($colmap);echo "</pre>";
		if(count($colmap) > 1) {
			$nameprefix = dirname($dirname).'/';
			$allcats = $dms->getDocumentCategories();
			$catids = array();
			foreach($allcats as $cat)
				$catids[$cat->getName()] = $cat;
			$allusers = $dms->getAllUsers();
			$userids = array();
			foreach($allusers as $muser)
				$userids[$muser->getLogin()] = $muser;
			while(!feof($fp)) {
				if($data = fgetcsv($fp, 0, $csvdelim, $csvencl)) {
					$mi = $nameprefix.$data[$colmap['filename']];
//					$metadata[$mi] = array('category'=>array());
					$md = array();
					$md['attributes'] = array();
					foreach($data as $i=>$coldata) {
						if(isset($colmap[$i])) {
							$md = call_user_func($colmap[$i][0], $colmap[$i][1], $coldata, $md);
						}
					}
					if(!empty($md['filename']))
						$metadata[$nameprefix.$md['filename']] = $md;
				}
			}
		}
	}
}
//echo "<pre>";print_r($metadata);echo "</pre>";
//exit;

$setfiledate = false;
if(isset($_GET['setfiledate']) && $_GET["setfiledate"]) {
	$setfiledate = true;
}

$setfolderdate = false;
if(isset($_GET['setfolderdate']) && $_GET["setfolderdate"]) {
	$setfolderdate = true;
}

function import_folder($dirname, $folder, $setfiledate, $setfolderdate, $metadata) { /* {{{ */
	global $user, $doccount, $foldercount;

	$d = dir($dirname);
	$sequence = 1;
	while(false !== ($entry = $d->read())) {
		$path = $dirname.'/'.$entry;
		if($entry != '.' && $entry != '..' && $entry != '.svn') {
			if(is_file($path)) {
				$name = utf8_basename($path);
				$filetmp = $path;

				$reviewers = array();
				$approvers = array();
				$version_comment = '';
				$reqversion = 1;
				$expires = false;
				$keywords = '';
				$categories = array();

				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mimetype = finfo_file($finfo, $path);
				$lastDotIndex = strrpos($name, ".");
				if (is_bool($lastDotIndex) && !$lastDotIndex) $filetype = ".";
				else $filetype = substr($name, $lastDotIndex);

				$docname = !empty($metadata[$path]['name']) ? $metadata[$path]['name'] : $name;
				$comment = !empty($metadata[$path]['comment']) ? $metadata[$path]['comment'] : '';
				$owner = !empty($metadata[$path]['owner']) ? $metadata[$path]['owner'] : $user;

				echo $mimetype." - ".$filetype." - ".$path."<br />\n";
				if($res = $folder->addDocument($docname, $comment, $expires, $owner, $keywords,
																		!empty($metadata[$path]['category']) ? $metadata[$path]['category'] : array(), $filetmp, $name,
																		$filetype, $mimetype, $sequence, $reviewers,
																		$approvers, $reqversion, $version_comment,
																	 	!empty($metadata[$path]['attributes']) ? $metadata[$path]['attributes'] : array())) {
					$doccount++;
					if($setfiledate) {
						$newdoc = $res[0];
						$newdoc->setDate(filemtime($path));
						$lc = $newdoc->getLatestContent();
						$lc->setDate(filemtime($path));
					}
				} else {
					echo "Error importing ".$path."<br />";
					echo "<pre>".print_r($res, true)."</pre>";
//					return false;
				}
				set_time_limit(30);
			} elseif(is_dir($path)) {
				$name = utf8_basename($path);
				if($newfolder = $folder->addSubFolder($name, '', $user, $sequence)) {
					$foldercount++;
					if($setfolderdate) {
						$newfolder->setDate(filemtime($path));
					}
					if(!import_folder($path, $newfolder, $setfiledate, $setfolderdate, $metadata))
						return false;
				} else {
//					return false;
				}
			}
			$sequence++;
		}
	}
	return true;
} /* }}} */

$foldercount = $doccount = 0;
if($newfolder = $folder->addSubFolder($_GET["dropfolderfileform1"], '', $user, 1)) {
	if($setfolderdate) {
		$newfolder->setDate(filemtime($dirname));
	}
	if(!import_folder($dirname, $newfolder, $setfiledate, $setfolderdate, $metadata))
		$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('error_importfs')));
	else {
		if(isset($_GET['remove']) && $_GET["remove"]) {
			$cmd = 'rm -rf '.$dirname;
			$ret = null;
			system($cmd, $ret);
		}
		$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_importfs', array('docs'=>$doccount, 'folders'=>$foldercount))));
	}
} else {
	$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('error_importfs')));
}

header("Location:../out/out.ViewFolder.php?folderid=".$newfolder->getID());
