<?php
declare(strict_types=1);
/**
 * Implementation of a document in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL2
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * The different states a document can be in
 */
/*
 * Document is in review state. A document is in review state when
 * it needs to be reviewed by a user or group.
 */
define("S_DRAFT_REV", 0);

/*
 * Document is in approval state. A document is in approval state when
 * it needs to be approved by a user or group.
 */
define("S_DRAFT_APP", 1);

/*
 * Document is released. A document is in release state either when
 * it needs no review or approval after uploaded or has been reviewed
 * and/or approved.
 */
define("S_RELEASED",  2);

/*
 * Document is in workflow. A document is in workflow if a workflow
 * has been started and has not reached a final state.
 */
define("S_IN_WORKFLOW",  3);

/*
 * Document is in a revision workflow. A revision workflow is started
 * some time after the document has been released.
 */
define("S_IN_REVISION",  4);

/*
 * Document is in draft status. Being in draft means that the document
 * is still worked on. This status is mainly for uploading documents
 * which aren't fully complete but needs to accessible for the public,
 * e.g. in order to colaborate on them.
 */
define("S_DRAFT",  5);

/*
 * Document needs correction after revision. This needs to be different from
 * the regular S_REJECTED because documents which has been rejected
 * in revision are not necessarily invalid but just needs correction.
 */
define("S_NEEDS_CORRECTION",  6);

/*
 * Document was rejected. A document is in rejected state when
 * the review failed or approval was not given.
 */
define("S_REJECTED", -1);

/*
 * Document is obsolete. A document can be obsoleted once it was
 * released.
 */
define("S_OBSOLETE", -2);

/*
 * Document is expired. A document expires when the expiration date
 * is reached
 */
define("S_EXPIRED",  -3);

/*
 * Lowest and highest status that may be set
 */
define("S_LOWEST_STATUS",  -3);
define("S_HIGHEST_STATUS",  6);

/**
 * The different states a workflow log can be in. This is used in
 * all tables tblDocumentXXXLog
 */
/*
 * workflow is in a neutral status waiting for action of user
 */
define("S_LOG_WAITING",  0);

/*
 * workflow has been successful ended. The document content has been
 * approved, reviewed, aknowledged or revised
 */
define("S_LOG_ACCEPTED",  1);

/*
 * workflow has been unsuccessful ended. The document content has been
 * rejected
 */
define("S_LOG_REJECTED",  -1);

/*
 * user has been removed from workflow. This can be for different reasons
 * 1. the user has been actively removed from the workflow, 2. the user has
 * been deleted.
 */
define("S_LOG_USER_REMOVED",  -2);

/*
 * workflow is sleeping until reactivation. The workflow has been set up
 * but not started. This is only valid for the revision workflow, which
 * may run over and over again.
 */
define("S_LOG_SLEEPING",  -3);

/**
 * Class to represent a document in the document management system
 *
 * A document in SeedDMS is similar to a file in a regular file system.
 * Documents may have any number of content elements
 * ({@link SeedDMS_Core_DocumentContent}). These content elements are often
 * called versions ordered in a timely manner. The most recent content element
 * is the current version.
 *
 * Documents can be linked to other documents and can have attached files.
 * The document content can be anything that can be stored in a regular
 * file.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010-2022 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Document extends SeedDMS_Core_Object { /* {{{ */
	/**
	 * @var string name of document
	 */
	protected $_name;

	/**
	 * @var string comment of document
	 */
	protected $_comment;

	/**
	 * @var integer unix timestamp of creation date
	 */
	protected $_date;

	/**
	 * @var integer id of user who is the owner
	 */
	protected $_ownerID;

	/**
	 * @var integer id of folder this document belongs to
	 */
	protected $_folderID;

	/**
	 * @var integer timestamp of expiration date
	 */
	protected $_expires;

	/**
	 * @var boolean true if access is inherited, otherwise false
	 */
	protected $_inheritAccess;

	/**
	 * @var integer default access if access rights are not inherited
	 */
	protected $_defaultAccess;

	/**
	 * @var array list of notifications for users and groups
	 */
	protected $_readAccessList;

	/**
	 * @var array list of notifications for users and groups
	 */
	public $_notifyList;

	/**
	 * @var boolean true if document is locked, otherwise false
	 */
	protected $_locked;

	/**
	 * @var string list of keywords
	 */
	protected $_keywords;

	/**
	 * @var SeedDMS_Core_DocumentCategory[] list of categories
	 */
	protected $_categories;

	/**
	 * @var integer position of document within the parent folder
	 */
	protected $_sequence;

	/**
	 * @var SeedDMS_Core_DocumentContent temp. storage for latestcontent
	 */
	protected $_latestContent;

	/**
	 * @var array temp. storage for content
	 */
	protected $_content;

	/**
	 * @var SeedDMS_Core_Folder
	 */
	protected $_folder;

	/** @var array of SeedDMS_Core_UserAccess and SeedDMS_Core_GroupAccess */
	protected $_accessList;

	function __construct($id, $name, $comment, $date, $expires, $ownerID, $folderID, $inheritAccess, $defaultAccess, $locked, $keywords, $sequence) { /* {{{ */
		parent::__construct($id);
		$this->_name = $name;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_expires = $expires;
		$this->_ownerID = $ownerID;
		$this->_folderID = $folderID;
		$this->_inheritAccess = $inheritAccess ? true : false;
		$this->_defaultAccess = $defaultAccess;
		$this->_locked = ($locked == null || $locked == '' ? -1 : $locked);
		$this->_keywords = $keywords;
		$this->_sequence = $sequence;
		$this->_categories = array();
		$this->_notifyList = array();
		$this->_latestContent = null;
		$this->_content = null;
		/* Cache */
		$this->clearCache();
	} /* }}} */

	/**
	 * Clear cache of this instance.
	 *
	 * The result of some expensive database actions (e.g. get all subfolders
	 * or documents) will be saved in a class variable to speed up consecutive
	 * calls of the same method. If a second call of the same method shall not
	 * use the cache, then it must be cleared.
	 *
	 */
	public function clearCache() { /* {{{ */
		$this->_parent = null;
		$this->_owner = null;
		$this->_documentLinks = null;
		$this->_documentFiles = null;
		$this->_content = null;
		$this->_accessList = null;
		$this->_notifyList = null;
	} /* }}} */

	/**
	 * Check if this object is of type 'document'.
	 *
	 * @param string $type type of object
	 */
	public function isType($type) { /* {{{ */
		return $type == 'document';
	} /* }}} */

	/**
	 * Return an array of database fields which are used for searching
	 * a term entered in the database search form
	 *
	 * @param SeedDMS_Core_DMS $dms
	 * @param array $searchin integer list of search scopes (2=name, 3=comment,
	 * 4=attributes)
	 * @return array list of database fields
	 */
	public static function getSearchFields($dms, $searchin) { /* {{{ */
		$db = $dms->getDB();

		$searchFields = array();
		if (in_array(1, $searchin)) {
			$searchFields[] = "`tblDocuments`.`keywords`";
		}
		if (in_array(2, $searchin)) {
			$searchFields[] = "`tblDocuments`.`name`";
		}
		if (in_array(3, $searchin)) {
			$searchFields[] = "`tblDocuments`.`comment`";
			$searchFields[] = "`tblDocumentContent`.`comment`";
		}
		if (in_array(4, $searchin)) {
			$searchFields[] = "`tblDocumentAttributes`.`value`";
			$searchFields[] = "`tblDocumentContentAttributes`.`value`";
		}
		if (in_array(5, $searchin)) {
			$searchFields[] = $db->castToText("`tblDocuments`.`id`");
		}

		return $searchFields;
	} /* }}} */

	/**
	 * Return a folder by its database record
	 *
	 * @param array $resArr array of folder data as returned by database
	 * @param SeedDMS_Core_DMS $dms
	 * @return SeedDMS_Core_Folder|bool instance of SeedDMS_Core_Folder if document exists
	 */
	public static function getInstanceByData($resArr, $dms) { /* {{{ */
		$classname = $dms->getClassname('document');
		/** @var SeedDMS_Core_Document $document */
		$document = new $classname($resArr["id"], $resArr["name"], $resArr["comment"], $resArr["date"], $resArr["expires"], $resArr["owner"], $resArr["folder"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr['lock'], $resArr["keywords"], $resArr["sequence"]);
		$document->setDMS($dms);
		$document = $document->applyDecorators();
		return $document;
	} /* }}} */

	/**
	 * Return an document by its id
	 *
	 * @param integer $id id of document
	 * @param SeedDMS_Core_DMS $dms
	 * @return bool|SeedDMS_Core_Document instance of SeedDMS_Core_Document if document exists, null
	 * if document does not exist, false in case of error
	 */
	public static function getInstance($id, $dms) { /* {{{ */
		$db = $dms->getDB();

//		$queryStr = "SELECT * FROM `tblDocuments` WHERE `id` = " . (int) $id;
		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lock` FROM `tblDocuments` LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id` = `tblDocumentLocks`.`document` WHERE `id` = " . (int) $id;
		if($dms->checkWithinRootDir)
			$queryStr .= " AND `folderList` LIKE '%:".$dms->rootFolderID.":%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return null;
		$resArr = $resArr[0];

		$resArr['lock'] = !$resArr['lock'] ? -1 : $resArr['lock'];

		return self::getInstanceByData($resArr, $dms);
	} /* }}} */

	/**
	 * Apply decorators
	 *
	 * @return object final object after all decorators has been applied
	 */
	function applyDecorators() { /* {{{ */
		if($decorators = $this->_dms->getDecorators('document')) {
			$s = $this;
			foreach($decorators as $decorator) {
				$s = new $decorator($s);
			}
			return $s;
		} else {
			return $this;
		}
	} /* }}} */

	/**
	 * Return the directory of the document in the file system relativ
	 * to the contentDir
	 *
	 * @return string directory of document
	 */
	function getDir() { /* {{{ */
		if($this->_dms->maxDirID) {
			$dirid = (int) (($this->_id-1) / $this->_dms->maxDirID) + 1;
			return $dirid."/".$this->_id."/";
		} else {
			return $this->_id."/";
		}
	} /* }}} */

	/**
	 * Return the name of the document
	 *
	 * @return string name of document
	 */
	function getName() { return $this->_name; }

	/**
	 * Set the name of the document
	 *
	 * @param $newName string new name of document
	 * @return bool
	 */
	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreSetName' callback is set */
		if(isset($this->_dms->callbacks['onPreSetName'])) {
			foreach($this->_dms->callbacks['onPreSetName'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newName);
				if(is_bool($ret))
					return $ret;
			}
		}

		$queryStr = "UPDATE `tblDocuments` SET `name` = ".$db->qstr($newName)." WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$oldName = $this->_name;
		$this->_name = $newName;

		/* Check if 'onPostSetName' callback is set */
		if(isset($this->_dms->callbacks['onPostSetName'])) {
			foreach($this->_dms->callbacks['onPostSetName'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $oldName);
				if(is_bool($ret))
					return $ret;
			}
		}

		return true;
	} /* }}} */

	/**
	 * Return the comment of the document
	 *
	 * @return string comment of document
	 */
	function getComment() { return $this->_comment; }

	/**
	 * Set the comment of the document
	 *
	 * @param $newComment string new comment of document
	 * @return bool
	 */
	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreSetComment' callback is set */
		if(isset($this->_dms->callbacks['onPreSetComment'])) {
			foreach($this->_dms->callbacks['onPreSetComment'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newComment);
				if(is_bool($ret))
					return $ret;
			}
		}

		$queryStr = "UPDATE `tblDocuments` SET `comment` = ".$db->qstr($newComment)." WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$oldComment = $this->_comment;
		$this->_comment = $newComment;

		/* Check if 'onPostSetComment' callback is set */
		if(isset($this->_dms->callbacks['onPostSetComment'])) {
			foreach($this->_dms->callbacks['onPostSetComment'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $oldComment);
				if(is_bool($ret))
					return $ret;
			}
		}

		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getKeywords() { return $this->_keywords; }

	/**
	 * @param string $newKeywords
	 * @return bool
	 */
	function setKeywords($newKeywords) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreSetKeywords' callback is set */
		if(isset($this->_dms->callbacks['onPreSetKeywords'])) {
			foreach($this->_dms->callbacks['onPreSetKeywords'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newKeywords);
				if(is_bool($ret))
					return $ret;
			}
		}

		$queryStr = "UPDATE `tblDocuments` SET `keywords` = ".$db->qstr($newKeywords)." WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$oldKeywords = $this->_keywords;
		$this->_keywords = $newKeywords;

		/* Check if 'onPostSetKeywords' callback is set */
		if(isset($this->_dms->callbacks['onPostSetKeywords'])) {
			foreach($this->_dms->callbacks['onPostSetKeywords'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $oldKeywords);
				if(is_bool($ret))
					return $ret;
			}
		}

		return true;
	} /* }}} */

	/**
	 * Check if document has a given category
	 *
	 * @param SeedDMS_Core_DocumentCategory $cat
	 * @return bool true if document has category, otherwise false
	 */
	function hasCategory($cat) { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$cat)
			return false;

		$queryStr = "SELECT * FROM `tblDocumentCategory` WHERE `documentID` = ".$this->_id." AND `categoryID`=".$cat->getId();
		$resArr = $db->getResultArray($queryStr);
		if (!$resArr)
			return false;

		return true;
	} /* }}} */

	/**
	 * Retrieve a list of all categories this document belongs to
	 *
	 * @return bool|SeedDMS_Core_DocumentCategory[]
	 */
	function getCategories() { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$this->_categories) {
			$queryStr = "SELECT * FROM `tblCategory` WHERE `id` IN (SELECT `categoryID` FROM `tblDocumentCategory` WHERE `documentID` = ".$this->_id.")";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_categories = [];
			foreach ($resArr as $row) {
				$cat = new SeedDMS_Core_DocumentCategory($row['id'], $row['name']);
				$cat->setDMS($this->_dms);
				$this->_categories[] = $cat;
			}
		}
		return $this->_categories;
	} /* }}} */

	/**
	 * Set a list of categories for the document
	 * This function will delete currently assigned categories and sets new
	 * categories.
	 *
	 * @param SeedDMS_Core_DocumentCategory[] $newCategories list of category objects
	 * @return bool
	 */
	function setCategories($newCategories) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreSetCategories' callback is set */
		if(isset($this->_dms->callbacks['onPreSetCategories'])) {
			foreach($this->_dms->callbacks['onPreSetCategories'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newCategories);
				if(is_bool($ret))
					return $ret;
			}
		}

		$db->startTransaction();
		$queryStr = "DELETE FROM `tblDocumentCategory` WHERE `documentID` = ". $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		foreach($newCategories as $cat) {
			$queryStr = "INSERT INTO `tblDocumentCategory` (`categoryID`, `documentID`) VALUES (". $cat->getId() .", ". $this->_id .")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();

		$oldCategories = $this->_categories;
		$this->_categories = $newCategories;

		/* Check if 'onPostSetCategories' callback is set */
		if(isset($this->_dms->callbacks['onPostSetCategories'])) {
			foreach($this->_dms->callbacks['onPostSetCategories'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $oldCategories);
				if(is_bool($ret))
					return $ret;
			}
		}

		return true;
	} /* }}} */

	/**
	 * Add a list of categories to the document
	 * This function will add a list of new categories to the document.
	 *
	 * @param array $newCategories list of category objects
	 */
	function addCategories($newCategories) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreAddCategories' callback is set */
		if(isset($this->_dms->callbacks['onPreAddCategories'])) {
			foreach($this->_dms->callbacks['onPreAddCategories'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newCategories);
				if(is_bool($ret))
					return $ret;
			}
		}

		if(!$this->_categories)
			$this->getCategories();

		$catids = array();
		foreach($this->_categories as $cat)
			$catids[] = $cat->getID();

		$db->startTransaction();
		$ncat = array(); // Array containing actually added new categories
		foreach($newCategories as $cat) {
			if(!in_array($cat->getID(), $catids)) {
				$queryStr = "INSERT INTO `tblDocumentCategory` (`categoryID`, `documentID`) VALUES (". $cat->getId() .", ". $this->_id .")";
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
				$ncat[] = $cat;
			}
		}
		$db->commitTransaction();

		$oldCategories = $this->_categories;
		$this->_categories = array_merge($this->_categories, $ncat);

		/* Check if 'onPostAddCategories' callback is set */
		if(isset($this->_dms->callbacks['onPostAddCategories'])) {
			foreach($this->_dms->callbacks['onPostAddCategories'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $oldCategories);
				if(is_bool($ret))
					return $ret;
			}
		}

		return true;
	} /* }}} */

	/**
	 * Remove a list of categories from the document
	 * This function will remove a list of assigned categories to the document.
	 *
	 * @param array $newCategories list of category objects
	 */
	function removeCategories($categories) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreRemoveCategories' callback is set */
		if(isset($this->_dms->callbacks['onPreRemoveCategories'])) {
			foreach($this->_dms->callbacks['onPreRemoveCategories'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $categories);
				if(is_bool($ret))
					return $ret;
			}
		}

		$catids = array();
		foreach($categories as $cat)
			$catids[] = $cat->getID();

		$queryStr = "DELETE FROM `tblDocumentCategory` WHERE `documentID` = ". $this->_id ." AND `categoryID` IN (".implode(',', $catids).")";
		if (!$db->getResult($queryStr)) {
			return false;
		}

		$oldCategories = $this->_categories;
		$this->_categories = null;

		/* Check if 'onPostRemoveCategories' callback is set */
		if(isset($this->_dms->callbacks['onPostRemoveCategories'])) {
			foreach($this->_dms->callbacks['onPostRemoveCategories'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $oldCategories);
				if(is_bool($ret))
					return $ret;
			}
		}

		return true;
	} /* }}} */

	/**
	 * Return creation date of the document
	 *
	 * @return integer unix timestamp of creation date
	 */
	function getDate() { /* {{{ */
		return $this->_date;
	} /* }}} */

	/**
	 * Set creation date of the document
	 *
	 * @param integer $date timestamp of creation date. If false then set it
	 * to the current timestamp
	 * @return boolean true on success
	 */
	function setDate($date) { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$date)
			$date = time();
		else {
			if(!is_numeric($date))
				return false;
		}

		$queryStr = "UPDATE `tblDocuments` SET `date` = " . (int) $date . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$this->_date = $date;
		return true;
	} /* }}} */

	/**
	 * Check, if this document is a child of a given folder
	 *
	 * @param object $folder parent folder
	 * @return boolean true if document is a direct child of the given folder
	 */
	function isDescendant($folder) { /* {{{ */
		/* First check if the parent folder is folder looking for */
		if ($this->getFolder()->getID() == $folder->getID())
			return true;
		/* Second, check for the parent folder of this document to be
		 * below the given folder
		 */
		if($this->getFolder()->isDescendant($folder))
			return true;
		return false;
	} /* }}} */

	/**
	 * Return the parent folder of the document
	 *
	 * @return SeedDMS_Core_Folder parent folder
	 */
	function getParent() { /* {{{ */
		return $this->getFolder();
	} /* }}} */

	function getFolder() { /* {{{ */
		if (!isset($this->_folder))
			$this->_folder = $this->_dms->getFolder($this->_folderID);
		return $this->_folder;
	} /* }}} */

	/**
	 * Set folder of a document
	 *
	 * This function basically moves a document from a folder to another
	 * folder.
	 *
	 * @param SeedDMS_Core_Folder $newFolder
	 * @return boolean false in case of an error, otherwise true
	 */
	function setParent($newFolder) { /* {{{ */
		return $this->setFolder($newFolder);
	} /* }}} */

	/**
	 * Set folder of a document
	 *
	 * This function basically moves a document from a folder to another
	 * folder.
	 *
	 * @param SeedDMS_Core_Folder $newFolder
	 * @return boolean false in case of an error, otherwise true
	 */
	function setFolder($newFolder) { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$newFolder)
			return false;

		if(!$newFolder->isType('folder'))
			return false;

		/* Check if 'onPreSetFolder' callback is set */
		if(isset($this->_dms->callbacks['onPreSetFolder'])) {
			foreach($this->_dms->callbacks['onPreSetFolder'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newFolder);
				if(is_bool($ret))
					return $ret;
			}
		}

		$db->startTransaction();

		$queryStr = "UPDATE `tblDocuments` SET `folder` = " . $newFolder->getID() . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Make sure that the folder search path is also updated.
		$path = $newFolder->getPath();
		$flist = "";
		/** @var SeedDMS_Core_Folder[] $path */
		foreach ($path as $f) {
			$flist .= ":".$f->getID();
		}
		if (strlen($flist)>1) {
			$flist .= ":";
		}
		$queryStr = "UPDATE `tblDocuments` SET `folderList` = '" . $flist . "' WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		$oldFolder = $this->_folder;
		$this->_folderID = $newFolder->getID();
		$this->_folder = $newFolder;

		/* Check if 'onPostSetFolder' callback is set */
		if(isset($this->_dms->callbacks['onPostSetFolder'])) {
			foreach($this->_dms->callbacks['onPostSetFolder'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $oldFolder);
				if(is_bool($ret))
					return $ret;
			}
		}

		return true;
	} /* }}} */

	/**
	 * Return owner of document
	 *
	 * @return SeedDMS_Core_User owner of document as an instance of {@link SeedDMS_Core_User}
	 */
	function getOwner() { /* {{{ */
		if (!isset($this->_owner))
			$this->_owner = $this->_dms->getUser($this->_ownerID);
		return $this->_owner;
	} /* }}} */

	/**
	 * Set owner of a document
	 *
	 * @param SeedDMS_Core_User $newOwner new owner
	 * @return boolean true if successful otherwise false
	 */
	function setOwner($newOwner) { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$newOwner)
			return false;

		if(!$newOwner->isType('user'))
			return false;

		$oldOwner = self::getOwner();

		$db->startTransaction();

		/* Check if 'onPreSetOwner' callback is set */
		if(isset($this->_dms->callbacks['onPreSetOwner'])) {
			foreach($this->_dms->callbacks['onPreSetOwner'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newOwner);
				if(is_bool($ret))
					return $ret;
			}
		}

		$queryStr = "UPDATE `tblDocuments` set `owner` = " . $newOwner->getID() . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* FIXME: Update also all locks and checkouts done by the previous owner */
		/*
		$queryStr = "UPDATE `tblDocumentLocks` set `userID` = " . $newOwner->getID() . " WHERE `document` = " . $this->_id . " AND `userID` = " . $oldOwner->getID();
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "UPDATE `tblDocumentCheckOuts` set `userID` = " . $newOwner->getID() . " WHERE `document` = " . $this->_id . " AND `userID` = " . $oldOwner->getID();
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		 */

		$db->commitTransaction();

		$this->_ownerID = $newOwner->getID();
		$this->_owner = $newOwner;

		/* Check if 'onPostSetOwner' callback is set */
		if(isset($this->_dms->callbacks['onPostSetOwner'])) {
			foreach($this->_dms->callbacks['onPostSetOwner'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $oldOwner);
				if(is_bool($ret))
					return $ret;
			}
		}

		return true;
	} /* }}} */

	/**
	 * @return bool|int
	 */
	function getDefaultAccess() { /* {{{ */
		if ($this->inheritsAccess()) {
			$res = $this->getFolder();
			if (!$res) return false;
			return $this->_folder->getDefaultAccess();
		}
		return $this->_defaultAccess;
	} /* }}} */

	/**
	 * Set default access mode
	 *
	 * This method sets the default access mode and also removes all notifiers which
	 * will not have read access anymore. Setting a default access mode will only
	 * have an immediate effect if the access rights are not inherited, otherwise
	 * it just updates the database record of the document and once the
	 * inheritance is turn off the default access mode will take effect.
	 *
	 * @param integer     $mode    access mode
	 * @param bool|string $noclean set to true if notifier list shall not be clean up
	 *
	 * @return bool
	 */
	function setDefaultAccess($mode, $noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		if($mode < M_LOWEST_RIGHT || $mode > M_HIGHEST_RIGHT)
			return false;

		$queryStr = "UPDATE `tblDocuments` set `defaultAccess` = " . (int) $mode . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_defaultAccess = $mode;

		/* Setting the default access mode does not have any effect if access
		 * is still inherited. In that case there is no need to clean the
		 * notification list.
		 */
		if(!$noclean && !$this->_inheritAccess)
			$this->cleanNotifyList();

		return true;
	} /* }}} */

	/**
	 * @return bool
	 */
	function inheritsAccess() { return $this->_inheritAccess; }

	/**
	 * This is supposed to be a replacement for inheritsAccess()
	 *
	 * @return bool
	 */
	function getInheritAccess() { return $this->_inheritAccess; }

	/**
	 * Set inherited access mode
	 * Setting inherited access mode will set or unset the internal flag which
	 * controls if the access mode is inherited from the parent folder or not.
	 * It will not modify the
	 * access control list for the current object. It will remove all
	 * notifications of users which do not even have read access anymore
	 * after setting or unsetting inherited access.
	 *
	 * @param boolean $inheritAccess set to true for setting and false for
	 *        unsetting inherited access mode
	 * @param boolean $noclean set to true if notifier list shall not be clean up
	 * @return boolean true if operation was successful otherwise false
	 */
	function setInheritAccess($inheritAccess, $noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblDocuments` SET `inheritAccess` = " . ($inheritAccess ? "1" : "0") . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_inheritAccess = ($inheritAccess ? true : false);

		if(!$noclean)
			$this->cleanNotifyList();

		return true;
	} /* }}} */

	/**
	 * Check if document expires
	 *
	 * @return boolean true if document has expiration date set, otherwise false
	 */
	function expires() { /* {{{ */
		if (intval($this->_expires) == 0)
			return false;
		else
			return true;
	} /* }}} */

	/**
	 * Get expiration time of document
	 *
	 * @return integer/boolean expiration date as unix timestamp or false
	 */
	function getExpires() { /* {{{ */
		if (intval($this->_expires) == 0)
			return false;
		else
			return $this->_expires;
	} /* }}} */

	/**
	 * Set expiration date as unix timestamp
	 *
	 * @param integer $expires unix timestamp of expiration date
	 * @return bool
	 */
	function setExpires($expires) { /* {{{ */
		$db = $this->_dms->getDB();

		$expires = (!$expires) ? 0 : $expires;

		if ($expires == $this->_expires) {
			// No change is necessary.
			return true;
		}

		$queryStr = "UPDATE `tblDocuments` SET `expires` = " . (int) $expires . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_expires = $expires;
		return true;
	} /* }}} */

	/**
	 * Check if the document has expired
	 *
	 * The method expects to database field 'expired' to hold the timestamp
	 * of the start of day at which end the document expires. The document will
	 * expire if that day is over. Hence, a document will *not* 
	 * be expired during the day of expiration but at the end of that day
	 *
	 * @return boolean true if document has expired otherwise false
	 */
	function hasExpired() { /* {{{ */
		if (intval($this->_expires) == 0) return false;
		if (time()>=$this->_expires+24*60*60) return true;
		return false;
	} /* }}} */

	/**
	 * Check if the document has expired and set the status accordingly
	 * It will also recalculate the status if the current status is
	 * set to S_EXPIRED but the document isn't actually expired.
	 * The method will update the document status log database table
	 * if needed.
	 * FIXME: some left over reviewers/approvers are in the way if
	 * no workflow is set and traditional workflow mode is on. In that
	 * case the status is set to S_DRAFT_REV or S_DRAFT_APP
	 *
	 * @return boolean true if status has changed
	 */
	function verifyLastestContentExpriry(){ /* {{{ */
		$lc=$this->getLatestContent();
		if($lc) {
			$st=$lc->getStatus();

			if (($st["status"]==S_DRAFT_REV || $st["status"]==S_DRAFT_APP || $st["status"]==S_IN_WORKFLOW || $st["status"]==S_RELEASED || $st["status"]==S_IN_REVISION) && $this->hasExpired()){
				return $lc->setStatus(S_EXPIRED,"", $this->getOwner());
			}
			elseif ($st["status"]==S_EXPIRED && !$this->hasExpired() ){
				$lc->verifyStatus(true, $this->getOwner());
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if latest content of the document has a scheduled
	 * revision workflow.
	 *
	 * This method was moved into SeedDMS_Core_DocumentContent and
	 * the original method in SeedDMS_Core_Document now uses it for
	 * the latest version.
	 *
	 * @param object $user user requesting the possible automatic change
	 * @param string $next next date for review
	 * @return boolean true if status has changed
	 */
	function checkForDueRevisionWorkflow($user, $next=''){ /* {{{ */
		$lc=$this->getLatestContent();
		if($lc) {
			return $lc->checkForDueRevisionWorkflow($user, $next);
		}
		return false;
	} /* }}} */

	/**
	 * Check if document is locked
	 *
	 * @return boolean true if locked otherwise false
	 */
	function isLocked() { return $this->_locked != -1; }

	/**
	 * Lock or unlock document
	 *
	 * @param SeedDMS_Core_User|bool $falseOrUser user object for locking or false for unlocking
	 * @return boolean true if operation was successful otherwise false
	 */
	function setLocked($falseOrUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$lockUserID = -1;
		if (is_bool($falseOrUser) && !$falseOrUser) {
			$queryStr = "DELETE FROM `tblDocumentLocks` WHERE `document` = ".$this->_id;
		}
		else if (is_object($falseOrUser)) {
			$queryStr = "INSERT INTO `tblDocumentLocks` (`document`, `userID`) VALUES (".$this->_id.", ".$falseOrUser->getID().")";
			$lockUserID = $falseOrUser->getID();
		}
		else {
			return false;
		}
		if (!$db->getResult($queryStr)) {
			return false;
		}
		unset($this->_lockingUser);
		$this->_locked = $lockUserID;
		return true;
	} /* }}} */

	/**
	 * Get the user currently locking the document
	 *
	 * @return SeedDMS_Core_User|bool user have a lock
	 */
	function getLockingUser() { /* {{{ */
		if (!$this->isLocked())
			return false;

		if (!isset($this->_lockingUser))
			$this->_lockingUser = $this->_dms->getUser($this->_locked);
		return $this->_lockingUser;
	} /* }}} */

	/**
	 * Check if document is checked out
	 *
	 * @return boolean true if checked out otherwise false
	 */
	function isCheckedOut() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblDocumentCheckOuts` WHERE `document` = " . (int) $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && $resArr==false) || (count($resArr)==0)) {
			// Could not find a check out for the selected document.
			return false;
		} else {
			// A check out has been identified for this document.
			return true;
		}
	} /* }}} */

	/**
	 * Get checkout info for document
	 *
	 * This returns the checkouts for a document. There could be several checkouts
	 * for one document, but usually there is just one.
	 *
	 * @return array/boolean records from table tblDocumentCheckOuts or false
	 * in case of an error.
	 */
	function getCheckOutInfo() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblDocumentCheckOuts` WHERE `document` = " . (int) $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && $resArr==false) || (count($resArr)==0)) {
			// Could not find a check out for the selected document.
			return false;
		} else {
			// A check out has been identified for this document.
			return $resArr;
		}
	} /* }}} */


	/**
	 * Check out document
	 *
	 * Creates a check out record for the document and copies the latest
	 * version of the document into the given checkout dir.
	 *
	 * @param object $user object of user doing the checkout
	 * @param string $checkoutdir directory where the file will be placed
	 * @return object object of class SeedDMS_Core_DocumentCheckOut
	 */
	function checkOut($user, $checkoutdir) { /* {{{ */
		$db = $this->_dms->getDB();

		if(self::isCheckedOut())
			return false;

		/* Check if checkout dir is writable */
		if(!file_exists($checkoutdir)) {
			return false;
		}

		$db->startTransaction();

		$lc = self::getLatestContent();

		$ext = pathinfo($this->getName(), PATHINFO_EXTENSION);
		$oext = pathinfo($lc->getOriginalFileName(), PATHINFO_EXTENSION);
		if($ext == $oext)
			$filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $this->getName());
		else {
			$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $this->getName()).'.'.$oext;
		}
		$filename = $checkoutdir.$this->getID().'-'.$lc->getVersion().'-'.$filename; //$lc->getOriginalFileName();
		$queryStr = "INSERT INTO `tblDocumentCheckOuts` (`document`, `version`, `userID`, `date`, `filename`) VALUES (".$this->_id.", ".$lc->getVersion().", ".$user->getID().", ".$db->getCurrentDatetime().", ".$db->qstr($filename).")";
		if (!$db->getResult($queryStr))
			return false;

		/* Try to copy the file */
		$err = SeedDMS_Core_File::copyFile($this->_dms->contentDir . $this->getDir() . $lc->getFileName(), $filename);
		if (!$err) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Check in document
	 *
	 * Τhis function is similar to SeedDMS_Core_Document::addContent()
	 * but reads the content from the file which was previously checked out.
	 * Internal this method calls
	 * SeedDMS_Core_Document::addContent() but takes over the original
	 * filename, filetype and mimetype from the checked out version.
	 * No matter in which state the current checked out file is, the
	 * document will be checked back in afterwards.
	 *
	 * There are various reason why a check in may fail. In those cases
	 * this method will return false, but if the checked out document has
	 * disappeared, the checkout will be ended and the method returns true
	 * without creating a new version.
	 *
	 * The check in may not be done by the user who has done the check out,
	 * but if it is a different user, this user must have unlimited access
	 * on the document.
	 *
	 * @param string $comment
	 * @param object $user
	 * @param array $reviewers
	 * @param array $approvers
	 * @param integer $version
	 * @param array $attributes
	 * @param object $workflow
	 * @param integer $initstate intial document status
	 * @return boolean|object false in case of error, true if no error occurs but
	 * the document remains unchanged (because the checked out file has not
	 * changed or it has disappeared and couldnt't be checked in), or
	 * an instance of class SeedDMS_Core_AddContentResultSet if the document
	 * was updated.
	 */
	function checkIn($comment, $user, $reviewers=array(), $approvers=array(), $version=0, $attributes=array(), $workflow=null, $initstate=S_RELEASED) { /* {{{ */
		$db = $this->_dms->getDB();

		$infos = self::getCheckOutInfo();
		if(!$infos)
			return false;
		$info = $infos[0];
		$lc = self::getLatestContent();

		/* If file doesn't exist anymore, then just remove the record from the db */
		if(!file_exists($info['filename'])) {
			$queryStr = "DELETE FROM `tblDocumentCheckOuts` WHERE `document` = ".$this->_id;
			$db->getResult($queryStr);
			return true;
		}

		/* Check if version of checked out file is equal to current version */
		if($lc->getVersion() != $info['version']) {
			return false;
		}

		/* Check if the user doing the check in is the same use as the one
		 * have done the check out or at least have unlimited rights on the
		 * document.
		 */
		if($user->getID() != $info['userID'] && $this->getAccessMode($user) < M_ALL) {
			return false;
		}

		$content = true;
		/* Do not create a new version if the file was unchanged */
		$checksum = SeedDMS_Core_File::checksum($info['filename']);
		if($checksum != $lc->getChecksum()) {
			$content = $this->addContent($comment, $user, $info['filename'], $lc->getOriginalFileName(), $lc->getFileType(), $lc->getMimeType(), $reviewers, $approvers, $version, $attributes, $workflow, $initstate);
			if($content) {
				if(!$this->_dms->forceRename) {
					SeedDMS_Core_File::removeFile($info['filename']);
				}
				$queryStr = "DELETE FROM `tblDocumentCheckOuts` WHERE `document` = ".$this->_id;
				$db->getResult($queryStr);
				return $content;
			} else {
				return false;
			}
		} else {
			SeedDMS_Core_File::removeFile($info['filename']);
			$queryStr = "DELETE FROM `tblDocumentCheckOuts` WHERE `document` = ".$this->_id;
			$db->getResult($queryStr);
			return true;
		}
	} /* }}} */

	/**
	 * Cancel check out of document
	 *
	 * This function will cancel a check out in progress by removing
	 * the check out record from the database and removing the file
	 * from the check out folder.
	 *
	 * @return boolean true if cancelation was successful
	 */
	function cancelCheckOut() { /* {{{ */
		$db = $this->_dms->getDB();

		$infos = self::getCheckOutInfo();
		if($infos) {
			$info = $infos[0];

			$db->startTransaction();
			$queryStr = "DELETE FROM `tblDocumentCheckOuts` WHERE `document` = ".$this->_id;
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			if(file_exists($info['filename']) && !SeedDMS_Core_File::removeFile($info['filename'])) {
				$db->rollbackTransaction();
				return false;
			}
			$db->commitTransaction();
		}

		return true;

	} /* }}} */

	/**
	 * Return the check out status of the document
	 *
	 * This method returns the checkout status of a previosly checked out
	 * document.
	 *
	 * @return int 1=The checked out file doesn't exists anymore,
	 * 2=The checked out version doesn't exists anymore
	 * 3=The checked out file has not been modified yet
	 * 4=new check out record in database found
	 * 0=The checked out file is modified and check in will create a new version
	 */
	function checkOutStatus() { /* {{{ */
		$infos = self::getCheckOutInfo();
		if(!$infos)
			return 4;

		$info = $infos[0];
		$lc = self::getLatestContent();

		/* If file doesn't exist anymore, then just remove the record from the db */
		if(!file_exists($info['filename'])) {
			return 1;
		}

		/* Check if version of checked out file is equal to current version */
		if($lc->getVersion() != $info['version']) {
			return 2;
		}

		$checksum = SeedDMS_Core_File::checksum($info['filename']);
		if($checksum == $lc->getChecksum()) {
			return 3;
		}

		return 0;
	} /* }}} */

	/**
	 * @return float
	 */
	function getSequence() { return $this->_sequence; }

	/**
	 * @param float $seq
	 * @return bool
	 */
	function setSequence($seq) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblDocuments` SET `sequence` = " . $seq . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_sequence = $seq;
		return true;
	} /* }}} */

	/**
	 * Delete all entries for this document from the access control list
	 *
	 * @param boolean $noclean set to true if notifier list shall not be clean up
	 * @return boolean true if operation was successful otherwise false
	 */
	function clearAccessList($noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM `tblACLs` WHERE `targetType` = " . T_DOCUMENT . " AND `target` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		if(!$noclean)
			$this->cleanNotifyList();

		return true;
	} /* }}} */

	/**
	 * Returns a list of access privileges
	 *
	 * If the document inherits the access privileges from the parent folder
	 * those will be returned.
	 * $mode and $op can be set to restrict the list of returned access
	 * privileges. If $mode is set to M_ANY no restriction will apply
	 * regardless of the value of $op. The returned array contains a list
	 * of {@link SeedDMS_Core_UserAccess} and
	 * {@link SeedDMS_Core_GroupAccess} objects. Even if the document
	 * has no access list the returned array contains the two elements
	 * 'users' and 'groups' which are than empty. The methode returns false
	 * if the function fails.
	 *
	 * @param int $mode access mode (defaults to M_ANY)
	 * @param int|string $op operation (defaults to O_EQ)
	 * @return bool|array
	 */
	function getAccessList($mode = M_ANY, $op = O_EQ) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->inheritsAccess()) {
			$res = $this->getFolder();
			if (!$res) return false;
			$pacl = $res->getAccessList($mode, $op);
			return $pacl;
		} else {
			$pacl = array("groups" => array(), "users" => array());
		}

		if (!isset($this->_accessList[$mode])) {
			if ($op!=O_GTEQ && $op!=O_LTEQ && $op!=O_EQ) {
				return false;
			}
			$modeStr = "";
			if ($mode!=M_ANY) {
				$modeStr = " AND `mode`".$op.(int)$mode;
			}
			$queryStr = "SELECT * FROM `tblACLs` WHERE `targetType` = ".T_DOCUMENT.
				" AND `target` = " . $this->_id . $modeStr . " ORDER BY `targetType`";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_accessList[$mode] = array("groups" => array(), "users" => array());
			foreach ($resArr as $row) {
				if ($row["userID"] != -1)
					array_push($this->_accessList[$mode]["users"], new SeedDMS_Core_UserAccess($this->_dms->getUser($row["userID"]), (int) $row["mode"]));
				else //if ($row["groupID"] != -1)
					array_push($this->_accessList[$mode]["groups"], new SeedDMS_Core_GroupAccess($this->_dms->getGroup($row["groupID"]), (int) $row["mode"]));
			}
		}

		return $this->_accessList[$mode];
		return SeedDMS_Core_DMS::mergeAccessLists($pacl, $this->_accessList[$mode]);
	} /* }}} */

	/**
	 * Add access right to folder
	 * This function may change in the future. Instead of passing a flag
	 * and a user/group id a user or group object will be expected.
	 * Starting with version 5.1.25 this method will first check if there
	 * is already an access right for the user/group.
	 *
	 * @param integer $mode access mode
	 * @param integer $userOrGroupID id of user or group
	 * @param integer $isUser set to 1 if $userOrGroupID is the id of a
	 *        user
	 * @return bool
	 */
	function addAccess($mode, $userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		if($mode < M_NONE || $mode > M_ALL)
			return false;

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		/* Adding a second access right will return false */
		$queryStr = "SELECT * FROM `tblACLs` WHERE `targetType` = ".T_DOCUMENT.
				" AND `target` = " . $this->_id . " AND ". $userOrGroup . " = ".$userOrGroupID;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) || $resArr)
				return false;

		$queryStr = "INSERT INTO `tblACLs` (`target`, `targetType`, ".$userOrGroup.", `mode`) VALUES
					(".$this->_id.", ".T_DOCUMENT.", " . (int) $userOrGroupID . ", " .(int) $mode. ")";
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		if ($mode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	/**
	 * Change access right of document
	 * This function may change in the future. Instead of passing the a flag
	 * and a user/group id a user or group object will be expected.
	 *
	 * @param integer $newMode access mode
	 * @param integer $userOrGroupID id of user or group
	 * @param integer $isUser set to 1 if $userOrGroupID is the id of a
	 *        user
	 * @return bool
	 */
	function changeAccess($newMode, $userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		$queryStr = "UPDATE `tblACLs` SET `mode` = " . (int) $newMode . " WHERE `targetType` = ".T_DOCUMENT." AND `target` = " . $this->_id . " AND " . $userOrGroup . " = " . (int) $userOrGroupID;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		if ($newMode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	/**
	 * Remove access rights for a user or group
	 *
	 * @param integer $userOrGroupID ID of user or group
	 * @param boolean $isUser true if $userOrGroupID is a user id, false if it
	 *        is a group id.
	 * @return boolean true on success, otherwise false
	 */
	function removeAccess($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		$queryStr = "DELETE FROM `tblACLs` WHERE `targetType` = ".T_DOCUMENT." AND `target` = ".$this->_id." AND ".$userOrGroup." = " . (int) $userOrGroupID;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if the user looses access rights.
		$mode = ($isUser ? $this->getAccessMode($this->_dms->getUser($userOrGroupID)) : $this->getGroupAccessMode($this->_dms->getGroup($userOrGroupID)));
		if ($mode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	/**
	 * Returns the greatest access privilege for a given user
	 *
	 * This function returns the access mode for a given user. An administrator
	 * and the owner of the folder has unrestricted access. A guest user has
	 * read only access or no access if access rights are further limited
	 * by access control lists. All other users have access rights according
	 * to the access control lists or the default access. This function will
	 * recursive check for access rights of parent folders if access rights
	 * are inherited.
	 *
	 * The function searches the access control list for entries of
	 * user $user. If it finds more than one entry it will return the
	 * one allowing the greatest privileges, but user rights will always
	 * precede group rights. If there is no entry in the
	 * access control list, it will return the default access mode.
	 * The function takes inherited access rights into account.
	 * For a list of possible access rights see @file inc.AccessUtils.php
	 *
	 * Having access on a document does not necessarily mean the document
	 * content is accessible too. Accessing the content is checked by
	 * {@link SeedDMS_Core_DocumentContent::getAccessMode()} which calls
	 * a callback function defined by the application. If the callback
	 * function is not set, access on the content is always granted.
	 *
	 * Before checking the access in the method itself a callback 'onCheckAccessDocument'
	 * is called. If it returns a value > 0, then this will be returned by this
	 * method without any further checks. The optional paramater $context
	 * will be passed as a third parameter to the callback. It contains
	 * the operation for which the access mode is retrieved. It is for example
	 * set to 'removeDocument' if the access mode is used to check for sufficient
	 * permission on deleting a document.
	 *
	 * @param $user object instance of class SeedDMS_Core_User
	 * @param string $context context in which the access mode is requested
	 * @return integer access mode
	 */
	function getAccessMode($user, $context='') { /* {{{ */
		if(!$user)
			return M_NONE;

		/* Check if 'onCheckAccessDocument' callback is set */
		if(isset($this->_dms->callbacks['onCheckAccessDocument'])) {
			foreach($this->_dms->callbacks['onCheckAccessDocument'] as $callback) {
				if(($ret = call_user_func($callback[0], $callback[1], $this, $user, $context)) > 0) {
					return $ret;
				}
			}
		}

		/* Administrators have unrestricted access */
		if ($user->isAdmin()) return M_ALL;

		/* The owner of the document has unrestricted access */
		if ($user->getID() == $this->_ownerID) return M_ALL;

		/* Check ACLs */
		$accessList = $this->getAccessList();
		if (!$accessList) return false;

		/** @var SeedDMS_Core_UserAccess $userAccess */
		foreach ($accessList["users"] as $userAccess) {
			if ($userAccess->getUserID() == $user->getID()) {
				$mode = $userAccess->getMode();
				if ($user->isGuest()) {
					if ($mode >= M_READ) $mode = M_READ;
				}
				return $mode;
			}
		}

		/* Get the highest right defined by a group */
		if($accessList['groups']) {
			$mode = 0;
			/** @var SeedDMS_Core_GroupAccess $groupAccess */
			foreach ($accessList["groups"] as $groupAccess) {
				if ($user->isMemberOfGroup($groupAccess->getGroup())) {
					if ($groupAccess->getMode() > $mode)
						$mode = $groupAccess->getMode();
				}
			}
			if($mode) {
				if ($user->isGuest()) {
					if ($mode >= M_READ) $mode = M_READ;
				}
				return $mode;
			}
		}

		$mode = $this->getDefaultAccess();
		if ($user->isGuest()) {
			if ($mode >= M_READ) $mode = M_READ;
		}
		return $mode;
	} /* }}} */

	/**
	 * Returns the greatest access privilege for a given group
	 *
	 * This function searches the access control list for entries of
	 * group $group. If it finds more than one entry it will return the
	 * one allowing the greatest privileges. If there is no entry in the
	 * access control list, it will return the default access mode.
	 * The function takes inherited access rights into account.
	 * For a list of possible access rights see @file inc.AccessUtils.php
	 *
	 * @param SeedDMS_Core_Group $group object instance of class SeedDMS_Core_Group
	 * @return integer access mode
	 */
	function getGroupAccessMode($group) { /* {{{ */
		$highestPrivileged = M_NONE;

		//ACLs durchforsten
		$foundInACL = false;
		$accessList = $this->getAccessList();
		if (!$accessList)
			return false;

		/** @var SeedDMS_Core_GroupAccess $groupAccess */
		foreach ($accessList["groups"] as $groupAccess) {
			if ($groupAccess->getGroupID() == $group->getID()) {
				$foundInACL = true;
				if ($groupAccess->getMode() > $highestPrivileged)
					$highestPrivileged = $groupAccess->getMode();
				if ($highestPrivileged == M_ALL) // max access right -> skip the rest
					return $highestPrivileged;
			}
		}

		if ($foundInACL)
			return $highestPrivileged;

		//Standard-Berechtigung verwenden
		return $this->getDefaultAccess();
	} /* }}} */

	/**
	 * Returns a list of all notifications
	 *
	 * The returned list has two elements called 'users' and 'groups'. Each one
	 * is an array itself countaining objects of class SeedDMS_Core_User and
	 * SeedDMS_Core_Group.
	 *
	 * @param integer $type type of notification (not yet used)
	 * @param bool $incdisabled set to true if disabled user shall be included
	 * @return array|bool
	 */
	function getNotifyList($type=0, $incdisabled=false) { /* {{{ */
		if (empty($this->_notifyList)) {
			$db = $this->_dms->getDB();

			$queryStr ="SELECT * FROM `tblNotify` WHERE `targetType` = " . T_DOCUMENT . " AND `target` = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_notifyList = array("groups" => array(), "users" => array());
			foreach ($resArr as $row)
			{
				if ($row["userID"] != -1) {
					$u = $this->_dms->getUser($row["userID"]);
					if($u && (!$u->isDisabled() || $incdisabled))
						array_push($this->_notifyList["users"], $u);
				} else { //if ($row["groupID"] != -1)
					$g = $this->_dms->getGroup($row["groupID"]);
					if($g)
						array_push($this->_notifyList["groups"], $g);
				}
			}
		}
		return $this->_notifyList;
	} /* }}} */

	/**
	 * Make sure only users/groups with read access are in the notify list
	 *
	 */
	function cleanNotifyList() { /* {{{ */
		// If any of the notification subscribers no longer have read access,
		// remove their subscription.
		if (empty($this->_notifyList))
			$this->getNotifyList();

		/* Make a copy of both notifier lists because removeNotify will empty
		 * $this->_notifyList and the second foreach will not work anymore.
		 */
		/** @var SeedDMS_Core_User[] $nusers */
		$nusers = $this->_notifyList["users"];
		/** @var SeedDMS_Core_Group[] $ngroups */
		$ngroups = $this->_notifyList["groups"];
		foreach ($nusers as $u) {
			if ($this->getAccessMode($u) < M_READ) {
				$this->removeNotify($u->getID(), true);
			}
		}
		foreach ($ngroups as $g) {
			if ($this->getGroupAccessMode($g) < M_READ) {
				$this->removeNotify($g->getID(), false);
			}
		}
	} /* }}} */

	/**
	 * Add a user/group to the notification list
	 * This function does not check if the currently logged in user
	 * is allowed to add a notification. This must be checked by the calling
	 * application.
	 *
	 * @param $userOrGroupID integer id of user or group to add
	 * @param $isUser integer 1 if $userOrGroupID is a user,
	 *                0 if $userOrGroupID is a group
	 * @return integer  0: Update successful.
	 *                 -1: Invalid User/Group ID.
	 *                 -2: Target User / Group does not have read access.
	 *                 -3: User is already subscribed.
	 *                 -4: Database / internal error.
	 */
	function addNotify($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser ? "`userID`" : "`groupID`");

		/* Verify that user / group exists. */
		$obj = ($isUser ? $this->_dms->getUser($userOrGroupID) : $this->_dms->getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		/* Verify that the requesting user has permission to add the target to
		 * the notification system.
		 */
		/*
		 * The calling application should enforce the policy on who is allowed
		 * to add someone to the notification system. If is shall remain here
		 * the currently logged in user should be passed to this function
		 *
		GLOBAL $user;
		if ($user->isGuest()) {
			return -2;
		}
		if (!$user->isAdmin()) {
			if ($isUser) {
				if ($user->getID() != $obj->getID()) {
					return -2;
				}
			}
			else {
				if (!$obj->isMember($user)) {
					return -2;
				}
			}
		}
		 */

		/* Verify that target user / group has read access to the document. */
		if ($isUser) {
			// Users are straightforward to check.
			if ($this->getAccessMode($obj) < M_READ) {
				return -2;
			}
		}
		else {
			// Groups are a little more complex.
			if ($this->getDefaultAccess() >= M_READ) {
				// If the default access is at least READ-ONLY, then just make sure
				// that the current group has not been explicitly excluded.
				$acl = $this->getAccessList(M_NONE, O_EQ);
				$found = false;
				/** @var SeedDMS_Core_GroupAccess $group */
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if ($found) {
					return -2;
				}
			}
			else {
				// The default access is restricted. Make sure that the group has
				// been explicitly allocated access to the document.
				$acl = $this->getAccessList(M_READ, O_GTEQ);
				if (is_bool($acl)) {
					return -4;
				}
				$found = false;
				/** @var SeedDMS_Core_GroupAccess $group */
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if (!$found) {
					return -2;
				}
			}
		}
		/* Check to see if user/group is already on the list. */
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_DOCUMENT."' ".
			"AND `tblNotify`.".$userOrGroup." = '".(int) $userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)>0) {
			return -3;
		}

		$queryStr = "INSERT INTO `tblNotify` (`target`, `targetType`, " . $userOrGroup . ") VALUES (" . $this->_id . ", " . T_DOCUMENT . ", " . (int) $userOrGroupID . ")";
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/**
	 * Remove a user or group from the notification list
	 * This function does not check if the currently logged in user
	 * is allowed to remove a notification. This must be checked by the calling
	 * application.
	 *
	 * @param integer $userOrGroupID id of user or group
	 * @param boolean $isUser boolean true if a user is passed in $userOrGroupID, false
	 *        if a group is passed in $userOrGroupID
	 * @param integer $type type of notification (0 will delete all) Not used yet!
	 * @return integer 0 if operation was succesful
	 *                 -1 if the userid/groupid is invalid
	 *                 -3 if the user/group is already subscribed
	 *                 -4 in case of an internal database error
	 */
	function removeNotify($userOrGroupID, $isUser, $type=0) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Verify that user / group exists. */
		/** @var SeedDMS_Core_Group|SeedDMS_Core_User $obj */
		$obj = ($isUser ? $this->_dms->getUser($userOrGroupID) : $this->_dms->getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		/* Verify that the requesting user has permission to add the target to
		 * the notification system.
		 */
		/*
		 * The calling application should enforce the policy on who is allowed
		 * to add someone to the notification system. If is shall remain here
		 * the currently logged in user should be passed to this function
		 *
		GLOBAL $user;
		if ($user->isGuest()) {
			return -2;
		}
		if (!$user->isAdmin()) {
			if ($isUser) {
				if ($user->getID() != $obj->getID()) {
					return -2;
				}
			}
			else {
				if (!$obj->isMember($user)) {
					return -2;
				}
			}
		}
		 */

		/* Check to see if the target is in the database. */
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_DOCUMENT."' ".
			"AND `tblNotify`.".$userOrGroup." = '".(int) $userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)==0) {
			return -3;
		}

		$queryStr = "DELETE FROM `tblNotify` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_DOCUMENT . " AND " . $userOrGroup . " = " . (int) $userOrGroupID;
		/* If type is given then delete only those notifications */
		if($type)
			$queryStr .= " AND `type` = ".(int) $type;
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/**
	 * Add content to a document
	 *
	 * Each document may have any number of content elements attached to it.
	 * Each content element has a version number. Newer versions (greater
	 * version number) replace older versions.
	 *
	 * @param string $comment comment
	 * @param object $user user who shall be the owner of this content
	 * @param string $tmpFile file containing the actuall content
	 * @param string $orgFileName original file name
	 * @param string $fileType
	 * @param string $mimeType MimeType of the content
	 * @param array $reviewers list of reviewers
	 * @param array $approvers list of approvers
	 * @param integer $version version number of content or 0 if next higher version shall be used.
	 * @param array $attributes list of version attributes. The element key
	 *        must be the id of the attribute definition.
	 * @param object $workflow
	 * @param integer $initstate intial document status
	 * @return bool|SeedDMS_Core_AddContentResultSet
	 */
	function addContent($comment, $user, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers=array(), $approvers=array(), $version=0, $attributes=array(), $workflow=null, $initstate=S_RELEASED) { /* {{{ */
		$db = $this->_dms->getDB();

		// the doc path is id/version.filetype
		$dir = $this->getDir();

		/* The version field in table tblDocumentContent used to be auto
		 * increment but that requires the field to be primary as well if
		 * innodb is used. That's why the version is now determined here.
		 */
		if ((int)$version<1) {
			$queryStr = "SELECT MAX(`version`) AS m FROM `tblDocumentContent` WHERE `document` = ".$this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$version = $resArr[0]['m']+1;
		}

		if($fileType == '.')
			$fileType = '';
		$filesize = SeedDMS_Core_File::fileSize($tmpFile);
		$checksum = SeedDMS_Core_File::checksum($tmpFile);

		$db->startTransaction();
		$queryStr = "INSERT INTO `tblDocumentContent` (`document`, `version`, `comment`, `date`, `createdBy`, `dir`, `orgFileName`, `fileType`, `mimeType`, `fileSize`, `checksum`) VALUES ".
						"(".$this->_id.", ".(int)$version.",".$db->qstr($comment).", ".$db->getCurrentTimestamp().", ".$user->getID().", ".$db->qstr($dir).", ".$db->qstr($orgFileName).", ".$db->qstr($fileType).", ".$db->qstr($mimeType).", ".$filesize.", ".$db->qstr($checksum).")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$contentID = $db->getInsertID('tblDocumentContent');

		// copy file
		if (!SeedDMS_Core_File::makeDir($this->_dms->contentDir . $dir)) {
			$db->rollbackTransaction();
			return false;
		}
		if($this->_dms->forceRename)
			$err = SeedDMS_Core_File::renameFile($tmpFile, $this->_dms->contentDir . $dir . $version . $fileType);
		else
			$err = SeedDMS_Core_File::copyFile($tmpFile, $this->_dms->contentDir . $dir . $version . $fileType);
		if (!$err) {
			$db->rollbackTransaction();
			return false;
		}

		$this->_content = null;
		$this->_latestContent = null;
		$content = $this->getLatestContent($contentID); /** @todo: Parameter not defined in Funktion */
		$docResultSet = new SeedDMS_Core_AddContentResultSet($content);
		$docResultSet->setDMS($this->_dms);

		if($attributes) {
			foreach($attributes as $attrdefid=>$attribute) {
				/* $attribute can be a string or an array */
				if($attribute) {
					if($attrdef = $this->_dms->getAttributeDefinition($attrdefid)) {
						if(!$content->setAttributeValue($attrdef, $attribute)) {
							$this->_removeContent($content);
							$db->rollbackTransaction();
							return false;
						}
					} else {
						$this->_removeContent($content);
						$db->rollbackTransaction();
						return false;
					}
				}
			}
		}

		$queryStr = "INSERT INTO `tblDocumentStatus` (`documentID`, `version`) ".
			"VALUES (". $this->_id .", ". (int) $version .")";
		if (!$db->getResult($queryStr)) {
			$this->_removeContent($content);
			$db->rollbackTransaction();
			return false;
		}

		$statusID = $db->getInsertID('tblDocumentStatus', 'statusID');

		if($workflow)
			$content->setWorkflow($workflow, $user);

		// Add reviewers into the database. Reviewers must review the document
		// and submit comments, if appropriate. Reviewers can also recommend that
		// a document be rejected.
		$pendingReview=false;
		/** @noinspection PhpUnusedLocalVariableInspection */
		foreach (array("i", "g") as $i){
			if (isset($reviewers[$i])) {
				foreach ($reviewers[$i] as $reviewerID) {
					$reviewer=($i=="i" ?$this->_dms->getUser($reviewerID) : $this->_dms->getGroup($reviewerID));
					$res = ($i=="i" ? $docResultSet->getContent()->addIndReviewer($reviewer, $user, true) : $docResultSet->getContent()->addGrpReviewer($reviewer, $user, true));
					$docResultSet->addReviewer($reviewer, $i, $res);
					// If no error is returned, or if the error is just due to email
					// failure, mark the state as "pending review".
					// FIXME: There seems to be no error code -4 anymore
					if ($res==0 || $res=-3 || $res=-4) {
						$pendingReview=true;
					}
				}
			}
		}
		// Add approvers to the database. Approvers must also review the document
		// and make a recommendation on its release as an approved version.
		$pendingApproval=false;
		/** @noinspection PhpUnusedLocalVariableInspection */
		foreach (array("i", "g") as $i){
			if (isset($approvers[$i])) {
				foreach ($approvers[$i] as $approverID) {
					$approver=($i=="i" ? $this->_dms->getUser($approverID) : $this->_dms->getGroup($approverID));
					$res=($i=="i" ? $docResultSet->getContent()->addIndApprover($approver, $user, true) : $docResultSet->getContent()->addGrpApprover($approver, $user, !$pendingReview));
					$docResultSet->addApprover($approver, $i, $res);
					// FIXME: There seems to be no error code -4 anymore
					if ($res==0 || $res=-3 || $res=-4) {
						$pendingApproval=true;
					}
				}
			}
		}

		// If there are no reviewers or approvers, the document is automatically
		// promoted to the released state.
		if ($pendingReview) {
			$status = S_DRAFT_REV;
			$comment = "";
		}
		elseif ($pendingApproval) {
			$status = S_DRAFT_APP;
			$comment = "";
		}
		elseif($workflow) {
			$status = S_IN_WORKFLOW;
			$comment = ", workflow: ".$workflow->getName();
		} elseif($initstate == S_DRAFT) {
			$status = $initstate;
			$comment = "";
		} else {
			$status = S_RELEASED;
			$comment = "";
		}
		$queryStr = "INSERT INTO `tblDocumentStatusLog` (`statusID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $statusID ."', '". $status."', 'New document content submitted". $comment ."', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$docResultSet->setStatus($status);

		$db->commitTransaction();
		return $docResultSet;
	} /* }}} */

	/**
	 * Replace a version of a document
	 *
	 * Each document may have any number of content elements attached to it.
	 * This function replaces the file content of a given version.
	 * Using this function is highly discourage, because it undermines the
	 * idea of keeping all versions of a document as originally saved.
	 * Content will only be replaced if the mimetype, filetype, user and
	 * original filename are identical to the version being updated.
	 *
	 * This function was introduced for the webdav server because any saving
	 * of a document created a new version.
	 *
	 * @param object $user user who shall be the owner of this content
	 * @param string $tmpFile file containing the actuall content
	 * @param string $orgFileName original file name
	 * @param string $fileType
	 * @param string $mimeType MimeType of the content
	 * @param integer $version version number of content or 0 if latest version shall be replaced.
	 * @return bool/array false in case of an error or a result set
	 */
	function replaceContent($version, $user, $tmpFile, $orgFileName, $fileType, $mimeType) { /* {{{ */
		$db = $this->_dms->getDB();

		// the doc path is id/version.filetype
		$dir = $this->getDir();

		/* If $version < 1 than replace the content of the latest version.
		 */
		if ((int) $version<1) {
			$queryStr = "SELECT MAX(`version`) AS m FROM `tblDocumentContent` WHERE `document` = ".$this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$version = $resArr[0]['m'];
		}

		$content = $this->getContentByVersion($version);
		if(!$content)
			return false;

		if($fileType == '.')
			$fileType = '';

		/* Check if $user, $orgFileName, $fileType and $mimeType are the same */
		if($user->getID() != $content->getUser()->getID()) {
			return false;
		}
		if($orgFileName != $content->getOriginalFileName()) {
			return false;
		}
		if($fileType != $content->getFileType()) {
			return false;
		}
		if($mimeType != $content->getMimeType()) {
			return false;
		}

		$filesize = SeedDMS_Core_File::fileSize($tmpFile);
		$checksum = SeedDMS_Core_File::checksum($tmpFile);

		$db->startTransaction();
		$queryStr = "UPDATE `tblDocumentContent` set `date`=".$db->getCurrentTimestamp().", `fileSize`=".$filesize.", `checksum`=".$db->qstr($checksum)." WHERE `id`=".$content->getID();
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// copy file
		if (!SeedDMS_Core_File::copyFile($tmpFile, $this->_dms->contentDir . $dir . $version . $fileType)) {
			$db->rollbackTransaction();
			return false;
		}

		$this->_content = null;
		$this->_latestContent = null;
		$db->commitTransaction();

		return true;
	} /* }}} */

	/**
	 * Return all content elements of a document
	 *
	 * This functions returns an array of content elements ordered by version.
	 * Version which are not accessible because of its status, will be filtered
	 * out. Access rights based on the document status are calculated for the
	 * currently logged in user.
	 *
	 * @return bool|SeedDMS_Core_DocumentContent[]
	 */
	function getContent() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_content)) {
			$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `document` = ".$this->_id." ORDER BY `version`";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_content = array();
			$classname = $this->_dms->getClassname('documentcontent');
			$user = $this->_dms->getLoggedInUser();
			foreach ($resArr as $row) {
				/** @var SeedDMS_Core_DocumentContent $content */
				$content = new $classname($row["id"], $this, $row["version"], $row["comment"], $row["date"], $row["createdBy"], $row["dir"], $row["orgFileName"], $row["fileType"], $row["mimeType"], $row['fileSize'], $row['checksum'], $row['revisiondate']);
				/* TODO: Better use content id as key in $this->_content. This
				 * would allow to remove a single content object in removeContent().
				 * Currently removeContent() must clear $this->_content completely
				 */
				if($user) {
					if($content->getAccessMode($user) >= M_READ)
						array_push($this->_content, $content);
				} else {
					array_push($this->_content, $content);
				}
			}
		}

		return $this->_content;
	} /* }}} */

	/**
	 * Return the content element of a document with a given version number
	 *
	 * This function will check if the version is accessible and return false
	 * if not. Access rights based on the document status are calculated for the
	 * currently logged in user.
	 *
	 * @param integer $version version number of content element
	 * @return SeedDMS_Core_DocumentContent|null|boolean object of class
	 * {@link SeedDMS_Core_DocumentContent}, null if not content was found,
	 * false in case of an error
	 */
	function getContentByVersion($version) { /* {{{ */
		if (!is_numeric($version)) return false;

		if (isset($this->_content)) {
			foreach ($this->_content as $revision) {
				if ($revision->getVersion() == $version)
					return $revision;
			}
			return null;
		}

		$db = $this->_dms->getDB();
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `document` = ".$this->_id." AND `version` = " . (int) $version;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		if (count($resArr) != 1)
			return null;

		$resArr = $resArr[0];
		$classname = $this->_dms->getClassname('documentcontent');
		/** @var SeedDMS_Core_DocumentContent $content */
		if($content = new $classname($resArr["id"], $this, $resArr["version"], $resArr["comment"], $resArr["date"], $resArr["createdBy"], $resArr["dir"], $resArr["orgFileName"], $resArr["fileType"], $resArr["mimeType"], $resArr['fileSize'], $resArr['checksum'], $resArr['revisiondate'])) {
			$user = $this->_dms->getLoggedInUser();
			/* A user with write access on the document may always see the version */
			if($user && $content->getAccessMode($user) == M_NONE)
				return null;
			else
				return $content;
		} else {
			return false;
		}
	} /* }}} */

	/**
	 * Check if a given version is the latest version of the document
	 *
	 * @param integer $version version number of content element
	 * @return SeedDMS_Core_DocumentContent|boolean object of class {@link SeedDMS_Core_DocumentContent}
	 * or false
	 */
	function isLatestContent($version) { /* {{{ */
		return $this->getLatestContent()->getVersion() == $version;
	} /* }}} */

	/**
	 * @return bool|null|SeedDMS_Core_DocumentContent
	 */
	function __getLatestContent() { /* {{{ */
		if (!$this->_latestContent) {
			$db = $this->_dms->getDB();
			$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `document` = ".$this->_id." ORDER BY `version` DESC LIMIT 1";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;
			if (count($resArr) != 1)
				return false;

			$resArr = $resArr[0];
			$classname = $this->_dms->getClassname('documentcontent');
			$this->_latestContent = new $classname($resArr["id"], $this, $resArr["version"], $resArr["comment"], $resArr["date"], $resArr["createdBy"], $resArr["dir"], $resArr["orgFileName"], $resArr["fileType"], $resArr["mimeType"], $resArr['fileSize'], $resArr['checksum'], $resArr['revisiondate']);
		}
		return $this->_latestContent;
	} /* }}} */

	/**
	 * Get the latest version of document
	 *
	 * This function returns the latest accessible version of a document.
	 * If content access has been restricted by the role of the user
	 * the function will go
	 * backwards in history until an accessible version is found. If none
	 * is found null will be returned.
	 * Access rights based on the document status are calculated for the
	 * currently logged in user.
	 *
	 * @return bool|SeedDMS_Core_DocumentContent object of class {@link SeedDMS_Core_DocumentContent}
	 */
	function getLatestContent() { /* {{{ */
		if (!$this->_latestContent) {
			$db = $this->_dms->getDB();
			$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `document` = ".$this->_id." ORDER BY `version` DESC";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$classname = $this->_dms->getClassname('documentcontent');
			$user = $this->_dms->getLoggedInUser();
			foreach ($resArr as $row) {
				/** @var SeedDMS_Core_DocumentContent $content */
				if (!$this->_latestContent) {
					$content = new $classname($row["id"], $this, $row["version"], $row["comment"], $row["date"], $row["createdBy"], $row["dir"], $row["orgFileName"], $row["fileType"], $row["mimeType"], $row['fileSize'], $row['checksum'], $row['revisiondate']);
					if($user) {
						/* If the user may even write the document, then also allow to see all content.
						 * This is needed because the user could upload a new version
						 */
						if($content->getAccessMode($user) >= M_READ) {
							$this->_latestContent = $content;
						}
					} else {
						$this->_latestContent = $content;
					}
				}
			}
		}

		return $this->_latestContent;
	} /* }}} */

	/**
	 * Remove version of document
	 *
	 * @param SeedDMS_Core_DocumentContent $version version number of content
	 * @return boolean true if successful, otherwise false
	 */
	private function _removeContent($version) { /* {{{ */
		$db = $this->_dms->getDB();

		$db->startTransaction();

		$status = $version->getStatus();
		$stID = $status["statusID"];

		$queryStr = "DELETE FROM `tblDocumentContent` WHERE `document` = " . $this->getID() . " AND `version` = " . $version->getVersion();
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblDocumentContentAttributes` WHERE `content` = " . $version->getId();
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblTransmittalItems` WHERE `document` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblDocumentStatusLog` WHERE `statusID` = '".$stID."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblDocumentStatus` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$status = $version->getReviewStatus();
		$stList = "";
		foreach ($status as $st) {
			$stList .= (strlen($stList)==0 ? "" : ", "). "'".$st["reviewID"]."'";
			$queryStr = "SELECT * FROM `tblDocumentReviewLog` WHERE `reviewID` = " . $st['reviewID'];
			$resArr = $db->getResultArray($queryStr);
			if ((is_bool($resArr) && !$resArr)) {
				$db->rollbackTransaction();
				return false;
			}
			foreach($resArr as $res) {
				$file = $this->_dms->contentDir . $this->getDir().'r'.$res['reviewLogID'];
				if(SeedDMS_Core_File::file_exists($file))
					SeedDMS_Core_File::removeFile($file);
			}
		}

		if (strlen($stList)>0) {
			$queryStr = "DELETE FROM `tblDocumentReviewLog` WHERE `tblDocumentReviewLog`.`reviewID` IN (".$stList.")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}
		$queryStr = "DELETE FROM `tblDocumentReviewers` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$status = $version->getApprovalStatus();
		$stList = "";
		foreach ($status as $st) {
			$stList .= (strlen($stList)==0 ? "" : ", "). "'".$st["approveID"]."'";
			$queryStr = "SELECT * FROM `tblDocumentApproveLog` WHERE `approveID` = " . $st['approveID'];
			$resArr = $db->getResultArray($queryStr);
			if ((is_bool($resArr) && !$resArr)) {
				$db->rollbackTransaction();
				return false;
			}
			foreach($resArr as $res) {
				$file = $this->_dms->contentDir . $this->getDir().'a'.$res['approveLogID'];
				if(SeedDMS_Core_File::file_exists($file))
					SeedDMS_Core_File::removeFile($file);
			}
		}

		if (strlen($stList)>0) {
			$queryStr = "DELETE FROM `tblDocumentApproveLog` WHERE `tblDocumentApproveLog`.`approveID` IN (".$stList.")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}
		$queryStr = "DELETE FROM `tblDocumentApprovers` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* Remove all receipts of document version.
		 * This implmentation is different from the above for removing approvals
		 * and reviews. It doesn't use getReceiptStatus() but reads the database
		 */
		$queryStr = "SELECT * FROM `tblDocumentRecipients` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr)) {
			$db->rollbackTransaction();
			return false;
		}

		$stList = array();
		foreach($resArr as $res) {
			$stList[] = $res['receiptID'];
		}

		if ($stList) {
			$queryStr = "DELETE FROM `tblDocumentReceiptLog` WHERE `receiptID` IN (".implode(',', $stList).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$queryStr = "DELETE FROM `tblDocumentRecipients` WHERE `receiptID` IN (".implode(',', $stList).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		/* Remove all revisions of document version.
		 * This implementation is different from the above for removing approvals
		 * and reviews. It doesn't use getRevisionStatus() but reads the database
		 */
		$queryStr = "SELECT * FROM `tblDocumentRevisors` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr)) {
			$db->rollbackTransaction();
			return false;
		}

		$stList = array();
		foreach($resArr as $res) {
			$stList[] = $res['revisionID'];
		}

		if ($stList) {
			$queryStr = "DELETE FROM `tblDocumentRevisionLog` WHERE `revisionID` IN (".implode(',', $stList).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$queryStr = "DELETE FROM `tblDocumentRevisors` WHERE `revisionID` IN (".implode(',', $stList).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$queryStr = "DELETE FROM `tblWorkflowDocumentContent` WHERE `document` = '". $this->getID() ."' AND `version` = '" . $version->getVersion()."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* Will be deleted automatically when record will be deleted
		 * from tblWorkflowDocumentContent
		$queryStr = "DELETE FROM `tblWorkflowLog` WHERE `document` = '". $this->getID() ."' AND `version` = '" . $version->getVersion."'";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		 */

		// remove only those document files attached to version
		$res = $this->getDocumentFiles($version->getVersion(), false);
		if (is_bool($res) && !$res) {
			$db->rollbackTransaction();
			return false;
		}

		foreach ($res as $documentfile)
			if(!$this->removeDocumentFile($documentfile->getId())) {
				$db->rollbackTransaction();
				return false;
			}

		if (SeedDMS_Core_File::file_exists( $this->_dms->contentDir.$version->getPath() ))
			if (!SeedDMS_Core_File::removeFile( $this->_dms->contentDir.$version->getPath() )) {
				$db->rollbackTransaction();
				return false;
			}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Call callback onPreRemoveDocument before deleting content
	 *
	 * @param SeedDMS_Core_DocumentContent $version version number of content
	 * @return bool|mixed
	 */
	function removeContent($version) { /* {{{ */
		$this->_dms->lasterror = '';
		$db = $this->_dms->getDB();

		/* Make sure the version exists */
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `document` = " . $this->getID() . " AND `version` = " . $version->getVersion();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		if (count($resArr)==0)
			return false;

		/* Make sure this is not the last version */
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `document` = " . $this->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		if (count($resArr)==1)
			return false;

		/* Check if 'onPreRemoveDocument' callback is set */
		if(isset($this->_dms->callbacks['onPreRemoveContent'])) {
			foreach($this->_dms->callbacks['onPreRemoveContent'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $version);
				if(is_bool($ret))
					return $ret;
			}
		}

		if(false === ($ret = self::_removeContent($version))) {
			return false;
		}

		/* Invalidate the content list and the latest content of this document,
		 * otherwise getContent() and getLatestContent()
		 * will still return the content just deleted.
		 */
		$this->_latestContent = null;
		$this->_content = null;

		/* Check if 'onPostRemoveDocument' callback is set */
		if(isset($this->_dms->callbacks['onPostRemoveContent'])) {
			foreach($this->_dms->callbacks['onPostRemoveContent'] as $callback) {
				if(!call_user_func($callback[0], $callback[1], $version)) {
				}
			}
		}

		return $ret;
	} /* }}} */

	/**
	 * Return a certain document link
	 *
	 * @param integer $linkID id of link
	 * @return SeedDMS_Core_DocumentLink|bool of SeedDMS_Core_DocumentLink or false in case of
	 *         an error.
	 */
	function getDocumentLink($linkID) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!is_numeric($linkID)) return false;

		$queryStr = "SELECT * FROM `tblDocumentLinks` WHERE `document` = " . $this->_id ." AND `id` = " . (int) $linkID;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		if (count($resArr)==0)
			return null;

		$resArr = $resArr[0];
		$document = $this->_dms->getDocument($resArr["document"]);
		$target = $this->_dms->getDocument($resArr["target"]);
		$link = new SeedDMS_Core_DocumentLink($resArr["id"], $document, $target, $resArr["userID"], $resArr["public"]);
		$user = $this->_dms->getLoggedInUser();
		if($link->getAccessMode($user, $document, $target) >= M_READ)
			return $link;
		return null;
	} /* }}} */

	/**
	 * Return all document links
	 *
	 * The list may contain all links to other documents, even those which
	 * may not be visible by certain users, unless you pass appropriate
	 * parameters to filter out public links and those created by
	 * the given user. The two parameters are or'ed. If $publiconly
	 * is set the method will return all public links disregarding the
	 * user. If $publiconly is not set but a user is set, the method
	 * will return all links of that user (public and none public).
	 * Setting a user and $publiconly to true will *not* return the
	 * public links of that user but all links which are public or
	 * owned by that user.
	 *
	 * The application must call
	 * SeedDMS_Core_DMS::filterDocumentLinks() afterwards to filter out
	 * those links pointing to a document not accessible by a given user.
	 *
	 * @param boolean           $publiconly return all publically visible links
	 * @param SeedDMS_Core_User $user       return also private links of this user
	 *
	 * @return array list of objects of class {@see SeedDMS_Core_DocumentLink}
	 */
	function getDocumentLinks($publiconly=false, $user=null) { /* {{{ */
		if (!isset($this->_documentLinks)) {
			$db = $this->_dms->getDB();

			$queryStr = "SELECT * FROM `tblDocumentLinks` WHERE `document` = " . $this->_id;
			$tmp = array();
			if($publiconly)
				$tmp[] = "`public`=1";
			if($user)
				$tmp[] = "`userID`=".$user->getID();
			if($tmp) {
				$queryStr .= " AND (".implode(" OR ", $tmp).")";
			}

			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;
			$this->_documentLinks = array();

			$user = $this->_dms->getLoggedInUser();
			foreach ($resArr as $row) {
				$target = $this->_dms->getDocument($row["target"]);
				$link = new SeedDMS_Core_DocumentLink($row["id"], $this, $target, $row["userID"], $row["public"]);
				if($link->getAccessMode($user, $this, $target) >= M_READ)
					array_push($this->_documentLinks, $link);
			}
		}
		return $this->_documentLinks;
	} /* }}} */

	/**
	 * Return all document having a link on this document
	 *
	 * The list contains all documents which have a link to the current
	 * document. The list contains even those documents which
	 * may not be accessible by the user, unless you pass appropriate
	 * parameters to filter out public links and those created by
	 * the given user.
	 * This functions is basically the reverse of
	 * {@see SeedDMS_Core_Document::getDocumentLinks()}
	 *
	 * The application must call
	 * SeedDMS_Core_DMS::filterDocumentLinks() afterwards to filter out
	 * those links pointing to a document not accessible by a given user.
	 *
	 * @param boolean           $publiconly return all publically visible links
	 * @param SeedDMS_Core_User $user       return also private links of this user
	 *
	 * @return array list of objects of class SeedDMS_Core_DocumentLink
	 */
	function getReverseDocumentLinks($publiconly=false, $user=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblDocumentLinks` WHERE `target` = " . $this->_id;
		$tmp = array();
		if($publiconly)
			$tmp[] = "`public`=1";
		if($user)
			$tmp[] = "`userID`=".$user->getID();
		if($tmp) {
			$queryStr .= " AND (".implode(" OR ", $tmp).")";
		}

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$links = array();
		foreach ($resArr as $row) {
			$document = $this->_dms->getDocument($row["document"]);
			$link = new SeedDMS_Core_DocumentLink($row["id"], $document, $this, $row["userID"], $row["public"]);
			if($link->getAccessMode($user, $document, $this) >= M_READ)
				array_push($links, $link);
		}

		return $links;
	} /* }}} */

	function addDocumentLink($targetID, $userID, $public) { /* {{{ */
		$db = $this->_dms->getDB();

		$public = ($public) ? 1 : 0;

		if (!is_numeric($targetID) || $targetID < 1)
			return false;

		if ($targetID == $this->_id)
			return false;

		if (!is_numeric($userID) || $userID < 1)
			return false;

		if(!($target = $this->_dms->getDocument($targetID)))
			return false;

		if(!($user = $this->_dms->getUser($userID)))
			return false;

		$queryStr = "INSERT INTO `tblDocumentLinks` (`document`, `target`, `userID`, `public`) VALUES (".$this->_id.", ".(int)$targetID.", ".(int)$userID.", ".$public.")";
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_documentLinks);

		$id = $db->getInsertID('tblDocumentLinks');
		$link = new SeedDMS_Core_DocumentLink($id, $this, $target, $user->getId(), $public);
		return $link;
	} /* }}} */

	function removeDocumentLink($linkID) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!is_numeric($linkID) || $linkID < 1)
			return false;

		$queryStr = "DELETE FROM `tblDocumentLinks` WHERE `document` = " . $this->_id ." AND `id` = " . (int) $linkID;
		if (!$db->getResult($queryStr)) return false;
		unset ($this->_documentLinks);
		return true;
	} /* }}} */

	/**
	 * Get attached file by its id
	 *
	 * @return object instance of SeedDMS_Core_DocumentFile, null if file is not
	 * accessible, false in case of an sql error
	 */
	function getDocumentFile($ID) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!is_numeric($ID)) return false;

		$queryStr = "SELECT * FROM `tblDocumentFiles` WHERE `document` = " . $this->_id ." AND `id` = " . (int) $ID;
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || count($resArr)==0) return false;

		$resArr = $resArr[0];
		$classname = $this->_dms->getClassname('documentfile');
		$file = new $classname($resArr["id"], $this, $resArr["userID"], $resArr["comment"], $resArr["date"], $resArr["dir"], $resArr["fileType"], $resArr["mimeType"], $resArr["orgFileName"], $resArr["name"],$resArr["version"],$resArr["public"]);
		$user = $this->_dms->getLoggedInUser();
		if($file->getAccessMode($user) >= M_READ)
			return $file;
		return null;
	} /* }}} */

	/**
	 * Get list of files attached to document
	 *
	 * @param integer $version      get only attachments for this version
	 * @param boolean $incnoversion include attachments without a version
	 *
	 * @return array list of files, false in case of an sql error
	 */
	function getDocumentFiles($version=0, $incnoversion=true) { /* {{{ */
		/* use a smarter caching because removing a document will call this function
		 * for each version and the document itself.
		 */
		$hash = substr(md5($version.$incnoversion), 0, 4);
		if (!isset($this->_documentFiles[$hash])) {
			$db = $this->_dms->getDB();

			$queryStr = "SELECT * FROM `tblDocumentFiles` WHERE `document` = " . $this->_id;
			if($version) {
				if($incnoversion)
					$queryStr .= " AND (`version`=0 OR `version`=".(int) $version.")";
				else
					$queryStr .= " AND (`version`=".(int) $version.")";
			}
			$queryStr .= " ORDER BY ";
			if($version) {
				$queryStr .= "`version` DESC,";
			}
			$queryStr .= "`date` DESC";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

			$this->_documentFiles = array($hash=>array());

			$user = $this->_dms->getLoggedInUser();
			$classname = $this->_dms->getClassname('documentfile');
			foreach ($resArr as $row) {
				$file = new $classname($row["id"], $this, $row["userID"], $row["comment"], $row["date"], $row["dir"], $row["fileType"], $row["mimeType"], $row["orgFileName"], $row["name"], $row["version"], $row["public"]);
				if($file->getAccessMode($user) >= M_READ)
					array_push($this->_documentFiles[$hash], $file);
			}
		}
		return $this->_documentFiles[$hash];
	} /* }}} */

	function addDocumentFile($name, $comment, $user, $tmpFile, $orgFileName, $fileType, $mimeType, $version=0, $public=1) { /* {{{ */
		$db = $this->_dms->getDB();

		$dir = $this->getDir();

		$db->startTransaction();
		$queryStr = "INSERT INTO `tblDocumentFiles` (`comment`, `date`, `dir`, `document`, `fileType`, `mimeType`, `orgFileName`, `userID`, `name`, `version`, `public`) VALUES ".
			"(".$db->qstr($comment).", ".$db->getCurrentTimestamp().", ".$db->qstr($dir).", ".$this->_id.", ".$db->qstr($fileType).", ".$db->qstr($mimeType).", ".$db->qstr($orgFileName).",".$user->getID().",".$db->qstr($name).", ".((int) $version).", ".($public ? 1 : 0).")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$id = $db->getInsertID('tblDocumentFiles');

		$file = $this->getDocumentFile($id);
		if (is_bool($file) && !$file) {
			$db->rollbackTransaction();
			return false;
		}

		// copy file
		if (!SeedDMS_Core_File::makeDir($this->_dms->contentDir . $dir)) return false;
		if($this->_dms->forceRename)
			$err = SeedDMS_Core_File::renameFile($tmpFile, $this->_dms->contentDir . $file->getPath());
		else
			$err = SeedDMS_Core_File::copyFile($tmpFile, $this->_dms->contentDir . $file->getPath());
		if (!$err) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();
		unset ($this->_documentFiles);
		return $file;
	} /* }}} */

	function removeDocumentFile($ID) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!is_numeric($ID) || $ID < 1)
			return false;

		$file = $this->getDocumentFile($ID);
		if (is_bool($file) && !$file) return false;

		$db->startTransaction();
		/* First delete the database record, because that can be undone
		 * if deletion of the file fails.
		 */
		$queryStr = "DELETE FROM `tblDocumentFiles` WHERE `document` = " . $this->getID() . " AND `id` = " . (int) $ID;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		if (SeedDMS_Core_File::file_exists( $this->_dms->contentDir . $file->getPath() )){
			if (!SeedDMS_Core_File::removeFile( $this->_dms->contentDir . $file->getPath() )) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();
		unset ($this->_documentFiles);

		return true;
	} /* }}} */

	/**
	 * Remove a document completly
	 *
	 * This methods calls the callback 'onPreRemoveDocument' before removing
	 * the document. The current document will be passed as the second
	 * parameter to the callback function. After successful deletion the
	 * 'onPostRemoveDocument' callback will be used. The current document id
	 * will be passed as the second parameter. If onPreRemoveDocument fails
	 * the whole function will fail and the document will not be deleted.
	 * The return value of 'onPostRemoveDocument' will be disregarded.
	 *
	 * @return boolean true on success, otherwise false
	 */
	function remove() { /* {{{ */
		$db = $this->_dms->getDB();
		$this->_dms->lasterror = '';

		/* Check if 'onPreRemoveDocument' callback is set */
		if(isset($this->_dms->callbacks['onPreRemoveDocument'])) {
			foreach($this->_dms->callbacks['onPreRemoveDocument'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this);
				if(is_bool($ret))
					return $ret;
			}
		}

		$res = $this->getContent();
		if (is_bool($res) && !$res) return false;

		$db->startTransaction();

		// remove content of document
		foreach ($this->_content as $version) {
			if (!$this->_removeContent($version)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		// remove all document files
		$res = $this->getDocumentFiles();
		if (is_bool($res) && !$res) {
			$db->rollbackTransaction();
			return false;
		}

		foreach ($res as $documentfile)
			if(!$this->removeDocumentFile($documentfile->getId())) {
				$db->rollbackTransaction();
				return false;
			}

		// TODO: versioning file?

		if (SeedDMS_Core_File::file_exists( $this->_dms->contentDir . $this->getDir() ))
			if (!SeedDMS_Core_File::removeDir( $this->_dms->contentDir . $this->getDir() )) {
				$db->rollbackTransaction();
				return false;
			}

		$queryStr = "DELETE FROM `tblDocuments` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblDocumentAttributes` WHERE `document` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblACLs` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_DOCUMENT;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblDocumentLinks` WHERE `document` = " . $this->_id . " OR `target` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblDocumentLocks` WHERE `document` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblDocumentCheckOuts` WHERE `document` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblDocumentFiles` WHERE `document` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblDocumentCategory` WHERE `documentID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete the notification list.
		$queryStr = "DELETE FROM `tblNotify` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_DOCUMENT;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		/* Check if 'onPostRemoveDocument' callback is set */
		if(isset($this->_dms->callbacks['onPostRemoveDocument'])) {
			foreach($this->_dms->callbacks['onPostRemoveDocument'] as $callback) {
				if(!call_user_func($callback[0], $callback[1], $this)) {
				}
			}
		}

		return true;
	} /* }}} */

	/**
	 * Get List of users and groups which have read access on the document
	 * The list will not include any guest users,
	 * administrators and the owner of the folder unless $listadmin resp.
	 * $listowner is set to true.
	 *
	 * This function is deprecated. Use
	 * {@see SeedDMS_Core_Document::getReadAccessList()} instead.
	 */
	protected function __getApproversList() { /* {{{ */
		return $this->getReadAccessList(0, 0, 0);
	} /* }}} */

	/**
	 * Returns a list of groups and users with read access on the document
	 *
	 * @param boolean $listadmin if set to true any admin will be listed too
	 * @param boolean $listowner if set to true the owner will be listed too
	 * @param boolean $listguest if set to true any guest will be listed too
	 *
	 * @return array list of users and groups
	 */
	function getReadAccessList($listadmin=0, $listowner=0, $listguest=0) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_readAccessList)) {
			$this->_readAccessList = array("groups" => array(), "users" => array());
			$userIDs = "";
			$groupIDs = "";
			$defAccess  = $this->getDefaultAccess();

			/* Check if the default access is < read access or >= read access.
			 * If default access is less than read access, then create a list
			 * of users and groups with read access.
			 * If default access is equal or greater then read access, then
			 * create a list of users and groups without read access.
			 */
			if ($defAccess<M_READ) {
				// Get the list of all users and groups that are listed in the ACL as
				// having read access to the document.
				$tmpList = $this->getAccessList(M_READ, O_GTEQ);
			}
			else {
				// Get the list of all users and groups that DO NOT have read access
				// to the document.
				$tmpList = $this->getAccessList(M_NONE, O_LTEQ);
			}
			/** @var SeedDMS_Core_GroupAccess $groupAccess */
			foreach ($tmpList["groups"] as $groupAccess) {
				$groupIDs .= (strlen($groupIDs)==0 ? "" : ", ") . $groupAccess->getGroupID();
			}

			/** @var SeedDMS_Core_UserAccess $userAccess */
			foreach ($tmpList["users"] as $userAccess) {
				$user = $userAccess->getUser();
				if (!$listadmin && $user->isAdmin()) continue;
				if (!$listowner && $user->getID() == $this->_ownerID) continue;
				if (!$listguest && $user->isGuest()) continue;
				$userIDs .= (strlen($userIDs)==0 ? "" : ", ") . $userAccess->getUserID();
			}

			// Construct a query against the users table to identify those users
			// that have read access to this document, either directly through an
			// ACL entry, by virtue of ownership or by having administrative rights
			// on the database.
			$queryStr="";
			/* If default access is less then read, $userIDs and $groupIDs contains
			 * a list of user with read access
			 */
			if ($defAccess < M_READ) {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
						"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
						"WHERE `tblGroupMembers`.`groupID` IN (". $groupIDs .") ".
						"AND `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." UNION ";
				}
				$queryStr .=
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`role` != ".SeedDMS_Core_User::role_guest.") ".
					"AND ((`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".SeedDMS_Core_User::role_admin.")".
					(strlen($userIDs) == 0 ? "" : " OR (`tblUsers`.`id` IN (". $userIDs ."))").
					") ORDER BY `login`";
			}
			/* If default access is equal or greater than M_READ, $userIDs and
			 * $groupIDs contains a list of user without read access
			 */
			else {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
						"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
						"WHERE `tblGroupMembers`.`groupID` NOT IN (". $groupIDs .")".
						"AND `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." ".
						(strlen($userIDs) == 0 ? "" : " AND (`tblUsers`.`id` NOT IN (". $userIDs ."))")." UNION ";
				} else {
					$queryStr .=
						"SELECT `tblUsers`.* FROM `tblUsers` ".
						"WHERE `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." ".
						(strlen($userIDs) == 0 ? "" : " AND (`tblUsers`.`id` NOT IN (". $userIDs ."))")." UNION ";
				}
				$queryStr .=
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".SeedDMS_Core_User::role_admin.") ".
//					"UNION ".
//					"SELECT `tblUsers`.* FROM `tblUsers` ".
//					"WHERE `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." ".
//					(strlen($userIDs) == 0 ? "" : " AND (`tblUsers`.`id` NOT IN (". $userIDs ."))").
					" ORDER BY `login`";
			}
			$resArr = $db->getResultArray($queryStr);
			if (!is_bool($resArr)) {
				foreach ($resArr as $row) {
					$user = $this->_dms->getUser($row['id']);
					if (!$listadmin && $user->isAdmin()) continue;
					if (!$listowner && $user->getID() == $this->_ownerID) continue;
					$this->_readAccessList["users"][] = $user;
				}
			}

			// Assemble the list of groups that have read access to the document.
			$queryStr="";
			if ($defAccess < M_READ) {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ".
						"WHERE `tblGroups`.`id` IN (". $groupIDs .") ORDER BY `name`";
				}
			}
			else {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ".
						"WHERE `tblGroups`.`id` NOT IN (". $groupIDs .") ORDER BY `name`";
				}
				else {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ORDER BY `name`";
				}
			}
			if (strlen($queryStr)>0) {
				$resArr = $db->getResultArray($queryStr);
				if (!is_bool($resArr)) {
					foreach ($resArr as $row) {
						$group = $this->_dms->getGroup($row["id"]);
						$this->_readAccessList["groups"][] = $group;
					}
				}
			}
		}
		return $this->_readAccessList;
	} /* }}} */

	/**
	 * Get the internally used folderList which stores the ids of folders from
	 * the root folder to the parent folder.
	 *
	 * @return string column separated list of folder ids
	 */
	function getFolderList() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `folderList` FROM `tblDocuments` WHERE id = ".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return $resArr[0]['folderList'];
	} /* }}} */

	/**
	 * Checks the internal data of the document and repairs it.
	 * Currently, this function only repairs an incorrect folderList
	 *
	 * @return boolean true on success, otherwise false
	 */
	function repair() { /* {{{ */
		$db = $this->_dms->getDB();

		$curfolderlist = $this->getFolderList();

		// calculate the folderList of the folder
		$parent = $this->getFolder();
		$pathPrefix="";
		$path = $parent->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}
		if($curfolderlist != $pathPrefix) {
			$queryStr = "UPDATE `tblDocuments` SET `folderList`='".$pathPrefix."' WHERE `id` = ". $this->_id;
			$res = $db->getResult($queryStr);
			if (!$res)
				return false;
		}
		return true;
	} /* }}} */

	/**
	 * Calculate the disk space including all versions of the document
	 *
	 * This is done by using the internal database field storing the
	 * filesize of a document version.
	 *
	 * @return integer total disk space in Bytes
	 */
	function getUsedDiskSpace() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT SUM(`fileSize`) sum FROM `tblDocumentContent` WHERE `document` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		return $resArr[0]['sum'];
	} /* }}} */

	/**
	 * Returns a list of events happend during the life of the document
	 *
	 * This includes the creation of new versions, approval and reviews, etc.
	 *
	 * @return array list of events
	 */
	function getTimeline() { /* {{{ */
		$db = $this->_dms->getDB();

		$timeline = array();

		$lc=$this->getLatestContent();
		$queryStr = "SELECT `revisiondate`, `version` FROM `tblDocumentContent` WHERE `document` = " . $this->_id . " AND `version` = " . $lc->getVersion();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		foreach ($resArr as $row) {
			if($row['revisiondate'] && substr($row['revisiondate'], 0, 4) != '0000')
				$timeline[] = array('date'=>substr($row['revisiondate'], 0, 10)." 00:00:00", 'allday'=>true, 'msg'=>'Scheduled revision of version '.$row['version'], 'type'=>'scheduled_revision', 'version'=>$row['version'], 'document'=>$this, 'params'=>array($row['version']));
		}

		$queryStr = "SELECT * FROM `tblDocumentFiles` WHERE `document` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		foreach ($resArr as $row) {
			$date = date('Y-m-d H:i:s', (int) $row['date']);
			$timeline[] = array('date'=>$date, 'msg'=>'Added attachment "'.$row['name'].'"', 'document'=>$this, 'type'=>'add_file', 'fileid'=>$row['id']);
		}

		$queryStr=
			"SELECT `tblDocumentStatus`.*, `tblDocumentStatusLog`.`statusLogID`,`tblDocumentStatusLog`.`status`, ".
			"`tblDocumentStatusLog`.`comment`, `tblDocumentStatusLog`.`date`, ".
			"`tblDocumentStatusLog`.`userID` ".
			"FROM `tblDocumentStatus` ".
			"LEFT JOIN `tblDocumentStatusLog` USING (`statusID`) ".
			"WHERE `tblDocumentStatus`.`documentID` = '". $this->_id ."' ".
			"ORDER BY `tblDocumentStatusLog`.`statusLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		/* The above query will also contain entries where a document status exists
		 * but no status log entry. Those records will have no date and must be
		 * skipped.
		 */
		foreach ($resArr as $row) {
			if($row['date']) {
				$date = $row['date'];
				$timeline[] = array('date'=>$date, 'msg'=>'Version '.$row['version'].': Status change to '.$row['status'], 'type'=>'status_change', 'version'=>$row['version'], 'document'=>$this, 'status'=>$row['status'], 'statusid'=>$row['statusID'], 'statuslogid'=>$row['statusLogID']);
			}
		}
		return $timeline;
	} /* }}} */

	/**
	 * Transfers the document to a new user
	 * 
	 * This method not just sets a new owner of the document but also
	 * transfers the document links, attachments and locks to the new user.
	 *
	 * @return boolean true if successful, otherwise false
	 */
	function transferToUser($newuser) { /* {{{ */
		$db = $this->_dms->getDB();

		if($newuser->getId() == $this->_ownerID)
			return true;

		$db->startTransaction();
		$queryStr = "UPDATE `tblDocuments` SET `owner` = ".$newuser->getId()." WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "UPDATE `tblDocumentLocks` SET `userID` = ".$newuser->getId()." WHERE `document` = " . $this->_id . " AND `userID` = ".$this->_ownerID;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "UPDATE `tblDocumentLinks` SET `userID` = ".$newuser->getId()." WHERE `document` = " . $this->_id . " AND `userID` = ".$this->_ownerID;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "UPDATE `tblDocumentFiles` SET `userID` = ".$newuser->getId()." WHERE `document` = " . $this->_id . " AND `userID` = ".$this->_ownerID;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$this->_ownerID = $newuser->getID();
		$this->_owner = $newuser;

		$db->commitTransaction();
		return true;
	} /* }}} */

} /* }}} */


/**
 * Class to represent content of a document
 *
 * Each document has content attached to it, often called a 'version' of the
 * document. The document content represents a file on the disk with some
 * meta data stored in the database. A document content has a version number
 * which is incremented with each replacement of the old content. Old versions
 * are kept unless they are explicitly deleted by
 * {@link SeedDMS_Core_Document::removeContent()}.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2022 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_DocumentContent extends SeedDMS_Core_Object { /* {{{ */
	/**
	 * @var object document
	 */
	protected $_document;

	/**
	 * @var integer version
	 */
	protected $_version;

	/**
	 * @var string comment
	 */
	protected $_comment;

	/**
	 * @var string date
	 */
	protected $_date;

	/**
	 * @var integer userID
	 */
	protected $_userID;

	/**
	 * @var string dir on disk (deprecated)
	 */
	protected $_dir;

	/**
	 * @var string original file name
	 */
	protected $_orgFileName;

	/**
	 * @var string file type (actually the extension without the leading dot)
	 */
	protected $_fileType;

	/**
	 * @var string mime type
	 */
	protected $_mimeType;

	/**
	 * @var string checksum of content
	 */
	protected $_checksum;

	/**
	 * @var object workflow
	 */
	protected $_workflow;

	/**
	 * @var object workflow state
	 */
	protected $_workflowState;

	/**
	 * @var object dms
	 */
	public $_dms;

	/**
	 * Recalculate the status of a document
	 *
	 * The methods checks the review and approval status and sets the
	 * status of the document accordingly.
	 *
	 * If status is S_RELEASED and the version has a workflow, then set
	 * the status to S_IN_WORKFLOW
	 * If status is S_RELEASED and there are reviewers => set status S_DRAFT_REV
	 * If status is S_RELEASED or S_DRAFT_REV and there are approvers => set
	 * status S_DRAFT_APP
	 * If status is draft and there are no approver and no reviewers => set
	 * status to S_RELEASED
	 * The status of a document with the current status S_OBSOLETE, S_REJECTED,
	 * S_NEEDS_CORRECTION or S_EXPIRED will not be changed unless the parameter
	 * $ignorecurrentstatus is set to true.
	 *
	 * This method may not be called after a negative approval or review to
	 * recalculated the status, because
	 * it doesn't take a defeating approval or review into account. This method
	 * does not set the status to S_REJECTED! It will
	 * just check for a pending workflow, approval or review and set the status
	 * accordingly, e.g. after the list of reviewers or appovers has been
	 * modified. If there is not pending workflow, approval or review the
	 * status will be set to S_RELEASED.
	 *
	 * This method will call {@see SeedDMS_Core_DocumentContent::setStatus()}
	 * which checks if the status has actually changed. This is, why this
	 * function can be called at any time without harm to the status log.
	 * The $initialstatus can be set, to define the status set when no other
	 * status is set. This happens if the document has no
	 *
	 * @param boolean $ignorecurrentstatus ignore the current status and
	 *        recalculate a new status in any case
	 * @param object $user the user initiating this method
	 * @param string $msg message stored in status log when status is set
	 * @param integer $initialstatus status to be set if no other status is set
	 */
	function verifyStatus($ignorecurrentstatus=false, $user=null, $msg='', $initialstatus=S_RELEASED) { /* {{{ */

		unset($this->_status);
		$st=$this->getStatus();

		/* Documents already obsoleted, rejected or expired will not change
		 * its status anymore, unless explicitly requested. Be aware, that
		 * this method has an unsufficient check for negative reviews and
		 * approvals. A document in status S_REJECTED may become S_RELEASED
		 * if there is at least one positive review or approval.
		 */
		if (!$ignorecurrentstatus && ($st["status"]==S_OBSOLETE || $st["status"]==S_REJECTED || $st["status"]==S_EXPIRED || $st["status"]==S_NEEDS_CORRECTION)) return $st['status'];

		$this->_workflow = null; // force to be reloaded from DB
		$hasworkflow = $this->getWorkflow() ? true : false;

		/* $pendingReview will be set when there are still open reviews */
		$pendingReview=false;
		/* $hasReview will be set if there is at least one positiv review */
		$hasReview=false;
		unset($this->_reviewStatus);  // force to be reloaded from DB
		$reviewStatus=$this->getReviewStatus();
		if (is_array($reviewStatus) && count($reviewStatus)>0) {
			foreach ($reviewStatus as $r){
				if ($r["status"]==0){
					$pendingReview=true;
					break;
				} elseif($r["status"]==1){
					$hasReview=true;
				}
			}
		}

		/* $pendingApproval will be set when there are still open approvals */
		$pendingApproval=false;
		/* $hasApproval will be set if there is at least one positiv review */
		$hasApproval=false;
		unset($this->_approvalStatus);  // force to be reloaded from DB
		$approvalStatus=$this->getApprovalStatus();
		if (is_array($approvalStatus) && count($approvalStatus)>0) {
			foreach ($approvalStatus as $a){
				if ($a["status"]==0){
					$pendingApproval=true;
					break;
				} elseif($a["status"]==1){
					$hasApproval=true;
				}
			}
		}
		$pendingRevision=false;
		$hasRevision=false;
		$needsCorrection=false;
		unset($this->_revisionStatus);  // force to be reloaded from DB
		$revsisionStatus=$this->getRevisionStatus();
		if (is_array($revsisionStatus) && count($revsisionStatus)>0) {
			foreach ($revsisionStatus as $a){
				if ($a["status"]==0){
					$pendingRevision=true;
					break;
				} elseif($a["status"]==1){
					$hasRevision=true;
				} elseif($a["status"]==-1){
					$needsCorrection=true;
				}
			}
		}

		$ret = false;
		/* First check for a running workflow or open reviews, approvals, revisions. */
		if ($hasworkflow) { $newstatus = S_IN_WORKFLOW; $ret = $this->setStatus(S_IN_WORKFLOW,$msg,$user); }
		elseif ($pendingReview) { $newstatus = S_DRAFT_REV; $ret = $this->setStatus(S_DRAFT_REV,$msg,$user); }
		elseif ($pendingApproval) { $newstatus = S_DRAFT_APP; $ret = $this->setStatus(S_DRAFT_APP,$msg,$user); }
		elseif ($pendingRevision) { $newstatus = S_IN_REVISION; $ret = $this->setStatus(S_IN_REVISION,$msg,$user); }
		/* This point will only be reached if there is no pending workflow, review,
		 * approval or revision but the current status is one of S_DRAFT_REV,
		 * S_DRAFT_APP or S_IN_REVISION. This can happen if formely set reviewers,
		 * approvers, revisors are completly removed. In case of S_DRAFT_REV and
		 * S_DRAFT_APP the document will go back into its initial status. If a
		 * positive review or approval was found the document will be released.
		 * Be aware that negative reviews or approvals are not taken into account,
		 * because in that case the document must have been rejected before calling
		 * this function. FIXME: this is a problem if the parameter $ignorecurrentstatus
		 * was set, because an already rejected document may be released with just
		 * one positive review or approval disregarding any negative reviews or
		 * approvals.
		 * A document in status S_IN_REVISION will be treated differently.
		 * It takes negative revisions into account!
		 *
		 * A document in status S_DRAFT will never go into S_RELEASED and document
		 * already released will never go back at this point into the given
		 * initial status, which can only by S_DRAFT or S_RELEASED
		 */
		elseif ($st["status"]!=S_DRAFT && $st["status"]!=S_RELEASED ) {
			if($st["status"]==S_DRAFT_REV || $st["status"]==S_DRAFT_APP) {
				if($hasReview || $hasApproval) { $newstatus = S_RELEASED; $ret = $this->setStatus(S_RELEASED,$msg,$user); }
				else { $newstatus = $initialstatus; $ret = $this->setStatus($initialstatus,$msg,$user); }
			} elseif($st["status"]==S_IN_REVISION) {
				if($needsCorrection) { $newstatus = S_NEEDS_CORRECTION; $ret = $this->setStatus(S_NEEDS_CORRECTION,$msg,$user); }
				else {
					$newstatus = S_RELEASED;
					$ret = $this->finishRevision($user, S_RELEASED, 'Finished revision workflow', $msg);
				}
			} elseif($st["status"]==S_EXPIRED) {
				$newstatus = S_RELEASED; $ret = $this->setStatus(S_RELEASED,$msg,$user);
			} elseif($st["status"]==S_IN_WORKFLOW) {
				$newstatus = $initialstatus; $ret = $this->setStatus($initialstatus,$msg,$user);
			}
		}

		return $ret ? $newstatus : $ret;
	} /* }}} */

	function __construct($id, $document, $version, $comment, $date, $userID, $dir, $orgFileName, $fileType, $mimeType, $fileSize=0, $checksum='', $revisionDate=null) { /* {{{ */
		parent::__construct($id);
		$this->_document = $document;
		$this->_version = (int) $version;
		$this->_comment = $comment;
		$this->_date = (int) $date;
		$this->_userID = (int) $userID;
		$this->_dir = $dir;
		$this->_orgFileName = $orgFileName;
		$this->_fileType = $fileType;
		$this->_mimeType = $mimeType;
		$this->_dms = $document->getDMS();
		if(!$fileSize) {
			$this->_fileSize = SeedDMS_Core_File::fileSize($this->_dms->contentDir . $this->getPath());
		} else {
			$this->_fileSize = $fileSize;
		}
		$this->_checksum = $checksum;
		$this->_workflow = null;
		$this->_workflowState = null;
		$this->_revisionDate = $revisionDate;
	} /* }}} */

	/**
	 * Return an document content by its id
	 *
	 * @param integer $id id of document
	 * @param SeedDMS_Core_DMS $dms
	 * @return bool|SeedDMS_Core_DocumentContent instance of SeedDMS_Core_DocumentContent
	 * if document content exists, null if document does not exist, false in case of error
	 */
	public static function getInstance($id, $dms) { /* {{{ */
		$db = $dms->getDB();

		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `id` = " . (int) $id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return null;
		$row = $resArr[0];

		$classname = $dms->getClassname('documentcontent');
		$user = $dms->getLoggedInUser();
		$document = $dms->getDocument($row['document']);
		$document->setDMS($dms);
		/** @var SeedDMS_Core_DocumentContent $documentcontent */
		$content = new $classname($row["id"], $document, $row["version"], $row["comment"], $row["date"], $row["createdBy"], $row["dir"], $row["orgFileName"], $row["fileType"], $row["mimeType"], $row['fileSize'], $row['checksum'], $row['revisiondate']);
		if($user) {
			if($content->getAccessMode($user) >= M_READ)
				return $content;
		} else {
			return $content;
		}
		return null;
	} /* }}} */

	/**
	 * Check if this object is of type 'documentcontent'.
	 *
	 * @param string $type type of object
	 */
	public function isType($type) { /* {{{ */
		return $type == 'documentcontent';
	} /* }}} */

	function getVersion() { return $this->_version; }
	function getComment() { return $this->_comment; }
	function getDate() { return $this->_date; }
	function getOriginalFileName() { return $this->_orgFileName; }
	function getFileType() { return $this->_fileType; }
	function getFileName(){ return $this->_version . $this->_fileType; }
	/**
	 * getDir and the corresponding database table field are deprecated
	 */
	function __getDir() { return $this->_dir; }
	function getMimeType() { return $this->_mimeType; }
	function getRevisionDate() { return $this->_revisionDate; }
	function getDocument() { return $this->_document; }

	function getUser() { /* {{{ */
		if (!isset($this->_user))
			$this->_user = $this->_document->getDMS()->getUser($this->_userID);
		return $this->_user;
	} /* }}} */

	/**
	 * Return path of file on disk relative to the content directory
	 *
	 * Since version 5.1.13 a single '.' in the fileType will be skipped.
	 * On Windows a file named 'name.' will be saved as 'name' but the fileType
	 * will contain the a single '.'.
	 *
	 * @return string path of file on disc
	 */
	function getPath() { return $this->_document->getDir() . $this->_version . $this->_fileType; }

	function setRevisionDate($date = false) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$date)
			$queryStr = "UPDATE `tblDocumentContent` SET `revisiondate` = null WHERE `document` = " . $this->_document->getID() .	" AND `version` = " . $this->_version;
		elseif($date == 'now')
			$queryStr = "UPDATE `tblDocumentContent` SET `revisiondate` = ".$db->getCurrentDatetime()." WHERE `document` = " . $this->_document->getID() .	" AND `version` = " . $this->_version;
		else
			$queryStr = "UPDATE `tblDocumentContent` SET `revisiondate` = ".$db->qstr($date)." WHERE `document` = " . $this->_document->getID() .	" AND `version` = " . $this->_version;
		if (!$db->getResult($queryStr))
			return false;

		$this->_revisionDate = $date;

		return true;
	} /* }}} */

	/**
	 * Set upload date of document content
	 *
	 * @param string $date date must be a timestamp or in the format 'Y-m-d H:i:s'
	 *
	 * @return boolean true on success, otherwise false
	 */
	function setDate($date = false) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$date)
			$date = time();
		else {
			if(is_string($date) && SeedDMS_Core_DMS::checkDate($date, 'Y-m-d H:i:s')) {
				$date = strtotime($date);
			} elseif(is_numeric($date))
				$date = (int) $date;
			else
				return false;
		}

		$queryStr = "UPDATE `tblDocumentContent` SET `date` = ". $date." WHERE `document` = " . $this->_document->getID() . " AND `version` = " . $this->_version;
		if (!$db->getResult($queryStr))
			return false;

		$this->_date = $date;

		return true;
	} /* }}} */

	function getFileSize() { /* {{{ */
		return $this->_fileSize;
	} /* }}} */

	/**
	 * Set file size by reading the file
	 */
	function setFileSize() { /* {{{ */
		$filesize = SeedDMS_Core_File::fileSize($this->_dms->contentDir . $this->_document->getDir() . $this->getFileName());
		if($filesize === false)
			return false;

		$db = $this->_document->getDMS()->getDB();
		$queryStr = "UPDATE `tblDocumentContent` SET `fileSize` = ".$filesize." WHERE `document` = " . $this->_document->getID() . " AND `version` = " . $this->_version;
		if (!$db->getResult($queryStr))
			return false;
		$this->_fileSize = $filesize;

		return true;
	} /* }}} */

	function getChecksum() { /* {{{ */
		return $this->_checksum;
	} /* }}} */

	/**
	 * Set checksum by reading the file
	 */
	function setChecksum() { /* {{{ */
		$checksum = SeedDMS_Core_File::checksum($this->_dms->contentDir . $this->_document->getDir() . $this->getFileName());
		if($checksum === false)
			return false;

		$db = $this->_document->getDMS()->getDB();
		$queryStr = "UPDATE `tblDocumentContent` SET `checksum` = ".$db->qstr($checksum)." WHERE `document` = " . $this->_document->getID() . " AND `version` = " . $this->_version;
		if (!$db->getResult($queryStr))
			return false;
		$this->_checksum = $checksum;

		return true;
	} /* }}} */

	/**
	 * Set file type by evaluating the mime type
	 */
	function setFileType() { /* {{{ */
		$mimetype = $this->getMimeType();

		$expect = SeedDMS_Core_File::fileExtension($mimetype);
		if($expect && '.'.$expect != $this->_fileType) {
			$db = $this->_document->getDMS()->getDB();
			$db->startTransaction();
			$queryStr = "UPDATE `tblDocumentContent` SET `fileType`='.".$expect."' WHERE `id` =   ". $this->_id;
			$res = $db->getResult($queryStr);
			if ($res) {
				if(!SeedDMS_Core_File::renameFile($this->_dms->contentDir.$this->_document->getDir() . $this->_version . $this->_fileType, $this->_dms->contentDir.$this->_document->getDir() . $this->_version . '.' . $expect)) {
					$db->rollbackTransaction();
				} else {
					$this->_fileType = '.'.$expect;
					$db->commitTransaction();
					return true;
				}
			} else {
				$db->rollbackTransaction();
			}
		}

		return false;
	} /* }}} */

	function setMimeType($newMimetype) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$newMimetype)
			return false;

		$newMimetype = trim($newMimetype);

		if(!$newMimetype)
			return false;

		$queryStr = "UPDATE `tblDocumentContent` SET `mimeType` = ".$db->qstr($newMimetype)." WHERE `document` = " . $this->_document->getID() . " AND `version` = " . $this->_version;
		if (!$db->getResult($queryStr))
			return false;

		$this->_mimeType = $newMimetype;

		return true;
	} /* }}} */

	function setComment($newComment) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		/* Check if 'onPreSetVersionComment' callback is set */
		if(isset($this->_dms->callbacks['onPreSetVersionComment'])) {
			foreach($this->_dms->callbacks['onPreSetVersionComment'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newComment);
				if(is_bool($ret))
					return $ret;
			}
		}

		$queryStr = "UPDATE `tblDocumentContent` SET `comment` = ".$db->qstr($newComment)." WHERE `document` = " . $this->_document->getID() . " AND `version` = " . $this->_version;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;

		/* Check if 'onPostSetVersionComment' callback is set */
		if(isset($this->_dms->callbacks['onPostSetVersionComment'])) {
			foreach($this->_dms->callbacks['onPostSetVersionComment'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $oldComment);
				if(is_bool($ret))
					return $ret;
			}
		}

		return true;
	} /* }}} */

	/**
	 * Get the latest status of the content
	 *
	 * The status of the content reflects its current review, approval or workflow
	 * state. A status can be a negative or positive number or 0. A negative
	 * numbers indicate a missing approval, review or an obsolete content.
	 * Positive numbers indicate some kind of approval or workflow being
	 * active, but not necessarily a release.
	 * S_DRAFT_REV, 0
	 * S_DRAFT_APP, 1
	 * S_RELEASED, 2
	 * S_IN_WORKFLOW, 3
	 * S_IN_REVISION, 4
	 * S_REJECTED, -1
	 * S_OBSOLETE, -2
	 * S_EXPIRED, -3
	 * When a content is inserted and does not need approval nor review,
	 * then its status is set to S_RELEASED immediately. Any change of
	 * the status is monitored in the table tblDocumentStatusLog. This
	 * function will always return the latest entry for the content.
	 *
	 * @return array latest record from tblDocumentStatusLog
	 */
	function getStatus($limit=1) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!is_numeric($limit)) return false;

		// Retrieve the current overall status of the content represented by
		// this object.
		if (!isset($this->_status)) {
			$queryStr=
				"SELECT `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
				"`tblDocumentStatusLog`.`comment`, `tblDocumentStatusLog`.`date`, ".
				"`tblDocumentStatusLog`.`userID` ".
				"FROM `tblDocumentStatus` ".
				"LEFT JOIN `tblDocumentStatusLog` USING (`statusID`) ".
				"WHERE `tblDocumentStatus`.`documentID` = '". $this->_document->getID() ."' ".
				"AND `tblDocumentStatus`.`version` = '". $this->_version ."' ".
				"ORDER BY `tblDocumentStatusLog`.`statusLogID` DESC LIMIT ".(int) $limit;

			$res = $db->getResultArray($queryStr);
			if (is_bool($res) && !$res)
				return false;
			if (count($res)!=1)
				return false;
			$this->_status = $res[0];
		}
		return $this->_status;
	} /* }}} */

	/**
	 * Get current and former states of the document content
	 *
	 * @param integer $limit if not set all log entries will be returned
	 * @return array list of status changes
	 */
	function getStatusLog($limit=0) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!is_numeric($limit)) return false;

		$queryStr=
			"SELECT `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
			"`tblDocumentStatusLog`.`comment`, `tblDocumentStatusLog`.`date`, ".
			"`tblDocumentStatusLog`.`userID` ".
			"FROM `tblDocumentStatus` ".
			"LEFT JOIN `tblDocumentStatusLog` USING (`statusID`) ".
			"WHERE `tblDocumentStatus`.`documentID` = '". $this->_document->getID() ."' ".
			"AND `tblDocumentStatus`.`version` = '". $this->_version ."' ".
			"ORDER BY `tblDocumentStatusLog`.`statusLogID` DESC ";
		if($limit)
			$queryStr .= "LIMIT ".(int) $limit;

		$res = $db->getResultArray($queryStr);
		if (is_bool($res) && !$res)
			return false;

		return $res;
	} /* }}} */

	/**
	 * Set the status of the content
	 * Setting the status means to add another entry into the table
	 * tblDocumentStatusLog. The method returns also false if the status
	 * is already set on the value passed to the method.
	 *
	 * @param integer $status     new status of content
	 * @param string  $comment    comment for this status change
	 * @param object  $updateUser user initiating the status change
	 * @param string  $date       date in the format 'Y-m-d H:i:s'
	 *
	 * @return boolean true on success, otherwise false
	 */
	function setStatus($status, $comment, $updateUser, $date='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!is_numeric($status)) return false;

		/* return an error if $updateuser is not set */
		if(!$updateUser || !$updateUser->isType('user'))
			return false;

		// If the supplied value lies outside of the accepted range, return an
		// error.
		if ($status < S_LOWEST_STATUS || $status > S_HIGHEST_STATUS) {
			return false;
		}

		// Retrieve the current overall status of the content represented by
		// this object, if it hasn't been done already.
		if (!isset($this->_status)) {
			$this->getStatus();
		}
		if ($this->_status["status"]==$status) {
			return true;
		}
		if($date) {
			if(!SeedDMS_Core_DMS::checkDate($date, 'Y-m-d H:i:s'))
				return false;
			$ddate = $db->qstr($date);
		} else
			$ddate = $db->getCurrentDatetime();
		$db->startTransaction();
		$queryStr = "INSERT INTO `tblDocumentStatusLog` (`statusID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $this->_status["statusID"] ."', '". (int) $status ."', ".$db->qstr($comment).", ".$ddate.", '". $updateUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			$db->rollbackTransaction();
			return false;
		}

		/* Check if 'onSetStatus' callback is set */
		if(isset($this->_dms->callbacks['onSetStatus'])) {
			foreach($this->_dms->callbacks['onSetStatus'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $updateUser, $this->_status["status"], $status);
				if(is_bool($ret)) {
					unset($this->_status);
					if($ret)
						$db->commitTransaction();
					else
						$db->rollbackTransaction();
					return $ret;
				}
			}
		}

		$db->commitTransaction();
		unset($this->_status);
		return true;
	} /* }}} */

	/**
	 * Rewrites the complete status log
	 *
	 * Attention: this function is highly dangerous.
	 * It removes an existing status log and rewrites it.
	 * This method was added for importing an xml dump.
	 *
	 * @param array $statuslog new status log with the newest log entry first.
	 * @return boolean true on success, otherwise false
	 */
	function rewriteStatusLog($statuslog) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$queryStr= "SELECT `tblDocumentStatus`.* FROM `tblDocumentStatus` WHERE `tblDocumentStatus`.`documentID` = '". $this->_document->getID() ."' AND `tblDocumentStatus`.`version` = '". $this->_version ."' ";
		$res = $db->getResultArray($queryStr);
		if (is_bool($res) && !$res)
			return false;

		$statusID = $res[0]['statusID'];

		$db->startTransaction();

		/* First, remove the old entries */
		$queryStr = "DELETE FROM `tblDocumentStatusLog` WHERE `statusID`=".$statusID;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* Second, insert the new entries */
		$statuslog = array_reverse($statuslog);
		foreach($statuslog as $log) {
			if(!SeedDMS_Core_DMS::checkDate($log['date'], 'Y-m-d H:i:s')) {
				$db->rollbackTransaction();
				return false;
			}
			$queryStr = "INSERT INTO `tblDocumentStatusLog` (`statusID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('".$statusID ."', '".(int) $log['status']."', ".$db->qstr($log['comment']) .", ".$db->qstr($log['date']).", ".$log['user']->getID().")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();
		return true;
	} /* }}} */


	/**
	 * Returns the access mode similar to a document
	 *
	 * There is no real access mode for document content, so this is more
	 * like a virtual access mode, derived from the status of the document
	 * content. The function checks if {@link SeedDMS_Core_DMS::noReadForStatus}
	 * contains the status of the version and returns M_NONE if it exists and
	 * the user is not involved in a workflow or review/approval/revision.
	 * This method is called by all functions that returns the content e.g.
	 * {@link SeedDMS_Core_Document::getLatestContent()}
	 * It is also used by {@link SeedDMS_Core_Document::getAccessMode()} to
	 * prevent access on the whole document if there is no accessible version.
	 *
	 * FIXME: This function only works propperly if $u is the currently logged in
	 * user, because noReadForStatus will be set for this user.
	 * FIXED: instead of using $dms->noReadForStatus it is take from the user's role
	 *
	 * @param object $u user
	 * @return integer either M_NONE or M_READ
	 */
	function getAccessMode($u) { /* {{{ */
		$dms = $this->_document->getDMS();

		/* Check if 'onCheckAccessDocumentContent' callback is set */
		if(isset($this->_dms->callbacks['onCheckAccessDocumentContent'])) {
			foreach($this->_dms->callbacks['onCheckAccessDocumentContent'] as $callback) {
				if(($ret = call_user_func($callback[0], $callback[1], $this, $u)) > 0) {
					return $ret;
				}
			}
		}

//		return M_READ;

		if(!$u)
			return M_NONE;

		/* If read access isn't further restricted by status, than grant read access */
		/* Old code
		if(!$dms->noReadForStatus)
			return M_READ;
		$noReadForStatus = $dms->noReadForStatus;
		*/
		$noReadForStatus = $u->getRole()->getNoAccess();
		if(!$noReadForStatus)
			return M_READ;

		/* If the current status is not in list of status without read access, then grant read access */
		if(!in_array($this->getStatus()['status'], $noReadForStatus))
			return M_READ;

		/* Administrators have unrestricted access */
		if ($u->isAdmin()) return M_READ;

		/* The owner of the document has unrestricted access */
		$owner = $this->_document->getOwner();
		if ($u->getID() == $owner->getID()) return M_READ;

		/* Read/Write access on the document will also grant access on the version */
		if($this->_document->getAccessMode($u) >= M_READWRITE) return M_READ;

		/* At this point the current status is in the list of status without read access.
		 * The only way to still gain read access is, if the user is involved in the
		 * process, e.g. is a reviewer, approver or an active person in the workflow.
		 */
		$s = $this->getStatus();
		switch($s['status']) {
		case S_DRAFT_REV:
			$status = $this->getReviewStatus();
			foreach ($status as $r) {
				if($r['status'] != -2) // Check if reviewer was removed
					switch ($r["type"]) {
					case 0: // Reviewer is an individual.
						if($u->getId() == $r["required"])
							return M_READ;
						break;
					case 1: // Reviewer is a group.
						$required = $dms->getGroup($r["required"]);
						if (is_object($required) && $required->isMember($u))
							return M_READ;
						break;
					}
			}
			break;
		case S_DRAFT_APP:
			$status = $this->getApprovalStatus();
			foreach ($status as $r) {
				if($r['status'] != -2) // Check if approver was removed
					switch ($r["type"]) {
					case 0: // Reviewer is an individual.
						if($u->getId() == $r["required"])
							return M_READ;
						break;
					case 1: // Reviewer is a group.
						$required = $dms->getGroup($r["required"]);
						if (is_object($required) && $required->isMember($u))
							return M_READ;
						break;
					}
			}
			break;
		case S_RELEASED:
			break;
		case S_IN_WORKFLOW:
			if(!$this->_workflow)
				$this->getWorkflow();

			if($this->_workflow) {
				if (!$this->_workflowState)
					$this->getWorkflowState();
				$transitions = $this->_workflow['workflow']->getNextTransitions($this->_workflowState);
				foreach($transitions as $transition) {
					if($this->triggerWorkflowTransitionIsAllowed($u, $transition))
						return M_READ;
				}
			}
			break;
		case S_IN_REVISION:
			$status = $this->getRevisionStatus();
			foreach ($status as $r) {
				if($r['status'] != -2) // Check if reviewer was removed
					switch ($r["type"]) {
					case 0: // Revisor is an individual.
						if($u->getId() == $r["required"])
							return M_READ;
						break;
					case 1: // Revisor is a group.
						$required = $dms->getGroup($r["required"]);
						if (is_object($required) && $required->isMember($u))
							return M_READ;
						break;
					}
			}
			break;
		case S_REJECTED:
			break;
		case S_OBSOLETE:
			break;
		case S_EXPIRED:
			break;
		}

		return M_NONE;
	} /* }}} */

	/**
	 * Return a list of all reviewers separated by individuals and groups
	 * This list will not take the review log into account. Therefore it
	 * can contain which has actually been deleted as a reviewer.
	 *
	 * @return array|bool|null
	 */
	function getReviewers() { /* {{{ */
		$dms = $this->_document->getDMS();
		$db = $dms->getDB();

		$queryStr=
			"SELECT * FROM `tblDocumentReviewers` WHERE `version`='".$this->_version
			."' AND `documentID` = '". $this->_document->getID() ."' ";

		$recs = $db->getResultArray($queryStr);
		if (is_bool($recs))
			return false;
		$reviewers = array('i'=>array(), 'g'=>array());
		foreach($recs as $rec) {
			if($rec['type'] == 0) {
				if($u = $dms->getUser($rec['required']))
					$reviewers['i'][] = $u;
			} elseif($rec['type'] == 1) {
				if($g = $dms->getGroup($rec['required']))
					$reviewers['g'][] = $g;
			}
		}
		return $reviewers;
	} /* }}} */

	/**
	 * Get the current review status of the document content
	 * The review status is a list of reviewers and its current status
	 *
	 * @param integer $limit the number of recent status changes per reviewer
	 * @return array list of review status
	 */
	function getReviewStatus($limit=1) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!is_numeric($limit)) return false;

		// Retrieve the current status of each assigned reviewer for the content
		// represented by this object.
		// FIXME: caching was turned off to make list of review log in ViewDocument
		// possible
		if (1 || !isset($this->_reviewStatus)) {
			/* First get a list of all reviews for this document content */
			$queryStr=
				"SELECT `reviewID` FROM `tblDocumentReviewers` WHERE `version`='".$this->_version
				."' AND `documentID` = '". $this->_document->getID() ."' ";
			$recs = $db->getResultArray($queryStr);
			if (is_bool($recs) && !$recs)
				return false;
			$this->_reviewStatus = array();
			if($recs) {
				foreach($recs as $rec) {
					$queryStr=
						"SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`reviewLogID`, `tblDocumentReviewLog`.`status`, ".
						"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
						"`tblDocumentReviewLog`.`userID`, `tblUsers`.`fullName`, `tblGroups`.`name` AS `groupName` ".
						"FROM `tblDocumentReviewers` ".
						"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
						"LEFT JOIN `tblUsers` on `tblUsers`.`id` = `tblDocumentReviewers`.`required`".
						"LEFT JOIN `tblGroups` on `tblGroups`.`id` = `tblDocumentReviewers`.`required`".
						"WHERE `tblDocumentReviewers`.`reviewID` = '". $rec['reviewID'] ."' ".
						"ORDER BY `tblDocumentReviewLog`.`reviewLogID` DESC LIMIT ".(int) $limit;

					$res = $db->getResultArray($queryStr);
					if (is_bool($res) && !$res) {
						unset($this->_reviewStatus);
						return false;
					}
					foreach($res as &$t) {
						$filename = $this->_dms->contentDir . $this->_document->getDir().'r'.$t['reviewLogID'];
						if(SeedDMS_Core_File::file_exists($filename))
							$t['file'] = $filename;
						else
							$t['file'] = '';
					}
					$this->_reviewStatus = array_merge($this->_reviewStatus, $res);
				}
			}
		}
		return $this->_reviewStatus;
	} /* }}} */

	/**
	 * Get the latest entries from the review log of the document content
	 *
	 * @param integer $limit the number of log entries returned, defaults to 1
	 * @return array list of review log entries
	 */
	function getReviewLog($limit=1) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!is_numeric($limit)) return false;

		$queryStr=
			"SELECT * FROM `tblDocumentReviewLog` LEFT JOIN `tblDocumentReviewers` ON  `tblDocumentReviewLog`.`reviewID` = `tblDocumentReviewers`.`reviewID` WHERE `version`='".$this->_version
			."' AND `documentID` = '". $this->_document->getID() ."' "
			."ORDER BY `tblDocumentReviewLog`.`reviewLogID` DESC LIMIT ".(int) $limit;
		$recs = $db->getResultArray($queryStr);
		if (is_bool($recs) && !$recs)
			return false;
		return($recs);
	} /* }}} */

	/**
	 * Rewrites the complete review log
	 *
	 * Attention: this function is highly dangerous.
	 * It removes an existing review log and rewrites it.
	 * This method was added for importing an xml dump.
	 *
	 * @param array $reviewlog new status log with the newest log entry first.
	 * @return boolean true on success, otherwise false
	 */
	function rewriteReviewLog($reviewers) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$queryStr= "SELECT `tblDocumentReviewers`.* FROM `tblDocumentReviewers` WHERE `tblDocumentReviewers`.`documentID` = '". $this->_document->getID() ."' AND `tblDocumentReviewers`.`version` = '". $this->_version ."' ";
		$res = $db->getResultArray($queryStr);
		if (is_bool($res) && !$res)
			return false;

		$db->startTransaction();

		if($res) {
			foreach($res as $review) {
				$reviewID = $review['reviewID'];

				/* First, remove the old entries */
				$queryStr = "DELETE FROM `tblDocumentReviewLog` WHERE `reviewID`=".$reviewID;
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}

				$queryStr = "DELETE FROM `tblDocumentReviewers` WHERE `reviewID`=".$reviewID;
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
			}
		}

		/* Second, insert the new entries */
		foreach($reviewers as $review) {
			$queryStr = "INSERT INTO `tblDocumentReviewers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('".$this->_document->getID()."', '".$this->_version."', ".$review['type'] .", ".(is_object($review['required']) ? $review['required']->getID() : (int) $review['required']).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$reviewID = $db->getInsertID('tblDocumentReviewers', 'reviewID');
			$reviewlog = array_reverse($review['logs']);
			foreach($reviewlog as $log) {
				if(!SeedDMS_Core_DMS::checkDate($log['date'], 'Y-m-d H:i:s')) {
					$db->rollbackTransaction();
					return false;
				}
				$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
					"VALUES ('".$reviewID ."', '".(int) $log['status']."', ".$db->qstr($log['comment']) .", ".$db->qstr($log['date']).", ".(is_object($log['user']) ? $log['user']->getID() : (int) $log['user']).")";
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
				$reviewLogID = $db->getInsertID('tblDocumentReviewLog', 'reviewLogID');
				if(!empty($log['file'])) {
					SeedDMS_Core_File::copyFile($log['file'], $this->_dms->contentDir . $this->_document->getDir() . 'r' . $reviewLogID);
				}
			}
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Return a list of all approvers separated by individuals and groups
	 * This list will not take the approval log into account. Therefore it
	 * can contain which has actually been deleted as an approver.
	 *
	 * @return array|bool|null
	 */
	function getApprovers() { /* {{{ */
		$dms = $this->_document->getDMS();
		$db = $dms->getDB();

		$queryStr=
			"SELECT * FROM `tblDocumentApprovers` WHERE `version`='".$this->_version
			."' AND `documentID` = '". $this->_document->getID() ."' ";

		$recs = $db->getResultArray($queryStr);
		if (is_bool($recs))
			return false;
		$approvers = array('i'=>array(), 'g'=>array());
		foreach($recs as $rec) {
			if($rec['type'] == 0) {
				if($u = $dms->getUser($rec['required']))
					$approvers['i'][] = $u;
			} elseif($rec['type'] == 1) {
				if($g = $dms->getGroup($rec['required']))
					$approvers['g'][] = $g;
			}
		}
		return $approvers;
	} /* }}} */

	/**
	 * Get the current approval status of the document content
	 * The approval status is a list of approvers and its current status
	 *
	 * @param integer $limit the number of recent status changes per approver
	 * @return array list of approval status
	 */
	function getApprovalStatus($limit=1) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!is_numeric($limit)) return false;

		// Retrieve the current status of each assigned approver for the content
		// represented by this object.
		// FIXME: caching was turned off to make list of approval log in ViewDocument
		// possible
		if (1 || !isset($this->_approvalStatus)) {
			/* First get a list of all approvals for this document content */
			$queryStr=
				"SELECT `approveID` FROM `tblDocumentApprovers` WHERE `version`='".$this->_version
				."' AND `documentID` = '". $this->_document->getID() ."' ";
			$recs = $db->getResultArray($queryStr);
			if (is_bool($recs) && !$recs)
				return false;
			$this->_approvalStatus = array();
			if($recs) {
				foreach($recs as $rec) {
					$queryStr=
						"SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`approveLogID`, `tblDocumentApproveLog`.`status`, ".
						"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
						"`tblDocumentApproveLog`.`userID`, `tblUsers`.`fullName`, `tblGroups`.`name` AS `groupName` ".
						"FROM `tblDocumentApprovers` ".
						"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
						"LEFT JOIN `tblUsers` on `tblUsers`.`id` = `tblDocumentApprovers`.`required` ".
						"LEFT JOIN `tblGroups` on `tblGroups`.`id` = `tblDocumentApprovers`.`required`".
						"WHERE `tblDocumentApprovers`.`approveID` = '". $rec['approveID'] ."' ".
						"ORDER BY `tblDocumentApproveLog`.`approveLogID` DESC LIMIT ".(int) $limit;

					$res = $db->getResultArray($queryStr);
					if (is_bool($res) && !$res) {
						unset($this->_approvalStatus);
						return false;
					}
					foreach($res as &$t) {
						$filename = $this->_dms->contentDir . $this->_document->getDir().'a'.$t['approveLogID'];
						if(SeedDMS_Core_File::file_exists($filename))
							$t['file'] = $filename;
						else
							$t['file'] = '';
					}
					$this->_approvalStatus = array_merge($this->_approvalStatus, $res);
				}
			}
		}
		return $this->_approvalStatus;
	} /* }}} */

	/**
	 * Get the latest entries from the approval log of the document content
	 *
	 * @param integer $limit the number of log entries returned, defaults to 1
	 * @return array list of approval log entries
	 */
	function getApproveLog($limit=1) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!is_numeric($limit)) return false;

		$queryStr=
			"SELECT * FROM `tblDocumentApproveLog` LEFT JOIN `tblDocumentApprovers` ON  `tblDocumentApproveLog`.`approveID` = `tblDocumentApprovers`.`approveID` WHERE `version`='".$this->_version
			."' AND `documentID` = '". $this->_document->getID() ."' "
			."ORDER BY `tblDocumentApproveLog`.`approveLogID` DESC LIMIT ".(int) $limit;
		$recs = $db->getResultArray($queryStr);
		if (is_bool($recs) && !$recs)
			return false;
		return($recs);
	} /* }}} */

	/**
	 * Rewrites the complete approval log
	 *
	 * Attention: this function is highly dangerous.
	 * It removes an existing review log and rewrites it.
	 * This method was added for importing an xml dump.
	 *
	 * @param array $reviewlog new status log with the newest log entry first.
	 * @return boolean true on success, otherwise false
	 */
	function rewriteApprovalLog($reviewers) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$queryStr= "SELECT `tblDocumentApprovers`.* FROM `tblDocumentApprovers` WHERE `tblDocumentApprovers`.`documentID` = '". $this->_document->getID() ."' AND `tblDocumentApprovers`.`version` = '". $this->_version ."' ";
		$res = $db->getResultArray($queryStr);
		if (is_bool($res) && !$res)
			return false;

		$db->startTransaction();

		if($res) {
			foreach($res as $review) {
				$reviewID = $review['reviewID'];

				/* First, remove the old entries */
				$queryStr = "DELETE FROM `tblDocumentApproveLog` WHERE `approveID`=".$reviewID;
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}

				$queryStr = "DELETE FROM `tblDocumentApprovers` WHERE `approveID`=".$reviewID;
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
			}
		}

		/* Second, insert the new entries */
		foreach($reviewers as $review) {
			$queryStr = "INSERT INTO `tblDocumentApprovers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('".$this->_document->getID()."', '".$this->_version."', ".$review['type'] .", ".(is_object($review['required']) ? $review['required']->getID() : (int) $review['required']).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$reviewID = $db->getInsertID('tblDocumentApprovers', 'approveID');
			$reviewlog = array_reverse($review['logs']);
			foreach($reviewlog as $log) {
				if(!SeedDMS_Core_DMS::checkDate($log['date'], 'Y-m-d H:i:s')) {
					$db->rollbackTransaction();
					return false;
				}
				$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
					"VALUES ('".$reviewID ."', '".(int) $log['status']."', ".$db->qstr($log['comment']) .", ".$db->qstr($log['date']).", ".(is_object($log['user']) ? $log['user']->getID() : (int) $log['user']).")";
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
				$approveLogID = $db->getInsertID('tblDocumentApproveLog', 'approveLogID');
				if(!empty($log['file'])) {
					SeedDMS_Core_File::copyFile($log['file'], $this->_dms->contentDir . $this->_document->getDir() . 'a' . $approveLogID);
				}
			}
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Get the current receipt status of the document content
	 * The receipt status is a list of receipts
	 *
	 * @param integer $limit maximum number of status changes per receiver
	 * @return array list of receipts
	 */
	function getReceiptStatus($limit=1) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!is_numeric($limit)) return false;

		// Retrieve the current status of each assigned reviewer for the content
		// represented by this object.
		// When just the last log entry for each recipient is needed then a single
		// sql statement is much faster than the code below which first retrieves
		// all receivers and than the logs
		// FIXME: caching was turned off to make list of review log in ViewDocument
		// possible
		if($limit == 1) {
			/* The following sql statement is somewhat optimized. The first join is
			 * crucial because it should first take the table with the least number
			 * of records and join the other tables. ttreceiptid join tblDocumentRecipients
			 * is faster than tblDocumentRecipients join ttreceiptid
			 */
			if (!$db->createTemporaryTable("ttreceiptid")) {
				return false;
			}
			$queryStr=
				"SELECT `tblDocumentRecipients`.*, `tblDocumentReceiptLog`.`receiptLogID`, `tblDocumentReceiptLog`.`status`, `tblDocumentReceiptLog`.`comment`, `tblDocumentReceiptLog`.`date`, `tblDocumentReceiptLog`.`userID`, `tblUsers`.`fullName`, `tblGroups`.`name` FROM `ttreceiptid` LEFT JOIN `tblDocumentRecipients` ON `tblDocumentRecipients`.`receiptID`=`ttreceiptid`.`receiptID` LEFT JOIN `tblDocumentReceiptLog` ON `ttreceiptid`.`maxLogID`=`tblDocumentReceiptLog`.`receiptLogID` LEFT JOIN `tblUsers` ON `tblDocumentRecipients`.`required`=`tblUsers`.`id` LEFT JOIN `tblGroups` ON `tblDocumentRecipients`.`required`=`tblGroups`.`id` WHERE `version`='".$this->_version
				."' AND `documentID` = '". $this->_document->getID() ."' ";
			$recs = $db->getResultArray($queryStr);
			if (is_bool($recs) && !$recs) {
				unset($this->_receiptStatus);
				return false;
			}
			$this->_receiptStatus = $recs;
		} elseif (1 || !isset($this->_receiptStatus)) {
			/* First get a list of all receipts for this document content */
			$queryStr=
				"SELECT `receiptID` FROM `tblDocumentRecipients` WHERE `version`='".$this->_version
				."' AND `documentID` = '". $this->_document->getID() ."' ";
			$recs = $db->getResultArray($queryStr);
			if (is_bool($recs) && !$recs)
				return false;
			$this->_receiptStatus = array();
			if($recs) {
				foreach($recs as $rec) {
					$queryStr=
						"SELECT `tblDocumentRecipients`.*, `tblDocumentReceiptLog`.`receiptLogID`, ".
						"`tblDocumentReceiptLog`.`status`, ".
						"`tblDocumentReceiptLog`.`comment`, ".
						"`tblDocumentReceiptLog`.`date`, ".
						"`tblDocumentReceiptLog`.`userID`, `tblUsers`.`fullName`, `tblGroups`.`name` AS `groupName` ".
						"FROM `tblDocumentRecipients` ".
						"LEFT JOIN `tblDocumentReceiptLog` USING (`receiptID`) ".
						"LEFT JOIN `tblUsers` on `tblUsers`.`id` = `tblDocumentRecipients`.`required` ".
						"LEFT JOIN `tblGroups` on `tblGroups`.`id` = `tblDocumentRecipients`.`required` ".
						"WHERE `tblDocumentRecipients`.`receiptID` = '". $rec['receiptID'] ."' ".
						"ORDER BY `tblDocumentReceiptLog`.`receiptLogID` DESC LIMIT ".(int) $limit;

					$res = $db->getResultArray($queryStr);
					if (is_bool($res) && !$res) {
						unset($this->_receiptStatus);
						return false;
					}
					$this->_receiptStatus = array_merge($this->_receiptStatus, $res);
				}
			}
		}
		return $this->_receiptStatus;
	} /* }}} */

	/**
	 * Rewrites the complete receipt log
	 * 
	 * Attention: this function is highly dangerous.
	 * It removes an existing receipt log and rewrites it.
	 * This method was added for importing an xml dump.
	 *
	 * @param array $receiptlog new status log with the newest log entry first.
	 * @return boolean true on success, otherwise false
	 */
	function rewriteReceiptLog($recipients) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$queryStr= "SELECT `tblDocumentRecipients`.* FROM `tblDocumentRecipients` WHERE `tblDocumentRecipients`.`documentID` = '". $this->_document->getID() ."' AND `tblDocumentRecipients`.`version` = '". $this->_version ."' ";
		$res = $db->getResultArray($queryStr);
		if (is_bool($res) && !$res)
			return false;

		$db->startTransaction();

		if($res) {
			foreach($res as $receipt) {
				$receiptID = $receipt['receiptID'];

				/* First, remove the old entries */
				$queryStr = "DELETE from `tblDocumentReceiptLog` where `receiptID`=".$receiptID;
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}

				$queryStr = "DELETE from `tblDocumentRecipients` where `receiptID`=".$receiptID;
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
			}
		}

		/* Second, insert the new entries */
		foreach($recipients as $receipt) {
			$queryStr = "INSERT INTO `tblDocumentRecipients` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('".$this->_document->getID()."', '".$this->_version."', ".$receipt['type'] .", ".(is_object($receipt['required']) ? $receipt['required']->getID() : (int) $receipt['required']).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$receiptID = $db->getInsertID('tblDocumentRecipients', 'receiptID');
			$receiptlog = array_reverse($receipt['logs']);
			foreach($receiptlog as $log) {
				if(!SeedDMS_Core_DMS::checkDate($log['date'], 'Y-m-d H:i:s')) {
					$db->rollbackTransaction();
					return false;
				}
				$queryStr = "INSERT INTO `tblDocumentReceiptLog` (`receiptID`, `status`, `comment`, `date`, `userID`) ".
					"VALUES ('".$receiptID ."', '".(int) $log['status']."', ".$db->qstr($log['comment']) .", ".$db->qstr($log['date']).", ".(is_object($log['user']) ? $log['user']->getID() : (int) $log['user']).")";
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
				$receiptLogID = $db->getInsertID('tblDocumentReceiptLog', 'receiptLogID');
				if(!empty($log['file'])) {
					SeedDMS_Core_File::copyFile($log['file'], $this->_dms->contentDir . $this->_document->getDir() . 'r' . $receiptLogID);
				}
			}
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Get the current revision status of the document content
	 * The revision status is a list of revisions
	 * If $limit is 1 it will return just the last log entry for each
	 * revisor.
	 * Keep in mind that a revision log may contain repeating revisions.
	 *
	 * @param integer $limit maximum number of records per revisor
	 * @return array list of revisions
	 */
	function getRevisionStatus($limit=1) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!is_numeric($limit)) return false;

		// Retrieve the current status of each assigned reviewer for the content
		// represented by this object.
		// FIXME: caching was turned off to make list of review log in ViewDocument
		// possible
		if (1 || !isset($this->_revisionStatus)) {
			/* First get a list of all revisions for this document content */
			$queryStr=
				"SELECT `revisionID` FROM `tblDocumentRevisors` WHERE `version`='".$this->_version
				."' AND `documentID` = '". $this->_document->getID() ."' ";
			$recs = $db->getResultArray($queryStr);
			if (is_bool($recs) && !$recs)
				return false;
			$this->_revisionStatus = array();
			if($recs) {
				foreach($recs as $rec) {
					$queryStr=
						"SELECT `tblDocumentRevisors`.*, `tblDocumentRevisionLog`.`revisionLogID`, ".
						"`tblDocumentRevisionLog`.`status`, ".
						"`tblDocumentRevisionLog`.`comment`, ".
						"`tblDocumentRevisionLog`.`date`, ".
						"`tblDocumentRevisionLog`.`userID`, `tblUsers`.`fullName`, `tblGroups`.`name` AS `groupName` ".
						"FROM `tblDocumentRevisors` ".
						"LEFT JOIN `tblDocumentRevisionLog` USING (`revisionID`) ".
						"LEFT JOIN `tblUsers` on `tblUsers`.`id` = `tblDocumentRevisors`.`required` ".
						"LEFT JOIN `tblGroups` on `tblGroups`.`id` = `tblDocumentRevisors`.`required` ".
						"WHERE `tblDocumentRevisors`.`revisionID` = '". $rec['revisionID'] ."' ".
						"ORDER BY `tblDocumentRevisionLog`.`revisionLogID` DESC LIMIT ".(int) $limit;

					$res = $db->getResultArray($queryStr);
					if (is_bool($res) && !$res) {
						unset($this->_revisionStatus);
						return false;
					}
					$this->_revisionStatus = array_merge($this->_revisionStatus, $res);
				}
			}
		}
		return $this->_revisionStatus;
	} /* }}} */

	/**
	 * Rewrites the complete revision log
	 * 
	 * Attention: this function is highly dangerous.
	 * It removes an existing revision log and rewrites it.
	 * This method was added for importing an xml dump.
	 *
	 * @param array $revisionlog new status log with the newest log entry first.
	 * @return boolean 0 on success, otherwise a negativ error number
	 */
	function rewriteRevisionLog($revisions) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$queryStr= "SELECT `tblDocumentRevisors`.* FROM `tblDocumentRevisors` WHERE `tblDocumentRevisors`.`documentID` = '". $this->_document->getID() ."' AND `tblDocumentRevisors`.`version` = '". $this->_version ."' ";
		$res = $db->getResultArray($queryStr);
		if (is_bool($res) && !$res)
			return false;

		$db->startTransaction();

		if($res) {
			foreach($res as $revision) {
				$revisionID = $revision['revisionID'];

				/* First, remove the old entries */
				$queryStr = "DELETE from `tblDocumentRevisionLog` where `revisionID`=".$revisionID;
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}

				$queryStr = "DELETE from `tblDocumentRevisors` where `revisionID`=".$revisionID;
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
			}
		}

		/* Second, insert the new entries */
		foreach($revisions as $revision) {
			$queryStr = "INSERT INTO `tblDocumentRevisors` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('".$this->_document->getID()."', '".$this->_version."', ".$revision['type'] .", ".(is_object($revision['required']) ? $revision['required']->getID() : (int) $revision['required']).")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$revisionID = $db->getInsertID('tblDocumentRevisors', 'revisionID');
			$revisionlog = array_reverse($revision['logs']);
			foreach($revisionlog as $log) {
				if(!SeedDMS_Core_DMS::checkDate($log['date'], 'Y-m-d H:i:s')) {
					$db->rollbackTransaction();
					return false;
				}
				$queryStr = "INSERT INTO `tblDocumentRevisionLog` (`revisionID`, `status`, `comment`, `date`, `userID`) ".
					"VALUES ('".$revisionID ."', '".(int) $log['status']."', ".$db->qstr($log['comment']) .", ".$db->qstr($log['date']).", ".(is_object($log['user']) ? $log['user']->getID() : (int) $log['user']).")";
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
				$revisionLogID = $db->getInsertID('tblDocumentRevisionLog', 'revisionLogID');
				if(!empty($log['file'])) {
					SeedDMS_Core_File::copyFile($log['file'], $this->_dms->contentDir . $this->_document->getDir() . 'r' . $revisionLogID);
				}
			}
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Check if document version has a scheduled revision workflow.
	 * The method will update the document status log database table
	 * if needed and set the revisiondate of the content to $next.
	 *
	 * FIXME: This method does not check if there are any revisors left. Even
	 * if all revisors have been removed, it will still start the revision workflow!
	 * NOTE: This seems not the case anymore. The status of each revision is
	 * checked. Only if at least one status is S_LOG_SLEEPING the revision will be
	 * started. This wouldn't be the case if all revisors had been removed.
	 *
	 * @param object $user user requesting the possible automatic change
	 * @param string $next next date for review
	 * @return boolean true if status has changed
	 */
	function checkForDueRevisionWorkflow($user, $next=''){ /* {{{ */
		$st=$this->getStatus();

		/* A revision workflow will only be started if the document version is released */
		if($st["status"] == S_RELEASED) {
			/* First check if there are any scheduled revisions currently sleeping */
			$pendingRevision=false;
			unset($this->_revisionStatus);  // force to be reloaded from DB
			$revisionStatus=$this->getRevisionStatus();
			if (is_array($revisionStatus) && count($revisionStatus)>0) {
				foreach ($revisionStatus as $a){
					if ($a["status"]==S_LOG_SLEEPING || $a["status"]==S_LOG_SLEEPING){
						$pendingRevision=true;
						break;
					}
				}
			}
			if(!$pendingRevision)
				return false;

			/* We have sleeping revision, next check if the revision is already due */
			if($this->getRevisionDate() && $this->getRevisionDate() <= date('Y-m-d 00:00:00')) {
				if($this->startRevision($user, 'Automatic start of revision workflow scheduled for '.$this->getRevisionDate())) {
					if($next) {
						$tmp = explode('-', substr($next, 0, 10));
						if(checkdate($tmp[1], $tmp[2], $tmp[0]))
							$this->setRevisionDate($next);
					} else {
						$this->setRevisionDate(false);
					}
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Add user as new reviewer
	 *
	 * @param object $user user in charge for the review
	 * @param object $requestUser user requesting the operation (usually the
	 * currently logged in user)
	 *
	 * @return integer|false if > 0 the id of the review log, if < 0 the error
	 * code, false in case of an sql error
	 */
	function addIndReviewer($user, $requestUser) { /* {{{ */
		if(!$user || !$requestUser)
			return -1;

		$db = $this->_document->getDMS()->getDB();

		if(!$user->isType('user'))
			return -1;

		$userID = $user->getID();

		// Get the list of users and groups with read access to this document.
		if($this->_document->getAccessMode($user) < M_READ) {
			return -2;
		}

		// Check to see if the user has already been added to the review list.
		$reviewStatus = $user->getReviewStatus($this->_document->getID(), $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return false;
		}
		$indstatus = false;
		if (count($reviewStatus["indstatus"]) > 0) {
			$indstatus = array_pop($reviewStatus["indstatus"]);
			if($indstatus["status"]!=-2) {
				// User is already on the list of reviewers; return an error.
				return -3;
			}
		}

		// Add the user into the review database.
		if (!$indstatus || ($indstatus && $indstatus["status"]!=-2)) {
			$queryStr = "INSERT INTO `tblDocumentReviewers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '0', '". $userID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return false;
			}
			$reviewID = $db->getInsertID('tblDocumentReviewers', 'reviewID');
		}
		else {
			$reviewID = isset($indstatus["reviewID"]) ? $indstatus["reviewID"] : NULL;
		}

		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $reviewID ."', '0', '', ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return false;
		}

		$reviewLogID = $db->getInsertID('tblDocumentReviewLog', 'reviewLogID');
		$db->dropTemporaryTable('ttreviewid');
		return $reviewLogID;
	} /* }}} */

	/**
	 * Add group as new reviewer
	 *
	 * @param object $group group in charge for the review
	 * @param object $requestUser user requesting the operation (usually the
	 * currently logged in user)
	 *
	 * @return integer|false if > 0 the id of the review log, if < 0 the error
	 * code, false in case of an sql error
	 */
	function addGrpReviewer($group, $requestUser) { /* {{{ */
		if(!$group || !$requestUser)
			return -1;

		$db = $this->_document->getDMS()->getDB();

		if(!$group->isType('group'))
			return -1;

		$groupID = $group->getID();

		// Get the list of users and groups with read access to this document.
		if (!isset($this->_readAccessList)) {
			// TODO: error checking.
			$this->_readAccessList = $this->_document->getReadAccessList();
		}
		$approved = false;
		foreach ($this->_readAccessList["groups"] as $appGroup) {
			if ($groupID == $appGroup->getID()) {
				$approved = true;
				break;
			}
		}
		if (!$approved) {
			return -2;
		}

		// Check to see if the group has already been added to the review list.
		$reviewStatus = $group->getReviewStatus($this->_document->getID(), $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return false;
		}
		if (count($reviewStatus) > 0 && $reviewStatus[0]["status"]!=-2) {
			// Group is already on the list of reviewers; return an error.
			return -3;
		}

		// Add the group into the review database.
		if (!isset($reviewStatus[0]["status"]) || (isset($reviewStatus[0]["status"]) && $reviewStatus[0]["status"]!=-2)) {
			$queryStr = "INSERT INTO `tblDocumentReviewers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '1', '". $groupID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return false;
			}
			$reviewID = $db->getInsertID('tblDocumentReviewers', 'reviewID');
		}
		else {
			$reviewID = isset($reviewStatus[0]["reviewID"])?$reviewStatus[0]["reviewID"]:NULL;
		}

		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $reviewID ."', '0', '', ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return false;
		}

		$reviewLogID = $db->getInsertID('tblDocumentReviewLog', 'reviewLogID');
		$db->dropTemporaryTable('ttreviewid');
		return $reviewLogID;
	} /* }}} */

	/**
	 * Add a review to the document content
	 *
	 * This method will add an entry to the table tblDocumentReviewLog.
	 * It will first check if the user is ment to review the document version.
	 * It not the return value is -3.
	 * Next it will check if the users has been removed from the list of
	 * reviewers. In that case -4 will be returned.
	 * If the given review status has been set by the user before, it cannot
	 * be set again and 0 will be returned. Іf the review could be succesfully
	 * added, the review log id will be returned.
	 *
	 * @see SeedDMS_Core_DocumentContent::setApprovalByInd()
	 *
	 * @param object  $user user doing the review
	 * @param object  $requestUser user asking for the review, this is mostly
	 * the user currently logged in.
	 * @param integer $status status of review
	 * @param string  $comment comment for review
	 *
	 * @return integer|bool new review log id, error code 0 till -4,
	 * false in case of an sql error
	 */
	function setReviewByInd($user, $requestUser, $status, $comment, $file='') { /* {{{ */
		if(!$user || !$requestUser)
			return -1;

		$db = $this->_document->getDMS()->getDB();

		if(!$user->isType('user'))
			return -1;

		// Check if the user is on the review list at all.
		$reviewStatus = $user->getReviewStatus($this->_document->getID(), $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return false;
		}
		if (count($reviewStatus["indstatus"])==0) {
			// User is not assigned to review this document. No action required.
			// Return an error.
			return -3;
		}
		$indstatus = array_pop($reviewStatus["indstatus"]);
		if ($indstatus["status"]==-2) {
			// User has been deleted from reviewers
			return -4;
		}
		// Check if the status is really different from the current status
		if ($indstatus["status"] == $status)
			return 0;

		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`,
			`comment`, `date`, `userID`) ".
			"VALUES ('". $indstatus["reviewID"] ."', '".
			(int) $status ."', ".$db->qstr($comment).", ".$db->getCurrentDatetime().", '".
			$requestUser->getID() ."')";
		$res=$db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return false;

		$reviewLogID = $db->getInsertID('tblDocumentReviewLog', 'reviewLogID');
		if($file) {
			SeedDMS_Core_File::copyFile($file, $this->_dms->contentDir . $this->_document->getDir() . 'r' . $reviewLogID);
		}
		return $reviewLogID;
	} /* }}} */

	/**
	 * Add another entry to review log which resets the status
	 *
	 * This method will not delete anything from the database, but will add
	 * a new review log entry which sets the status to 0. This is only allowed
	 * if the current status is either 1 (reviewed) or -1 (rejected).
	 *
	 * After calling this method SeedDMS_Core_DocumentCategory::verifyStatus()
	 * should be called to recalculate the document status.
	 *
	 * @param integer $reviewid id of review
	 * @param SeedDMS_Core_User $requestUser user requesting the removal
	 * @param string $comment comment
	 *
	 * @return integer|bool true if successful, error code < 0,
	 * false in case of an sql error
	 */
	public function removeReview($reviewid, $requestUser, $comment='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		// Check to see if the user can be removed from the review list.
		$reviews = $this->getReviewStatus();
		if (is_bool($reviews) && !$reviews) {
			return false;
		}
		$reviewStatus = null;
		foreach($reviews as $review) {
			if($review['reviewID'] == $reviewid) {
				$reviewStatus = $review;
				break;
			}
		}
		if(!$reviewStatus)
			return -2;

		// The review log entry may only be removed if the status is 1 or -1
		if ($reviewStatus["status"] != 1 && $reviewStatus["status"] != -1)
			return -3;

		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`,
			`comment`, `date`, `userID`) ".
			"VALUES ('". $reviewStatus["reviewID"] ."', '0', ".$db->qstr($comment).", ".$db->getCurrentDatetime().", '".
			$requestUser->getID() ."')";
		$res=$db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return false;

		return true;
	} /* }}} */

	/**
	 * Add a review to the document content
	 *
	 * This method is similar to
	 * {@see SeedDMS_Core_DocumentContent::setReviewByInd()} but adds a review
	 * for a group instead of a user.
	 *
	 * @param object  $group group doing the review
	 * @param object  $requestUser user asking for the review, this is mostly
	 * the user currently logged in.
	 * @param integer $status status of review
	 * @param string  $comment comment for review
	 *
	 * @return integer|bool new review log id, error code 0 till -4,
	 * false in case of an sql error
	 */
	function setReviewByGrp($group, $requestUser, $status, $comment, $file='') { /* {{{ */
		if(!$group || !$requestUser)
			return -1;

		$db = $this->_document->getDMS()->getDB();

		if(!$group->isType('group'))
			return -1;

		// Check if the group is on the review list at all.
		$reviewStatus = $group->getReviewStatus($this->_document->getID(), $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return false;
		}
		if (count($reviewStatus)==0) {
			// User is not assigned to review this document. No action required.
			// Return an error.
			return -3;
		}
		if ((int) $reviewStatus[0]["status"]==-2) {
			// Group has been deleted from reviewers
			return -4;
		}

		// Check if the status is really different from the current status
		if ($reviewStatus[0]["status"] == $status)
			return 0;

		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`,
			`comment`, `date`, `userID`) ".
			"VALUES ('". $reviewStatus[0]["reviewID"] ."', '".
			(int) $status ."', ".$db->qstr($comment).", ".$db->getCurrentDatetime().", '".
			$requestUser->getID() ."')";
		$res=$db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return false;

		$reviewLogID = $db->getInsertID('tblDocumentReviewLog', 'reviewLogID');
		if($file) {
			SeedDMS_Core_File::copyFile($file, $this->_dms->contentDir . $this->_document->getDir() . 'r' . $reviewLogID);
		}
		return $reviewLogID;
 } /* }}} */

	/**
	 * Add user as new approver
	 *
	 * @param object $user user in charge for the approval
	 * @param object $requestUser user requesting the operation (usually the
	 * currently logged in user)
	 *
	 * @return integer|false if > 0 the id of the approval log, if < 0 the error
	 * code, false in case of an sql error
	 */
	function addIndApprover($user, $requestUser) { /* {{{ */
		if(!$user || !$requestUser)
			return -1;

		$db = $this->_document->getDMS()->getDB();

		if(!$user->isType('user'))
			return -1;

		$userID = $user->getID();

		// Get the list of users and groups with read access to this document.
		if($this->_document->getAccessMode($user) < M_READ) {
			return -2;
		}

		// Check if the user has already been added to the approvers list.
		$approvalStatus = $user->getApprovalStatus($this->_document->getID(), $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return false;
		}
		$indstatus = false;
		if (count($approvalStatus["indstatus"]) > 0) {
			$indstatus = array_pop($approvalStatus["indstatus"]);
			if($indstatus["status"]!=-2) {
				// User is already on the list of approverss; return an error.
				return -3;
			}
		}

		if ( !$indstatus || (isset($indstatus["status"]) && $indstatus["status"]!=-2)) {
			// Add the user into the approvers database.
			$queryStr = "INSERT INTO `tblDocumentApprovers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '0', '". $userID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return false;
			}
			$approveID = $db->getInsertID('tblDocumentApprovers', 'approveID');
		}
		else {
			$approveID = isset($indstatus["approveID"]) ? $indstatus["approveID"] : NULL;
		}

		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $approveID ."', '0', '', ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return false;
		}

		$approveLogID = $db->getInsertID('tblDocumentApproveLog', 'approveLogID');
		$db->dropTemporaryTable('ttapproveid');
		return $approveLogID;
	} /* }}} */

	/**
	 * Add group as new approver
	 *
	 * @param object $group group in charge for the approval
	 * @param object $requestUser user requesting the operation (usually the
	 * currently logged in user)
	 *
	 * @return integer|false if > 0 the id of the approval log, if < 0 the error
	 * code, false in case of an sql error
	 */
	function addGrpApprover($group, $requestUser) { /* {{{ */
		if(!$group || !$requestUser)
			return -1;

		$db = $this->_document->getDMS()->getDB();

		if(!$group->isType('group'))
			return -1;

		$groupID = $group->getID();

		// Get the list of users and groups with read access to this document.
		if (!isset($this->_readAccessList)) {
			// TODO: error checking.
			$this->_readAccessList = $this->_document->getReadAccessList();
		}
		$approved = false;
		foreach ($this->_readAccessList["groups"] as $appGroup) {
			if ($groupID == $appGroup->getID()) {
				$approved = true;
				break;
			}
		}
		if (!$approved) {
			return -2;
		}

		// Check if the group has already been added to the approver list.
		$approvalStatus = $group->getApprovalStatus($this->_document->getID(), $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return false;
		}
		if (count($approvalStatus) > 0 && $approvalStatus[0]["status"]!=-2) {
			// Group is already on the list of approvers; return an error.
			return -3;
		}

		// Add the group into the approver database.
		if (!isset($approvalStatus[0]["status"]) || (isset($approvalStatus[0]["status"]) && $approvalStatus[0]["status"]!=-2)) {
			$queryStr = "INSERT INTO `tblDocumentApprovers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '1', '". $groupID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return false;
			}
			$approveID = $db->getInsertID('tblDocumentApprovers', 'approveID');
		}
		else {
			$approveID = isset($approvalStatus[0]["approveID"])?$approvalStatus[0]["approveID"]:NULL;
		}

		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $approveID ."', '0', '', ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return false;
		}

		$approveLogID = $db->getInsertID('tblDocumentApproveLog', 'approveLogID');
		$db->dropTemporaryTable('ttapproveid');
		return $approveLogID;
	} /* }}} */

	/**
	 * Sets approval status of a document content for a user
	 *
	 * This function can be used to approve or reject a document content, or
	 * to reset its approval state. In most cases this function will be
	 * called by an user, but  an admin may set the approval for
	 * somebody else.
	 * It is first checked if the user is in the list of approvers at all.
	 * Then it is check if the approval status is already -2. In both cases
	 * the function returns with an error.
	 *
	 * @see SeedDMS_Core_DocumentContent::setReviewByInd()
	 *
	 * @param object  $user user in charge for doing the approval
	 * @param object  $requestUser user actually calling this function
	 * @param integer $status the status of the approval, possible values are
	 *        0=unprocessed (maybe used to reset a status)
	 *        1=approved,
	 *       -1=rejected,
	 *       -2=user is deleted (use {link
	 *       SeedDMS_Core_DocumentContent::delIndApprover} instead)
	 * @param string $comment approval comment
	 *
	 * @return integer|bool new review log id, error code 0 till -4,
	 * false in case of an sql error
	 */
	function setApprovalByInd($user, $requestUser, $status, $comment, $file='') { /* {{{ */
		if(!$user || !$requestUser)
			return -1;

		$db = $this->_document->getDMS()->getDB();

		if(!$user->isType('user'))
			return -1;

		// Check if the user is on the approval list at all.
		$approvalStatus = $user->getApprovalStatus($this->_document->getID(), $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return false;
		}
		if (count($approvalStatus["indstatus"])==0) {
			// User is not assigned to approve this document. No action required.
			// Return an error.
			return -3;
		}
		$indstatus = array_pop($approvalStatus["indstatus"]);
		if ($indstatus["status"]==-2) {
			// User has been deleted from approvers
			return -4;
		}
		// Check if the status is really different from the current status
		if ($indstatus["status"] == $status)
			return 0;

		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`,
			`comment`, `date`, `userID`) ".
			"VALUES ('". $indstatus["approveID"] ."', '".
			(int) $status ."', ".$db->qstr($comment).", ".$db->getCurrentDatetime().", '".
			$requestUser->getID() ."')";
		$res=$db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return false;

		$approveLogID = $db->getInsertID('tblDocumentApproveLog', 'approveLogID');
		if($file) {
			SeedDMS_Core_File::copyFile($file, $this->_dms->contentDir . $this->_document->getDir() . 'a' . $approveLogID);
		}
		return $approveLogID;
	} /* }}} */

	/**
	 * Add another entry to approval log which resets the status
	 *
	 * This method will not delete anything from the database, but will add
	 * a new approval log entry which sets the status to 0. This is only allowed
	 * if the current status is either 1 (approved) or -1 (rejected).
	 *
	 * After calling this method SeedDMS_Core_DocumentCategory::verifyStatus()
	 * should be called to recalculate the document status.
	 *
	 * @param integer $approveid id of approval
	 * @param SeedDMS_Core_User $requestUser user requesting the removal
	 * @param string $comment comment
	 *
	 * @return integer|bool true if successful, error code < 0,
	 * false in case of an sql error
	 */
	public function removeApproval($approveid, $requestUser, $comment='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		// Check to see if the user can be removed from the approval list.
		$approvals = $this->getApprovalStatus();
		if (is_bool($approvals) && !$approvals) {
			return false;
		}
		$approvalStatus = null;
		foreach($approvals as $approval) {
			if($approval['approveID'] == $approveid) {
				$approvalStatus = $approval;
				break;
			}
		}
		if(!$approvalStatus)
			return -2;

		// The approval log entry may only be removed if the status is 1 or -1
		if ($approvalStatus["status"] != 1 && $approvalStatus["status"] != -1)
			return -3;

		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`,
			`comment`, `date`, `userID`) ".
			"VALUES ('". $approvalStatus["approveID"] ."', '0', ".$db->qstr($comment).", ".$db->getCurrentDatetime().", '".
			$requestUser->getID() ."')";
		$res=$db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return false;

		return true;
 } /* }}} */

	/**
	 * Sets approval status of a document content for a group
	 *
	 * The functions behaves like
	 * {link SeedDMS_Core_DocumentContent::setApprovalByInd} but does it for
	 * a group instead of a user
	 */
	function setApprovalByGrp($group, $requestUser, $status, $comment, $file='') { /* {{{ */
		if(!$group || !$requestUser)
			return -1;

		$db = $this->_document->getDMS()->getDB();

		if(!$group->isType('group'))
			return -1;

		// Check if the group is on the approval list at all.
		$approvalStatus = $group->getApprovalStatus($this->_document->getID(), $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return false;
		}
		if (count($approvalStatus)==0) {
			// User is not assigned to approve this document. No action required.
			// Return an error.
			return -3;
		}
		if ($approvalStatus[0]["status"]==-2) {
			// Group has been deleted from approvers
			return -4;
		}

		// Check if the status is really different from the current status
		if ($approvalStatus[0]["status"] == $status)
			return 0;

		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`,
			`comment`, `date`, `userID`) ".
			"VALUES ('". $approvalStatus[0]["approveID"] ."', '".
			(int) $status ."', ".$db->qstr($comment).", ".$db->getCurrentDatetime().", '".
			$requestUser->getID() ."')";
		$res=$db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return false;

		$approveLogID = $db->getInsertID('tblDocumentApproveLog', 'approveLogID');
		if($file) {
			SeedDMS_Core_File::copyFile($file, $this->_dms->contentDir . $this->_document->getDir() . 'a' . $approveLogID);
		}
		return $approveLogID;
 } /* }}} */

	function addIndRecipient($user, $requestUser) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$userID = $user->getID();

		// Get the list of users and groups with read access to this document.
		if($this->_document->getAccessMode($user) < M_READ) {
			return -2;
		}

		// Check to see if the user has already been added to the receipt list.
		$receiptStatus = $user->getReceiptStatus($this->_document->getID(), $this->_version);
		if (is_bool($receiptStatus) && !$receiptStatus) {
			return -1;
		}
		$indstatus = false;
		if (count($receiptStatus["indstatus"]) > 0) {
			$indstatus = array_pop($receiptStatus["indstatus"]);
			if($indstatus["status"]!=-2) {
				// User is already on the list of recipients; return an error.
				return -3;
			}
		}

		// Add the user into the recipients database.
		if (!$indstatus || ($indstatus && $indstatus["status"]!=-2)) {
			$queryStr = "INSERT INTO `tblDocumentRecipients` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '0', '". $userID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return -1;
			}
			$receiptID = $db->getInsertID('tblDocumentRecipients', 'receiptID');
		}
		else {
			$receiptID = isset($indstatus["receiptID"]) ? $indstatus["receiptID"] : NULL;
		}

		$queryStr = "INSERT INTO `tblDocumentReceiptLog` (`receiptID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $receiptID ."', '0', '', ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		// Add recipient to event notification table.
		//$this->_document->addNotify($userID, true);

		$receiptLogID = $db->getInsertID('tblDocumentReceiptLog', 'receiptLogID');
		$db->dropTemporaryTable('ttreceiptid');
		return $receiptLogID;
	} /* }}} */

	function addGrpRecipient($group, $requestUser) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$groupID = $group->getID();

		// Get the list of users and groups with read access to this document.
		if (!isset($this->_readAccessList)) {
			// TODO: error checking.
			$this->_readAccessList = $this->_document->getReadAccessList();
		}
		$approved = false;
		foreach ($this->_readAccessList["groups"] as $appGroup) {
			if ($groupID == $appGroup->getID()) {
				$approved = true;
				break;
			}
		}
		if (!$approved) {
			return -2;
		}

		// Check to see if the group has already been added to the review list.
		$receiptStatus = $group->getReceiptStatus($this->_document->getID(), $this->_version);
		if (is_bool($receiptStatus) && !$receiptStatus) {
			return -1;
		}
		$status = false;
		if (count($receiptStatus["status"]) > 0) {
			$status = array_pop($receiptStatus["status"]);
			if($status["status"]!=-2) {
				// User is already on the list of recipients; return an error.
				return -3;
			}
		}

		// Add the group into the recipients database.
		if (!$status || ($status && $status["status"]!=-2)) {
			$queryStr = "INSERT INTO `tblDocumentRecipients` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '1', '". $groupID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return -1;
			}
			$receiptID = $db->getInsertID('tblDocumentRecipients', 'receiptID');
		}
		else {
			$receiptID = isset($status["receiptID"]) ? $status["receiptID"] : NULL;
		}

		$queryStr = "INSERT INTO `tblDocumentReceiptLog` (`receiptID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $receiptID ."', '0', '', ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		$receiptLogID = $db->getInsertID('tblDocumentReceiptLog', 'receiptLogID');
		$db->dropTemporaryTable('ttreceiptid');
		return $receiptLogID;
	} /* }}} */

	/**
	 * Add an individual revisor to the document content
	 *
	 * This function adds a user as a revisor but doesn't start the
	 * revision workflow by default. This behaviour is different from all
	 * other workflows (approval, review, receipt), because it adds
	 * an initial entry in the revision log, which marks the revision as
	 * 'sleeping'. The workflow is started at a later point in time by adding 
	 * the second entry in the revision log which puts it into 'waiting'.
	 *
	 * @param object $user user to be added as a revisor
	 * @param object $requestUser user requesting the addition
	 * @return integer 0 if successful otherwise a value < 0
	 */
	function addRevisor($object, $requestUser) { /* {{{ */
		$dms = $this->_document->getDMS();
		$db = $dms->getDB();

		/* getRevisionStatus() returns an array with either an element
		 * 'indstatus' (user) or 'status' (group) containing the revision log
		 */
		if(get_class($object) == $dms->getClassname('user')) {
			$field = 'indstatus';
			$type = 0;

			// Get the list of users and groups with read access to this document.
			if($this->_document->getAccessMode($object) < M_READ) {
				return -2;
			}
		} elseif(get_class($object) == $dms->getClassname('group')) {
			$field = 'status';
			$type = 1;

			// Get the list of users and groups with read access to this document.
			if($this->_document->getGroupAccessMode($object) < M_READ) {
				return -2;
			}
		} else {
			return -1;
		}

		// Check to see if the user has already been added to the revisor list.
		$revisionStatus = $object->getRevisionStatus($this->_document->getID(), $this->_version);
		if (is_bool($revisionStatus) && !$revisionStatus) {
			return -1;
		}

		/* There are two cases: 1. the user has not been added at all or 2.
		 * the user was added before but has been removed later. In both
		 * cases the user may be added. In case 2. 'indstatus' will be set
		 * and the last status is -2. If it is not -2, then the user is still
		 * in the process and cannot be added again.
		 */
		$indstatus = false;
		if(isset($revisionStatus[$field])) {
			if (count($revisionStatus[$field]) > 0) {
				$indstatus = array_pop($revisionStatus[$field]);
				if($indstatus["status"] != S_LOG_USER_REMOVED) {
					// User is already on the list of recipients; return an error.
					return -3;
				}
			}
		}

		// Add the user into the revisors database.
		if (!$indstatus) {
			$queryStr = "INSERT INTO `tblDocumentRevisors` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '". $type ."', '". $object->getID() ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return -1;
			}
			$revisionID = $db->getInsertID('tblDocumentRevisors', 'revisionID');
		} else {
			$revisionID = isset($indstatus["revisionID"]) ? $indstatus["revisionID"] : NULL;
		}

		/* If a user is added when the revision has already been startet, then
		 * put it into S_LOG_WAITING otherwise into S_LOG_SLEEPING. Attention, if a
		 * document content is in any other status but S_IN_REVISION, then it will
		 * end up in S_LOG_SLEEPING. As this method is also called by removeFromProcesses()
		 * when another user takes over the processes, it may happen that revisions
		 * of document contents in status e.g. S_OBSOLETE, S_EXPIRED will change its
		 * status from S_LOG_WAITING to S_LOG_SLEEPING. 
		 * This could only be fixed if this method could set an initial revision status
		 * by possibly passing it as another parameter to the method.
		 */
		$st=$this->getStatus();
		$queryStr = "INSERT INTO `tblDocumentRevisionLog` (`revisionID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $revisionID ."', '".($st["status"] == S_IN_REVISION ? S_LOG_WAITING : S_LOG_SLEEPING)."', '', ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		$revisionLogID = $db->getInsertID('tblDocumentRevisionLog', 'revisionLogID');
		$db->dropTemporaryTable('ttrevisionid');
		return $revisionLogID;
	} /* }}} */

	function addIndRevisor($user, $requestUser, $donotstart=true) { /* {{{ */
		return self::addRevisor($user, $requestUser, $donotstart);
	} /* }}} */

	function addGrpRevisor($group, $requestUser, $donotstart=true) { /* {{{ */
		return self::addRevisor($group, $requestUser, $donotstart);
	} /* }}} */

	/**
	 * Add a receipt to the document content
	 *
	 * This method will add an entry to the table tblDocumentReceiptLog.
	 * It will first check if the user is ment to receipt the document version.
	 * If not the return value is -3.
	 * Next it will check if the user has been removed from the list of
	 * recipients. In that case -4 will be returned.
	 * If the given receipt has been set by the user before, it cannot
	 * be set again and 0 will be returned. Іf the receipt could be succesfully
	 * added, the receiptview log id will be returned.
	 *
	 * @see SeedDMS_Core_DocumentContent::setApprovalByInd()
	 * @param object $user user doing the receipt
	 * @param object $requestUser user asking for the receipt, this is mostly
	 * @param integer $status the status of the receipt, possible values are
	 *        0=unprocessed (may be used to reset a status)
	 *        1=received,
	 *       -1=rejected,
	 *       -2=user is deleted (use {link
	 *       SeedDMS_Core_DocumentContent::delIndRecipient} instead)
	 * the user currently logged in.
	 * @return integer new receipt log id
	 */
	function setReceiptByInd($user, $requestUser, $status, $comment) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		// Check to see if the user can be removed from the review list.
		$receiptStatus = $user->getReceiptStatus($this->_document->getID(), $this->_version);
		if (is_bool($receiptStatus) && !$receiptStatus) {
			return -1;
		}
		if (count($receiptStatus["indstatus"])==0) {
			// User is not assigned to receipt this document. No action required.
			// Return an error.
			return -3;
		}
		$indstatus = array_pop($receiptStatus["indstatus"]);
		if ($indstatus["status"] == S_LOG_USER_REMOVED) {
			// User has been deleted from recipients
			return -4;
		}
		// Check if the status is really different from the current status
		if ($indstatus["status"] == $status)
			return 0;

		$queryStr = "INSERT INTO `tblDocumentReceiptLog` (`receiptID`, `status`,
			`comment`, `date`, `userID`) ".
			"VALUES ('". $indstatus["receiptID"] ."', '".
			(int) $status ."', ".$db->qstr($comment).", ".$db->getCurrentDatetime().", '".
			$requestUser->getID() ."')";
		$res=$db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return -1;
		else {
			$receiptLogID = $db->getInsertID('tblDocumentReceiptLog', 'receiptLogID');
			return $receiptLogID;
		}
 } /* }}} */

	/**
	 * Add a receipt to the document content
	 *
	 * This method is similar to
	 * {@see SeedDMS_Core_DocumentContent::setReceiptByInd()} but adds a receipt
	 * for a group instead of a user.
	 *
	 * @param object $group group doing the receipt
	 * @param object $requestUser user asking for the receipt, this is mostly
	 * the user currently logged in.
	 * @return integer new receipt log id
	 */
	function setReceiptByGrp($group, $requestUser, $status, $comment) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		// Check to see if the user can be removed from the recipient list.
		$receiptStatus = $group->getReceiptStatus($this->_document->getID(), $this->_version);
		if (is_bool($receiptStatus) && !$receiptStatus) {
			return -1;
		}
		if (count($receiptStatus)==0) {
			// User is not assigned to receipt this document. No action required.
			// Return an error.
			return -3;
		}
		$grpstatus = array_pop($receiptStatus["status"]);
		if ($grpstatus["status"] == S_LOG_USER_REMOVED) {
			// Group has been deleted from recipients
			return -4;
		}

		// Check if the status is really different from the current status
		if ($grpstatus["status"] == $status)
			return 0;

		$queryStr = "INSERT INTO `tblDocumentReceiptLog` (`receiptID`, `status`,
			`comment`, `date`, `userID`) ".
			"VALUES ('". $grpstatus["receiptID"] ."', '".
			(int) $status ."', ".$db->qstr($comment).", ".$db->getCurrentDatetime().", '".
			$requestUser->getID() ."')";
		$res=$db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return -1;
		else {
			$receiptLogID = $db->getInsertID('tblDocumentReceiptLog', 'receiptLogID');
			return $receiptLogID;
		}
 } /* }}} */

	/**
	 * Add a revision to the document content
	 *
	 * This method will add an entry to the table tblDocumentRevisionLog.
	 * It will first check if the user is ment to revision the document version.
	 * If not the return value is -3.
	 * Next it will check if the user has been removed from the list of
	 * recipients. In that case -4 will be returned.
	 * If the given revision has been set by the user before, it cannot
	 * be set again and 0 will be returned. Іf the revision could be succesfully
	 * added, the revision log id will be returned.
	 *
	 * @see SeedDMS_Core_DocumentContent::setApprovalByInd()
	 * @param object $user user doing the revision
	 * @param object $requestUser user asking for the revision, this is mostly
	 * the user currently logged in.
	 * @param integer $status the status of the revision, possible values are
	 *        0=unprocessed (may be used to reset a status)
	 *        1=received,
	 *       -2=user is deleted (use {link
	 *       SeedDMS_Core_DocumentContent::delIndRecipient} instead)
	 *       -3=workflow revision is sleeping
	 * @return integer new revision log id, 0, or a value < 0. 0 means the
	 * status has not changed because the new status is equal the current
	 * status. A value < 0 indicate
	 * an error. -1: internal error, -3: user may not revise this document
	 * -4: the user has been removed from the list of revisors,
	 * -5: the revision has not been started at all.
	 */
	function setRevision($object, $requestUser, $status, $comment) { /* {{{ */
		$dms = $this->_document->getDMS();
		$db = $dms->getDB();

		/* getRevisionStatus() returns an array with either an element
		 * 'indstatus' (user) or 'status' (group) containing the revision log
		 */
		if(get_class($object) == $dms->getClassname('user')) {
			$field = 'indstatus';
		} elseif(get_class($object) == $dms->getClassname('group')) {
			$field = 'status';
		} else {
			return -1;
		}

		// Check to see if the user/group can be removed from the review list.
		$revisionStatus = $object->getRevisionStatus($this->_document->getID(), $this->_version);
		if (is_bool($revisionStatus) && !$revisionStatus) {
			return -1;
		}
		if (!isset($revisionStatus[$field])) {
			// User is not assigned to revision this document. No action required.
			// Return an error.
			return -3;
		}
		$indstatus = array_pop($revisionStatus[$field]);

		/* check if revision workflow has been started already */
		if($indstatus['status'] == S_LOG_SLEEPING && ($status == S_LOG_REJECTED || $status == S_LOG_ACCEPTED))
			return -5;

		if ($indstatus["status"] == -2) {
			// User has been deleted from recipients
			return -4;
		}
		// Check if the status is really different from the current status
		if ($indstatus["status"] == $status)
			return 0;

		$queryStr = "INSERT INTO `tblDocumentRevisionLog` (`revisionID`, `status`,
			`comment`, `date`, `userID`) ".
			"VALUES ('". $indstatus["revisionID"] ."', '".
			(int) $status ."', ".$db->qstr($comment).", ".$db->getCurrentDatetime().", '".
			$requestUser->getID() ."')";
		$res=$db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return -1;
		else {
			$revisionLogID = $db->getInsertID('tblDocumentRevisionLog', 'revisionLogID');
			return $revisionLogID;
		}
 } /* }}} */

	function setRevisionByInd($user, $requestUser, $status, $comment) { /* {{{ */
		return self::setRevision($user, $requestUser, $status, $comment);
	} /* }}} */

	function setRevisionByGrp($group, $requestUser, $status, $comment) { /* {{{ */
		return self::setRevision($group, $requestUser, $status, $comment);
	} /* }}} */

	function delIndReviewer($user, $requestUser, $msg='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$user->isType('user'))
			return -1;

		// Check to see if the user can be removed from the review list.
		$reviewStatus = $user->getReviewStatus($this->_document->getID(), $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return false;
		}
		if (count($reviewStatus["indstatus"])==0) {
			// User is not assigned to review this document. No action required.
			// Return an error.
			return -2;
		}
		$indstatus = array_pop($reviewStatus["indstatus"]);
		if ($indstatus["status"]!=0) {
			// User has already submitted a review or has already been deleted;
			// return an error.
			return -3;
		}

		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $indstatus["reviewID"] ."', '".S_LOG_USER_REMOVED."', ".$db->qstr($msg).", ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return false;
		}

		return 0;
	} /* }}} */

	function delGrpReviewer($group, $requestUser, $msg='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$group->isType('group'))
			return -1;

		$groupID = $group->getID();

		// Check to see if the user can be removed from the review list.
		$reviewStatus = $group->getReviewStatus($this->_document->getID(), $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return false;
		}
		if (count($reviewStatus)==0) {
			// User is not assigned to review this document. No action required.
			// Return an error.
			return -2;
		}
		if ($reviewStatus[0]["status"]!=0) {
			// User has already submitted a review or has already been deleted;
			// return an error.
			return -3;
		}

		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $reviewStatus[0]["reviewID"] ."', '".S_LOG_USER_REMOVED."', ".$db->qstr($msg).", ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return false;
		}

		return 0;
	} /* }}} */

	function delIndApprover($user, $requestUser, $msg='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$user->isType('user'))
			return -1;

		$userID = $user->getID();

		// Check if the user is on the approval list at all.
		$approvalStatus = $user->getApprovalStatus($this->_document->getID(), $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return false;
		}
		if (count($approvalStatus["indstatus"])==0) {
			// User is not assigned to approve this document. No action required.
			// Return an error.
			return -2;
		}
		$indstatus = array_pop($approvalStatus["indstatus"]);
		if ($indstatus["status"]!=0) {
			// User has already submitted an approval or has already been deleted;
			// return an error.
			return -3;
		}

		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $indstatus["approveID"] ."', '".S_LOG_USER_REMOVED."', ".$db->qstr($msg).", ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return false;
		}

		return 0;
	} /* }}} */

	function delGrpApprover($group, $requestUser, $msg='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$group->isType('group'))
			return -1;

		$groupID = $group->getID();

		// Check if the group is on the approval list at all.
		$approvalStatus = $group->getApprovalStatus($this->_document->getID(), $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return false;
		}
		if (count($approvalStatus)==0) {
			// User is not assigned to approve this document. No action required.
			// Return an error.
			return -2;
		}
		if ($approvalStatus[0]["status"]!=0) {
			// User has already submitted an approval or has already been deleted;
			// return an error.
			return -3;
		}

		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $approvalStatus[0]["approveID"] ."', '".S_LOG_USER_REMOVED."', ".$db->qstr($msg).", ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return false;
		}

		return 0;
	} /* }}} */

	function delIndRecipient($user, $requestUser, $msg='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$userID = $user->getID();

		// Check to see if the user can be removed from the recipient list.
		$receiptStatus = $user->getReceiptStatus($this->_document->getID(), $this->_version);
		if (is_bool($receiptStatus) && !$receiptStatus) {
			return -1;
		}
		if (count($receiptStatus["indstatus"])==0) {
			// User is not assigned to receipt this document. No action required.
			// Return an error.
			return -2;
		}
		$indstatus = array_pop($receiptStatus["indstatus"]);
		if ($indstatus["status"]!=0) {
			// User has already submitted a receipt or has already been deleted;
			// return an error.
			return -3;
		}

		$queryStr = "INSERT INTO `tblDocumentReceiptLog` (`receiptID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $indstatus["receiptID"] ."', '".S_LOG_USER_REMOVED."', ".$db->qstr($msg).", ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		return 0;
	} /* }}} */

	function delGrpRecipient($group, $requestUser, $msg='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$groupID = $group->getID();

		// Check to see if the user can be removed from the recipient list.
		$receiptStatus = $group->getReceiptStatus($this->_document->getID(), $this->_version);
		if (is_bool($receiptStatus) && !$receiptStatus) {
			return -1;
		}
		if (count($receiptStatus["status"])==0) {
			// User is not assigned to receipt this document. No action required.
			// Return an error.
			return -2;
		}
		$status = array_pop($receiptStatus["status"]);
		if ($status["status"]!=0) {
			// User has already submitted a receipt or has already been deleted;
			// return an error.
			return -3;
		}

		$queryStr = "INSERT INTO `tblDocumentReceiptLog` (`receiptID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $status["receiptID"] ."', '".S_LOG_USER_REMOVED."', ".$db->qstr($msg).", ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		return 0;
	} /* }}} */

	/**
	 * Removes a user from the revision workflow
	 *
	 * This methods behaves differently from one in the other workflows, e.g.
	 * {@see SeedDMS_Core_DocumentContent::delIndReviewer}, because it
	 * also takes into account if the workflow has been started already.
	 * A workflow has been started, when there are entries in the revision log.
	 * If the revision workflow has not been started, then the user will
	 * be silently removed from the list of revisors. If the workflow has
	 * started already, then log entry will indicated the removal of the
	 * user (just as it is done with the other workflows)
	 *
	 * @param object $object user/group which is to be removed
	 * @param object $requestUser user requesting the removal
	 * @return integer 0 if removal was successfull, -1 if an internal error
	 * occured, -3 if the user is not in the list of revisors
	 *
	 */
	function delRevisor($object, $requestUser, $msg='') { /* {{{ */
		$dms = $this->_document->getDMS();
		$db = $dms->getDB();

		/* getRevisionStatus() returns an array with either an element
		 * 'indstatus' (user) or 'status' (group) containing the revision log
		 */
		if(get_class($object) == $dms->getClassname('user')) {
			$field = 'indstatus';
			$type = 0;
		} elseif(get_class($object) == $dms->getClassname('group')) {
			$field = 'status';
			$type = 1;
		} else {
			return -1;
		}

		// Check to see if the user/group can be removed from the revisor list.
		$revisionStatus = $object->getRevisionStatus($this->_document->getID(), $this->_version);
		if (is_bool($revisionStatus) && !$revisionStatus) {
			return -1;
		}

		if (!isset($revisionStatus[$field])) {
			// User is not assigned to revision this document. No action required.
			// Return an error.
			return -2;
		}

		/* If the revision log doesn't contain an entry yet, then remove the
		 * user/group from the list of revisors. The first case should not happen.
		 */
		if(count($revisionStatus[$field]) == 0) {
			$queryStr = "DELETE from `tblDocumentRevisors` WHERE `documentID` = ". $this->_document->getID() ." AND `version` = ".$this->_version." AND `type` = ". $type ." AND `required` = ".$object->getID();
			if (!$db->getResult($queryStr)) {
				return -1;
			}
		} else {
			$indstatus = array_pop($revisionStatus[$field]);
			if ($indstatus["status"] != S_LOG_WAITING && $indstatus["status"] != S_LOG_SLEEPING) {
				// User has already submitted a revision or has already been deleted;
				// return an error.
				if($indstatus["status"] == S_LOG_USER_REMOVED)
					return -3;
				else
					return -4;
			}

			$queryStr = "INSERT INTO `tblDocumentRevisionLog` (`revisionID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $indstatus["revisionID"] ."', '".S_LOG_USER_REMOVED."', ".$db->qstr($msg).", ".$db->getCurrentDatetime().", '". $requestUser->getID() ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return -1;
			}
		}

		return 0;
	} /* }}} */

	function delIndRevisor($user, $requestUser, $msg='') { /* {{{ */
		return self::delRevisor($user, $requestUser, $msg);
	} /* }}} */

	function delGrpRevisor($group, $requestUser, $msg='') { /* {{{ */
		return self::delRevisor($group, $requestUser, $msg);
	} /* }}} */

	/**
	 * Start a new revision workflow
	 *
	 * This function starts a new revision unless there are users/groups
	 * having finished the previous revision. This means the log status
	 * must be S_LOG_SLEEPING or the user/group was removed (S_LOG_USER_REMOVED)
	 *
	 * @param object $requestUser user requesting the revision start
	 * @param string $msg message saved for the initial log message
	 */
	function startRevision($requestUser, $msg='') { /* {{{ */
		$dms = $this->_document->getDMS();
		$db = $dms->getDB();

		$revisionStatus = self::getRevisionStatus();
		if(!$revisionStatus)
			return false;

		/* A new revision may only be started if we are not in the middle of
		 * revision or the user/group has been removed from the workflow
		 */
		/* Taken out, because it happened that a revision wasn't started for each revisor
		 * but just for some.
		 * Checking for each revisor not being sleeping prevented a second start of the
		 * revision for the remaining revisors still sleeping.
		foreach($revisionStatus as $status) {
			if($status['status'] != S_LOG_SLEEPING && $status['status'] != S_LOG_USER_REMOVED)
				return false;
		}
		 */

		/* Make sure all Logs will be set to the right status, in order to
		 * prevent inconsistent states. Actually it could be a feature to
		 * force only some users/groups to revise the document, but for now
		 * this may not be possible.
		 */
		$db->startTransaction();
		$startedrev = false;
		foreach($revisionStatus as $status) {
			if($status['status'] == S_LOG_SLEEPING) {
				$queryStr = "INSERT INTO `tblDocumentRevisionLog` (`revisionID`, `status`,
					`comment`, `date`, `userID`) ".
					"VALUES ('". $status["revisionID"] ."', ".
					S_LOG_WAITING.", ".$db->qstr($msg).", ".$db->getCurrentDatetime().", '".
					$requestUser->getID() ."')";
				$res=$db->getResult($queryStr);
				if (is_bool($res) && !$res) {
					$db->rollbackTransaction();
					return false;
				}
				$startedrev = true;
			}
		}
		/* Set status only if at least one revision was started */
		if($startedrev)
			if(!$this->setStatus(S_IN_REVISION, "Started revision scheduled for ".$this->getRevisionDate(), $requestUser)) {
				$db->rollbackTransaction();
				return false;
			}
		$db->commitTransaction();
		return true;

	} /* }}} */

	/**
	 * Finish a revision workflow
	 *
	 * This function ends a revision This means the log status
	 * is set back S_LOG_SLEEPING and the document status is set as
	 * passed to the method. The function doesn't not check if all
	 * users/groups has made it vote already.
	 *
	 * @param object $requestUser user requesting the revision start
	 * @param integer $docstatus document status
	 * @param string $msg message saved in revision log
	 * @param string $msg message saved in document status log
	 */
	function finishRevision($requestUser, $docstatus, $msg='', $docmsg='') { /* {{{ */
		$dms = $this->_document->getDMS();
		$db = $dms->getDB();

		$revisionStatus = self::getRevisionStatus();
		if(!$revisionStatus)
			return false;

		/* A revision may only be finished if it wasn't finished already
		 */
		foreach($revisionStatus as $status) {
			if($status['status'] == S_LOG_SLEEPING)
				return false;
		}

		/* Make sure all Logs will be set to the right status, in order to
		 * prevent inconsistent states. Actually it could be a feature to
		 * end only some users/groups to revise the document, but for now
		 * this may not be possible.
		 */
		$db->startTransaction();
		/* Does it make sense to put all revisions into sleeping mode? I guess
		 * not. If a document was released or rejected the revision are useless
		 * anyway 
		 */
		foreach($revisionStatus as $status) {
			if($status['status'] != S_LOG_SLEEPING && $status['status'] != S_LOG_USER_REMOVED) {
				$queryStr = "INSERT INTO `tblDocumentRevisionLog` (`revisionID`, `status`,
					`comment`, `date`, `userID`) ".
					"VALUES ('". $status["revisionID"] ."', ".
					S_LOG_SLEEPING.", ".$db->qstr($msg).", ".$db->getCurrentDatetime().", '".
					$requestUser->getID() ."')";
				$res=$db->getResult($queryStr);
				if (is_bool($res) && !$res) {
					$db->rollbackTransaction();
					return false;
				}
			}
		}
		if(!$this->setStatus($docstatus, $docmsg, $requestUser)) {
			$db->rollbackTransaction();
			return false;
		}
		$db->commitTransaction();
		return true;

	} /* }}} */

	/**
	 * Set state of workflow assigned to the document content
	 *
	 * @param object $state
	 */
	function setWorkflowState($state) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if($this->_workflow) {
			$queryStr = "UPDATE `tblWorkflowDocumentContent` set `state`=". $state->getID() ." WHERE `id`=". $this->_workflow['id'];
			if (!$db->getResult($queryStr)) {
				return false;
			}
			$this->_workflowState = $state;
			return true;
		}
		return false;
	} /* }}} */

	/**
	 * Get state of workflow assigned to the document content
	 *
	 * @return object/boolean an object of class SeedDMS_Core_Workflow_State
	 *         or false in case of error, e.g. the version has not a workflow
	 */
	function getWorkflowState() { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$this->_workflow)
			$this->getWorkflow();

		if(!$this->_workflow)
			return false;

		if (!$this->_workflowState) {
			$queryStr=
				"SELECT b.* FROM `tblWorkflowDocumentContent` a LEFT JOIN `tblWorkflowStates` b ON a.`state` = b.id WHERE a.`state` IS NOT NULL AND `a`.`id`=". $this->_workflow['id'];
			$recs = $db->getResultArray($queryStr);
			if (!$recs)
				return false;
			$this->_workflowState = new SeedDMS_Core_Workflow_State($recs[0]['id'], $recs[0]['name'], $recs[0]['maxtime'], $recs[0]['precondfunc'], $recs[0]['documentstatus']);
			$this->_workflowState->setDMS($this->_document->getDMS());
		}
		return $this->_workflowState;
	} /* }}} */

	/**
	 * Assign a workflow to a document content
	 *
	 * @param object $workflow
	 */
	function setWorkflow($workflow, $user) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$this->getWorkflow();
		if($this->_workflow)
			return false;

		if($workflow && is_object($workflow)) {
			$db->startTransaction();
			$initstate = $workflow->getInitState();
			$queryStr = "INSERT INTO `tblWorkflowDocumentContent` (`workflow`, `document`, `version`, `state`, `date`) VALUES (". $workflow->getID(). ", ". $this->_document->getID() .", ". $this->_version .", ".$initstate->getID().", ".$db->getCurrentDatetime().")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$this->getWorkflow();
			if($workflow->getID() != $this->_workflow['workflow']->getID()) {
				$db->rollbackTransaction();
				return false;
			}
			if(!$this->setStatus(S_IN_WORKFLOW, "Added workflow '".$workflow->getName()."'", $user)) {
				$db->rollbackTransaction();
				return false;
			}
			$db->commitTransaction();
			return true;
		}
		return false;
	} /* }}} */

	/**
	 * Get workflow assigned to the document content
	 *
	 * The method returns the last workflow if one was assigned.
	 * If the document version is in a sub workflow, it will have
	 * a never date and therefore will be found first.
	 * The methods also sets $this->_workflow['id'] and
	 * $this->_workflow['parent']. $this->_workflow['id'] is the
	 * id from table tblWorkflowDocumentContent which is used to
	 * get log entries for this workflow.
	 * This method will only get a currently running workflow in
	 * a state. Once a
	 * workflow has ended, the current state of the workflow was
	 * set to null.
	 *
	 * @param bool $full return not just workflow but the data from
	 *        tblWorkflowDocumentContent too
	 * @return object/boolean an object of class SeedDMS_Core_Workflow
	 *         or false in case of error, e.g. the version has not a workflow
	 */
	function getWorkflow($full = false) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if (!$this->_workflow) {
			$queryStr=
				"SELECT a.`id` as `wdcid`, a.`parent`, a.`date`, b.* FROM `tblWorkflowDocumentContent` a LEFT JOIN `tblWorkflows` b ON a.`workflow` = b.`id` WHERE a.`version`='".$this->_version
				."' AND a.`document` = '". $this->_document->getID() ."' "
				." AND a.`state` IS NOT NULL"
				." ORDER BY `date` DESC LIMIT 1";
			$recs = $db->getResultArray($queryStr);
			if (is_bool($recs) && !$recs)
				return false;
			if(!$recs)
				return false;
			$this->_workflow = array('id'=>(int)$recs[0]['wdcid'], 'parent'=>(int)$recs[0]['parent'], 'date'=>$recs[0]['date'], 'workflow'=>new SeedDMS_Core_Workflow($recs[0]['id'], $recs[0]['name'], $this->_document->getDMS()->getWorkflowState($recs[0]['initstate']), $recs[0]["layoutdata"]));
			$this->_workflow['workflow']->setDMS($this->_document->getDMS());
		}
		if($full)
			return $this->_workflow;
		else
			return $this->_workflow['workflow'];
	} /* }}} */

	/**
	 * Rewrites the complete workflow log
	 *
	 * Attention: this function is highly dangerous.
	 * It removes an existing workflow log and rewrites it.
	 * This method was added for importing an xml dump.
	 *
	 * @param array $workflowlog new workflow log with the newest log entry first.
	 * @return boolean true on success, otherwise false
	 */
	function rewriteWorkflowLog($workflowlog) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		/* Get the workflowdocumentcontent */
		$queryStr = "SELECT `id` FROM `tblWorkflowDocumentContent` WHERE `tblWorkflowDocumentContent`.`document` = '". $this->_document->getID() ."' AND `tblWorkflowDocumentContent`.`version` = '". $this->_version ."'";
		$recs = $db->getResultArray($queryStr);
		if (is_bool($recs) && !$recs)
			return false;
		if (!$recs)
			return false;

		$db->startTransaction();

		/* First, remove the old entries */
		$queryStr = "DELETE FROM `tblWorkflowLog` WHERE `tblWorkflowLog`.`workflowdocumentcontent` IN (SELECT `id` FROM `tblWorkflowDocumentContent` WHERE `tblWorkflowDocumentContent`.`document` = '". $this->_document->getID() ."' AND `tblWorkflowDocumentContent`.`version` = '". $this->_version ."')";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* Second, insert the new entries */
		$workflowlog = array_reverse($workflowlog);
		foreach($workflowlog as $log) {
			if(!SeedDMS_Core_DMS::checkDate($log['date'], 'Y-m-d H:i:s')) {
				$db->rollbackTransaction();
				return false;
			}
			$queryStr = "INSERT INTO `tblWorkflowLog` (`workflowdocumentcontent`, `transition`, `comment`, `date`, `userid`) ".
				"VALUES ('".$recs[0]['id'] ."', '".(int) $log['transition']->getID()."', ".$db->qstr($log['comment']) .", ".$db->qstr($log['date']).", ".$log['user']->getID().")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Restart workflow from its initial state
	 *
	 * @return boolean true if workflow could be restarted
	 *         or false in case of error
	 */
	function rewindWorkflow() { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$this->getWorkflow();

		if (!$this->_workflow) {
			return true;
		}
		$workflow = $this->_workflow['workflow'];

		$db->startTransaction();
		$queryStr = "DELETE from `tblWorkflowLog` WHERE `workflowdocumentcontent` = ".$this->_workflow['id'];
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$this->setWorkflowState($workflow->getInitState());
		$db->commitTransaction();

		return true;
	} /* }}} */

	/**
	 * Remove workflow
	 *
	 * Fully removing a workflow including entries in the workflow log is
	 * only allowed if the workflow is still its initial state.
	 * At a later point of time only unlinking the document from the
	 * workflow is allowed. It will keep any log entries and set the state
	 * to NULL.
	 * A workflow is unlinked from a document when enterNextState()
	 * succeeds.
	 *
	 * @param object $user user doing initiating the removal
	 * @param boolean $unlink if true, just unlink the workflow from the
	 *        document but do not remove the workflow log. The $unlink
	 *        flag has been added to detach the workflow from the document
	 *        when it has reached a valid end state
	          (see SeedDMS_Core_DocumentContent::enterNextState())
	 * @return boolean true if workflow could be removed
	 *         or false in case of error
	 */
	function removeWorkflow($user, $unlink=false) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$this->getWorkflow();

		if (!$this->_workflow) {
			return true;
		}

		$workflow = $this->_workflow['workflow'];

		/* A workflow should always be in a state, but in case it isn't, the
		 * at least allow to remove the workflow.
		 */
		$currentstate = $this->getWorkflowState();
		if(!$currentstate || SeedDMS_Core_DMS::checkIfEqual($workflow->getInitState(), $currentstate) || $unlink == true) {
			$db->startTransaction();
			if($unlink) {
				$queryStr=
					"UPDATE `tblWorkflowDocumentContent` SET `state` = NULL WHERE `id`=".$this->_workflow['id'];
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
			} else {
				$queryStr=
					"DELETE FROM `tblWorkflowDocumentContent` WHERE `id`=".$this->_workflow['id'];
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
				/* will be deleted automatically when tblWorkflowDocumentContent is deleted
				$queryStr=
					"DELETE FROM `tblWorkflowLog` WHERE "
					."`version`='".$this->_version."' "
					." AND `document` = '". $this->_document->getID() ."' "
					." AND `workflow` = '". $workflow->getID() ."' ";
				if (!$db->getResult($queryStr)) {
					$db->rollbackTransaction();
					return false;
				}
				 */
			}
			$this->_workflow = null;
			$this->_workflowState = null;
			$this->verifyStatus(false, $user, 'Workflow removed');
			$db->commitTransaction();
		}

		return true;
	} /* }}} */

	/**
	 * Run a sub workflow
	 *
	 * @param object $subworkflow
	 */
	function getParentWorkflow() { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		/* document content must be in a workflow */
		$this->getWorkflow();
		if(!$this->_workflow)
			return false;

		if(!$this->_workflow['parent'])
			return false;

		$queryStr=
			"SELECT * FROM `tblWorkflowDocumentContent` WHERE `parent`=".$this->_workflow['parent'];
		$recs = $db->getResultArray($queryStr);
		if (is_bool($recs) && !$recs)
			return false;
		if(!$recs)
			return false;

		if($recs[0]['workflow'])
			return $this->_document->getDMS()->getWorkflow((int)$recs[0]['workflow']);

		return false;
	} /* }}} */

	/**
	 * Run a sub workflow
	 *
	 * @param object $subworkflow
	 */
	function runSubWorkflow($subworkflow) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		/* document content must be in a workflow */
		$this->getWorkflow();
		if(!$this->_workflow)
			return false;

		/* The current workflow state must match the sub workflows initial state */
		if($subworkflow->getInitState()->getID() != $this->_workflowState->getID())
			return false;

		if($subworkflow) {
			$initstate = $subworkflow->getInitState();
			$queryStr = "INSERT INTO `tblWorkflowDocumentContent` (`parent`, `workflow`, `document`, `version`, `state`, `date`) VALUES (". $this->_workflow['id']. ", ". $subworkflow->getID(). ", ". $this->_document->getID() .", ". $this->_version .", ".$initstate->getID().", ".$db->getCurrentDatetime().")";
			if (!$db->getResult($queryStr)) {
				return false;
			}
			$this->_workflow = array('id'=>$db->getInsertID('tblWorkflowDocumentContent'),  'parent'=>$this->_workflow['id'], 'workflow'=>$subworkflow);
			return true;
		}
		return true;
	} /* }}} */

	/**
	 * Return from sub workflow to parent workflow.
	 * The method will trigger the given transition
	 *
	 * FIXME: Needs much better checking if this is allowed
	 *
	 * @param object $user intiating the return
	 * @param object $transtion to trigger
	 * @param string comment for the transition trigger
	 */
	function returnFromSubWorkflow($user, $transition=null, $comment='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		/* document content must be in a workflow */
		$this->getWorkflow();
		if(!$this->_workflow)
			return false;

		if ($this->_workflow) {
			$db->startTransaction();

			$queryStr = "UPDATE `tblWorkflowDocumentContent` SET `state` = NULL WHERE `id` = '" . $this->_workflow['id']."'";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}

			/* Calling getWorkflow() should find the parent workflow, better check */
			$parent = $this->_workflow['parent'];
			$this->_workflow = null;
			$this->getWorkflow();
			if($this->_workflow['id'] != $parent) {
				$db->rollbackTransaction();
				return false;
			}

			if($transition) {
				if(false === $this->triggerWorkflowTransition($user, $transition, $comment)) {
					$db->rollbackTransaction();
					return false;
				}
			}

			$db->commitTransaction();
		}
		return $this->_workflow['workflow'];
	} /* }}} */

	/**
	 * Check if the user is allowed to trigger the transition
	 * A user is allowed if either the user itself or
	 * a group of which the user is a member of is registered for
	 * triggering a transition. This method does not change the workflow
	 * state of the document content.
	 *
	 * @param object $user
	 * @return boolean true if user may trigger transaction
	 */
	function triggerWorkflowTransitionIsAllowed($user, $transition) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$this->_workflow)
			$this->getWorkflow();

		if(!$this->_workflow)
			return false;

		if(!$this->_workflowState)
			$this->getWorkflowState();

		/* Check if the user has already triggered the transition */
		$queryStr=
			"SELECT * FROM `tblWorkflowLog` WHERE `workflowdocumentcontent` = ".$this->_workflow['id']." AND userid = ".$user->getID();
		$queryStr .= " AND `transition` = ".$transition->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if(count($resArr))
			return false;

		/* Get all transition users allowed to trigger the transition */
		$transusers = $transition->getUsers();
		if($transusers) {
			foreach($transusers as $transuser) {
				if($user->getID() == $transuser->getUser()->getID())
					return true;
			}
		}

		/* Get all transition groups whose members are allowed to trigger
		 * the transition */
		$transgroups = $transition->getGroups();
		if($transgroups) {
			foreach($transgroups as $transgroup) {
				$group = $transgroup->getGroup();
				if($group->isMember($user))
					return true;
			}
		}

		return false;
	} /* }}} */

	/**
	 * Check if all conditions are met to change the workflow state
	 * of a document content (run the transition).
	 * The conditions are met if all explicitly set users and a sufficient
	 * number of users of the groups have acknowledged the content.
	 *
	 * @return boolean true if transaction maybe executed
	 */
	function executeWorkflowTransitionIsAllowed($transition) { /* {{{ */
		if(!$this->_workflow)
			$this->getWorkflow();

		if(!$this->_workflow)
			return false;

		if(!$this->_workflowState)
			$this->getWorkflowState();

		/* Get the Log of transition triggers */
		$entries = $this->getWorkflowLog($transition);
		if(!$entries)
			return false;

		/* Get all transition users allowed to trigger the transition
		 * $allowedusers is a list of all users allowed to trigger the
		 * transition
		 */
		$transusers = $transition->getUsers();
		$allowedusers = array();
		foreach($transusers as $transuser) {
			$a = $transuser->getUser();
			$allowedusers[$a->getID()] = $a;
		}

		/* Get all transition groups whose members are allowed to trigger
		 * the transition */
		$transgroups = $transition->getGroups();
		foreach($entries as $entry) {
			$loguser = $entry->getUser();
			/* Unset each allowed user if it was found in the log */
			if(isset($allowedusers[$loguser->getID()]))
				unset($allowedusers[$loguser->getID()]);
			/* Also check groups if required. Count the group membership of
			 * each user in the log in the array $gg
			 */
			if($transgroups) {
				$loggroups = $loguser->getGroups();
				foreach($loggroups as $loggroup) {
					if(!isset($gg[$loggroup->getID()]))
						$gg[$loggroup->getID()] = 1;
					else
						$gg[$loggroup->getID()]++;
				}
			}
		}
		/* If there are allowed users left, then there some users still
		 * need to trigger the transition.
		 */
		if($allowedusers)
			return false;

		if($transgroups) {
			foreach($transgroups as $transgroup) {
				$group = $transgroup->getGroup();
				$minusers = $transgroup->getNumOfUsers();
				if(!isset($gg[$group->getID()]))
					return false;
				if($gg[$group->getID()] < $minusers)
					return false;
			}
		}
		return true;
	} /* }}} */

	/**
	 * Trigger transition
	 *
	 * This method will be deprecated
	 *
	 * The method will first check if the user is allowed to trigger the
	 * transition. If the user is allowed, an entry in the workflow log
	 * will be added, which is later used to check if the transition
	 * can actually be processed. The method will finally call
	 * executeWorkflowTransitionIsAllowed() which checks all log entries
	 * and does the transitions post function if all users and groups have
	 * triggered the transition. Finally enterNextState() is called which
	 * will try to enter the next state.
	 *
	 * @param object $user
	 * @param object $transition
	 * @param string $comment user comment
	 * @return boolean/object next state if transition could be triggered and
	 *         then next state could be entered,
	 *         true if the transition could just be triggered or
	 *         false in case of an error
	 */
	function triggerWorkflowTransition($user, $transition, $comment='') { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$this->_workflow)
			$this->getWorkflow();

		if(!$this->_workflow)
			return false;

		if(!$this->_workflowState)
			$this->getWorkflowState();

		if(!$this->_workflowState)
			return false;

		/* Check if the user is allowed to trigger the transition.
		 */
		if(!$this->triggerWorkflowTransitionIsAllowed($user, $transition))
			return false;

		$queryStr = "INSERT INTO `tblWorkflowLog` (`workflowdocumentcontent`, `userid`, `transition`, `date`, `comment`) VALUES (".$this->_workflow['id'].", ".(int) $user->getID(). ", ".(int) $transition->getID().", ".$db->getCurrentDatetime().", ".$db->qstr($comment).")";
		if (!$db->getResult($queryStr))
			return false;

		/* Check if this transition is processed. Run the post function in
		 * that case. A transition is processed when all users and groups
		 * have triggered it.
		 */
		if($this->executeWorkflowTransitionIsAllowed($transition)) {
			/* run post function of transition */
//			echo "run post function of transition ".$transition->getID()."<br />";
		}

		/* Go into the next state. This will only succeed if the pre condition
		 * function of that states succeeds.
		 */
		$nextstate = $transition->getNextState();
		if($this->enterNextState($user, $nextstate)) {
			return $nextstate;
		}
		return true;

	} /* }}} */

	/**
	 * Enter next state of workflow if possible
	 *
	 * The method will check if one of the following states in the workflow
	 * can be reached.
	 * It does it by running
	 * the precondition function of that state. The precondition function
	 * gets a list of all transitions leading to the state. It will
	 * determine, whether the transitions has been triggered and if that
	 * is sufficient to enter the next state. If no pre condition function
	 * is set, then 1 of n transtions are enough to enter the next state.
	 *
	 * If moving in the next state is possible and this state has a
	 * corresponding document state, then the document state will be
	 * updated and the workflow will be detached from the document.
	 *
	 * @param object $user
	 * @param object $nextstate
	 * @return boolean true if the state could be reached
	 *         false if not
	 */
	function enterNextState($user, $nextstate) { /* {{{ */

			/* run the pre condition of the next state. If it is not set
			 * the next state will be reached if one of the transitions
			 * leading to the given state can be processed.
			 */
			if($nextstate->getPreCondFunc() == '') {
				$workflow = $this->_workflow['workflow'];
				$transitions = $workflow->getPreviousTransitions($nextstate);
				foreach($transitions as $transition) {
//				echo "transition ".$transition->getID()." led to state ".$nextstate->getName()."<br />";
					if($this->executeWorkflowTransitionIsAllowed($transition)) {
//					echo "stepping into next state<br />";
						$this->setWorkflowState($nextstate);

						/* Check if the new workflow state has a mapping into a
						 * document state. If yes, set the document state will
						 * be updated and the workflow will be removed from the
						 * document.
						 */
						$docstate = $nextstate->getDocumentStatus();
						if($docstate == S_RELEASED || $docstate == S_REJECTED) {
							$this->setStatus($docstate, "Workflow has ended", $user);
							/* Detach the workflow from the document, but keep the
							 * workflow log
							 */
							$this->removeWorkflow($user, true);
							return true ;
						}

						/* make sure the users and groups allowed to trigger the next
						 * transitions are also allowed to read the document
						 */
						$transitions = $workflow->getNextTransitions($nextstate);
						foreach($transitions as $tran) {
//							echo "checking access for users/groups allowed to trigger transition ".$tran->getID()."<br />";
							$transusers = $tran->getUsers();
							foreach($transusers as $transuser) {
								$u = $transuser->getUser();
//								echo $u->getFullName()."<br />";
								if($this->_document->getAccessMode($u) < M_READ) {
									$this->_document->addAccess(M_READ, $u->getID(), 1);
//									echo "granted read access<br />";
								} else {
//									echo "has already access<br />";
								}
							}
							$transgroups = $tran->getGroups();
							foreach($transgroups as $transgroup) {
								$g = $transgroup->getGroup();
//								echo $g->getName()."<br />";
								if ($this->_document->getGroupAccessMode($g) < M_READ) {
									$this->_document->addAccess(M_READ, $g->getID(), 0);
//									echo "granted read access<br />";
								} else {
//									echo "has already access<br />";
								}
							}
						}
						return(true);
					} else {
//						echo "transition not ready for process now<br />";
					}
				}
				return false;
			} else {
			}

	} /* }}} */

	/**
	 * Get the so far logged operations on the document content within the
	 * workflow. If the document content is currently in a workflow and
	 * a transition is passed, then the
	 * log entries will be restricted on the workflow and returned as a one
	 * dimensional list. Without a running workflow the log entries of
	 * all workflows in the past are returned grouped by workflow.
	 * This result is a two dimensional array. The keys of the first
	 * dimension are the ids used in table tblWorkflowDocumentContent.
	 * If only the logs of last workflow run are of interesst, then just
	 * take the last element of the returned array.
	 *
	 * Example: A workflow was started for a document content.
	 * This will add an entry in tblWorkflowDocumentContent whose state is set
	 * to the initial state of the workflow and a new autoinc id, e.g. with id 45
	 * Once any step in the workflow was triggered, the table tblWorkflowLog will
	 * have an entry for workflowdocumentcontent=45.
	 * Retrieving the workflow log as long the document is still in the workflow
	 * will return the log entries for the current workflow. In this particular
	 * case it will be an array with one log entry.
	 * Once the workflow has ended this method will still return the log entries
	 * but in a 2-dimensional array with the first dimension set to 45.
	 *
	 * The same document version can be run through the same or a different
	 * workflow again which will lead to a new entry in
	 * tblWorkflowDocumentContent, e.g. with id 46.  Getting the log entries
	 * while the content is still in the workflow will return only those entries
	 * for the current workflow. Once the workflow has ended, this methods
	 * returns a 2-dimensional array with two elements in the first dimension.
	 * One for key 45 and another one for key 46.
	 *
	 * @return array list of objects
	 */
	function getWorkflowLog($transition = null) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$this->_workflow)
			$this->getWorkflow();

		$queryStr=
			"SELECT `a`.`id`, `a`.`userid`, `a`.`transition`, `a`.`date`, `a`.`comment`, `a`.`workflowdocumentcontent`, `b`.`version`, `b`.`document`, `b`.`workflow` FROM `tblWorkflowLog` `a` LEFT JOIN `tblWorkflowDocumentContent` `b` ON `a`.`workflowdocumentcontent` = `b`.`id` WHERE `b`.`version`='".$this->_version ."' AND `b`.`document` = '". $this->_document->getID() ."'"; // AND `workflow` = ". $this->_workflow->getID();
		if($transition) {
			$queryStr .= " AND `a`.`transition` = ".$transition->getID();
		}
		if($this->_workflow)
			$queryStr .= " AND `a`.`workflowdocumentcontent` = ".$this->_workflow['id'];
		$queryStr .= " ORDER BY `a`.`date`";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$workflowlogs = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$workflow = $this->_document->getDMS()->getWorkflow($resArr[$i]["workflow"]);
			$workflowlog = new SeedDMS_Core_Workflow_Log($resArr[$i]["id"], $this->_document->getDMS()->getDocument($resArr[$i]["document"]), $resArr[$i]["version"], $workflow, $this->_document->getDMS()->getUser($resArr[$i]["userid"]), $workflow->getTransition($resArr[$i]["transition"]), $resArr[$i]["date"], $resArr[$i]["comment"]);
			$workflowlog->setDMS($this);
			if($this->_workflow)
				$workflowlogs[] = $workflowlog;
			else
				$workflowlogs[$resArr[$i]["workflowdocumentcontent"]][] = $workflowlog;
		}

		return $workflowlogs;
	} /* }}} */

	/**
	 * Get the latest workflow log entry for the document content within the
	 * workflow. Even after finishing the workflow (when the document content
	 * does not have a workflow set anymore) this function returns the last
	 * log entry.
	 *
	 * @return object
	 */
	function getLastWorkflowLog() { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

/*
		if(!$this->_workflow)
			$this->getWorkflow();

		if(!$this->_workflow)
			return false;
 */
		$queryStr=
			"SELECT `a`.*, `b`.`workflow`, `b`.`document`, `b`.`version` FROM `tblWorkflowLog` `a` LEFT JOIN `tblWorkflowDocumentContent` `b` ON `a`.`workflowdocumentcontent` = `b`.`id` WHERE `b`.`version`='".$this->_version ."' AND `b`.`document` = '". $this->_document->getID() ."'";
		$queryStr .= " ORDER BY `id` DESC LIMIT 1";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$i = 0;
		$workflow = $this->_document->getDMS()->getWorkflow($resArr[$i]["workflow"]);
		$workflowlog = new SeedDMS_Core_Workflow_Log($resArr[$i]["id"], $this->_document->getDMS()->getDocument($resArr[$i]["document"]), $resArr[$i]["version"], $workflow, $this->_document->getDMS()->getUser($resArr[$i]["userid"]), $workflow->getTransition($resArr[$i]["transition"]), $resArr[$i]["date"], $resArr[$i]["comment"]);
		$workflowlog->setDMS($this);

		return $workflowlog;
	} /* }}} */

	/**
	 * Check if the document content needs an action by a user
	 *
	 * This method will return true if document content is in a transition
	 * which can be triggered by the given user.
	 *
	 * @param SeedDMS_Core_User $user
	 * @return boolean true is action is needed
	 */
	function needsWorkflowAction($user) { /* {{{ */
		$needwkflaction = false;
		if($this->_workflow) {
			$workflow = $this->_workflow['workflow'];
			if (!$this->_workflowState)
				$this->getWorkflowState();
			$workflowstate = $this->_workflowState;
			if($transitions = $workflow->getNextTransitions($workflowstate)) {
				foreach($transitions as $transition) {
					if($this->triggerWorkflowTransitionIsAllowed($user, $transition)) {
						$needwkflaction = true;
					}
				}
			}
		}
		return $needwkflaction;
	} /* }}} */

	/**
	 * Checks the internal data of the document version and repairs it.
	 * Currently, this function only repairs a missing filetype
	 *
	 * @return boolean true on success, otherwise false
	 */
	function repair() { /* {{{ */
		$dms = $this->_document->getDMS();
		$db = $this->_dms->getDB();

		if(SeedDMS_Core_File::file_exists($this->_dms->contentDir.$this->_document->getDir() . $this->_version . $this->_fileType)) {
			if(strlen($this->_fileType) < 2) {
				switch($this->_mimeType) {
				case "application/pdf":
				case "image/png":
				case "image/gif":
				case "image/jpg":
					$expect = substr($this->_mimeType, -3, 3);
					if($this->_fileType != '.'.$expect) {
						$db->startTransaction();
						$queryStr = "UPDATE `tblDocumentContent` SET `fileType`='.".$expect."' WHERE `id` = ". $this->_id;
						$res = $db->getResult($queryStr);
						if ($res) {
							if(!SeedDMS_Core_File::renameFile($this->_dms->contentDir.$this->_document->getDir() . $this->_version . $this->_fileType, $this->_dms->contentDir.$this->_document->getDir() . $this->_version . '.' . $expect)) {
								$db->rollbackTransaction();
							} else {
								$db->commitTransaction();
							}
						} else {
							$db->rollbackTransaction();
						}
					}
					break;
				}
			}
		} elseif(SeedDMS_Core_File::file_exists($this->_document->getDir() . $this->_version . '.')) {
			echo "no file";
		} else {
			echo $this->_dms->contentDir.$this->_document->getDir() . $this->_version . $this->_fileType;
		}
		return true;
	} /* }}} */

} /* }}} */


/**
 * Class to represent a link between two document
 *
 * Document links are to establish a reference from one document to
 * another document. The owner of the document link may not be the same
 * as the owner of one of the documents.
 * Use {@link SeedDMS_Core_Document::addDocumentLink()} to add a reference
 * to another document.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2022 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_DocumentLink { /* {{{ */
	/**
	 * @var integer internal id of document link
	 */
	protected $_id;

	/**
	 * @var SeedDMS_Core_Document reference to document this link belongs to
	 */
	protected $_document;

	/**
	 * @var object reference to target document this link points to
	 */
	protected $_target;

	/**
	 * @var integer id of user who is the owner of this link
	 */
	protected $_userID;

	/**
	 * @var integer 1 if this link is public, or 0 if is only visible to the owner
	 */
	protected $_public;

	/**
	 * SeedDMS_Core_DocumentLink constructor.
	 * @param $id
	 * @param $document
	 * @param $target
	 * @param $userID
	 * @param $public
	 */
	function __construct($id, $document, $target, $userID, $public) {
		$this->_id = $id;
		$this->_document = $document;
		$this->_target = $target;
		$this->_userID = $userID;
		$this->_public = $public ? true : false;
	}

	/**
	 * Check if this object is of type 'documentlink'.
	 *
	 * @param string $type type of object
	 */
	public function isType($type) { /* {{{ */
		return $type == 'documentlink';
	} /* }}} */

	/**
	 * @return int
	 */
	function getID() { return $this->_id; }

	/**
	 * @return SeedDMS_Core_Document
	 */
	function getDocument() {
		return $this->_document;
	}

	/**
	 * @return object
	 */
	function getTarget() {
		return $this->_target;
	}

	/**
	 * @return bool|SeedDMS_Core_User
	 */
	function getUser() {
		if (!isset($this->_user)) {
			$this->_user = $this->_document->getDMS()->getUser($this->_userID);
		}
		return $this->_user;
	}

	/**
	 * @return int
	 */
	function isPublic() { return $this->_public; }

	/**
	 * Returns the access mode similar to a document
	 *
	 * There is no real access mode for document links, so this is just
	 * another way to add more access restrictions than the default restrictions.
	 * It is only called for public document links, not accessed by the owner
	 * or the administrator.
	 *
	 * @param SeedDMS_Core_User $u user
	 * @param $source
	 * @param $target
	 * @return int either M_NONE or M_READ
	 */
	function getAccessMode($u, $source, $target) { /* {{{ */
		$dms = $this->_document->getDMS();

		/* Check if 'onCheckAccessDocumentLink' callback is set */
		if(isset($dms->callbacks['onCheckAccessDocumentLink'])) {
			foreach($dms->callbacks['onCheckAccessDocumentLink'] as $callback) {
				if(($ret = call_user_func($callback[0], $callback[1], $this, $u, $source, $target)) > 0) {
					return $ret;
				}
			}
		}

		return M_READ;
	} /* }}} */

} /* }}} */

/**
 * Class to represent a file attached to a document
 *
 * Beside the regular document content arbitrary files can be attached
 * to a document. This is a similar concept as attaching files to emails.
 * The owner of the attached file and the document may not be the same.
 * Use {@link SeedDMS_Core_Document::addDocumentFile()} to attach a file.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2022 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_DocumentFile { /* {{{ */
	/**
	 * @var integer internal id of document file
	 */
	protected $_id;

	/**
	 * @var SeedDMS_Core_Document reference to document this file belongs to
	 */
	protected $_document;

	/**
	 * @var integer id of user who is the owner of this link
	 */
	protected $_userID;

	/**
	 * @var string comment for the attached file
	 */
	protected $_comment;

	/**
	 * @var string date when the file was attached
	 */
	protected $_date;

	/**
	 * @var integer version of document this file is attached to
	 */
	protected $_version;

	/**
	 * @var integer 1 if this link is public, or 0 if is only visible to the owner
	 */
	protected $_public;

	/**
	 * @var string directory where the file is stored. This is the
	 * document id with a proceding '/'.
	 * FIXME: looks like this isn't used anymore. The file path is
	 * constructed by getPath()
	 */
	protected $_dir;

	/**
	 * @var string extension of the original file name with a leading '.'
	 */
	protected $_fileType;

	/**
	 * @var string mime type of the file
	 */
	protected $_mimeType;

	/**
	 * @var string name of the file that was originally uploaded
	 */
	protected $_orgFileName;

	/**
	 * @var string name of the file as given by the user
	 */
	protected $_name;

	/**
	 * SeedDMS_Core_DocumentFile constructor.
	 * @param $id
	 * @param $document
	 * @param $userID
	 * @param $comment
	 * @param $date
	 * @param $dir
	 * @param $fileType
	 * @param $mimeType
	 * @param $orgFileName
	 * @param $name
	 * @param $version
	 * @param $public
	 */
	function __construct($id, $document, $userID, $comment, $date, $dir, $fileType, $mimeType, $orgFileName,$name,$version,$public) {
		$this->_id = $id;
		$this->_document = $document;
		$this->_userID = $userID;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_dir = $dir;
		$this->_fileType = $fileType;
		$this->_mimeType = $mimeType;
		$this->_orgFileName = $orgFileName;
		$this->_name = $name;
		$this->_version = $version;
		$this->_public = $public ? true : false;
	}

	/**
	 * Check if this object is of type 'documentfile'.
	 *
	 * @param string $type type of object
	 */
	public function isType($type) { /* {{{ */
		return $type == 'documentfile';
	} /* }}} */

	/**
	 * @return int
	 */
	function getID() { return $this->_id; }

	/**
	 * @return SeedDMS_Core_Document
	 */
	function getDocument() { return $this->_document; }

	/**
	 * @return int
	 */
	function getUserID() { return $this->_userID; }

	/**
	 * @return string
	 */
	function getComment() { return $this->_comment; }

	/*
	 * Set the comment of the document file
	 *
	 * @param string $newComment string new comment of document
	 */
	function setComment($newComment) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$queryStr = "UPDATE `tblDocumentFiles` SET `comment` = ".$db->qstr($newComment)." WHERE `document` = ".$this->_document->getId()." AND `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getDate() { return $this->_date; }

	/**
	 * Set creation date of the document file
	 *
	 * @param integer $date timestamp of creation date. If false then set it
	 * to the current timestamp
	 * @return boolean true on success
	 */
	function setDate($date=null) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!$date)
			$date = time();
		else {
			if(!is_numeric($date))
				return false;
		}

		$queryStr = "UPDATE `tblDocumentFiles` SET `date` = " . (int) $date . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$this->_date = $date;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getDir() { return $this->_dir; }

	/**
	 * @return string
	 */
	function getFileType() { return $this->_fileType; }

	/**
	 * @return string
	 */
	function getMimeType() { return $this->_mimeType; }

	/**
	 * @return string
	 */
	function getOriginalFileName() { return $this->_orgFileName; }

	/**
	 * @return string
	 */
	function getName() { return $this->_name; }

	/*
	 * Set the name of the document file
	 *
	 * @param $newComment string new name of document
	 */
	function setName($newName) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$queryStr = "UPDATE `tblDocumentFiles` SET `name` = ".$db->qstr($newName)." WHERE `document` = ".$this->_document->getId()." AND `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;

		return true;
	} /* }}} */

	/**
	 * @return bool|SeedDMS_Core_User
	 */
	function getUser() {
		if (!isset($this->_user))
			$this->_user = $this->_document->getDMS()->getUser($this->_userID);
		return $this->_user;
	}

	/**
	 * @return string
	 */
	function getPath() {
		return $this->_document->getDir() . "f" .$this->_id . $this->_fileType;
	}

	/**
	 * @return int
	 */
	function getVersion() { return $this->_version; }

	/*
	 * Set the version of the document file
	 *
	 * @param $newComment string new version of document
	 */
	function setVersion($newVersion) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		if(!is_numeric($newVersion) && $newVersion != '')
			return false;

		$queryStr = "UPDATE `tblDocumentFiles` SET `version` = ".(int) $newVersion." WHERE `document` = ".$this->_document->getId()." AND `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_version = (int) $newVersion;
		return true;
	} /* }}} */

	/**
	 * @return int
	 */
	function isPublic() { return $this->_public; }

	/*
	 * Set the public flag of the document file
	 *
	 * @param $newComment string new comment of document
	 */
	function setPublic($newPublic) { /* {{{ */
		$db = $this->_document->getDMS()->getDB();

		$queryStr = "UPDATE `tblDocumentFiles` SET `public` = ".($newPublic ? 1 : 0)." WHERE `document` = ".$this->_document->getId()." AND `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_public = $newPublic ? true : false;
		return true;
	} /* }}} */

	/**
	 * Returns the access mode similar to a document
	 *
	 * There is no real access mode for document files, so this is just
	 * another way to add more access restrictions than the default restrictions.
	 * It is only called for public document files, not accessed by the owner
	 * or the administrator.
	 *
	 * @param object $u user
	 * @return integer either M_NONE or M_READ
	 */
	function getAccessMode($u) { /* {{{ */
		$dms = $this->_document->getDMS();

		/* Check if 'onCheckAccessDocumentLink' callback is set */
		if(isset($this->_dms->callbacks['onCheckAccessDocumentFile'])) {
			foreach($this->_dms->callbacks['onCheckAccessDocumentFile'] as $callback) {
				if(($ret = call_user_func($callback[0], $callback[1], $this, $u)) > 0) {
					return $ret;
				}
			}
		}

		return M_READ;
	} /* }}} */

} /* }}} */

//
// Perhaps not the cleanest object ever devised, it exists to encapsulate all
// of the data generated during the addition of new content to the database.
// The object stores a copy of the new DocumentContent object, the newly assigned
// reviewers and approvers and the status.
//
/**
 * Class to represent a list of document contents
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2022 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_AddContentResultSet { /* {{{ */

	/**
	 * @var null
	 */
	protected $_indReviewers;

	/**
	 * @var null
	 */
	protected $_grpReviewers;

	/**
	 * @var null
	 */
	protected $_indApprovers;

	/**
	 * @var null
	 */
	protected $_grpApprovers;

	/**
	 * @var
	 */
	protected $_content;

	/**
	 * @var null
	 */
	protected $_status;

	/**
	 * @var SeedDMS_Core_DMS back reference to document management system
	 */
	protected $_dms;

	/**
	 * SeedDMS_Core_AddContentResultSet constructor.
	 * @param $content
	 */
	function __construct($content) { /* {{{ */
		$this->_content = $content;
		$this->_indReviewers = null;
		$this->_grpReviewers = null;
		$this->_indApprovers = null;
		$this->_grpApprovers = null;
		$this->_status = null;
		$this->_dms = null;
	} /* }}} */

	/**
	 * Set dms this object belongs to.
	 *
	 * Each object needs a reference to the dms it belongs to. It will be
	 * set when the object is created.
	 * The dms has a references to the currently logged in user
	 * and the database connection.
	 *
	 * @param SeedDMS_Core_DMS $dms reference to dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/**
	 * @param $reviewer
	 * @param $type
	 * @param $status
	 * @return bool
	 */
	function addReviewer($reviewer, $type, $status) { /* {{{ */
		$dms = $this->_dms;

		if (!is_object($reviewer) || (strcasecmp($type, "i") && strcasecmp($type, "g")) && !is_integer($status)){
			return false;
		}
		if (!strcasecmp($type, "i")) {
			if (strcasecmp(get_class($reviewer), $dms->getClassname("user"))) {
				return false;
			}
			if ($this->_indReviewers == null) {
				$this->_indReviewers = array();
			}
			$this->_indReviewers[$status][] = $reviewer;
		}
		if (!strcasecmp($type, "g")) {
			if (strcasecmp(get_class($reviewer), $dms->getClassname("group"))) {
				return false;
			}
			if ($this->_grpReviewers == null) {
				$this->_grpReviewers = array();
			}
			$this->_grpReviewers[$status][] = $reviewer;
		}
		return true;
	} /* }}} */

	/**
	 * @param $approver
	 * @param $type
	 * @param $status
	 * @return bool
	 */
	function addApprover($approver, $type, $status) { /* {{{ */
		$dms = $this->_dms;

		if (!is_object($approver) || (strcasecmp($type, "i") && strcasecmp($type, "g")) && !is_integer($status)){
			return false;
		}
		if (!strcasecmp($type, "i")) {
			if (strcasecmp(get_class($approver), $dms->getClassname("user"))) {
				return false;
			}
			if ($this->_indApprovers == null) {
				$this->_indApprovers = array();
			}
			$this->_indApprovers[$status][] = $approver;
		}
		if (!strcasecmp($type, "g")) {
			if (strcasecmp(get_class($approver), $dms->getClassname("group"))) {
				return false;
			}
			if ($this->_grpApprovers == null) {
				$this->_grpApprovers = array();
			}
			$this->_grpApprovers[$status][] = $approver;
		}
		return true;
	} /* }}} */

	/**
	 * @param $status
	 * @return bool
	 */
	function setStatus($status) { /* {{{ */
		if (!is_integer($status)) {
			return false;
		}
		if ($status<-3 || $status>3) {
			return false;
		}
		$this->_status = $status;
		return true;
	} /* }}} */

	/**
	 * @return null
	 */
	function getStatus() { /* {{{ */
		return $this->_status;
	} /* }}} */

	/**
	 * @return mixed
	 */
	function getContent() { /* {{{ */
		return $this->_content;
	} /* }}} */

	/**
	 * @param $type
	 * @return array|bool|null
	 */
	function getReviewers($type) { /* {{{ */
		if (strcasecmp($type, "i") && strcasecmp($type, "g")) {
			return false;
		}
		if (!strcasecmp($type, "i")) {
			return ($this->_indReviewers == null ? array() : $this->_indReviewers);
		}
		else {
			return ($this->_grpReviewers == null ? array() : $this->_grpReviewers);
		}
	} /* }}} */

	/**
	 * @param $type
	 * @return array|bool|null
	 */
	function getApprovers($type) { /* {{{ */
		if (strcasecmp($type, "i") && strcasecmp($type, "g")) {
			return false;
		}
		if (!strcasecmp($type, "i")) {
			return ($this->_indApprovers == null ? array() : $this->_indApprovers);
		}
		else {
			return ($this->_grpApprovers == null ? array() : $this->_grpApprovers);
		}
	} /* }}} */
} /* }}} */
