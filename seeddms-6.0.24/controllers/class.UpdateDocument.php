<?php
/**
 * Implementation of UpdateDocument controller
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
class SeedDMS_Controller_UpdateDocument extends SeedDMS_Controller_Common {

	public function run() { /* {{{ */
		$name = $this->getParam('name');
		$comment = $this->getParam('comment');

		/* Call preUpdateDocument early, because it might need to modify some
		 * of the parameters.
		 */
		if(false === $this->callHook('preUpdateDocument', $this->params['document'])) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preUpdateDocument_failed';
			return null;
		}

		$comment = $this->getParam('comment');
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$document = $this->params['document'];
		$settings = $this->params['settings'];
		$fulltextservice = $this->params['fulltextservice'];
		$folder = $this->params['folder'];
		$userfiletmp = $this->getParam('userfiletmp');
		$userfilename = $this->getParam('userfilename');
		$filetype = $this->getParam('filetype');
		$userfiletype = $this->getParam('userfiletype');
		$reviewers = $this->getParam('reviewers');
		$approvers = $this->getParam('approvers');
		$recipients = $this->getParam('recipients');
		$reqversion = $this->getParam('reqversion');
		$comment = $this->getParam('comment');
		$attributes = $this->getParam('attributes');
		$workflow = $this->getParam('workflow');
		$maxsizeforfulltext = $this->getParam('maxsizeforfulltext');
		$initialdocumentstatus = $this->getParam('initialdocumentstatus');

		$content = $this->callHook('updateDocument');
		if($content === null) {
			$filesize = SeedDMS_Core_File::fileSize($userfiletmp);
			if($contentResult=$document->addContent($comment, $user, $userfiletmp, utf8_basename($userfilename), $filetype, $userfiletype, $reviewers, $approvers, $version=0, $attributes, $workflow, $initialdocumentstatus)) {

				if ($this->hasParam('expires')) {
					if($document->setExpires($this->getParam('expires'))) {
					} else {
					}
				}

				if(!empty($recipients['i'])) {
					foreach($recipients['i'] as $uid) {
						if($u = $dms->getUser($uid)) {
							$res = $contentResult->getContent()->addIndRecipient($u, $user);
						}
					}
				}
				if(!empty($recipients['g'])) {
					foreach($recipients['g'] as $gid) {
						if($g = $dms->getGroup($gid)) {
							$res = $contentResult->getContent()->addGrpRecipient($g, $user);
						}
					}
				}

				$content = $contentResult->getContent();
			} else {
				$this->errormsg = 'error_update_document';
				$result = false;
			}
		} elseif($result === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_updateDocument_failed';
			return false;
		}

		if($fulltextservice && ($index = $fulltextservice->Indexer()) && $content) {
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

		if(false === $this->callHook('postUpdateDocument', $document, $content)) {
		}

		return $content;
	} /* }}} */
}

