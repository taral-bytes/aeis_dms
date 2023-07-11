<?php
/**
 * Implementation of a field
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
 * Class for managing a field.
 *
 * @category   DMS
 * @package    SeedDMS_SQLiteFTS
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_SQLiteFTS_Field {

	/**
	 * Field name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Field value
	 *
	 * @var boolean
	 */
	public $value;

	/**
	 * Object constructor
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function __construct($name, $value) {
		$this->name  = $name;
		$this->value = $value;
	}

	/**
	 * Constructs a String-valued Field that is not tokenized, but is indexed
	 * and stored.  Useful for non-text fields, e.g. date or url.
	 *
	 * @param string $name
	 * @param string $value
	 * @return SeedDMS_SQLiteFTS_Field
	 */
	public static function keyword($name, $value) {
			return new self($name, $value);
	}

	/**
	 * Constructs a String-valued Field that is tokenized and indexed,
	 * and is stored in the index, for return with hits.  Useful for short text
	 * fields, like "title" or "subject". Term vector will not be stored for this field.
	 *
	 * @param string $name
	 * @param string $value
	 * @return SeedDMS_SQLiteFTS_Field
	 */
	public static function text($name, $value) {
			return new self($name, $value);
	}

	/**
	 * Constructs a String-valued Field that is tokenized and indexed,
	 * but that is not stored in the index.
	 *
	 * @param string $name
	 * @param string $value
	 * @return SeedDMS_SQLiteFTS_Field
	 */
	public static function unStored($name, $value) {
		return new self($name, $value);
	}
}
