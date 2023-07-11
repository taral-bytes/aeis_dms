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
class SeedDMS_ConversionServiceImageToImage extends SeedDMS_ConversionServiceBase {
	/**
	 * timeout
	 */
	public $timeout;

	public function __construct($from, $to) { /* {{{ */
		parent::__construct();
		$this->from = $from;
		$this->to = $to;
		$this->timeout = 5;
	} /* }}} */

	public function getInfo() { /* {{{ */
		return "Convert with imagick or gd php functions";
	} /* }}} */

	public function getAdditionalParams() { /* {{{ */
		return [
			['name'=>'width', 'type'=>'number', 'description'=>'Width of converted image']
		];
	} /* }}} */

	/**
	 * Convert a pixel image into png and scale it
	 *
	 * This method uses imagick and if not available falls back to the gd library.
	 */
	public function convert($infile, $target = null, $params = array()) { /* {{{ */
		$start = microtime(true);
		if(extension_loaded('imagick')) {
			$imagick = new Imagick();
			try {
				if($imagick->readImage($infile)) {
					if(!empty($params['width']))
						$imagick->scaleImage(min((int) $params['width'], $imagick->getImageWidth()), 0);
					$end = microtime(true);
					if($this->logger) {
						$this->logger->log('Conversion from '.$this->from.' to '.$this->to.' with imagick service took '.($end-$start).' sec.', PEAR_LOG_INFO);
					}
					if($target) {
						return $imagick->writeImage($target);
					} else {
						return $imagick->getImageBlob();
					}
				}
			} catch (ImagickException $e) {
				return false;
			}
		} elseif(extension_loaded('gd')) {
			$im = null;
			switch($this->from) {
			case 'image/jpeg':
			case 'image/jpg':
				$im = @imagecreatefromjpeg($infile);
				break;
			case 'image/png':
				$im = @imagecreatefrompng($infile);
				break;
			case 'image/gif':
				$im = @imagecreatefromgif($infile);
				break;
			}
			if($im) {
				$size = getimagesize($infile);
				if(!empty($params['width']))
					$im = imagescale($im, min((int) $params['width'], $size[0]));
				$end = microtime(true);
				if($this->logger) {
					$this->logger->log('Conversion from '.$this->from.' to '.$this->to.' with gd image service took '.($end-$start).' sec.', PEAR_LOG_INFO);
				}
				if($target) {
					return imagepng($im, $target);
				} else {
					ob_start();
					var_dump(imagepng($im));
					$image = ob_get_clean();
					return $image;
				}
			} else {
				return false;
			}
		}
		return false;
	} /* }}} */
}


