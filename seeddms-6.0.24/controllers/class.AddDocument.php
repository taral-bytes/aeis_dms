<?php
/**
 * Implementation of AddDocument controller
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
class SeedDMS_Controller_AddDocument extends SeedDMS_Controller_Common {

	public function run() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$fulltextservice = $this->params['fulltextservice'];
		$folder = $this->params['folder'];

		/* Call preAddDocument early, because it might need to modify some
		 * of the parameters.
		 */
		if(false === $this->callHook('preAddDocument')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preAddDocument_failed';
			return null;
		}

		$name = $this->getParam('name');
		$comment = $this->getParam('comment');
		$documentsource = $this->params['documentsource'];
		$expires = $this->getParam('expires');
		$keywords = $this->getParam('keywords');
		$cats = $this->getParam('categories');
		$owner = $this->getParam('owner');
		$userfiletmp = $this->getParam('userfiletmp');
		$userfilename = $this->getParam('userfilename');
		$filetype = $this->getParam('filetype');
		$userfiletype = $this->getParam('userfiletype');
		$sequence = $this->getParam('sequence');
		$reviewers = $this->getParam('reviewers');
		$approvers = $this->getParam('approvers');
		$recipients = $this->getParam('recipients');
		$reqversion = $this->getParam('reqversion');
		$version_comment = $this->getParam('versioncomment');
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
		if($attributes_version = $this->getParam('attributesversion')) {
			foreach($attributes_version as $attrdefid=>$attribute) {
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
		}
		$workflow = $this->getParam('workflow');
		$notificationgroups = $this->getParam('notificationgroups');
		$notificationusers = $this->getParam('notificationusers');
		$initialdocumentstatus = $this->getParam('initialdocumentstatus');
		$maxsizeforfulltext = $this->getParam('maxsizeforfulltext');
		$defaultaccessdocs = $this->getParam('defaultaccessdocs');

		$document = $this->callHook('addDocument');
		if($document === null) {
			$filesize = SeedDMS_Core_File::fileSize($userfiletmp);
			$res = $folder->addDocument($name, $comment, $expires, $owner, $keywords,
															$cats, $userfiletmp, utf8_basename($userfilename),
	                            $filetype, $userfiletype, $sequence,
	                            $reviewers, $approvers, $reqversion,
	                            $version_comment, $attributes, $attributes_version, $workflow, $initialdocumentstatus);

			if (is_bool($res) && !$res) {
				$this->errormsg = "error_occured";
				return false;
			}

			$document = $res[0];

			/* Set access as specified in settings. */
			if($defaultaccessdocs) {
				if($defaultaccessdocs > 0 && $defaultaccessdocs < 4) {
					$document->setInheritAccess(0, true);
					$document->setDefaultAccess($defaultaccessdocs, true);
				}
			}

			$lc = $document->getLatestContent();
			if($recipients) {
				if($recipients['i']) {
					foreach($recipients['i'] as $uid) {
						if($u = $dms->getUser($uid)) {
							$res = $lc->addIndRecipient($u, $user);
						}
					}
				}
				if($recipients['g']) {
					foreach($recipients['g'] as $gid) {
						if($g = $dms->getGroup($gid)) {
							$res = $lc->addGrpRecipient($g, $user);
						}
					}
				}
			}

			/* Add a default notification for the owner of the document */
			if($settings->_enableOwnerNotification) {
				$res = $document->addNotify($owner->getID(), true);
			}
			/* Check if additional notification shall be added */
			foreach($notificationusers as $notuser) {
				if($document->getAccessMode($notuser) >= M_READ)
					$res = $document->addNotify($notuser->getID(), true);
			}
			foreach($notificationgroups as $notgroup) {
				if($document->getGroupAccessMode($notgroup) >= M_READ)
					$res = $document->addNotify($notgroup->getID(), false);
			}
		} elseif($document === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_addDocument_failed';
			return false;
		}

		if($fulltextservice && ($index = $fulltextservice->Indexer()) && $document) {
			$idoc = $fulltextservice->IndexedDocument($document);
			if(false !== $this->callHook('preIndexDocument', $document, $idoc)) {
				$index->addDocument($idoc);
				$index->commit();
			}
		}

		if(false === $this->callHook('postAddDocument', $document)) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_postAddDocument_failed';
			return false;
		}

		return $document;
	} /* }}} */
}

