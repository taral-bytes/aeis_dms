<?php
/**
 * Implementation of PdfPreview controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for previewing a document
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_PdfPreview extends SeedDMS_Controller_Common {

	public function run() {
		global $theme;
		$dms = $this->params['dms'];
		$type = $this->params['type'];
		$settings = $this->params['settings'];
		$conversionmgr = $this->params['conversionmgr'];

		switch($type) {
			case "version":
				$version = $this->params['version'];
				$document = $this->params['document'];
				if($version < 1) {
					$content = $this->callHook('documentLatestContent', $document);
					if($content === null)
						$content = $document->getLatestContent();
				} else {
					$content = $this->callHook('documentContent', $document, $version);
					if($content === null)
						$content = $document->getContentByVersion($version);
				}
				if (!is_object($content)) {
					$this->errormsg = 'invalid_version';
					return false;
				}
				/* set params['content'] for compatiblity with older extensions which
				 * expect the content in the controller
				 */
				$this->params['content'] = $content;
				if(null === $this->callHook('version')) {
					$previewer = new SeedDMS_Preview_PdfPreviewer($settings->_cacheDir, $settings->_cmdTimeout);
					if($conversionmgr)
						$previewer->setConversionMgr($conversionmgr);
					else
						$previewer->setConverters(isset($settings->_converters['pdf']) ? $settings->_converters['pdf'] : array());
					$previewer->setXsendfile($settings->_enableXsendfile);
					if(!$previewer->hasPreview($content)) {
						add_log_line("");
						if(!$previewer->createPreview($content)) {
							add_log_line("", PEAR_LOG_ERR);
						}
					}
					if(!$previewer->hasPreview($content)) {
						header('Content-Type: application/pdf');
						readfile('../views/'.$theme.'/images/empty.pdf');
						exit;
					}
					header('Content-Type: application/pdf');
					$previewer->getPreview($content);
				}
				break;
		}
		return true;
	}
}
