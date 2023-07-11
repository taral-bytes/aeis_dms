<?php

/**
 * Implementation of view class
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once "inc.ClassHook.php";

/**
 * Parent class for all view classes
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Common
{
	protected $theme;

	protected $params;

	protected $baseurl;

	protected $imgpath;

	public function __construct($params, $theme = 'bootstrap')
	{
		$this->theme = $theme;
		$this->params = $params;
		$this->baseurl = '';
		if (isset($params['settings']))
			$this->imgpath = $params['settings']->_httpRoot . 'views/' . $theme . '/images/';
		else
			$this->imgpath = '../views/' . $theme . '/images/';
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
	public function __invoke($get = array())
	{
		$action = null;
		$request = $this->getParam('request');
		if ($request) {
			if ($request->isMethod('get'))
				$action = $request->query->get('action');
			elseif ($request->isMethod('post'))
				$action = $request->request->get('action');
		}
		if (!$this->callHook('preRun', get_class($this), $action ? $action : 'show')) {
			if ($action) {
				if (method_exists($this, $action)) {
					$this->{$action}();
				} else {
					echo "Missing action '" . htmlspecialchars($action) . "'";
				}
			} else
				$this->show();
		} else {
			return false;
		}
		$this->callHook('postRun', $action ? $action : 'show');
	}

	public function setParams($params)
	{
		$this->params = $params;
	}

	public function setParam($name, $value)
	{
		$this->params[$name] = $value;
	}

	public function getParam($name)
	{
		if (isset($this->params[$name]))
			return $this->params[$name];
		return null;
	}

	/**
	 * Check if the view has a parameter with the given name
	 *
	 * @param string $name name of parameter
	 * @return boolean true if parameter exists otherwise false
	 */
	public function hasParam($name)
	{
		return isset($this->params[$name]) ? true : false;
	}

	public function unsetParam($name)
	{
		if (isset($this->params[$name]))
			unset($this->params[$name]);
	}

	public function setBaseUrl($baseurl)
	{
		$this->baseurl = $baseurl;
	}

	public function getTheme()
	{
		return $this->theme;
	}

	public function show()
	{ }

	/**
	 * Return a list of hook classes for the current class
	 *
	 * Hooks are associated to a view class. Calling a hook with
	 * SeedDMS_View_Common::callHook() will run through a list of
	 * view classes searching for the hook. This method returns this
	 * list.
	 *
	 * If a view is implemented in SeedDMS_View_Example which inherits
	 * from SeedDMS_Theme_Style which again inherits from SeedDMS_View_Common,
	 * then this method will return an array with the elments:
	 * 'Example', 'Style', 'Common'. If SeedDMS_View_Example also sets
	 * the class property 'viewAliasName', then this value will be added
	 * to the beginning of the list.
	 *
	 * When a hook is called, it will run through this list and checks
	 * if $GLOBALS['SEEDDMS_HOOKS']['view'][<element>] exists and contains
	 * an instanciated class. This class must implement the hook.
	 *
	 * @return array list of view class names.
	 */
	protected function getHookClassNames()
	{ /* {{{ */
		$tmps = array();
		/* the viewAliasName can be set in the view to specify a different name
		 * than extracted from the class name.
		 */
		if (property_exists($this, 'viewAliasName') && !empty($this->viewAliasName)) {
			$tmps[] = $this->viewAliasName;
		}
		$tmp = explode('_', get_class($this));
		$tmps[] = $tmp[2];
		foreach (class_parents($this) as $pc) {
			$tmp = explode('_', $pc);
			$tmps[] = $tmp[2];
		}
		/* Run array_unique() in case the parent class has the same suffix */
		$tmps = array_unique($tmps);
		return $tmps;
	} /* }}} */

	/**
	 * Call a hook with a given name
	 *
	 * Checks if a hook with the given name and for the current view
	 * exists and executes it. The name of the current view is taken
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
	public function callHook($hook)
	{ /* {{{ */
		$tmps = $this->getHookClassNames();
		$ret = null;
		foreach ($tmps as $tmp)
			if (isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)])) {
				foreach ($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)] as $hookObj) {
					if (method_exists($hookObj, $hook)) {
						switch (func_num_args()) {
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
						if ($tmpret !== null) {
							if (is_string($tmpret)) {
								$ret = ($ret === null) ? $tmpret : (is_string($ret) ? $ret . $tmpret : array_merge($ret, array($tmpret)));
							} elseif (is_array($tmpret) || is_object($tmpret)) {
								$ret = ($ret === null) ? $tmpret : (is_string($ret) ? array_merge(array($ret), $tmpret) : array_merge($ret, $tmpret));
							} else
								$ret = $tmpret;
						}
					}
				}
			}
		return $ret;
	} /* }}} */

	/**
	 * Return all hook objects for the given or calling class
	 *
	 * <code>
	 * <?php
	 * $hookObjs = $this->getHookObjects();
	 * foreach($hookObjs as $hookObj) {
	 *   if (method_exists($hookObj, $hook)) {
	 *     $ret = $hookObj->$hook($this, ...);
	 *     ...
	 *   }
	 * }
	 * ?>
	 * </code>
	 *
	 * The method does not return hooks for parent classes nor does it
	 * evaluate the viewAliasName property.
	 *
	 * @params string $classname name of class (current class if left empty)
	 * @return array list of hook objects registered for the class
	 */
	public function getHookObjects($classname = '')
	{ /* {{{ */
		if ($classname)
			$tmps = array(explode('_', $classname)[2]);
		else
			$tmps = $this->getHookClassNames();
		$hooks = [];
		foreach ($tmps as $tmp) {
			if (isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)])) {
				$hooks = array_merge($hooks, $GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)]);
			}
		}
		return $hooks;
	} /* }}} */

	/**
	 * Check if a hook is registered
	 *
	 * @param $hook string name of hook
	 * @return mixed false if one of the hooks fails,
	 *               true if all hooks succedded,
	 *               null if no hook was called
	 */
	public function hasHook($hook)
	{ /* {{{ */
		$tmps = $this->getHookClassNames();
		foreach ($tmps as $tmp) {
			if (isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)])) {
				foreach ($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp)] as $hookObj) {
					if (method_exists($hookObj, $hook)) {
						return true;
					}
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if the access on the view with given name or the current view itself
	 * may be accessed.
	 *
	 * The function requires the parameter 'accessobject' to be available in the
	 * view, because it calls SeedDMS_AccessOperation::check_view_access()
	 * to check access rights. If the the optional $name is not set the
	 * current view is used.
	 *
	 * If $name is an array then just one of the passed objects in the array
	 * must be accessible for this function to return true.
	 *
	 * @param string|array $name name of view or list of view names
	 * @return boolean true if access is allowed otherwise false
	 */
	protected function check_view_access($name = '')
	{ /* {{{ */
		if (!$name)
			$name = $this;
		if (!isset($this->params['accessobject']))
			return false;
		$access = $this->params['accessobject']->check_view_access($name);
		return $access;

		if (isset($this->params['user']) && $this->params['user']->isAdmin()) {
			if ($access === -1)
				return false;
			else
				return true;
		}

		return ($access === 1);
	} /* }}} */

	/**
	 * Create an url to a view
	 *
	 * @param string $name name of view
	 * @param array $urlparams list of url parameters
	 * @return string $url
	 */
	protected function html_url($view, $urlparams = array())
	{ /* {{{ */
		$url = $this->params['settings']->_httpRoot . "out/out." . $view . ".php";
		if (is_array($urlparams))
			$url .= "?" . http_build_query($urlparams);
		elseif (is_string($urlparams))
			$url .= "?" . $urlparams;
		return $url;
	} /* }}} */

	/**
	 * Create a html link to a view
	 *
	 * First checks if the view may be accessed by the user
	 *
	 * @param string $name name of view
	 * @param array $urlparams list of url parameters
	 * @param array $linkparams list of link attributes (e.g. class, target)
	 * @param string $link the link text itself
	 * @param boolean $hsc set to false if htmlspecialchars() shall not be called
	 * @return string link
	 */
	protected function html_link($view = '', $urlparams = array(), $linkparams = array(), $link, $hsc = true, $nocheck = false, $wrap = array())
	{ /* {{{ */
		if (!$nocheck)
			if (!$this->check_view_access($view))
				return '';
		$url = $this->html_url($view, $urlparams);
		$tag = "<a href=\"" . $url . "\"";
		if ($linkparams)
			foreach ($linkparams as $k => $v)
				$tag .= " " . $k . "=\"" . $v . "\"";
		$tag .= ">" . ($hsc ? htmlspecialchars($link) : $link) . "</a>";
		if (is_array($wrap) && count($wrap) == 2)
			return $wrap[0] . $tag . $wrap[1];
		return $tag;
	} /* }}} */

	public function jsTranslations($keys)
	{ /* {{{ */
		echo "var trans = {\n";
		foreach ($keys as $key) {
			echo "	'" . $key . "': '" . str_replace("'", "\\\'", getMLText($key)) . "',\n";
		}
		echo "};\n";
	} /* }}} */

	public static function getContrastColor($hexcolor)
	{ /* {{{ */
		$r = hexdec(substr($hexcolor, 1, 2));
		$g = hexdec(substr($hexcolor, 3, 2));
		$b = hexdec(substr($hexcolor, 5, 2));
		if (0) {
			$yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
			return ($yiq >= 148) ? '000000' : 'ffffff';
		} else {
			$l = (max($r, max($g, $b)) + min($r, min($g, $b))) / 2;
			return ($l > 128) ? '000000' : 'ffffff';
		}
	} /* }}} */
}
