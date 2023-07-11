<?php
/**
 * Implementation of the transmittal object in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a transmittal in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Transmittal {
	/**
	 * @var integer id of transmittal
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var string name of transmittal
	 *
	 * @access protected
	 */
	var $_name;

	/**
	 * @var string comment of transmittal
	 *
	 * @access protected
	 */
	var $_comment;

	/**
	 * @var boolean true if transmittal is public
	 *
	 * @access protected
	 */
	var $_isPublic;

	/**
	 * @var object user this transmittal belongs to
	 *
	 * @access protected
	 */
	var $_user;

	/**
	 * @var object date of creation
	 *
	 * @access protected
	 */
	var $_date;

	/**
	 * @var object items
	 *
	 * @access protected
	 */
	var $_items;

	/**
	 * @var object reference to the dms instance this user belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	function __construct($id, $user, $name, $comment, $isPublic=0, $date='') {
		$this->_id = $id;
		$this->_name = $name;
		$this->_comment = $comment;
		$this->_user = $user;
		$this->_isPublic = $isPublic;
		$this->_date = $date;
		$this->_items = array();
		$this->_dms = null;
	}

	/**
	 * Get an instance of a transmittal object
	 *
	 * @param string|integer $id id or name of transmittal, depending
	 * on the 3rd parameter.
	 * @param object $dms instance of dms
	 * @param string $by search by [id|name]. If this
	 * parameter is left empty, the user will be search by its Id.
	 * @return object instance of class SeedDMS_Core_Transmittal
	 */
	public static function getInstance($id, $dms, $by='') { /* {{{ */
		if(!$dms || get_class($dms) != 'SeedDMS_Core_DMS')
			return false;

		$db = $dms->getDB();

		switch($by) {
		case 'name':
			$queryStr = "SELECT * FROM `tblTransmittals` WHERE `name` = ".$db->qstr($id);
			break;
		default:
			$queryStr = "SELECT * FROM `tblTransmittals` WHERE id = " . (int) $id;
		}

		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$uclassname = $dms->getClassname('user');
		$user = $uclassname::getInstance($resArr['userID'], $dms);
		$transmittal = new self($resArr["id"], $user, $resArr["name"], $resArr["comment"], $resArr["public"], $resArr["date"]);
		$transmittal->setDMS($dms);
		return $transmittal;
	} /* }}} */

	/**
	 * Get all instances of a transmittal object
	 *
	 * @param string|integer $id id or name of transmittal, depending
	 * on the 3rd parameter.
	 * @param object $dms instance of dms
	 * @param string $by search by [id|name]. If this
	 * parameter is left empty, the user will be search by its Id.
	 * @return object instance of class SeedDMS_Core_Transmittal
	 */
	public static function getAllInstances($user, $orderby, $dms) { /* {{{ */
		$db = $dms->getDB();

		$queryStr = "SELECT * FROM `tblTransmittals`";
		if($user)
			$queryStr .= " WHERE `userID` = " . $user->getID();

		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;

		$uclassname = $dms->getClassname('user');
		$transmittals = array();
		foreach ($resArr as $res) {
			$user = $uclassname::getInstance($res['userID'], $dms);
			$transmittal = new self($res["id"], $user, $res["name"], $res["comment"], $res["public"], $res["date"]);
			$transmittal->setDMS($dms);
			$transmittals[] = $transmittal;
		}
		return $transmittals;
	} /* }}} */

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblTransmittals` SET `name` =".$db->qstr($newName)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	function getComment() { return $this->_comment; }

	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblTransmittals` SET `comment` =".$db->qstr($newComment)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	function getUser() { return $this->_user; }

	function getItems() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$this->_items) {
			$queryStr = "SELECT `tblTransmittalItems`.* FROM `tblTransmittalItems` ".
				"LEFT JOIN `tblDocuments` ON `tblTransmittalItems`.`document`=`tblDocuments`.`id` ".
				"WHERE `tblTransmittalItems`.`transmittal` = '". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_users = array();

			$classname = $this->_dms->getClassname('transmittalitem');
			foreach ($resArr as $row) {
				$document = $this->_dms->getDocument($row['document']);
				$content = $document->getContentByVersion($row['version']);
				$item = new $classname($row["id"], $this, $content, $row["date"]);
				array_push($this->_items, $item);
			}
		}
		return $this->_items;
	} /* }}} */

	function getSize() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$this->_items) {
			self::getItems();
		}

		$size = 0;
		foreach ($this->_items as $item) {
			if($content = $item->getContent()) {
				$size += $content->getFileSize();
			}
		}
		return $size;
	} /* }}} */

	/**
	 * Add an item to the transmittal
	 *
	 * @param object $item instance of SeedDMS_Core_DocumentContent
	 * @return boolean true if item could be added, otherwise false
	 */
	function addContent($item) { /* {{{ */
		$db = $this->_dms->getDB();

		if(get_class($item) != $this->_dms->getClassname('documentcontent'))
			return false;

		$document = $item->getDocument();
		$queryStr = "INSERT INTO `tblTransmittalItems` (`transmittal`, `document`, `version`, `date`) ".
			"VALUES ('". $this->_id ."', ".$document->getID().", ".$item->getVersion().", ".$db->getCurrentDatetime().")";
		$res=$db->getResult($queryStr);
		if(!$res) {
			return false;
		}
		$itemID = $db->getInsertID('tblTransmittalItems');

		return SeedDMS_Core_TransmittalItem::getInstance($itemID, $this->_dms);
	} /* }}} */

	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM `tblTransmittals` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		return true;
	} /* }}} */

}

/**
 * Class to represent a transmittal in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_TransmittalItem {
	/**
	 * @var integer id of transmittal item
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var object document content
	 *
	 * @access protected
	 */
	var $_content;

	/**
	 * @var object transmittal
	 *
	 * @access protected
	 */
	var $_transmittal;

	/**
	 * @var object date of creation
	 *
	 * @access protected
	 */
	var $_date;

	function __construct($id, $transmittal, $content, $date='') {
		$this->_id = $id;
		$this->_transmittal = $transmittal;
		$this->_content = $content;
		$this->_date = $date;
		$this->_dms = null;
	}

	public static function getInstance($id, $dms) { /* {{{ */
		if(!$dms || get_class($dms) != 'SeedDMS_Core_DMS')
			return false;

		$db = $dms->getDB();

		$queryStr = "SELECT * FROM `tblTransmittalItems` WHERE `id` = " . (int) $id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];

		$transmittal = SeedDMS_Core_Transmittal::getInstance($resArr['transmittal'], $dms);
		$dclassname = $dms->getClassname('document');
		$document = $dclassname::getInstance($resArr['document'], $dms);
		$content = $document->getContentByVersion((int) $resArr['version']);

		$item = new self($resArr["id"], $transmittal, $content, $resArr["date"]);
		$item->setDMS($dms);
		return $item;
	} /* }}} */

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getTransmittal() { return $this->_transmittal; }

	function getContent() { return $this->_content; }

	function getDate() { return $this->_date; }

	function remove() { /* {{{ */
		$db = $this->_dms->getDB();
		$transmittal = $this->_transmittal;

		$queryStr = "DELETE FROM `tblTransmittalItems` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		return true;
	} /* }}} */

	/**
	 * Check if the content referenzed by the transmittal item is unequal
	 * to the latest content of the document.
	 *
	 * This function updateѕ always to the latest version of the document,
	 * even if the version in the item is higher. This can happen if a
	 * version has been removed.
	 *
	 * @return boolean/integer false in case of an error, otherwise the new
	 * version.
	 */
	function updateContent() { /* {{{ */
		$db = $this->_dms->getDB();
		$transmittal = $this->_transmittal;

		$document = $this->_content->getDocument();
		$latestcontent = $document->getLatestContent();
		if($latestcontent->getVersion() != $this->_content->getVersion()) {
			$queryStr = "UPDATE `tblTransmittalItems` set `version` = ".$latestcontent->getVersion()." WHERE `id` = " . $this->_id;
			if (!$db->getResult($queryStr)) {
				return false;
			}
		}

		return $latestcontent->getVersion();
	} /* }}} */
}
?>
