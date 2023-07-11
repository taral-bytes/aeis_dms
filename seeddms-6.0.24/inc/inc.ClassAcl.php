<?php
/**
 * Implementation of a access control list.
 *
 * SeedDMS uses access control list for setting permission,
 * on various operations.
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2016 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent an access request object
 *
 * This class provides a model for access request objects.
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Acl { /* {{{ */
	/**
	 * @var object $dms reference to dms object.
	 * @access public
	 */
	public $_dms;

	/**
	 * Create a new instance of an acl
	 *
	 * @param object $dms object of dms
	 * @return object instance of SeedDMS_Acl
	 */
	public function __construct($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/**
	 * Check if Aro has access on Aco
	 *
	 * @param object $aro access request object
	 * @param object $aco access control object
	 * @return integer/boolean -1 if access is explictly denied, 1 if access
	 * is explictly allow, 0 if no access restrictions exists, false if
	 * an error occured.
	 */
	public function check($aro, $aco) { /* {{{ */
		$db = $this->_dms->getDB();

		while($aco) {
			$acoid = $aco->getID();
			$queryStr = "SELECT * FROM `tblArosAcos` WHERE `aro`=".$aro->getID()." AND `aco`=".$acoid;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr === false)
				return false;
			if (count($resArr) == 1)
				return((int) $resArr[0]['read']);

			$aco = $aco->getParent();
		}

		return 0;
	} /* }}} */

	public function toggle($aro, $aco) { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "SELECT * FROM `tblArosAcos` WHERE `aro`=".$aro->getID()." AND `aco`=".$aco->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];

		$newperm = $resArr['read'] == 1 ? -1 : 1;
		$queryStr = "UPDATE `tblArosAcos` SET `read`=".$newperm." WHERE `aro`=".$aro->getID()." AND `aco`=".$aco->getID();
		if (!$db->getResult($queryStr))
			return false;
		return true;

	} /* }}} */

	public function add($aro, $aco, $perm=-1) { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "SELECT * FROM `tblArosAcos` WHERE `aro`=".$aro->getID()." AND `aco`=".$aco->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr) == 1) {
			$resArr = $resArr[0];

			$newperm = $resArr['read'] == 1 ? -1 : 1;
			$queryStr = "UPDATE `tblArosAcos` SET `read`=".$newperm." WHERE `aro`=".$aro->getID()." AND `aco`=".$aco->getID();
			if (!$db->getResult($queryStr))
				return false;
		} else {
			$queryStr = "INSERT INTO `tblArosAcos` (`aro`, `aco`, `read`) VALUES (".$aro->getID().", ".$aco->getID().", ".$perm.")";
			if (!$db->getResult($queryStr))
				return false;
		}
		return true;

	} /* }}} */

	public function remove($aro, $aco) { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "DELETE FROM `tblArosAcos` WHERE `aro`=".$aro->getID()." AND `aco`=".$aco->getID();
		if (!$db->getResult($queryStr))
			return false;
		return true;
	} /* }}} */
} /* }}} */

/**
 * Class to represent an access request/controll object
 *
 * This class provides a model for access request/controll objects.
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_AroAco { /* {{{ */
	/**
	 * @var object $dms reference to dms object.
	 * @access protected
	 */
	public $_dms;

	/**
	 * @var integer id of access request object
	 */
	protected $_id;

	/**
	 * @var integer id of parent of access request object
	 */
	protected $_parent;

	/**
	 * @var string alias of access request object
	 */
	protected $_alias;

	/**
	 * @var object object of access request object
	 */
	protected $_object;

	/**
	 * Create a new instance of an aro
	 *
	 * @param object $dms object of dms
	 * @return object instance of SeedDMS_Aco
	 */
	function __construct($dms, $id, $parent, $object, $alias) { /* {{{ */
		$this->_dmѕ = $dms;
		$this->_id = $id;
		$this->_parent = $parent;
		$this->_object = $object;
		$this->_alias = $alias;
	} /* }}} */

	public function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	public function getDMS() { /* {{{ */
		return($this->_dms);
	} /* }}} */

	public function getID() { /* {{{ */
		return $this->_id;
	} /* }}} */

	public function getAlias() { /* {{{ */
		return $this->_alias;
	} /* }}} */

	public function getObject() { /* {{{ */
		return $this->_object;
	} /* }}} */
} /* }}} */

/**
 * Class to represent an access request object
 *
 * This class provides a model for access request objects.
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Aro extends SeedDMS_AroAco { /* {{{ */

	/**
	 * Create a new instance of an aro
	 *
	 * @param object $dms object to access the underlying database
	 * @return object instance of SeedDMS_Aro
	 */
	public static function getInstance($id, $dms) { /* {{{ */
		$db = $dms->getDB();
		if(is_int($id)) {
			$queryStr = "SELECT * FROM `tblAros` WHERE `id` = " . (int) $id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr === false)
				return null;
			if (count($resArr) != 1)
				return null;
			$resArr = $resArr[0];
		} elseif(is_object($id)) {
			if($dms->getClassname('role') == get_class($id)) {
				$model = 'Role';
				$queryStr = "SELECT * FROM `tblAros` WHERE `model`=".$db->qstr($model)." AND `foreignid`=".$id->getID();
				$resArr = $db->getResultArray($queryStr);
				if (is_bool($resArr) && $resArr === false)
					return null;
				if (count($resArr) == 0) {
					$queryStr = "INSERT INTO `tblAros` (`parent`, `model`, `foreignid`) VALUES (0, ".$db->qstr($model).", ".$id->getID().")";
					if (!$db->getResult($queryStr))
						return null;
					$id = $db->getInsertID();
					$queryStr = "SELECT * FROM `tblAros` WHERE `id` = " . $id;
					$resArr = $db->getResultArray($queryStr);
				}
				$resArr = $resArr[0];
			} else {
				return null;
			}
		}

		if($resArr['model'] == 'Role') {
			$classname = $dms->getClassname('role');
			$object = $classname::getInstance($resArr['foreignid'], $dms);
		} else {
			$object = null;
		}

		$aro = new SeedDMS_Aro($dms, $resArr["id"], $resArr['parent'], $object, $resArr['alias']);
		$aro->setDMS($dms);
		return $aro;
	} /* }}} */

} /* }}} */

/**
 * Class to represent an access control object
 *
 * This class provides a model for access control objects.
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Aco extends SeedDMS_AroAco{ /* {{{ */

	/**
	 * Create a new instance of an aco
	 *
	 * @param object $dms object to access the underlying database
	 * @return object instance of SeedDMS_Aco
	 */
	public static function getInstance($id, $dms) { /* {{{ */
		$db = $dms->getDB();
		if(is_int($id)) {
			$queryStr = "SELECT * FROM `tblAcos` WHERE `id` = " . (int) $id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr === false)
				return null;
			if (count($resArr) == 0) {
				return null;
			}
			$resArr = $resArr[0];
		} elseif(is_string($id)) {
			$tmp = explode('/', $id);
			$parentid = 0;
			foreach($tmp as $part) {
				$queryStr = "SELECT * FROM `tblAcos` WHERE `alias` = " . $db->qstr($part);
//				if($parentid)
				$queryStr .= " AND parent=".$parentid;
				$resArr = $db->getResultArray($queryStr);
				if (is_bool($resArr) && $resArr === false)
					return null;
				if (count($resArr) == 0) {
					$queryStr = "INSERT INTO `tblAcos` (`parent`, `alias`, `model`) VALUES (".$parentid.",".$db->qstr($part).", '')";
					if (!$db->getResult($queryStr))
						return null;
					$id = $db->getInsertID();
					$queryStr = "SELECT * FROM `tblAcos` WHERE `id` = " . $id;
					$resArr = $db->getResultArray($queryStr);
				}
				$parentid = (int) $resArr[0]['id'];
			}
			$resArr = $resArr[0];
		}

		if($resArr['model'] == 'Document') {
			$classname = $dms->getClassname('document');
			$object = $classname::getInstance($resArr['foreignid'], $dms);
		} elseif($resArr['model'] == 'Folder') {
			$classname = $dms->getClassname('focument');
			$object = $classname::getInstance($resArr['foreignid'], $dms);
		} else {
			$object = null;
		}

		$aco = new SeedDMS_Aco($dms, $resArr["id"], $resArr['parent'], $object, $resArr['alias']);
		$aco->setDMS($dms);
		return $aco;
	} /* }}} */

	public function getChildren() { /* {{{ */
		$dms = $this->getDMS();
		$db = $dms->getDB();
		$queryStr = "SELECT * FROM `tblAcos` WHERE `parent` = ".$this->_id." ORDER BY `alias`";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return null;
		if (count($resArr) == 0)
			return null;

		$acos = array();
		foreach($resArr as $row) {
			$aco = new SeedDMS_Aco($dms, $row["id"], $row['parent'], null, $row['alias']);
			$aco->setDMS($dms);
			$acos[] = $aco;
		}
		return $acos;
	} /* }}} */

	public function getPermission($aro) { /* {{{ */
		if(!$aro)
			return 0;
		$dms = $this->getDMS();
		$db = $dms->getDB();
		$queryStr = "SELECT * FROM `tblArosAcos` WHERE `aro`=".$aro->getID()." AND `aco`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr) != 1)
			return 0;
		return (int) $resArr[0]['read'];
	} /* }}} */

	public static function getRoot($dms) { /* {{{ */
		$db = $dms->getDB();
		$queryStr = "SELECT * FROM `tblAcos` WHERE `parent` = 0 ORDER BY `alias`";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return null;

		$acos = array();
		foreach($resArr as $row) {
			$aco = new SeedDMS_Aco($dms, $row["id"], $row['parent'], null, $row['alias']);
			$aco->setDMS($dms);
			$acos[] = $aco;
		}
		return $acos;
	} /* }}} */

	public function getParent() { /* {{{ */
		$dms = $this->getDMS();
		$db = $dms->getDB();
		$queryStr = "SELECT * FROM `tblAcos` WHERE `id` = ".$this->_parent;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return null;
		if (count($resArr) != 1)
			return null;

		$row = $resArr[0];
		$aco = new SeedDMS_Aco($dms, $row["id"], $row['parent'], null, $row['alias']);
		$aco->setDMS($dms);
		return $aco;
	} /* }}} */
} /* }}} */
