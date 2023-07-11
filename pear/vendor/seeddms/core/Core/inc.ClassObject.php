<?php
/**
 * Implementation of an generic object in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL2
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class to represent a generic object in the document management system
 *
 * This is the base class for generic objects in SeedDMS.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Object { /* {{{ */
	/**
	 * @var integer unique id of object
	 */
	protected $_id;

	/**
	 * @var array list of attributes
	 */
	protected $_attributes;

	/**
	 * @var SeedDMS_Core_DMS back reference to document management system
	 */
	public $_dms;

	/**
	 * SeedDMS_Core_Object constructor.
	 * @param $id
	 */
	function __construct($id) { /* {{{ */
		$this->_id = $id;
		$this->_dms = null;
	} /* }}} */

	/**
	 * Check if this object is of a given type.
	 *
	 * This method must be implemened in the child class
	 *
	 * @param string $type type of object
	 */
	public function isType($type) {return false;}

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
	public function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	public function getDMS() { /* {{{ */
		return $this->_dms;
	} /* }}} */

	/**
	 * Return the internal id of the document
	 *
	 * @return integer id of document
	 */
	public function getID() { return $this->_id; }

	/**
	 * Returns all attributes set for the object
	 *
	 * @return array|bool
     */
	public function getAttributes() { /* {{{ */
		if (!$this->_attributes) {
			$db = $this->_dms->getDB();

			switch(get_class($this)) {
				case $this->_dms->getClassname('document'):
					$queryStr = "SELECT a.* FROM `tblDocumentAttributes` a LEFT JOIN `tblAttributeDefinitions` b ON a.`attrdef`=b.`id` WHERE a.`document` = " . $this->_id." ORDER BY b.`name`";
					break;
				case $this->_dms->getClassname('documentcontent'):
					$queryStr = "SELECT a.* FROM `tblDocumentContentAttributes` a LEFT JOIN `tblAttributeDefinitions` b ON a.`attrdef`=b.`id` WHERE a.`content` = " . $this->_id." ORDER BY b.`name`";
					break;
				case $this->_dms->getClassname('folder'):
					$queryStr = "SELECT a.* FROM `tblFolderAttributes` a LEFT JOIN `tblAttributeDefinitions` b ON a.`attrdef`=b.`id` WHERE a.`folder` = " . $this->_id." ORDER BY b.`name`";
					break;
				default:
					return false;
			}
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

			$this->_attributes = array();

			foreach ($resArr as $row) {
				$attrdef = $this->_dms->getAttributeDefinition($row['attrdef']);
				$attr = new SeedDMS_Core_Attribute($row["id"], $this, $attrdef, $row["value"]);
				$attr->setDMS($this->_dms);
				$this->_attributes[$attrdef->getId()] = $attr;
			}
		}
		return $this->_attributes;

	} /* }}} */

    /**
     * Returns an attribute of the object for the given attribute definition
     *
     * @param SeedDMS_Core_AttributeDefinition $attrdef
     * @return array|string value of attritbute or false. The value is an array
     * if the attribute is defined as multi value
     */
	public function getAttribute($attrdef) { /* {{{ */
		if (!$this->_attributes) {
			$this->getAttributes();
		}

		if (isset($this->_attributes[$attrdef->getId()])) {
			return $this->_attributes[$attrdef->getId()];
		} else {
			return false;
		}

	} /* }}} */

	/**
	 * Returns an attribute value of the object for the given attribute definition
	 *
	 * @param SeedDMS_Core_AttributeDefinition $attrdef
	 * @return array|string value of attritbute or false. The value is an array
	 * if the attribute is defined as multi value
	 */
	public function getAttributeValue($attrdef) { /* {{{ */
		if (!$this->_attributes) {
			$this->getAttributes();
		}

		if (isset($this->_attributes[$attrdef->getId()])) {
			$value = $this->_attributes[$attrdef->getId()]->getValue();
			if($attrdef->getMultipleValues()) {
				$sep = substr($value, 0, 1);
				$vsep = $attrdef->getValueSetSeparator();
				/* If the value doesn't start with the separator used in the value set,
				 * then assume that the value was not saved with a leading separator.
				 * This can happen, if the value was previously a single value from
				 * the value set and later turned into a multi value attribute.
				 */
				if($sep == $vsep)
					return(explode($sep, substr($value, 1)));
				else
					return(array($value));
			} else {
				return $this->_attributes[$attrdef->getId()]->getParsedValue();
			}
		} else
			return false;

	} /* }}} */

    /**
     * Returns an attribute value of the object for the given attribute definition
     *
     * This is a short cut for getAttribute($attrdef)->getValueAsArray() but
     * first checks if the object has an attribute for the given attribute
     * definition.
     *
     * @param SeedDMS_Core_AttributeDefinition $attrdef
     * @return array|bool
     * even if the attribute is not defined as multi value
     */
	public function getAttributeValueAsArray($attrdef) { /* {{{ */
		if (!$this->_attributes) {
			$this->getAttributes();
		}

		if (isset($this->_attributes[$attrdef->getId()])) {
			return $this->_attributes[$attrdef->getId()]->getValueAsArray();
		} else
			return false;

	} /* }}} */

	/**
	 * Returns an attribute value of the object for the given attribute definition
	 *
	 * This is a short cut for getAttribute($attrdef)->getValueAsString() but
	 * first checks if the object has an attribute for the given attribute
	 * definition.
	 *
     * @param SeedDMS_Core_AttributeDefinition $attrdef
	 * @return string value of attritbute or false. The value is always a string
	 * even if the attribute is defined as multi value
	 */
	public function getAttributeValueAsString($attrdef) { /* {{{ */
		if (!$this->_attributes) {
			$this->getAttributes();
		}

		if (isset($this->_attributes[$attrdef->getId()])) {
			return $this->_attributes[$attrdef->getId()]->getValue();
		} else
			return false;

	} /* }}} */

	/**
	 * Set an attribute of the object for the given attribute definition
	 *
	 * @param SeedDMS_Core_AttributeDefinition $attrdef definition of attribute
	 * @param array|string $value value of attribute, for multiple values this
	 * must be an array
	 * @return boolean true if operation was successful, otherwise false
	 */
	public function setAttributeValue($attrdef, $value) { /* {{{ */
		$db = $this->_dms->getDB();
		if (!$this->_attributes) {
			$this->getAttributes();
		}
		switch($attrdef->getType()) {
		case SeedDMS_Core_AttributeDefinition::type_boolean:
			$value = ($value === true || $value != '' || $value == 1) ? 1 : 0;
			break;
		}
		if($attrdef->getMultipleValues() && is_array($value)) {
			if(in_array($attrdef->getType(), array(SeedDMS_Core_AttributeDefinition::type_user, SeedDMS_Core_AttributeDefinition::type_group)))
				$sep = ',';
			else
				$sep = substr($attrdef->getValueSet(), 0, 1);
			$value = $sep.implode($sep, $value);
		}
		/* Handle the case if an attribute is not set already */
		if(!isset($this->_attributes[$attrdef->getId()])) {
			switch(get_class($this)) {
				case $this->_dms->getClassname('document'):
					$tablename = 'tblDocumentAttributes';
					$queryStr = "INSERT INTO `tblDocumentAttributes` (`document`, `attrdef`, `value`) VALUES (".$this->_id.", ".$attrdef->getId().", ".$db->qstr($value).")";
					break;
				case $this->_dms->getClassname('documentcontent'):
					$tablename = 'tblDocumentContentAttributes';
					$queryStr = "INSERT INTO `tblDocumentContentAttributes` (`content`, `attrdef`, `value`) VALUES (".$this->_id.", ".$attrdef->getId().", ".$db->qstr($value).")";
					break;
				case $this->_dms->getClassname('folder'):
					$tablename = 'tblFolderAttributes';
					$queryStr = "INSERT INTO `tblFolderAttributes` (`folder`, `attrdef`, `value`) VALUES (".$this->_id.", ".$attrdef->getId().", ".$db->qstr($value).")";
					break;
				default:
					return false;
			}
			$res = $db->getResult($queryStr);
			if (!$res)
				return false;

			$attr = new SeedDMS_Core_Attribute($db->getInsertID($tablename), $this, $attrdef, $value);
			$attr->setDMS($this->_dms);
			$this->_attributes[$attrdef->getId()] = $attr;

			/* Check if 'onPostAddAttribute' callback is set */
			if(isset($this->_dms->callbacks['onPostAddAttribute'])) {
				foreach($this->_dms->callbacks['onPostAddAttribute'] as $callback) {
					if(!call_user_func($callback[0], $callback[1], $this, $attrdef, $value)) {
					}
				}
			}

			return true;
		}

		/* The attribute already exists. setValue() will either update or delete it. */
		$this->_attributes[$attrdef->getId()]->setValue($value);

		return true;
	} /* }}} */

	/**
	 * Remove an attribute of the object for the given attribute definition
	 *
	 * FIXME: shouldn't this rather be setAttributeValue() with an empty value?
	 *
	 * @param SeedDMS_Core_AttributeDefinition $attrdef
	 * @return boolean true if operation was successful, otherwise false
	 */
	public function removeAttribute($attrdef) { /* {{{ */
		$db = $this->_dms->getDB();
		if (!$this->_attributes) {
			$this->getAttributes();
		}
		if(isset($this->_attributes[$attrdef->getId()])) {
			$oldvalue = $this->_attributes[$attrdef->getId()]->getValue();
			switch(get_class($this)) {
				case $this->_dms->getClassname('document'):
					$queryStr = "DELETE FROM `tblDocumentAttributes` WHERE `document`=".$this->_id." AND `attrdef`=".$attrdef->getId();
					break;
				case $this->_dms->getClassname('documentcontent'):
					$queryStr = "DELETE FROM `tblDocumentContentAttributes` WHERE `content`=".$this->_id." AND `attrdef`=".$attrdef->getId();
					break;
				case $this->_dms->getClassname('folder'):
					$queryStr = "DELETE FROM `tblFolderAttributes` WHERE `folder`=".$this->_id." AND `attrdef`=".$attrdef->getId();
					break;
				default:
					return false;
			}
			$res = $db->getResult($queryStr);
			if (!$res)
				return false;

			/* Check if 'onPostRemoveAttribute' callback is set */
			if(isset($this->_dms->callbacks['onPostRemoveAttribute'])) {
				foreach($this->_dms->callbacks['onPostRemoveAttribute'] as $callback) {
					if(!call_user_func($callback[0], $callback[1], $this, $attrdef, $oldvalue)) {
					}
				}
			}

			unset($this->_attributes[$attrdef->getId()]);
		}
		return true;
	} /* }}} */
} /* }}} */
