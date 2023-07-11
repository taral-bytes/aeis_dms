<?php
/**
 * Implementation of conversion service pdf class
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
 * Implementation of conversion service pdf class
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_ConversionServicePdfToImage extends SeedDMS_ConversionServiceBase {
	/**
	 * timeout
	 */
	public $timeout;

	public function __construct($from, $to) {
		parent::__construct();
		$this->from = $from;
		$this->to = $to;
		$this->timeout = 5;
	}

	public function getInfo() {
		return "Convert with imagick php functions";
	}

	public function getAdditionalParams() { /* {{{ */
		return [
			['name'=>'width', 'type'=>'number', 'description'=>'Width of converted image'],
			['name'=>'page', 'type'=>'number', 'description'=>'Page of Pdf document'],
		];
	} /* }}} */

	public function convert($infile, $target = null, $params = array()) {
		$start = microtime(true);
		$imagick = new Imagick();
		/* Setting a smaller resolution will speed up the conversion
		 * A resolution of 72,72 will create a 596x842 image
		 * Setting it to 36,36 will create a 298x421 image which should
		 * be sufficient in most cases, but keep in mind that images are
		 * not scaled up. Hence, a width of 400px still results in a 298px
		 * wide image
		 */
		$imagick->setResolution(72,72);
		$page = 0;
		if(!empty($params['page']) && intval($params['page']) > 0)
			$page = intval($params['page'])-1;
		try {
			if($imagick->readImage($infile.'['.$page.']')) {
				if(!empty($params['width']))
					$imagick->scaleImage(min((int) $params['width'], $imagick->getImageWidth()), 0);
				/* Remove alpha channel and set to white */
				$imagick->setImageBackgroundColor('white');
				/* Setting the color-type and bit-depth produces much smaller images
				 * because the default depth appears to be 16 bit
				 */
				$imagick->setOption('png:color-type', 6);
				$imagick->setOption('png:bit-depth', 8);
				$imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
				$imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
				$imagick->setImageFormat('png');
				$end = microtime(true);
				if($this->logger) {
					$this->logger->log('Conversion from '.$this->from.' to '.$this->to.' with pdf service took '.($end-$start).' sec.', PEAR_LOG_INFO);
				}
				if($target) {
					return $imagick->writeImage($target);
				} else {
					return $imagick->getImageBlob();
				}
			}
		} catch (ImagickException $e) {
			$this->success = false;
			return false;
		}
		return false;
	}
}



