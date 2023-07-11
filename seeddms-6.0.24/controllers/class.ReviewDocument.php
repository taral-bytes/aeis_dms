<?php
/**
 * Implementation of ReviewDocument controller
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
class SeedDMS_Controller_ReviewDocument extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$document = $this->params['document'];
		$content = $this->params['content'];
		$reviewstatus = $this->params['reviewstatus'];
		$reviewtype = $this->params['reviewtype'];
		$group = $this->params['group'];
		$comment = $this->params['comment'];
		$file = $this->params['file'];

		/* Get the document id and name before removing the document */
		$docname = $document->getName();
		$documentid = $document->getID();

		if(!$this->callHook('preReviewDocument', $content)) {
		}

		$result = $this->callHook('reviewDocument', $content);
		if($result === null) {

			if ($reviewtype == "ind") {
				$reviewLogID = $content->setReviewByInd($user, $user, $reviewstatus, $comment, $file);
				if($reviewLogID === false || 0 > $reviewLogID) {
					$this->error = 1;
					$this->errormsg = "review_update_failed";
					return false;
				}
			} elseif ($reviewtype == "grp") {
				$reviewLogID = $content->setReviewByGrp($group, $user, $reviewstatus, $comment, $file);
				if($reviewLogID === false || 0 > $reviewLogID) {
					$this->error = 1;
					$this->errormsg = "review_update_failed";
					return false;
				}
			}
		}

		/* Check to see if the overall status for the document version needs to be
		 * updated.
		 */
		$result = $this->callHook('reviewUpdateDocumentStatus', $content);
		if($result === null) {
			if ($reviewstatus == -1){
				if($content->setStatus(S_REJECTED,$comment,$user)) {
				}
			} else {
				$docReviewStatus = $content->getReviewStatus();
				if (is_bool($docReviewStatus) && !$docReviewStatus) {
					$this->error = 1;
					$this->errormsg = "cannot_retrieve_review_snapshot";
					return false;
				}
				$reviewCT = 0;
				$reviewTotal = 0;
				foreach ($docReviewStatus as $drstat) {
					if ($drstat["status"] == 1) {
						$reviewCT++;
					}
					if ($drstat["status"] != -2) {
						$reviewTotal++;
					}
				}
				// If all reviews have been received and there are no rejections, retrieve a
				// count of the approvals required for this document.
				if ($reviewCT == $reviewTotal) {
					$docApprovalStatus = $content->getApprovalStatus();
					if (is_bool($docApprovalStatus) && !$docApprovalStatus) {
						$this->error = 1;
						$this->errormsg = "cannot_retrieve_approval_snapshot";
						return false;
					}
					$approvalCT = 0;
					$approvalTotal = 0;
					foreach ($docApprovalStatus as $dastat) {
						if ($dastat["status"] == 1) {
							$approvalCT++;
						}
						if ($dastat["status"] != -2) {
							$approvalTotal++;
						}
					}
					// If the approvals received is less than the approvals total, then
					// change status to pending approval.
					if ($approvalCT<$approvalTotal) {
						$newStatus=S_DRAFT_APP;
					}
					else {
						// Otherwise, change the status to released.
						$newStatus=S_RELEASED;
					}
					if ($content->setStatus($newStatus, getMLText("automatic_status_update"), $user)) {
						// Send notification to subscribers.

					}
				}
			}
		}

		if(!$this->callHook('postReviewDocument', $content)) {
		}

		return true;
	}
}

