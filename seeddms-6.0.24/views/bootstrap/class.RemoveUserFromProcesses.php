<?php
/**
 * Implementation of RemoveUserFromProcesses view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for RemoveUserFromProcesses view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RemoveUserFromProcesses extends SeedDMS_Theme_Style {

	public function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
		$this->printDeleteDocumentButtonJs();
		/* Add js for catching click on document in one page mode */
		$this->printClickDocumentJs();
?>
$(document).ready( function() {
  $('body').on('click', 'label.checkbox, td span', function(ev){
    ev.preventDefault();
    $('#kkkk.ajax').data('action', $(this).data('action'));
    $('#kkkk.ajax').trigger('update', {userid: $(this).data('userid'), task: $(this).data('task'), type: $(this).data('type')});
  });
	$('body').on('click', '#selectall', function(ev){
		$("input.markforprocess").each(function () { this.checked = !this.checked; });
		ev.preventDefault();
	});
});
<?php
	} /* }}} */

	function printList() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$cachedir = $this->params['cachedir'];
		$rootfolder = $this->params['rootfolder'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewconverters'];
		$timeout = $this->params['timeout'];
		$rmuser = $this->params['rmuser'];
		$allusers = $this->params['allusers'];
		$task = $this->params['task'];
		$type = $this->params['type'];

		if(!$task)
			return;

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$docs = array();
		switch($task) {
		case "reviews_not_touched":
			$reviewStatus = $rmuser->getReviewStatus();
			foreach($reviewStatus['indstatus'] as $ri) {
				$document = $dms->getDocument($ri['documentID']);
				$ri['latest'] = $document->getLatestContent()->getVersion();
				if($ri['latest'] == $ri['version']) {
					if($ri['status'] == 0) {
						$document->verifyLastestContentExpriry();
						$lc = $document->getLatestContent();
						if($document->getAccessMode($user) >= M_READ && $lc) {
							$docs[] = $document;
						}
					}
				}
			}
			break;
		case "reviews_accepted":
			$reviewStatus = $rmuser->getReviewStatus();
			foreach($reviewStatus['indstatus'] as $ri) {
				$document = $dms->getDocument($ri['documentID']);
				$ri['latest'] = $document->getLatestContent()->getVersion();
				if($ri['latest'] == $ri['version']) {
					if($ri['status'] == 1) {
						$document->verifyLastestContentExpriry();
						$lc = $document->getLatestContent();
						if($document->getAccessMode($user) >= M_READ && $lc) {
							$docs[] = $document;
						}
					}
				}
			}
			break;
		case "reviews_rejected":
			$reviewStatus = $rmuser->getReviewStatus();
			foreach($reviewStatus['indstatus'] as $ri) {
				$document = $dms->getDocument($ri['documentID']);
				$ri['latest'] = $document->getLatestContent()->getVersion();
				if($ri['latest'] == $ri['version']) {
					if($ri['status'] == -1) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "approvals_not_touched":
			$approvalStatus = $rmuser->getApprovalStatus();
			foreach($approvalStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == 0) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "approvals_accepted":
			$approvalStatus = $rmuser->getApprovalStatus();
			foreach($approvalStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == 1) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "approvals_rejected":
			$approvalStatus = $rmuser->getApprovalStatus();
			foreach($approvalStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == -1) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "receipts_not_touched":
			$receiptStatus = $rmuser->getReceiptStatus();
			foreach($receiptStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == 0) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "receipts_accepted":
			$receiptStatus = $rmuser->getReceiptStatus();
			foreach($receiptStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == 1) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "receipts_rejected":
			$receiptStatus = $rmuser->getReceiptStatus();
			foreach($receiptStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == -1) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "revisions_not_touched":
			$revisionStatus = $rmuser->getRevisionStatus();
			foreach($revisionStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == 0) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "revisions_accepted":
			$revisionStatus = $rmuser->getRevisionStatus();
			foreach($revisionStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == 1) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "revisions_rejected":
			$revisionStatus = $rmuser->getRevisionStatus();
			foreach($revisionStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == -1) {
						$docs[] = $document;
					}
				}
			}
			break;
		case "revisions_pending":
			$revisionStatus = $rmuser->getRevisionStatus();
			foreach($revisionStatus['indstatus'] as $ai) {
				$document = $dms->getDocument($ai['documentID']);
				$ai['latest'] = $document->getLatestContent()->getVersion();
				if($ai['latest'] == $ai['version']) {
					if($ai['status'] == -3) {
						$docs[] = $document;
					}
				}
			}
			break;
		}
		if($docs) {
			echo '<form id="processform" action="../op/op.UsrMgr.php" method="post">';
			echo '<input type="hidden" name="userid" value="'.$rmuser->getID().'">';
			if($type) {
				$kk = explode('_', $type, 2);
				echo '<input type="hidden" name="status['.$kk[0].'][]" value="'.$kk[1].'">';
			}
			echo '<input type="hidden" name="task" value="'.$task.'">';
			echo '<input type="hidden" name="action" value="removefromprocesses">';
			echo '<input type="hidden" name="needsdocs" value="1">';
			echo createHiddenFieldWithKey('removefromprocesses');
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th></th>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("status")."</th>\n";
			print "<th>".getMLText("action")."</th>\n";
			print "<th><span id=\"selectall\"><i class=\"fa fa-arrows-h\" title=\"".getMLText('object_cleaner_toggle_checkboxes')."\"></i></span></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($docs as $document) {
				$document->verifyLastestContentExpriry();
				$lc = $document->getLatestContent();
				if($document->getAccessMode($user) >= M_READ && $lc) {
					$txt = $this->callHook('documentListItem', $document, $previewer, false);
					if(is_string($txt))
						echo $txt;
					else {
						$extracontent = array();
						$extracontent['below_title'] = $this->getListRowPath($document);
						echo $this->documentListRowStart($document);
						echo $this->documentListRow($document, $previewer, true, 0, $extracontent);
						echo '<td>';
						echo '<input type="checkbox" class="markforprocess" value="'.$document->getId().'" name="docs['.$document->getId().']">';
						echo '</td>';
						echo $this->documentListRowEnd($document);
					}
				}
			}
			echo "</tbody>\n</table>";
			$options = array(array(0, getMLText('do_no_transfer_to_user')));
			foreach ($allusers as $currUser) {
				if ($currUser->isGuest() || ($currUser->getID() == $rmuser->getID()) )
					continue;

				if ($rmuser && $currUser->getID()==$rmuser->getID()) $selected=$count;
				$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()." - ".$currUser->getFullName()));
			}
			$this->formField(
				getMLText("transfer_process_to_user"),
				array(
					'element'=>'select',
					'name'=>'assignTo',
					'class'=>'chzn-select',
					'options'=>$options
				)
			);
			$this->formSubmit('<i class="fa fa-remove"></i> '.getMLText('transfer_processes_to_user'),'','','primary');
			echo '</form>';
		}
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$rmuser = $this->params['rmuser'];
		$allusers = $this->params['allusers'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("rm_user_from_processes"));

		$this->rowStart();
		$this->columnStart(4);
		$this->warningMsg(getMLText("confirm_rm_user_from_processes", array ("username" => htmlspecialchars($rmuser->getFullName()))));

		$reviewStatus = $rmuser->getReviewStatus();
		$tmpr = array();
		$cr = array("-2"=>0, '-1'=>0, '0'=>0, '1'=>0);
		foreach($reviewStatus['indstatus'] as $ri) {
			$doc = $dms->getDocument($ri['documentID']);
			$ri['latest'] = $doc->getLatestContent()->getVersion();
			if($ri['latest'] == $ri['version'])
				$cr[$ri['status']]++;
			if(isset($tmpr[$ri['status']]))
				$tmpr[$ri['status']][] = $ri;
			else
				$tmpr[$ri['status']] = array($ri);
		}

		$approvalStatus = $rmuser->getApprovalStatus();
		$tmpa = array();
		$ca = array("-2"=>0, '-1'=>0, '0'=>0, '1'=>0);
		foreach($approvalStatus['indstatus'] as $ai) {
			$doc = $dms->getDocument($ai['documentID']);
			$ai['latest'] = $doc->getLatestContent()->getVersion();
			if($ai['latest'] == $ai['version'])
				$ca[$ai['status']]++;
			if(isset($tmpa[$ai['status']]))
				$tmpa[$ai['status']][] = $ai;
			else
				$tmpa[$ai['status']] = array($ai);
		}

		$receiptStatus = $rmuser->getReceiptStatus();
		$tmpb = array();
		$cb = array("-2"=>0, '-1'=>0, '0'=>0, '1'=>0);
		foreach($receiptStatus['indstatus'] as $ai) {
			$doc = $dms->getDocument($ai['documentID']);
			$ai['latest'] = $doc->getLatestContent()->getVersion();
			if($ai['latest'] == $ai['version'])
				$cb[$ai['status']]++;
			if(isset($tmpb[$ai['status']]))
				$tmpb[$ai['status']][] = $ai;
			else
				$tmpb[$ai['status']] = array($ai);
		}

		$revisionStatus = $rmuser->getRevisionStatus();
		$tmpc = array();
		$cc = array("-3"=>0, "-2"=>0, '-1'=>0, '0'=>0, '1'=>0);
		foreach($revisionStatus['indstatus'] as $ai) {
			$doc = $dms->getDocument($ai['documentID']);
			$ai['latest'] = $doc->getLatestContent()->getVersion();
			if($ai['latest'] == $ai['version'])
				$cc[$ai['status']]++;
			if(isset($tmpc[$ai['status']]))
				$tmpc[$ai['status']][] = $ai;
			else
				$tmpc[$ai['status']] = array($ai);
		}

		$out = array();
		if(isset($tmpr["0"])) {
			$out[] = array(
				'0',
				'review',
				'not_touched',
				getMLText('reviews_not_touched', array('no_reviews' => count($tmpr["0"]))),
				getMLText('reviews_not_touched_latest', array('no_reviews' => $cr["0"]))
			);	
		}
		if(isset($tmpr["1"])) {
			$out[] = array(
				'1',
				'review',
				'accepted',
				getMLText('reviews_accepted', array('no_reviews' => count($tmpr["1"]))),
				getMLText('reviews_accepted_latest', array('no_reviews' => $cr["1"]))
			);	
		}
		if(isset($tmpr["-1"])) {
			$out[] = array(
				'-1',
				'review',
				'rejected',
				getMLText('reviews_rejected', array('no_reviews' => count($tmpr["-1"]))),
				getMLText('reviews_rejected_latest', array('no_reviews' => $cr["-1"]))
			);	
		}
		if(isset($tmpa["0"])) {
			$out[] = array(
				'0',
				'approval',
				'not_touched',
				getMLText('approvals_not_touched', array('no_approvals' => count($tmpa["0"]))),
				getMLText('approvals_not_touched_latest', array('no_approvals' => $ca["0"]))
			);
		}
		if(isset($tmpa["1"])) {
			$out[] = array(
				'1',
				'approval',
				'accepted',
				getMLText('approvals_accepted', array('no_approvals' => count($tmpa["1"]))),
				getMLText('approvals_accepted_latest', array('no_approvals' => $ca["1"]))
			);
		}
		if(isset($tmpa["-1"])) {
			$out[] = array(
				'-1',
				'approval',
				'rejected',
				getMLText('approvals_rejected', array('no_approvals' => count($tmpa["-1"]))),
				getMLText('approvals_rejected_latest', array('no_approvals' => $ca["-1"]))
			);
		}
		if(isset($tmpb["0"])) {
			$out[] = array(
				'0',
				'receipt',
				'not_touched',
				getMLText('receipts_not_touched', array('no_receipts' => count($tmpb["0"]))),
				getMLText('receipts_not_touched_latest', array('no_receipts' => $cb["0"]))
			);
		}
		if(isset($tmpb["1"])) {
			$out[] = array(
				'1',
				'receipt',
				'accepted',
				getMLText('receipts_accepted', array('no_receipts' => count($tmpb["1"]))),
				getMLText('receipts_accepted_latest', array('no_receipts' => $cb["1"]))
			);
		}
		if(isset($tmpb["-1"])) {
			$out[] = array(
				'-1',
				'receipt',
				'rejected',
				getMLText('receipts_rejected', array('no_receipts' => count($tmpb["-1"]))),
				getMLText('receipts_rejected_latest', array('no_receipts' => $cb["-1"]))
			);
		}
		if(isset($tmpc["0"])) {
			$out[] = array(
				'0',
				'revision',
				'not_touched',
				getMLText('revisions_not_touched', array('no_revisions' => count($tmpc["0"]))),
				getMLText('revisions_not_touched_latest', array('no_revisions' => $cc["0"]))
			);
		}
		if(isset($tmpc["1"])) {
			$out[] = array(
				'1',
				'revision',
				'accepted',
				getMLText('revisions_accepted', array('no_revisions' => count($tmpc["1"]))),
				getMLText('revisions_accepted_latest', array('no_revisions' => $cc["1"]))
			);
		}
		if(isset($tmpc["-1"])) {
			$out[] = array(
				'-1',
				'revision',
				'rejected',
				getMLText('revisions_rejected', array('no_revisions' => count($tmpc["-1"]))),
				getMLText('revisions_rejected_latest', array('no_revisions' => $cc["-1"]))
			);
		}
		if(isset($tmpc["-3"])) {
			$out[] = array(
				'-3',
				'revision',
				'pending',
				getMLText('revisions_pending', array('no_revisions' => count($tmpc["-3"]))),
				getMLText('revisions_pending_latest', array('no_revisions' => $cc["-3"]))
			);
		}

?>

<form class="form-horizontal" action="../op/op.UsrMgr.php" name="form1" method="post">
<input type="hidden" name="userid" value="<?php print $rmuser->getID();?>">
<input type="hidden" name="action" value="removefromprocesses">
<?php echo createHiddenFieldWithKey('removefromprocesses'); ?>

<?php
		echo "<table class=\"table table-condensed table-sm\">";
		foreach($out as $o) {
			echo "<tr><td>".$o[3]."</td><td>".$o[4]."</td><td><input style=\"margin-top: 0px;\" type=\"checkbox\" name=\"status[".$o[1]."][]\" value=\"".$o[0]."\"></td><td><span title=\"".getMLText('select_documents_for_process')."\" data-action=\"printList\" data-userid=\"".$rmuser->getId()."\" data-task=\"".$o[1]."s_".$o[2]."\" data-type=\"".$o[1]."_".$o[0]."\"><i class=\"fa fa-list\"></i></span></td></tr>";
		}
		echo "</table>";

		$this->infoMsg(getMLText("info_rm_user_from_processes_user"));

		$options = array(array(0, getMLText('do_no_transfer_to_user')));
		foreach ($allusers as $currUser) {
			if ($currUser->isGuest() || ($currUser->getID() == $rmuser->getID()) )
				continue;

			if ($rmuser && $currUser->getID()==$rmuser->getID()) $selected=$count;
			$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()." - ".$currUser->getFullName()));
		}
		$this->formField(
			getMLText("transfer_process_to_user"),
			array(
				'element'=>'select',
				'name'=>'assignTo',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);

		$this->formSubmit("<i class=\"fa fa-remove\"></i> ".getMLText('rm_user_from_processes'));
?>

</form>
<?php
		$this->columnEnd();
		$this->columnStart(8);
		echo '<div id="kkkk" class="ajax" data-view="RemoveUserFromProcesses" data-action="printList" data-query="userid='.$rmuser->getId().'"></div>';
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
