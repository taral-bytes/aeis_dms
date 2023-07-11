<?php
/**
 * Implementation of TriggerWorkflow view
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
 * Class which outputs the html page for TriggerWorkflow view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_TriggerWorkflow extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
$(document).ready(function() {
	$("#form1").validate({
		messages: {
			comment: "<?php printMLText("js_no_comment");?>"
		},
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$accessobject = $this->params['accessobject'];
		$document = $this->params['document'];
		$transition = $this->params['transition'];
		$latestContent = $document->getLatestContent();

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("trigger_workflow"));

		$action = $transition->getAction();
		$currentstate = $latestContent->getWorkflowState();
		$wkflogs = $latestContent->getWorkflowLog();
		/* Check if latest content is still in workflow, which should be
		 * always the case, otherwise this code would be executed.
		 * In that case the returned log is just a list of entries for the
		 * current workflow. If the document was not in a workflow, then the
		 * log entries for all workflows of this content will be returned
		 */
		if($workflow = $latestContent->getWorkflow())
			$wkflog = $wkflogs;
		else
			$wkflog = array_shift($wkflogs);

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

		//$this->contentContainerStart();
		// Display the Workflow form.
		$this->rowStart();
		$this->columnStart(4);
		$this->infoMsg($msg);
?>
	<form class="form-horizontal" method="post" action="../op/op.TriggerWorkflow.php" id="form1" name="form1">
	<input type='hidden' name='documentid' value='<?php echo $document->getId(); ?>'/>
	<input type='hidden' name='version' value='<?php echo $latestContent->getVersion(); ?>'/>
	<input type='hidden' name='transition' value='<?php echo $transition->getID(); ?>'/>
	<?php echo createHiddenFieldWithKey('triggerworkflow'); ?>
<?php
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'name'=>'comment',
				'rows'=>4,
				'required'=>false
			)
		);
		$this->formSubmit(getMLText("action_".strtolower($action->getName()), array(), htmlspecialchars($action->getName())));
?>
	</form>
<?php
		$this->columnEnd();
		$this->columnStart(8);
?>
	<div id="workflowgraph">
<?php
		if($accessobject->check_view_access('WorkflowGraph')) {
?>
	<iframe src="out.WorkflowGraph.php?workflow=<?php echo $workflow->getID(); ?>&transitions[]=<?php echo $transition->getID(); ?>&documentid=<?php echo $document->getID(); ?>" width="100%" height="600" style="border: 1px solid #AAA;"></iframe>
<?php
		}
?>
	</div>
<?php
		$this->columnEnd();
		$this->rowEnd();
		//$this->contentContainerEnd();

		if($wkflog) {
			$this->contentHeading(getMLText("workflow_log"));
			echo "<table class=\"table table-condensed table-sm\">";
			echo "<tr><th>".getMLText('action')."</th><th>Start state</th><th>End state</th><th>".getMLText('date')."</th><th>".getMLText('user')."</th><th>".getMLText('comment')."</th></tr>";
			foreach($wkflog as $entry) {
				echo "<tr>";
				echo "<td>".htmlspecialchars(getMLText('action_'.strtolower($entry->getTransition()->getAction()->getName()), array(), $entry->getTransition()->getAction()->getName()))."</td>";
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
