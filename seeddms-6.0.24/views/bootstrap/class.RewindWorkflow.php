<?php
/**
 * Implementation of RewindWorkflow view
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
 * Class which outputs the html page for Rewindorkflow view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RewindWorkflow extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$accessobject = $this->params['accessobject'];
		$document = $this->params['document'];

		$latestContent = $document->getLatestContent();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("rewind_workflow"));

		$currentstate = $latestContent->getWorkflowState();
		$wkflogs = $latestContent->getWorkflowLog();
		$wkflog = array_shift($wkflogs);
		$workflow = $latestContent->getWorkflow();

		if($currentstate) {
			$msg = "The document is currently in state: ".$currentstate->getName()."<br />";
			if($wkflog) {
				foreach($wkflog as $entry) {
					if($entry->getTransition()->getNextState()->getID() == $currentstate->getID()) {
						$enterdate = $entry->getDate();
						$enterts = makeTsFromLongDate($enterdate);
					}
				}
				if(!empty($enterdate)) {
					$msg .= "The state was entered at ".$enterdate." which was ";
					$msg .= getReadableDuration((time()-$enterts))." ago.<br />";
				}
			}
			$msg .= "The document may stay in this state for ".$currentstate->getMaxTime()." sec.";
		} else {
			$msg = getMLText('workflow_in_unknown_state');
		}

		// Display the Workflow form.
		$this->rowStart();
		$this->columnStart(4);
		$this->infoMsg($msg);
		$this->warningMsg(getMLText("rewind_workflow_warning"));
?>
	<form method="post" action="../op/op.RewindWorkflow.php" name="form1">
	<?php echo createHiddenFieldWithKey('rewindworkflow'); ?>
	<input type='hidden' name='documentid' value='<?php echo $document->getId(); ?>'/>
	<input type='hidden' name='version' value='<?php echo $latestContent->getVersion(); ?>'/>
	<button type='submit' class="btn btn-danger"><i class="fa fa-refresh"></i> <?php printMLText("rewind_workflow"); ?></button>
	</form>
<?php
		$this->columnEnd();
		$this->columnStart(8);
?>
	<div id="workflowgraph">
<?php
		if($accessobject->check_view_access('WorkflowGraph')) {
?>
	<iframe src="out.WorkflowGraph.php?workflow=<?php echo $workflow->getID(); ?>" width="100%" height="600" style="border: 1px solid #AAA;"></iframe>
<?php
		}
?>
	</div>
<?php
		$this->columnEnd();
		$this->rowEnd();

		if($wkflog) {
			$this->contentHeading(getMLText("workflow_log"));
			echo "<table class=\"table table-condensed table-sm\">";
			echo "<tr><th>".getMLText('action')."</th><th>Start state</th><th>End state</th><th>".getMLText('date')."</th><th>".getMLText('user')."</th><th>".getMLText('comment')."</th></tr>";
			foreach($wkflog as $entry) {
				echo "<tr>";
				echo "<td>".htmlspecialchars(getMLText('action_'.$entry->getTransition()->getAction()->getName()))."</td>";
				echo "<td>".htmlspecialchars($entry->getTransition()->getState()->getName())."</td>";
				echo "<td>".htmlspecialchars($entry->getTransition()->getNextState()->getName())."</td>";
				echo "<td>".$entry->getDate()."</td>";
				echo "<td>".htmlspecialchars($entry->getUser()->getFullname())."</td>";
				echo "<td>".htmlspecialchars($entry->getComment())."</td>";
				echo "</tr>";
			}
			echo "</table>\n";
		}

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
