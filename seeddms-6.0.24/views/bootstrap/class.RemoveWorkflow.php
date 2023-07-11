<?php
/**
 * Implementation of RemoveWorkflow view
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
 * Class which outputs the html page for Removeorkflow view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RemoveWorkflow extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$workflow = $this->params['workflow'];

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($workflow->getName()))));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("rm_workflow"));
		$this->contentContainerStart();
		// Display the Workflow form.
		$this->rowStart();
		$this->columnStart(4);
?>
	<p><?php printMLText("rm_workflow_warning"); ?></p>
	<form method="post" action="../op/op.RemoveWorkflow.php" name="form1">
	<?php echo createHiddenFieldWithKey('removeworkflow'); ?>
	<input type='hidden' name='workflowid' value='<?php echo $workflow->getId(); ?>'/>
	<button type='submit' class="btn btn-danger"><i class="fa fa-remove"></i> <?php printMLText("rm_workflow"); ?></button>
	</form>
<?php
		$this->columnEnd();
		$this->columnStart(8);
?>
	<div id="workflowgraph">
	<iframe src="out.WorkflowGraph.php?workflow=<?php echo $workflow->getID(); ?>" width="100%" height="670" style="border: 1px solid #AAA;"></iframe>
	</div>
<?php
		$this->columnEnd();
		$this->rowEnd();
		$this->contentContainerEnd();

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
