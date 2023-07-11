<?php
/**
 * Implementation of conversion service class
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once("inc/inc.ClassConversionServiceBase.php");

/**
 * Implementation of conversion service class for text to text
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_ConversionServiceTextToText extends SeedDMS_ConversionServiceBase {
	public function __construct($from, $to) {
		parent::__construct();
		$this->from = $from;
		$this->to = $to;
	}

	public function getInfo() {
		return "Pass through document contents";
	}

	public function convert($infile, $target = null, $params = array()) {
		if($target) {
			file_put_contents($target, file_get_contents($infile));
			return true;
		} else
			return file_get_contents($infile);
	}
}

