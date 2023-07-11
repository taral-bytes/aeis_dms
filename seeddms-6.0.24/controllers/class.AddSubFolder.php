<?php
/**
 * Implementation of AddSubFolder controller
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
 * Class which does the busines logic for downloading a document
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_AddSubFolder extends SeedDMS_Controller_Common {

	public function run() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$fulltextservice = $this->params['fulltextservice'];
		$folder = $this->params['folder'];

		/* Call preAddSubFolder early, because it might need to modify some
		 * of the parameters.
		 */
		if(false === $this->callHook('preAddSubFolder')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preAddSubFolder_failed';
			return false;
		}

		$name = $this->getParam('name');
		$comment = $this->getParam('comment');
		$sequence = $this->getParam('sequence');
		$attributes = $this->getParam('attributes');
		foreach($attributes as $attrdefid=>$attribute) {
			if($attrdef = $dms->getAttributeDefinition($attrdefid)) {
				if(null === ($ret = $this->callHook('validateAttribute', $attrdef, $attribute))) {
				if($attribute) {
					switch($attrdef->getType()) {
					case SeedDMS_Core_AttributeDefinition::type_date:
						$attribute = date('Y-m-d', makeTsFromDate($attribute));
						break;
					}
					if(!$attrdef->validate($attribute, null, true)) {
						$this->errormsg = getAttributeValidationError($attrdef->getValidationError(), $attrdef->getName(), $attribute);
						return false;
					}
				} elseif($attrdef->getMinValues() > 0) {
					$this->errormsg = array("attr_min_values", array("attrname"=>$attrdef->getName()));
					return false;
				}
				} else {
					if($ret === false)
						return false;
				}
			}
		}
		$notificationgroups = $this->getParam('notificationgroups');
		$notificationusers = $this->getParam('notificationusers');

		$subFolder = $this->callHook('addSubFolder');
		if($subFolder === null) {
			$subFolder = $folder->addSubFolder($name, $comment, $user, $sequence, $attributes);
			if (!is_object($subFolder)) {
				$this->errormsg = "error_occured";
				return false;
			}
			/* Check if additional notification shall be added */
			foreach($notificationusers as $notuser) {
				if($subFolder->getAccessMode($user) >= M_READ)
					$res = $subFolder->addNotify($notuser->getID(), true);
			}
			foreach($notificationgroups as $notgroup) {
				if($subFolder->getGroupAccessMode($notgroup) >= M_READ)
					$res = $subFolder->addNotify($notgroup->getID(), false);
			}
		} elseif($subFolder === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_addFolder_failed';
			return false;
		}

		if($fulltextservice && ($index = $fulltextservice->Indexer()) && $subFolder) {
			$idoc = $fulltextservice->IndexedDocument($subFolder);
			if(false !== $this->callHook('preIndexFolder', $subFolder, $idoc)) {
				$index->addDocument($idoc);
				$index->commit();
			}
		}

		if(false === $this->callHook('postAddSubFolder', $subFolder)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_postAddSubFoder_failed';
			return false;
		}

		return $subFolder;
	} /* }}} */
}

