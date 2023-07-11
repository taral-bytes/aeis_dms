<?php
/**
 * SeedDMS_SQLiteFTS
 *
 * @category   SeedDMS
 * @package    SeedDMS
 * @copyright  Copyright (c) 2021 uwe@steinmann.cx
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
* @category   SeedDMS
* @package    SeedDMS
* @copyright  Copyright (c) 2021 uwe@steinmann.cx
* @license    http://framework.zend.com/license/new-bsd     New BSD License
*/
class SeedDMS_SQLiteFTS_Exception extends Exception
{
	/**
	 * Construct the exception
	 *
	 * @param  string $msg
	 * @param  int $code
	 * @param  Exception $previous
	 * @return void
	 */
	public function __construct($msg = '', $code = 0, Exception $previous = null) {
		parent::__construct($msg, (int) $code, $previous);
	}

	/**
	 * String representation of the exception
	 *
	 * @return string
	 */
	public function __toString() {
		return parent::__toString();
	}

}
