<?php
/**
 * Implementation of Role manager controller
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
 * Class which does the busines logic for role manager
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_RoleMgr extends SeedDMS_Controller_Common {

	public function run() {
	}

	public function addrole() {
		$dms = $this->params['dms'];
		$name = $this->params['name'];
		$role = $this->params['role'];

		return($dms->addRole($name, $role));
	}

	public function removerole() {
		$roleobj = $this->params['roleobj'];
		return $roleobj->remove();
	}

	public function editrole() {
		$dms = $this->params['dms'];
		$name = $this->params['name'];
		$role = $this->params['role'];
		$roleobj = $this->params['roleobj'];
		$noaccess = $this->params['noaccess'];

		if ($roleobj->getName() != $name)
			$roleobj->setName($name);
		if ($roleobj->getRole() != $role)
			$roleobj->setRole($role);
		$roleobj->setNoAccess($noaccess);

		return true;
	}
}
