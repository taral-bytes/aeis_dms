<?php
/**
 * Implementation of Transmittal Download controller
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
 * Class which does the busines logic for downloading a transmittal
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_TransmittalDownload extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$transmittal = $this->params['transmittal'];

		$items = $transmittal->getItems();
		if($items) {
			include("../inc/inc.ClassDownloadMgr.php");
			$downmgr = new SeedDMS_Download_Mgr();
			if($extraheader = $this->callHook('extraDownloadHeader'))
				$downmgr->addHeader($extraheader);

			foreach($items as $item) {
				$content = $item->getContent();
				$document = $content->getDocument();
				if ($document->getAccessMode($user) >= M_READ) {
					$extracols = $this->callHook('extraDownloadColumns', $document);
					$filename = $this->callHook('filenameDownloadItem', $content);
					if($rawcontent = $this->callHook('rawcontent', $content)) {
						$downmgr->addItem($content, $extracols, $rawcontent, $filename);
					} else
						$downmgr->addItem($content, $extracols, null, $filename);
				}
			}

			$filename = tempnam(sys_get_temp_dir(), 'transmittal-download-');
			if($filename) {
				if($downmgr->createArchive($filename)) {
					header("Content-Transfer-Encoding: binary");
					header("Content-Length: " . filesize($filename));
					header("Content-Disposition: attachment; filename=\"export-" .date('Y-m-d') . ".zip\"");
					header("Content-Type: application/zip");
					header("Cache-Control: must-revalidate");

					readfile($filename);
				} else {
				}
				unlink($filename);
			}
			exit;
		}
	}
}

