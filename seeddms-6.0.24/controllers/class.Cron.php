<?php
/**
 * Implementation of Cron controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2020 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for the regular cron job
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2020 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_Cron extends SeedDMS_Controller_Common {

	public function run() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$logger = $this->params['logger'];
		$mode = $this->params['mode'];
		$db = $dms->getDb();

		$scheduler = new SeedDMS_Scheduler($db);
		$tasks = $scheduler->getTasks();

		$jsonarr = [];
		foreach($tasks as $task) {
			if(isset($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()]) && is_object($taskobj = resolveTask($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()][$task->getTask()]))) {
				$arr = array(
					'extension'=>$task->getExtension(),
					'name'=>$task->getTask(),
					'mode'=>$mode,
					'disabled' => (bool) $task->getDisabled(),
					'isdue' => $task->isDue(),
				);
				switch($mode) {
				case "run":
				case "dryrun":
					if(method_exists($taskobj, 'execute')) {
            if(!$task->getDisabled() && $task->isDue()) {
							if($mode == 'run') {
								/* Schedule the next run right away to prevent a second execution
								 * of the task when the cron job of the scheduler is called before
								 * the last run was finished. The task itself can still be scheduled
								 * to fast, but this is up to the admin of seeddms.
								 */
								$task->updateLastNextRun();
								if($taskobj->execute($task)) {
									add_log_line("Execution of task ".$task->getExtension()."::".$task->getTask()." successful.");
									$arr['success'] = true;
								} else {
									add_log_line("Execution of task ".$task->getExtension()."::".$task->getTask()." failed, task has been disabled.", PEAR_LOG_ERR);
									$arr['success'] = false;
									$task->setDisabled(1);
								}
							} elseif($mode == 'dryrun') {
								$arr['success'] = true;
							}
            }
					}
					break;
				case "check":
					$arr['error'] = false;
					if(!method_exists($taskobj, 'execute')) {
						$arr['error'] = true;
						$arr['messages'][] = 'Missing method execute()';
					}
					if(get_parent_class($taskobj) != 'SeedDMS_SchedulerTaskBase') {
						$arr['error'] = true;
						$arr['error'][] = "Wrong parent class";
					}
					break;
				case "list":
				default:
					header("Content-Type: application/json");
					$arr['nextrun']=$task->getNextRun();
					$arr['frequency']=$task->getFrequency();
					$arr['params']=array();
					if($params = $task->getParameter()) {
						foreach($params as $key=>$value) {
							$p = $taskobj->getAdditionalParamByName($key);
							$arr['params'][$key] = ($p['type'] == 'password') ? '*******' : $value;
						}
					}
					break;
				}
				$jsonarr[] = $arr;
			}
		}
		echo json_encode($jsonarr);

		return true;
	} /* }}} */
}

