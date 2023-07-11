<?php
/**
 * Implementation of ReviseDocument view
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
 * Class which outputs the html page for ReviseDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ReviseDocument extends SeedDMS_Theme_Style {

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
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$content = $this->params['version'];
		$revisionid = $this->params['revisionid'];

		$reviews = $content->getRevisionStatus();
		foreach($reviews as $review) {
			if($review['revisionID'] == $revisionid) {
				$revisionStatus = $review;
				break;
			}
		}

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("submit_revision"));
		if(getMLText('info_submit_revision', array(), ''))
			$this->infoMsg(getMLText('info_submit_revision', array(), ''));

		// Display the Revision form.
		$revisiontype = ($revisionStatus['type'] == 0) ? 'ind' : 'grp';
		if($revisionStatus["status"]!=0) {

			print "<table class=\"folderView\"><thead><tr>";
			print "<th>".getMLText("status")."</th>";
			print "<th>".getMLText("comment")."</th>";
			print "<th>".getMLText("last_update")."</th>";
			print "</tr></thead><tbody><tr>";
			print "<td>";
			printRevisionStatusText($revisionStatus["status"]);
			print "</td>";
			print "<td>".htmlspecialchars($revisionStatus["comment"])."</td>";
			$indUser = $dms->getUser($revisionStatus["userID"]);
			print "<td>".$revisionStatus["date"]." - ". htmlspecialchars($indUser->getFullname()) ."</td>";
			print "</tr></tbody></table><br>\n";
		}
?>
	<form class="form-horizontal" method="post" action="../op/op.ReviseDocument.php" id="form<?= $revisiontype ?>" name="form<?= $revisiontype ?>">
	<?php echo createHiddenFieldWithKey('revisedocument'); ?>
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
		$options = array();
		if($revisionStatus['status'] != 1)
			$options[] = array('1', getMLText("status_revised"));
		if($revisionStatus['status'] != -1)
			$options[] = array('-1', getMLText("status_needs_correction"));
		$this->formField(
			getMLText("revision_status"),
			array(
				'element'=>'select',
				'name'=>'revisionStatus',
				'options'=>$options,
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit(getMLText('submit_revision'), $revisiontype.'Revision');
?>
	<input type='hidden' name='revisionType' value='<?= $revisiontype ?>'/>
	<?php if($revisiontype == 'grp'): ?>
	<input type='hidden' name='revisionGroup' value='<?php echo $revisionStatus['required']; ?>'/>
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
