<?php
/**
 * Implementation of conversion service base class
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Implementation of conversion service base class
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */
abstract class SeedDMS_ConversionServiceBase {
	/**
	 * mimetype original file
	 */
	public $from;

	/**
	 * mimetype converted file
	 */
	public $to;

	/**
	 * logger
	 */
	protected $logger;

	/**
	 * conversion manager
	 */
	protected $conversionmgr;

	/**
	 * @var $success set to false if conversion failed
	 */
	protected $success;

	public function __construct() {
		$this->from = null;
		$this->to = null;
		$this->success = true;
		$this->logger = null;
		$this->conversionmgr = null;
	}

	public function setLogger($logger) {
		$this->logger = $logger;
	}

	public function setConversionMgr($conversionmgr) {
		$this->conversionmgr = $conversionmgr;
	}

	public function getConversionMgr() {
		return $this->conversionmgr;
	}

	public function getInfo() {
		return 'Conversion service';
	}

	public function getAdditionalParams() { /* {{{ */
		return [];
	} /* }}} */

	public function wasSuccessful() { /* {{{ */
		return $this->success;
	} /* }}} */

	/**
	 * This method does the conversion
	 *
	 * It either returns the content of converted file (if $target is null)
	 * or writes the converted file into $target and returns true on success
	 * or false on error.
	 */
	public function convert($infile, $target = null, $params = array()) {
		return false;
	}
}
