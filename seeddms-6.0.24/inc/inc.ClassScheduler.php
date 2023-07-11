<?php
/**
 * Implementation of an SchedulerTask.
 *
 * SeedDMS can be extended by extensions. Extension usually implement
 * hook.
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2018 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a SchedulerTask
 *
 * This class provides some very basic methods to manage extensions.
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2011 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Scheduler {

	/**
	 * Instanz of database
	 */
	protected $db;

	public function getTask($id) { /* {{{ */
		return SeedDMS_SchedulerTask::getInstance($id, $this->db);
	} /* }}} */

	public function getTasksByExtension($extname, $taskname) { /* {{{ */
		return SeedDMS_SchedulerTask::getInstancesByExtension($extname, $taskname, $this->db);
	} /* }}} */

	public function getTasks() { /* {{{ */
		return SeedDMS_SchedulerTask::getInstances($this->db);
	} /* }}} */

	public function addTask($extname, $taskname, $name, $description, $frequency, $disabled, $params) { /* {{{ */
		$db = $this->db;
		if(!$extname)
			return false;
		if(!$taskname)
			return false;
		try {
			$cron = Cron\CronExpression::factory($frequency);
		} catch (Exception $e) {
			return false;
		}
		$nextrun = $cron->getNextRunDate()->format('Y-m-d H:i:s');

		$queryStr = "INSERT INTO `tblSchedulerTask` (`extension`, `task`, `name`, `description`, `frequency`, `disabled`, `params`, `nextrun`, `lastrun`) VALUES (".$db->qstr($extname).", ".$db->qstr($taskname).", ".$db->qstr($name).", ".$db->qstr($description).", ".$db->qstr($frequency).", ".intval($disabled).", ".$db->qstr(json_encode($params)).", '".$nextrun."', NULL)";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$task = SeedDMS_SchedulerTask::getInstance($db->getInsertID('tblSchedulerTask'), $db);

		return $task;
	} /* }}} */

	function __construct($db) {
		$this->db = $db;
	}

}
