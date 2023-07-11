<?php
/**
 * Implementation of ApproveDocument controller
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
class SeedDMS_Controller_ApproveDocument extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$document = $this->params['document'];
		$content = $this->params['content'];
		$approvalstatus = $this->params['approvalstatus'];
		$approvaltype = $this->params['approvaltype'];
		$group = $this->params['group'];
		$comment = $this->params['comment'];
		$file = $this->params['file'];

		/* Get the document id and name before removing the document */
		$docname = $document->getName();
		$documentid = $document->getID();

		if(!$this->callHook('preApproveDocument', $content)) {
		}

		$result = $this->callHook('approveDocument', $content);
		if($result === null) {
			if ($approvaltype == "ind") {
				$approvalLogID = $content->setApprovalByInd($user, $user, $approvalstatus, $comment, $file);
				if($approvalLogID === false || 0 > $approvalLogID) {
					$this->error = 1;
					$this->errormsg = "approval_update_failed";
					return false;
				}
			} elseif ($approvaltype == "grp") {
				$approvalLogID = $content->setApprovalByGrp($group, $user, $approvalstatus, $comment, $file);
				if($approvalLogID === false || 0 > $approvalLogID) {
					$this->error = 1;
					$this->errormsg = "approval_update_failed";
					return false;
				}
			}
		}

		/* Check to see if the overall status for the document version needs to be
		 * updated.
		 */
		$result = $this->callHook('approveUpdateDocumentStatus', $content);
		if($result === null) {
			/* If document was rejected, set the document status to S_REJECTED right away */
			if ($approvalstatus == -1){
				if($content->setStatus(S_REJECTED,$comment,$user)) {
					$this->callHook('postApproveDocument', $content, S_REJECTED);
				}
			} else {
				$docApprovalStatus = $content->getApprovalStatus();
				if (is_bool($docApprovalStatus) && !$docApprovalStatus) {
					$this->error = 1;
					$this->errormsg = "cannot_retrieve_approval_snapshot";
					return false;
				}
				$approvalCT = 0;
				$approvalTotal = 0;
				foreach ($docApprovalStatus as $drstat) {
					if ($drstat["status"] == 1) {
						$approvalCT++;
					}
					if ($drstat["status"] != -2) {
						$approvalTotal++;
					}
				}
				// If all approvals have been received and there are no rejections, retrieve a
				// count of the approvals required for this document.
				if ($approvalCT == $approvalTotal) {
					// Change the status to released.
					if($content->setStatus(S_RELEASED, getMLText("automatic_status_update"), $user)) {
						$this->callHook('postApproveDocument', $content, S_RELEASED);
					}
				}
			}
		}

		if(!$this->callHook('postApproveDocument', $content)) {
		}

		return true;
	}
}

