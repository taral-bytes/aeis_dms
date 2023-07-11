<?php
/**
 * Implementation of SetWorkflow view
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
 * Class which outputs the html page for SetWorkflow view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_SetWorkflow extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$document = $this->params['document'];
		header('Content-Type: application/javascript; charset=UTF-8');
?>
function showWorkflow(selectObj) {
	id = selectObj.options[selectObj.selectedIndex].value;
	if (id > 0) {
		$('#workflowgraph').show();
		$('#workflowgraph iframe').attr('src', 'out.WorkflowGraph.php?documentid=<?php echo $document->getID(); ?>&workflow='+id);
	} else {
		$('#workflowgraph').hide();
	}

}
$(document).ready( function() {
	$( "#selector" ).change(function() {
		showWorkflow(this);
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];

		$latestContent = $document->getLatestContent();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("set_workflow"));

		// Display the Workflow form.
		$this->rowStart();
		$this->columnStart(4);
				$workflows = $dms->getAllWorkflows();
				if($workflows) {
?>
		<form class="form-horizontal" action="../op/op.SetWorkflow.php" method="post" name="form1">
		<?php echo createHiddenFieldWithKey('setworkflow'); ?>
		<input type="hidden" name="documentid" value="<?php print $document->getID(); ?>">
		<input type="hidden" name="version" value="<?php print $latestContent->getVersion(); ?>">
		<input type="hidden" name="showtree" value="<?php echo showtree();?>">

<?php
		$this->contentContainerStart();
					$mandatoryworkflow = $user->getMandatoryWorkflow();
					$workflows=$dms->getAllWorkflows();
					$options = array();
					foreach ($workflows as $workflow) {
						$options[] = array($workflow->getID(), htmlspecialchars($workflow->getName()), $mandatoryworkflow && $mandatoryworkflow->getID() == $workflow->getID());
					}
					$this->formField(
						getMLText("workflow"),
						array(
							'element'=>'select',
							'id'=>'selector',
							'name'=>'workflow',
							'data-placeholder'=>getMLText('select_workflow'),
							'options'=>$options
						)
					);
		$this->contentContainerEnd();
					$this->formSubmit(getMLText('set_workflow'));
?>

		</form>
<?php
				} else {
?>
		<p><?php printMLText('no_workflow_available'); ?></p>
<?php
				}
		$this->columnEnd();
		$this->columnStart(8);
?>
	<div id="workflowgraph" style="display: none;">
	<iframe src="" width="100%" height="500" style="border: 1px solid #AAA;"></iframe>
	</div>
<?php
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
