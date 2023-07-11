<?php
/**
 * Implementation of conversion service class
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2023 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once("inc/inc.ClassConversionServiceBase.php");

/**
 * Implementation of conversion service from text to image
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2023 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_ConversionServiceTextToImage extends SeedDMS_ConversionServiceBase {
	public function __construct($from, $to) {
		parent::__construct();
		$this->from = $from;
		$this->to = $to;
	}

	public function getInfo() {
		return "Convert with imagick php functions";
	}

	public function getAdditionalParams() { /* {{{ */
		return [
			['name'=>'width', 'type'=>'number', 'description'=>'Width of converted image'],
			['name'=>'page', 'type'=>'number', 'description'=>'Page of text document'],
		];
	} /* }}} */

	private function wordWrapAnnotation($image, $draw, $text, $maxWidth) { /* {{{ */
		$words = preg_split('%\s%', trim($text), -1, PREG_SPLIT_NO_EMPTY);
		$lines = array();
		$i = 0;
		$lineHeight = 0;

		while (count($words) > 0) {
			$metrics = $image->queryFontMetrics($draw, implode(' ', array_slice($words, 0, ++$i)));
			$lineHeight = max($metrics['textHeight'], $lineHeight);

			// check if we have found the word that exceeds the line width
			if ($metrics['textWidth'] > $maxWidth or count($words) < $i) {
				// handle case where a single word is longer than the allowed line width (just add this as a word on its own line?)
				if ($i == 1)
					$i++;

				$lines[] = implode(' ', array_slice($words, 0, --$i));
				$words = array_slice($words, $i);
				$i = 0;
			}
		}

		return array($lines, $lineHeight);
	} /* }}} */

	public function convert($infile, $target = null, $params = array()) { /* {{{ */
		$boxWidth = 596;
		$boxHeight = 842;
		$boxTop = 30;
		$boxBottom = 30;
		$boxLeft = 30;
		$boxRight = 30;
		$parSep = 10;
		$fontSize = 10;

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
			if($imagick->newImage($boxWidth, $boxHeight, "white")) {
				$draw = new ImagickDraw();
				$draw->setStrokeColor("none");
				$draw->setFont("Courier");
				$draw->setFontSize($fontSize);
				$draw->setTextAlignment(Imagick::ALIGN_LEFT);

				$content = file_get_contents($infile);
				$lines = preg_split('~\R~',$content);
				$boxY = $boxTop;
				$pagecount = 0;
				foreach($lines as $line) {
					if($line) {
						$rlines = $this->wordWrapAnnotation($imagick, $draw, $line, $boxWidth-$boxLeft-$boxRight);
						foreach($rlines[0] as $rline) {
							if($pagecount == $page && $boxY < ($boxHeight-$boxBottom)) {
								$imagick->annotateImage($draw, $boxLeft, $boxY, 0, $rline);
							}
							$boxY = $boxY + $rlines[1];
						}
					} else {
						$boxY += $parSep;
					}
					if($boxY >= ($boxHeight-$boxBottom)) {
						$pagecount++;
						$boxY = $boxTop;
						if($pagecount > $page)
							break;
					}
				}

				if(!empty($params['width']))
					$imagick->scaleImage(min((int) $params['width'], $imagick->getImageWidth()), 0);
				$imagick->setImageFormat('png');
				$end = microtime(true);
				if($this->logger) {
					$this->logger->log('Conversion from '.$this->from.' to '.$this->to.' with text service took '.($end-$start).' sec.', PEAR_LOG_INFO);
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
		return false;
	} /* }}} */
}



