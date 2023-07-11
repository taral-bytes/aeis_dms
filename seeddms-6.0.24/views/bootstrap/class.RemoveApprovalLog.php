<?php
/**
 * Implementation of RemoveApprovalLog view
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
 * Class which outputs the html page for RemoveApprovalLog view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RemoveApprovalLog extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
$(document).ready(function() {
	$("#form1").validate({
		rules: {
			comment: {
				required: true
			},
		},
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
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

		$approves = $content->getApprovalStatus();
		foreach($approves as $approve) {
			if($approve['approveID'] == $approveid) {
				$approveStatus = $approve;
				break;
			}
		}

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("remove_approval_log"));
		$this->warningMsg(getMLText('warning_remove_approval_log'));

		// Display the Approval form.
		if($approveStatus["status"]!=0) {

			print "<table class=\"table table-content table-sm\"><thead><tr>";
			print "<th>".getMLText("status")."</th>";
			print "<th>".getMLText("comment")."</th>";
			print "<th>".getMLText("last_update")."</th>";
			print "</tr></thead><tbody><tr>";
			print "<td>";
			printApprovalStatusText($approveStatus["status"]);
			print "</td>";
			print "<td>".htmlspecialchars($approveStatus["comment"])."</td>";
			$indUser = $dms->getUser($approveStatus["userID"]);
			print "<td>".$approveStatus["date"]." - ". htmlspecialchars($indUser->getFullname()) ."</td>";
			print "</tr></tbody></table><br>\n";
		}
?>
	<form method="post" action="../op/op.RemoveApprovalLog.php" id="form1" name="form1">
	<?php echo createHiddenFieldWithKey('removeapprovallog'); ?>
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
		$this->contentContainerEnd();

		$this->formSubmit('<i class="fa fa-remove"></i> '.getMLText('remove_approval_log'));
?>
	<input type='hidden' name='approveid' value='<?= $approveid ?>'/>
	<input type='hidden' name='documentid' value='<?= $document->getID() ?>'/>
	<input type='hidden' name='version' value='<?= $content->getVersion() ?>'/>
	</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>

