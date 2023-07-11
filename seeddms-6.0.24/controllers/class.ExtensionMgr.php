<?php
/**
 * Implementation of ExtensionMgr controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2018 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for managing extensions
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2018 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_ExtensionMgr extends SeedDMS_Controller_Common {

	public function refresh() { /* {{{ */
		$dms = $this->params['dms'];
		$extmgr = $this->params['extmgr'];

		$extmgr->createExtensionConf();
		return true;
	} /* }}} */

	public function download() { /* {{{ */
		$dms = $this->params['dms'];
		$settings = $this->params['settings'];
		$extmgr = $this->params['extmgr'];
		$extname = $this->params['extname'];

		$filename = $extmgr->createArchive($extname, $extmgr->getExtensionConfiguration()[$extname]['version']);

		if(null === $this->callHook('download')) {
			if(file_exists($filename)) {
				header("Content-Transfer-Encoding: binary");
				header("Content-Disposition: attachment; filename=\"" . utf8_basename($filename) . "\"; filename*=UTF-8''".utf8_basename($filename));
				header("Content-Type: application/zip");
				header("Cache-Control: must-revalidate");

				sendFile($filename);
			}
		}
		return true;
	} /* }}} */

	public function upload() { /* {{{ */
		$dms = $this->params['dms'];
		$extmgr = $this->params['extmgr'];
		$file = $this->params['file'];

		if($extmgr->updateExtension($file)) {
			$extmgr->createExtensionConf();
		} else {
			$this->setErrorMsg($extmgr->getErrorMsg());
			return false;
		}
		return true;
	} /* }}} */

	public function getlist() { /* {{{ */
		$dms = $this->params['dms'];
		$extmgr = $this->params['extmgr'];
		$forceupdate = $this->params['forceupdate'];
		$version = $this->params['version'];

		if(!$extmgr->updateExtensionList($version, $forceupdate)) {
			$this->errormsg = $extmgr->getErrorMsg();
			return false;
		}

		return true;
	} /* }}} */

	public function toggle() { /* {{{ */
		$dms = $this->params['dms'];
		$settings = $this->params['settings'];
		$extmgr = $this->params['extmgr'];
		$extname = $this->params['extname'];

		if($settings->extensionIsDisabled($extname))
			$settings->enableExtension($extname);
		else
			$settings->disableExtension($extname);
		$settings->save();

		return true;
	} /* }}} */

}
