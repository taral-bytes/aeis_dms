<?php
/**
 * Implementation of SchedulerTaskMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for SchedulerTaskMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_SchedulerTaskMgr extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$theme = $this->params['theme'];
		header('Content-Type: application/javascript');
?>
$(document).ready( function() {
	$('body').on('click', '.addtask', function(ev){
		ev.preventDefault();
		$('#editaddtask.ajax').trigger('update', {extension: $(this).data('extension'), task: $(this).data('task')});
	});
	$('body').on('click', '.listtasks', function(ev){
		ev.preventDefault();
		$('#listtasks.ajax').trigger('update', {extension: $(this).data('extension'), task: $(this).data('task')});
	});
	$('body').on('click', '.edittask', function(ev){
		ev.preventDefault();
		$('#editaddtask.ajax').trigger('update', {taskid: $(this).data('id'), action: $(this).data('action')});
		$("html, body").animate({ scrollTop: 0 }, "slow");
	});
	$('#listtasks.ajax').trigger('update', {});

	$('body').on('click', '.removetask', function(ev){
		ev.preventDefault();
		ev.stopPropagation();
		id = $(ev.currentTarget).attr('rel');
		confirmmsg = $(ev.currentTarget).attr('confirmmsg');
		msg = $(ev.currentTarget).attr('msg');
		formtoken = '<?= createFormKey('removetask') ?>';
<?php if($theme == 'bootstrap'): ?>
		bootbox.dialog(confirmmsg, [{
			"label" : "<i class='fa fa-remove'></i> <?= getMLText("rm_task") ?>",
			"class" : "btn-danger",
			"callback": function() {
				$.post('../op/op.SchedulerTaskMgr.php',
					{ action: 'removetask', taskid: id, formtoken: formtoken },
					function(data) {
						if(data.success) {
							$('#table-row-task-'+id).hide('slow');
							noty({
								text: msg,
								type: 'success',
								dismissQueue: true,
								layout: 'topRight',
								theme: 'defaultTheme',
								timeout: 1500,
							});
						} else {
							noty({
								text: data.message,
								type: 'error',
								dismissQueue: true,
								layout: 'topRight',
								theme: 'defaultTheme',
								timeout: 3500,
							});
						}
					},
					'json'
				);
			}
		}, {
			"label" : "<?= getMLText("cancel") ?>",
			"class" : "btn-cancel",
			"callback": function() {
			}
		}]);
<?php else: ?>
		bootbox.confirm({
			"message": confirmmsg,
			"buttons": {
				"confirm": {
					"label" : "<i class='fa fa-remove'></i> <?= getMLText("rm_task") ?>",
					"className" : "btn-danger",
				},
				"cancel": {
					"label" : "<?= getMLText("cancel") ?>",
					"className" : "btn-secondary",
				}
			},
			"callback": function(result) {
				if(result) {
					$.post('../op/op.SchedulerTaskMgr.php',
						{ action: 'removetask', taskid: id, formtoken: formtoken },
						function(data) {
							if(data.success) {
								$('#table-row-task-'+id).hide('slow');
								noty({
									text: msg,
									type: 'success',
									dismissQueue: true,
									layout: 'topRight',
									theme: 'defaultTheme',
									timeout: 1500,
								});
							} else {
								noty({
									text: data.message,
									type: 'error',
									dismissQueue: true,
									layout: 'topRight',
									theme: 'defaultTheme',
									timeout: 3500,
								});
							}
						},
						'json'
					);
				}
			}
		});
<?php endif ?>
	});
});
<?php
	} /* }}} */

	public function form() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$extname = $this->params['extname'];
		$taskname = $this->params['taskname'];
		if($extname && $taskname && is_object($taskobj = resolveTask($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$extname][$taskname]))) {
			if(method_exists($taskobj, 'getAdditionalParams'))
				$additionalparams = $taskobj->getAdditionalParams();
			else
				$additionalparams = null;
?>
	<form action="../op/op.SchedulerTaskMgr.php" method="post" class="form-horizontal">
	<?= createHiddenFieldWithKey('addtask') ?>
	<input type="hidden" name="action" value="addtask">
	<input type="hidden" name="extension" value="<?= $extname ?>">
	<input type="hidden" name="task" value="<?= $taskname ?>">
	<div class="control-group">
		<label class="control-label" for="extension"><?= getMLText("scheduler_class");?>:</label>
		<div class="controls">
		<span class="input uneditable-input"><?= $extname ?>::<?= $taskname ?></span>
		</div>
	</div>
<?php
			$this->formField(
				getMLText('task_name'),
				array(
					'element'=>'input',
					'type'=>'text',
					'id'=>'name',
					'name'=>'name',
					'value'=>'',
					'required'=>true,
				)
			);
			$this->formField(
				getMLText('task_description'),
				array(
					'element'=>'input',
					'type'=>'text',
					'id'=>'description',
					'name'=>'description',
					'value'=>'',
					'required'=>false,
				)
			);
			$this->formField(
				getMLText('task_frequency'),
				array(
					'element'=>'input',
					'type'=>'text',
					'id'=>'frequency',
					'name'=>'frequency',
					'value'=>'',
					'required'=>true,
					'placeholder'=>getMLText('task_frequency_placeholder'),
				)
			);
			$this->formField(
				getMLText('task_disabled'),
				array(
					'element'=>'input',
					'type'=>'checkbox',
					'id'=>'disabled',
					'name'=>'disabled',
					'value'=>'1',
					'checked'=>true,
				)
			);
			if($additionalparams) {
				foreach($additionalparams as $param) {
					switch($param['type']) {
					case 'boolean':
						$this->formField(
							getMLText("task_".$extname."_".$taskname."_".$param['name']),
							array(
								'element'=>'input',
								'type'=>'checkbox',
								'id'=>'params_'.$param['name'],
								'name'=>'params['.$param['name'].']',
								'value'=>'1',
								'checked'=>false,
							),
							array(
								'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
							)
						);
						break;
					case 'password':
						$this->formField(
							getMLText('task_'.$extname."_".$taskname."_".$param['name']),
							array(
								'element'=>'input',
								'type'=>'password',
								'id'=>'params_'.$param['name'],
								'name'=>'params['.$param['name'].']',
								'required'=>false
							),
							array(
								'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
							)
						);
						break;
					case 'select':
						$this->formField(
							getMLText('task_'.$extname."_".$taskname."_".$param['name']),
							array(
								'element'=>'select',
								'class'=>'chzn-select',
								'name'=>'params['.$param['name'].']'.(!empty($param['multiple']) ? '[]' : ''),
								'multiple'=>isset($param['multiple']) ? $param['multiple'] : false,
								'attributes'=>array(array('data-placeholder', getMLText('select_value'), array('data-no_results_text', getMLText('unknown_value')))),
								'options'=>$param['options'],
							),
							array(
								'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
							)
						);
						break;
					case "folder":
						$this->formField(
							getMLText('task_'.$extname."_".$taskname."_".$param['name']),
							$this->getFolderChooserHtml("form".$extname.$taskname, M_READ, -1, 0, 'params['.$param['name']."]")
						);
						break;
					case "users":
						$users = $dms->getAllUsers();
						$options = [];
						foreach ($users as $currUser) {
							if (!$currUser->isGuest())
								$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin().' - '.$currUser->getFullName()), false, array(array('data-subtitle', htmlspecialchars($currUser->getEmail()))));
						}
						$this->formField(
							getMLText('task_'.$extname."_".$taskname."_".$param['name']),
							array(
								'element'=>'select',
								'class'=>'chzn-select',
								'name'=>'params['.$param['name'].']'.(!empty($param['multiple']) ? '[]' : ''),
								'multiple'=>isset($param['multiple']) ? $param['multiple'] : false,
								'attributes'=>array(array('data-placeholder', getMLText('select_value'), array('data-no_results_text', getMLText('unknown_value')))),
								'options'=>$options
							),
							array(
								'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
							)
						);
						break;
					default:
						$this->formField(
							getMLText('task_'.$extname."_".$taskname."_".$param['name']),
							array(
								'element'=>'input',
								'type'=>(($param['type'] == 'integer') ? 'number' : 'text'),
								'id'=>'params_'.$param['name'],
								'name'=>'params['.$param['name'].']',
								'required'=>false
							),
							array(
								'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
							)
						);
						break;
					}
				}
			}
?>
	<div class="control-group">
		<label class="control-label" for="login"></label>
		<div class="controls">
			<?php $this->formSubmit('<i class="fa fa-save"></i> '.getMLText('save'),'','','primary');?>
		</div>
	</div>
	</form>
<?php
		}
	} /* }}} */

	public function edittask() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$scheduler = $this->params['scheduler'];
		$taskid = $this->params['taskid'];

		$task = $scheduler->getTask($taskid);
		if(!isset($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()])) {
			$this->errorMsg(getMLText('scheduler_extension_not_available'));
			return;
		}

		$extname = $task->getExtension();
		$taskname = $task->getTask();
		$taskobj = $GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$extname][$taskname];
		$taskobj = resolveTask($taskobj);
		if(!is_object($taskobj)) {
			$this->errorMsg(getMLText('task_class_not_callable'));
			return;
		}
?>
	<form action="../op/op.SchedulerTaskMgr.php" method="post" class="form-horizontal">
	<?=	createHiddenFieldWithKey('edittask') ?>
	<input type="hidden" name="action" value="edittask">
	<input type="hidden" name="taskid" value="<?= $taskid ?>">
	<input type="hidden" name="extension" value="<?= $extname ?>">
	<input type="hidden" name="task" value="<?= $taskname ?>">
<?php
		$this->formField(
			getMLText('scheduler_class'),
			$extname
		);
		$this->formField(
			getMLText('task_name'),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
				'value'=>$task->getName(),
				'required'=>true,
			)
		);
		$this->formField(
			getMLText('task_description'),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'description',
				'name'=>'description',
				'value'=>$task->getDescription(),
				'required'=>false,
			)
		);
		$this->formField(
			getMLText('task_frequency'),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'frequency',
				'name'=>'frequency',
				'value'=>$task->getFrequency(),
				'required'=>true,
				'placeholder'=>getMLText('task_frequency_placeholder'),
			)
		);
		$this->formField(
			getMLText('task_disabled'),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'id'=>'disabled',
				'name'=>'disabled',
				'value'=>'1',
				'checked'=>$task->getDisabled(),
			)
		);
		if($additionalparams = $taskobj->getAdditionalParams()) {
			foreach($additionalparams as $param) {
				switch($param['type']) {
				case 'boolean':
					$this->formField(
						getMLText("task_".$extname."_".$taskname."_".$param['name']),
						array(
							'element'=>'input',
							'type'=>'checkbox',
							'id'=>'params_'.$param['name'],
							'name'=>'params['.$param['name'].']',
							'value'=>'1',
							'checked'=>$task->getParameter($param['name']) == 1,
						),
						array(
							'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
						)
					);
					break;
				case 'password':
					$this->formField(
						getMLText("task_".$extname."_".$taskname."_".$param['name']),
						array(
							'element'=>'input',
							'type'=>'password',
							'id'=>'params_'.$param['name'],
							'name'=>'params['.$param['name'].']',
							'value'=>$task->getParameter()[$param['name']],
							'required'=>false
						),
						array(
							'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
						)
					);
					break;
				case 'select':
					if(!empty($param['multiple']))
						$vals = $task->getParameter()[$param['name']];
					else
						$vals = [$task->getParameter()[$param['name']]];
					foreach($param['options'] as &$opt) {
						if($opt[0] && in_array($opt[0], $vals))
							$opt[2] = true;
					}
					$this->formField(
						getMLText('task_'.$extname."_".$taskname."_".$param['name']),
						array(
							'element'=>'select',
							'class'=>'chzn-select',
							'name'=>'params['.$param['name'].']'.(!empty($param['multiple']) ? '[]' : ''),
							'multiple'=>isset($param['multiple']) ? $param['multiple'] : false,
							'attributes'=>array(array('data-placeholder', getMLText('select_value'), array('data-no_results_text', getMLText('unknown_value')))),
							'options'=>$param['options'],
						),
						array(
							'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
						)
					);
					break;
				case "folder":
					$folderid = $task->getParameter()[$param['name']];
					$this->formField(
						getMLText('task_'.$extname."_".$taskname."_".$param['name']),
					 	$this->getFolderChooserHtml("form".$extname.$taskid, M_READ, -1, $folderid ? $dms->getFolder($folderid) : 0, 'params['.$param['name']."]")
					);
					break;
				case "users":
					if(!empty($param['multiple']))
						$userids = $task->getParameter()[$param['name']];
					else
						$userids = [$task->getParameter()[$param['name']]];
					$users = $dms->getAllUsers();
					$options = [];
					foreach ($users as $currUser) {
						if (!$currUser->isGuest())
							$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin().' - '.$currUser->getFullName()), in_array($currUser->getID(), $userids), array(array('data-subtitle', htmlspecialchars($currUser->getEmail()))));
					}
					$this->formField(
						getMLText('task_'.$extname."_".$taskname."_".$param['name']),
						array(
							'element'=>'select',
							'class'=>'chzn-select',
							'name'=>'params['.$param['name'].']'.(!empty($param['multiple']) ? '[]' : ''),
							'multiple'=>isset($param['multiple']) ? $param['multiple'] : false,
							'attributes'=>array(array('data-placeholder', getMLText('select_value'), array('data-no_results_text', getMLText('unknown_value')))),
							'options'=>$options
						),
						array(
							'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
						)
					);
					break;
				default:
					$this->formField(
						getMLText("task_".$extname."_".$taskname."_".$param['name']),
						array(
							'element'=>'input',
							'type'=>(($param['type'] == 'integer') ? 'number' : 'text'),
							'id'=>'params_'.$param['name'],
							'name'=>'params['.$param['name'].']',
							'value'=>$task->getParameter()[$param['name']],
							'required'=>false
						),
						array(
							'help'=>isset($param['description']) ? $param['description'] : getMLText("task_".$extname."_".$taskname."_".$param['name']."_desc")
						)
					);
					break;
				}
			}
		}
?>
	<div class="control-group">
		<label class="control-label" for="login"></label>
		<div class="controls">
			<?php $this->formSubmit('<i class="fa fa-save"></i> '.getMLText('save'),'','','primary');?>
		</div>
	</div>
	</form>
<?php
	} /* }}} */

	public function tasklist() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$extname = $this->params['extname'];
		$taskname = $this->params['taskname'];
		$scheduler = $this->params['scheduler'];

		if($extname && $taskname)
			$tasks = $scheduler->getTasksByExtension($extname, $taskname);
		else
			$tasks = $scheduler->getTasks();
		if(!$tasks)
			return;

		$this->contentHeading(getMLText("scheduler_class_tasks"));
		echo "<table class=\"table _table-condensed\">\n";
		print "<thead>\n<tr>\n";
		print "<th>".getMLText('scheduler_class')."</th>\n";	
		print "<th>".getMLText('task_name')."/".getMLText('task_description')."</th>\n";	
		print "<th>".getMLText('task_frequency')."</th>\n";	
		print "<th>".getMLText('task_next_run')."</th>\n";	
		print "<th>".getMLText('task_last_run')."</th>\n";	
		print "<th></th>\n";	
		print "</tr></thead><tbody>\n";
		foreach($tasks as $task) {
			if(!isset($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()][$task->getTask()]) || !is_object(resolveTask($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()][$task->getTask()])))
				$class = 'table-danger error';
			else
				$class = 'table-success success';
			echo "<tr id=\"table-row-task-".$task->getID()."\" class=\"".(!$task->getDisabled() ? " ".$class : "")."\">";
			echo "<td>";
			echo $task->getExtension()."::".$task->getTask();
			echo "</td>";
			echo "<td width=\"100%\">";
			echo "<strong>".$task->getName()."</strong></br>";
			echo $task->getDescription();
			echo "</td>";
			echo "<td>";
			echo $task->getFrequency();
			echo "</td>";
			echo "<td>";
			echo getLongReadableDate(makeTsFromDate($task->getNextRun()));
			echo "</td>";
			echo "<td>";
			if($task->getLastRun())
				echo getLongReadableDate(makeTsFromDate($task->getLastRun()));
			echo "</td>";
			echo "<td nowrap>";
			print "<div class=\"list-action\">";
			print "<a class=\"removetask\" rel=\"".$task->getID()."\" msg=\"".getMLText('remove_task')."\" confirmmsg=\"".htmlspecialchars(getMLText("confirm_rm_task"), ENT_QUOTES)."\" title=\"".getMLText("remove_task")."\"><i class=\"fa fa-remove\"></i></a>";
			if(isset($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()])) {
				print "<a class=\"edittask\" data-action=\"edittask\" data-id=\"".$task->getID()."\" href=\"../out/out.SchedulerTaskMgr.php?action=edittask\" title=\"".getMLText("edit_task")."\"><i class=\"fa fa-edit\"></i></a>";
			}
			print "</div>";
			echo "</td>";
			echo "</tr>";
		}
		echo "</tbody></table>\n";
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$extname = $this->params['extname'];
		$taskname = $this->params['taskname'];
		$scheduler = $this->params['scheduler'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("scheduler_task_mgr"));
		$this->rowStart();
		$this->columnStart(6);
		if(!empty($GLOBALS['SEEDDMS_SCHEDULER']['tasks'])) {
			echo "<table class=\"table _table-condensed\">\n";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText('scheduler_class')."</th>\n";	
			print "<th>".getMLText('scheduler_class_description')."</th>\n";	
			print "<th>".getMLText('scheduler_class_parameter')."</th>\n";	
			print "<th></th>\n";	
			print "</tr></thead><tbody>\n";
			$errmsgs = array();
			foreach($GLOBALS['SEEDDMS_SCHEDULER']['tasks'] as $extname=>$tasks) {
				foreach($tasks as $taskname=>$task) {
					$task = resolveTask($task);
					if(!is_object($task))
						continue;
					echo "<tr>";
					echo "<td>";
					echo $extname."::".$taskname;
					echo "</td>";
					echo "<td width=\"100%\">";
					echo $task->getDescription();
					echo "</td>";
					echo "<td>";
					$params = $task->getAdditionalParams();
					$k = array();
					foreach($params as $param)
						$k[] = $param['name'];
					echo implode(', ', $k);
					echo "</td>";
					echo "<td>";
					print "<div class=\"list-action\">";
					$t = $scheduler->getTasksByExtension($extname, $taskname);
					if($t) {
						print "<a class=\"listtasks\" data-extension=\"".$extname."\" data-task=\"".$taskname."\" href=\"../out/out.SchedulerTaskMgr.php?extension=".$extname."&task=".$taskname."\" title=\"".getMLText("list_tasks")."\"><i class=\"fa fa-list\"></i></a>";
					}
					print "<a class=\"addtask\" data-extension=\"".$extname."\" data-task=\"".$taskname."\" href=\"../out/out.SchedulerTaskMgr.php?extension=".$extname."&task=".$taskname."\" title=\"".getMLText("add_task")."\"><i class=\"fa fa-plus\"></i></a>";
					print "</div>";
					echo "</td>";
					echo "</tr>";
				}
			}
			echo "</tbody></table>\n";
		}
?>
		<div id="listtasks" class="ajax" data-view="SchedulerTaskMgr" data-action="tasklist"></div>
<?php
		$this->columnEnd();
		$this->columnStart(6);
?>
		<div id="editaddtask" class="ajax" data-view="SchedulerTaskMgr" data-action="form"></div>
<?php
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
