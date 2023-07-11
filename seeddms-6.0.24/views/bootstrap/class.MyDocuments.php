<?php
/**
 * Implementation of MyDocuments view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for MyDocuments view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_MyDocuments extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
		$this->printDeleteDocumentButtonJs();
		$this->printClickDocumentJs();
?>
$(document).ready( function() {
	$('body').on('click', 'ul.sidenav li a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {});
	});
	$('body').on('click', 'table th a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {orderby: $(this).data('orderby'), orderdir: $(this).data('orderdir')});
	});
});
<?php
	} /* }}} */

	protected function printListHeader($resArr, $previewer, $action=false) { /* {{{ */
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];

		print "<table class=\"table table-condensed\">";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";
		if($action) {
			print "<th>";
			print "<a data-action=\"".$action."\" data-orderby=\"n\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("name")."</a> ".($orderby == 'n' || $orderby == '' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')." &middot; ";
			print "<a data-action=\"".$action."\" data-orderby=\"u\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("last_update")."</a> ".($orderby == 'u' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')." &middot; ";
			print "<a data-action=\"".$action."\" data-orderby=\"e\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("expires")."</a> ".($orderby == 'e' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '');
			print "</th>\n";
		} else
			print "<th>".getMLText("name")."</th>\n";
		if($action)
			print "<th><a data-action=\"".$action."\" data-orderby=\"s\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("status")."</a>".($orderby == 's' ? " ".($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')."</th>\n";
		else
			print "<th>".getMLText("status")."</th>\n";
		print "<th>".getMLText("action")."</th>\n";
		print "</tr>\n</thead>\n<tbody>\n";
	} /* }}} */

	protected function printListFooter() { /* {{{ */
		echo "</tbody>\n</table>";
	} /* }}} */

	protected function printList($resArr, $previewer, $action=false) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		$this->printListHeader($resArr, $previewer, $action);
		$noaccess = 0;
		$docs = [];
		foreach ($resArr as $res) {
			if($document = $dms->getDocument($res["id"])) {
				$document->verifyLastestContentExpriry();

				if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
					$docs[] = $document;
				} else {
					$noaccess++;
				}
			}
		}
		if($this->hasHook('filterList'))
			$docs = $this->callHook('filterList', $docs, $action);
		foreach($docs as $document) {
			$txt = $this->callHook('documentListItem', $document, $previewer, false);
			if(is_string($txt))
				echo $txt;
			else
				echo $this->documentListRow($document, $previewer, false);
		}
		$this->printListFooter();

		if($noaccess) {
			$this->warningMsg(getMLText('list_contains_no_access_docs', array('count'=>$noaccess)));
		}
	} /* }}} */

	function listReviews() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$db = $dms->getDB();
		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$resArr = $dms->getDocumentList('ReviewByMe', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_to_review"));
		if($resArr) {
			$this->printList($resArr, $previewer, 'listReviews');
		} else {
			printMLText("no_docs_to_review");
		}

	} /* }}} */

	function listApprovals() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$resArr = $dms->getDocumentList('ApproveByMe', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}
		$this->contentHeading(getMLText("documents_to_approve"));
		if($resArr) {
			$this->printList($resArr, $previewer, 'listApprovals');
		} else {
			printMLText("no_docs_to_approve");
		}
	} /* }}} */

	function listDocsToLookAt() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$workflowmode = $this->params['workflowmode'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		if($workflowmode != 'advanced') {
			/* Get list of documents owned by current user that are
			 * pending review or pending approval.
			 */
			$resArr = $dms->getDocumentList('AppRevOwner', $user, false, $orderby, $orderdir);
			if (is_bool($resArr) && !$resArr) {
				$this->contentHeading(getMLText("warning"));
				$this->contentContainer(getMLText("internal_error_exit"));
				$this->htmlEndPage();
				exit;
			}

			$this->contentHeading(getMLText("documents_user_requiring_attention"));
			if ($resArr) {
				$this->printList($resArr, $previewer, 'listDocsToLookAt');
			} else {
				printMLText("no_docs_to_look_at");
			}
		} else {
			$resArr = $dms->getDocumentList('WorkflowOwner', $user, false, $orderby, $orderdir);
			if (is_bool($resArr) && !$resArr) {
				$this->contentHeading(getMLText("warning"));
				$this->contentContainer("Internal error. Unable to complete request. Exiting.");
				$this->htmlEndPage();
				exit;
			}

			$this->contentHeading(getMLText("documents_user_requiring_attention"));
			if($resArr) {
				$this->printList($resArr, $previewer);
			}
			else printMLText("no_docs_to_look_at");
		}
	} /* }}} */

	function listReceiveOwner() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$showtree = $this->params['showtree'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of documents owned by current user */
		$resArr = $dms->getDocumentList('ReceiveOwner', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_user_reception"));
		if($resArr) {
			$this->printList($resArr, $previewer, 'listReceiveOwner');
		}
		else printMLText("empty_notify_list");
	} /* }}} */

	function listNoReceiveOwner() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$showtree = $this->params['showtree'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of documents owned by current user */
		$resArr = $dms->getDocumentList('NoReceiveOwner', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_user_no_reception"));
		if($resArr) {
			$this->printList($resArr, $previewer, 'listNoReceiveOwner');
		}
		else printMLText("empty_notify_list");
	} /* }}} */

	function listMyDocs() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$showtree = $this->params['showtree'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of documents owned by current user */
		$resArr = $dms->getDocumentList('MyDocs', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("all_documents"));
		if($resArr) {
			$this->printList($resArr, $previewer, 'listMyDocs');
		}
		else printMLText("empty_notify_list");
	} /* }}} */

	function listWorkflow() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		// Get document list for the current user.
		$workflowStatus = $user->getWorkflowStatus();

		$resArr = $dms->getDocumentList('WorkflowByMe', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		if (count($resArr)>0) {
			// Create an array to hold all of these results, and index the array by
			// document id. This makes it easier to retrieve document ID information
			// later on and saves us having to repeatedly poll the database every time
			// new document information is required.
			$docIdx = array();
			foreach ($resArr as $res) {
				$docIdx[$res["id"]][$res["version"]] = $res;
			}

			// List the documents where a review has been requested.
			$this->contentHeading(getMLText("documents_to_process"));

			$printheader=true;
			$iRev = array();
			$dList = array();
			foreach ($workflowStatus["u"] as $st) {

				if ( isset($docIdx[$st["document"]][$st["version"]]) && !in_array($st["document"], $dList) ) {
					$dList[] = $st["document"];
					$document = $dms->getDocument($st["document"]);
					$document->verifyLastestContentExpriry();

					if ($printheader){
						print "<table class=\"table table-condensed\">";
						print "<thead>\n<tr>\n";
						print "<th></th>\n";
						print "<th>".getMLText("name")."</th>\n";
						print "<th>".getMLText("status")."</th>\n";
						print "<th>".getMLText("action")."</th>\n";
						print "</tr>\n</thead>\n<tbody>\n";
						$printheader=false;
					}

					$txt = $this->callHook('documentListItem', $document, $previewer);
					if(is_string($txt))
						echo $txt;
					else {
						echo $this->documentListRow($document, $previewer, false, $st['version']);
					}
				}
			}
			foreach ($workflowStatus["g"] as $st) {

				if (!in_array($st["document"], $iRev) && isset($docIdx[$st["document"]][$st["version"]]) && !in_array($st["document"], $dList) /* && $docIdx[$st["documentID"]][$st["version"]]['owner'] != $user->getId() */) {
					$dList[] = $st["document"];
					$document = $dms->getDocument($st["document"]);
					$document->verifyLastestContentExpriry();

					if ($printheader){
						print "<table class=\"table table-condensed\">";
						print "<thead>\n<tr>\n";
						print "<th></th>\n";
						print "<th>".getMLText("name")."</th>\n";
						print "<th>".getMLText("status")."</th>\n";
						print "<th>".getMLText("action")."</th>\n";
						print "</tr>\n</thead>\n<tbody>\n";
						$printheader=false;
					}

					$txt = $this->callHook('documentListItem', $document, $previewer);
					if(is_string($txt))
						echo $txt;
					else {
						echo $this->documentListRow($document, $previewer, false, $st['version']);
					}
				}
			}
			if (!$printheader){
				echo "</tbody>\n</table>";
			}else{
				printMLText("no_docs_to_check");
			}
		}

	} /* }}} */

	function listRevisions() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		// Get document list for the current user.
		$revisionStatus = $user->getRevisionStatus();

		$resArr = $dms->getDocumentList('ReviseByMe', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_to_revise"));
		if($resArr) {
			$this->printList($resArr, $previewer, 'listRevisions');
		} else {
			printMLText("no_docs_to_revise");
		}
	} /* }}} */

	function listReceipts() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$resArr = $dms->getDocumentList('ReceiptByMe', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_to_receipt"));
		if($resArr) {
			$this->printList($resArr, $previewer, 'listReceipts');
		} else {
			printMLText("no_docs_to_receipt");
		}

	} /* }}} */

	function listRejects() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of documents owned by current user that has
		 * been rejected.
		 */
		$resArr = $dms->getDocumentList('RejectOwner', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_user_rejected"));
		if ($resArr) {
			$this->printList($resArr, $previewer, 'listRejects');
		}
		else printMLText("no_docs_rejected");

	} /* }}} */

	function listLockedDocs() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of documents locked by current user */
		$resArr = $dms->getDocumentList('LockedByMe', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_locked_by_you"));
		if ($resArr) {
			$this->printList($resArr, $previewer, 'listLockedDocs');
		}
		else printMLText("no_docs_locked");

	} /* }}} */

	function listExpiredOwner() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of documents expired and owned by current user */
		$resArr = $dms->getDocumentList('ExpiredOwner', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_expired"));
		if ($resArr) {
			$this->printList($resArr, $previewer, 'listExpiredOwner');
		}
		else printMLText("no_docs_expired");

	} /* }}} */

	function listObsoleteOwner() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of obsolete documents and owned by current user */
		$resArr = $dms->getDocumentList('ObsoleteOwner', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_user_obsolete"));
		if ($resArr) {
			$this->printList($resArr, $previewer, 'listObsoleteOwner');
		}
		else printMLText("no_docs_obsolete");

	} /* }}} */

	function listNeedsCorrectionOwner() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of obsolete documents and owned by current user */
		$resArr = $dms->getDocumentList('NeedsCorrectionOwner', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_user_needs_correction"));
		if ($resArr) {
			$this->printList($resArr, $previewer, 'listNeedsCorrectionOwner');
		}
		else printMLText("no_docs_needs_correction");

	} /* }}} */

	function listDraftOwner() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of draft documents and owned by current user */
		$resArr = $dms->getDocumentList('DraftOwner', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_user_draft"));
		if ($resArr) {
			$this->printList($resArr, $previewer, 'listDraftOwner');
		}
		else printMLText("no_docs_draft");

	} /* }}} */

	function listCheckedoutDocs() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		/* Get list of documents checked out by current user */
		$resArr = $dms->getDocumentList('CheckedOutByMe', $user, false, $orderby, $orderdir);
		if (is_bool($resArr) && !$resArr) {
			$this->contentHeading(getMLText("warning"));
			$this->contentContainer(getMLText("internal_error_exit"));
			$this->htmlEndPage();
			exit;
		}

		$this->contentHeading(getMLText("documents_checked_out_by_you"));
		if ($resArr) {
			$this->printList($resArr, $previewer, 'listCheckedoutDocs');
		}
		else printMLText("no_docs_checked_out");
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$orderby = $this->params['orderby'];
		$orderdir = $this->params['orderdir'];
		$listtype = $this->params['listtype'];
		$cachedir = $this->params['cachedir'];
		$workflowmode = $this->params['workflowmode'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$db = $dms->getDB();

		$this->htmlStartPage(getMLText("my_documents"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_documents"), "my_documents");

		$this->rowStart();
		$this->columnStart(3);
		$this->contentHeading(getMLText("my_documents"));
		$menuitems = [];
		$resArr = $dms->getDocumentList('MyDocs', $user);
		$menuitems[] = array('label'=>getMLText('all_documents'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#all_documents"), array('data-action', "listmyDocs")));

		$resArr = $dms->getDocumentList('ReceiveOwner', $user);
		$menuitems[] = array('label'=>getMLText('documents_user_reception'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_user_reception"), array('data-action', "listReceiveOwner")));
		$resArr = $dms->getDocumentList('NoReceiveOwner', $user);
		$menuitems[] = array('label'=>getMLText('documents_user_no_reception'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_user_no_reception"), array('data-action', "listNoReceiveOwner")));
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			$resArr = $dms->getDocumentList('AppRevOwner', $user);
			$menuitems[] = array('label'=>getMLText('documents_user_requiring_attention'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_user_requiring_attention"), array('data-action', "listDocsToLookAt")));
		}
		self::showNavigationListWithBadges($menuitems);

		$menuitems = [];
		$this->contentHeading(getMLText("documents_in_process"));
		$resArr = $dms->getDocumentList('DraftOwner', $user);
		$menuitems[] = array('label'=>getMLText('documents_user_draft'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_user_draft"), array('data-action', "listDraftOwner")));
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			$resArr = $dms->getDocumentList('RejectOwner', $user);
			$menuitems[] = array('label'=>getMLText('documents_user_rejected'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_user_rejected"), array('data-action', "listRejects")));
		}
		$resArr = $dms->getDocumentList('CheckedOutByMe', $user);
		$menuitems[] = array('label'=>getMLText('documents_checked_out_by_you'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_checked_out_by_you"), array('data-action', "listCheckedoutDocs")));
		$resArr = $dms->getDocumentList('LockedByMe', $user);
		$menuitems[] = array('label'=>getMLText('documents_locked_by_you'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_locked_by_you"), array('data-action', "listLockedDocs")));
		self::showNavigationListWithBadges($menuitems);

		$menuitems = [];
		$this->contentHeading(getMLText("tasks"));
		if($workflowmode == 'traditional') {
			$resArr = $dms->getDocumentList('ReviewByMe', $user);
			$menuitems[] = array('label'=>getMLText('documents_to_review'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_to_review"), array('data-action', "listReviews")));
		}
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			$resArr = $dms->getDocumentList('ApproveByMe', $user);
			$menuitems[] = array('label'=>getMLText('documents_to_approve'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_to_approve"), array('data-action', "listApprovals")));
		} else {
			$resArr = $dms->getDocumentList('WorkflowByMe', $user);
			$menuitems[] = array('label'=>getMLText('documents_to_process'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_to_process"), array('data-action', "listWorkflow")));
		}
		$resArr = $dms->getDocumentList('ReceiptByMe', $user);
		$menuitems[] = array('label'=>getMLText('documents_to_receipt'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_to_receipt"), array('data-action', "listReceipts")));
		$resArr = $dms->getDocumentList('ReviseByMe', $user);
		$menuitems[] = array('label'=>getMLText('documents_to_revise'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_to_revise"), array('data-action', "listRevisions")));
		$resArr = $dms->getDocumentList('NeedsCorrectionOwner', $user);
		$menuitems[] = array('label'=>getMLText('documents_user_needs_correction'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_user_needs_correction"), array('data-action', "listNeedsCorrectionOwner")));
		self::showNavigationListWithBadges($menuitems);

		$menuitems = [];
		$this->contentHeading(getMLText("archive"));
		$resArr = $dms->getDocumentList('ExpiredOwner', $user);
		$menuitems[] = array('label'=>getMLText('documents_user_expiration'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_user_expiration"), array('data-action', "listExpiredOwner")));
		$resArr = $dms->getDocumentList('ObsoleteOwner', $user);
		$menuitems[] = array('label'=>getMLText('documents_user_obsolete'), 'badge'=>count($resArr), 'attributes'=>array(array('data-href', "#documents_user_obsolete"), array('data-action', "listObsoleteOwner")));
		self::showNavigationListWithBadges($menuitems);

		$this->columnEnd();
		$this->columnStart(9);
		echo '<div id="kkkk" class="ajax" data-view="MyDocuments" data-action="'.($listtype ? $listtype : 'listDocsToLookAt').'"></div>';

		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
