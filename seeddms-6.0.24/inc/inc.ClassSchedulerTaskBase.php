<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2018 Uwe Steinmann <uwe@steinmann.cx>
*  All rights reserved
*
*  This script is part of the SeedDMS project. The SeedDMS project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Base class for scheduler task
 *
 * @author  Uwe Steinmann <uwe@steinmann.cx>
 * @package SeedDMS
 */
class SeedDMS_SchedulerTaskBase {
	var $dms;

	var $user;

	var $settings;

	var $logger;

	var $fulltextservice;

	var $notifier;

	/**
	 * Call a hook with a given name
	 *
	 * Checks if a hook with the given name and for the current task
	 * exists and executes it. The name of the current task is taken
	 * from the current class name by lower casing the first char.
	 * This function will execute all registered hooks in the order
	 * they were registered.
	 *
	 * Attention: as func_get_arg() cannot handle references passed to the hook,
	 * callHook() should not be called if that is required. In that case get
	 * a list of hook objects with getHookObjects() and call the hooks yourself.
	 *
	 * @params string $hook name of hook
	 * @return string concatenated string, merged arrays or whatever the hook
	 * function returns
	 */
	public function callHook($hook) { /* {{{ */
		$tmps = array();
		$tmp = explode('_', get_class($this));
		$tmps[] = $tmp[1];
		$tmp = explode('_', get_parent_class($this));
		$tmps[] = $tmp[1];
		/* Run array_unique() in case the parent class has the same suffix */
		$tmps = array_unique($tmps);
		$ret = null;
		foreach($tmps as $tmp)
		if(isset($GLOBALS['SEEDDMS_HOOKS']['task'][lcfirst($tmp)])) {
			foreach($GLOBALS['SEEDDMS_HOOKS']['task'][lcfirst($tmp)] as $hookObj) {
				if (method_exists($hookObj, $hook)) {
					switch(func_num_args()) {
						case 1:
							$tmpret = $hookObj->$hook($this);
							break;
						case 2:
							$tmpret = $hookObj->$hook($this, func_get_arg(1));
							break;
						case 3:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2));
							break;
						case 4:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2), func_get_arg(3));
							break;
						default:
						case 5:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2), func_get_arg(3), func_get_arg(4));
							break;
					}
					if($tmpret !== null) {
						if(is_string($tmpret)) {
							$ret = ($ret === null) ? $tmpret : (is_string($ret) ? $ret.$tmpret : array_merge($ret, array($tmpret)));
						} elseif(is_array($tmpret) || is_object($tmpret)) {
							$ret = ($ret === null) ? $tmpret : (is_string($ret) ? array_merge(array($ret), $tmpret) : array_merge($ret, $tmpret));
						} else
							$ret = $tmpret;
					}
				}
			}
		}
		return $ret;
	} /* }}} */

	public function __construct($dms=null, $user=null, $settings=null, $logger=null, $fulltextservice=null, $notifier=null, $conversionmgr=null) { /* {{{ */
		$this->dms = $dms;
		$this->user = $user;
		$this->settings = $settings;
		$this->logger = $logger;
		$this->fulltextservice = $fulltextservice;
		$this->notifier = $notifier;
		$this->conversionmgr = $conversionmgr;
	} /* }}} */

	public function execute(SeedDMS_SchedulerTask $task) { /* {{{ */
		return true;
	} /* }}} */

	public function getDescription() { /* {{{ */
		return '';
	} /* }}} */

	public function getAdditionalParams() { /* {{{ */
		return array();
	} /* }}} */

	public function getAdditionalParamByName($name) { /* {{{ */
		foreach($this->getAdditionalParams() as $param) {
			if($param['name'] == $name)
				return $param;
		}
		return null;
	} /* }}} */
}

?>
