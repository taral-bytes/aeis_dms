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
class SeedDMS_SchedulerTask {
	/**
	 * Instanz of database
	 */
	protected $db;

	/**
	 * @var integer unique id of task
	 */
	protected $_id;

	/**
	 * @var string name of task
	 */
	protected $_name;

	/**
	 * @var string description of task
	 */
	protected $_description;

	/**
	 * @var string extension of task
	 */
	protected $_extension;

	/**
	 * @var string task of task
	 */
	protected $_task;

	/**
	 * @var string frequency of task
	 */
	protected $_frequency;

	/**
	 * @var integer set if disabled
	 */
	protected $_disabled;

	/**
	 * @var integer last run
	 */
	protected $_lastrun;

	/**
	 * @var integer next run
	 */
	protected $_nextrun;

	public static function getInstance($id, $db) { /* {{{ */
		$queryStr = "SELECT * FROM `tblSchedulerTask` WHERE `id` = " . (int) $id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return null;
		$row = $resArr[0];

		$task = new self($row["id"], $row['name'], $row["description"], $row["extension"], $row["task"], $row["frequency"], $row['disabled'], json_decode($row['params'], true), $row["nextrun"], $row["lastrun"]);
		$task->setDB($db);

		return $task;
	} /* }}} */

	public static function getInstances($db) { /* {{{ */
		$queryStr = "SELECT * FROM `tblSchedulerTask`";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) == 0)
			return array();

		$tasks = array();
		foreach($resArr as $row) {
			$task = new self($row["id"], $row['name'], $row["description"], $row["extension"], $row["task"], $row["frequency"], $row['disabled'], json_decode($row['params'], true), $row["nextrun"], $row["lastrun"]);
			$task->setDB($db);
			$tasks[] = $task;
		}

		return $tasks;
	} /* }}} */

	public static function getInstancesByExtension($extname, $taskname, $db) { /* {{{ */
		$queryStr = "SELECT * FROM `tblSchedulerTask` WHERE `extension` = '".$extname."' AND `task` = '".$taskname."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) == 0)
			return array();

		$tasks = array();
		foreach($resArr as $row) {
			$task = new self($row["id"], $row['name'], $row["description"], $row["extension"], $row["task"], $row["frequency"], $row['disabled'], json_decode($row['params'], true), $row["nextrun"], $row["lastrun"]);
			$task->setDB($db);
			$tasks[] = $task;
		}

		return $tasks;
	} /* }}} */

	function __construct($id, $name, $description, $extension, $task, $frequency, $disabled, $params, $nextrun, $lastrun) {
		$this->_id = $id;
		$this->_name = $name;
		$this->_description = $description;
		$this->_extension = $extension;
		$this->_task = $task;
		$this->_frequency = $frequency;
		$this->_disabled = $disabled;
		$this->_params = $params;
		$this->_nextrun = $nextrun;
		$this->_lastrun = $lastrun;
	}

	public function setDB($db) {
		$this->db = $db;
	}

	public function getID() {
		return $this->_id;
	}

	public function getName() {
		return $this->_name;
	}

	public function setName($newName) { /* {{{ */
		$db = $this->db;

		$queryStr = "UPDATE `tblSchedulerTask` SET `name` =".$db->qstr($newName)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	public function getDescription() {
		return $this->_description;
	}

	public function setDescription($newDescripion) { /* {{{ */
		$db = $this->db;

		$queryStr = "UPDATE `tblSchedulerTask` SET `description` =".$db->qstr($newDescripion)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_description = $newDescripion;
		return true;
	} /* }}} */

	public function getExtension() {
		return $this->_extension;
	}

	public function getTask() {
		return $this->_task;
	}

	public function getFrequency() {
		return $this->_frequency;
	}

	public function setFrequency($newFrequency) { /* {{{ */
		$db = $this->db;

		try {
			$cron = Cron\CronExpression::factory($newFrequency);
		} catch (Exception $e) {
			return false;
		}
		$nextrun = $cron->getNextRunDate()->format('Y-m-d H:i:s');

		$queryStr = "UPDATE `tblSchedulerTask` SET `frequency` =".$db->qstr($newFrequency).", `nextrun` = '".$nextrun."' WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_frequency = $newFrequency;
		$this->_nextrun = $nextrun;
		return true;
	} /* }}} */

	public function getNextRun() {
		return $this->_nextrun;
	}

	public function getLastRun() {
		return $this->_lastrun;
	}

	public function getDisabled() {
		return $this->_disabled;
	}

	public function setDisabled($newDisabled) { /* {{{ */
		$db = $this->db;

		$queryStr = "UPDATE `tblSchedulerTask` SET `disabled` =".intval($newDisabled)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_disabled = $newDisabled;
		return true;
	} /* }}} */

	public function setParameter($newParams) { /* {{{ */
		$db = $this->db;

		$queryStr = "UPDATE `tblSchedulerTask` SET `params` =".$db->qstr(json_encode($newParams))." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_params = $newParams;
		return true;
	} /* }}} */

	public function getParameter($name = '') {
		if($name)
			return isset($this->_params[$name]) ? $this->_params[$name] : null;
		return $this->_params;
	}

	public function isDue() {
		return $this->_nextrun < date('Y-m-d H:i:s');
	}

	public function updateLastNextRun() {
		$db = $this->db;

		$lastrun = date('Y-m-d H:i:s');
		try {
			$cron = Cron\CronExpression::factory($this->_frequency);
			$nextrun = $cron->getNextRunDate()->format('Y-m-d H:i:s');
		} catch (Exception $e) {
			$nextrun = null;
		}

		$queryStr = "UPDATE `tblSchedulerTask` SET `lastrun`=".$db->qstr($lastrun).", `nextrun`=".($nextrun ? $db->qstr($nextrun) : "NULL")." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_lastrun = $lastrun;
		$this->_nextrun = $nextrun;
	}

	/**
	 * Delete task
	 *
	 * @return boolean true on success or false in case of an error
	 */
	function remove() { /* {{{ */
		$db = $this->db;

		$queryStr = "DELETE FROM `tblSchedulerTask` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		return true;
	} /* }}} */

}
