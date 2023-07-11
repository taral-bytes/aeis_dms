<?php
/**
 * Implementation of ApproveDocument view
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
 * Class which outputs the html page for ApproveDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ApproveDocument extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
$(document).ready(function() {
	$("#formind").validate({
		rules: {
			comment: {
				required: true
			},
			approvalStatus: {
				required: true
			},
		},
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
			approvalStatus: "<?php printMLText("js_no_approval_status");?>",
		},
	});
	$("#formgrp").validate({
		rules: {
			comment: {
				required: true
			},
			approvalStatus: {
				required: true
			},
		},
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
			approvalStatus: "<?php printMLText("js_no_approval_status");?>",
		},
	});
});
<?php
		$this->printFileChooserJs();
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$content = $this->params['version'];
		$approveid = $this->params['approveid'];

		$approvals = $content->getApprovalStatus();
		foreach($approvals as $approval) {
			if($approval['approveID'] == $approveid) {
				$approvalStatus = $approval;
				break;
			}
		}

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("add_approval"));

		// Display the Approval form.
		$approvaltype = ($approvalStatus['type'] == 0) ? 'ind' : 'grp';
		if($approvalStatus["status"]!=0) {

			print "<table class=\"table table-condensed table-sm\"><thead><tr>";
			print "<th>".getMLText("status")."</th>";
			print "<th>".getMLText("comment")."</th>";
			print "<th>".getMLText("last_update")."</th>";
			print "</tr></thead><tbody><tr>";
			print "<td>";
			printApprovalStatusText($approvalStatus["status"]);
			print "</td>";
			print "<td>".htmlspecialchars($approvalStatus["comment"])."</td>";
			$indUser = $dms->getUser($approvalStatus["userID"]);
			print "<td>".$approvalStatus["date"]." - ". htmlspecialchars($indUser->getFullname()) ."</td>";
			print "</tr></tbody></table><br>\n";
		}
?>
	<form class="form-horizontal" method="post" action="../op/op.ApproveDocument.php" id="form<?= $approvaltype ?>" name="form<?= $approvaltype ?>" enctype="multipart/form-data">
	<?php echo createHiddenFieldWithKey('approvedocument'); ?>
<?php
		$this->contentContainerStart();

		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'name'=>'comment',
				'required'=>true,
				'rows'=>4,
				'cols'=>80
			)
		);
		$this->formField(
			getMLText("approval_file"),
			$this->getFileChooserHtml('approvalfile', false)
		);
		$options = array();
		if($approvalStatus['status'] != 1)
			$options[] = array('1', getMLText("status_approved"));
		if($approvalStatus['status'] != -1)
			$options[] = array('-1', getMLText("rejected"));
		$this->formField(
			getMLText("approval_status"),
			array(
				'element'=>'select',
				'name'=>'approvalStatus',
				'options'=>$options,
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit(getMLText('submit_approval'), $approvaltype.'Approval');
?>
	<input type='hidden' name='approvalType' value='<?= $approvaltype ?>'/>
	<?php if($approvaltype == 'grp'): ?>
	<input type='hidden' name='approvalGroup' value="<?php echo $approvalStatus['required']; ?>" />
	<?php endif; ?>
	<input type='hidden' name='documentid' value='<?php echo $document->getId() ?>'/>
	<input type='hidden' name='version' value='<?php echo $content->getVersion(); ?>'/>
	</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
