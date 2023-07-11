<?php
/**
 * Implementation of a document
 *
 * @category   DMS
 * @package    SeedDMS_SQLiteFTS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class for managing a document.
 *
 * @category   DMS
 * @package    SeedDMS_SQLiteFTS
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_SQLiteFTS_Document {

	/**
	 * @var integer $id id of document
	 * @access protected
	 */
	public $id;

	/**
	 * @var array $fields fields
	 * @access protected
	 */
	protected $fields;

	public function ___get($key) { /* {{{ */
		if(isset($this->fields[$key]))
			return $this->fields[$key];
		else
			return false;
	} /* }}} */

	public function _addField($key, $value) { /* {{{ */
		//if($key == 'document_id') {
		if($key == 'docid') {
			$this->id = $this->fields[$key] = (int) $value;
		} else {
			if(isset($this->fields[$key]))
				$this->fields[$key] .= ' '.$value;
			else
				$this->fields[$key] = $value;
		}
	} /* }}} */

	public function addField(SeedDMS_SQLiteFTS_Field $field) { /* {{{ */
		$this->fields[$field->name] = $field;
		if($field->name == 'docid') {
			$this->id = $field->value;
		} 
		return $this;
	} /* }}} */

	/**
	 * Return an array with the names of the fields in this document.
	 *
	 * @return array
	 */
	public function getFieldNames() {
		return array_keys($this->fields);
	}

	public function _getFieldValue($key) { /* {{{ */
		if(isset($this->fields[$key]))
			return $this->fields[$key];
		else
			return false;
	} /* }}} */

	/**
	 * Proxy method for getFieldValue(), provides more convenient access to
	 * the string value of a field.
	 *
	 * @param  string $name
	 * @return string
	 */
	public function __get($name) {
		return $this->getFieldValue($name);
	}

	/**
	 * Returns Zend_Search_Lucene_Field object for a named field in this document.
	 *
	 * @param string $fieldName
	 * @return Zend_Search_Lucene_Field
	 */
	public function getField($fieldName) {
		if (!array_key_exists($fieldName, $this->fields)) {
			require_once 'Exception.php';
			throw new SeedDMS_SQLiteFTS_Exception("Field name \"$fieldName\" not found in document.");
		}
		return $this->fields[$fieldName];
	}

	/**
	 * Returns the string value of a named field in this document.
	 *
	 * @see __get()
	 * @return string
	 */
	public function getFieldValue($fieldName) {
		return $this->getField($fieldName)->value;
	}
}
?>
