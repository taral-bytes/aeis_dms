<?php
/**
 * Implementation of conversion service image class
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
 * Implementation of conversion service image class
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_ConversionServiceImageToText extends SeedDMS_ConversionServiceBase {
	/**
	 * timeout
	 */
	public $timeout;

	public function __construct($from, $to) { /* {{{ */
		parent::__construct();
		$this->from = $from;
		$this->to = $to;
	} /* }}} */

	public function getInfo() { /* {{{ */
		return "Convert by extracting iptc data";
	} /* }}} */

	public function getAdditionalParams() { /* {{{ */
		return [
		];
	} /* }}} */

	/**
	 * Convert a pixel image into text by reading the iptc data
	 *
	 * This method uses getimagesize() to extract the data.
	 */
	public function convert($infile, $target = null, $params = array()) { /* {{{ */
		$start = microtime(true);
		$imsize = getimagesize($infile, $moreinfo);
		$txt = '';
		if(!empty($moreinfo['APP13'])) {
			$iptcdata = iptcparse($moreinfo['APP13']);
			foreach(['2#005', '2#015', '2#025', '2#105', '2#080', '2#115', '2#120'] as $key) {
				if(isset($iptcdata[$key]))
					$txt .= implode(' ', $iptcdata[$key])."\n";
			}
		}
		$end = microtime(true);
		if($this->logger) {
			$this->logger->log('Conversion from '.$this->from.' to '.$this->to.' by extracting iptc took '.($end-$start).' sec.', PEAR_LOG_INFO);
		}
		if($target) {
			file_put_contents($target, $txt);
			return true;
		} else {
			return $txt;
		}
	} /* }}} */
}


