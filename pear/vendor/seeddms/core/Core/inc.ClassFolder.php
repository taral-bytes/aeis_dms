<?php
/**
 * Implementation of a folder in the document management system
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
 * Class to represent a folder in the document management system
 *
 * A folder in SeedDMS is equivalent to a directory in a regular file
 * system. It can contain further subfolders and documents. Each folder
 * has a single parent except for the root folder which has no parent.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Folder extends SeedDMS_Core_Object {
	/**
	 * @var string name of folder
	 */
	protected $_name;

	/**
	 * @var integer id of parent folder
	 */
	protected $_parentID;

	/**
	 * @var string comment of document
	 */
	protected $_comment;

	/**
	 * @var integer id of user who is the owner
	 */
	protected $_ownerID;

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
	 * @var integer position of folder within the parent folder
	 */
	protected $_sequence;

	/**
	 * @var
	 */
	protected $_date;

	/**
	 * @var SeedDMS_Core_Folder cached parent folder
	 */
	protected $_parent;

	/**
	 * @var SeedDMS_Core_User cached owner of folder
	 */
	protected $_owner;

	/**
	 * @var SeedDMS_Core_Folder[] cached array of sub folders
	 */
	protected $_subFolders;

	/**
	 * @var SeedDMS_Core_Document[] cache array of child documents
	 */
	protected $_documents;

	/**
	 * @var SeedDMS_Core_UserAccess[]|SeedDMS_Core_GroupAccess[]
	 */
	protected $_accessList;

	/**
	 * SeedDMS_Core_Folder constructor.
	 * @param $id
	 * @param $name
	 * @param $parentID
	 * @param $comment
	 * @param $date
	 * @param $ownerID
	 * @param $inheritAccess
	 * @param $defaultAccess
	 * @param $sequence
	 */
	function __construct($id, $name, $parentID, $comment, $date, $ownerID, $inheritAccess, $defaultAccess, $sequence) { /* {{{ */
		parent::__construct($id);
		$this->_id = $id;
		$this->_name = $name;
		$this->_parentID = $parentID;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_ownerID = $ownerID;
		$this->_inheritAccess = $inheritAccess;
		$this->_defaultAccess = $defaultAccess;
		$this->_sequence = $sequence;
		$this->_notifyList = array();
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
		$this->_subFolders = null;
		$this->_documents = null;
		$this->_accessList = null;
		$this->_notifyList = null;
	} /* }}} */

	/**
	 * Check if this object is of type 'folder'.
	 *
	 * @param string $type type of object
	 */
	public function isType($type) { /* {{{ */
		return $type == 'folder';
	} /* }}} */

	/**
	 * Return an array of database fields which used for searching
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
		if (in_array(2, $searchin)) {
			$searchFields[] = "`tblFolders`.`name`";
		}
		if (in_array(3, $searchin)) {
			$searchFields[] = "`tblFolders`.`comment`";
		}
		if (in_array(4, $searchin)) {
			$searchFields[] = "`tblFolderAttributes`.`value`";
		}
		if (in_array(5, $searchin)) {
			$searchFields[] = $db->castToText("`tblFolders`.`id`");
		}
		return $searchFields;
	} /* }}} */

	/**
	 * Return a sql statement with all tables used for searching.
	 * This must be a syntactically correct left join of all tables.
	 *
	 * @return string sql expression for left joining tables
	 */
	public static function getSearchTables() { /* {{{ */
		$sql = "`tblFolders` LEFT JOIN `tblFolderAttributes` on `tblFolders`.`id`=`tblFolderAttributes`.`folder`";
		return $sql;
	} /* }}} */

	/**
	 * Return a folder by its database record
	 *
	 * @param array $resArr array of folder data as returned by database
	 * @param SeedDMS_Core_DMS $dms
	 * @return SeedDMS_Core_Folder|bool instance of SeedDMS_Core_Folder if document exists
	 */
	public static function getInstanceByData($resArr, $dms) { /* {{{ */
		$classname = $dms->getClassname('folder');
		/** @var SeedDMS_Core_Folder $folder */
		$folder = new $classname($resArr["id"], $resArr["name"], $resArr["parent"], $resArr["comment"], $resArr["date"], $resArr["owner"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr["sequence"]);
		$folder->setDMS($dms);
		$folder = $folder->applyDecorators();
		return $folder;
	} /* }}} */

	/**
	 * Return a folder by its id
	 *
	 * @param integer $id id of folder
	 * @param SeedDMS_Core_DMS $dms
	 * @return SeedDMS_Core_Folder|bool instance of SeedDMS_Core_Folder if document exists, null
	 * if document does not exist, false in case of error
	 */
	public static function getInstance($id, $dms) { /* {{{ */
		$db = $dms->getDB();

		$queryStr = "SELECT * FROM `tblFolders` WHERE `id` = " . (int) $id;
		if($dms->checkWithinRootDir && ($id != $dms->rootFolderID))
			$queryStr .= " AND `folderList` LIKE '%:".$dms->rootFolderID.":%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		elseif (count($resArr) != 1)
			return null;

		return self::getInstanceByData($resArr[0], $dms);
	} /* }}} */

	/**
	 * Return a folder by its name
	 *
	 * This function retrieves a folder from the database by its name. The
	 * search covers the whole database. If
	 * the parameter $folder is not null, it will search for the name
	 * only within this parent folder. It will not be done recursively.
	 *
	 * @param string $name name of the folder
	 * @param SeedDMS_Core_Folder $folder parent folder
	 * @return SeedDMS_Core_Folder|boolean found folder or false
	 */
	public static function getInstanceByName($name, $folder=null, $dms) { /* {{{ */
		if (!$name) return false;

		$db = $dms->getDB();
		$queryStr = "SELECT * FROM `tblFolders` WHERE `name` = " . $db->qstr($name);
		if($folder)
			$queryStr .= " AND `parent` = ". $folder->getID();
		if($dms->checkWithinRootDir && ($id != $dms->rootFolderID))
			$queryStr .= " AND `folderList` LIKE '%:".$dms->rootFolderID.":%'";
		$queryStr .= " LIMIT 1";
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return null;

		return self::getInstanceByData($resArr[0], $dms);
	} /* }}} */

	/**
	 * Apply decorators
	 *
	 * @return object final object after all decorators has been applied
	 */
	function applyDecorators() { /* {{{ */
		if($decorators = $this->_dms->getDecorators('folder')) {
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
	 * Get the name of the folder.
	 *
	 * @return string name of folder
	 */
	public function getName() { return $this->_name; }

	/**
	 * Set the name of the folder.
	 *
	 * @param string $newName set a new name of the folder
	 * @return bool
	 */
	public function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreSetName' callback is set */
		if(isset($this->_dms->callbacks['onPreSetName'])) {
			foreach($this->_dms->callbacks['onPreSetName'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newName);
				if(is_bool($ret))
					return $ret;
			}
		}

		$queryStr = "UPDATE `tblFolders` SET `name` = " . $db->qstr($newName) . " WHERE `id` = ". $this->_id;
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
	 * @return string
	 */
	public function getComment() { return $this->_comment; }

	/**
	 * @param $newComment
	 * @return bool
	 */
	public function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreSetComment' callback is set */
		if(isset($this->_dms->callbacks['onPreSetComment'])) {
			foreach($this->_dms->callbacks['onPreSetComment'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this, $newComment);
				if(is_bool($ret))
					return $ret;
			}
		}

		$queryStr = "UPDATE `tblFolders` SET `comment` = " . $db->qstr($newComment) . " WHERE `id` = ". $this->_id;
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
	 * Return creation date of folder
	 *
	 * @return integer unix timestamp of creation date
	 */
	public function getDate() { /* {{{ */
		return $this->_date;
	} /* }}} */

	/**
	 * Set creation date of the folder
	 *
	 * @param integer $date timestamp of creation date. If false then set it
	 * to the current timestamp
	 * @return boolean true on success
	 */
	function setDate($date) { /* {{{ */
		$db = $this->_dms->getDB();

		if($date === false)
			$date = time();
		else {
			if(!is_numeric($date))
				return false;
		}

		$queryStr = "UPDATE `tblFolders` SET `date` = " . (int) $date . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$this->_date = $date;
		return true;
	} /* }}} */

	/**
	 * Returns the parent
	 *
	 * @return null|bool|SeedDMS_Core_Folder returns null, if there is no parent folder
	 * and false in case of an error
	 */
	public function getParent() { /* {{{ */
		if ($this->_id == $this->_dms->rootFolderID || empty($this->_parentID)) {
			return null;
		}

		if (!isset($this->_parent)) {
			$this->_parent = $this->_dms->getFolder($this->_parentID);
		}
		return $this->_parent;
	} /* }}} */

	/**
	 * Check if the folder is subfolder
	 *
	 * This method checks if the current folder is in the path of the 
	 * passed subfolder. In that case the current folder is a parent,
	 * grant parent, grant grant parent, etc. of the subfolder or
	 * to say it differently the passed folder is somewhere below the
	 * current folder.
	 *
	 * This is basically the opposite of {@see SeedDMS_Core_Folder::isDescendant()}
	 *
	 * @param SeedDMS_Core_Folder $subfolder folder to be checked if it is
	 * a subfolder on any level of the current folder
	 * @return bool true if passed folder is a subfolder, otherwise false
	 */
	function isSubFolder($subfolder) { /* {{{ */
		$target_path = $subfolder->getPath();
		foreach($target_path as $next_folder) {
			// the target folder contains this instance in the parent path
			if($this->getID() == $next_folder->getID()) return true;
		}
		return false;
	} /* }}} */

	/**
	 * Set a new folder
	 *
	 * This function moves a folder from one parent folder into another parent
	 * folder. It will fail if the root folder is moved.
	 *
	 * @param SeedDMS_Core_Folder $newParent new parent folder
	 * @return boolean true if operation was successful otherwise false
	 */
	public function setParent($newParent) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->_id == $this->_dms->rootFolderID || empty($this->_parentID)) {
			return false;
		}

		/* Check if the new parent is the folder to be moved or even
		 * a subfolder of that folder
		 */
		if($this->isSubFolder($newParent)) {
			return false;
		}

		// Update the folderList of the folder
		$pathPrefix="";
		$path = $newParent->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}
		$queryStr = "UPDATE `tblFolders` SET `parent` = ".$newParent->getID().", `folderList`='".$pathPrefix."' WHERE `id` = ". $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_parentID = $newParent->getID();
		$this->_parent = $newParent;

		// Must also ensure that any documents in this folder tree have their
		// folderLists updated.
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		/* Update path in folderList for all documents */
		$queryStr = "SELECT `tblDocuments`.`id`, `tblDocuments`.`folderList` FROM `tblDocuments` WHERE `folderList` LIKE '%:".$this->_id.":%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		foreach ($resArr as $row) {
			$newPath = preg_replace("/^.*:".$this->_id.":(.*$)/", $pathPrefix."\\1", $row["folderList"]);
			$queryStr="UPDATE `tblDocuments` SET `folderList` = '".$newPath."' WHERE `tblDocuments`.`id` = '".$row["id"]."'";
			/** @noinspection PhpUnusedLocalVariableInspection */
			$res = $db->getResult($queryStr);
		}

		/* Update path in folderList for all folders */
		$queryStr = "SELECT `tblFolders`.`id`, `tblFolders`.`folderList` FROM `tblFolders` WHERE `folderList` LIKE '%:".$this->_id.":%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		foreach ($resArr as $row) {
			$newPath = preg_replace("/^.*:".$this->_id.":(.*$)/", $pathPrefix."\\1", $row["folderList"]);
			$queryStr="UPDATE `tblFolders` SET `folderList` = '".$newPath."' WHERE `tblFolders`.`id` = '".$row["id"]."'";
			/** @noinspection PhpUnusedLocalVariableInspection */
			$res = $db->getResult($queryStr);
		}

		return true;
	} /* }}} */

	/**
	 * Returns the owner
	 *
	 * @return object owner of the folder
	 */
	public function getOwner() { /* {{{ */
		if (!isset($this->_owner))
			$this->_owner = $this->_dms->getUser($this->_ownerID);
		return $this->_owner;
	} /* }}} */

	/**
	 * Set the owner
	 *
	 * @param SeedDMS_Core_User $newOwner of the folder
	 * @return boolean true if successful otherwise false
	 */
	function setOwner($newOwner) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblFolders` set `owner` = " . $newOwner->getID() . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_ownerID = $newOwner->getID();
		$this->_owner = $newOwner;
		return true;
	} /* }}} */

	/**
	 * @return bool|int
	 */
	function getDefaultAccess() { /* {{{ */
		if ($this->inheritsAccess()) {
			/* Access is supposed to be inherited but it could be that there
			 * is no parent because the configured root folder id is somewhere
			 * below the actual root folder.
			 */
			$res = $this->getParent();
			if ($res)
				return $this->_parent->getDefaultAccess();
		}

		return $this->_defaultAccess;
	} /* }}} */

	/**
	 * Set default access mode
	 *
	 * This method sets the default access mode and also removes all notifiers which
	 * will not have read access anymore.
	 *
	 * @param integer $mode access mode
	 * @param boolean $noclean set to true if notifier list shall not be clean up
	 * @return bool
	 */
	function setDefaultAccess($mode, $noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblFolders` set `defaultAccess` = " . (int) $mode . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_defaultAccess = $mode;

		if(!$noclean)
			$this->cleanNotifyList();

		return true;
	} /* }}} */

	function inheritsAccess() { return $this->_inheritAccess; }

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

		$inheritAccess = ($inheritAccess) ? "1" : "0";

		$queryStr = "UPDATE `tblFolders` SET `inheritAccess` = " . (int) $inheritAccess . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_inheritAccess = $inheritAccess;

		if(!$noclean)
			$this->cleanNotifyList();

		return true;
	} /* }}} */

	function getSequence() { return $this->_sequence; }

	function setSequence($seq) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblFolders` SET `sequence` = " . $seq . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_sequence = $seq;
		return true;
	} /* }}} */

	/**
	 * Check if folder has subfolders
	 * This function just checks if a folder has subfolders disregarding
	 * any access rights.
	 *
	 * @return int number of subfolders or false in case of an error
	 */
	function hasSubFolders() { /* {{{ */
		$db = $this->_dms->getDB();
		if (isset($this->_subFolders)) {
			/** @noinspection PhpUndefinedFieldInspection */
			return count($this->_subFolders);
		}
		$queryStr = "SELECT count(*) as c FROM `tblFolders` WHERE `parent` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return (int) $resArr[0]['c'];
	} /* }}} */

	/**
	 * Check if folder has as subfolder with given name
	 *
	 * @param string $name
	 * @return bool true if subfolder exists, false if not or in case
	 * of an error
	 */
	function hasSubFolderByName($name) { /* {{{ */
		$db = $this->_dms->getDB();
		/* Always check the database instead of iterating over $this->_documents, because
		 * it is probably not slower
		 */
		$queryStr = "SELECT count(*) as c FROM `tblFolders` WHERE `parent` = " . $this->_id . " AND `name` = ".$db->qstr($name);
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return ($resArr[0]['c'] > 0);
	} /* }}} */

	/**
	 * Returns a list of subfolders
	 * This function does not check for access rights. Use
	 * {@link SeedDMS_Core_DMS::filterAccess} for checking each folder against
	 * the currently logged in user and the access rights.
	 *
	 * @param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 * @param string $dir direction of sorting (asc or desc)
	 * @param integer $limit limit number of subfolders
	 * @param integer $offset offset in retrieved list of subfolders
	 * @return SeedDMS_Core_Folder[]|bool list of folder objects or false in case of an error
	 */
	function getSubFolders($orderby="", $dir="asc", $limit=0, $offset=0) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_subFolders)) {
			$queryStr = "SELECT * FROM `tblFolders` WHERE `parent` = " . $this->_id;

			if ($orderby && $orderby[0]=="n") $queryStr .= " ORDER BY `name`";
			elseif ($orderby && $orderby[0]=="s") $queryStr .= " ORDER BY `sequence`";
			elseif ($orderby && $orderby[0]=="d") $queryStr .= " ORDER BY `date`";
			if($dir == 'desc')
				$queryStr .= " DESC";
			if(is_int($limit) && $limit > 0) {
				$queryStr .= " LIMIT ".$limit;
				if(is_int($offset) && $offset > 0)
					$queryStr .= " OFFSET ".$offset;
			}

			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$classname = $this->_dms->getClassname('folder');
			$this->_subFolders = array();
			for ($i = 0; $i < count($resArr); $i++)
//				$this->_subFolders[$i] = $this->_dms->getFolder($resArr[$i]["id"]);
				$this->_subFolders[$i] = $classname::getInstanceByData($resArr[$i], $this->_dms);
		}

		return $this->_subFolders;
	} /* }}} */

	/**
	 * Add a new subfolder
	 *
	 * @param string $name name of folder
	 * @param string $comment comment of folder
	 * @param object $owner owner of folder
	 * @param integer $sequence position of folder in list of sub folders.
	 * @param array $attributes list of document attributes. The element key
	 *        must be the id of the attribute definition.
	 * @return bool|SeedDMS_Core_Folder
	 *         an error.
	 */
	function addSubFolder($name, $comment, $owner, $sequence, $attributes=array()) { /* {{{ */
		$db = $this->_dms->getDB();

		// Set the folderList of the folder
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$db->startTransaction();

		//inheritAccess = true, defaultAccess = M_READ
		$queryStr = "INSERT INTO `tblFolders` (`name`, `parent`, `folderList`, `comment`, `date`, `owner`, `inheritAccess`, `defaultAccess`, `sequence`) ".
					"VALUES (".$db->qstr($name).", ".$this->_id.", ".$db->qstr($pathPrefix).", ".$db->qstr($comment).", ".$db->getCurrentTimestamp().", ".$owner->getID().", 1, ".M_READ.", ". $sequence.")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$newFolder = $this->_dms->getFolder($db->getInsertID('tblFolders'));
		unset($this->_subFolders);

		if($attributes) {
			foreach($attributes as $attrdefid=>$attribute) {
				if($attribute)
					if($attrdef = $this->_dms->getAttributeDefinition($attrdefid)) {
						if(!$newFolder->setAttributeValue($attrdef, $attribute)) {
							$db->rollbackTransaction();
							return false;
						}
					} else {
						$db->rollbackTransaction();
						return false;
					}
			}
		}

		$db->commitTransaction();

		/* Check if 'onPostAddSubFolder' callback is set */
		if(isset($this->_dms->callbacks['onPostAddSubFolder'])) {
			foreach($this->_dms->callbacks['onPostAddSubFolder'] as $callback) {
					/** @noinspection PhpStatementHasEmptyBodyInspection */
					if(!call_user_func($callback[0], $callback[1], $newFolder)) {
				}
			}
		}

		return $newFolder;
	} /* }}} */

	/**
	 * Returns an array of all parents, grand parent, etc. up to root folder.
	 * The folder itself is the last element of the array.
	 *
	 * @return array|bool
	 */
	function getPath() { /* {{{ */
		if (!isset($this->_parentID) || ($this->_parentID == "") || ($this->_parentID == 0) || ($this->_id == $this->_dms->rootFolderID)) {
			return array($this);
		}
		else {
			$res = $this->getParent();
			if (!$res) return false;

			$path = $this->_parent->getPath();
			if (!$path) return false;

			array_push($path, $this);
			return $path;
		}
	} /* }}} */

	/**
	 * Returns a file system path
	 *
	 * This path contains by default spaces around the slashes for better readability.
	 * Run str_replace(' / ', '/', $path) on it or pass '/' as $sep to get a valid unix
	 * file system path.
	 *
	 * @param bool $skiproot skip the name of the root folder and start with $sep
	 * @param string $sep separator between path elements
	 * @return string path separated with ' / '
	 */
	function getFolderPathPlain($skiproot = false, $sep = ' / ') { /* {{{ */
		$path="".$sep;
		$folderPath = $this->getPath();
		for ($i = 0; $i < count($folderPath); $i++) {
			if($i > 0 || !$skiproot) {
				$path .= $folderPath[$i]->getName();
				if ($i+1 < count($folderPath))
					$path .= $sep;
			}
		}
		return trim($path);
	} /* }}} */

	/**
	 * Check, if this folder is a subfolder of a given folder
	 *
	 * This is basically the opposite of {@see SeedDMS_Core_Folder::isSubFolder()}
	 *
	 * @param object $folder parent folder
	 * @return boolean true if folder is a subfolder
	 */
	function isDescendant($folder) { /* {{{ */
		/* If the current folder has no parent it cannot be a descendant */
		if(!$this->getParent())
			return false;
		/* Check if the passed folder is the parent of the current folder.
		 * In that case the current folder is a subfolder of the passed folder.
		 */
		if($this->getParent()->getID() == $folder->getID())
			return true;
		/* Recursively go up to the root folder */
		return $this->getParent()->isDescendant($folder);
	} /* }}} */

	/**
	 * Check if folder has documents
	 * This function just checks if a folder has documents diregarding
	 * any access rights.
	 *
	 * @return int number of documents or false in case of an error
	 */
	function hasDocuments() { /* {{{ */
		$db = $this->_dms->getDB();
		/* Do not use the cache because it may not contain all documents if
		 * the former call getDocuments() limited the number of documents
		if (isset($this->_documents)) {
			return count($this->_documents);
		}
		 */
		$queryStr = "SELECT count(*) as c FROM `tblDocuments` WHERE `folder` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return (int) $resArr[0]['c'];
	} /* }}} */

	/**
	 * Check if folder has document with given name
	 *
	 * @param string $name
	 * @return bool true if document exists, false if not or in case
	 * of an error
	 */
	function hasDocumentByName($name) { /* {{{ */
		$db = $this->_dms->getDB();
		/* Always check the database instead of iterating over $this->_documents, because
		 * it is probably not slower
		 */
		$queryStr = "SELECT count(*) as c FROM `tblDocuments` WHERE `folder` = " . $this->_id . " AND `name` = ".$db->qstr($name);
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return ($resArr[0]['c'] > 0);
	} /* }}} */

	/**
	 * Get all documents of the folder
	 * This function does not check for access rights. Use
	 * {@link SeedDMS_Core_DMS::filterAccess} for checking each document against
	 * the currently logged in user and the access rights.
	 *
	 * @param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 * @param string $dir direction of sorting (asc or desc)
	 * @param integer $limit limit number of documents
	 * @param integer $offset offset in retrieved list of documents
	 * @return SeedDMS_Core_Document[]|bool list of documents or false in case of an error
	 */
	function getDocuments($orderby="", $dir="asc", $limit=0, $offset=0) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_documents)) {
			$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lock` FROM `tblDocuments` LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id` = `tblDocumentLocks`.`document` WHERE `folder` = " . $this->_id;
			if ($orderby && $orderby[0]=="n") $queryStr .= " ORDER BY `name`";
			elseif($orderby && $orderby[0]=="s") $queryStr .= " ORDER BY `sequence`";
			elseif($orderby && $orderby[0]=="d") $queryStr .= " ORDER BY `date`";
			if($dir == 'desc')
				$queryStr .= " DESC";
			if(is_int($limit) && $limit > 0) {
				$queryStr .= " LIMIT ".$limit;
				if(is_int($offset) && $offset > 0)
					$queryStr .= " OFFSET ".$offset;
			}

			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_documents = array();
			$classname = $this->_dms->getClassname('document');
			foreach ($resArr as $row) {
					$row['lock'] = !$row['lock'] ? -1 : $row['lock'];
//				array_push($this->_documents, $this->_dms->getDocument($row["id"]));
				array_push($this->_documents, $classname::getInstanceByData($row, $this->_dms));
			}
		}
		return $this->_documents;
	} /* }}} */

	/**
	 * Count all documents and subfolders of the folder
	 *
	 * This function also counts documents and folders of subfolders, so
	 * basically it works like recursively counting children.
	 *
	 * This function checks for access rights up the given limit. If more
	 * documents or folders are found, the returned value will be the number
	 * of objects available and the precise flag in the return array will be
	 * set to false. This number should not be revelead to the
	 * user, because it allows to gain information about the existens of
	 * objects without access right.
	 * Setting the parameter $limit to 0 will turn off access right checking
	 * which is reasonable if the $user is an administrator.
	 *
	 * @param SeedDMS_Core_User $user
	 * @param integer $limit maximum number of folders and documents that will
	 *        be precisly counted by taken the access rights into account
	 * @return array|bool with four elements 'document_count', 'folder_count'
	 *        'document_precise', 'folder_precise' holding
	 * the counted number and a flag if the number is precise.
	 * @internal param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 */
	function countChildren($user, $limit=10000) { /* {{{ */
		$db = $this->_dms->getDB();

		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$queryStr = "SELECT id FROM `tblFolders` WHERE `folderList` like '".$pathPrefix. "%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$result = array();

		$folders = array();
		$folderids = array($this->_id);
		$cfolders = count($resArr);
		if($cfolders < $limit) {
			foreach ($resArr as $row) {
				$folder = $this->_dms->getFolder($row["id"]);
				if ($folder->getAccessMode($user) >= M_READ) {
					array_push($folders, $folder);
					array_push($folderids, $row['id']);
				}
			}
			$result['folder_count'] = count($folders);
			$result['folder_precise'] = true;
		} else {
			foreach ($resArr as $row) {
				array_push($folderids, $row['id']);
			}
			$result['folder_count'] = $cfolders;
			$result['folder_precise'] = false;
		}

		$documents = array();
		if($folderids) {
			$queryStr = "SELECT id FROM `tblDocuments` WHERE `folder` in (".implode(',', $folderids). ")";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$cdocs = count($resArr);
			if($cdocs < $limit) {
				foreach ($resArr as $row) {
					$document = $this->_dms->getDocument($row["id"]);
					if ($document->getAccessMode($user) >= M_READ)
						array_push($documents, $document);
				}
				$result['document_count'] = count($documents);
				$result['document_precise'] = true;
			} else {
				$result['document_count'] = $cdocs;
				$result['document_precise'] = false;
			}
		}

		return $result;
	} /* }}} */

	// $comment will be used for both document and version leaving empty the version_comment 
	/**
	 * Add a new document to the folder
	 * This function will add a new document and its content from a given file.
	 * It does not check for access rights on the folder. The new documents
	 * default access right is read only and the access right is inherited.
	 *
	 * @param string $name name of new document
	 * @param string $comment comment of new document
	 * @param integer $expires expiration date as a unix timestamp or 0 for no
	 *        expiration date
	 * @param object $owner owner of the new document
	 * @param SeedDMS_Core_User $keywords keywords of new document
	 * @param SeedDMS_Core_DocumentCategory[] $categories list of category objects
	 * @param string $tmpFile the path of the file containing the content
	 * @param string $orgFileName the original file name
	 * @param string $fileType usually the extension of the filename
	 * @param string $mimeType mime type of the content
	 * @param float $sequence position of new document within the folder
	 * @param array $reviewers list of users who must review this document
	 * @param array $approvers list of users who must approve this document
	 * @param int|string $reqversion version number of the content
	 * @param string $version_comment comment of the content. If left empty
	 *        the $comment will be used.
	 * @param array $attributes list of document attributes. The element key
	 *        must be the id of the attribute definition.
	 * @param array $version_attributes list of document version attributes.
	 *        The element key must be the id of the attribute definition.
	 * @param SeedDMS_Core_Workflow $workflow
	 * @param integer $initstate initial document state (only S_RELEASED and
	 *        S_DRAFT are allowed)
	 * @return array|bool false in case of error, otherwise an array
	 *        containing two elements. The first one is the new document, the
	 * second one is the result set returned when inserting the content.
	 */
	function addDocument($name, $comment, $expires, $owner, $keywords, $categories, $tmpFile, $orgFileName, $fileType, $mimeType, $sequence, $reviewers=array(), $approvers=array(),$reqversion=0,$version_comment="", $attributes=array(), $version_attributes=array(), $workflow=null, $initstate=S_RELEASED) { /* {{{ */
		$db = $this->_dms->getDB();

		$expires = (!$expires) ? 0 : $expires;

		// Must also ensure that the document has a valid folderList.
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$db->startTransaction();

		$queryStr = "INSERT INTO `tblDocuments` (`name`, `comment`, `date`, `expires`, `owner`, `folder`, `folderList`, `inheritAccess`, `defaultAccess`, `locked`, `keywords`, `sequence`) VALUES ".
					"(".$db->qstr($name).", ".$db->qstr($comment).", ".$db->getCurrentTimestamp().", ".(int) $expires.", ".$owner->getID().", ".$this->_id.",".$db->qstr($pathPrefix).", 1, ".M_READ.", -1, ".$db->qstr($keywords).", " . $sequence . ")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$document = $this->_dms->getDocument($db->getInsertID('tblDocuments'));

		$curuser = $this->_dms->getLoggedInUser();
		$res = $document->addContent($version_comment, $curuser ? $curuser : $owner, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers, $approvers, $reqversion, $version_attributes, $workflow, $initstate);

		if (is_bool($res) && !$res) {
			$db->rollbackTransaction();
			return false;
		}

		if($categories) {
			if(!$document->setCategories($categories)) {
				$document->remove();
				$db->rollbackTransaction();
				return false;
			}
		}

		if($attributes) {
			foreach($attributes as $attrdefid=>$attribute) {
				/* $attribute can be a string or an array */
				if($attribute) {
					if($attrdef = $this->_dms->getAttributeDefinition($attrdefid)) {
						if(!$document->setAttributeValue($attrdef, $attribute)) {
							$document->remove();
							$db->rollbackTransaction();
							return false;
						}
					} else {
						$document->remove();
						$db->rollbackTransaction();
						return false;
					}
				}
			}
		}

		$db->commitTransaction();

		/* Check if 'onPostAddDocument' callback is set */
		if(isset($this->_dms->callbacks['onPostAddDocument'])) {
			foreach($this->_dms->callbacks['onPostAddDocument'] as $callback) {
					/** @noinspection PhpStatementHasEmptyBodyInspection */
					if(!call_user_func($callback[0], $callback[1], $document)) {
				}
			}
		}

		return array($document, $res);
	} /* }}} */

	/**
	 * Remove a single folder
	 *
	 * Removes just a single folder, but not its subfolders or documents
	 * This function will fail if the folder has subfolders or documents
	 * because of referencial integrity errors.
	 *
	 * @return boolean true on success, false in case of an error
	 */
	protected function removeFromDatabase() { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreRemoveFolder' callback is set */
		if(isset($this->_dms->callbacks['onPreRemoveFromDatabaseFolder'])) {
			foreach($this->_dms->callbacks['onPreRemoveFromDatabaseFolder'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this);
				if(is_bool($ret))
					return $ret;
			}
		}

		$db->startTransaction();
		// unset homefolder as it will no longer exist
		$queryStr = "UPDATE `tblUsers` SET `homefolder`=NULL WHERE `homefolder` =  " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Remove database entries
		$queryStr = "DELETE FROM `tblFolders` WHERE `id` =  " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblFolderAttributes` WHERE `folder` =  " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblACLs` WHERE `target` = ". $this->_id. " AND `targetType` = " . T_FOLDER;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblNotify` WHERE `target` = ". $this->_id. " AND `targetType` = " . T_FOLDER;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$db->commitTransaction();

		/* Check if 'onPostRemoveFolder' callback is set */
		if(isset($this->_dms->callbacks['onPostRemoveFromDatabaseFolder'])) {
			foreach($this->_dms->callbacks['onPostRemoveFromDatabaseFolder'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $this->_id)) {
				}
			}
		}

		return true;
	} /* }}} */

	/**
	 * Remove recursively a folder
	 *
	 * Removes a folder, all its subfolders and documents
	 * This method triggers the callbacks onPreRemoveFolder and onPostRemoveFolder.
	 * If onPreRemoveFolder returns a boolean then this method will return
	 * imediately with the value returned by the callback. Otherwise the
	 * regular removal is executed, which in turn
	 * triggers further onPreRemoveFolder and onPostRemoveFolder callbacks
	 * and its counterparts for documents (onPreRemoveDocument, onPostRemoveDocument).
	 *
	 * @return boolean true on success, false in case of an error
	 */
	function remove() { /* {{{ */
		/** @noinspection PhpUnusedLocalVariableInspection */
		$db = $this->_dms->getDB();

		// Do not delete the root folder.
		if ($this->_id == $this->_dms->rootFolderID || !isset($this->_parentID) || ($this->_parentID == null) || ($this->_parentID == "") || ($this->_parentID == 0)) {
			return false;
		}

		/* Check if 'onPreRemoveFolder' callback is set */
		if(isset($this->_dms->callbacks['onPreRemoveFolder'])) {
			foreach($this->_dms->callbacks['onPreRemoveFolder'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this);
				if(is_bool($ret))
					return $ret;
			}
		}

		//Entfernen der Unterordner und Dateien
		$res = $this->getSubFolders();
		if (is_bool($res) && !$res) return false;
		$res = $this->getDocuments();
		if (is_bool($res) && !$res) return false;

		foreach ($this->_subFolders as $subFolder) {
			$res = $subFolder->remove();
			if (!$res) {
				return false;
			}
		}

		foreach ($this->_documents as $document) {
			$res = $document->remove();
			if (!$res) {
				return false;
			}
		}

		$ret = $this->removeFromDatabase();
		if(!$ret)
			return $ret;

		/* Check if 'onPostRemoveFolder' callback is set */
		if(isset($this->_dms->callbacks['onPostRemoveFolder'])) {
			foreach($this->_dms->callbacks['onPostRemoveFolder'] as $callback) {
				call_user_func($callback[0], $callback[1], $this);
			}
		}

		return $ret;
	} /* }}} */

	/**
	 * Empty recursively a folder
	 *
	 * Removes all subfolders and documents of a folder but not the folder itself
	 * This method will call remove() on all its children.
	 * This method triggers the callbacks onPreEmptyFolder and onPostEmptyFolder.
	 * If onPreEmptyFolder returns a boolean then this method will return
	 * imediately.
	 * Be aware that the recursive calls of remove() will trigger the callbacks
	 * onPreRemoveFolder, onPostRemoveFolder, onPreRemoveDocument and onPostRemoveDocument.
	 *
	 * @return boolean true on success, false in case of an error
	 */
	function emptyFolder() { /* {{{ */
		/** @noinspection PhpUnusedLocalVariableInspection */
		$db = $this->_dms->getDB();

		/* Check if 'onPreEmptyFolder' callback is set */
		if(isset($this->_dms->callbacks['onPreEmptyFolder'])) {
			foreach($this->_dms->callbacks['onPreEmptyFolder'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this);
				if(is_bool($ret))
					return $ret;
			}
		}

		//Entfernen der Unterordner und Dateien
		$res = $this->getSubFolders();
		if (is_bool($res) && !$res) return false;
		$res = $this->getDocuments();
		if (is_bool($res) && !$res) return false;

		foreach ($this->_subFolders as $subFolder) {
			$res = $subFolder->remove();
			if (!$res) {
				return false;
			}
		}

		foreach ($this->_documents as $document) {
			$res = $document->remove();
			if (!$res) {
				return false;
			}
		}

		/* Check if 'onPostEmptyFolder' callback is set */
		if(isset($this->_dms->callbacks['onPostEmptyFolder'])) {
			foreach($this->_dms->callbacks['onPostEmptyFolder'] as $callback) {
				call_user_func($callback[0], $callback[1], $this);
			}
		}

		return true;
	} /* }}} */

	/**
	 * Returns a list of access privileges
	 *
	 * If the folder inherits the access privileges from the parent folder
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
	 * @param integer $mode access mode (defaults to M_ANY)
	 * @param integer $op operation (defaults to O_EQ)
	 * @return bool|SeedDMS_Core_GroupAccess|SeedDMS_Core_UserAccess
	 */
	function getAccessList($mode = M_ANY, $op = O_EQ) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->inheritsAccess()) {
			/* Access is supposed to be inherited but it could be that there
			 * is no parent because the configured root folder id is somewhere
			 * below the actual root folder.
			 */
			$res = $this->getParent();
			if ($res) {
				$pacl = $res->getAccessList($mode, $op);
				return $pacl;
			}
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
			$queryStr = "SELECT * FROM `tblACLs` WHERE `targetType` = ".T_FOLDER.
				" AND `target` = " . $this->_id .	$modeStr . " ORDER BY `targetType`";
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
	 * Delete all entries for this folder from the access control list
	 *
	 * @param boolean $noclean set to true if notifier list shall not be clean up
	 * @return boolean true if operation was successful otherwise false
	 */
	function clearAccessList($noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM `tblACLs` WHERE `targetType` = " . T_FOLDER . " AND `target` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		if(!$noclean)
			$this->cleanNotifyList();

		return true;
	} /* }}} */

	/**
	 * Add access right to folder
	 * This function may change in the future. Instead of passing the a flag
	 * and a user/group id a user or group object will be expected.
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
		$queryStr = "SELECT * FROM `tblACLs` WHERE `targetType` = ".T_FOLDER.
				" AND `target` = " . $this->_id . " AND ". $userOrGroup . " = ".$userOrGroupID;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) || $resArr)
			return false;

		$queryStr = "INSERT INTO `tblACLs` (`target`, `targetType`, ".$userOrGroup.", `mode`) VALUES 
					(".$this->_id.", ".T_FOLDER.", " . (int) $userOrGroupID . ", " .(int) $mode. ")";
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
	 * Change access right of folder
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

		$queryStr = "UPDATE `tblACLs` SET `mode` = " . (int) $newMode . " WHERE `targetType` = ".T_FOLDER." AND `target` = " . $this->_id . " AND " . $userOrGroup . " = " . (int) $userOrGroupID;
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
	 * @param $userOrGroupID
	 * @param $isUser
	 * @return bool
	 */
	function removeAccess($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		$queryStr = "DELETE FROM `tblACLs` WHERE `targetType` = ".T_FOLDER." AND `target` = ".$this->_id." AND ".$userOrGroup." = " . (int) $userOrGroupID;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		$mode = ($isUser ? $this->getAccessMode($this->_dms->getUser($userOrGroupID)) : $this->getGroupAccessMode($this->_dms->getGroup($userOrGroupID)));
		if ($mode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	/**
	 * Get the access mode of a user on the folder
	 *
	 * The access mode is either M_READ, M_READWRITE, M_ALL, or M_NONE.
	 * It is determined
	 * - by the user (admins and owners have always access mode M_ALL)
	 * - by the access list for the user (possibly inherited)
	 * - by the default access mode
	 *
	 * This function returns the access mode for a given user. An administrator
	 * and the owner of the folder has unrestricted access. A guest user has
	 * read only access or no access if access rights are further limited
	 * by access control lists all the default access.
	 * All other users have access rights according
	 * to the access control lists or the default access. This function will
	 * recursively check for access rights of parent folders if access rights
	 * are inherited.
	 *
	 * Before checking the access itself a callback 'onCheckAccessFolder'
	 * is called. If it returns a value > 0, then this will be returned by this
	 * method without any further checks. The optional paramater $context
	 * will be passed as a third parameter to the callback. It contains
	 * the operation for which the access mode is retrieved. It is for example
	 * set to 'removeDocument' if the access mode is used to check for sufficient
	 * permission on deleting a document. This callback could be used to
	 * override any existing access mode in a certain context.
	 *
	 * @param SeedDMS_Core_User $user user for which access shall be checked
	 * @param string $context context in which the access mode is requested
	 * @return integer access mode
	 */
	function getAccessMode($user, $context='') { /* {{{ */
		if(!$user)
			return M_NONE;

		/* Check if 'onCheckAccessFolder' callback is set */
		if(isset($this->_dms->callbacks['onCheckAccessFolder'])) {
			foreach($this->_dms->callbacks['onCheckAccessFolder'] as $callback) {
				if(($ret = call_user_func($callback[0], $callback[1], $this, $user, $context)) > 0) {
					return $ret;
				}
			}
		}

		/* Administrators have unrestricted access */
		if ($user->isAdmin()) return M_ALL;

		/* The owner of the folder has unrestricted access */
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
	 * Get the access mode for a group on the folder
	 * This function returns the access mode for a given group. The algorithmn
	 * applied to get the access mode is the same as describe at
	 * {@link getAccessMode}
	 *
	 * @param SeedDMS_Core_Group $group group for which access shall be checked
	 * @return integer access mode
	 */
	function getGroupAccessMode($group) { /* {{{ */
		$highestPrivileged = M_NONE;
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
				if ($highestPrivileged == M_ALL) /* no need to check further */
					return $highestPrivileged;
			}
		}
		if ($foundInACL)
			return $highestPrivileged;

		/* Take default access */
		return $this->getDefaultAccess();
	} /* }}} */

	/** @noinspection PhpUnusedParameterInspection */
	/**
	 * Get a list of all notification
	 * This function returns all users and groups that have registerd a
	 * notification for the folder
	 *
	 * @param integer $type type of notification (not yet used)
	 * @param bool $incdisabled set to true if disabled user shall be included
	 * @return SeedDMS_Core_User[]|SeedDMS_Core_Group[]|bool array with a the elements 'users' and 'groups' which
	 *        contain a list of users and groups.
	 */
	function getNotifyList($type=0, $incdisabled=false) { /* {{{ */
		if (empty($this->_notifyList)) {
			$db = $this->_dms->getDB();

			$queryStr ="SELECT * FROM `tblNotify` WHERE `targetType` = " . T_FOLDER . " AND `target` = " . $this->_id;
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
				} else {//if ($row["groupID"] != -1)
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
		$ngroups = $this->_notifyList["groups"];
		foreach ($nusers as $u) {
			if ($this->getAccessMode($u) < M_READ) {
				$this->removeNotify($u->getID(), true);
			}
		}

		/** @var SeedDMS_Core_Group[] $ngroups */
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
	 * @param integer $userOrGroupID
	 * @param boolean $isUser true if $userOrGroupID is a user id otherwise false
	 * @return integer error code
	 *    -1: Invalid User/Group ID.
	 *    -2: Target User / Group does not have read access.
	 *    -3: User is already subscribed.
	 *    -4: Database / internal error.
	 *     0: Update successful.
	 */
	function addNotify($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		/* Verify that user / group exists */
		/** @var SeedDMS_Core_User|SeedDMS_Core_Group $obj */
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

		//
		// Verify that user / group has read access to the document.
		//
		if ($isUser) {
			// Users are straightforward to check.
			if ($this->getAccessMode($obj) < M_READ) {
				return -2;
			}
		}
		else {
			// FIXME: Why not check the access list first and if this returns
			// not result, then use the default access?
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
		//
		// Check to see if user/group is already on the list.
		//
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_FOLDER."' ".
			"AND `tblNotify`.".$userOrGroup." = '". (int) $userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)>0) {
			return -3;
		}

		$queryStr = "INSERT INTO `tblNotify` (`target`, `targetType`, " . $userOrGroup . ") VALUES (" . $this->_id . ", " . T_FOLDER . ", " .  (int) $userOrGroupID . ")";
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/**
	 * Removes notify for a user or group to folder
	 * This function does not check if the currently logged in user
	 * is allowed to remove a notification. This must be checked by the calling
	 * application.
	 *
	 * @param integer $userOrGroupID
	 * @param boolean $isUser true if $userOrGroupID is a user id otherwise false
	 * @param int $type type of notification (0 will delete all) Not used yet!
	 * @return int error code
	 *    -1: Invalid User/Group ID.
	 * -3: User is not subscribed.
	 * -4: Database / internal error.
	 * 0: Update successful.
	 */
	function removeNotify($userOrGroupID, $isUser, $type=0) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Verify that user / group exists. */
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
		GLOBAL  $user;
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

		//
		// Check to see if the target is in the database.
		//
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_FOLDER."' ".
			"AND `tblNotify`.".$userOrGroup." = '". (int) $userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)==0) {
			return -3;
		}

		$queryStr = "DELETE FROM `tblNotify` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND " . $userOrGroup . " = " .  (int) $userOrGroupID;
		/* If type is given then delete only those notifications */
		if($type)
			$queryStr .= " AND `type` = ".(int) $type;
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/**
	 * Get List of users and groups which have read access on the document
	 *
	 * This function is deprecated. Use
	 * {@see SeedDMS_Core_Folder::getReadAccessList()} instead.
	 */
	function getApproversList() { /* {{{ */
		return $this->getReadAccessList(0, 0);
	} /* }}} */

	/**
	 * Returns a list of groups and users with read access on the folder
	 * The list will not include any guest users,
	 * administrators and the owner of the folder unless $listadmin resp.
	 * $listowner is set to true.
	 *
	 * @param boolean $listadmin if set to true any admin will be listed too
	 * @param boolean $listowner if set to true the owner will be listed too
	 * @param boolean $listguest if set to true any guest will be listed too
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
				// having read access to the folder.
				$tmpList = $this->getAccessList(M_READ, O_GTEQ);
			}
			else {
				// Get the list of all users and groups that DO NOT have read access
				// to the folder.
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
			// that have read access to this folder, either directly through an
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
				}
				$queryStr .=
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".SeedDMS_Core_User::role_admin.") ".
					"UNION ".
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." ".
					(strlen($userIDs) == 0 ? "" : " AND (`tblUsers`.`id` NOT IN (". $userIDs ."))").
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

			// Assemble the list of groups that have read access to the folder.
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

		$queryStr = "SELECT `folderList` FROM `tblFolders` where `id` = ".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		return $resArr[0]['folderList'];
	} /* }}} */

	/**
	 * Checks the internal data of the folder and repairs it.
	 * Currently, this function only repairs an incorrect folderList
	 *
	 * @return boolean true on success, otherwise false
	 */
	function repair() { /* {{{ */
		$db = $this->_dms->getDB();

		$curfolderlist = $this->getFolderList();

		// calculate the folderList of the folder
		$parent = $this->getParent();
		$pathPrefix="";
		$path = $parent->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}
		if($curfolderlist != $pathPrefix) {
			$queryStr = "UPDATE `tblFolders` SET `folderList`='".$pathPrefix."' WHERE `id` = ". $this->_id;
			$res = $db->getResult($queryStr);
			if (!$res)
				return false;
		}
		return true;
	} /* }}} */

	/**
	 * Get the min and max sequence value for documents
	 *
	 * @return bool|array array with keys 'min' and 'max', false in case of an error
	 */
	function getDocumentsMinMax() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT min(`sequence`) AS `min`, max(`sequence`) AS `max` FROM `tblDocuments` WHERE `folder` = " . (int) $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		return $resArr[0];
	} /* }}} */

	/**
	 * Get the min and max sequence value for folders
	 *
	 * @return bool|array array with keys 'min' and 'max', false in case of an error
	 */
	function getFoldersMinMax() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT min(`sequence`) AS `min`, max(`sequence`) AS `max` FROM `tblFolders` WHERE `parent` = " . (int) $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		return $resArr[0];
	} /* }}} */

	/**
	 * Reorder documents of folder
	 *
	 * Fix the sequence numbers of all documents in the folder, by assigning new
	 * numbers starting from 1 incrementing by 1. This can be necessary if sequence
	 * numbers are not unique which makes manual reordering for documents with
	 * identical sequence numbers impossible.
	 *
	 * @return bool false in case of an error, otherwise true
	 */
	function reorderDocuments() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `id` FROM `tblDocuments` WHERE `folder` = " . (int) $this->_id . " ORDER BY `sequence`";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$db->startTransaction();
		$no = 1.0;
		foreach($resArr as $doc) {
			$queryStr = "UPDATE `tblDocuments` SET `sequence` = " . $no . " WHERE `id` = ". $doc['id'];
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
			$no += 1.0;
		}
		$db->commitTransaction();

		return true;
	} /* }}} */


}

?>
