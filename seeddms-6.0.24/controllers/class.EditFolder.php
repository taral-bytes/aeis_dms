<?php
/**
 * Implementation of EditFolder controller
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
 * Class which does the busines logic for editing a folder
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_EditFolder extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$fulltextservice = $this->params['fulltextservice'];
		$folder = $this->params['folder'];

		if(false === $this->callHook('preEditFolder')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preEditFolder_failed';
			return null;
		}

		$result = $this->callHook('editFolder', $folder);
		if($result === null) {
			$name = $this->params['name'];
			if(($oldname = $folder->getName()) != $name)
				if(!$folder->setName($name))
					return false;

			$comment = $this->params['comment'];
			if(($oldcomment = $folder->getComment()) != $comment)
				if(!$folder->setComment($comment))
					return false;

			$attributes = $this->params['attributes'];
			$oldattributes = $folder->getAttributes();
			if($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					$attrdef = $dms->getAttributeDefinition($attrdefid);
					if(null === ($ret = $this->callHook('validateAttribute', $attrdef, $attribute))) {
					if($attribute) {
						switch($attrdef->getType()) {
						case SeedDMS_Core_AttributeDefinition::type_date:
							$attribute = date('Y-m-d', makeTsFromDate($attribute));
							break;
						}
						if(!$attrdef->validate($attribute, $folder, false)) {
							$this->errormsg	= getAttributeValidationText($attrdef->getValidationError(), $attrdef->getName(), $attribute);
							return false;
						}

						if(!isset($oldattributes[$attrdefid]) || $attribute != $oldattributes[$attrdefid]->getValue()) {
							if(!$folder->setAttributeValue($dms->getAttributeDefinition($attrdefid), $attribute))
								return false;
						}
					} elseif($attrdef->getMinValues() > 0) {
						$this->errormsg = getMLText("attr_min_values", array("attrname"=>$attrdef->getName()));
							return false;
					} elseif(isset($oldattributes[$attrdefid])) {
						if(!$folder->removeAttribute($dms->getAttributeDefinition($attrdefid)))
							return false;
					}
					} else {
						if($ret === false)
							return false;
					}
				}
			}
			foreach($oldattributes as $attrdefid=>$oldattribute) {
				if(!isset($attributes[$attrdefid])) {
					if(!$folder->removeAttribute($dms->getAttributeDefinition($attrdefid)))
						return false;
				}
			}

			$sequence = $this->params['sequence'];
			if(strcasecmp($sequence, "keep")) {
				if($folder->setSequence($sequence)) {
				} else {
					return false;
				}
			}

			/* There are various hooks in inc/inc.FulltextInit.php which will take
			 * care of reindexing it. They just delete the indexing date which is
			 * faster then indexing the folder completely
			 *
			if($fulltextservice && ($index = $fulltextservice->Indexer()) && $folder) {
				$idoc = $fulltextservice->IndexedDocument($folder);
				if(false !== $this->callHook('preIndexFolder', $folder, $idoc)) {
					$lucenesearch = $fulltextservice->Search();
					if($hit = $lucenesearch->getFolder((int) $folder->getId())) {
						$index->delete($hit->id);
					}
					$index->addDocument($idoc);
					$index->commit();
				}
			}
			 */
 
		} elseif($result === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_editFolder_failed';
			return false;
		}

		if(false === $this->callHook('postEditFolder')) {
		}

		return true;
	}
}
