<?php
/**
 * Implementation of OverrideContentStatus view
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
 * Class which outputs the html page for OverrideContentStatus view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_OverrideContentStatus extends SeedDMS_Theme_Style {

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
			overrideStatus: {
				required: true
			},
		},
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
			overrideStatus: "<?php printMLText("js_no_override_status");?>",
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

		$overallStatus = $content->getStatus();
		$reviewStatus = $content->getReviewStatus();
		$approvalStatus = $content->getApprovalStatus();

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);

		$this->contentHeading(getMLText("change_status"));

// Display the Review form.
?>
<form class="form-horizontal" method="post" action="../op/op.OverrideContentStatus.php" id="form1" name="form1">
	<?php echo createHiddenFieldWithKey('overridecontentstatus'); ?>
	<input type='hidden' name='documentid' value='<?php echo $document->getID() ?>'/>
	<input type='hidden' name='version' value='<?php echo $content->getVersion() ?>'/>
<?php
		$this->contentContainerStart();
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'name'=>'comment',
				'required'=>true,
				'rows'=>4,
			)
		);
		$options = array();
		$options[] = array('', '');
		if ($overallStatus["status"] == S_OBSOLETE)
			$options[] = array(S_RELEASED, getOverallStatusText(S_RELEASED));
		if ($overallStatus["status"] != S_OBSOLETE)
			$options[] = array(S_OBSOLETE, getOverallStatusText(S_OBSOLETE));
		if ($overallStatus["status"] != S_DRAFT)
			$options[] = array(S_DRAFT, getOverallStatusText(S_DRAFT));
		if ($overallStatus["status"] != S_NEEDS_CORRECTION)
			$options[] = array(S_NEEDS_CORRECTION, getOverallStatusText(S_NEEDS_CORRECTION));
		$this->formField(
			getMLText("status"),
			array(
				'element'=>'select',
				'name'=>'overrideStatus',
				'options'=>$options,
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('update'));
?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
