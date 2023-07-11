<?php
/**
 * Implementation of ReceiptDocument controller
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
class SeedDMS_Controller_ReceiptDocument extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$document = $this->params['document'];
		$content = $this->params['content'];
		$receiptstatus = $this->params['receiptstatus'];
		$receipttype = $this->params['receipttype'];
		$group = $this->params['group'];
		$comment = $this->params['comment'];

		/* Get the document id and name before removing the document */
		$docname = $document->getName();
		$documentid = $document->getID();

		if(!$this->callHook('preReceiptDocument', $content)) {
		}

		$result = $this->callHook('receiptDocument', $content);
		if($result === null) {

			if ($receipttype == "ind") {
				if(0 > $content->setReceiptByInd($user, $user, $receiptstatus, $comment)) {
					$this->error = 1;
					$this->errormsg = "receipt_update_failed";
					return false;
				}
			} elseif ($receipttype == "grp") {
				if(0 > $content->setReceiptByGrp($group, $user, $receiptstatus, $comment)) {
					$this->error = 1;
					$this->errormsg = "receipt_update_failed";
					return false;
				}
			}
		}

		if(!$this->callHook('postReceiptDocument', $content)) {
		}

		return true;
	}
}

