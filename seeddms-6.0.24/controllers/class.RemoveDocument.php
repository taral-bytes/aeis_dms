<?php
/**
 * Implementation of RemoveDocument controller
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
class SeedDMS_Controller_RemoveDocument extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$document = $this->params['document'];
		$fulltextservice = $this->params['fulltextservice'];

		$folder = $document->getFolder();

		/* Get the document id and name before removing the document */
		$docname = $document->getName();
		$documentid = $document->getID();

		if(false === $this->callHook('preRemoveDocument')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preRemoveDocument_failed';
			return false;
		}

		$result = $this->callHook('removeDocument', $document);
		if($result === null) {
			$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir);
			$previewer->deleteDocumentPreviews($document);
			if (!$document->remove()) {
				if($dms->lasterror)
					$this->errormsg = $dms->lasterror;
				else
					$this->errormsg = "error_occured";
				return false;
			}
		} elseif($result === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_removeDocument_failed';
			return false;
		}

		/* Remove the document from the fulltext index */
		if($fulltextservice && ($index = $fulltextservice->Indexer())) {
			$lucenesearch = $fulltextservice->Search();
			if($hit = $lucenesearch->getDocument($documentid)) {
				$index->delete($hit->id);
				$index->commit();
			}
		}

		if(false === $this->callHook('postRemoveDocument')) {
		}

		return true;
	}
}
