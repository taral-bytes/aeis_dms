<?php
/**
 * Implementation of conversion manager
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once("inc/inc.ClassConversionServiceExec.php");
require_once("inc/inc.ClassConversionServiceImageToImage.php");
require_once("inc/inc.ClassConversionServiceImageToText.php");
require_once("inc/inc.ClassConversionServicePdfToImage.php");
require_once("inc/inc.ClassConversionServiceTextToText.php");
require_once("inc/inc.ClassConversionServiceTextToImage.php");

/**
 * Implementation of conversion manager
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_ConversionMgr {
	/**
	 * List of services for converting documents
	 */
	public $services;

	/**
	 * @var $success set to false if conversion failed
	 */
	protected $success;

	public function __construct() { /* {{{ */
		$this->services = array();
		$this->success = true;
	} /* }}} */

	public function addService($service) { /* {{{ */
		$service->setConversionMgr($this);
		$this->services[$service->from][$service->to][] = $service;
		return $service;
	} /* }}} */

	public function hasService($from, $to) { /* {{{ */
		if(!empty($this->services[$from][$to]))
			return true;
		else
			return false;
	} /* }}} */

	/**
	 * Return the list of mimetypes which can be converted
	 * into the given mimetype
	 *
	 * @param string $askto mimetype to be converted into
	 * @return array list of from mimetypes 
	 */
	public function getFromWithTo($askto) { /* {{{ */
		$fromret = [];
		foreach($this->services as $from=>$toservices)
			foreach($toservices as $to=>$service)
				if($to == $askto)
					$fromret[] = $from;
		return $fromret;
	} /* }}} */

	/**
	 * Return the service that would be tried first for converting
	 * the document.
	 *
	 * The conversion manager may not use this service but choose a different
	 * one when it fails.
	 */
	public function getService($from, $to) { /* {{{ */
		if(!empty($this->services[$from][$to]))
			return end($this->services[$from][$to]);
		else
			return null;
	} /* }}} */

	public function getServices() { /* {{{ */
		return $this->services;
	} /* }}} */

	public function wasSuccessful() { /* {{{ */
		return $this->success;
	} /* }}} */

	/**
	 * Convert a file from one format into another format
	 *
	 * This method will try each conversion service until a service
	 * fails or was successful. If a service succeeds it must not
	 * return false, null, '' or 0
	 *
	 * @param string $file name of file to convert
	 * @param string $from mimetype of input file
	 * @param string $to   mimetype of output file
	 * @param string $target name of target file. If none is given the
	 * content of the converted document will be returned.
	 * @param array $params additional parameter needed for the conversion,
	 * e.g. the width of an image
	 *
	 * @return boolean true on success, other false
	 */
	public function convert($file, $from, $to, $target=null, $params=array()) { /* {{{ */
		if(isset($this->services[$from][$to])) {
			$services = $this->services[$from][$to];
			for(end($services); key($services)!==null; prev($services)) {
				$service = current($services);
				$text = $service->convert($file, $target, $params);
				if(!$service->wasSuccessful()) {
					$this->success = false;
					return false;
				}
				if($text)
					return $text;
			}
		}
		return true;
	} /* }}} */
}
