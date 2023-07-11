<?php
/**
 * Implementation of WorkspaceActionsMgr view
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
 * Class which outputs the html page for WorkspaceActionsMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_WorkflowActionsMgr extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
function runValidation() {
	$("#form1").validate({
		rules: {
			name: {
				required: true
			},
		},
		messages: {
			name: "<?php printMLText("js_no_name");?>",
		}
	});
}

$(document).ready( function() {
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {workflowactionid: $(this).val()});
	});
});
<?php
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selworkflowaction = $this->params['selworkflowaction'];

		if($selworkflowaction) {
			if($selworkflowaction->isUsed()) {
				$transitions = $selworkflowaction->getTransitions();
				if($transitions) {
					echo "<table class=\"table table-condensed table-sm\">";
					echo "<thead><tr><th>".getMLText('workflow')."</th><th>".getMLText('previous_state')."</th><th>".getMLText('next_state')."</th></tr></thead>\n";
					echo "<tbody>";
					foreach($transitions as $transition) {
						$state = $transition->getState();
						$nextstate = $transition->getNextState();
						$docstatus = $nextstate->getDocumentStatus();
						$workflow = $transition->getWorkflow();
						echo "<tr>";
						echo "<td>";
						echo htmlspecialchars($workflow->getName());
						echo "</td><td>";
						echo '<i class="fa fa-circle'.($workflow->getInitState()->getId() == $state->getId() ? ' initstate' : ' in-workflow').'"></i> '.htmlspecialchars($state->getName());
						echo "</td><td>";
						echo '<i class="fa fa-circle'.($docstatus == S_RELEASED ? ' released' : ($docstatus == S_REJECTED ? ' rejected' : ' in-workflow')).'"></i> '.htmlspecialchars($nextstate->getName());
						echo "</td></tr>";
					}
					echo "</tbody>";
					echo "</table>";
				}
			}
		}
	} /* }}} */

	function actionmenu() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selworkflowaction = $this->params['selworkflowaction'];

		if($selworkflowaction && !$selworkflowaction->isUsed()) {
			$button = array(
				'label'=>getMLText('action'),
				'menuitems'=>array(
				)
			);
			$button['menuitems'][] = array('label'=>'<i class="fa fa-remove"></i> '.getMLText("rm_workflow_action"), 'link'=>'../op/op.RemoveWorkflowAction.php?workflowactionid='.$selworkflowaction->getID().'&formtoken='.createFormKey('removeworkflowaction'));
			self::showButtonwithMenu($button);
		}
	} /* }}} */

	function showWorkflowActionForm($action) { /* {{{ */
		if($action) {
			if($action->isUsed()) {
				$this->infoMsg(getMLText('workflow_action_in_use'));
			}
		}
?>
<form action="../op/op.WorkflowActionsMgr.php" method="post" class="form-horizontal" id="form1" name="form1">
<?php
		if($action) {
			echo createHiddenFieldWithKey('editworkflowaction');
?>
	<input type="hidden" name="workflowactionid" value="<?php print $action->getID();?>">
	<input type="hidden" name="action" value="editworkflowaction">
<?php
		} else {
			echo createHiddenFieldWithKey('addworkflowaction');
?>
			<input type="hidden" name="action" value="addworkflowaction">
<?php
		}
		$this->contentContainerStart();
		$this->formField(
			getMLText("workflow_action_name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
				'value'=>($action ? htmlspecialchars($action->getName()) : '')
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit('<i class="fa fa-save"></i> '.getMLText("save"));
?>
	</form>
<?php
	} /* }}} */

	function form() { /* {{{ */
		$selworkflowaction = $this->params['selworkflowaction'];

		$this->showWorkflowActionForm($selworkflowaction);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selworkflowaction = $this->params['selworkflowaction'];

		$workflowactions = $dms->getAllWorkflowActions();

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("workflow_actions_management"));
		$this->rowStart();
		$this->columnStart(4);
?>
		<form class="form-horizontal">
<?php
		$options = array();
		$options[] = array('-1', getMLText("choose_workflow_action"));
		$options[] = array('0', getMLText("add_workflow_action"));
		foreach ($workflowactions as $currWorkflowAction) {
			$options[] = array($currWorkflowAction->getID(), htmlspecialchars($currWorkflowAction->getName()), $selworkflowaction && $currWorkflowAction->getID()==$selworkflowaction->getID());
		}
		$this->formField(
			null, //getMLText("selection"),
			array(
				'element'=>'select',
				'id'=>'selector',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
?>
		</form>
	  <div class="ajax" style="margin-bottom: 15px;" data-view="WorkflowActionsMgr" data-action="actionmenu" <?php echo ($selworkflowaction ? "data-query=\"workflowactionid=".$selworkflowaction->getID()."\"" : "") ?>></div>
		<div class="ajax" data-view="WorkflowActionsMgr" data-action="info" <?php echo ($selworkflowaction ? "data-query=\"workflowactionid=".$selworkflowaction->getID()."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->columnStart(8);
?>
		<div class="ajax" data-view="WorkflowActionsMgr" data-action="form" data-afterload="()=>{runValidation();}" <?php echo ($selworkflowaction ? "data-query=\"workflowactionid=".$selworkflowaction->getID()."\"" : "") ?>></div>
	</div>
</div>

<?php
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
