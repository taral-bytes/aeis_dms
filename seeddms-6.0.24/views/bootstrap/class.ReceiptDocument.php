<?php
/**
 * Implementation of ReceiptDocument view
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
 * Class which outputs the html page for ReceiptDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ReceiptDocument extends SeedDMS_Theme_Style {

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
			receiptStatus: {
				required: true
			},
		},
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
			receiptStatus: "<?php printMLText("js_no_receipt_status");?>",
		},
	});
	$("#formgrp").validate({
		rules: {
			comment: {
				required: true
			},
			receiptStatus: {
				required: true
			},
		},
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
			receiptStatus: "<?php printMLText("js_no_receipt_status");?>",
		},
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$content = $this->params['version'];
		$receiptreject = $this->params['receiptreject'];

		$receipts = $content->getReceiptStatus();
		foreach($receipts as $receipt) {
			if($receipt['receiptID'] == $_GET['receiptid']) {
				$receiptStatus = $receipt;
				break;
			}
		}

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("submit_receipt"));
		if(getMLText('info_submit_receipt', array(), ''))
			$this->infoMsg(getMLText('info_submit_receipt', array(), ''));

		// Display the Receipt form.
		$receipttype = ($receiptStatus['type'] == 0) ? 'ind' : 'grp';
		if($receiptStatus["status"]!=0) {

			print "<table class=\"folderView\"><thead><tr>";
			print "<th>".getMLText("status")."</th>";
			print "<th>".getMLText("comment")."</th>";
			print "<th>".getMLText("last_update")."</th>";
			print "</tr></thead><tbody><tr>";
			print "<td>";
			printReceiptStatusText($receiptStatus["status"]);
			print "</td>";
			print "<td>".htmlspecialchars($receiptStatus["comment"])."</td>";
			$indUser = $dms->getUser($receiptStatus["userID"]);
			print "<td>".$receiptStatus["date"]." - ". htmlspecialchars($indUser->getFullname()) ."</td>";
			print "</tr></tbody></table><br>\n";
		}
?>
	<form class="form-horizontal" method="post" action="../op/op.ReceiptDocument.php" id="form<?= $receipttype ?>" name="form<?= $receipttype ?>">
	<?php echo createHiddenFieldWithKey('receiptdocument'); ?>
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
		if($receiptreject) {
			$options = array();
			if($receiptStatus['status'] != 1)
				$options[] = array('1', getMLText("status_receipted"));
			if($receiptStatus['status'] != -1)
				$options[] = array('-1', getMLText("rejected"));
			$this->formField(
				getMLText("receipt_status"),
				array(
					'element'=>'select',
					'name'=>'receiptStatus',
					'options'=>$options,
				)
			);
		} else {
			echo '<input type="hidden" name="receiptStatus" value="1" />';
		}
		$this->contentContainerEnd();
		$this->formSubmit(getMLText('submit_receipt'), $receipttype.'Receipt');
?>
		<input type='hidden' name='receiptType' value='<?= $receipttype ?>'/>
		<?php if($receipttype == 'grp'): ?>
		<input type='hidden' name='receiptGroup' value='<?php echo $receiptStatus['required']; ?>'/>
		<?php endif; ?>
		<input type='hidden' name='documentid' value='<?php echo $document->getID() ?>'/>
		<input type='hidden' name='version' value='<?php echo $content->getVersion() ?>'/>
	</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
