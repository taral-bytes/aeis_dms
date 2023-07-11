<?php
/**
 * Implementation of ReviewDocument view
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
 * Class which outputs the html page for ReviewDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ReviewDocument extends SeedDMS_Theme_Style {

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
			reviewStatus: {
				required: true
			},
		},
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
			reviewStatus: "<?php printMLText("js_no_review_status");?>",
		},
	});
	$("#formgrp").validate({
		rules: {
			comment: {
				required: true
			},
			reviewStatus: {
				required: true
			},
		},
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
			reviewStatus: "<?php printMLText("js_no_review_status");?>",
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

		$reviews = $content->getReviewStatus();
		foreach($reviews as $review) {
			if($review['reviewID'] == $_GET['reviewid']) {
				$reviewStatus = $review;
				break;
			}
		}

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("submit_review"));

		// Display the Review form.
		$reviewtype = ($reviewStatus['type'] == 0) ? 'ind' : 'grp';
		if($reviewStatus["status"]!=0) {

			print "<table class=\"table table-condensed table-sm\"><thead><tr>";
			print "<th>".getMLText("status")."</th>";
			print "<th>".getMLText("comment")."</th>";
			print "<th>".getMLText("last_update")."</th>";
			print "</tr></thead><tbody><tr>";
			print "<td>";
			printReviewStatusText($reviewStatus["status"]);
			print "</td>";
			print "<td>".htmlspecialchars($reviewStatus["comment"])."</td>";
			$indUser = $dms->getUser($reviewStatus["userID"]);
			print "<td>".$reviewStatus["date"]." - ". htmlspecialchars($indUser->getFullname()) ."</td>";
			print "</tr></tbody></table><br>\n";
		}
?>
	<form class="form-horizontal" method="post" action="../op/op.ReviewDocument.php" id="form<?= $reviewtype ?>" name="form<?= $reviewtype ?>" enctype="multipart/form-data">
	<?php echo createHiddenFieldWithKey('reviewdocument'); ?>
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
			getMLText("review_file"),
			$this->getFileChooserHtml('reviewfile', false)
		);
		$options = array();
		if($reviewStatus['status'] != 1)
			$options[] = array('1', getMLText('status_reviewed'));
		if($reviewStatus['status'] != -1)
			$options[] = array('-1', getMLText('rejected'));
		$this->formField(
			getMLText("review_status"),
			array(
				'element'=>'select',
				'name'=>'reviewStatus',
				'options'=>$options
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit(getMLText('submit_review'), $reviewtype.'Review');
?>
	<input type='hidden' name='reviewType' value='<?= $reviewtype ?>'/>
	<?php if($reviewtype == 'grp'): ?>
	<input type='hidden' name='reviewGroup' value='<?php echo $reviewStatus['required']; ?>'/>
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
