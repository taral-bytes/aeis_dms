<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
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

function getTime() {
	if (function_exists('microtime')) {
		$tm = microtime();
		$tm = explode(' ', $tm);
		return (float) sprintf('%f', $tm[1] + $tm[0]);
	}
	return time();
}

// Redirect to the search page if the navigation search button has been
// selected without supplying any search terms.
if (isset($_GET["navBar"])) {
	if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
		$folderid=$settings->_rootFolderID;
	} else {
		$folderid = $_GET["folderid"];
	}
}

$includecontent = false;
if (isset($_GET["includecontent"]) && $_GET["includecontent"])
	$includecontent = true;

$newowner = null;
if (isset($_GET["newowner"]) && is_numeric($_GET["newowner"]) && $_GET['newowner'] > 0) {
	$newowner = $dms->getUser((int) $_GET['newowner']);
}

$changecategory = null;
if (isset($_GET["changecategory"]) && is_numeric($_GET["changecategory"]) && $_GET['changecategory'] > 0) {
	$changecategory = $dms->getDocumentCategory((int) $_GET['changecategory']);
}
$removecategory = 0;
if (isset($_GET["removecategory"]) && is_numeric($_GET["removecategory"]) && $_GET['removecategory'] > 0) {
	$removecategory = (int) $_GET['removecategory'];
}

/* Creation date {{{ */
$createstartts = null;
$createstartdate = null;
$createendts = null;
$createenddate = null;
$created['from'] = null;
$created['to'] = null;
if(!empty($_GET["created"]["from"])) {
	$createstartts = makeTsFromDate($_GET["created"]["from"]);
	$createstartdate = array('year'=>(int)date('Y', $createstartts), 'month'=>(int)date('m', $createstartts), 'day'=>(int)date('d', $createstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
	if (!checkdate($createstartdate['month'], $createstartdate['day'], $createstartdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
	}
	$created['from'] = $createstartts;
}
if(!empty($_GET["created"]["to"])) {
	$createendts = makeTsFromDate($_GET["created"]["to"]);
	$createenddate = array('year'=>(int)date('Y', $createendts), 'month'=>(int)date('m', $createendts), 'day'=>(int)date('d', $createendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
	if (!checkdate($createenddate['month'], $createenddate['day'], $createenddate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
	}
	$created['to'] = $createendts;
}
/* }}} */

/* Modification date {{{ */
$modifystartts = null;
$modifystartdate = null;
$modifyendts = null;
$modifyenddate = null;
$modified['from'] = null;
$modified['to'] = null;
if(!empty($_GET["modified"]["from"])) {
	$modifystartts = makeTsFromDate($_GET["modified"]["from"]);
	$modifystartdate = array('year'=>(int)date('Y', $modifystartts), 'month'=>(int)date('m', $modifystartts), 'day'=>(int)date('d', $modifystartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
	if (!checkdate($modifystartdate['month'], $modifystartdate['day'], $modifystartdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_modification_date_end"));
	}
	$modified['from'] = $modifystartts;
}
if(!empty($_GET["modified"]["to"])) {
	$modifyendts = makeTsFromDate($_GET["modified"]["to"]);
	$modifyenddate = array('year'=>(int)date('Y', $modifyendts), 'month'=>(int)date('m', $modifyendts), 'day'=>(int)date('d', $modifyendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
	if (!checkdate($modifyenddate['month'], $modifyenddate['day'], $modifyenddate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_modification_date_end"));
	}
	$modified['to'] = $modifyendts;
}
/* }}} */

// Check to see if the search has been restricted to a particular
// document owner.
// $_GET['owner'] can be a name of an array of names or ids {{{
$owner = [];
$ownernames = []; // Needed by fulltext search
$ownerobjs = []; // Needed by database search
if(!empty($_GET["owner"])) {
	$owner = $_GET['owner'];
	if (!is_array($_GET['owner'])) {
		if(is_numeric($_GET['owner']))
			$o = $dms->getUser($_GET['owner']);
		else
			$o = $dms->getUserByLogin($_GET['owner']);
		if($o) {
			$ownernames[] = $o->getLogin();
			$ownerobjs[] = $o;
		}
	} else {
		foreach($_GET["owner"] as $l) {
			if($l) {
				if(is_numeric($l))
					$o = $dms->getUser($l);
				else
					$o = $dms->getUserByLogin($l);
				if($o) {
					$ownernames[] = $o->getLogin();
					$ownerobjs[] = $o;
				}
			}
		}
	}
} /* }}} */

	// category {{{
	$categories = array();
	$categorynames = array();
	$category = array();
	if(isset($_GET['category']) && $_GET['category']) {
		$category = $_GET['category'];
		foreach($_GET['category'] as $catid) {
			if($catid) {
				if(is_numeric($catid)) {
					if($cat = $dms->getDocumentCategory($catid)) {
						$categories[] = $cat;
						$categorynames[] = $cat->getName();
					}
				} else {
					$categorynames[] = $catid;
				}
			}
		}
	} /* }}} */

	if (isset($_GET["orderby"]) && is_string($_GET["orderby"])) {
		$orderby = $_GET["orderby"];
	}
	else {
		$orderby = "";
	}

$terms = [];
$limit = (isset($_GET["limit"]) && is_numeric($_GET["limit"])) ? (int) $_GET['limit'] : 20;
$fullsearch = ((!isset($_GET["fullsearch"]) && $settings->_defaultSearchMethod == 'fulltext') || !empty($_GET["fullsearch"])) && $settings->_enableFullSearch;
$facetsearch = !empty($_GET["facetsearch"]) && $settings->_enableFullSearch;
if($fullsearch) {
// Search in Fulltext {{{
	if (isset($_GET["query"]) && is_string($_GET["query"])) {
		$query = $_GET["query"];
//		if(isset($_GET['action']) && ($_GET['action'] == 'typeahead'))
//			$query .= '*';
	}
	else {
		$query = "";
	}

	//
	// Get the page number to display. If the result set contains more than
	// 25 entries, it is displayed across multiple pages.
	//
	// This requires that a page number variable be used to track which page the
	// user is interested in, and an extra clause on the select statement.
	//
	// Default page to display is always one.
	$pageNumber=1;
	if (isset($_GET["pg"])) {
		if (is_numeric($_GET["pg"]) && $_GET["pg"]>0) {
			$pageNumber = (integer)$_GET["pg"];
		}
		elseif (!strcasecmp($_GET["pg"], "all")) {
			$pageNumber = "all";
		}
	}

	// --------------- Suche starten --------------------------------------------

	// Check to see if the search has been restricted to a particular
	// mimetype. {{{
	$mimetype = [];
	if (isset($_GET["mimetype"])) {
		if (!is_array($_GET['mimetype'])) {
			if(!empty($_GET['mimetype']))
				$mimetype[] = $_GET['mimetype'];
		} else {
			foreach($_GET["mimetype"] as $l) {
				if($l)
					$mimetype[] = $l;
			}
		}
	} /* }}} */

	/* Creation date {{{ 
	$createstartts = null;
	$createstartdate = null;
	$createendts = null;
	$createenddate = null;
	$created = [];
	if(!empty($_GET["created"]["from"])) {
		$createstartts = makeTsFromDate($_GET["created"]["from"]);
		$createstartdate = array('year'=>(int)date('Y', $createstartts), 'month'=>(int)date('m', $createstartts), 'day'=>(int)date('d', $createstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
		if (!checkdate($createstartdate['month'], $createstartdate['day'], $createstartdate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
		}
		$created['from'] = $createstartts;
	}
	if(!empty($_GET["created"]["to"])) {
		$createendts = makeTsFromDate($_GET["created"]["to"]);
		$createenddate = array('year'=>(int)date('Y', $createendts), 'month'=>(int)date('m', $createendts), 'day'=>(int)date('d', $createendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
		if (!checkdate($createenddate['month'], $createenddate['day'], $createenddate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
		}
		$created['to'] = $createendts;
	}
	 }}} */

	// status
	if(isset($_GET['status']))
		$status = $_GET['status'];
	else
		$status = array();

	// record_type
	if(isset($_GET['record_type']))
		$record_type = $_GET['record_type'];
	else
		$record_type = array();

	if (isset($_GET["attributes"]))
		$attributes = $_GET["attributes"];
	else
		$attributes = array();

	foreach($attributes as $an=>&$av) {
		if(substr($an, 0, 5) == 'attr_') {
			$tmp = explode('_', $an);
			if($attrdef = $dms->getAttributeDefinition($tmp[1])) {
				switch($attrdef->getType()) {
				/* Turn dates into timestamps */
				case SeedDMS_Core_AttributeDefinition::type_date:
					foreach(['from', 'to'] as $kk)
						if(!empty($av[$kk])) {
							if(!is_numeric($av[$kk])) {
								$av[$kk] = makeTsFromDate($av[$kk]);
							}
						}
					break;
				}
			}
		}
	}

	/* Create $order array for fulltext search */
	$order = ['by'=>'', 'dir'=>''];
	switch($orderby) {
	case 'dd':
		$order = ['by'=>'created', 'dir'=>'desc'];
		break;
	case 'd':
		$order = ['by'=>'created', 'dir'=>'asc'];
		break;
	case 'nd':
		$order = ['by'=>'title', 'dir'=>'desc'];
		break;
	case 'n':
		$order = ['by'=>'title', 'dir'=>'asc'];
		break;
	case 'id':
		$order = ['by'=>'id', 'dir'=>'desc'];
		break;
	case 'i':
		$order = ['by'=>'id', 'dir'=>'asc'];
		break;
	default:
		$order = ['by'=>'', 'dir'=>''];
	}

	//print_r($attributes);exit;
	// Check to see if the search has been restricted to a particular sub-tree in
	// the folder hierarchy.
	$startFolder = null;
	if (isset($_GET["folderfullsearchid"]) && is_numeric($_GET["folderfullsearchid"]) && $_GET["folderfullsearchid"]>0) {
		$targetid = $_GET["folderfullsearchid"];
		$startFolder = $dms->getFolder($targetid);
		if (!is_object($startFolder)) {
			UI::exitError(getMLText("search"),getMLText("invalid_folder_id"));
		}
	}

	$rootFolder = $dms->getFolder($settings->_rootFolderID);

	$startTime = getTime();
	if($settings->_fullSearchEngine == 'lucene') {
		Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
	}

	if(strlen($query) < 4 && strpos($query, '*')) {
		$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('splash_invalid_searchterm')));
		$dcount = 0;
		$totalPages = 0;
		$entries = array();
		$searchTime = 0;
	} else {
		$startTime = getTime();
//		$limit = 20;
		$total = 0;
		$index = $fulltextservice->Indexer();
		if($index) {
			if(!empty($settings->_suggestTerms) && !empty($_GET['query'])) {
				$st = preg_split("/[\s,]+/", trim($_GET['query']));
				if($lastterm = end($st))
					$terms = $index->terms($lastterm, $settings->_suggestTerms);
			}
			$lucenesearch = $fulltextservice->Search();
			$searchresult = $lucenesearch->search($query, array('record_type'=>$record_type, 'owner'=>$ownernames, 'status'=>$status, 'category'=>$categorynames, 'user'=>$user->isAdmin() ? [] : [$user->getLogin()], 'mimetype'=>$mimetype, 'startFolder'=>$startFolder, 'rootFolder'=>$rootFolder, 'created_start'=>$createstartts, 'created_end'=>$createendts, 'modified_start'=>$modifystartts, 'modified_end'=>$modifyendts, 'attributes'=>$attributes), ($pageNumber == 'all' ? array() : array('limit'=>$limit, 'offset'=>$limit * ($pageNumber-1))), $order);
			if($searchresult === false) {
				$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('splash_invalid_searchterm')));
				$dcount = 0;
				$fcount = 0;
				$totalPages = 0;
				$entries = array();
				$facets = array();
				$searchTime = 0;
			} else {
				$entries = array();
				$facets = $searchresult['facets'];
				$dcount = 0;
				$fcount = 0;
				if($searchresult['hits']) {
					foreach($searchresult['hits'] as $hit) {
						if($hit['document_id'][0] == 'D') {
							if($tmp = $dms->getDocument(substr($hit['document_id'], 1))) {
//								if($tmp->getAccessMode($user) >= M_READ) {
									$tmp->verifyLastestContentExpriry();
									$entries[] = $tmp;
									$dcount++;
//								}
							}
						} elseif($hit['document_id'][0] == 'F') {
							if($tmp = $dms->getFolder(substr($hit['document_id'], 1))) {
//								if($tmp->getAccessMode($user) >= M_READ) {
									$entries[] = $tmp;
									$fcount++;
//								}
							}
						}
					}
					if(isset($facets['record_type'])) {
						$fcount = isset($facets['record_type']['folder']) ? $facets['record_type']['folder'] : 0;
						$dcount = isset($facets['record_type']['document']) ? $facets['record_type']['document'] : 0 ;
					}
				}
				if(/* $pageNumber != 'all' && */$searchresult['count'] > $limit) {
					$totalPages = (int) ($searchresult['count']/$limit);
					if($searchresult['count']%$limit)
						$totalPages++;
//					if($limit > 0)
//						$entries = array_slice($entries, ($pageNumber-1)*$limit, $limit);
				} else {
					$totalPages = 1;
				}
				$total = $searchresult['count'];
			}
			$searchTime = getTime() - $startTime;
			$searchTime = round($searchTime, 2);
		} else {
			$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('splash_invalid_search_service')));
			$dcount = 0;
			$fcount = 0;
			$totalPages = 0;
			$entries = array();
			$facets = array();
			$searchTime = 0;
		}
	}
	$reception = array();
	// }}}
} else {
	// Search in Database {{{
	if (isset($_GET["query"]) && is_string($_GET["query"])) {
		$query = $_GET["query"];
	}
	else {
		$query = "";
	}

	/* Select if only documents (0x01), only folders (0x02) or both (0x03)
	 * are found
	 */
	$resultmode = 0x03;
	if (isset($_GET["resultmode"]) && is_numeric($_GET["resultmode"])) {
			$resultmode = $_GET['resultmode'];
	}

	$mode = "AND";
	if (isset($_GET["mode"]) && is_numeric($_GET["mode"]) && $_GET["mode"]==0) {
			$mode = "OR";
	}

	$searchin = array();
	if (isset($_GET['searchin']) && is_array($_GET["searchin"])) {
		foreach ($_GET["searchin"] as $si) {
			if (isset($si) && is_numeric($si)) {
				switch ($si) {
					case 1: // keywords
					case 2: // name
					case 3: // comment
					case 4: // attributes
					case 5: // id
						$searchin[$si] = $si;
						break;
				}
			}
		}
	}

	// if none is checkd search all
	if (count($searchin)==0) $searchin=array(1, 2, 3, 4, 5);

	// Check to see if the search has been restricted to a particular sub-tree in
	// the folder hierarchy.
	if (isset($_GET["targetid"]) && is_numeric($_GET["targetid"]) && $_GET["targetid"]>0) {
		$targetid = $_GET["targetid"];
		$startFolder = $dms->getFolder($targetid);
	}
	else {
		$startFolder = $dms->getRootFolder();
	}
	if (!is_object($startFolder)) {
		UI::exitError(getMLText("search"),getMLText("invalid_folder_id"));
	}

	// Check to see if the search has been restricted to a particular
	/* document owner. {{{
	$owner = array();
	$ownerobjs = array();
	if (isset($_GET["owner"])) {
		$owner = $_GET['owner'];
		if (!is_array($_GET['owner'])) {
			if(!empty($_GET['owner']) && $o = $dms->getUser($_GET['owner'])) {
				$ownerobjs[] = $o;
			} else
				UI::exitError(getMLText("search"),getMLText("unknown_owner"));
		} else {
			foreach($_GET["owner"] as $l) {
				if($o = $dms->getUser($l)) {
					$ownerobjs[] = $o;
				}
			}
		}
	}  }}} */

	/* Creation date {{{ 
	$createstartdate = array();
	$createenddate = array();
	if(!empty($_GET["createstart"])) {
		$createstartts = makeTsFromDate($_GET["createstart"]);
		$createstartdate = array('year'=>(int)date('Y', $createstartts), 'month'=>(int)date('m', $createstartts), 'day'=>(int)date('d', $createstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
	}
	if ($createstartdate && !checkdate($createstartdate['month'], $createstartdate['day'], $createstartdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
	}
	if(!empty($_GET["createend"])) {
		$createendts = makeTsFromDate($_GET["createend"]);
		$createenddate = array('year'=>(int)date('Y', $createendts), 'month'=>(int)date('m', $createendts), 'day'=>(int)date('d', $createendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
	}
	if ($createenddate && !checkdate($createenddate['month'], $createenddate['day'], $createenddate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
	}
	}}} */

	/* Revision date {{{ */
	$revisionstartdate = array();
	$revisionenddate = array();
	if(!empty($_GET["revisiondatestart"])) {
		$revisionstartts = makeTsFromDate($_GET["revisiondatestart"]);
		$revisionstartdate = array('year'=>(int)date('Y', $revisionstartts), 'month'=>(int)date('m', $revisionstartts), 'day'=>(int)date('d', $revisionstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
		if (!checkdate($revisionstartdate['month'], $revisionstartdate['day'], $revisionstartdate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_revision_date_start"));
		}
	}
	if(!empty($_GET["revisiondateend"])) {
		$revisionendts = makeTsFromDate($_GET["revisiondateend"]);
		$revisionenddate = array('year'=>(int)date('Y', $revisionendts), 'month'=>(int)date('m', $revisionendts), 'day'=>(int)date('d', $revisionendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
		if (!checkdate($revisionenddate['month'], $revisionenddate['day'], $revisionenddate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_revision_date_end"));
		}
	}
	/* }}} */

	/* Status date {{{ */
	$statusstartdate = array();
	$statusenddate = array();
	if(!empty($_GET["statusdatestart"])) {
		$statusstartts = makeTsFromDate($_GET["statusdatestart"]);
		$statusstartdate = array('year'=>(int)date('Y', $statusstartts), 'month'=>(int)date('m', $statusstartts), 'day'=>(int)date('d', $statusstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
	}
	if ($statusstartdate && !checkdate($statusstartdate['month'], $statusstartdate['day'], $statusstartdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_status_date_start"));
	}
	if(!empty($_GET["statusdateend"])) {
		$statusendts = makeTsFromDate($_GET["statusdateend"]);
		$statusenddate = array('year'=>(int)date('Y', $statusendts), 'month'=>(int)date('m', $statusendts), 'day'=>(int)date('d', $statusendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
	}
	if ($statusenddate && !checkdate($statusenddate['month'], $statusenddate['day'], $statusenddate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_status_date_end"));
	}
	/* }}} */

	/* Expiration date {{{ */
	$expstartdate = array();
	$expenddate = array();
	if(!empty($_GET["expirationstart"])) {
		$expstartts = makeTsFromDate($_GET["expirationstart"]);
		$expstartdate = array('year'=>(int)date('Y', $expstartts), 'month'=>(int)date('m', $expstartts), 'day'=>(int)date('d', $expstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
		if (!checkdate($expstartdate['month'], $expstartdate['day'], $expstartdate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_expiration_date_start"));
		}
	}
	if(!empty($_GET["expirationend"])) {
		$expendts = makeTsFromDate($_GET["expirationend"]);
		$expenddate = array('year'=>(int)date('Y', $expendts), 'month'=>(int)date('m', $expendts), 'day'=>(int)date('d', $expendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
		if (!checkdate($expenddate['month'], $expenddate['day'], $expenddate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_expiration_date_end"));
		}
	}
	/* }}} */

	// status
	$status = isset($_GET['status']) ? $_GET['status'] : array();
	/*
	$status = array();
	if (isset($_GET["draft"])){
		$status[] = S_DRAFT;
	}
	if (isset($_GET["pendingReview"])){
		$status[] = S_DRAFT_REV;
	}
	if (isset($_GET["pendingApproval"])){
		$status[] = S_DRAFT_APP;
	}
	if (isset($_GET["inWorkflow"])){
		$status[] = S_IN_WORKFLOW;
	}
	if (isset($_GET["released"])){
		$status[] = S_RELEASED;
	}
	if (isset($_GET["rejected"])){
		$status[] = S_REJECTED;
	}
	if (isset($_GET["inrevision"])){
		$status[] = S_IN_REVISION;
	}
	if (isset($_GET["obsolete"])){
		$status[] = S_OBSOLETE;
	}
	if (isset($_GET["expired"])){
		$status[] = S_EXPIRED;
	}
	if (isset($_GET["needs_correction"])){
		$status[] = S_NEEDS_CORRECTION;
	}
	 */

	$reception = array();
	if (isset($_GET["reception"])){
		$reception = $_GET["reception"];
	}

	/* Do not search for folders if result shall be filtered by status.
	 * If this is not done, unexplainable results will be delivered.
	 * e.g. a search for expired documents of a given user will list
	 * also all folders of that user because the status doesn't apply
	 * to folders.
	 */
//	if($status)
//		$resultmode = 0x01;

	if (isset($_GET["attributes"]))
		$attributes = $_GET["attributes"];
	else
		$attributes = array();

	foreach($attributes as $attrdefid=>$attribute) {
		$attrdef = $dms->getAttributeDefinition($attrdefid);
		if($attribute) {
			if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) {
				if(is_array($attribute)) {
					if(!empty($attributes[$attrdefid]['from']))
						$attributes[$attrdefid]['from'] = date('Y-m-d', makeTsFromDate($attribute['from']));
					if(!empty($attributes[$attrdefid]['to']))
						$attributes[$attrdefid]['to'] = date('Y-m-d', makeTsFromDate($attribute['to']));
				} else {
					$attributes[$attrdefid] = date('Y-m-d', makeTsFromDate($attribute));
				}
			}
		}
	}

	//
	// Get the page number to display. If the result set contains more than
	// 25 entries, it is displayed across multiple pages.
	//
	// This requires that a page number variable be used to track which page the
	// user is interested in, and an extra clause on the select statement.
	//
	// Default page to display is always one.
	$pageNumber=1;
//	$limit = 15;
	if (isset($_GET["pg"])) {
		if (is_numeric($_GET["pg"]) && $_GET["pg"]>0) {
			$pageNumber = (int) $_GET["pg"];
		}
		elseif (!strcasecmp($_GET["pg"], "all")) {
			$pageNumber = "all";
		}
	}

	// ---------------- Start searching -----------------------------------------
	$startTime = getTime();
	$resArr = $dms->search(array(
		'query'=>$query,
		'limit'=>0,
		'offset'=>0 /*$limit, ($pageNumber-1)*$limit*/,
		'logicalmode'=>$mode,
		'searchin'=>$searchin,
		'startFolder'=>$startFolder,
		'owner'=>$ownerobjs,
		'status'=>$status,
		'creationstartdate'=>$created['from'], //$createstartdate ? $createstartdate : array(),
		'creationenddate'=>$created['to'], //$createenddate ? $createenddate : array(),
		'modificationstartdate'=>$modified['from'],
		'modificationenddate'=>$modified['to'],
		'categories'=>$categories,
		'attributes'=>$attributes,
		'mode'=>$resultmode,
		'expirationstartdate'=>$expstartdate ? $expstartdate : array(),
		'expirationenddate'=>$expenddate ? $expenddate : array(),
		'revisionstartdate'=>$revisionstartdate ? $revisionstartdate : array(),
		'revisionenddate'=>$revisionenddate ? $revisionenddate : array(),
		'reception'=>$reception,
		'statusstartdate'=>$statusstartdate ? $statusstartdate : array(),
		'statusenddate'=>$statusenddate ? $statusenddate : array(),
		'orderby'=>$orderby
	));
	$total = $resArr['totalDocs'] + $resArr['totalFolders'];
	$searchTime = getTime() - $startTime;
	$searchTime = round($searchTime, 2);

	$entries = array();
	$fcount = 0;
	if(!isset($_GET['action']) || $_GET['action'] != 'export') {
		if($resArr['folders']) {
			foreach ($resArr['folders'] as $entry) {
				if ($entry->getAccessMode($user) >= M_READ) {
					$entries[] = $entry;
					$fcount++;
				}
			}
		}
	}
	$dcount = 0;
	if($resArr['docs']) {
		foreach ($resArr['docs'] as $entry) {
			if ($entry->getAccessMode($user) >= M_READ) {
				if($entry->getLatestContent()) {
					$entry->verifyLastestContentExpriry();
					$entries[] = $entry;
					$dcount++;
				}
			}
		}
	}
	$totalPages = 1;
	if ((!isset($_GET['action']) || $_GET['action'] != 'export') /*&& (!isset($_GET["pg"]) || strcasecmp($_GET["pg"], "all"))*/) {
		$totalPages = (int) (count($entries)/$limit);
		if(count($entries)%$limit)
			$totalPages++;
		if($pageNumber != 'all')
			$entries = array_slice($entries, ($pageNumber-1)*$limit, $limit);
	} else
		$totalPages = 1;
	$facets = array();
// }}}
}

// -------------- Output results --------------------------------------------

if($settings->_showSingleSearchHit && count($entries) == 1) {
	$entry = $entries[0];
	if($entry->isType('document')) {
		header('Location: ../out/out.ViewDocument.php?documentid='.$entry->getID());
		exit;
	} elseif($entry->isType('folder')) {
		header('Location: ../out/out.ViewFolder.php?folderid='.$entry->getID());
		exit;
	}
} else {
	$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
	$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
	$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
	if($view) {
		$view->setParam('facets', $facets);
		$view->setParam('accessobject', $accessop);
		$view->setParam('query', $query);
		$view->setParam('includecontent', $includecontent);
		$view->setParam('marks', isset($_GET['marks']) ? $_GET['marks'] : array());
		$view->setParam('newowner', $newowner);
		$view->setParam('changecategory', $changecategory);
		$view->setParam('removecategory', $removecategory);
		$view->setParam('searchhits', $entries);
		$view->setParam('terms', $terms);
		$view->setParam('totalpages', $totalPages);
		$view->setParam('pagenumber', $pageNumber);
		$view->setParam('limit', $limit);
		$view->setParam('searchtime', $searchTime);
		$view->setParam('urlparams', $_GET);
		$view->setParam('cachedir', $settings->_cacheDir);
		$view->setParam('onepage', $settings->_onePageMode); // do most navigation by reloading areas of pages with ajax
		$view->setParam('showtree', showtree());
		$view->setParam('enableRecursiveCount', $settings->_enableRecursiveCount);
		$view->setParam('maxRecursiveCount', $settings->_maxRecursiveCount);
		$view->setParam('total', $total);
		$view->setParam('totaldocs', $dcount /*resArr['totalDocs']*/);
		$view->setParam('totalfolders', $fcount /*resArr['totalFolders']*/);
		$view->setParam('fullsearch', $fullsearch);
		$view->setParam('facetsearch', $facetsearch);
		$view->setParam('mode', isset($mode) ? $mode : '');
		$view->setParam('orderby', isset($orderby) ? $orderby : '');
		$view->setParam('defaultsearchmethod', !empty($_GET["fullsearch"]) || $settings->_defaultSearchMethod);
		$view->setParam('resultmode', isset($resultmode) ? $resultmode : '');
		$view->setParam('searchin', isset($searchin) ? $searchin : array());
		$view->setParam('startfolder', isset($startFolder) ? $startFolder : null);
		$view->setParam('owner', $owner);
		$view->setParam('createstartdate', $createstartts);
		$view->setParam('createenddate', $createendts);
		$view->setParam('created', $created);
		$view->setParam('revisionstartdate', !empty($revisionstartdate) ? getReadableDate($revisionstartts) : '');
		$view->setParam('revisionenddate', !empty($revisionenddate) ? getReadableDate($revisionendts) : '');
		$view->setParam('modifystartdate', $modifystartts);
		$view->setParam('modifyenddate', $modifyendts);
		$view->setParam('modified', $modified);
		$view->setParam('expstartdate', !empty($expstartdate) ? getReadableDate($expstartts) : '');
		$view->setParam('expenddate', !empty($expenddate) ? getReadableDate($expendts) : '');
		$view->setParam('statusstartdate', !empty($statusstartdate) ? getReadableDate($statusstartts) : '');
		$view->setParam('statusenddate', !empty($statusenddate) ? getReadableDate($statusendts) : '');
		$view->setParam('status', $status);
		$view->setParam('recordtype', isset($record_type) ? $record_type : null);
		$view->setParam('categories', isset($categories) ? $categories : '');
		$view->setParam('category', $category);
		$view->setParam('mimetype', isset($mimetype) ? $mimetype : '');
		$view->setParam('attributes', isset($attributes) ? $attributes : '');
		$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_document, SeedDMS_Core_AttributeDefinition::objtype_documentcontent, SeedDMS_Core_AttributeDefinition::objtype_folder, SeedDMS_Core_AttributeDefinition::objtype_all));
		$view->setParam('attrdefs', $attrdefs);
		$allCats = $dms->getDocumentCategories();
		$view->setParam('allcategories', $allCats);
		$allUsers = $dms->getAllUsers($settings->_sortUsersInList);
		$view->setParam('allusers', $allUsers);
		$view->setParam('workflowmode', $settings->_workflowMode);
		$view->setParam('enablefullsearch', $settings->_enableFullSearch);
		$view->setParam('previewWidthList', $settings->_previewWidthList);
		$view->setParam('convertToPdf', $settings->_convertToPdf);
		$view->setParam('previewConverters', isset($settings->_converters['preview']) ? $settings->_converters['preview'] : array());
		$view->setParam('conversionmgr', $conversionmgr);
		$view->setParam('timeout', $settings->_cmdTimeout);
		$view->setParam('xsendfile', $settings->_enableXsendfile);
		$view->setParam('reception', $reception);
		$view->setParam('showsinglesearchhit', $settings->_showSingleSearchHit);
		$view($_GET);
		exit;
	}
}
