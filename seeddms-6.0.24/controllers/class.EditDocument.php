<?php
/**
 * Implementation of EditDocument controller
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
 * Class which does the busines logic for editing a document
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_EditDocument extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$fulltextservice = $this->params['fulltextservice'];
		$document = $this->params['document'];
		$name = $this->params['name'];

		if(false === $this->callHook('preEditDocument')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preEditDocument_failed';
			return null;
		}

		$result = $this->callHook('editDocument', $document);
		if($result === null) {
			$name = $this->params['name'];
			$oldname = $document->getName();
			if($oldname != $name)
				if(!$document->setName($name))
					return false;

			$comment = $this->params['comment'];
			if(($oldcomment = $document->getComment()) != $comment)
				if(!$document->setComment($comment))
					return false;

			$expires = $this->params['expires'];
			$oldexpires = $document->getExpires();
			if ($expires != $oldexpires) {
				if(false === $this->callHook('preSetExpires', $document, $expires)) {
				}

				if(!$document->setExpires($expires)) {
					return false;
				}

				$document->verifyLastestContentExpriry();

				if(false === $this->callHook('postSetExpires', $document, $expires)) {
				}
			}

			$keywords = $this->params['keywords'];
			$oldkeywords = $document->getKeywords();
			if ($oldkeywords != $keywords) {
				if(false === $this->callHook('preSetKeywords', $document, $keywords, $oldkeywords)) {
				}

				if(!$document->setKeywords($keywords)) {
					return false;
				}

				if(false === $this->callHook('postSetKeywords', $document, $keywords, $oldkeywords)) {
				}
			}

			$categories = $this->params['categories'];
			$oldcategories = $document->getCategories();
			if($categories) {
				$categoriesarr = array();
				foreach($categories as $catid) {
					if($cat = $dms->getDocumentCategory($catid)) {
						$categoriesarr[] = $cat;
					}
					
				}
				$oldcatsids = array();
				foreach($oldcategories as $oldcategory)
					$oldcatsids[] = $oldcategory->getID();

				if (count($categoriesarr) != count($oldcategories) ||
						array_diff($categories, $oldcatsids)) {
					if(false === $this->callHook('preSetCategories', $document, $categoriesarr, $oldcategories)) {
					}
					if(!$document->setCategories($categoriesarr)) {
						return false;
					}
					if(false === $this->callHook('postSetCategories', $document, $categoriesarr, $oldcategories)) {
					}
				}
			} elseif($oldcategories) {
				if(false === $this->callHook('preSetCategories', $document, array(), $oldcategories)) {
				}
				if(!$document->setCategories(array())) {
					return false;
				}
				if(false === $this->callHook('postSetCategories', $document, array(), $oldcategories)) {
				}
			}

			$attributes = $this->params['attributes'];
			$oldattributes = $document->getAttributes();
			if($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					if($attrdef = $dms->getAttributeDefinition($attrdefid)) {
						if(null === ($ret = $this->callHook('validateAttribute', $attrdef, $attribute))) {
						if($attribute) {
							switch($attrdef->getType()) {
							case SeedDMS_Core_AttributeDefinition::type_date:
								$attribute = date('Y-m-d', makeTsFromDate($attribute));
								break;
							}
							if(!$attrdef->validate($attribute, $document, false)) {
								$this->errormsg	= getAttributeValidationError($attrdef->getValidationError(), $attrdef->getName(), $attribute);
								return false;
							}

							if(!isset($oldattributes[$attrdefid]) || $attribute != $oldattributes[$attrdefid]->getValue()) {
								if(!$document->setAttributeValue($dms->getAttributeDefinition($attrdefid), $attribute))
									return false;
							}
						} elseif($attrdef->getMinValues() > 0) {
							$this->errormsg = array("attr_min_values", array("attrname"=>$attrdef->getName()));
						} elseif(isset($oldattributes[$attrdefid])) {
							if(!$document->removeAttribute($dms->getAttributeDefinition($attrdefid)))
								return false;
						}
						} else {
							if($ret === false)
								return false;
						}
					}
				}
			}
			foreach($oldattributes as $attrdefid=>$oldattribute) {
				if(!isset($attributes[$attrdefid])) {
					if(!$document->removeAttribute($dms->getAttributeDefinition($attrdefid)))
						return false;
				}
			}

			$sequence = $this->params['sequence'];
			if(strcasecmp($sequence, "keep")) {
				if($document->setSequence($sequence)) {
				} else {
					return false;
				}
			}

			/* There are various hooks in inc/inc.FulltextInit.php which will take
			 * care of reindexing it. They just delete the indexing date which is
			 * faster then indexing the folder completely
			 *
			if($fulltextservice && ($index = $fulltextservice->Indexer()) && $document) {
				$idoc = $fulltextservice->IndexedDocument($document);
				if(false !== $this->callHook('preIndexDocument', $document, $idoc)) {
					$lucenesearch = $fulltextservice->Search();
					if($hit = $lucenesearch->getDocument((int) $document->getId())) {
						$index->delete($hit->id);
					}
					$index->addDocument($idoc);
					$index->commit();
				}
			}
			 */
 
		} elseif($result === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_editDocument_failed';
			return false;
		}

		if(false === $this->callHook('postEditDocument')) {
		}

		return true;
	}
}
