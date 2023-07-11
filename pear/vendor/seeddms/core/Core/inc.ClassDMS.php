<?php
/**
 * Implementation of the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include some files
 */
require_once("inc.AccessUtils.php");
require_once("inc.FileUtils.php");
require_once("inc.ClassAccess.php");
require_once("inc.ClassObject.php");
require_once("inc.ClassFolder.php");
require_once("inc.ClassDocument.php");
require_once("inc.ClassGroup.php");
require_once("inc.ClassUser.php");
require_once("inc.ClassKeywords.php");
require_once("inc.ClassNotification.php");
require_once("inc.ClassAttribute.php");

/**
 * Class to represent the complete document management system.
 * This class is needed to do most of the dms operations. It needs
 * an instance of {@link SeedDMS_Core_DatabaseAccess} to access the
 * underlying database. Many methods are factory functions which create
 * objects representing the entities in the dms, like folders, documents,
 * users, or groups.
 *
 * Each dms has its own database for meta data and a data store for document
 * content. Both must be specified when creating a new instance of this class.
 * All folders and documents are organized in a hierachy like
 * a regular file system starting with a {@link $rootFolderID}
 *
 * This class does not enforce any access rights on documents and folders
 * by design. It is up to the calling application to use the methods
 * {@link SeedDMS_Core_Folder::getAccessMode()} and
 * {@link SeedDMS_Core_Document::getAccessMode()} and interpret them as desired.
 * Though, there are two convenient functions to filter a list of
 * documents/folders for which users have access rights for. See
 * {@link filterAccess()}
 * and {@link filterUsersByAccess()}
 *
 * Though, this class has a method to set the currently logged in user
 * ({@link setUser}), it does not have to be called, because
 * there is currently no class within the SeedDMS core which needs the logged
 * in user. {@link SeedDMS_Core_DMS} itself does not do any user authentication.
 * It is up to the application using this class.
 *
 * <code>
 * <?php
 * include("inc/inc.ClassDMS.php");
 * $db = new SeedDMS_Core_DatabaseAccess($type, $hostname, $user, $passwd, $name);
 * $db->connect() or die ("Could not connect to db-server");
 * $dms = new SeedDMS_Core_DMS($db, $contentDir);
 * $dms->setRootFolderID(1);
 * ...
 * ?>
 * </code>
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_DMS {
	/**
	 * @var SeedDMS_Core_DatabaseAccess $db reference to database object. This must be an instance
	 *      of {@link SeedDMS_Core_DatabaseAccess}.
	 * @access protected
	 */
	protected $db;

	/**
	 * @var array $classnames list of classnames for objects being instanciate
	 *      by the dms
	 * @access protected
	 */
	protected $classnames;

	/**
	 * @var array $decorators list of decorators for objects being instanciate
	 *      by the dms
	 * @access protected
	 */
	protected $decorators;

	/**
	 * @var SeedDMS_Core_User $user reference to currently logged in user. This must be
	 *      an instance of {@link SeedDMS_Core_User}. This variable is currently not
	 *      used. It is set by {@link setUser}.
	 * @access private
	 */
	private $user;

	/**
	 * @var string $contentDir location in the file system where all the
	 *      document data is located. This should be an absolute path.
	 * @access public
	 */
	public $contentDir;

	/**
	 * @var integer $rootFolderID ID of root folder
	 * @access public
	 */
	public $rootFolderID;

	/**
	 * @var integer $maxDirID maximum number of documents per folder on the
	 *      filesystem. If this variable is set to a value != 0, the content
	 *      directory will have a two level hierarchy for document storage.
	 * @access public
	 */
	public $maxDirID;

	/**
	 * @var boolean $forceRename use renameFile() instead of copyFile() when
	 *      copying the document content into the data store. The default is
	 *      to copy the file. This parameter only affects the methods
	 *      SeedDMS_Core_Document::addDocument() and
	 *      SeedDMS_Core_Document::addDocumentFile(). Setting this to true
	 *      may save resources especially for large files.
	 * @access public
	 */
	public $forceRename;

	/**
	 * @var array $noReadForStatus list of status without read right
	 *      online. DO NOT USE ANYMORE. SeedDMS_Core_DocumentContent::getAccessMode()
	 *      was the only method using it, but it now takes the noReadForStatus info
	 *      from the user's role
	 * @access public
	 */
	public $noReadForStatus;

	/**
	 * @var boolean $checkWithinRootDir check if folder/document being accessed
	 *      is within the rootdir
	 * @access public
	 */
	public $checkWithinRootDir;

	/**
	 * @var string $version version of pear package
	 * @access public
	 */
	public $version;

	/**
	 * @var boolean $usecache true if internal cache shall be used
	 * @access public
	 */
	public $usecache;

	/**
	 * @var array $cache cache for various objects
	 * @access public
	 */
	protected $cache;

	/**
	 * @var array $callbacks list of methods called when certain operations,
	 * like removing a document, are executed. Set a callback with
	 * {@link SeedDMS_Core_DMS::setCallback()}.
	 * The key of the array is the internal callback function name. Each
	 * array element is an array with two elements: the function name
	 * and the parameter passed to the function.
	 *
	 * Currently implemented callbacks are:
	 *
	 * onPreRemoveDocument($user_param, $document);
	 *   called before deleting a document. If this function returns false
	 *   the document will not be deleted.
	 *
	 * onPostRemoveDocument($user_param, $document_id);
	 *   called after the successful deletion of a document.
	 *
	 * @access public
	 */
	public $callbacks;

	/**
	 * @var string last error message. This can be set by hooks to pass an
	 * error message from the hook to the application which has called the
	 * method containing the hook. For example SeedDMS_Core_Document::remove()
	 * calls the hook 'onPreRemoveDocument'. The hook function can set $dms->lasterror
	 * which can than be read when SeedDMS_Core_Document::remove() fails.
	 * This variable could be set in any SeedDMS_Core class, but is currently
	 * only set by hooks.
	 * @access public
	 */
	public $lasterror;

	/**
	 * @var SeedDMS_Core_DMS
	 */
//	public $_dms;


	/**
	 * Checks if two objects are equal by comparing their IDs
	 *
	 * The regular php check done by '==' compares all attributes of
	 * two objects, which is often not required. This method will first check
	 * if the objects are instances of the same class and than if they
	 * have the same id.
	 *
	 * @param object $object1 first object to be compared
	 * @param object $object2 second object to be compared
	 * @return boolean true if objects are equal, otherwise false
	 */
	static function checkIfEqual($object1, $object2) { /* {{{ */
		if(get_class($object1) != get_class($object2))
			return false;
		if($object1->getID() != $object2->getID())
			return false;
		return true;
	} /* }}} */

	/**
	 * Checks if a list of objects contains a single object by comparing their IDs
	 *
	 * This function is only applicable on list containing objects which have
	 * a method getID() because it is used to check if two objects are equal.
	 * The regular php check on objects done by '==' compares all attributes of
	 * two objects, which isn't required. The method will first check
	 * if the objects are instances of the same class.
	 *
	 * The result of the function can be 0 which happens if the first element
	 * of an indexed array matches.
	 *
	 * @param object $object object to look for (needle)
	 * @param array $list list of objects (haystack)
	 * @return boolean/integer index in array if object was found, otherwise false
	 */
	static function inList($object, $list) { /* {{{ */
		foreach($list as $i=>$item) {
			if(get_class($item) == get_class($object) && $item->getID() == $object->getID())
				return $i;
		}
		return false;
	} /* }}} */

	/**
	 * Checks if date conforms to a given format
	 *
	 * @param string $date date to be checked
	 * @param string $format format of date. Will default to 'Y-m-d H:i:s' if
	 * format is not given.
	 * @return boolean true if date is in propper format, otherwise false
	 */
	static function checkDate($date, $format='Y-m-d H:i:s') { /* {{{ */
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	} /* }}} */

	/**
	 * Filter out objects which are not accessible in a given mode by a user.
	 *
	 * The list of objects to be checked can be of any class, but has to have
	 * a method getAccessMode($user) which checks if the given user has at
	 * least the access right on the object as passed in $minMode.
	 * Hence, passing a group instead of user is possible.
	 *
	 * This function can be used for documents and folders and calls
	 * {@link SeedDMS_Core_Folder::getAccessMode()} or
	 * {@link SeedDMS_Core_Document::getAccessMode()}. A document is also
	 * filtered out if it has no latest content, which can happen if access
	 * on documents in a certain state has been restricted.
	 *
	 * @param array $objArr list of objects (either documents or folders)
	 * @param object $user user for which access is checked
	 * @param integer $minMode minimum access mode required (M_ANY, M_NONE,
	 *        M_READ, M_READWRITE, M_ALL)
	 * @return array filtered list of objects
	 */
	static function filterAccess($objArr, $user, $minMode) { /* {{{ */
		if (!is_array($objArr)) {
			return array();
		}
		$newArr = array();
		foreach ($objArr as $obj) {
			if ($obj->getAccessMode($user) >= $minMode) {
				$dms = $obj->getDMS();
				if($obj->isType('document')) {
					if($obj->getLatestContent())
						array_push($newArr, $obj);
				} else {
					array_push($newArr, $obj);
				}
			}
		}
		return $newArr;
	} /* }}} */

	/**
	 * Filter out users which cannot access an object in a given mode.
	 *
	 * The list of users to be checked can be of any class, but has to have
	 * a method getAccessMode($user) which checks if a user has at least the
	 * access right as passed in $minMode. Hence, passing a list of groups
	 * instead of users is possible.
	 *
	 * @param object $obj object that shall be accessed
	 * @param array $users list of users/groups which are to check for sufficient
	 *        access rights
	 * @param integer $minMode minimum access right on the object for each user
	 *        (M_ANY, M_NONE, M_READ, M_READWRITE, M_ALL)
	 * @return array filtered list of users
	 */
	static function filterUsersByAccess($obj, $users, $minMode) { /* {{{ */
		$newArr = array();
		foreach ($users as $currUser) {
			if ($obj->getAccessMode($currUser) >= $minMode)
				array_push($newArr, $currUser);
		}
		return $newArr;
	} /* }}} */

	/**
	 * Filter out document links which can not be accessed by a given user
	 *
	 * Returns a filtered list of links which are accessible by the
	 * given user. A link is only accessible, if it is publically visible,
	 * owned by the user, or the accessing user is an administrator.
	 *
	 * @param SeedDMS_Core_DocumentLink[] $links list of objects of type SeedDMS_Core_DocumentLink
	 * @param object $user user for which access is being checked
	 * @param string $access set if source or target of link shall be checked
	 * for sufficient access rights. Set to 'source' if the source document
	 * of a link is to be checked, set to 'target' for the target document.
	 * If not set, then access right aren't checked at all.
	 * @return array filtered list of links
	 */
	static function filterDocumentLinks($user, $links, $access='') { /* {{{ */
		$tmp = array();
		foreach ($links as $link) {
			if ($link->isPublic() || ($link->getUser()->getID() == $user->getID()) || $user->isAdmin()){
				if($access == 'source') {
					$obj = $link->getDocument();
					if ($obj->getAccessMode($user) >= M_READ)
						array_push($tmp, $link);
				} elseif($access == 'target') {
					$obj = $link->getTarget();
					if ($obj->getAccessMode($user) >= M_READ)
						array_push($tmp, $link);
				} else {
					array_push($tmp, $link);
				}
			}
		}
		return $tmp;
	} /* }}} */

	/**
	 * Merge access lists
	 *
	 * Merges two access lists. Objects of the second list will override objects
	 * in the first list.
	 *
	 * @param array $first list of access rights as returned by
	 * SeedDMS_Core_Document:: getAccessList() or SeedDMS_Core_Folder::getAccessList()
	 * @param array $secont list of access rights
	 * @return array merged list
	 */
	static function mergeAccessLists($first, $second) { /* {{{ */
		if($first && !$second)
			return $first;
		if(!$first && $second)
			return $second;

		$tmp = array('users'=>array(), 'groups'=>array());
		if(!isset($first['users']) || !isset($first['groups']) ||
			!isset($second['users']) || !isset($second['groups']))
			return false;

		foreach ($first['users'] as $f) {
			$new = $f;
			foreach ($second['users'] as $i=>$s) {
				if($f->getUserID() == $s->getUserID()) {
					$new = $s;
					unset($second['users'][$i]);
					break;
				}
			}
			array_push($tmp['users'], $new);
		}
		foreach ($seconf['users'] as $f) {
			array_push($tmp['users'], $f);
		}

		foreach ($first['groups'] as $f) {
			$new = $f;
			foreach ($second['groups'] as $i=>$s) {
				if($f->getGroupID() == $s->getGroupID()) {
					$new = $s;
					unset($second['groups'][$i]);
					break;
				}
			}
			array_push($tmp['groups'], $new);
		}
		foreach ($second['groups'] as $f) {
			array_push($tmp['groups'], $f);
		}

		return $tmp;
	} /* }}} */

	/*
	 * Filter out document attachments which can not be accessed by a given user
	 *
	 * Returns a filtered list of files which are accessible by the
	 * given user. A file is only accessible, if it is publically visible,
	 * owned by the user, or the accessing user is an administrator.
	 *
	 * @param array $files list of objects of type SeedDMS_Core_DocumentFile
	 * @param object $user user for which access is being checked
	 * @return array filtered list of files
	 */
	static function filterDocumentFiles($user, $files) { /* {{{ */
		$tmp = array();
		if($files) {
			foreach ($files as $file)
				if ($file->isPublic() || ($file->getUser()->getID() == $user->getID()) || $user->isAdmin() || ($file->getDocument()->getOwner()->getID() == $user->getID()))
					array_push($tmp, $file);
		}
		return $tmp;
	} /* }}} */

	/** @noinspection PhpUndefinedClassInspection */
	/**
	 * Create a new instance of the dms
	 *
	 * @param SeedDMS_Core_DatabaseAccess $db object of class {@link SeedDMS_Core_DatabaseAccess}
	 *        to access the underlying database
	 * @param string $contentDir path in filesystem containing the data store
	 *        all document contents is stored
	 */
	function __construct($db, $contentDir) { /* {{{ */
		$this->db = $db;
		if(substr($contentDir, -1) == '/')
			$this->contentDir = $contentDir;
		else
			$this->contentDir = $contentDir.'/';
		$this->rootFolderID = 1;
		$this->user = null;
		$this->maxDirID = 0; //31998;
		$this->forceRename = false;
		$this->checkWithinRootDir = false;
		$this->noReadForStatus = array();
		$this->user = null;
		$this->classnames = array();
		$this->classnames['folder'] = 'SeedDMS_Core_Folder';
		$this->classnames['document'] = 'SeedDMS_Core_Document';
		$this->classnames['documentcontent'] = 'SeedDMS_Core_DocumentContent';
		$this->classnames['documentfile'] = 'SeedDMS_Core_DocumentFile';
		$this->classnames['user'] = 'SeedDMS_Core_User';
		$this->classnames['role'] = 'SeedDMS_Core_Role';
		$this->classnames['group'] = 'SeedDMS_Core_Group';
		$this->classnames['transmittal'] = 'SeedDMS_Core_Transmittal';
		$this->classnames['transmittalitem'] = 'SeedDMS_Core_TransmittalItem';
		$this->usecache = false;
		$this->cache['users'] = [];
		$this->callbacks = array();
		$this->lasterror = '';
		$this->version = '@package_version@';
		if($this->version[0] == '@')
			$this->version = '6.0.24';
	} /* }}} */

	/**
	 * Return class name of classes instanciated by SeedDMS_Core
	 *
	 * This method returns the class name of those objects being instantiated
	 * by the dms. Each class has an internal place holder, which must be
	 * passed to function.
	 *
	 * @param string $objectname placeholder (can be one of 'folder', 'document',
	 * 'documentcontent', 'user', 'group')
	 *
	 * @return string/boolean name of class or false if object name is invalid
	 */
	function getClassname($objectname) { /* {{{ */
		if(isset($this->classnames[$objectname]))
			return $this->classnames[$objectname];
		else
			return false;
	} /* }}} */

	/**
	 * Set class name of instantiated objects
	 *
	 * This method sets the class name of those objects being instatiated
	 * by the dms. It is mainly used to create a new class (possible
	 * inherited from one of the available classes) implementing new
	 * features. The method should be called in the postInitDMS hook.
	 *
	 * @param string $objectname placeholder (can be one of 'folder', 'document',
	 * 'documentcontent', 'user', 'group'
	 * @param string $classname name of class
	 *
	 * @return string/boolean name of old class or false if not set
	 */
	function setClassname($objectname, $classname) { /* {{{ */
		if(isset($this->classnames[$objectname]))
			$oldclass =  $this->classnames[$objectname];
		else
			$oldclass = false;
		$this->classnames[$objectname] = $classname;
		return $oldclass;
	} /* }}} */

	/**
	 * Return list of decorators
	 *
	 * This method returns the list of decorator class names of those objects
	 * being instantiated
	 * by the dms. Each class has an internal place holder, which must be
	 * passed to function.
	 *
	 * @param string $objectname placeholder (can be one of 'folder', 'document',
	 * 'documentcontent', 'user', 'group')
	 *
	 * @return array/boolean list of class names or false if object name is invalid
	 */
	function getDecorators($objectname) { /* {{{ */
		if(isset($this->decorators[$objectname]))
			return $this->decorators[$objectname];
		else
			return false;
	} /* }}} */

	/**
	 * Add a decorator
	 *
	 * This method adds a single decorator class name to the list of decorators
	 * of those objects being instantiated
	 * by the dms. Each class has an internal place holder, which must be
	 * passed to function.
	 *
	 * @param string $objectname placeholder (can be one of 'folder', 'document',
	 * 'documentcontent', 'user', 'group')
	 *
	 * @return boolean true if decorator could be added, otherwise false
	 */
	function addDecorator($objectname, $decorator) { /* {{{ */
		$this->decorators[$objectname][] = $decorator;
		return true;
	} /* }}} */

	/**
	 * Return database where meta data is stored
	 *
	 * This method returns the database object as it was set by the first
	 * parameter of the constructor.
	 *
	 * @return SeedDMS_Core_DatabaseAccess database
	 */
	function getDB() { /* {{{ */
		return $this->db;
	} /* }}} */

	/**
	 * Return the database version
	 *
	 * @return array|bool
	 */
	function getDBVersion() { /* {{{ */
		$tbllist = $this->db->TableList();
		$tbllist = explode(',',strtolower(join(',',$tbllist)));
		if(!in_array('tblversion', $tbllist))
			return false;
		$queryStr = "SELECT * FROM `tblVersion` ORDER BY `major`,`minor`,`subminor` LIMIT 1";
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];
		return $resArr;
	} /* }}} */

	/**
	 * Check if the version in the database is the same as of this package
	 * Only the major and minor version number will be checked.
	 *
	 * @return boolean returns false if versions do not match, but returns
	 *         true if version matches or table tblVersion does not exists.
	 */
	function checkVersion() { /* {{{ */
		$tbllist = $this->db->TableList();
		$tbllist = explode(',',strtolower(join(',',$tbllist)));
		if(!in_array('tblversion', $tbllist))
			return true;
		$queryStr = "SELECT * FROM `tblVersion` ORDER BY `major`,`minor`,`subminor` LIMIT 1";
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];
		$ver = explode('.', $this->version);
		if(($resArr['major'] != $ver[0]) || ($resArr['minor'] != $ver[1]))
			return false;
		return true;
	} /* }}} */

	/**
	 * Set id of root folder
	 *
	 * This function must be called right after creating an instance of
	 * {@link SeedDMS_Core_DMS}
	 *
	 * The new root folder id will only be set if the folder actually
	 * exists. In that case the old root folder id will be returned.
	 * If it does not exists, the method will return false;
	 * @param integer $id id of root folder
	 * @return boolean/int old root folder id if new root folder exists, otherwise false
	 */
	function setRootFolderID($id) { /* {{{ */
		if($this->getFolder($id)) {
			$oldid = $this->rootFolderID;
			$this->rootFolderID = $id;
			return $oldid;
		}
		return false;
	} /* }}} */

	/**
	 * Set maximum number of subdirectories per directory
	 *
	 * The value of maxDirID is quite crucial, because each document is
	 * stored within a directory in the filesystem. Consequently, there can be
	 * a maximum number of documents, because depending on the file system
	 * the maximum number of subdirectories is limited. Since version 3.3.0 of
	 * SeedDMS an additional directory level has been introduced, which
	 * will be created when maxDirID is not 0. All documents
	 * from 1 to maxDirID-1 will be saved in 1/<docid>, documents from maxDirID
	 * to 2*maxDirID-1 are stored in 2/<docid> and so on.
	 *
	 * Modern file systems like ext4 do not have any restrictions on the number
	 * of subdirectories anymore. Therefore it is best if this parameter is
	 * set to 0. Never change this parameter if documents has already been
	 * created.
	 *
	 * This function must be called right after creating an instance of
	 * {@link SeedDMS_Core_DMS}
	 *
	 * @param integer $id id of root folder
	 */
	function setMaxDirID($id) { /* {{{ */
		$this->maxDirID = $id;
	} /* }}} */

	/**
	 * Get root folder
	 *
	 * @return SeedDMS_Core_Folder|boolean return the object of the root folder or false if
	 *        the root folder id was not set before with {@link setRootFolderID}.
	 */
	function getRootFolder() { /* {{{ */
		if(!$this->rootFolderID) return false;
		return $this->getFolder($this->rootFolderID);
	} /* }}} */

	function setForceRename($enable) { /* {{{ */
		$this->forceRename = $enable;
	} /* }}} */

	/**
	 * Set the logged in user
	 *
	 * This method tells SeeDMS_Core_DMS the currently logged in user. It must be
	 * called right after instanciating the class, because some methods in
	 * SeedDMS_Core_Document() require the currently logged in user.
	 *
	 * @param object $user this muss not be empty and an instance of SeedDMS_Core_User
	 * @return bool|object returns the old user object or null on success, otherwise false
	 *
	 */
	function setUser($user) { /* {{{ */
		if(!$user) {
			$olduser = $this->user;
			$this->user = null;
			return $olduser;
		}
		if(is_object($user) && (get_class($user) == $this->getClassname('user'))) {
			$olduser = $this->user;
			$this->user = $user;
			return $olduser;
		}
		return false;
	} /* }}} */

	/**
	 * Get the logged in user
	 *
	 * Returns the currently logged in user, as set by setUser()
	 *
	 * @return SeedDMS_Core_User $user
	 *
	 */
	function getLoggedInUser() { /* {{{ */
		return $this->user;
	} /* }}} */

	/**
	 * Return a document by its id
	 *
	 * This function retrieves a document from the database by its id.
	 *
	 * @param integer $id internal id of document
	 * @return SeedDMS_Core_Document instance of {@link SeedDMS_Core_Document}, null or false
	 */
	function getDocument($id) { /* {{{ */
		$classname = $this->classnames['document'];
		return $classname::getInstance($id, $this);
	} /* }}} */

	/**
	 * Returns all documents of a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsByUser($user) { /* {{{ */
		return $user->getDocuments();
	} /* }}} */

	/**
	 * Returns all documents locked by a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsLockedByUser($user) { /* {{{ */
		return $user->getDocumentsLocked();
	} /* }}} */

	/**
	 * Returns all documents which already expired or will expire in the future
	 *
	 * The parameter $date will be relative to the start of the day. It can
	 * be either a number of days (if an integer is passed) or a date string
	 * in the format 'YYYY-MM-DD'.
	 * If the parameter $date is a negative number or a date in the past, then
	 * all documents from the start of that date till the end of the current
	 * day will be returned. If $date is a positive integer or $date is a
	 * date in the future, then all documents from the start of the current
	 * day till the end of the day of the given date will be returned.
	 * Passing 0 or the
	 * current date in $date, will return all documents expiring the current
	 * day.
	 * @param string $date date in format YYYY-MM-DD or an integer with the number
	 *   of days. A negative value will cover the days in the past.
	 * @param SeedDMS_Core_User $user limits the documents on those owned
	 *   by this user
	 * @param string $orderby n=name, e=expired
	 * @param string $orderdir d=desc or a=asc
	 * @param bool $update update status of document if set to true
	 * @return bool|SeedDMS_Core_Document[]
	 */
	function getDocumentsExpired($date, $user=null, $orderby='e', $orderdir='desc', $update=true) { /* {{{ */
		$db = $this->getDB();

		if (!$db->createTemporaryTable("ttstatid") || !$db->createTemporaryTable("ttcontentid")) {
			return false;
		}

		$tsnow = mktime(0, 0, 0); /* Start of today */
		if(is_int($date)) {
			$ts = $tsnow + $date * 86400;
		} elseif(is_string($date)) {
			$tmp = explode('-', $date, 3);
			if(count($tmp) != 3)
                return false;
            if(!self::checkDate($date, 'Y-m-d'))
                return false;
			$ts = mktime(0, 0, 0, $tmp[1], $tmp[2], $tmp[0]);
		} else
			return false;

		if($ts < $tsnow) { /* Check for docs expired in the past */
			$startts = $ts;
			$endts = $tsnow+86400; /* Use end of day */
			$updatestatus = $update;
		} else { /* Check for docs which will expire in the future */
			$startts = $tsnow;
			$endts = $ts+86400; /* Use end of day */
			$updatestatus = false;
		}

		/* Get all documents which have an expiration date. It doesn't check for
		 * the latest status which should be S_EXPIRED, but doesn't have to, because
		 * status may have not been updated after the expiration date has been reached.
		 **/
		$queryStr = "SELECT `tblDocuments`.`id`, `tblDocumentStatusLog`.`status`  FROM `tblDocuments` ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`document` = `tblDocuments`.`id` ".
			"LEFT JOIN `tblDocumentContent` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` AND `tblDocumentContent`.`version` = `ttcontentid`.`maxVersion` ".
			"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` AND `tblDocumentContent`.`version` = `tblDocumentStatus`.`version` ".
			"LEFT JOIN `ttstatid` ON `ttstatid`.`statusID` = `tblDocumentStatus`.`statusID` ".
			"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusLogID` = `ttstatid`.`maxLogID`";
		$queryStr .= 
			" WHERE `tblDocuments`.`expires` >= ".$startts." AND `tblDocuments`.`expires` < ".$endts;
		if($user)
			$queryStr .=
				" AND `tblDocuments`.`owner` = '".$user->getID()."' ";
		$queryStr .= 
			" ORDER BY ".($orderby == 'e' ? "`expires`" : "`name`")." ".($orderdir == 'd' ? "DESC" : "ASC");

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		/** @var SeedDMS_Core_Document[] $documents */
		$documents = array();
		foreach ($resArr as $row) {
			$document = $this->getDocument($row["id"]);
			if($updatestatus) {
				$document->verifyLastestContentExpriry();
			}
			$documents[] = $document;
		}
		return $documents;
	} /* }}} */

	/**
	 * Returns a document by its name
	 *
	 * This function searches a document by its name and restricts the search
	 * to the given folder if passed as the second parameter.
	 * If there are more than one document with that name, then only the
	 * one with the highest id will be returned. 
	 *
	 * @param string $name Name of the document
	 * @param object $folder parent folder of document
	 * @return SeedDMS_Core_Document|null|boolean found document or null if not document was found or false in case of an error
	 */
	function getDocumentByName($name, $folder=null) { /* {{{ */
		$name = trim($name);
		if (!$name) return false;

		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocuments` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `tblDocuments`.`name` = " . $this->db->qstr($name);
		if($folder)
			$queryStr .= " AND `tblDocuments`.`folder` = ". $folder->getID();
		if($this->checkWithinRootDir)
			$queryStr .= " AND `tblDocuments`.`folderList` LIKE '%:".$this->rootFolderID.":%'";
		$queryStr .= " ORDER BY `tblDocuments`.`id` DESC LIMIT 1";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if(!$resArr)
			return null;

		$row = $resArr[0];
		/** @var SeedDMS_Core_Document $document */
		$document = new $this->classnames['document']($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
		$document->setDMS($this);
		return $document;
	} /* }}} */

	/**
	 * Returns a document by the original file name of the last version
	 *
	 * This function searches a document by the name of the last document
	 * version and restricts the search
	 * to given folder if passed as the second parameter.
	 * If there are more than one document with that name, then only the
	 * one with the highest id will be returned. 
	 *
	 * @param string $name Name of the original file
	 * @param object $folder parent folder of document
	 * @return SeedDMS_Core_Document|null|boolean found document or null if not document was found or false in case of an error
	 */
	function getDocumentByOriginalFilename($name, $folder=null) { /* {{{ */
		$name = trim($name);
		if (!$name) return false;

		if (!$this->db->createTemporaryTable("ttcontentid")) {
			return false;
		}
		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocuments` ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`document` = `tblDocuments`.`id` ".
			"LEFT JOIN `tblDocumentContent` ON `tblDocumentContent`.`document` = `tblDocuments`.`id` AND `tblDocumentContent`.`version` = `ttcontentid`.`maxVersion` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `tblDocumentContent`.`orgFileName` = " . $this->db->qstr($name);
		if($folder)
			$queryStr .= " AND `tblDocuments`.`folder` = ". $folder->getID();
		$queryStr .= " ORDER BY `tblDocuments`.`id` DESC LIMIT 1";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if(!$resArr)
			return null;

		$row = $resArr[0];
		/** @var SeedDMS_Core_Document $document */
		$document = new $this->classnames['document']($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
		$document->setDMS($this);
		return $document;
	} /* }}} */

	/**
	 * Return a document content by its id
	 *
	 * This function retrieves a document content from the database by its id.
	 *
	 * @param integer $id internal id of document content
	 * @return bool|null|SeedDMS_Core_DocumentContent found document content or null if not document content was found or false in case of an error

	 */
	function getDocumentContent($id) { /* {{{ */
		$classname = $this->classnames['documentcontent'];
		return $classname::getInstance($id, $this);
	} /* }}} */

	/**
	 * Returns all documents with a predefined search criteria
	 *
	 * @param string $listtype type of document list, can be 'AppRevByMe',
	 * 'AppRevOwner', 'ReceiptByMe', 'ReviseByMe', 'LockedByMe', 'MyDocs'
	 * @param object $user user
	 * @return array list of documents records
	 */
	function countTasks($listtype, $user=null, $param5=true) { /* {{{ */
		if (!$this->db->createTemporaryTable("ttstatid") || !$this->db->createTemporaryTable("ttcontentid")) {
			return false;
		}
		$groups = array();
		if($user) {
			$tmp = $user->getGroups();
			foreach($tmp as $group)
				$groups[] = $group->getID();
		}
		$selectStr = "count(distinct ttcontentid.document) c ";
		$queryStr = 
			"FROM `ttcontentid` ".
			"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID`=`ttcontentid`.`document` AND `tblDocumentStatus`.`version`=`ttcontentid`.`maxVersion` ".
			"LEFT JOIN `ttstatid` ON `ttstatid`.`statusID` = `tblDocumentStatus`.`statusID` ".
			"LEFT JOIN `tblDocumentStatusLog` ON `ttstatid`.`statusID` = `tblDocumentStatusLog`.`statusID` AND `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ";
		switch($listtype) {
		case 'ReviewByMe': // Documents I have to review {{{
			if (!$this->db->createTemporaryTable("ttreviewid")) {
				return false;
			}
			$queryStr .=
				"LEFT JOIN `tblDocumentReviewers` on `ttcontentid`.`document`=`tblDocumentReviewers`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentReviewers`.`version` ".
				"LEFT JOIN `ttreviewid` ON `ttreviewid`.`reviewID` = `tblDocumentReviewers`.`reviewID` ".
				"LEFT JOIN `tblDocumentReviewLog` ON `tblDocumentReviewLog`.`reviewLogID`=`ttreviewid`.`maxLogID` ";

			$queryStr .= "WHERE (`tblDocumentReviewers`.`type` = 0 AND `tblDocumentReviewers`.`required` = ".$user->getID()." ";
			if($groups)
				$queryStr .= "OR `tblDocumentReviewers`.`type` = 1 AND `tblDocumentReviewers`.`required` IN (".implode(',', $groups).") ";
			$queryStr .= ") ";
			$queryStr .= "AND `tblDocumentReviewLog`.`status` = 0 ";
			$docstatarr = array(S_DRAFT_REV);
			if($param5)
				$docstatarr[] = S_EXPIRED;
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".implode(',', $docstatarr).") ";
			break; /* }}} */
		case 'ApproveByMe': // Documents I have to approve {{{
			if (!$this->db->createTemporaryTable("ttapproveid")) {
				return false;
			}
			$queryStr .=
				"LEFT JOIN `tblDocumentApprovers` on `ttcontentid`.`document`=`tblDocumentApprovers`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentApprovers`.`version` ".
				"LEFT JOIN `ttapproveid` ON `ttapproveid`.`approveID` = `tblDocumentApprovers`.`approveID` ".
				"LEFT JOIN `tblDocumentApproveLog` ON `tblDocumentApproveLog`.`approveLogID`=`ttapproveid`.`maxLogID` ";

			if($user) {
				$queryStr .= "WHERE (`tblDocumentApprovers`.`type` = 0 AND `tblDocumentApprovers`.`required` = ".$user->getID()." ";
				if($groups)
					$queryStr .= "OR `tblDocumentApprovers`.`type` = 1 AND `tblDocumentApprovers`.`required` IN (".implode(',', $groups).") ";
				$queryStr .= ") ";
			}
			$queryStr .= "AND `tblDocumentApproveLog`.`status` = 0 ";
			$docstatarr = array(S_DRAFT_APP);
			if($param5)
				$docstatarr[] = S_EXPIRED;
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".implode(',', $docstatarr).") ";
			break; /* }}} */
		case 'ReceiptByMe': // Documents I have to receipt {{{
			if (!$this->db->createTemporaryTable("ttreceiptid")) {
				return false;
			}
			$queryStr .=
				"LEFT JOIN `tblDocumentRecipients` on `ttcontentid`.`document`=`tblDocumentRecipients`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentRecipients`.`version` ".
				"LEFT JOIN `ttreceiptid` ON `ttreceiptid`.`receiptID` = `tblDocumentRecipients`.`receiptID` ".
				"LEFT JOIN `tblDocumentReceiptLog` ON `tblDocumentReceiptLog`.`receiptLogID`=`ttreceiptid`.`maxLogID` ";

			if($user) {
				$queryStr .= "WHERE (`tblDocumentRecipients`.`type` = 0 AND `tblDocumentRecipients`.`required` = ".$user->getID()." ";
				if($groups)
					$queryStr .= "OR `tblDocumentRecipients`.`type` = 1 AND `tblDocumentRecipients`.`required` IN (".implode(',', $groups).") ";
				$queryStr .= ") ";
			}
			$queryStr .= "AND `tblDocumentReceiptLog`.`status` = 0 ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_RELEASED.") ";
			break; /* }}} */
		case 'ReviseByMe': // Documents I have to receipt {{{
			if (!$this->db->createTemporaryTable("ttrevisionid")) {
				return false;
			}
			$queryStr .=
				"LEFT JOIN `tblDocumentRevisors` on `ttcontentid`.`document`=`tblDocumentRevisors`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentRevisors`.`version` ".
				"LEFT JOIN `ttrevisionid` ON `ttrevisionid`.`revisionID` = `tblDocumentRevisors`.`revisionID` ".
				"LEFT JOIN `tblDocumentRevisionLog` ON `tblDocumentRevisionLog`.`revisionLogID`=`ttrevisionid`.`maxLogID` ";

			if($user) {
				$queryStr .= "WHERE (`tblDocumentRevisors`.`type` = 0 AND `tblDocumentRevisors`.`required` = ".$user->getID()." ";
				if($groups)
					$queryStr .= "OR `tblDocumentRevisors`.`type` = 1 AND `tblDocumentRevisors`.`required` IN (".implode(',', $groups).") ";
				$queryStr .= ") ";
			}
			$queryStr .= "AND `tblDocumentRevisionLog`.`status` = 0 ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_IN_REVISION.") ";
			break; /* }}} */
		case 'SleepingReviseByMe': // Documents I have to receipt {{{
			if (!$this->db->createTemporaryTable("ttrevisionid")) {
				return false;
			}
			$queryStr .=
				"LEFT JOIN `tblDocumentRevisors` on `ttcontentid`.`document`=`tblDocumentRevisors`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentRevisors`.`version` ".
				"LEFT JOIN `ttrevisionid` ON `ttrevisionid`.`revisionID` = `tblDocumentRevisors`.`revisionID` ".
				"LEFT JOIN `tblDocumentRevisionLog` ON `tblDocumentRevisionLog`.`revisionLogID`=`ttrevisionid`.`maxLogID` ";

			if($user) {
				$queryStr .= "WHERE (`tblDocumentRevisors`.`type` = 0 AND `tblDocumentRevisors`.`required` = ".$user->getID()." ";
				if($groups)
					$queryStr .= "OR `tblDocumentRevisors`.`type` = 1 AND `tblDocumentRevisors`.`required` IN (".implode(',', $groups).") ";
				$queryStr .= ") ";
			}
			$queryStr .= "AND `tblDocumentContent`.`revisiondate` IS NOT NULL AND `tblDocumentContent`.`revisiondate` <= ".$this->db->getCurrentDatetime(14)." ";
			$queryStr .= "AND `tblDocumentRevisionLog`.`status` = -3 ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_RELEASED.") ";
			break; /* }}} */
		case 'NeedsCorrectionOwner': // Documents that need to be corrected {{{
			$queryStr .=
				"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `ttcontentid`.`document` ";
			$queryStr .= "WHERE `tblDocuments`.`owner` = '".$user->getID()."' ".
				"AND `tblDocumentStatusLog`.`status` IN (".S_NEEDS_CORRECTION.") ";
			break; /* }}} */
		case 'WorkflowByMe': // Documents which need my workflow action {{{

			$queryStr .=
				"LEFT JOIN `tblWorkflowDocumentContent` on `ttcontentid`.`document`=`tblWorkflowDocumentContent`.`document` AND `ttcontentid`.`maxVersion`=`tblWorkflowDocumentContent`.`version` ".
				"LEFT JOIN `tblWorkflowTransitions` on `tblWorkflowDocumentContent`.`workflow`=`tblWorkflowTransitions`.`workflow` AND `tblWorkflowDocumentContent`.`state`=`tblWorkflowTransitions`.`state` ".
				"LEFT JOIN `tblWorkflowTransitionUsers` on `tblWorkflowTransitionUsers`.`transition` = `tblWorkflowTransitions`.`id` ".
				"LEFT JOIN `tblWorkflowTransitionGroups` on `tblWorkflowTransitionGroups`.`transition` = `tblWorkflowTransitions`.`id` ";

			if($user) {
				$queryStr .= "WHERE (`tblWorkflowTransitionUsers`.`userid` = ".$user->getID()." ";
				if($groups)
					$queryStr .= "OR `tblWorkflowTransitionGroups`.`groupid` IN (".implode(',', $groups).")";
				$queryStr .= ") ";
			}
			$queryStr .= "AND `tblDocumentStatusLog`.`status` = ".S_IN_WORKFLOW." ";
			break; // }}}
		}
		if($queryStr) {
			$resArr = $this->db->getResultArray('SELECT '.$selectStr.$queryStr);
			if (is_bool($resArr) && !$resArr) {
				return false;
			}
		} else {
			return false;
		}
		return $resArr[0]['c'];
	} /* }}} */

	/**
	 * Returns all documents with a predefined search criteria
	 *
	 * The records return have the following elements
	 *
	 * From Table tblDocuments
	 * [id] => id of document
	 * [name] => name of document
	 * [comment] => comment of document
	 * [date] => timestamp of creation date of document
	 * [expires] => timestamp of expiration date of document
	 * [owner] => user id of owner
	 * [folder] => id of parent folder
	 * [folderList] => column separated list of folder ids, e.g. :1:41:
	 * [inheritAccess] => 1 if access is inherited
	 * [defaultAccess] => default access mode
	 * [locked] => always -1 (TODO: is this field still used?)
	 * [keywords] => keywords of document
	 * [sequence] => sequence of document
	 *
	 * From Table tblDocumentLocks
	 * [lockUser] => id of user locking the document
	 *
	 * From Table tblDocumentStatusLog
	 * [version] => latest version of document
	 * [statusID] => id of latest status log
	 * [documentID] => id of document
	 * [status] => current status of document
	 * [statusComment] => comment of current status
	 * [statusDate] => datetime when the status was entered, e.g. 2014-04-17 21:35:51
	 * [userID] => id of user who has initiated the status change
	 *
	 * From Table tblUsers
	 * [ownerName] => name of owner of document
	 * [statusName] => name of user who has initiated the status change
	 *
	 * @param string $listtype type of document list, can be 'AppRevByMe',
	 * 'AppRevOwner', 'ReceiptByMe', 'ReviseByMe', 'LockedByMe', 'MyDocs'
	 * @param SeedDMS_Core_User $param1 user
	 * @param bool|integer|string $param2 if set to true
	 * 'ReviewByMe', 'ApproveByMe', 'AppRevByMe', 'ReviseByMe', 'ReceiptByMe'
	 * will also return documents which the reviewer, approver, etc.
	 * has already taken care of. If set to false only
	 * untouched documents will be returned. In case of 'ExpiredOwner',
	 * 'SleepingReviseByMe' this
	 * parameter contains the number of days (a negative number is allowed)
	 * relativ to the current date or a date in format 'yyyy-mm-dd'
	 * (even in the past).
	 * @param string $param3 sort list by this field
	 * @param string $param4 order direction
	 * @param bool $param5 set to false if expired documents shall not be considered
	 * @return array|bool
	 */
	function getDocumentList($listtype, $param1=null, $param2=false, $param3='', $param4='', $param5=true) { /* {{{ */
		/* The following query will get all documents and lots of additional
		 * information. It requires the two temporary tables ttcontentid and
		 * ttstatid.
		 */
		if (!$this->db->createTemporaryTable("ttstatid") || !$this->db->createTemporaryTable("ttcontentid")) {
			return false;
		}
		/* The following statement retrieves the status of the last version of all
		 * documents. It must be restricted by further where clauses.
		 */
/*
		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`, ".
			"`tblDocumentContent`.`version`, `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
			"`tblDocumentStatusLog`.`comment` AS `statusComment`, `tblDocumentStatusLog`.`date` as `statusDate`, ".
			"`tblDocumentStatusLog`.`userID`, `oTbl`.`fullName` AS `ownerName`, `sTbl`.`fullName` AS `statusName` ".
			"FROM `tblDocumentContent` ".
			"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
			"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
			"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
			"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"LEFT JOIN `tblUsers` AS `oTbl` on `oTbl`.`id` = `tblDocuments`.`owner` ".
			"LEFT JOIN `tblUsers` AS `sTbl` on `sTbl`.`id` = `tblDocumentStatusLog`.`userID` ".
			"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
			"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version` ";
 */
		/* New sql statement which retrieves all documents, its latest version and
		 * status, the owner and user initiating the latest status.
		 * It doesn't need the where clause anymore. Hence the statement could be
		 * extended with further left joins.
		 */
		$selectStr = "`tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`, ".
			"`tblDocumentContent`.`version`, `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
			"`tblDocumentStatusLog`.`comment` AS `statusComment`, `tblDocumentStatusLog`.`date` as `statusDate`, ".
			"`tblDocumentStatusLog`.`userID`, `oTbl`.`fullName` AS `ownerName`, `sTbl`.`fullName` AS `statusName` ";
		$queryStr =
			"FROM `ttcontentid` ".
			"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `ttcontentid`.`document` ".
			"LEFT JOIN `tblDocumentContent` ON `tblDocumentContent`.`document` = `ttcontentid`.`document` AND `tblDocumentContent`.`version` = `ttcontentid`.`maxVersion` ".
			"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID`=`ttcontentid`.`document` AND `tblDocumentStatus`.`version`=`ttcontentid`.`maxVersion` ".
			"LEFT JOIN `ttstatid` ON `ttstatid`.`statusID` = `tblDocumentStatus`.`statusID` ".
			"LEFT JOIN `tblDocumentStatusLog` ON `ttstatid`.`statusID` = `tblDocumentStatusLog`.`statusID` AND `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
			"LEFT JOIN `tblDocumentLocks` ON `ttcontentid`.`document`=`tblDocumentLocks`.`document` ".
			"LEFT JOIN `tblUsers` `oTbl` ON `oTbl`.`id` = `tblDocuments`.`owner` ".
			"LEFT JOIN `tblUsers` `sTbl` ON `sTbl`.`id` = `tblDocumentStatusLog`.`userID` ";

//		echo $queryStr;

		switch($listtype) {
		case 'AppRevByMe': // Documents I have to review/approve {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			// Get document list for the current user.
			$reviewStatus = $user->getReviewStatus();
			$approvalStatus = $user->getApprovalStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			// Take only those documents into account which hasn't be touched by the user
			$dList = array();
			foreach ($reviewStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($reviewStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($approvalStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($approvalStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$docstatarr = array(S_DRAFT_REV, S_DRAFT_APP);
				if($param5)
					$docstatarr[] = S_EXPIRED;
				$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".implode(',', $docstatarr).") ".
							"AND `tblDocuments`.`id` IN (" . $docCSV . ") ".
							"ORDER BY `statusDate` DESC";
			} else {
				$queryStr = '';
			}
			break; // }}}
		case 'ReviewByMe': // Documents I have to review {{{
			if (!$this->db->createTemporaryTable("ttreviewid")) {
				return false;
			}
			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$groups = array();
			if($user) {
				$tmp = $user->getGroups();
				foreach($tmp as $group)
					$groups[] = $group->getID();
			}

			$selectStr .= ", `tblDocumentReviewLog`.`date` as `duedate` ";
			$queryStr .=
				"LEFT JOIN `tblDocumentReviewers` ON `ttcontentid`.`document`=`tblDocumentReviewers`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentReviewers`.`version` ".
				"LEFT JOIN `ttreviewid` ON `ttreviewid`.`reviewID` = `tblDocumentReviewers`.`reviewID` ".
				"LEFT JOIN `tblDocumentReviewLog` ON `tblDocumentReviewLog`.`reviewLogID`=`ttreviewid`.`maxLogID` ";

			if(1) {
			if($user) {
				$queryStr .= "WHERE (`tblDocumentReviewers`.`type` = 0 AND `tblDocumentReviewers`.`required` = ".$user->getID()." ";
				if($groups)
					$queryStr .= "OR `tblDocumentReviewers`.`type` = 1 AND `tblDocumentReviewers`.`required` IN (".implode(',', $groups).") ";
				$queryStr .= ") ";
			}
			$docstatarr = array(S_DRAFT_REV);
			if($param5)
				$docstatarr[] = S_EXPIRED;
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".implode(',', $docstatarr).") ";
			if(!$param2)
				$queryStr .= " AND `tblDocumentReviewLog`.`status` = 0 ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `tblDocumentStatusLog`.`status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			} else {
			$queryStr .= "WHERE 1=1 ";

			// Get document list for the current user.
			$reviewStatus = $user->getReviewStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			// Take only those documents into account which hasn't be touched by the user
			// ($st["status"]==0)
			$dList = array();
			foreach ($reviewStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($reviewStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_EXPIRED.") ".
							"AND `tblDocuments`.`id` IN (" . $docCSV . ") ";
				//$queryStr .= "ORDER BY `statusDate` DESC";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			}
			break; // }}}
		case 'ApproveByMe': // Documents I have to approve {{{
			if (!$this->db->createTemporaryTable("ttapproveid")) {
				return false;
			}
			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$groups = array();
			if($user) {
				$tmp = $user->getGroups();
				foreach($tmp as $group)
					$groups[] = $group->getID();
			}

			$selectStr .= ", `tblDocumentApproveLog`.`date` as `duedate` ";
			$queryStr .=
				"LEFT JOIN `tblDocumentApprovers` ON `ttcontentid`.`document`=`tblDocumentApprovers`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentApprovers`.`version` ".
				"LEFT JOIN `ttapproveid` ON `ttapproveid`.`approveID` = `tblDocumentApprovers`.`approveID` ".
				"LEFT JOIN `tblDocumentApproveLog` ON `tblDocumentApproveLog`.`approveLogID`=`ttapproveid`.`maxLogID` ";

			if(1) {
			if($user) {
			$queryStr .= "WHERE (`tblDocumentApprovers`.`type` = 0 AND `tblDocumentApprovers`.`required` = ".$user->getID()." ";
			if($groups)
				$queryStr .= "OR `tblDocumentApprovers`.`type` = 1 AND `tblDocumentApprovers`.`required` IN (".implode(',', $groups).")";
			$queryStr .= ") ";
			}
			$docstatarr = array(S_DRAFT_APP);
			if($param5)
				$docstatarr[] = S_EXPIRED;
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".implode(',', $docstatarr).") ";
			if(!$param2)
				$queryStr .= " AND `tblDocumentApproveLog`.`status` = 0 ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `tblDocumentStatusLog`.`status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			} else {
			$queryStr .= "WHERE 1=1 ";

			// Get document list for the current user.
			$approvalStatus = $user->getApprovalStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			// Take only those documents into account which hasn't be touched by the user
			// ($st["status"]==0)
			$dList = array();
			foreach ($approvalStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($approvalStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$docstatarr = array(S_DRAFT_APP);
				if($param5)
					$docstatarr[] = S_EXPIRED;
				$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".implode(',', $docstatarr).") ".
							"AND `tblDocuments`.`id` IN (" . $docCSV . ") ";
				//$queryStr .= "ORDER BY `statusDate` DESC";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			}
			break; // }}}
		case 'ReceiptByMe': // Documents I have to receipt {{{
			if (!$this->db->createTemporaryTable("ttreceiptid")) {
				return false;
			}
			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$groups = array();
			$tmp = $user->getGroups();
			foreach($tmp as $group)
				$groups[] = $group->getID();

			$selectStr .= ", `tblDocumentReceiptLog`.`date` as `duedate` ";
			$queryStr .=
				"LEFT JOIN `tblDocumentRecipients` on `ttcontentid`.`document`=`tblDocumentRecipients`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentRecipients`.`version` ".
				"LEFT JOIN `ttreceiptid` ON `ttreceiptid`.`receiptID` = `tblDocumentRecipients`.`receiptID` ".
				"LEFT JOIN `tblDocumentReceiptLog` ON `tblDocumentReceiptLog`.`receiptLogID`=`ttreceiptid`.`maxLogID` ";

			if(1) {
			$queryStr .= "WHERE (`tblDocumentRecipients`.`type` = 0 AND `tblDocumentRecipients`.`required` = ".$user->getID()." ";
			/* Checking for groups slows down the statement extremly on sqlite */
			if($groups)
				$queryStr .= "OR `tblDocumentRecipients`.`type` = 1 AND `tblDocumentRecipients`.`required` IN (".implode(',', $groups).")";
			$queryStr .= ") ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` = ".S_RELEASED." ";
			if(!$param2)
				$queryStr .= " AND `tblDocumentReceiptLog`.`status` = 0 ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `tblDocumentStatusLog`.`status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			} else {
			$queryStr .= "WHERE 1=1 ";

			// Get document list for the current user.
			$receiptStatus = $user->getReceiptStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			// Take only those documents into account which hasn't be touched by the user
			// ($st["status"]==0)
			$dList = array();
			foreach ($receiptStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($receiptStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$queryStr .= "AND `tblDocuments`.`id` IN (" . $docCSV . ") ";
//				$queryStr .= "ORDER BY `statusDate` DESC";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			}
			break; // }}}
		case 'ReviseByMe': // Documents I have to revise {{{
			if (!$this->db->createTemporaryTable("ttrevisionid")) {
				return false;
			}
			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$groups = array();
			$tmp = $user->getGroups();
			foreach($tmp as $group)
				$groups[] = $group->getID();

			$selectStr .= ", `tblDocumentRevisionLog`.`date` as `duedate` ";
			$queryStr .=
				"LEFT JOIN `tblDocumentRevisors` on `ttcontentid`.`document`=`tblDocumentRevisors`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentRevisors`.`version` ".
				"LEFT JOIN `ttrevisionid` ON `ttrevisionid`.`revisionID` = `tblDocumentRevisors`.`revisionID` ".
				"LEFT JOIN `tblDocumentRevisionLog` ON `tblDocumentRevisionLog`.`revisionLogID`=`ttrevisionid`.`maxLogID` ";

			if(1) {
			$queryStr .= "WHERE (`tblDocumentRevisors`.`type` = 0 AND `tblDocumentRevisors`.`required` = ".$user->getID()." ";
			if($groups)
				$queryStr .= "OR `tblDocumentRevisors`.`type` = 1 AND `tblDocumentRevisors`.`required` IN (".implode(',', $groups).")";
			$queryStr .= ") ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` = ".S_IN_REVISION." ";
			if(!$param2)
				$queryStr .= " AND `tblDocumentRevisionLog`.`status` = 0 ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `tblDocumentStatusLog`.`status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			} else {
			$queryStr .= "WHERE 1=1 ";

			// Get document list for the current user.
			$revisionStatus = $user->getRevisionStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			$dList = array();
			foreach ($revisionStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($revisionStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$queryStr .= "AND `tblDocuments`.`id` IN (" . $docCSV . ") ";
				//$queryStr .= "ORDER BY `statusDate` DESC";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			}
			break; // }}}
		case 'SleepingReviseByMe': // Documents I have to revise but are still sleeping {{{
			if (!$this->db->createTemporaryTable("ttrevisionid")) {
				return false;
			}

			$dayoffset = 0;
			if(is_int($param2)) {
				$dayoffset = (int) $param2;
			}

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$groups = array();
			$tmp = $user->getGroups();
			foreach($tmp as $group)
				$groups[] = $group->getID();

			$selectStr .= ", `tblDocumentRevisionLog`.`date` as `duedate` ";
			$queryStr .=
				"LEFT JOIN `tblDocumentRevisors` on `ttcontentid`.`document`=`tblDocumentRevisors`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentRevisors`.`version` ".
				"LEFT JOIN `ttrevisionid` ON `ttrevisionid`.`revisionID` = `tblDocumentRevisors`.`revisionID` ".
				"LEFT JOIN `tblDocumentRevisionLog` ON `tblDocumentRevisionLog`.`revisionLogID`=`ttrevisionid`.`maxLogID` ";

			$queryStr .= "WHERE (`tblDocumentRevisors`.`type` = 0 AND `tblDocumentRevisors`.`required` = ".$user->getID()." ";
			if($groups)
				$queryStr .= "OR `tblDocumentRevisors`.`type` = 1 AND `tblDocumentRevisors`.`required` IN (".implode(',', $groups).")";
			$queryStr .= ") ";
			$queryStr .= "AND `tblDocumentContent`.`revisiondate` IS NOT NULL AND `tblDocumentContent`.`revisiondate` <= ".$this->db->getCurrentDatetime($dayoffset)." ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` = ".S_RELEASED." ";
			$queryStr .= " AND `tblDocumentRevisionLog`.`status` = -3 ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `tblDocumentStatusLog`.`status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		case 'DueRevision': // Documents with a due revision, which is not started {{{
			if (!$this->db->createTemporaryTable("ttrevisionid")) {
				return false;
			}

			$dayoffset = 0;
			if(is_int($param2)) {
				$dayoffset = (int) $param2;
			}

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$selectStr .= ", `tblDocumentContent`.`revisiondate` ";
			$queryStr .= "WHERE `tblDocumentContent`.`revisiondate` IS NOT NULL AND `tblDocumentContent`.`revisiondate` <= ".$this->db->getCurrentDatetime($dayoffset)." ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` = ".S_RELEASED." ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			$queryStr .= ", `tblDocumentContent`.`revisiondate` ASC";
			break; // }}}
		case 'WorkflowByMe': // Documents I to trigger in Worklflow {{{
			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			if(1) {
			$groups = array();
			if($user) {
				$tmp = $user->getGroups();
				foreach($tmp as $group)
					$groups[] = $group->getID();
			}
			$selectStr = 'distinct '.$selectStr;
			$queryStr .=
				"LEFT JOIN `tblWorkflowDocumentContent` ON `ttcontentid`.`document`=`tblWorkflowDocumentContent`.`document` AND `ttcontentid`.`maxVersion`=`tblWorkflowDocumentContent`.`version` ".
				"LEFT JOIN `tblWorkflowTransitions` ON `tblWorkflowDocumentContent`.`workflow`=`tblWorkflowTransitions`.`workflow` AND `tblWorkflowDocumentContent`.`state`=`tblWorkflowTransitions`.`state` ".
				"LEFT JOIN `tblWorkflowTransitionUsers` ON `tblWorkflowTransitionUsers`.`transition` = `tblWorkflowTransitions`.`id` ".
				"LEFT JOIN `tblWorkflowTransitionGroups` ON `tblWorkflowTransitionGroups`.`transition` = `tblWorkflowTransitions`.`id` ";

			if($user) {
				$queryStr .= "WHERE (`tblWorkflowTransitionUsers`.`userid` = ".$user->getID()." ";
				if($groups)
					$queryStr .= "OR `tblWorkflowTransitionGroups`.`groupid` IN (".implode(',', $groups).")";
				$queryStr .= ") ";
			}
			$queryStr .= "AND `tblDocumentStatusLog`.`status` = ".S_IN_WORKFLOW." ";
//			echo 'SELECT '.$selectStr." ".$queryStr;
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else $queryStr .= "ORDER BY `name`";
			} else {
			$queryStr .= "WHERE 1=1 ";
			// Get document list for the current user.
			$workflowStatus = $user->getWorkflowStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			$dList = array();
			foreach ($workflowStatus["u"] as $st) {
				if (!in_array($st["document"], $dList)) {
					$dList[] = $st["document"];
				}
			}
			foreach ($workflowStatus["g"] as $st) {
				if (!in_array($st["document"], $dList)) {
					$dList[] = $st["document"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$queryStr .=
							//"AND `tblDocumentStatusLog`.`status` IN (".S_IN_WORKFLOW.", ".S_EXPIRED.") ".
							"AND `tblDocuments`.`id` IN (" . $docCSV . ") ".
							"ORDER BY `statusDate` DESC";
			} else {
				$queryStr = '';
			}
			}
			break; // }}}
		case 'AppRevOwner': // Documents waiting for review/approval/revision I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			/** @noinspection PhpUndefinedConstantInspection */
			$queryStr .=	"AND `tblDocuments`.`owner` = '".$user->getID()."' ".
				"AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_DRAFT_APP.", ".S_IN_REVISION.") ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
//			$queryStr .= "AND `tblDocuments`.`owner` = '".$user->getID()."' ".
//				"AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_DRAFT_APP.") ".
//				"ORDER BY `statusDate` DESC";
			break; // }}}
		case 'ReceiveOwner': // Documents having a reception I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			//			$qs = 'SELECT DISTINCT `documentID` FROM `tblDocumentRecipients` LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentRecipients`.`version` AND `ttcontentid`.`document` = `tblDocumentRecipients`.`documentID`';
			// sql statement without older versions of a document
			$qs = 'SELECT DISTINCT `document` as `documentID` FROM `ttcontentid` a LEFT JOIN `tblDocumentRecipients` b on a.`document`=b.`documentID` AND a.`maxVersion`=b.`version` WHERE b.`receiptID` IS NOT NULL';
			$ra = $this->db->getResultArray($qs);
			if (is_bool($ra) && !$ra) {
				return false;
			}
			$docs = array();
			foreach($ra as $d) {
				$docs[] = $d['documentID'];
			}

			if ($docs) {
				$queryStr .= "AND `tblDocuments`.`id` IN (" . implode(',', $docs) . ") ";
				$queryStr .=	"AND `tblDocuments`.`owner` = '".$user->getID()."'";
				$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_RELEASED.") ";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			break; // }}}
		case 'NoReceiveOwner': // Documents *not* having a reception I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			//			$qs = 'SELECT DISTINCT `documentID` FROM `tblDocumentRecipients` LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentRecipients`.`version` AND `ttcontentid`.`document` = `tblDocumentRecipients`.`documentID`';
			// sql statement without older versions of a document
			$qs = 'SELECT DISTINCT `document` as `documentID` FROM `ttcontentid` a LEFT JOIN `tblDocumentRecipients` b on a.`document`=b.`documentID` AND a.`maxVersion`=b.`version` WHERE b.`receiptID` IS NULL';
			$ra = $this->db->getResultArray($qs);
			if (is_bool($ra) && !$ra) {
				return false;
			}
			$docs = array();
			foreach($ra as $d) {
				$docs[] = $d['documentID'];
			}

			if ($docs) {
				$queryStr .= "AND `tblDocuments`.`id` IN (" . implode(',', $docs) . ") ";
				$queryStr .=	"AND `tblDocuments`.`owner` = '".$user->getID()."' ".
					"AND `tblDocumentStatusLog`.`status` IN (".S_RELEASED.") ";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			break; // }}}
		case 'RejectOwner': // Documents that has been rejected and I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			$queryStr .= "AND `tblDocuments`.`owner` = '".$user->getID()."' ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_REJECTED.") ";
			//$queryStr .= "ORDER BY `statusDate` DESC";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		case 'LockedByMe': // Documents locked by me {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$qs = 'SELECT `document` FROM `tblDocumentLocks` WHERE `userID`='.$user->getID();
			$ra = $this->db->getResultArray($qs);
			if (is_bool($ra) && !$ra) {
				return false;
			}
			$docs = array();
			foreach($ra as $d) {
				$docs[] = $d['document'];
			}

			if ($docs) {
				$queryStr .= "AND `tblDocuments`.`id` IN (" . implode(',', $docs) . ") ";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			break; // }}}
		case 'ExpiredOwner': // Documents expired and owned by me {{{
			if(is_int($param2)) {
				$ts = mktime(0, 0, 0) + $param2 * 86400;
			} elseif(is_string($param2)) {
				$tmp = explode('-', $param2, 3);
				if(count($tmp) != 3)
					return false;
				if(!self::checkDate($param2, 'Y-m-d'))
					return false;
				$ts = mktime(0, 0, 0, $tmp[1], $tmp[2], $tmp[0]);
			} else
				$ts = mktime(0, 0, 0)-365*86400; /* Start of today - 1 year */

			$tsnow = mktime(0, 0, 0); /* Start of today */
			if($ts < $tsnow) { /* Check for docs expired in the past */
				$startts = $ts;
				$endts = $tsnow+86400; /* Use end of day */
			} else { /* Check for docs which will expire in the future */
				$startts = $tsnow;
				$endts = $ts+86400; /* Use end of day */
			}

			$queryStr .= 
				"WHERE `tblDocuments`.`expires` >= ".$startts." AND `tblDocuments`.`expires` <= ".$endts." ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			$queryStr .=	"AND `tblDocuments`.`owner` = '".$user->getID()."' ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		case 'ObsoleteOwner': // Documents that are obsolete and I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			$queryStr .= "AND `tblDocuments`.`owner` = '".$user->getID()."' ".
				"AND `tblDocumentStatusLog`.`status` IN (".S_OBSOLETE.") ";
			//$queryStr .= "ORDER BY `statusDate` DESC";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		case 'NeedsCorrectionOwner': // Documents that needs correction and I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			$queryStr .= "AND `tblDocuments`.`owner` = '".$user->getID()."' ".
				"AND `tblDocumentStatusLog`.`status` IN (".S_NEEDS_CORRECTION.") ";
			//$queryStr .= "ORDER BY `statusDate` DESC";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		case 'DraftOwner': // Documents in draft status and I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			$queryStr .= "AND `tblDocuments`.`owner` = '".$user->getID()."' ".
				"AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT.") ";
			//$queryStr .= "ORDER BY `statusDate` DESC";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		case 'WorkflowOwner': // Documents waiting for workflow trigger I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$queryStr .= "AND `tblDocuments`.`owner` = '".$user->getID()."' ".
				"AND `tblDocumentStatusLog`.`status` IN (".S_IN_WORKFLOW.") ".
				"ORDER BY `statusDate` DESC";
			break; // }}}
		case 'MyDocs': // Documents owned by me {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			$queryStr .=	"AND `tblDocuments`.`owner` = '".$user->getID()."' ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		case 'CheckedOutByMe': // Documents I have checked out {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$qs = 'SELECT `document` FROM `tblDocumentCheckOuts` WHERE `userID`='.$user->getID();
			$ra = $this->db->getResultArray($qs);
			if (is_bool($ra) && !$ra) {
				return false;
			}
			$docs = array();
			foreach($ra as $d) {
				$docs[] = $d['document'];
			}

			if ($docs) {
				$queryStr .= "AND `tblDocuments`.`id` IN (" . implode(',', $docs) . ") ";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			break; // }}}
		default: // {{{
			return false;
			break; // }}}
		}

		if($queryStr) {
			$resArr = $this->db->getResultArray('SELECT '.$selectStr.$queryStr);
			if (is_bool($resArr) && !$resArr) {
				return false;
			}
			/*
			$documents = array();
			foreach($resArr as $row)
				$documents[] = $this->getDocument($row["id"]);
			 */
		} else {
			return array();
		}

		return $resArr;
	} /* }}} */

	function makeTimeStamp($hour, $min, $sec, $year, $month, $day) { /* {{{ */
		$thirtyone = array (1, 3, 5, 7, 8, 10, 12);
		$thirty = array (4, 6, 9, 11);

		// Very basic check that the terms are valid. Does not fail for illegal
		// dates such as 31 Feb.
		if (!is_numeric($hour) || !is_numeric($min) || !is_numeric($sec) || !is_numeric($year) || !is_numeric($month) || !is_numeric($day) || $month<1 || $month>12 || $day<1 || $day>31 || $hour<0 || $hour>23 || $min<0 || $min>59 || $sec<0 || $sec>59) {
			return false;
		}
		$year = (int) $year;
		$month = (int) $month;
		$day = (int) $day;

		if(in_array($month, $thirtyone)) {
			$max=31;
		} elseif(in_array($month, $thirty)) {
			$max=30;
		} else {
			$max=(($year % 4 == 0) && ($year % 100 != 0 || $year % 400 == 0)) ? 29 : 28;
		}

		// Check again if day of month is valid in the given month
		if ($day>$max) {
			return false;
		}

		return mktime($hour, $min, $sec, $month, $day, $year);
	} /* }}} */

	/**
	 * Search the database for documents
	 *
	 * Note: the creation date will be used to check againts the
	 * date saved with the document
	 * or folder. The modification date will only be used for documents. It
	 * is checked against the creation date of the document content. This
	 * meanѕ that updateѕ of a document will only result in a searchable
	 * modification if a new version is uploaded.
	 *
	 * If the search is filtered by an expiration date, only documents with
	 * an expiration date will be found. Even if just an end date is given.
	 *
	 * dates, integers and floats fields are treated as ranges (expecting a 'from'
	 * and 'to' value) unless they have a value set.
	 *
	 * @param string $query seach query with space separated words
	 * @param integer $limit number of items in result set
	 * @param integer $offset index of first item in result set
	 * @param string $logicalmode either AND or OR
	 * @param array $searchin list of fields to search in
	 *        1 = keywords, 2=name, 3=comment, 4=attributes, 5=id
	 * @param SeedDMS_Core_Folder|null $startFolder search in the folder only (null for root folder)
	 * @param SeedDMS_Core_User $owner search for documents owned by this user
	 * @param array $status list of status
	 * @param array $creationstartdate search for documents created after this date
	 * @param array $creationenddate search for documents created before this date
	 * @param array $modificationstartdate search for documents modified after this date
	 * @param array $modificationenddate search for documents modified before this date
	 * @param array $categories list of categories the documents must have assigned
	 * @param array $attributes list of attributes. The key of this array is the
	 * attribute definition id. The value of the array is the value of the
	 * attribute. If the attribute may have multiple values it must be an array.
	 * attributes with a range must have the elements 'from' and 'to'
	 * @param integer $mode decide whether to search for documents/folders
	 *        0x1 = documents only
	 *        0x2 = folders only
	 *        0x3 = both
	 * @param array $expirationstartdate search for documents expiring after and on this date
	 * @param array $expirationenddate search for documents expiring before and on this date
	 * @return array|bool
	 */
	function search($query, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=null, $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x3, $expirationstartdate=array(), $expirationenddate=array(), $reception=array()) { /* {{{ */
		$orderby = '';
		$revisionstartdate = $revisionenddate = '';
		$statusstartdate = array();
		$statusenddate = array();
		if(is_array($query)) {
			foreach(array('limit', 'offset', 'logicalmode', 'searchin', 'startFolder', 'owner', 'status', 'creationstartdate', 'creationenddate', 'modificationstartdate', 'modificationenddate', 'categories', 'attributes', 'mode', 'revisionstartdate', 'revisionenddate', 'expirationstartdate', 'expirationenddate', 'reception') as $paramname)
				${$paramname} = isset($query[$paramname]) ? $query[$paramname] : ${$paramname};
			foreach(array('orderby', 'statusstartdate', 'statusenddate') as $paramname)
				${$paramname} = isset($query[$paramname]) ? $query[$paramname] : '';
			$query = isset($query['query']) ? $query['query'] : '';
		}
		/* Ensure $logicalmode has a valid value */
		if($logicalmode != 'OR')
			$logicalmode = 'AND';

		// Split the search string into constituent keywords.
		$tkeys=array();
		if (strlen($query)>0) {
			$tkeys = preg_split("/[\t\r\n ,]+/", $query);
		}

		// if none is checkd search all
		if (count($searchin)==0)
			$searchin=array(1, 2, 3, 4, 5);

		/*--------- Do it all over again for folders -------------*/
		$totalFolders = 0;
		if($mode & 0x2) {
			$searchKey = "";

			$classname = $this->classnames['folder'];
			$searchFields = $classname::getSearchFields($this, $searchin);

			if (count($searchFields)>0) {
				foreach ($tkeys as $key) {
					$key = trim($key);
					if (strlen($key)>0) {
						$searchKey = (strlen($searchKey)==0 ? "" : $searchKey." ".$logicalmode." ")."(".implode(" like ".$this->db->qstr("%".$key."%")." OR ", $searchFields)." like ".$this->db->qstr("%".$key."%").")";
					}
				}
			}

			// Check to see if the search has been restricted to a particular sub-tree in
			// the folder hierarchy.
			$searchFolder = "";
			if ($startFolder) {
				$searchFolder = "`tblFolders`.`folderList` LIKE '%:".$startFolder->getID().":%'";
				if($this->checkWithinRootDir)
					$searchFolder = '('.$searchFolder." AND `tblFolders`.`folderList` LIKE '%:".$this->rootFolderID.":%')";
			} elseif($this->checkWithinRootDir) {
				$searchFolder = "`tblFolders`.`folderList` LIKE '%:".$this->rootFolderID.":%'";
			}

			// Check to see if the search has been restricted to a particular
			// document owner.
			$searchOwner = "";
			if ($owner) {
				if(is_array($owner)) {
					$ownerids = array();
					foreach($owner as $o)
						$ownerids[] = $o->getID();
					if($ownerids)
						$searchOwner = "`tblFolders`.`owner` IN (".implode(',', $ownerids).")";
				} else {
					$searchOwner = "`tblFolders`.`owner` = '".$owner->getId()."'";
				}
			}

			// Check to see if the search has been restricted to a particular
			// attribute.
			$searchAttributes = array();
			if ($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					if($attribute) {
						$attrdef = $this->getAttributeDefinition($attrdefid);
						if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
							if($valueset = $attrdef->getValueSet()) {
								if(is_string($attribute))
									$attribute = array($attribute);
								foreach($attribute as &$v)
									$v = trim($this->db->qstr($v), "'");
								if($attrdef->getMultipleValues()) {
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND (`tblFolderAttributes`.`value` like '%".$valueset[0].implode("%' OR `tblFolderAttributes`.`value` like '%".$valueset[0], $attribute)."%') AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
								} else {
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND (`tblFolderAttributes`.`value`='".(is_array($attribute) ? implode("' OR `tblFolderAttributes`.`value` = '", $attribute) : $attribute)."') AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
								}
							} else {
								if(in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_date, SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]) && is_array($attribute)) {
									$kkll = [];
									if(!empty($attribute['from'])) {
										if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_int)
											$kkll[] = "CAST(`tblFolderAttributes`.`value` AS INTEGER)>=".(int) $attribute['from'];
										elseif($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_float)
											$kkll[] = "CAST(`tblFolderAttributes`.`value` AS DECIMAL)>=".(float) $attribute['from'];
										else
											$kkll[] = "`tblFolderAttributes`.`value`>=".$this->db->qstr($attribute['from']);
									}
									if(!empty($attribute['to'])) {
										if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_int)
											$kkll[] = "CAST(`tblFolderAttributes`.`value` AS INTEGER)<=".(int) $attribute['to'];
										elseif($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_float)
											$kkll[] = "CAST(`tblFolderAttributes`.`value` AS DECIMAL)<=".(float) $attribute['to'];
										else
											$kkll[] = "`tblFolderAttributes`.`value`<=".$this->db->qstr($attribute['to']);
									}
									if($kkll)
										$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND ".implode(' AND ', $kkll)." AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
								} elseif(is_string($attribute)) {
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND `tblFolderAttributes`.`value` like ".$this->db->qstr("%".$attribute."%")." AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
								}
							}
						}
					}
				}
			}

			// Is the search restricted to documents created between two specific dates?
			$searchCreateDate = "";
			if ($creationstartdate) {
				if(is_numeric($creationstartdate))
					$startdate = $creationstartdate;
				else
					$startdate = SeedDMS_Core_DMS::makeTimeStamp($creationstartdate['hour'], $creationstartdate['minute'], $creationstartdate['second'], $creationstartdate['year'], $creationstartdate["month"], $creationstartdate["day"]);
				if ($startdate) {
					$searchCreateDate .= "`tblFolders`.`date` >= ".$this->db->qstr($startdate);
				}
			}
			if ($creationenddate) {
				if(is_numeric($creationenddate))
					$stopdate = $creationenddate;
				else
					$stopdate = SeedDMS_Core_DMS::makeTimeStamp($creationenddate['hour'], $creationenddate['minute'], $creationenddate['second'], $creationenddate["year"], $creationenddate["month"], $creationenddate["day"]);
				if ($stopdate) {
					/** @noinspection PhpUndefinedVariableInspection */
					if($startdate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblFolders`.`date` <= ".$this->db->qstr($stopdate);
				}
			}

			$searchQuery = "FROM ".$classname::getSearchTables()." WHERE 1=1";

			if (strlen($searchKey)>0) {
				$searchQuery .= " AND (".$searchKey.")";
			}
			if (strlen($searchFolder)>0) {
				$searchQuery .= " AND ".$searchFolder;
			}
			if (strlen($searchOwner)>0) {
				$searchQuery .= " AND (".$searchOwner.")";
			}
			if (strlen($searchCreateDate)>0) {
				$searchQuery .= " AND (".$searchCreateDate.")";
			}
			if ($searchAttributes) {
				$searchQuery .= " AND (".implode(" AND ", $searchAttributes).")";
			}

			/* Do not search for folders if not at least a search for a key,
			 * an owner, or creation date is requested.
			 */
			if($searchKey || $searchOwner || $searchCreateDate || $searchAttributes) {
				// Count the number of rows that the search will produce.
				$resArr = $this->db->getResultArray("SELECT COUNT(*) AS num FROM (SELECT DISTINCT `tblFolders`.id ".$searchQuery.") a");
				if ($resArr && isset($resArr[0]) && is_numeric($resArr[0]["num"]) && $resArr[0]["num"]>0) {
					$totalFolders = (integer)$resArr[0]["num"];
				}

				// If there are no results from the count query, then there is no real need
				// to run the full query. TODO: re-structure code to by-pass additional
				// queries when no initial results are found.

				// Only search if the offset is not beyond the number of folders
				if($totalFolders > $offset) {
					// Prepare the complete search query, including the LIMIT clause.
					$searchQuery = "SELECT DISTINCT `tblFolders`.`id` ".$searchQuery." GROUP BY `tblFolders`.`id`";

					switch($orderby) {
					case 'dd':
						$searchQuery .= " ORDER BY `tblFolders`.`date` DESC";
						break;
					case 'da':
					case 'd':
						$searchQuery .= " ORDER BY `tblFolders`.`date`";
						break;
					case 'nd':
						$searchQuery .= " ORDER BY `tblFolders`.`name` DESC";
						break;
					case 'na':
					case 'n':
						$searchQuery .= " ORDER BY `tblFolders`.`name`";
						break;
					case 'id':
						$searchQuery .= " ORDER BY `tblFolders`.`id` DESC";
						break;
					case 'ia':
					case 'i':
						$searchQuery .= " ORDER BY `tblFolders`.`id`";
						break;
					default:
						break;
					}

					if($limit) {
						$searchQuery .= " LIMIT ".$limit." OFFSET ".$offset;
					}

					// Send the complete search query to the database.
					$resArr = $this->db->getResultArray($searchQuery);
				} else {
					$resArr = array();
				}

				// ------------------- Ausgabe der Ergebnisse ----------------------------
				$numResults = count($resArr);
				if ($numResults == 0) {
					$folderresult = array('totalFolders'=>$totalFolders, 'folders'=>array());
				} else {
					foreach ($resArr as $folderArr) {
						$folders[] = $this->getFolder($folderArr['id']);
					}
					/** @noinspection PhpUndefinedVariableInspection */
					$folderresult = array('totalFolders'=>$totalFolders, 'folders'=>$folders);
				}
			} else {
				$folderresult = array('totalFolders'=>0, 'folders'=>array());
			}
		} else {
			$folderresult = array('totalFolders'=>0, 'folders'=>array());
		}

		/*--------- Do it all over again for documents -------------*/

		$totalDocs = 0;
		if($mode & 0x1) {
			$searchKey = "";

			$classname = $this->classnames['document'];
			$searchFields = $classname::getSearchFields($this, $searchin);

			if (count($searchFields)>0) {
				foreach ($tkeys as $key) {
					$key = trim($key);
					if (strlen($key)>0) {
						$searchKey = (strlen($searchKey)==0 ? "" : $searchKey." ".$logicalmode." ")."(".implode(" like ".$this->db->qstr("%".$key."%")." OR ", $searchFields)." like ".$this->db->qstr("%".$key."%").")";
					}
				}
			}

			// Check to see if the search has been restricted to a particular sub-tree in
			// the folder hierarchy.
			$searchFolder = "";
			if ($startFolder) {
				$searchFolder = "`tblDocuments`.`folderList` LIKE '%:".$startFolder->getID().":%'";
				if($this->checkWithinRootDir)
					$searchFolder = '('.$searchFolder." AND `tblDocuments`.`folderList` LIKE '%:".$this->rootFolderID.":%')";
			} elseif($this->checkWithinRootDir) {
				$searchFolder = "`tblDocuments`.`folderList` LIKE '%:".$this->rootFolderID.":%'";
			}

			// Check to see if the search has been restricted to a particular
			// document owner.
			$searchOwner = "";
			if ($owner) {
				if(is_array($owner)) {
					$ownerids = array();
					foreach($owner as $o)
						$ownerids[] = $o->getID();
					if($ownerids)
						$searchOwner = "`tblDocuments`.`owner` IN (".implode(',', $ownerids).")";
				} else {
					$searchOwner = "`tblDocuments`.`owner` = '".$owner->getId()."'";
				}
			}

			// Check to see if the search has been restricted to a particular
			// document category.
			$searchCategories = "";
			if ($categories) {
				$catids = array();
				foreach($categories as $category)
					$catids[] = $category->getId();
				$searchCategories = "`tblDocumentCategory`.`categoryID` in (".implode(',', $catids).")";
			}

			// Check to see if the search has been restricted to a particular
			// attribute.
			$searchAttributes = array();
			if ($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					if($attribute) {
						$lsearchAttributes = [];
						$attrdef = $this->getAttributeDefinition($attrdefid);
						if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
							if($valueset = $attrdef->getValueSet()) {
								if(is_string($attribute))
									$attribute = array($attribute);
								foreach($attribute as &$v)
									$v = trim($this->db->qstr($v), "'");
								if($attrdef->getMultipleValues()) {
									$lsearchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND (`tblDocumentAttributes`.`value` like '%".$valueset[0].implode("%' OR `tblDocumentAttributes`.`value` like '%".$valueset[0], $attribute)."%') AND `tblDocumentAttributes`.`document` = `tblDocuments`.`id`)";
								} else {
									$lsearchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND (`tblDocumentAttributes`.`value`='".(is_array($attribute) ? implode("' OR `tblDocumentAttributes`.`value` = '", $attribute) : $attribute)."') AND `tblDocumentAttributes`.`document` = `tblDocuments`.`id`)";
								}
							} else {
								if(in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_date, SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]) && is_array($attribute)) {
									$kkll = [];
									if(!empty($attribute['from'])) {
										if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_int)
											$kkll[] = "CAST(`tblDocumentAttributes`.`value` AS INTEGER)>=".(int) $attribute['from'];
										elseif($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_float)
											$kkll[] = "CAST(`tblDocumentAttributes`.`value` AS DECIMAL)>=".(float) $attribute['from'];
										else
											$kkll[] = "`tblDocumentAttributes`.`value`>=".$this->db->qstr($attribute['from']);
									}
									if(!empty($attribute['to'])) {
										if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_int)
											$kkll[] = "CAST(`tblDocumentAttributes`.`value` AS INTEGER)<=".(int) $attribute['to'];
										elseif($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_float)
											$kkll[] = "CAST(`tblDocumentAttributes`.`value` AS DECIMAL)<=".(float) $attribute['to'];
										else
											$kkll[] = "`tblDocumentAttributes`.`value`<=".$this->db->qstr($attribute['to']);
									}
									if($kkll)
										$lsearchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND ".implode(' AND ', $kkll)." AND `tblDocumentAttributes`.`document`=`tblDocuments`.`id`)";
								} else {
									$lsearchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentAttributes`.`value` like ".$this->db->qstr("%".$attribute."%")." AND `tblDocumentAttributes`.`document` = `tblDocuments`.`id`)";
								}
							}
						}
						if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
							if($valueset = $attrdef->getValueSet()) {
								if(is_string($attribute))
									$attribute = array($attribute);
								foreach($attribute as &$v)
									$v = trim($this->db->qstr($v), "'");
								if($attrdef->getMultipleValues()) {
									$lsearchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND (`tblDocumentContentAttributes`.`value` like '%".$valueset[0].implode("%' OR `tblDocumentContentAttributes`.`value` like '%".$valueset[0], $attribute)."%') AND `tblDocumentContentAttributes`.`content` = `tblDocumentContent`.`id`)";
								} else {
									$lsearchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND (`tblDocumentContentAttributes`.`value`='".(is_array($attribute) ? implode("' OR `tblDocumentContentAttributes`.`value` = '", $attribute) : $attribute)."') AND `tblDocumentContentAttributes`.content = `tblDocumentContent`.id)";
								}
							} else {
								if(in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_date, SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]) && is_array($attribute)) {
									$kkll = [];
									if(!empty($attribute['from'])) {
										if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_int)
											$kkll[] = "CAST(`tblDocumentContentAttributes`.`value` AS INTEGER)>=".(int) $attribute['from'];
										elseif($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_float)
											$kkll[] = "CAST(`tblDocumentContentAttributes`.`value` AS DECIMAL)>=".(float) $attribute['from'];
										else
											$kkll[] = "`tblDocumentContentAttributes`.`value`>=".$this->db->qstr($attribute['from']);
									}
									if(!empty($attribute['to'])) {
										if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_int)
											$kkll[] = "CAST(`tblDocumentContentAttributes`.`value` AS INTEGER)<=".(int) $attribute['to'];
										elseif($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_float)
											$kkll[] = "CAST(`tblDocumentContentAttributes`.`value` AS DECIMAL)<=".(float) $attribute['to'];
										else
											$kkll[] = "`tblDocumentContentAttributes`.`value`<=".$this->db->qstr($attribute['to']);
									}
									if($kkll)
										$lsearchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND ".implode(' AND ', $kkll)." AND `tblDocumentContentAttributes`.`content`=`tblDocumentContent`.`id`)";
								} else {
									$lsearchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentContentAttributes`.`value` like ".$this->db->qstr("%".$attribute."%")." AND `tblDocumentContentAttributes`.content = `tblDocumentContent`.id)";
								}
							}
						}
						if($lsearchAttributes)
							$searchAttributes[] = "(".implode(" OR ", $lsearchAttributes).")";
					}
				}
			}

			// Is the search restricted to documents created between two specific dates?
			$searchCreateDate = "";
			if ($creationstartdate) {
				if(is_numeric($creationstartdate))
					$startdate = $creationstartdate;
				else
					$startdate = SeedDMS_Core_DMS::makeTimeStamp($creationstartdate['hour'], $creationstartdate['minute'], $creationstartdate['second'], $creationstartdate['year'], $creationstartdate["month"], $creationstartdate["day"]);
				if ($startdate) {
					$searchCreateDate .= "`tblDocuments`.`date` >= ".$this->db->qstr($startdate);
				}
			}
			if ($creationenddate) {
				if(is_numeric($creationenddate))
					$stopdate = $creationenddate;
				else
					$stopdate = SeedDMS_Core_DMS::makeTimeStamp($creationenddate['hour'], $creationenddate['minute'], $creationenddate['second'], $creationenddate["year"], $creationenddate["month"], $creationenddate["day"]);
				if ($stopdate) {
					if($searchCreateDate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblDocuments`.`date` <= ".$this->db->qstr($stopdate);
				}
			}

			if ($modificationstartdate) {
				if(is_numeric($modificationstartdate))
					$startdate = $modificationstartdate;
				else
					$startdate = SeedDMS_Core_DMS::makeTimeStamp($modificationstartdate['hour'], $modificationstartdate['minute'], $modificationstartdate['second'], $modificationstartdate['year'], $modificationstartdate["month"], $modificationstartdate["day"]);
				if ($startdate) {
					if($searchCreateDate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblDocumentContent`.`date` >= ".$startdate;
				}
			}
			if ($modificationenddate) {
				if(is_numeric($modificationenddate))
					$stopdate = $modificationenddate;
				else
					$stopdate = SeedDMS_Core_DMS::makeTimeStamp($modificationenddate['hour'], $modificationenddate['minute'], $modificationenddate['second'], $modificationenddate["year"], $modificationenddate["month"], $modificationenddate["day"]);
				if ($stopdate) {
					if($searchCreateDate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblDocumentContent`.`date` <= ".$this->db->qstr($stopdate);
				}
			}
			$searchRevisionDate = "";
			if ($revisionstartdate) {
				$startdate = sprintf('%04d-%02d-%02d', $revisionstartdate['year'], $revisionstartdate["month"], $revisionstartdate["day"]);
				if ($startdate) {
					if($searchRevisionDate)
						$searchRevisionDate .= " AND ";
					$searchRevisionDate .= "`tblDocumentContent`.`revisiondate` >= '".$startdate."'";
				}
			}
			if ($revisionenddate) {
				$stopdate = sprintf('%04d-%02d-%02d', $revisionenddate["year"], $revisionenddate["month"], $revisionenddate["day"]);
				if ($stopdate) {
					if($searchRevisionDate)
						$searchRevisionDate .= " AND ";
					$searchRevisionDate .= "`tblDocumentContent`.`revisiondate` <= '".$stopdate."'";
				}
			}
			$searchExpirationDate = '';
			if ($expirationstartdate) {
				$startdate = SeedDMS_Core_DMS::makeTimeStamp($expirationstartdate['hour'], $expirationstartdate['minute'], $expirationstartdate['second'], $expirationstartdate['year'], $expirationstartdate["month"], $expirationstartdate["day"]);
				if ($startdate) {
					$searchExpirationDate .= "`tblDocuments`.`expires` >= ".$this->db->qstr($startdate);
				}
			}
			if ($expirationenddate) {
				$stopdate = SeedDMS_Core_DMS::makeTimeStamp($expirationenddate['hour'], $expirationenddate['minute'], $expirationenddate['second'], $expirationenddate["year"], $expirationenddate["month"], $expirationenddate["day"]);
				if ($stopdate) {
					if($searchExpirationDate)
						$searchExpirationDate .= " AND ";
					else // do not find documents without an expiration date
						$searchExpirationDate .= "`tblDocuments`.`expires` != 0 AND ";
					$searchExpirationDate .= "`tblDocuments`.`expires` <= ".$this->db->qstr($stopdate);
				}
			}
			$searchStatusDate = '';
			if ($statusstartdate) {
				$startdate = $statusstartdate['year'].'-'.$statusstartdate["month"].'-'.$statusstartdate["day"].' '.$statusstartdate['hour'].':'.$statusstartdate['minute'].':'.$statusstartdate['second'];
				if ($startdate) {
					if($searchStatusDate)
						$searchStatusDate .= " AND ";
					$searchStatusDate .= "`tblDocumentStatusLog`.`date` >= ".$this->db->qstr($startdate);
				}
			}
			if ($statusenddate) {
				$stopdate = $statusenddate['year'].'-'.$statusenddate["month"].'-'.$statusenddate["day"].' '.$statusenddate['hour'].':'.$statusenddate['minute'].':'.$statusenddate['second'];
				if ($stopdate) {
					if($searchStatusDate)
						$searchStatusDate .= " AND ";
					$searchStatusDate .= "`tblDocumentStatusLog`.`date` <= ".$this->db->qstr($stopdate);
				}
			}

			// ---------------------- Suche starten ----------------------------------

			//
			// Construct the SQL query that will be used to search the database.
			//

			if (!$this->db->createTemporaryTable("ttcontentid") || !$this->db->createTemporaryTable("ttstatid")) {
				return false;
			}
			if($reception) {
				if (!$this->db->createTemporaryTable("ttreceiptid")) {
					return false;
				}
			}

			$searchQuery = "FROM `tblDocuments` ".
				"LEFT JOIN `tblDocumentContent` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
				"LEFT JOIN `tblDocumentAttributes` ON `tblDocuments`.`id` = `tblDocumentAttributes`.`document` ".
				"LEFT JOIN `tblDocumentContentAttributes` ON `tblDocumentContent`.`id` = `tblDocumentContentAttributes`.`content` ".
				"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
				"LEFT JOIN `ttstatid` ON `ttstatid`.`statusID` = `tblDocumentStatus`.`statusID` ".
				"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusLogID` = `ttstatid`.`maxLogID` ".
				"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
				"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
				"LEFT JOIN `tblDocumentCategory` ON `tblDocuments`.`id`=`tblDocumentCategory`.`documentID` ".
//				"LEFT JOIN `tblDocumentRecipients` ON `tblDocuments`.`id`=`tblDocumentRecipients`.`documentID` ".
//				"LEFT JOIN `tblDocumentReceiptLog` ON `tblDocumentRecipients`.`receiptID`=`tblDocumentReceiptLog`.`receiptID` ".
//				"LEFT JOIN `ttreceiptid` ON `ttreceiptid`.`maxLogID` = `tblDocumentReceiptLog`.`receiptLogID` ".
				"WHERE ".
//				"`ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` AND ".
//				"`ttreceiptid`.`maxLogID`=`tblDocumentReceiptLog`.`receiptLogID` AND ".
				"`ttcontentid`.`maxVersion` = `tblDocumentContent`.`version`";

			if (strlen($searchKey)>0) {
				$searchQuery .= " AND (".$searchKey.")";
			}
			if (strlen($searchFolder)>0) {
				$searchQuery .= " AND ".$searchFolder;
			}
			if (strlen($searchOwner)>0) {
				$searchQuery .= " AND (".$searchOwner.")";
			}
			if (strlen($searchCategories)>0) {
				$searchQuery .= " AND (".$searchCategories.")";
			}
			if (strlen($searchCreateDate)>0) {
				$searchQuery .= " AND (".$searchCreateDate.")";
			}
			if (strlen($searchRevisionDate)>0) {
				$searchQuery .= " AND (".$searchRevisionDate.")";
			}
			if (strlen($searchExpirationDate)>0) {
				$searchQuery .= " AND (".$searchExpirationDate.")";
			}
			if (strlen($searchStatusDate)>0) {
				$searchQuery .= " AND (".$searchStatusDate.")";
			}
			if ($searchAttributes) {
				$searchQuery .= " AND (".implode(" AND ", $searchAttributes).")";
			}

			// status
			if ($status) {
				$searchQuery .= " AND `tblDocumentStatusLog`.`status` IN (".implode(',', $status).")";
			}

			if($reception) {
				$searchReception = array();
				/* still waiting for users/groups to acknownledge reception */
				if(in_array("missingaction", $reception))
					$searchReception[] = "b.`status` IN (0)";
				/* document has not been acknowledeged by at least one user/group */
				if(in_array("hasrejection", $reception))
					$searchReception[] = "b.`status` IN (-1)";
				/* document has been acknowledeged by at least one user/group */
				if(in_array("hasacknowledge", $reception))
					$searchReception[] = "b.`status` IN (1)";
				/* document has been acknowledeged by all users/groups !!! not working !!! */
				if(in_array("completeacknowledge", $reception))
					$searchReception[] = "b.`status` NOT IN (-1, 0)";
				if($searchReception) {
					$searchQuery .= " AND EXISTS (SELECT NULL FROM `tblDocumentRecipients` a LEFT JOIN `tblDocumentReceiptLog` b ON a.`receiptID`=b.`receiptID` LEFT JOIN `ttreceiptid` c ON c.`maxLogID` = b.`receiptLogID` WHERE ";
					$searchQuery .= "c.`maxLogID`=b.`receiptLogID` AND `tblDocuments`.`id` = a.`documentID` ";
					$searchQuery .= "AND (".implode(' OR ', $searchReception)."))";
				}
			}

			if($searchKey || $searchOwner || $searchCategories || $searchCreateDate || $searchRevisionDate || $searchExpirationDate || $searchStatusDate || $searchAttributes || $status) {
				// Count the number of rows that the search will produce.
				$resArr = $this->db->getResultArray("SELECT COUNT(*) AS num FROM (SELECT DISTINCT `tblDocuments`.`id` ".$searchQuery.") a");
				$totalDocs = 0;
				if (is_numeric($resArr[0]["num"]) && $resArr[0]["num"]>0) {
					$totalDocs = (integer)$resArr[0]["num"];
				}

				// If there are no results from the count query, then there is no real need
				// to run the full query. TODO: re-structure code to by-pass additional
				// queries when no initial results are found.

				// Prepare the complete search query, including the LIMIT clause.
				$searchQuery = "SELECT DISTINCT `tblDocuments`.*, ".
					"`tblDocumentContent`.`version`, ".
					"`tblDocumentStatusLog`.`status`, `tblDocumentLocks`.`userID` as `lockUser` ".$searchQuery;

				switch($orderby) {
				case 'dd':
					$orderbyQuery = " ORDER BY `tblDocuments`.`date` DESC";
					break;
				case 'da':
				case 'd':
					$orderbyQuery = " ORDER BY `tblDocuments`.`date`";
					break;
				case 'nd':
					$orderbyQuery = " ORDER BY `tblDocuments`.`name` DESC";
					break;
				case 'na':
				case 'n':
					$orderbyQuery = " ORDER BY `tblDocuments`.`name`";
					break;
				case 'id':
					$orderbyQuery = " ORDER BY `tblDocuments`.`id` DESC";
					break;
				case 'ia':
				case 'i':
					$orderbyQuery = " ORDER BY `tblDocuments`.`id`";
					break;
				default:
					$orderbyQuery = "";
					break;
				}

				// calculate the remaining entrїes of the current page
				// If page is not full yet, get remaining entries
				if($limit) {
					$remain = $limit - count($folderresult['folders']);
					if($remain) {
						if($remain == $limit)
							$offset -= $totalFolders;
						else
							$offset = 0;

						$searchQuery .= $orderbyQuery;

						if($limit)
							$searchQuery .= " LIMIT ".$limit." OFFSET ".$offset;

						// Send the complete search query to the database.
						$resArr = $this->db->getResultArray($searchQuery);
						if($resArr === false)
							return false;
					} else {
						$resArr = array();
					}
				} else {
					$searchQuery .= $orderbyQuery;

					// Send the complete search query to the database.
					$resArr = $this->db->getResultArray($searchQuery);
					if($resArr === false)
						return false;
				}

				// ------------------- Ausgabe der Ergebnisse ----------------------------
				$numResults = count($resArr);
				if ($numResults == 0) {
					$docresult = array('totalDocs'=>$totalDocs, 'docs'=>array());
				} else {
					foreach ($resArr as $docArr) {
						$docs[] = $this->getDocument($docArr['id']);
					}
					/** @noinspection PhpUndefinedVariableInspection */
					$docresult = array('totalDocs'=>$totalDocs, 'docs'=>$docs);
				}
			} else {
				$docresult = array('totalDocs'=>0, 'docs'=>array());
			}
		} else {
			$docresult = array('totalDocs'=>0, 'docs'=>array());
		}

		if($limit) {
			$totalPages = (integer)(($totalDocs+$totalFolders)/$limit);
			if ((($totalDocs+$totalFolders)%$limit) > 0) {
				$totalPages++;
			}
		} else {
			$totalPages = 1;
		}

		return array_merge($docresult, $folderresult, array('totalPages'=>$totalPages));
	} /* }}} */

	/**
	 * Return a folder by its id
	 *
	 * This function retrieves a folder from the database by its id.
	 *
	 * @param integer $id internal id of folder
	 * @return SeedDMS_Core_Folder instance of SeedDMS_Core_Folder or false
	 */
	function getFolder($id) { /* {{{ */
		$classname = $this->classnames['folder'];
		return $classname::getInstance($id, $this);
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
	function getFolderByName($name, $folder=null) { /* {{{ */
		$name = trim($name);
		$classname = $this->classnames['folder'];
		return $classname::getInstanceByName($name, $folder, $this);
	} /* }}} */

	/**
	 * Returns a list of folders and error message not linked in the tree
	 *
	 * This function checks all folders in the database.
	 *
	 * @return array|bool
	 */
	function checkFolders() { /* {{{ */
		$queryStr = "SELECT * FROM `tblFolders`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false)
			return false;

		$cache = array();
		foreach($resArr as $rec) {
			$cache[$rec['id']] = array('name'=>$rec['name'], 'parent'=>$rec['parent'], 'folderList'=>$rec['folderList']);
		}
		$errors = array();
		foreach($cache as $id=>$rec) {
			if(!array_key_exists($rec['parent'], $cache) && $rec['parent'] != 0) {
				$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Missing parent');
			}
			if(!isset($errors[$id]))	{
				/* Create the real folderList and compare it with the stored folderList */
				$parent = $rec['parent'];
				$fl = [];
				while($parent) {
					array_unshift($fl, $parent);
					$parent = $cache[$parent]['parent'];
				}
				if($fl)
					$flstr = ':'.implode(':', $fl).':';
				else
					$flstr = '';
				if($flstr != $rec['folderList'])
					$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Wrong folder list '.$flstr.'!='.$rec['folderList']);
			}
			if(!isset($errors[$id]))	{
				/* This is the old insufficient test which will most likely not be called
				 * anymore, because the check for a wrong folder list will cache a folder
				 * list problem anyway.
				 */
				$tmparr = explode(':', $rec['folderList']);
				array_shift($tmparr);
				if(count($tmparr) != count(array_unique($tmparr))) {
					$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Duplicate entry in folder list ('.$rec['folderList'].')');
				}
			}
		}

		return $errors;
	} /* }}} */

	/**
	 * Returns a list of documents and error message not linked in the tree
	 *
	 * This function checks all documents in the database.
	 *
	 * @return array|bool
	 */
	function checkDocuments() { /* {{{ */
		$queryStr = "SELECT * FROM `tblFolders`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false)
			return false;

		$fcache = array();
		foreach($resArr as $rec) {
			$fcache[$rec['id']] = array('name'=>$rec['name'], 'parent'=>$rec['parent'], 'folderList'=>$rec['folderList']);
		}

		$queryStr = "SELECT * FROM `tblDocuments`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false)
			return false;

		$dcache = array();
		foreach($resArr as $rec) {
			$dcache[$rec['id']] = array('name'=>$rec['name'], 'parent'=>$rec['folder'], 'folderList'=>$rec['folderList']);
		}
		$errors = array();
		foreach($dcache as $id=>$rec) {
			if(!array_key_exists($rec['parent'], $fcache) && $rec['parent'] != 0) {
				$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Missing parent');
			}
			if(!isset($errors[$id]))	{
				/* Create the real folderList and compare it with the stored folderList */
				$parent = $rec['parent'];
				$fl = [];
				while($parent) {
					array_unshift($fl, $parent);
					$parent = $fcache[$parent]['parent'];
				}
				if($fl)
					$flstr = ':'.implode(':', $fl).':';
				if($flstr != $rec['folderList'])
					$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Wrong folder list '.$flstr.'!='.$rec['folderList']);
			}
			if(!isset($errors[$id]))	{
				$tmparr = explode(':', $rec['folderList']);
				array_shift($tmparr);
				if(count($tmparr) != count(array_unique($tmparr))) {
					$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Duplicate entry in folder list ('.$rec['folderList'].'');
				}
			}
		}

		return $errors;
	} /* }}} */

	/**
	 * Return a user by its id
	 *
	 * This function retrieves a user from the database by its id.
	 *
	 * @param integer $id internal id of user
	 * @return SeedDMS_Core_User|boolean instance of {@link SeedDMS_Core_User} or false
	 */
	function getUser($id) { /* {{{ */
		if($this->usecache && isset($this->cache['users'][$id])) {
			return $this->cache['users'][$id];
		}
		$classname = $this->classnames['user'];
		$user = $classname::getInstance($id, $this);
		if($this->usecache)
			$this->cache['users'][$id] = $user;
		return $user;
	} /* }}} */

	/**
	 * Return a user by its login
	 *
	 * This function retrieves a user from the database by its login.
	 * If the second optional parameter $email is not empty, the user must
	 * also have the given email.
	 *
	 * @param string $login internal login of user
	 * @param string $email email of user
	 * @return object instance of {@link SeedDMS_Core_User} or false
	 */
	function getUserByLogin($login, $email='') { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getInstance($login, $this, 'name', $email);
	} /* }}} */

	/**
	 * Return a user by its email
	 *
	 * This function retrieves a user from the database by its email.
	 * It is needed when the user requests a new password.
	 *
	 * @param integer $email email address of user
	 * @return object instance of {@link SeedDMS_Core_User} or false
	 */
	function getUserByEmail($email) { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getInstance($email, $this, 'email');
	} /* }}} */

	/**
	 * Return list of all users
	 *
	 * @param string $orderby
	 * @return array of instances of <a href='psi_element://SeedDMS_Core_User'>SeedDMS_Core_User</a> or false
	 * or false
	 */
	function getAllUsers($orderby = '') { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getAllInstances($orderby, $this);
	} /* }}} */

	/**
	 * Add a new user
	 *
	 * @param string $login login name
	 * @param string $pwd password of new user
	 * @param $fullName
	 * @param string $email Email of new user
	 * @param string $language language of new user
	 * @param $theme
	 * @param string $comment comment of new user
	 * @param int|string $role role of new user (can be 0=normal, 1=admin, 2=guest)
	 * @param integer $isHidden hide user in all lists, if this is set login
	 *        is still allowed
	 * @param integer $isDisabled disable user and prevent login
	 * @param string $pwdexpiration
	 * @param int $quota
	 * @param null $homefolder
	 * @return bool|SeedDMS_Core_User
	 */
	function addUser($login, $pwd, $fullName, $email, $language, $theme, $comment, $role='3', $isHidden=0, $isDisabled=0, $pwdexpiration='', $quota=0, $homefolder=null) { /* {{{ */
		$db = $this->db;
		if (is_object($this->getUserByLogin($login))) {
			return false;
		}
		if(!is_object($role)) {
		  if($role == '')
              $role = SeedDMS_Core_Role::getInstance(3, $this);
          else
              $role = SeedDMS_Core_Role::getInstance($role, $this);
        }
		if(trim($pwdexpiration) == '' || trim($pwdexpiration) == 'never') {
			$pwdexpiration = 'NULL';
		} elseif(trim($pwdexpiration) == 'now') {
			$pwdexpiration = $db->qstr(date('Y-m-d H:i:s'));
		} else {
			$pwdexpiration = $db->qstr($pwdexpiration);
		}
		$queryStr = "INSERT INTO `tblUsers` (`login`, `pwd`, `fullName`, `email`, `language`, `theme`, `comment`, `role`, `hidden`, `disabled`, `pwdExpiration`, `quota`, `homefolder`) VALUES (".$db->qstr($login).", ".$db->qstr($pwd).", ".$db->qstr($fullName).", ".$db->qstr($email).", '".$language."', '".$theme."', ".$db->qstr($comment).", '".intval($role->getId())."', '".intval($isHidden)."', '".intval($isDisabled)."', ".$pwdexpiration.", '".intval($quota)."', ".($homefolder ? intval($homefolder) : "NULL").")";
		$res = $this->db->getResult($queryStr);
		if (!$res)
			return false;

		$user = $this->getUser($this->db->getInsertID('tblUsers'));

		/* Check if 'onPostAddUser' callback is set */
		if(isset($this->callbacks['onPostAddUser'])) {
			foreach($this->callbacks['onPostAddUser'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $user)) {
				}
			}
		}

		return $user;
	} /* }}} */

	/**
	 * Get a group by its id
	 *
	 * @param integer $id id of group
	 * @return SeedDMS_Core_Group|boolean group or false if no group was found
	 */
	function getGroup($id) { /* {{{ */
		if($this->usecache && isset($this->cache['groups'][$id])) {
			return $this->cache['groups'][$id];
		}
		$classname = $this->classnames['group'];
		$group = $classname::getInstance($id, $this, '');
		if($this->usecache)
			$this->cache['groups'][$id] = $group;
		return $group;
	} /* }}} */

	/**
	 * Get a group by its name
	 *
	 * @param string $name name of group
	 * @return SeedDMS_Core_Group|boolean group or false if no group was found
	 */
	function getGroupByName($name) { /* {{{ */
		$name = trim($name);
		$classname = $this->classnames['group'];
		return $classname::getInstance($name, $this, 'name');
	} /* }}} */

	/**
	 * Get a list of all groups
	 *
	 * @return SeedDMS_Core_Group[] array of instances of {@link SeedDMS_Core_Group}
	 */
	function getAllGroups() { /* {{{ */
		$classname = $this->classnames['group'];
		return $classname::getAllInstances('name', $this);
	} /* }}} */

	/**
	 * Create a new user group
	 *
	 * @param string $name name of group
	 * @param string $comment comment of group
	 * @return SeedDMS_Core_Group|boolean instance of {@link SeedDMS_Core_Group} or false in
	 *         case of an error.
	 */
	function addGroup($name, $comment) { /* {{{ */
		$name = trim($name);
		if (is_object($this->getGroupByName($name))) {
			return false;
		}

		$queryStr = "INSERT INTO `tblGroups` (`name`, `comment`) VALUES (".$this->db->qstr($name).", ".$this->db->qstr($comment).")";
		if (!$this->db->getResult($queryStr))
			return false;

		$group = $this->getGroup($this->db->getInsertID('tblGroups'));

		/* Check if 'onPostAddGroup' callback is set */
		if(isset($this->callbacks['onPostAddGroup'])) {
			foreach($this->callbacks['onPostAddGroup'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $group)) {
				}
			}
		}

		return $group;
	} /* }}} */

	/**
	 * Get a role by its id
	 *
	 * @param integer $id id of role
	 * @return object/boolean role or false if no role was found
	 */
	function getRole($id) { /* {{{ */
		$classname = $this->classnames['role'];
		return $classname::getInstance($id, $this);
	} /* }}} */

	/**
	 * Get a role by its name
	 *
	 * @param integer $name name of role
	 * @return object/boolean role or false if no role was found
	 */
	function getRoleByName($name) { /* {{{ */
		$classname = $this->classnames['role'];
		return $classname::getInstance($name, $this, 'name');
	} /* }}} */

	/**
	 * Return list of all roles
	 *
	 * @return array of instances of {@link SeedDMS_Core_Role} or false
	 */
	function getAllRoles($orderby = '') { /* {{{ */
		$classname = $this->classnames['role'];
		return $classname::getAllInstances($orderby, $this);
	} /* }}} */

	/**
	 * Create a new role
	 *
	 * @param string $name name of role
	 * @return object/boolean instance of {@link SeedDMS_Core_Role} or false in
	 *         case of an error.
	 */
	function addRole($name, $role) { /* {{{ */
		if (is_object($this->getRoleByName($name))) {
			return false;
		}

		$queryStr = "INSERT INTO `tblRoles` (`name`, `role`) VALUES (".$this->db->qstr($name).", ".$role.")";
		if (!$this->db->getResult($queryStr))
			return false;

		return $this->getRole($this->db->getInsertID('tblRoles'));
	} /* }}} */

	/**
	 * Get a transmittal by its id
	 *
	 * @param integer $id id of transmittal
	 * @return object/boolean transmittal or false if no group was found
	 */
	function getTransmittal($id) { /* {{{ */
		$classname = $this->classnames['transmittal'];
		return $classname::getInstance($id, $this, '');
	} /* }}} */

	/**
	 * Get a transmittal by its name
	 *
	 * @param string $name name of transmittal
	 * @return object/boolean transmittal or false if no group was found
	 */
	function getTransmittalByName($name) { /* {{{ */
		$classname = $this->classnames['transmittal'];
		return $classname::getInstance($name, $this, 'name');
	} /* }}} */

	/**
	 * Return list of all transmittals
	 *
	 * @return array of instances of {@link SeedDMS_Core_Transmittal} or false
	 */
	function getAllTransmittals($user=null, $orderby = '') { /* {{{ */
		$classname = $this->classnames['transmittal'];
		return $classname::getAllInstances($user, $orderby, $this);
	} /* }}} */

	/**
	 * Create a new transmittal
	 *
	 * @param string $name name of group
	 * @param string $comment comment of group
	 * @param object $user user this transmittal belongs to
	 * @return object/boolean instance of {@link SeedDMS_Core_Transmittal} or
	 *         false in case of an error.
	 */
	function addTransmittal($name, $comment, $user) { /* {{{ */
		if (is_object($this->getTransmittalByName($name))) {
			return false;
		}

		$queryStr = "INSERT INTO `tblTransmittals` (`name`, `comment`, `userID`) VALUES (".$this->db->qstr($name).", ".$this->db->qstr($comment).", ".$user->getID().")";
		if (!$this->db->getResult($queryStr))
			return false;

		return $this->getTransmittal($this->db->getInsertID('tblTransmittals'));
	} /* }}} */

	function getKeywordCategory($id) { /* {{{ */
		if (!is_numeric($id) || $id < 1)
			return false;

		$queryStr = "SELECT * FROM `tblKeywordCategories` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		if (count($resArr) != 1)
			return null;

		$resArr = $resArr[0];
		$cat = new SeedDMS_Core_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getKeywordCategoryByName($name, $userID) { /* {{{ */
		if (!is_numeric($userID) || $userID < 1)
			return false;
		$name = trim($name);
		$queryStr = "SELECT * FROM `tblKeywordCategories` WHERE `name` = " . $this->db->qstr($name) . " AND `owner` = " . (int) $userID;
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		if (count($resArr) != 1)
			return null;

		$resArr = $resArr[0];
		$cat = new SeedDMS_Core_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getAllKeywordCategories($userIDs = array()) { /* {{{ */
		$queryStr = "SELECT * FROM `tblKeywordCategories`";
		/* Ensure $userIDs() will only contain integers > 0 */
		$userIDs = array_filter(array_unique(array_map('intval', $userIDs)), function($a) {return $a > 0;});
		if ($userIDs) {
			$queryStr .= " WHERE `owner` IN (".implode(',', $userIDs).")";
		}

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new SeedDMS_Core_KeywordCategory($row["id"], $row["owner"], $row["name"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	/**
	 * This function should be replaced by getAllKeywordCategories()
	 *
	 * @param $userID
	 * @return SeedDMS_Core_KeywordCategory[]|bool
	 */
	function getAllUserKeywordCategories($userID) { /* {{{ */
		if (!is_numeric($userID) || $userID < 1)
			return false;
		return self::getAllKeywordCategories([$userID]);
	} /* }}} */

	function addKeywordCategory($userID, $name) { /* {{{ */
		if (!is_numeric($userID) || $userID < 1)
			return false;
		$name = trim($name);
		if(!$name)
			return false;
		if (is_object($this->getKeywordCategoryByName($name, $userID))) {
			return false;
		}
		$queryStr = "INSERT INTO `tblKeywordCategories` (`owner`, `name`) VALUES (".(int) $userID.", ".$this->db->qstr($name).")";
		if (!$this->db->getResult($queryStr))
			return false;

		$category = $this->getKeywordCategory($this->db->getInsertID('tblKeywordCategories'));

		/* Check if 'onPostAddKeywordCategory' callback is set */
		if(isset($this->callbacks['onPostAddKeywordCategory'])) {
			foreach($this->callbacks['onPostAddKeywordCategory'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $category)) {
				}
			}
		}

		return $category;
	} /* }}} */

	function getDocumentCategory($id) { /* {{{ */
		if (!is_numeric($id) || $id < 1)
			return false;

		$queryStr = "SELECT * FROM `tblCategory` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		
        if (is_bool($resArr) && !$resArr)
			return false;
        if (count($resArr) != 1)
			return null;

		$resArr = $resArr[0];
		$cat = new SeedDMS_Core_DocumentCategory($resArr["id"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getDocumentCategories() { /* {{{ */
		$queryStr = "SELECT * FROM `tblCategory` order by `name`";

		$resArr = $this->db->getResultArray($queryStr);
		
		if (is_bool($resArr) && !$resArr) 
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new SeedDMS_Core_DocumentCategory($row["id"], $row["name"],$row["position"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	/**
	 * Get a category by its name
	 *
	 * The name of a category is by default unique.
	 *
	 * @param string $name human readable name of category
	 * @return SeedDMS_Core_DocumentCategory|boolean instance of {@link SeedDMS_Core_DocumentCategory}
	 */
	function getDocumentCategoryByName($name) { /* {{{ */
		$name = trim($name);
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblCategory` WHERE `name`=".$this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);
		if (!$resArr)
			return false;

		$row = $resArr[0];
		$cat = new SeedDMS_Core_DocumentCategory($row["id"], $row["name"]);
		$cat->setDMS($this);

		return $cat;
	} /* }}} */

	function addDocumentCategory($name,$type) { /* {{{ */

		$name = trim($name);
		$type = trim($type);
		if(!$name || !$type)
			return false;
		if (is_object($this->getDocumentCategoryByName($name))) {
			return false;
		}

		$queryStr = "INSERT INTO `tblCategory` (`name`,`type`) VALUES (" . $this->db->qstr($name) . "," . $this->db->qstr($type) . ")";
		if (!$this->db->getResult($queryStr))
			return false;

		$category = $this->getDocumentCategory($this->db->getInsertID('tblCategory'));

		/* Check if 'onPostAddDocumentCategory' callback is set */
		if(isset($this->callbacks['onPostAddDocumentCategory'])) {
			foreach($this->callbacks['onPostAddDocumentCategory'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $category)) {
				}
			}
		}

		return $category;
	} /* }}} */

	/**
	 * Get all notifications for a group
	 *
	 * deprecated: User {@link SeedDMS_Core_Group::getNotifications()}
	 *
	 * @param object $group group for which notifications are to be retrieved
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return array array of notifications
	 */
	function getNotificationsByGroup($group, $type=0) { /* {{{ */
		return $group->getNotifications($type);
	} /* }}} */

	/**
	 * Get all notifications for a user
	 *
	 * deprecated: User {@link SeedDMS_Core_User::getNotifications()}
	 *
	 * @param object $user user for which notifications are to be retrieved
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return array array of notifications
	 */
	function getNotificationsByUser($user, $type=0) { /* {{{ */
		return $user->getNotifications($type);
	} /* }}} */

	/**
	 * Create a token to request a new password.
	 * This function will not delete the password but just creates an entry
	 * in tblUserRequestPassword indicating a password request.
	 *
	 * @param SeedDMS_Core_User $user
	 * @return string|boolean hash value of false in case of an error
	 */
	function createPasswordRequest($user) { /* {{{ */
		$lenght = 32;
		if (function_exists("random_bytes")) {
			$bytes = random_bytes(ceil($lenght / 2));
		} elseif (function_exists("openssl_random_pseudo_bytes")) {
			$bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
		} else {
			return false;
		}
		$hash = bin2hex($bytes);
		$queryStr = "INSERT INTO `tblUserPasswordRequest` (`userID`, `hash`, `date`) VALUES (" . $user->getId() . ", " . $this->db->qstr($hash) .", ".$this->db->getCurrentDatetime().")";
		$resArr = $this->db->getResult($queryStr);
		if (is_bool($resArr) && !$resArr) return false;
		return $hash;

	} /* }}} */

	/**
	 * Check if hash for a password request is valid.
	 * This function searches a previously create password request and
	 * returns the user.
	 *
	 * @param string $hash
	 * @return bool|SeedDMS_Core_User
	 */
	function checkPasswordRequest($hash) { /* {{{ */
		/* Get the password request from the database */
		$queryStr = "SELECT * FROM `tblUserPasswordRequest` WHERE `hash`=".$this->db->qstr($hash);
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];

		return $this->getUser($resArr['userID']);

	} /* }}} */

	/**
	 * Delete a password request
	 *
	 * @param string $hash
	 * @return bool
	 */
	function deletePasswordRequest($hash) { /* {{{ */
		/* Delete the request, so nobody can use it a second time */
		$queryStr = "DELETE FROM `tblUserPasswordRequest` WHERE `hash`=".$this->db->qstr($hash);
		if (!$this->db->getResult($queryStr))
			return false;
		return true;
	} /* }}} */

	/**
	 * Return a attribute definition by its id
	 *
	 * This function retrieves a attribute definitionr from the database by
	 * its id.
	 *
	 * @param integer $id internal id of attribute defintion
	 * @return bool|SeedDMS_Core_AttributeDefinition or false
	 */
	function getAttributeDefinition($id) { /* {{{ */
		if (!is_numeric($id) || $id < 1)
			return false;

		$queryStr = "SELECT * FROM `tblAttributeDefinitions` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

        if (is_bool($resArr) && $resArr == false)
            return false;
        if (count($resArr) != 1)
            return null;

		$resArr = $resArr[0];

		$attrdef = new SeedDMS_Core_AttributeDefinition($resArr["id"], $resArr["name"], $resArr["objtype"], $resArr["type"], $resArr["multiple"], $resArr["minvalues"], $resArr["maxvalues"], $resArr["valueset"], $resArr["regex"]);
		$attrdef->setDMS($this);
		return $attrdef;
	} /* }}} */

	/**
	 * Return a attribute definition by its name
	 *
	 * This function retrieves an attribute def. from the database by its name.
	 *
	 * @param string $name internal name of attribute def.
	 * @return SeedDMS_Core_AttributeDefinition|boolean instance of {@link SeedDMS_Core_AttributeDefinition} or false
	 */
	function getAttributeDefinitionByName($name) { /* {{{ */
		$name = trim($name);
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblAttributeDefinitions` WHERE `name` = " . $this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

        if (is_bool($resArr) && $resArr == false)
            return false;
        if (count($resArr) != 1)
            return null;

		$resArr = $resArr[0];

		$attrdef = new SeedDMS_Core_AttributeDefinition($resArr["id"], $resArr["name"], $resArr["objtype"], $resArr["type"], $resArr["multiple"], $resArr["minvalues"], $resArr["maxvalues"], $resArr["valueset"], $resArr["regex"]);
		$attrdef->setDMS($this);
		return $attrdef;
	} /* }}} */

	/**
	 * Return list of all attributes definitions
	 *
	 * @param integer|array $objtype select those attributes defined for an object type
	 * @param integer|array $type select those attributes defined for a type
	 * @return bool|SeedDMS_Core_AttributeDefinition[] of instances of <a href='psi_element://SeedDMS_Core_AttributeDefinition'>SeedDMS_Core_AttributeDefinition</a> or false
	 * or false
	 */
	function getAllAttributeDefinitions($objtype=0, $type=0) { /* {{{ */
		$queryStr = "SELECT * FROM `tblAttributeDefinitions`";
		if($objtype || $type) {
			$queryStr .= ' WHERE ';
			if($objtype) {
				if(is_array($objtype))
					$queryStr .= '`objtype` in (\''.implode("','", $objtype).'\')';
				else
					$queryStr .= '`objtype`='.intval($objtype);
			}
			if($objtype && $type) {
				$queryStr .= ' AND ';
			}
			if($type) {
				if(is_array($type))
					$queryStr .= '`type` in (\''.implode("','", $type).'\')';
				else
					$queryStr .= '`type`='.intval($type);
			}
		}
		$queryStr .= ' ORDER BY `name`';
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		/** @var SeedDMS_Core_AttributeDefinition[] $attrdefs */
		$attrdefs = array();

		for ($i = 0; $i < count($resArr); $i++) {
			$attrdef = new SeedDMS_Core_AttributeDefinition($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["objtype"], $resArr[$i]["type"], $resArr[$i]["multiple"], $resArr[$i]["minvalues"], $resArr[$i]["maxvalues"], $resArr[$i]["valueset"], $resArr[$i]["regex"]);
			$attrdef->setDMS($this);
			$attrdefs[$i] = $attrdef;
		}

		return $attrdefs;
	} /* }}} */

	/**
	 * Add a new attribute definition
	 *
	 * @param string $name name of attribute
	 * @param $objtype
	 * @param string $type type of attribute
	 * @param bool|int $multiple set to 1 if attribute has multiple attributes
	 * @param integer $minvalues minimum number of values
	 * @param integer $maxvalues maximum number of values if multiple is set
	 * @param string $valueset list of allowed values (csv format)
	 * @param string $regex
	 * @return bool|SeedDMS_Core_User
	 */
	function addAttributeDefinition($name, $objtype, $type, $multiple=0, $minvalues=0, $maxvalues=1, $valueset='', $regex='') { /* {{{ */
		$name = trim($name);
		if(!$name)
			return false;
		if (is_object($this->getAttributeDefinitionByName($name))) {
			return false;
		}
		if($objtype < SeedDMS_Core_AttributeDefinition::objtype_all || $objtype > SeedDMS_Core_AttributeDefinition::objtype_documentcontent)
			return false;
		if(!$type)
			return false;
		if(trim($valueset)) {
			$valuesetarr = array_map('trim', explode($valueset[0], substr($valueset, 1)));
			$valueset = $valueset[0].implode($valueset[0], $valuesetarr);
		} else {
			$valueset = '';
		}
		$queryStr = "INSERT INTO `tblAttributeDefinitions` (`name`, `objtype`, `type`, `multiple`, `minvalues`, `maxvalues`, `valueset`, `regex`) VALUES (".$this->db->qstr($name).", ".intval($objtype).", ".intval($type).", ".intval($multiple).", ".intval($minvalues).", ".intval($maxvalues).", ".$this->db->qstr($valueset).", ".$this->db->qstr($regex).")";
		$res = $this->db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getAttributeDefinition($this->db->getInsertID('tblAttributeDefinitions'));
	} /* }}} */

	/**
	 * Return list of all workflows
	 *
	 * @return SeedDMS_Core_Workflow[]|bool of instances of {@link SeedDMS_Core_Workflow} or false
	 */
	function getAllWorkflows() { /* {{{ */
		$queryStr = "SELECT * FROM `tblWorkflows` ORDER BY `name`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$queryStr = "SELECT * FROM `tblWorkflowStates` ORDER BY `name`";
		$ressArr = $this->db->getResultArray($queryStr);

		if (is_bool($ressArr) && $ressArr == false)
			return false;

		for ($i = 0; $i < count($ressArr); $i++) {
			$wkfstates[$ressArr[$i]["id"]] = new SeedDMS_Core_Workflow_State($ressArr[$i]["id"], $ressArr[$i]["name"], $ressArr[$i]["maxtime"], $ressArr[$i]["precondfunc"], $ressArr[$i]["documentstatus"]);
		}

		/** @var SeedDMS_Core_Workflow[] $workflows */
		$workflows = array();
		for ($i = 0; $i < count($resArr); $i++) {
			/** @noinspection PhpUndefinedVariableInspection */
			$workflow = new SeedDMS_Core_Workflow($resArr[$i]["id"], $resArr[$i]["name"], $wkfstates[$resArr[$i]["initstate"]], $resArr[$i]["layoutdata"]);
			$workflow->setDMS($this);
			$workflows[$i] = $workflow;
		}

		return $workflows;
	} /* }}} */

	/**
	 * Return workflow by its Id
	 *
	 * @param integer $id internal id of workflow
	 * @return SeedDMS_Core_Workflow|bool of instances of {@link SeedDMS_Core_Workflow}, null if no workflow was found or false
	 */
	function getWorkflow($id) { /* {{{ */
		if (!is_numeric($id) || $id < 1)
			return false;

		$queryStr = "SELECT * FROM `tblWorkflows` WHERE `id`=".intval($id);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return null;

		$initstate = $this->getWorkflowState($resArr[0]['initstate']);

		$workflow = new SeedDMS_Core_Workflow($resArr[0]["id"], $resArr[0]["name"], $initstate, $resArr[0]["layoutdata"]);
		$workflow->setDMS($this);

		return $workflow;
	} /* }}} */

	/**
	 * Return workflow by its name
	 *
	 * @param string $name name of workflow
	 * @return SeedDMS_Core_Workflow|bool of instances of {@link SeedDMS_Core_Workflow} or null if no workflow was found or false
	 */
	function getWorkflowByName($name) { /* {{{ */
		$name = trim($name);
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblWorkflows` WHERE `name`=".$this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return null;

		$initstate = $this->getWorkflowState($resArr[0]['initstate']);

		$workflow = new SeedDMS_Core_Workflow($resArr[0]["id"], $resArr[0]["name"], $initstate, $resArr[0]["layoutdata"]);
		$workflow->setDMS($this);

		return $workflow;
	} /* }}} */

	/**
	 * Add a new workflow
	 *
	 * @param string $name name of workflow
	 * @param SeedDMS_Core_Workflow_State $initstate initial state of workflow
	 * @return bool|SeedDMS_Core_Workflow
	 */
	function addWorkflow($name, $initstate) { /* {{{ */
		$db = $this->db;
		$name = trim($name);
		if(!$name)
			return false;
		if (is_object($this->getWorkflowByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO `tblWorkflows` (`name`, `initstate`) VALUES (".$db->qstr($name).", ".$initstate->getID().")";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getWorkflow($db->getInsertID('tblWorkflows'));
	} /* }}} */

	/**
	 * Return a workflow state by its id
	 *
	 * This function retrieves a workflow state from the database by its id.
	 *
	 * @param integer $id internal id of workflow state
	 * @return bool|SeedDMS_Core_Workflow_State or false
	 */
	function getWorkflowState($id) { /* {{{ */
		if (!is_numeric($id) || $id < 1)
			return false;

		$queryStr = "SELECT * FROM `tblWorkflowStates` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if (count($resArr) != 1)
		 	return null;

		$resArr = $resArr[0];

		$state = new SeedDMS_Core_Workflow_State($resArr["id"], $resArr["name"], $resArr["maxtime"], $resArr["precondfunc"], $resArr["documentstatus"]);
		$state->setDMS($this);
		return $state;
	} /* }}} */

	/**
	 * Return workflow state by its name
	 *
	 * @param string $name name of workflow state
	 * @return bool|SeedDMS_Core_Workflow_State or false
	 */
	function getWorkflowStateByName($name) { /* {{{ */
		$name = trim($name);
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblWorkflowStates` WHERE `name`=".$this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return null;

		$resArr = $resArr[0];

		$state = new SeedDMS_Core_Workflow_State($resArr["id"], $resArr["name"], $resArr["maxtime"], $resArr["precondfunc"], $resArr["documentstatus"]);
		$state->setDMS($this);

		return $state;
	} /* }}} */

	/**
	 * Return list of all workflow states
	 *
	 * @return SeedDMS_Core_Workflow_State[]|bool of instances of {@link SeedDMS_Core_Workflow_State} or false
	 */
	function getAllWorkflowStates() { /* {{{ */
		$queryStr = "SELECT * FROM `tblWorkflowStates` ORDER BY `name`";
		$ressArr = $this->db->getResultArray($queryStr);

		if (is_bool($ressArr) && $ressArr == false)
			return false;

		$wkfstates = array();
		for ($i = 0; $i < count($ressArr); $i++) {
			$wkfstate = new SeedDMS_Core_Workflow_State($ressArr[$i]["id"], $ressArr[$i]["name"], $ressArr[$i]["maxtime"], $ressArr[$i]["precondfunc"], $ressArr[$i]["documentstatus"]);
			$wkfstate->setDMS($this);
			$wkfstates[$i] = $wkfstate;
		}

		return $wkfstates;
	} /* }}} */

	/**
	 * Add new workflow state
	 *
	 * @param string $name name of workflow state
	 * @param integer $docstatus document status when this state is reached
	 * @return bool|SeedDMS_Core_Workflow_State
	 */
	function addWorkflowState($name, $docstatus) { /* {{{ */
		$db = $this->db;
		$name = trim($name);
		if(!$name)
			return false;
		if (is_object($this->getWorkflowStateByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO `tblWorkflowStates` (`name`, `documentstatus`) VALUES (".$db->qstr($name).", ".(int) $docstatus.")";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getWorkflowState($db->getInsertID('tblWorkflowStates'));
	} /* }}} */

	/**
	 * Return a workflow action by its id
	 *
	 * This function retrieves a workflow action from the database by its id.
	 *
	 * @param integer $id internal id of workflow action
	 * @return SeedDMS_Core_Workflow_Action|bool instance of {@link SeedDMS_Core_Workflow_Action} or false
	 */
	function getWorkflowAction($id) { /* {{{ */
		if (!is_numeric($id) || $id < 1)
			return false;

		$queryStr = "SELECT * FROM `tblWorkflowActions` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if (count($resArr) != 1)
		 	return null;

		$resArr = $resArr[0];

		$action = new SeedDMS_Core_Workflow_Action($resArr["id"], $resArr["name"]);
		$action->setDMS($this);
		return $action;
	} /* }}} */

	/**
	 * Return a workflow action by its name
	 *
	 * This function retrieves a workflow action from the database by its name.
	 *
	 * @param string $name name of workflow action
	 * @return SeedDMS_Core_Workflow_Action|bool instance of {@link SeedDMS_Core_Workflow_Action} or false
	 */
	function getWorkflowActionByName($name) { /* {{{ */
		$name = trim($name);
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblWorkflowActions` WHERE `name` = " . $this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if (count($resArr) != 1)
		 	return null;

		$resArr = $resArr[0];

		$action = new SeedDMS_Core_Workflow_Action($resArr["id"], $resArr["name"]);
		$action->setDMS($this);
		return $action;
	} /* }}} */

	/**
	 * Return list of workflow action
	 *
	 * @return SeedDMS_Core_Workflow_Action[]|bool list of instances of {@link SeedDMS_Core_Workflow_Action} or false
	 */
	function getAllWorkflowActions() { /* {{{ */
		$queryStr = "SELECT * FROM `tblWorkflowActions`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		/** @var SeedDMS_Core_Workflow_Action[] $wkfactions */
		$wkfactions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$action = new SeedDMS_Core_Workflow_Action($resArr[$i]["id"], $resArr[$i]["name"]);
			$action->setDMS($this);
			$wkfactions[$i] = $action;
		}

		return $wkfactions;
	} /* }}} */

	/**
	 * Add new workflow action
	 *
	 * @param string $name name of workflow action
	 * @return SeedDMS_Core_Workflow_Action|bool
	 */
	function addWorkflowAction($name) { /* {{{ */
		$db = $this->db;
		$name = trim($name);
		if(!$name)
			return false;
		if (is_object($this->getWorkflowActionByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO `tblWorkflowActions` (`name`) VALUES (".$db->qstr($name).")";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getWorkflowAction($db->getInsertID('tblWorkflowActions'));
	} /* }}} */

	/**
	 * Return a workflow transition by its id
	 *
	 * This function retrieves a workflow transition from the database by its id.
	 *
	 * @param integer $id internal id of workflow transition
	 * @return SeedDMS_Core_Workflow_Transition|bool instance of {@link SeedDMS_Core_Workflow_Transition} or false
	 */
	function getWorkflowTransition($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$transition = new SeedDMS_Core_Workflow_Transition($resArr["id"], $this->getWorkflow($resArr["workflow"]), $this->getWorkflowState($resArr["state"]), $this->getWorkflowAction($resArr["action"]), $this->getWorkflowState($resArr["nextstate"]), $resArr["maxtime"]);
		$transition->setDMS($this);
		return $transition;
	} /* }}} */

	/**
	 * Return all documents waiting for or in reception
	 *
	 * This function retrieves all documents and its version which are waiting for
	 * reception
	 *
	 * @return object instance of {@link SeedDMS_Core_DocumentContent} or false
	 */
	function getDocumentsInReception() { /* {{{ */
		if (!$this->db->createTemporaryTable("ttreceiptid") || !$this->db->createTemporaryTable("ttcontentid")) {
			return false;
		}
		$queryStr =
			"SELECT `tblDocumentRecipients`.*, `tblDocumentReceiptLog`.`status` FROM `tblDocumentRecipients` LEFT JOIN `ttreceiptid` ON `tblDocumentRecipients`.`receiptID` = `ttreceiptid`.`receiptID` LEFT JOIN `tblDocumentReceiptLog` ON `ttreceiptid`.`maxLogID` = `tblDocumentReceiptLog`.`receiptLogID` LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion`=`tblDocumentRecipients`.`version` AND `ttcontentid`.`document`=`tblDocumentRecipients`.`documentID` WHERE `tblDocumentReceiptLog`.`status`=0 AND `ttcontentid`.`maxVersion` IS NOT NULL";
		$resArr = $this->db->getResultArray($queryStr);

		return $resArr;
	} /* }}} */

	/**
	 * Return all documents revisors waiting for a revision to start (sleeping)
	 * or are required to revise the document (waiting)
	 *
	 * This function retrieves all revisors which are waiting for
	 * revision or already in revision
	 * Note: the name of the method is somewhat misleading, because it
	 * does not return documents but just database records from table
	 * tblDocumentRevisors and tblDocumentRevisionLog
	 *
	 * @return array list of revisors or false in case of an error
	 */
	function getDocumentsInRevision() { /* {{{ */
		if (!$this->db->createTemporaryTable("ttrevisionid") || !$this->db->createTemporaryTable("ttcontentid")) {
			return false;
		}
		$queryStr =
			"SELECT `tblDocumentRevisors`.*, `tblDocumentRevisionLog`.`status` FROM `tblDocumentRevisors` LEFT JOIN `ttrevisionid` ON `tblDocumentRevisors`.`revisionID` = `ttrevisionid`.`revisionID` LEFT JOIN `tblDocumentRevisionLog` ON `ttrevisionid`.`maxLogID` = `tblDocumentRevisionLog`.`revisionLogID` LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion`=`tblDocumentRevisors`.`version` AND `ttcontentid`.`document`=`tblDocumentRevisors`.`documentID` WHERE `tblDocumentRevisionLog`.`status` in (".S_LOG_WAITING.", ".S_LOG_SLEEPING.") AND `ttcontentid`.`maxVersion` IS NOT NULL";
		$resArr = $this->db->getResultArray($queryStr);

		return $resArr;
	} /* }}} */

	/**
	 * Returns document content which is not linked to a document
	 *
	 * This method is for finding straying document content without
	 * a parent document. In normal operation this should not happen
	 * but little checks for database consistency and possible errors
	 * in the application may have left over document content though
	 * the document is gone already.
	 *
	 * @return array|bool
	 */
	function getUnlinkedDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `document` NOT IN (SELECT id FROM `tblDocuments`)";
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		$versions = array();
		foreach($resArr as $row) {
			/** @var SeedDMS_Core_Document $document */
			$document = new $this->classnames['document']($row['document'], '', '', '', '', '', '', '', '', '', '', '');
			$document->setDMS($this);
			$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
			$versions[] = $version;
		}
		return $versions;

	} /* }}} */

	/**
	 * Returns document content which has no file size set
	 *
	 * This method is for finding document content without a file size
	 * set in the database. The file size of a document content was introduced
	 * in version 4.0.0 of SeedDMS for implementation of user quotas.
	 *
	 * @return SeedDMS_Core_Document[]|bool
	 */
	function getNoFileSizeDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `fileSize` = 0 OR `fileSize` is null";
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		/** @var SeedDMS_Core_Document[] $versions */
		$versions = array();
		foreach($resArr as $row) {
			$document = $this->getDocument($row['document']);
			/* getting the document can fail if it is outside the root folder
			 * and checkWithinRootDir is enabled.
			 */
			if($document) {
				$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum'], $row['fileSize'], $row['checksum']);
				$versions[] = $version;
			}
		}
		return $versions;

	} /* }}} */

	/**
	 * Returns document content which has no checksum set
	 *
	 * This method is for finding document content without a checksum
	 * set in the database. The checksum of a document content was introduced
	 * in version 4.0.0 of SeedDMS for finding duplicates.
	 * @return bool|SeedDMS_Core_Document[]
	 */
	function getNoChecksumDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `checksum` = '' OR `checksum` is null";
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		/** @var SeedDMS_Core_Document[] $versions */
		$versions = array();
		foreach($resArr as $row) {
			$document = $this->getDocument($row['document']);
			/* getting the document can fail if it is outside the root folder
			 * and checkWithinRootDir is enabled.
			 */
			if($document) {
				$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
				$versions[] = $version;
			}
		}
		return $versions;

	} /* }}} */

	/**
	 * Returns document content which has the incorrect file type
	 *
	 * This method is for finding document content with an incorrect
	 * or missing file type. It just checks documents contents
	 * with a certain mime type.
	 * @return bool|SeedDMS_Core_Document[]
	 */
	function getWrongFiletypeDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `mimeType` in ('application/zip', 'application/pdf', 'image/png', 'image/gif', 'image/jpg', 'audio/mp3', 'text/rtf')";
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		/** @var SeedDMS_Core_Document[] $versions */
		$versions = array();
		foreach($resArr as $row) {
			$expect = '';
			switch($row['mimeType']) {
			case "application/zip":
			case "application/pdf":
			case "image/png":
			case "image/gif":
			case "image/jpg":
			case "audio/mp3":
			case "text/rtf":
				$expect = substr($row['mimeType'], -3, 3);
				break;
			}
			if($expect) {
				if($row['fileType'] != '.'.$expect) {
					/** @var SeedDMS_Core_Document $document */
					$document = new $this->classnames['document']($row['document'], '', '', '', '', '', '', '', '', '', '', '');
					$document->setDMS($this);
					$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
					$versions[] = $version;
				}
			}
		}
		return $versions;

	} /* }}} */

	/**
	 * Returns document content which is duplicated
	 *
	 * This method is for finding document content which is available twice
	 * in the database. The checksum of a document content was introduced
	 * in version 4.0.0 of SeedDMS for finding duplicates.
	 * @return array|bool
	 */
	function getDuplicateDocumentContent() { /* {{{ */
		$queryStr = "SELECT a.*, b.`id` as dupid FROM `tblDocumentContent` a LEFT JOIN `tblDocumentContent` b ON a.`checksum`=b.`checksum` WHERE a.`id`!=b.`id` ORDER BY a.`id` LIMIT 1000";
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		/** @var SeedDMS_Core_Document[] $versions */
		$versions = array();
		foreach($resArr as $row) {
			$document = $this->getDocument($row['document']);
			/* getting the document can fail if it is outside the root folder
			 * and checkWithinRootDir is enabled.
			 */
			if($document) {
				$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
				if(!isset($versions[$row['dupid']])) {
					$versions[$row['id']]['content'] = $version;
					$versions[$row['id']]['duplicates'] = array();
				} else
					$versions[$row['dupid']]['duplicates'][] = $version;
			}
		}
		return $versions;

	} /* }}} */

	/**
	 * Returns folders which contain documents with none unique sequence number
	 *
	 * This method is for finding folders with documents not having a
	 * unique sequence number. Those documents cannot propperly be sorted
	 * by sequence and changing their position is impossible if more than
	 * two documents with the same sequence number exists, e.g.
	 * doc 1: 3
	 * doc 2: 5
	 * doc 3: 5
	 * doc 4: 5
	 * doc 5: 7
	 * If document 4 was to be moved between doc 1 and 2 it get sequence
	 * number 4 ((5+3)/2).
	 * But if document 4 was to be moved between doc 2 and 3 it will again
	 * have sequence number 5.
	 *
	 * @return array|bool
	 */
	function getDuplicateSequenceNo() { /* {{{ */
		$queryStr = "SELECT DISTINCT `folder` FROM (SELECT `folder`, `sequence`, count(*) c FROM `tblDocuments` GROUP BY `folder`, `sequence` HAVING c > 1) a";
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		$folders = array();
		foreach($resArr as $row) {
			$folder = $this->getFolder($row['folder']);
			if($folder)
				$folders[] = $folder;
		}
		return $folders;

	} /* }}} */

	/**
	 * Returns a list of reviews, approvals, receipts, revisions which are not
	 * linked to a user, group anymore
	 *
	 * This method is for finding reviews or approvals whose user
	 * or group  was deleted and not just removed from the process.
	 *
	 * @param string $process
	 * @param string $usergroup
	 * @return array
	 */
	function getProcessWithoutUserGroup($process, $usergroup) { /* {{{ */
		switch($process) {
		case 'review':
			$queryStr = "SELECT a.*, b.`name` FROM `tblDocumentReviewers`";
			break;
		case 'approval':
			$queryStr = "SELECT a.*, b.`name` FROM `tblDocumentApprovers`";
			break;
		case 'receipt':
			$queryStr = "SELECT a.*, b.`name` FROM `tblDocumentRecipients`";
			break;
		case 'revision':
			$queryStr = "SELECT a.*, b.`name` FROM `tblDocumentRevisors`";
			break;
		}
		/** @noinspection PhpUndefinedVariableInspection */
		$queryStr .= " a LEFT JOIN `tblDocuments` b ON a.`documentID`=b.`id` WHERE";
		switch($usergroup) {
		case 'user':
			$queryStr .= " a.`type`=0 and a.`required` not in (SELECT `id` FROM `tblUsers`) ORDER BY b.`id`";
			break;
		case 'group':
			$queryStr .= " a.`type`=1 and a.`required` not in (SELECT `id` FROM `tblGroups`) ORDER BY b.`id`";
			break;
		}
		return $this->db->getResultArray($queryStr);
	} /* }}} */

	/**
	 * Removes all reviews, approvals, receipts, revisions which are not linked
	 * to a user, group anymore
	 *
	 * This method is for removing all reviews or approvals whose user
	 * or group  was deleted and not just removed from the process.
	 * If the optional parameter $id is set, only this user/group id is removed.
	 * @param string $process
	 * @param string $usergroup
	 * @param int $id
	 * @return array
	 */
	function removeProcessWithoutUserGroup($process, $usergroup, $id=0) { /* {{{ */
		/* Entries of tblDocumentReviewLog or tblDocumentApproveLog are deleted
		 * because of CASCADE ON
		 */
		switch($process) {
		case 'review':
			$queryStr = "DELETE FROM tblDocumentReviewers";
			break;
		case 'approval':
			$queryStr = "DELETE FROM tblDocumentApprovers";
			break;
		case 'receipt':
			$queryStr = "DELETE FROM tblDocumentRecipients";
			break;
		case 'revision':
			$queryStr = "DELETE FROM tblDocumentRevisors";
			break;
		}
		/** @noinspection PhpUndefinedVariableInspection */
		$queryStr .= " WHERE";
		switch($usergroup) {
		case 'user':
			$queryStr .= " type=0 AND";
			if($id)
				$queryStr .= " required=".((int) $id)." AND";
			$queryStr .= " required NOT IN (SELECT id FROM tblUsers)";
			break;
		case 'group':
			$queryStr .= " type=1 AND";
			if($id)
				$queryStr .= " required=".((int) $id)." AND";
			$queryStr .= " required NOT IN (SELECT id FROM tblGroups)";
			break;
		}
		return $this->db->getResultArray($queryStr);
	} /* }}} */

	/**
	 * Returns statitical information
	 *
	 * This method returns all kind of statistical information like
	 * documents or used space per user, recent activity, etc.
	 *
	 * @param string $type type of statistic
	 * @return array|bool returns false if the sql statement fails, returns an empty
	 * array if no documents or folder where found, otherwise returns a non empty
	 * array with statistical data
	 */
	function getStatisticalData($type='') { /* {{{ */
		switch($type) {
			case 'docsperuser':
				$queryStr = "SELECT ".$this->db->concat(array('b.`fullName`', "' ('", 'b.`login`', "')'"))." AS `key`, count(`owner`) AS total FROM `tblDocuments` a LEFT JOIN `tblUsers` b ON a.`owner`=b.`id` GROUP BY `owner`, b.`fullName`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;

				return $resArr;
			case 'foldersperuser':
				$queryStr = "SELECT ".$this->db->concat(array('b.`fullName`', "' ('", 'b.`login`', "')'"))." AS `key`, count(`owner`) AS total FROM `tblFolders` a LEFT JOIN `tblUsers` b ON a.`owner`=b.`id` GROUP BY `owner`, b.`fullName`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;

				return $resArr;
			case 'docspermimetype':
				$queryStr = "SELECT b.`mimeType` AS `key`, count(`mimeType`) AS total FROM `tblDocuments` a LEFT JOIN `tblDocumentContent` b ON a.`id`=b.`document` GROUP BY b.`mimeType`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;

				return $resArr;
			case 'docspercategory':
				$queryStr = "SELECT b.`name` AS `key`, count(a.`categoryID`) AS total FROM `tblDocumentCategory` a LEFT JOIN `tblCategory` b ON a.`categoryID`=b.id GROUP BY a.`categoryID`, b.`name`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;

				return $resArr;
			case 'docsperstatus':
				/** @noinspection PhpUnusedLocalVariableInspection */
				$queryStr = "SELECT b.`status` AS `key`, count(b.`status`) AS total FROM (SELECT a.id, max(b.version), max(c.`statusLogID`) AS maxlog FROM `tblDocuments` a LEFT JOIN `tblDocumentStatus` b ON a.id=b.`documentID` LEFT JOIN `tblDocumentStatusLog` c ON b.`statusID`=c.`statusID` GROUP BY a.`id`, b.`version` ORDER BY a.`id`, b.`statusID`) a LEFT JOIN `tblDocumentStatusLog` b ON a.`maxlog`=b.`statusLogID` GROUP BY b.`status`";
				$queryStr = "SELECT b.`status` AS `key`, count(b.`status`) AS total FROM (SELECT a.`id`, max(c.`statusLogID`) AS maxlog FROM `tblDocuments` a LEFT JOIN `tblDocumentStatus` b ON a.id=b.`documentID` LEFT JOIN `tblDocumentStatusLog` c ON b.`statusID`=c.`statusID` GROUP BY a.`id` ORDER BY a.id) a LEFT JOIN `tblDocumentStatusLog` b ON a.maxlog=b.`statusLogID` GROUP BY b.`status`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;

				return $resArr;
			case 'docspermonth':
				$queryStr = "SELECT *, count(`key`) AS total FROM (SELECT ".$this->db->getDateExtract("date", '%Y-%m')." AS `key` FROM `tblDocuments`) a GROUP BY `key` ORDER BY `key`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;

				return $resArr;
			case 'docsaccumulated':
				$queryStr = "SELECT *, count(`key`) AS total FROM (SELECT ".$this->db->getDateExtract("date")." AS `key` FROM `tblDocuments`) a GROUP BY `key` ORDER BY `key`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;

				$sum = 0;
				foreach($resArr as &$res) {
					$sum += $res['total'];
					/* auxially variable $key is need because sqlite returns
					 * a key '`key`'
					 */
					$res['key'] = mktime(12, 0, 0, substr($res['key'], 5, 2), substr($res['key'], 8, 2), substr($res['key'], 0, 4)) * 1000;
					$res['total'] = $sum;
				}
				return $resArr;
			case 'docstotal':
				$queryStr = "SELECT count(*) AS total FROM `tblDocuments`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;
				return (int) $resArr[0]['total'];
			case 'folderstotal':
				$queryStr = "SELECT count(*) AS total FROM `tblFolders`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;
				return (int) $resArr[0]['total'];
			case 'userstotal':
				$queryStr = "SELECT count(*) AS total FROM `tblUsers`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;
				return (int) $resArr[0]['total'];
			case 'sizeperuser':
				$queryStr = "SELECT ".$this->db->concat(array('c.`fullName`', "' ('", 'c.`login`', "')'"))." AS `key`, sum(`fileSize`) AS total FROM `tblDocuments` a LEFT JOIN `tblDocumentContent` b ON a.id=b.`document` LEFT JOIN `tblUsers` c ON a.`owner`=c.`id` GROUP BY a.`owner`, c.`fullName`";
				$resArr = $this->db->getResultArray($queryStr);
				if(is_bool($resArr) && $resArr == false)
					return false;

				return $resArr;
			default:
				return array();
		}
	} /* }}} */

	/**
	 * Returns changes with a period of time
	 *
	 * This method returns a list of all changes happened in the database
	 * within a given period of time. It currently just checks for
	 * entries in the database tables tblDocumentContent, tblDocumentFiles,
	 * and tblDocumentStatusLog
	 *
	 * @param string $startts
	 * @param string $endts
	 * @return array|bool
	 * @internal param string $start start date, defaults to start of current day
	 * @internal param string $end end date, defaults to end of start day
	 */
	function getTimeline($startts='', $endts='') { /* {{{ */
		if(!$startts)
			$startts = mktime(0, 0, 0);
		if(!$endts)
			$endts = $startts+86400;

		/** @var SeedDMS_Core_Document[] $timeline */
		$timeline = array();

		if(0) {
		$queryStr = "SELECT DISTINCT `document` FROM `tblDocumentContent` WHERE `date` > ".$startts." AND `date` < ".$endts." OR `revisiondate` > '".date('Y-m-d H:i:s', $startts)."' AND `revisiondate` < '".date('Y-m-d H:i:s', $endts)."' UNION SELECT DISTINCT `document` FROM `tblDocumentFiles` WHERE `date` > ".$startts." AND `date` < ".$endts;
		} else {
		$startdate = date('Y-m-d H:i:s', $startts);
		$enddate = date('Y-m-d H:i:s', $endts);
		$queryStr = "SELECT DISTINCT `documentID` AS `document` FROM `tblDocumentStatus` LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatus`.`statusId`=`tblDocumentStatusLog`.`statusID` WHERE `date` > ".$this->db->qstr($startdate)." AND `date` < ".$this->db->qstr($enddate)." UNION SELECT DISTINCT document FROM `tblDocumentFiles` WHERE `date` > ".$this->db->qstr($startdate)." AND `date` < ".$this->db->qstr($enddate)." UNION SELECT DISTINCT `document` FROM `tblDocumentFiles` WHERE `date` > ".$startts." AND `date` < ".$endts;
		}
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;
		foreach($resArr as $rec) {
			$document = $this->getDocument($rec['document']);
			$timeline = array_merge($timeline, $document->getTimeline());
		}
		return $timeline;

	} /* }}} */

	/**
	 * Returns changes with a period of time
	 *
	 * This method is similar to getTimeline() but returns more dedicated lists
	 * of documents or folders which has change in various ways.
	 *
	 * @param string $mode
	 * @param string $startts
	 * @param string $endts
	 * @return array|bool
	 * @internal param string $start start date, defaults to start of current day
	 * @internal param string $end end date, defaults to end of start day
	 */
	function getLatestChanges($mode, $startts='', $endts='') { /* {{{ */
		if(!$startts)
			$startts = mktime(0, 0, 0);
		if(!$endts)
			$endts = $startts+86400;

		$startdate = date('Y-m-d H:i:s', $startts);
		$enddate = date('Y-m-d H:i:s', $endts);

		$objects = [];
		switch($mode) {
		case 'statuschange':
			/* Count entries in tblDocumentStatusLog for each tblDocumentStatus and
			 * take only those into account with at least 2 log entries. For the
			 * document id do a left join with tblDocumentStatus
			 * This is similar to ttstatid + the count + the join
			 * c > 1 is required to find only those documents with a changed status
			 */
			$queryStr = "SELECT `a`.*, `tblDocumentStatus`.`documentId` as `document` FROM (SELECT `tblDocumentStatusLog`.`statusID` AS `statusID`, MAX(`tblDocumentStatusLog`.`statusLogID`) AS `maxLogID`, COUNT(`tblDocumentStatusLog`.`statusLogID`) AS `c`, `tblDocumentStatusLog`.`date` FROM `tblDocumentStatusLog` GROUP BY `tblDocumentStatusLog`.`statusID` HAVING `c` > 1 ORDER BY `tblDocumentStatusLog`.`date` DESC) `a` LEFT JOIN `tblDocumentStatus` ON `a`.`statusID`=`tblDocumentStatus`.`statusID` WHERE `a`.`date` > ".$this->db->qstr($startdate)." AND `a`.`date` < ".$this->db->qstr($enddate)." ";
			$resArr = $this->db->getResultArray($queryStr);
			if ($resArr === false)
				return false;
			foreach($resArr as $rec) {
				if($object = $this->getDocument($rec['document']))
					$objects[] = $object;
			}
			break;
		case 'newdocuments':
			$queryStr = "SELECT `id` AS `document` FROM `tblDocuments` WHERE `date` > ".$startts." AND `date` < ".$endts." ORDER BY `date` DESC";
			$resArr = $this->db->getResultArray($queryStr);
			if ($resArr === false)
				return false;
			foreach($resArr as $rec) {
				if($object = $this->getDocument($rec['document']))
					$objects[] = $object;
			}
			break;
		case 'updateddocuments':
			/* DISTINCT is need if there is more than 1 update of the document in the
			 * given period of time. Without it, the query will return the document
			 * more than once.
			 */
			$queryStr = "SELECT DISTINCT `document` AS `document` FROM `tblDocumentContent` LEFT JOIN `tblDocuments` ON `tblDocumentContent`.`document`=`tblDocuments`.`id` WHERE `tblDocumentContent`.`date` > ".$startts." AND `tblDocumentContent`.`date` < ".$endts." AND `tblDocumentContent`.`date` > `tblDocuments`.`date` ORDER BY `tblDocumentContent`.`date` DESC";
			$resArr = $this->db->getResultArray($queryStr);
			if ($resArr === false)
				return false;
			foreach($resArr as $rec) {
				if($object = $this->getDocument($rec['document']))
					$objects[] = $object;
			}
			break;
		case 'newfolders':
			$queryStr = "SELECT `id` AS `folder` FROM `tblFolders` WHERE `date` > ".$startts." AND `date` < ".$endts." ORDER BY `date` DESC";
			$resArr = $this->db->getResultArray($queryStr);
			if ($resArr === false)
				return false;
			foreach($resArr as $rec) {
				if($object = $this->getFolder($rec['folder']))
					$objects[] = $object;
			}
			break;
		}
		return $objects;
	} /* }}} */

	/**
	 * Set a callback function
	 *
	 * The function passed in $func must be a callable and $name may not be empty.
	 *
	 * Setting a callback with the method will remove all priorly set callbacks.
	 *
	 * @param string $name internal name of callback
	 * @param mixed $func function name as expected by {call_user_method}
	 * @param mixed $params parameter passed as the first argument to the
	 *        callback
	 * @return bool true if adding the callback succeeds otherwise false
	 */
	function setCallback($name, $func, $params=null) { /* {{{ */
		if($name && $func && is_callable($func)) {
			$this->callbacks[$name] = array(array($func, $params));
			return true;
		} else {
			return false;
		}
	} /* }}} */

	/**
	 * Add a callback function
	 *
	 * The function passed in $func must be a callable and $name may not be empty.
	 *
	 * @param string $name internal name of callback
	 * @param mixed $func function name as expected by {call_user_method}
	 * @param mixed $params parameter passed as the first argument to the
	 *        callback
	 * @return bool true if adding the callback succeeds otherwise false
	 */
	function addCallback($name, $func, $params=null) { /* {{{ */
		if($name && $func && is_callable($func)) {
			$this->callbacks[$name][] = array($func, $params);
			return true;
		} else {
			return false;
		}
	} /* }}} */

	/**
	 * Check if a callback with the given has been set
	 *
	 * @param string $name internal name of callback
	 * @return bool true a callback exists otherwise false
	 */
	function hasCallback($name) { /* {{{ */
		if($name && !empty($this->callbacks[$name]))
			return true;
		return false;
	} /* }}} */

}
