<?php
if(isset($_SERVER['SEEDDMS_HOME'])) {
	ini_set('include_path', $_SERVER['SEEDDMS_HOME'].'/utils'. PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = $_SERVER['SEEDDMS_HOME'];
} else {
	ini_set('include_path', dirname($argv[0]). PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = dirname($argv[0]);
}


function usage() { /* {{{ */
	echo "Usage:\n";
	echo "  seeddms-schedulercli [-h] [-v] [--config <file>]\n";
	echo "\n";
	echo "Description:\n";
	echo "  Check for scheduled tasks.\n";
	echo "\n";
	echo "Options:\n";
	echo "  -h, --help: print usage information and exit.\n";
	echo "  -v, --version: print version and exit.\n";
	echo "  --config: set alternative config file.\n";
	echo "  --mode: set mode of operation (run, dryrun, check, list).\n";
} /* }}} */

$version = "0.0.1";
$shortoptions = "hvc";
$longoptions = array('help', 'version', 'config:', 'mode:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
	usage();
	exit(0);
}

/* Print help and exit */
if(isset($options['h']) || isset($options['help'])) {
	usage();
	exit(0);
}

/* Print version and exit */
if(isset($options['v']) || isset($options['verѕion'])) {
	echo $version."\n";
	exit(0);
}

/* Set alternative config file */
if(isset($options['config'])) {
	define('SEEDDMS_CONFIG_FILE', $options['config']);
} elseif(isset($_SERVER['SEEDDMS_CONFIG_FILE'])) {
	define('SEEDDMS_CONFIG_FILE', $_SERVER['SEEDDMS_CONFIG_FILE']);
}

$mode = 'list';
if(isset($options['mode'])) {
	if(!in_array($options['mode'], array('run', 'dryrun', 'check', 'list'))) {
		usage();
		exit(1);
	}
	$mode = $options['mode'];
}

include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.Utils.php");
include($myincpath."/inc/inc.LogInit.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Language.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");
include($myincpath."/inc/inc.Scheduler.php");
include($myincpath."/inc/inc.ClassController.php");

if(!($user = $dms->getUserByLogin('cli_scheduler'))) {
	add_log_line("Execution of tasks failed because of missing user 'cli_scheduler'. Will exit now.", PEAR_LOG_ERR);
	exit;
}

$scheduler = new SeedDMS_Scheduler($db);
$tasks = $scheduler->getTasks();

foreach($tasks as $task) {
	if(isset($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()]) && is_object($taskobj = resolveTask($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()][$task->getTask()]))) {
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
						add_log_line("Running '".$task->getExtension()."::".$task->getTask()."::".$task->getName()."'");
						echo "Running '".$task->getExtension()."::".$task->getTask()."::".$task->getName()."'\n";
						if($taskobj->execute($task)) {
							add_log_line("Execution of task '".$task->getExtension()."::".$task->getTask()."::".$task->getName()."' successful.");
						} else {
							add_log_line("Execution of task ".$task->getExtension()."::".$task->getTask()."::".$task->getName()." failed, task has been disabled.", PEAR_LOG_ERR);
							$task->setDisabled(1);
						}
					} elseif($mode == 'dryrun') {
						echo "Running '".$task->getExtension()."::".$task->getTask()."::".$task->getName()."' in dry mode\n";
					}
				}
			}
			break;
		case "check":
			echo "Checking ".$task->getExtension()."::".$task->getTask().":\n";
			if(!method_exists($taskobj, 'execute')) {
				echo "  Missing method execute()\n";
			}
			if(get_parent_class($taskobj) != 'SeedDMS_SchedulerTaskBase') {
				echo "  wrong parent class\n";
			}
			break;
		case "list":
			if(!$task->getDisabled()) {
				if($task->isDue())
					echo "*";
				else
					echo " ";
			} else {
					echo "-";
			}
			echo " '".$task->getExtension()."::".$task->getTask()."::".$task->getName()."'";
			echo " ".$task->getNextRun();
			echo " ".$task->getFrequency();
			echo "\n";
			if($params = $task->getParameter()) {
				foreach($params as $key=>$value) {
					$p = $taskobj->getAdditionalParamByName($key);
					echo "    ".$key.": ";
					switch($p['type']) {
					case 'password':
						echo '********';
						break;
					default:
						if(is_array($value))
							echo implode(', ', $value);
						else
							echo $value;
					}
					echo PHP_EOL;
				}
			}
			break;
		}
	}

}
