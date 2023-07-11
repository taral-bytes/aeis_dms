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

function getBaseData($colname, $coldata, $objdata) { /* {{{ */
	$objdata[$colname] = $coldata;
	return $objdata;
} /* }}} */

function renderBaseData($colname, $objdata) { /* {{{ */
	return $objdata[$colname];
} /* }}} */

function getBooleanData($colname, $coldata, $objdata) { /* {{{ */
	$objdata[$colname] = $coldata == '1';
	return $objdata;
} /* }}} */

function renderBooleanData($colname, $objdata) { /* {{{ */
	return $objdata[$colname] ? '1' : '0';
} /* }}} */

function getPasswordPlainData($colname, $coldata, $objdata) { /* {{{ */
	/* Setting 'passenc' to null will not update the password */
	$objdata['passenc'] = $coldata ? seed_pass_hash($coldata) : null;
	return $objdata;
} /* }}} */

function renderPasswordHashedData($colname, $objdata) { /* {{{ */
	return substr($objdata[$colname], 0, 16).'...';
} /* }}} */

function renderPasswordPlainData($colname, $objdata) { /* {{{ */
	return $objdata[$colname];
} /* }}} */

function getQuotaData($colname, $coldata, $objdata) { /* {{{ */
	$objdata[$colname] = SeedDMS_Core_File::parse_filesize($coldata);
	return $objdata;
} /* }}} */

function renderQuotaData($colname, $objdata) { /* {{{ */
	return SeedDMS_Core_File::format_filesize($objdata[$colname]);
} /* }}} */

function getFolderData($colname, $coldata, $objdata) { /* {{{ */
	global $dms;
	if($coldata) {
		if($folder = $dms->getFolder((int)$coldata)) {
			$objdata['homefolder'] = $folder;
		} else {
			$objdata['homefolder'] = null;
			$objdata['__logs__'][] = array('type'=>'error', 'msg'=> "No such folder with id '".(int) $coldata."'");
		}
	} else {
		$objdata['homefolder'] = null;
	}
	return $objdata;
} /* }}} */

function renderFolderData($colname, $objdata) { /* {{{ */
	return is_object($objdata[$colname]) ? $objdata[$colname]->getName() : '';
} /* }}} */

function getGroupData($colname, $coldata, $objdata) { /* {{{ */
	global $dms;
	/* explode column name to extract index of group. Actually, the whole column
	 * name could be used as well, as it is just a unique index in the array
	 * of groups.
	 */
	$kk = explode('_', $colname);
	if(count($kk) == 2)
		$gn = $kk[1];
	else
		$gn = '1';
	if(!isset($objdata['groups']))
		$objdata['groups'] = [];
	/* $coldata can be empty, if an imported users is assigned to less groups
	 * than group columns exists.
	 */
	if($coldata) {
		if($group = $dms->getGroupByName($coldata)) {
			$objdata['groups'][$gn] = $group;
		} else {
			$objdata['__logs__'][] = array('type'=>'error', 'msg'=> "No such group with name '".$coldata."'");
		}
	}
	return $objdata;
} /* }}} */

function renderGroupData($colname, $objdata) { /* {{{ */
	$html = '';
	$kk = explode('_', $colname);
	if(count($kk) == 2)
		$gn = $kk[1];
	else
		$gn = '1';
	if(!empty($objdata['groups'][$gn]))
		$html .= $objdata['groups'][$gn]->getName();
	return $html;
} /* }}} */

function getRoleData($colname, $coldata, $objdata) { /* {{{ */
	global $dms;
	if($role = $dms->getRoleByName($coldata)) {
		$objdata['role'] = $role;
	} else {
		$objdata['role'] = null;
		$objdata['__logs__'][] = array('type'=>'error', 'msg'=> "No such role with name '".$coldata."'");
	}
	return $objdata;
} /* }}} */

function renderRoleData($colname, $objdata) { /* {{{ */
	$html = '';
	if($objdata[$colname])
		$html .= $objdata[$colname]->getName();
	return $html;
} /* }}} */

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$log = array();
$newusers = array();
$csvheader = array();
$colmap = array();
if (isset($_FILES['userdata']) && $_FILES['userdata']['error'] == 0) {
	if(!is_uploaded_file($_FILES["userdata"]["tmp_name"]))
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));

	if($_FILES["userdata"]["size"] == 0)
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("uploading_zerosize"));

	$csvdelim = ';';
	$csvencl = '"';
	if($fp = fopen($_FILES['userdata']['tmp_name'], 'r')) {
		/* First of all build up a column map, which contains for each columen
		 * the column name
		 * (taken from the first line of the csv file), a function for getting
		 * interpreting the data from the csv file and a function to return the
		 * interpreted data as a string.
		 * The column map will only contain entries for known column (whose head
		 * line is one of 'login', 'email', 'name', 'role', 'homefolder', etc.)
		 * Unknown columns will be skipped and the index in the column map will
		 * be left out.
		 */
		if($csvheader = fgetcsv($fp, 0, $csvdelim, $csvencl)) {
			foreach($csvheader as $i=>$colname) {
				$colname = trim($colname);
				if(substr($colname, 0, 5) == 'group') {
					$colmap[$i] = array("getGroupData", "renderGroupData", $colname);
				} elseif(in_array($colname, array('role'))) {
					$colmap[$i] = array("getRoleData", "renderRoleData", $colname);
				} elseif(in_array($colname, array('homefolder'))) {
					$colmap[$i] = array("getFolderData", "renderFolderData", $colname);
				} elseif(in_array($colname, array('quota'))) {
					$colmap[$i] = array("getQuotaData", "renderQuotaData", $colname);
				} elseif(in_array($colname, array('passenc'))) {
					$colmap[$i] = array("getBaseData", "renderPasswordHashedData", $colname);
				} elseif(in_array($colname, array('password'))) {
					/* getPasswordPlainData() will set 'passenc' */
					$colmap[$i] = array("getPasswordPlainData", "renderPasswordPlainData", 'passenc');
				} elseif(in_array($colname, array('login', 'name', 'passenc', 'email', 'comment', 'group'))) {
					$colmap[$i] = array("getBaseData", "renderBaseData", $colname);
				} elseif(in_array($colname, array('disabled', 'hidden'))) {
					$colmap[$i] = array("getBooleanData", "renderBooleanData", $colname);
				} elseif(substr($colname, 0, 5) == 'attr:') {
					$kk = explode(':', $colname, 2);
					if(($attrdef = $dms->getAttributeDefinitionByName($kk[1])) || ($attrdef = $dms->getAttributeDefinition((int) $kk[1]))) {
						$colmap[$i] = array("getAttributeData", "renderAttributeData", $attrdef);
					}
				}
			}
		}
//		echo "<pre>";print_r($colmap);echo "</pre>";
		if(count($colmap) > 1) {
			$allusers = $dms->getAllUsers();
			$userids = array();
			foreach($allusers as $muser)
				$userids[$muser->getLogin()] = $muser;
			/* Run through all records in the csv file and fill $newusers.
			 * $newusers will contain an associated array for each record, with
			 * the key being the column name. The array may be shorter than
			 * the number of columns, because $colmap may not contain a mapping
			 * for each column.
			 */
			$newusers = array();
			while(!feof($fp)) {
				if($data = fgetcsv($fp, 0, $csvdelim, $csvencl)) {
					$md = array();
					foreach($data as $i=>$coldata) {
						/* First check if a column mapping exists. It could be missing
						 * because the column has a not known header or it is missing.
						 */
						if(isset($colmap[$i])) {
							$md = call_user_func($colmap[$i][0], $colmap[$i][2], $coldata, $md);
						}
					}
					if($md && $md['login'])
						$newusers[$md['login']] = $md;
				}
			}
//			echo "<pre>";print_r($newusers);echo "</pre>";exit;
			$makeupdate = !empty($_POST['update']);
			foreach($newusers as $uhash=>$u) {
				$log[$uhash] = [];
				if($eu = $dms->getUserByLogin($u['login'])) {
					if(isset($u['name']) && $u['name'] != $eu->getFullName()) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Name of user updated. '".$u['name']."' != '".$eu->getFullName()."'");
						if($makeupdate)
							$eu->setFullName($u['name']);
					}
					if(isset($u['email']) && $u['email'] != $eu->getEmail()) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Email of user updated. '".$u['email']."' != '".$eu->getEmail()."'");
						if($makeupdate)
							$eu->setEmail($u['email']);
					}
					if(isset($u['passenc']) && !is_null($u['passenc']) && $u['passenc'] != $eu->getPwd()) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Encrypted password of user updated. '".$u['passenc']."' != '".$eu->getPwd()."'");
						if($makeupdate)
							$eu->setPwd($u['passenc']);
					}
					if(isset($u['comment']) && $u['comment'] != $eu->getComment()) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Comment of user updated. '".$u['comment']."' != '".$eu->getComment()."'");
						if($makeupdate)
							$eu->setComment($u['comment']);
					}
					if(isset($u['language']) && $u['language'] != $eu->getLanguage()) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Language of user updated. '".$u['language']."' != '".$eu->getLanguage()."'");
						if($makeupdate)
							$eu->setLanguage($u['language']);
					}
					if(isset($u['quota']) && $u['quota'] != $eu->getQuota()) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Quota of user updated. '".$u['quota']."' != '".$eu->getQuota()."'");
						if($makeupdate)
							$eu->setQuota($u['quota']);
					}
					if(isset($u['disabled']) && $u['disabled'] != $eu->isDisabled()) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Disabled flag of user updated. '".$u['disabled']."' != '".$eu->isDisabled()."'");
						if($makeupdate)
							$eu->setDisabled($u['disabled']);
					}
					if(isset($u['hidden']) && $u['hidden'] != $eu->isHidden()) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Hidden flag of user updated. '".$u['hidden']."' != '".$eu->isHidden()."'");
						if($makeupdate)
							$eu->setHidden($u['hidden']);
					}
					if(isset($u['homefolder']) && $u['homefolder']->getId() != $eu->getHomeFolder()) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Homefolder of user updated. '".(is_object($u['homefolder']) ? $u['homefolder']->getId() : '')."' != '".($eu->getHomeFolder() ? $eu->getHomeFolder() : '')."'");
						if($makeupdate)
							$eu->setHomeFolder($u['homefolder']);
					}
					$func = function($o) {return $o->getID();};
					if(isset($u['groups']) && implode(',',array_map($func, $u['groups'])) != implode(',',array_map($func, $eu->getGroups()))) {
						$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "Groups of user updated. '".implode(',',array_map($func, $u['groups']))."' != '".implode(',',array_map($func, $eu->getGroups()))."'");
						if($makeupdate) {
							foreach($eu->getGroups() as $g)
								$eu->leaveGroup($g);
							foreach($u['groups'] as $g)
								$eu->joinGroup($g);
						}
					}
//					$log[$uhash][] = array('id'=>$eu->getLogin(), 'type'=>'success', 'msg'=> "User '".$eu->getLogin()."' updated.");
				} else {
					if(!empty($u['login']) && !empty($u['name']) && !empty($u['email'])) {
						if(!empty($_POST['addnew'])) {
							$ret = $dms->addUser($u['login'], !empty($u['passenc']) ? $u['passenc'] : '', $u['name'], $u['email'], !empty($u['language']) ? $u['language'] : 'en_GB', 'bootstrap', !empty($u['comment']) ? $u['comment'] : '', $u['role']);
							if($ret) {
								$log[$uhash][] = array('id'=>$u['login'], 'type'=>'success', 'msg'=> "User '".$u['name']."' added.");
								foreach($u['groups'] as $g) {
									if($g)
										$ret->joinGroup($g);
								}
							} else
								$log[$uhash][] = array('id'=>$u['login'], 'type'=>'error', 'msg'=> "User '".$u['name']."' could not be added.");
						} else {
//							$log[$uhash][] = array('id'=>$u['login'], 'type'=>'success', 'msg'=> "User '".$u['name']."' can be added.");
						}
					} else {
						$log[$uhash][] = array('id'=>$u['login'], 'type'=>'error', 'msg'=> "Too much data missing");
					}
				}
			}
		}
	}
}

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if($view) {
	$view->setParam('log', $log);
	$view->setParam('newusers', $newusers);
	$view->setParam('colmap', $colmap);
	$view->setParam('accessobject', $accessop);
	$view($_GET);
	exit;
}

