<?php
/**
 * Implementation of EmptyFolder controller
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
class SeedDMS_Controller_EmptyFolder extends SeedDMS_Controller_Common {

	/* Register a callback which removes each document/folder from the fulltext index
	 * The callback must return null otherwise the removal will be canceled.
	 */
	static function removeFromIndex($arr, $object) { /* {{{ */
		$fulltextservice = $arr[0];
		$lucenesearch = $fulltextservice->Search();
		$hit = null;
		if($object->isType('document'))
			$hit = $lucenesearch->getDocument($object->getID());
		elseif($object->isType('folder'))
			$hit = $lucenesearch->getFolder($object->getID());
		if($hit) {
			$index = $fulltextservice->Indexer();
			$index->delete($hit->id);
			$index->commit();
		}
		return null;
	} /* }}} */

	static function removePreviews($arr, $document) { /* {{{ */
		$previewer = $arr[0];

		$previewer->deleteDocumentPreviews($document);
		return null;
	} /* }}} */

	public function run() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$folder = $this->params['folder'];
		$fulltextservice = $this->params['fulltextservice'];

		/* Get the folder id and name before removing the folder */
		$foldername = $folder->getName();
		$folderid = $folder->getID();

		if(false === $this->callHook('preEmptyFolder')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preEmptyFolder_failed';
			return false;
		}

		$result = $this->callHook('emptyFolder', $folder);
		if($result === null) {
			if($fulltextservice && ($index = $fulltextservice->Indexer())) {
				/* Register a callback which is called by SeedDMS_Core when a folder
				 * or document is removed. The second parameter passed to this callback
				 * is the document or folder to be removed.
				 */
				$dms->addCallback('onPreRemoveDocument', 'SeedDMS_Controller_EmptyFolder::removeFromIndex', array($fulltextservice));
				$dms->addCallback('onPreRemoveFolder', 'SeedDMS_Controller_EmptyFolder::removeFromIndex', array($fulltextservice));
			}

			/* Register another callback which removes the preview images of the document */
			$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir);
			$dms->addCallback('onPreRemoveDocument', 'SeedDMS_Controller_EmptyFolder::removePreviews', array($previewer));

			if (!$folder->emptyFolder()) {
				$this->errormsg = 'error_occured';
				return false;
			}
		} elseif($result === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_emptyFolder_failed';
			return false;
		}

		if(false === $this->callHook('postEmptyFolder')) {
		}

		return true;
	} /* }}} */
}
