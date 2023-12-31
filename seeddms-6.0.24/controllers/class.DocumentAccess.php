<?php
/**
 * Implementation of DocumentAccess controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2017 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for editing a folder
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2017 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_DocumentAccess extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$settings = $this->params['settings'];
		$action = $this->params['action'];

		return null;
	}

	// Change owner -----------------------------------------------------------
	public function setowner() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$settings = $this->params['settings'];

		if(false === $this->callHook('preSetOwner', $document)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preSetOwner_failed';
			return null;
		}
		$newowner = $this->params['newowner'];
		$oldowner = $document->getOwner();
		if($document->setOwner($newowner)) {
			if(false === $this->callHook('postSetOwner', $document, $oldowner)) {
				if(empty($this->errormsg))
					$this->errormsg = 'hook_postSetOwner_failed';
				return null;
			}
		}
		return true;
	}

	public function notinherit() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$settings = $this->params['settings'];

		if(false === $this->callHook('preSetNotInherit', $document)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preSetNotInherit_failed';
			return null;
		}

		/* Get default access before access is not longer inherited. This
		 * will return the default access from the parent folder.
		 */
		$defAccess = $document->getDefaultAccess();
		if(!$document->setInheritAccess(false)) {
			return false;
		}

		if(!$document->setDefaultAccess($defAccess)) {
			return false;
		}

		//copy ACL of parent folder
		$mode = $this->params['mode'];
		if ($mode == "copy") {
			$accessList = $folder->getAccessList();
			foreach ($accessList["users"] as $userAccess)
				$document->addAccess($userAccess->getMode(), $userAccess->getUserID(), true);
			foreach ($accessList["groups"] as $groupAccess)
				$document->addAccess($groupAccess->getMode(), $groupAccess->getGroupID(), false);
		}

		if(false === $this->callHook('postSetNotInherit', $document)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_postSetNotInherit_failed';
			return null;
		}
		return true;
	}

	public function inherit() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$settings = $this->params['settings'];

		if(false === $this->callHook('preSetInherit', $document)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preSetInherit_failed';
			return null;
		}
		if(!$document->clearAccessList() || !$document->setInheritAccess(true)) {
			return false;
		}

		if(false === $this->callHook('postSetInherit', $document)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_postSetInherit_failed';
			return null;
		}
		return true;
	}

	public function setdefault() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$settings = $this->params['settings'];

		if(false === $this->callHook('preSetDefault', $document)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preSetDefault_failed';
			return null;
		}

		$mode = $this->params['mode'];
		if(!$document->setDefaultAccess($mode)) {
			return false;
		}

		if(false === $this->callHook('postSetDefault', $document)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_postSetDefault_failed';
			return null;
		}
		return true;
	}

	public function editaccess() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$settings = $this->params['settings'];

		$mode = $this->params['mode'];
		$userid = $this->params['userid'];
		$groupid = $this->params['groupid'];
		if ($userid) {
			$document->changeAccess($mode, $userid, true);
		}
		elseif ($groupid) {
			$document->changeAccess($mode, $groupid, false);
		}
		return true;
	}

	public function delaccess() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$settings = $this->params['settings'];

		$userid = $this->params['userid'];
		$groupid = $this->params['groupid'];
		if ($userid) {
			$document->removeAccess($userid, true);
		}
		elseif ($groupid) {
			$document->removeAccess($groupid, false);
		}
		return true;
	}

	public function addaccess() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$settings = $this->params['settings'];

		$mode = $this->params['mode'];
		$userid = $this->params['userid'];
		$groupid = $this->params['groupid'];
		if ($userid && $userid != -1) {
			$document->addAccess($mode, $userid, true);
		}
		elseif ($groupid && $groupid != -1) {
			$document->addAccess($mode, $groupid, false);
		}
		return true;
	}
}
