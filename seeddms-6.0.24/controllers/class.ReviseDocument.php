<?php
/**
 * Implementation of ReviseDocument controller
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
class SeedDMS_Controller_ReviseDocument extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$document = $this->params['document'];
		$content = $this->params['content'];
		$revisionstatus = $this->params['revisionstatus'];
		$revisiontype = $this->params['revisiontype'];
		$group = $this->params['group'];
		$comment = $this->params['comment'];

		/* if set to true, a single reject will reject the doc. If set to false
		 * all revisions will be collected first and afterwards the doc is rejected
		 * if one has rejected it. So in the very end the doc is rejected, but
		 * doc remainѕ in S_IN_REVISION until all have revised the doc
		 */
		$onevotereject = $this->params['onevotereject'];

		/* Get the document id and name before removing the document */
		$docname = $document->getName();
		$documentid = $document->getID();

		if(!$this->callHook('preReviseDocument', $content)) {
		}

		$result = $this->callHook('reviseDocument', $content);
		if($result === null) {

			if ($revisiontype == "ind") {
				if(0 > $content->setRevision($user, $user, $revisionstatus, $comment)) {
					$this->error = 1;
					$this->errormsg = "revision_update_failed";
					return false;
				}
			} elseif ($revisiontype == "grp") {
				if(0 > $content->setRevision($group, $user, $revisionstatus, $comment)) {
					$this->error = 1;
					$this->errormsg = $ll."revision_update_failed";
					return false;
				}
			}
		}

		/* Check to see if the overall status for the document version needs to be
		 * updated.
		 */
		$result = $this->callHook('reviseUpdateDocumentStatus', $content);
		if($result === null) {
			if ($onevotereject && $revisionstatus == -1){
				if(!$content->setStatus(S_NEEDS_CORRECTION,$comment,$user)) {
					$this->error = 1;
					$this->errormsg = "revision_update_failed";
					return false;
				}
			} else {
				$docRevisionStatus = $content->getRevisionStatus();
				if (is_bool($docRevisionStatus) && !$docRevisionStatus) {
					$this->error = 1;
					$this->errormsg = "cannot_retrieve_revision_snapshot";
					return false;
				}
				$revisionok = 0;
				$revisionnotok = 0;
				$revisionTotal = 0;
				foreach ($docRevisionStatus as $drstat) {
					if ($drstat["status"] == 1) {
						$revisionok++;
					}
					if ($drstat["status"] == -1) {
						$revisionnotok++;
					}
					if ($drstat["status"] != -2) {
						$revisionTotal++;
					}
				}
				// If all revisions have been done and there are no rejections,
				// then release the document. If all revisions have been done but some
				// of them were rejections then documents needs correction.
				// Otherwise put it back into revision workflow
				if ($revisionok == $revisionTotal) {
					$newStatus=S_RELEASED;
					if ($content->finishRevision($user, $newStatus, 'Finished revision workflow', getMLText("automatic_status_update"))) {
						if(!$this->callHook('finishReviseDocument', $content)) {
						}
					}
				} elseif (($revisionok + $revisionnotok) == $revisionTotal) {
					$newStatus=S_NEEDS_CORRECTION;
//					if ($content->finishRevision($user, $newStatus, 'Finished revision workflow', getMLText("automatic_status_update"))) {
					if(!$content->setStatus($newStatus,$comment,$user)) {
						$this->error = 1;
						$this->errormsg = "revision_update_failed";
						return false;
					}
				} else {
					$newStatus=S_IN_REVISION;
					if(!$content->setStatus($newStatus,$comment,$user)) {
						$this->error = 1;
						$this->errormsg = "revision_update_failed";
						return false;
					}
				}
			}
		}

		if(!$this->callHook('postReviseDocument', $content)) {
		}

		return true;
	}
}

