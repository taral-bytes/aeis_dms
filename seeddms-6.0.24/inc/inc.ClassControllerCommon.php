<?php
//    SeedDMS. Document Management System
//    Copyright (C) 2013 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

class SeedDMS_Controller_Common {
	/**
	 * @var array $params list of parameters
	 * @access protected
	 */
	protected $params;

	/**
	 * @var integer $error error number of last run
	 * @access protected
	 */
	protected $error;

	/**
	 * @var string $errormsg error message of last run
	 * @access protected
	 */
	protected $errormsg;

	/**
	 * @var mixed $lasthookresult result of last hook
	 * @access protected
	 */
	protected $lasthookresult;

	public function __construct($params) {
		$this->params = $params;
		$this->error = 0;
		$this->errormsg = '';
	}

	/**
	 * Call method with name in $get['action']
	 *
	 * Until 5.1.26 (6.0.19) this method took the name of the
	 * controller method to run from the element 'action' passed
	 * in the array $get. Since 5.1.27 (6.0.20) a PSR7 Request
	 * object is available in the controller and used to get the
	 * action.
	 *
	 * @params array $get $_GET or $_POST variables (since 5.1.27 this is no longer used)
	 * @return mixed return value of called method
	 */
	public function __invoke($get=array()) {
		$action = null;
		if(!$action = $this->getParam('action')) {
			$request = $this->getParam('request');
			if($request) {
				if($request->isMethod('get'))
					$action = $request->query->get('action');
				elseif($request->isMethod('post'))
					$action = $request->request->get('action');
			}
		}
		if(!$this->callHook('preRun', get_class($this), $action ? $action : 'run')) {
			if($action) {
				if(method_exists($this, $action)) {
					return $this->{$action}();
				} else {
					echo "Missing action '".$action."'";
					return false;
				}
			} else
				return $this->run();
		} else {
			return false;
		}
		$this->callHook('postRun', get_class($this), $action ? $action : 'run');
	}

	public function setParams($params) {
		$this->params = $params;
	}

	public function setParam($name, $value) {
		$this->params[$name] = $value;
	}

	/**
	 * Return value of a parameter with the given name
	 *
	 * This function may return null if the parameter does not exist or
	 * has a value of null. If in doubt call hasParam() to check if the
	 * parameter exists.
	 *
	 * @param string $name name of parameter
	 * @return mixed value of parameter or null if parameter does not exist
	 */
	public function getParam($name) {
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}

	/**
	 * Check if the controller has a parameter with the given name
	 *
	 * @param string $name name of parameter
	 * @return boolean true if parameter exists otherwise false
	 */
	public function hasParam($name) {
		return isset($this->params[$name]) ? true : false;
	}

	/**
	 * Remove a parameter with the given name
	 *
	 * @param string $name name of parameter
	 */
	public function unsetParam($name) {
		if(isset($this->params[$name]))
			unset($this->params[$name]);
	}

	public function run() {
	}

	/**
	 * Get error number of last run
	 *
	 * @return integer error number
	 */
	public function getErrorNo() { /* {{{ */
		return $this->error;
	} /* }}} */

	/**
	 * Get error message of last run
	 *
	 * @return string error message
	 */
	public function getErrorMsg() { /* {{{ */
		return $this->errormsg;
	} /* }}} */

	/**
	 * Set error message
	 *
	 * @param string $msg error message
	 */
	public function setErrorMsg($msg) { /* {{{ */
		$this->errormsg = $msg;
	} /* }}} */

	/**
	 * Return a list of hook classes for the current class
	 *
	 * Hooks are associated to a controller class. Calling a hook with
	 * SeedDMS_View_Common::callHook() will run through a list of
	 * controller classes searching for the hook. This method returns this
	 * list.
	 *
	 * If a controller is implemented in SeedDMS_View_Example which inherits
	 * from SeedDMS_Theme_Style which again inherits from SeedDMS_View_Common,
	 * then this method will return an array with the elments:
	 * 'Example', 'Style', 'Common'. If SeedDMS_View_Example also sets
	 * the class property 'controllerAliasName', then this value will be added
	 * to the beginning of the list.
	 *
	 * When a hook is called, it will run through this list and checks
	 * if $GLOBALS['SEEDDMS_HOOKS']['controller'][<element>] exists and contains
	 * an instanciated class. This class must implement the hook.
	 *
	 * @return array list of controller class names.
	 */
	protected function getHookClassNames() { /* {{{ */
		$tmps = array();
		/* the controllerAliasName can be set in the controller to specify a different name
		 * than extracted from the class name.
		 */
		if(property_exists($this, 'controllerAliasName') && !empty($this->controllerAliasName)) {
			$tmps[] = $this->controllerAliasName;
		}
		$tmp = explode('_', get_class($this));
		$tmps[] = $tmp[2];
		foreach(class_parents($this) as $pc) {
			$tmp = explode('_', $pc);
			$tmps[] = $tmp[2];
		}
		/* Run array_unique() in case the parent class has the same suffix */
		$tmps = array_unique($tmps);
		return $tmps;
	} /* }}} */

	/**
	 * Call all hooks registered for a controller
	 *
	 * Executes all hooks registered for the current controller.
	 * A hook is just a php function which is passed the current controller and
	 * additional paramaters passed to this method.
	 *
	 * If a hook returns false, then no other hook will be called, because this
	 * method returns right away. If hook returns null, then this is treated like
	 * it was never called and the next hook is called. Any other value
	 * returned by a hook will be temporarily saved (possibly overwriting a value
	 * from a previously called hook) and the next hook is called.
	 * The temporarily saved return value is eventually returned by this method
	 * when all hooks are called and no following hook failed.
	 * The temporarily saved return value is also saved in a protected class
	 * variable $lasthookresult which is initialized to null. This could be used
	 * by following hooks to check if preceding hook did already succeed.
	 *
	 * Consider that a failing hook (returns false) will imediately quit this
	 * function an return false, even if a formerly called hook has succeeded.
	 * Also consider, that a second succeeding hook will overwrite the return value
	 * of a previously called hook.
	 * Third, keep in mind that there is no predefined order of hooks.
	 *
	 * Example 1: Assuming the hook 'loginRestrictions' in the 'Login' controller
	 * is implemented by two extensions.
	 * One extension restricts login to a certain time of the day and a second one
	 * checks the strength of the password. If the password strength is to low, it
	 * will prevent login. If the hook in the first extension allows login (returns true)
	 * and the second doesn't (returns false), then this method will return false.
	 * If the hook in the second extension doesn't care and therefore returns null, then
	 * this method will return true.
	 *
	 * Example 2: Assuming the hook 'authenticate' in the 'Login' controller
	 * is implemented by two extensions. This hook must return false if authentication
	 * fails, null if the hook does not care, or a
	 * valid user in case of a successful authentication.
	 * If the first extension is able to authenticate the user, the hook in the second
	 * extension will still be called and could fail. So the return value of this
	 * method is false. The second hook could actually succeed as well and return a
	 * different user than the first hook which will eventually be returned by this
	 * method. The last hook will always win. If you need to know if a previously
	 * called hook succeeded, you can check the class variable $lasthookresult in the
	 * hook.
	 *
	 * @param $hook string name of hook
	 * @return mixed false if one of the hooks fails,
	 *               true/value if all hooks succedded,
	 *               null if no hook was called
	 */
	function callHook($hook) { /* {{{ */
		$tmps = $this->getHookClassNames();
		$ret = null;
		foreach($tmps as $tmp)
		if(isset($GLOBALS['SEEDDMS_HOOKS']['controller'][lcfirst($tmp)])) {
			$this->lasthookresult = null;
			foreach($GLOBALS['SEEDDMS_HOOKS']['controller'][lcfirst($tmp)] as $hookObj) {
				if (method_exists($hookObj, $hook)) {
					switch(func_num_args()) {
						case 4:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2), func_get_arg(3));
							break;
						case 3:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2));
							break;
						case 2:
							$tmpret = $hookObj->$hook($this, func_get_arg(1));
							break;
						case 1:
						default:
							$tmpret = $hookObj->$hook($this);
					}
					if($tmpret === false) {
						return $tmpret;
					}
					if($tmpret !== null) {
						$this->lasthookresult = $tmpret;
						if(is_string($tmpret)) {
							$ret = ($ret === null) ? $tmpret : (is_string($ret) ? $ret.$tmpret : array_merge($ret, array($tmpret)));
						} elseif(is_array($tmpret)) { // || is_object($tmpret)) {
							$ret = ($ret === null) ? $tmpret : (is_string($ret) ? array_merge(array($ret), $tmpret) : array_merge($ret, $tmpret));
						} else
							$ret = $tmpret;
					}
				}
			}
//			return $this->lasthookresult;
		}
		return $ret;
	} /* }}} */

	/**
	 * Check if a hook is registered
	 *
	 * @param $hook string name of hook
	 * @return mixed false if one of the hooks fails,
	 *               true if all hooks succedded,
	 *               null if no hook was called
	 */
	function hasHook($hook) { /* {{{ */
		$tmps = $this->getHookClassNames();
		foreach($tmps as $tmp) {
			if(isset($GLOBALS['SEEDDMS_HOOKS']['controller'][lcfirst($tmp)])) {
				foreach($GLOBALS['SEEDDMS_HOOKS']['controller'][lcfirst($tmp)] as $hookObj) {
					if (method_exists($hookObj, $hook)) {
						return true;
					}
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if the access on the contoller with given name or the current
	 * controller itself may be accessed.
	 *
	 * The function requires the parameter 'accessobject' to be available in the
	 * controller, because it calls SeedDMS_AccessOperation::check_controller_access()
	 * to check access rights. If the the optional $name is not set the
	 * current controller is used.
	 *
	 * @param string|array $name name of controller or list of controller names
	 * @return boolean true if access is allowed otherwise false
	 */
	protected function check_access($name='') { /* {{{ */
		if(!$name)
			$name = $this;
		if(!isset($this->params['accessobject']))
			return false;
		$access = $this->params['accessobject']->check_controller_access($name);
		return $access;
	} /* }}} */

}
