<?php
/**
 * Implementation of the decorator pattern
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class which implements a simple decorator pattern
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Decorator {
	protected $o;

	public function __construct($object) {
		$this->o = $object;
	}

	public function __call($method, $args)
	{
		if (!method_exists($this->o, $method)) {
			throw new Exception("Undefined method $method attempt.");
		}
		/* In case the called method returns the object itself, then return this object */
		$result = call_user_func_array(array($this->o, $method), $args);
	 	return $result === $this->o ? $this : $result;
	}   
}

