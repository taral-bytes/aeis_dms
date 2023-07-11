<?php
/**
 * Initialize extensions
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

global $logger;

require "inc.ClassExtensionMgr.php";
require_once "inc.ClassSchedulerTaskBase.php";
require_once "inc.ClassExtBase.php";

$extMgr = new SeedDMS_Extension_Mgr($settings->_rootDir."/ext", $settings->_cacheDir, $settings->_repositoryUrl, $settings->_proxyUrl, $settings->_proxyUser, $settings->_proxyPassword);

$version = new SeedDMS_Version;

foreach($extMgr->getExtensionConfiguration() as $extname=>$extconf) {
	if(!$settings->extensionIsDisabled($extname)) {
		$disabled = true;
		if($extMgr->checkExtensionByName($extname, $extconf)) {
			$disabled = false;
		} else {
			$settings->disableExtension($extname);
		//	echo $extMgr->getErrorMsg();
		}
		/* check for requirements */
		/*
		if(!empty($extconf['constraints']['depends']['seeddms'])) {
			$t = explode('-', $extconf['constraints']['depends']['seeddms'], 2);
			if(SeedDMS_Extension_Mgr::cmpVersion($t[0], $version->version()) > 0 || ($t[1] && SeedDMS_Extension_Mgr::cmpVersion($t[1], $version->version()) < 0))
				$disabled = true;
			else
				$disabled = false;
		}
		 */
		if(!$disabled) {
			if(isset($extconf['class']) && isset($extconf['class']['file']) && isset($extconf['class']['name'])) {
				$classfile = $settings->_rootDir."/ext/".$extname."/".$extconf['class']['file'];
				if(file_exists($classfile)) {
					include($classfile);
					$obj = new $extconf['class']['name']($settings, null, $logger);
					if(method_exists($obj, 'init'))
						$obj->init($extMgr);
				}
			}
			if(isset($extconf['language']['file'])) {
				$langfile = $settings->_rootDir."/ext/".$extname."/".$extconf['language']['file'];
				if(file_exists($langfile)) {
					unset($__lang);
					include($langfile);
					if(isset($__lang) && $__lang) {
						foreach($__lang as $lang=>&$data) {
							if(isset($GLOBALS['LANG'][$lang]))
								$GLOBALS['LANG'][$lang] = array_merge($GLOBALS['LANG'][$lang], $data);
							else
								$GLOBALS['LANG'][$lang] = $data;
						}
					}
				}
			}
		}
	}
}
