<?php
/**
 * Implementation of the workflow object in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent an workflow in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Workflow { /* {{{ */
	/**
	 * @var integer id of workflow
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var string name of the workflow
	 *
	 * @access protected
	 */
	var $_name;

	/**
	 * @var SeedDMS_Core_Workflow_State initial state of the workflow
	 *
	 * @access protected
	 */
	var $_initstate;

	/**
	 * @var data for rendering graph
	 *
	 * @access protected
	 */
	var $_layoutdata;

	/**
	 * @var SeedDMS_Core_Workflow_Transition[] list of transitions
	 *
	 * @access protected
	 */
	var $_transitions;

	/**
	 * @var SeedDMS_Core_DMS reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	/**
	 * SeedDMS_Core_Workflow constructor.
	 * @param int $id
	 * @param string $name
	 * @param SeedDMS_Core_Workflow_State $initstate
	 * @param string $layoutdata
	 */
	function __construct($id, $name, $initstate, $layoutdata) { /* {{{ */
		$this->_id = $id;
		$this->_name = $name;
		$this->_initstate = $initstate;
		$this->_layoutdata = $layoutdata;
		$this->_transitions = null;
		$this->_dms = null;
	} /* }}} */

	/**
	 * @param SeedDMS_Core_DMS $dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/**
	 * @return int
	 */
	function getID() { return $this->_id; }

	/**
	 * @return string
	 */
	function getName() { return $this->_name; }

	/**
	 * @param $newName
	 * @return bool
	 */
	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflows` SET `name` = ".$db->qstr($newName)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	/**
	 * @return SeedDMS_Core_Workflow_State
	 */
	function getInitState() { return $this->_initstate; }

	/**
	 * @param SeedDMS_Core_Workflow_State $state
	 * @return bool
	 */
	function setInitState($state) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflows` SET `initstate` = ".$state->getID()." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_initstate = $state;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getLayoutData() { return $this->_layoutdata; }

	/**
	 * @param string $layoutdata
	 */
	function setLayoutData($newdata) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflows` SET `layoutdata` = ".$db->qstr($newdata)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_layoutdata = $newdata;
		return true;
	} /* }}} */

	/**
	 * @return SeedDMS_Core_Workflow_Transition[]|bool
	 */
	function getTransitions() { /* {{{ */
		$db = $this->_dms->getDB();

		if($this->_transitions)
			return $this->_transitions;

		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `workflow`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this, $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$resArr[$i]["id"]] = $wkftransition;
		}

		$this->_transitions = $wkftransitions;

		return $this->_transitions;
	} /* }}} */

    /**
     * Get all states this workflow at some point may reach
     *
     * It basically iterates through all transistions and makes a unique
     * list of the start and end state.
     *
	 * @return array list of states
	 */
	function getStates() { /* {{{ */
		/** @noinspection PhpUnusedLocalVariableInspection */
		$db = $this->_dms->getDB();

		if(!$this->_transitions)
			$this->getTransitions();

		$states = array();
        foreach($this->_transitions as $transition) {
			if(!isset($states[$transition->getState()->getID()]))
				$states[$transition->getState()->getID()] = $transition->getState();
			if(!isset($states[$transition->getNextState()->getID()]))
				$states[$transition->getNextState()->getID()] = $transition->getNextState();
		}

		return $states;
	} /* }}} */

	/**
	 * Get the transition by its id
	 *
	 * @param integer $id id of transition
	 * @return bool|SeedDMS_Core_Workflow_Transition
	 */
	function getTransition($id) { /* {{{ */
		/** @noinspection PhpUnusedLocalVariableInspection */
		$db = $this->_dms->getDB();

		if(!$this->_transitions)
			$this->getTransitions();

		if($this->_transitions[$id])
			return $this->_transitions[$id];

		return false;
	} /* }}} */

	/**
	 * Get the transitions that can be triggered while being in the given state
	 *
	 * @param SeedDMS_Core_Workflow_State $state current workflow state
	 * @return SeedDMS_Core_Workflow_Transition[]|bool
	 */
	function getNextTransitions($state) { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$state)
			return false;

		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `workflow`=".$this->_id." AND `state`=".$state->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this, $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$i] = $wkftransition;
		}

		return $wkftransitions;
	} /* }}} */

	/**
	 * Get the transitions that lead to the given state
	 *
	 * @param SeedDMS_Core_Workflow_State $state current workflow state
	 * @return SeedDMS_Core_Workflow_Transition[]|bool
	 */
	function getPreviousTransitions($state) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `workflow`=".$this->_id." AND `nextstate`=".$state->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this, $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$i] = $wkftransition;
		}

		return $wkftransitions;
	} /* }}} */

	/**
	 * Get all transitions from one state into another state
	 *
	 * @param SeedDMS_Core_Workflow_State $state state to start from
	 * @param SeedDMS_Core_Workflow_State $nextstate state after transition
	 * @return SeedDMS_Core_Workflow_Transition[]|bool
	 */
	function getTransitionsByStates($state, $nextstate) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `workflow`=".$this->_id." AND `state`=".$state->getID()." AND `nextstate`=".$nextstate->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this, $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$i] = $wkftransition;
		}

		return $wkftransitions;
	} /* }}} */

	/**
	 * Remove a transition from a workflow
	 * Deprecated! User SeedDMS_Core_Workflow_Transition::remove() instead.
	 *
	 * @param SeedDMS_Core_Workflow_Transition $transition
	 * @return boolean true if no error occured, otherwise false
	 */
	function removeTransition($transition) { /* {{{ */
		return $transition->remove();
	} /* }}} */

	/**
	 * Add new transition to workflow
	 *
	 * @param SeedDMS_Core_Workflow_State $state
	 * @param SeedDMS_Core_Workflow_Action $action
	 * @param SeedDMS_Core_Workflow_State $nextstate
	 * @param SeedDMS_Core_User[] $users
	 * @param SeedDMS_Core_Group[] $groups
	 * @return SeedDMS_Core_Workflow_Transition|bool instance of new transition
	 */
	function addTransition($state, $action, $nextstate, $users, $groups) { /* {{{ */
		$db = $this->_dms->getDB();
		
		$db->startTransaction();
		$queryStr = "INSERT INTO `tblWorkflowTransitions` (`workflow`, `state`, `action`, `nextstate`) VALUES (".$this->_id.", ".$state->getID().", ".$action->getID().", ".$nextstate->getID().")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* force reloading all transitions otherwise getTransition() will fail if two
		 * transitions are added in a row, without reloading the workflow
		 */
		$this->_transitions = array();
		$transition = $this->getTransition($db->getInsertID('tblWorkflowTransitions'));

		foreach($users as $user) {
			$queryStr = "INSERT INTO `tblWorkflowTransitionUsers` (`transition`, `userid`) VALUES (".$transition->getID().", ".$user->getID().")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		foreach($groups as $group) {
			$queryStr = "INSERT INTO `tblWorkflowTransitionGroups` (`transition`, `groupid`, `minusers`) VALUES (".$transition->getID().", ".$group->getID().", 1)";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();
		return $transition;
	} /* }}} */

	/**
	 * Check if workflow is currently used by any document
	 *
	 * @return boolean true if workflow is used, otherwise false
	 */
	function isUsed() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM `tblWorkflowDocumentContent` WHERE `workflow`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* }}} */

	/**
	 * @param SeedDMS_Core_Workflow_State[] $laststates
	 * @return SeedDMS_Core_Workflow_State[]|bool
	 */
	private function penetrate($laststates) {
		$state = end($laststates);
		$transitions = $this->getNextTransitions($state);
		foreach($transitions as $transition) {
			$nextstate = $transition->getNextState();
			/* Check if nextstate is already in list of previous states */
			foreach($laststates as $laststate) {
				if($laststate->getID() == $nextstate->getID())
					return array_merge($laststates, array($nextstate));
			}
			if($ret = $this->penetrate(array_merge($laststates, array($nextstate))))
				return $ret;
		}
		return false;
	}

	/**
	 * Check if workflow contains cycles
	 *
	 * @return boolean list of states if workflow contains cycles, otherwise false
	 */
	function checkForCycles() { /* {{{ */
		/** @noinspection PhpUnusedLocalVariableInspection */
		$db = $this->_dms->getDB();
		
		$initstate = $this->getInitState();

		return $this->penetrate(array($initstate));
	} /* }}} */

	/**
	 * Remove the workflow and all its transitions
	 * Do not remove actions and states of the workflow
	 *
	 * @return boolean true on success or false in case of an error
	 *         false is also returned if the workflow is currently in use
	 */
	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		if($this->isUsed())
			return false;

		$db->startTransaction();

		$queryStr = "DELETE FROM `tblWorkflowTransitions` WHERE `workflow` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblWorkflowMandatoryWorkflow` WHERE `workflow` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete workflow itself
		$queryStr = "DELETE FROM `tblWorkflows` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		return true;
	} /* }}} */

} /* }}} */

/**
 * Class to represent a workflow state in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Workflow_State { /* {{{ */
	/**
	 * @var integer id of workflow state
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var string name of the workflow state
	 *
	 * @access protected
	 */
	var $_name;

	/**
	 * @var int maximum of seconds allowed in this state
	 *
	 * @access protected
	 */
	var $_maxtime;

	/**
	 * @var int maximum of seconds allowed in this state
	 *
	 * @access protected
	 */
	var $_precondfunc;

	/**
	 * @var int matching documentstatus when this state is reached
	 *
	 * @access protected
	 */
	var $_documentstatus;

	/**
	 * @var SeedDMS_Core_DMS reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	/**
	 * SeedDMS_Core_Workflow_State constructor.
	 * @param $id
	 * @param $name
	 * @param $maxtime
	 * @param $precondfunc
	 * @param $documentstatus
	 */
	function __construct($id, $name, $maxtime, $precondfunc, $documentstatus) {
		$this->_id = $id;
		$this->_name = $name;
		$this->_maxtime = $maxtime;
		$this->_precondfunc = $precondfunc;
		$this->_documentstatus = $documentstatus;
		$this->_dms = null;
	}

	/**
	 * @param $dms
	 */
	function setDMS($dms) {
		$this->_dms = $dms;
	}

	/**
	 * @return int
	 */
	function getID() { return $this->_id; }

	/**
	 * @return string
	 */
	function getName() { return $this->_name; }

	/**
	 * @param string $newName
	 * @return bool
	 */
	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowStates` SET `name` = ".$db->qstr($newName)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	/**
	 * @return int maximum
	 */
	function getMaxTime() { return $this->_maxtime; }

	/**
	 * @param $maxtime
	 * @return bool
	 */
	function setMaxTime($maxtime) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowStates` SET `maxtime` = ".intval($maxtime)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_maxtime = $maxtime;
		return true;
	} /* }}} */

	/**
	 * @return int maximum
	 */
	function getPreCondFunc() { return $this->_precondfunc; }

	/**
	 * @param $precondfunc
	 * @return bool
	 */
	function setPreCondFunc($precondfunc) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowStates` SET `precondfunc` = ".$db->qstr($precondfunc)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		/** @noinspection PhpUndefinedVariableInspection */
		$this->_maxtime = $maxtime; /* @todo fix me */
		return true;
	} /* }}} */

	/**
	 * Get the document status which is set when this state is reached
	 *
	 * The document status uses the define states S_REJECTED and S_RELEASED
	 * Only those two states will update the document status
	 *
	 * @return integer document status
	 */
	function getDocumentStatus() { return $this->_documentstatus; }

	/**
	 * @param $docstatus
	 * @return bool
	 */
	function setDocumentStatus($docstatus) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowStates` SET `documentstatus` = ".intval($docstatus)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_documentstatus = $docstatus;
		return true;
	} /* }}} */

	/**
	 * Check if workflow state is currently used by any workflow transition
	 *
	 * @return boolean true if workflow is used, otherwise false
	 */
	function isUsed() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `state`=".$this->_id. " OR `nextstate`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* }}} */

	/**
	 * Return workflow transitions the status is being used in
	 *
	 * @return SeedDMS_Core_Workflow_Transition[]|boolean array of workflow transitions or false in case of an error
	 */
	function getTransitions() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `state`=".$this->_id. " OR `nextstate`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this->_dms->getWorkflow($resArr[$i]["workflow"]), $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$resArr[$i]["id"]] = $wkftransition;
		}

		return $wkftransitions;
	} /* }}} */

	/**
	 * Remove the workflow state
	 *
	 * @return boolean true on success or false in case of an error
	 *         false is also returned if the workflow state is currently in use
	 */
	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		if($this->isUsed())
			return false;

		$db->startTransaction();

		// Delete workflow state itself
		$queryStr = "DELETE FROM `tblWorkflowStates` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		return true;
	} /* }}} */

} /* }}} */

/**
 * Class to represent a workflow action in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Workflow_Action { /* {{{ */
	/**
	 * @var integer id of workflow action
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var string name of the workflow action
	 *
	 * @access protected
	 */
	var $_name;

	/**
	 * @var SeedDMS_Core_DMS reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	/**
	 * SeedDMS_Core_Workflow_Action constructor.
	 * @param $id
	 * @param $name
	 */
	function __construct($id, $name) {
		$this->_id = $id;
		$this->_name = $name;
		$this->_dms = null;
	}

	/**
	 * @param $dms
	 */
	function setDMS($dms) {
		$this->_dms = $dms;
	}

	/**
	 * @return int
	 */
	function getID() { return $this->_id; }

	/**
	 * @return string name
	 */
	function getName() { return $this->_name; }

	/**
	 * @param $newName
	 * @return bool
	 */
	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowActions` SET `name` = ".$db->qstr($newName)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	/**
	 * Check if workflow action is currently used by any workflow transition
	 *
	 * @return boolean true if workflow action is used, otherwise false
	 */
	function isUsed() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `action`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* }}} */

	/**
	 * Return workflow transitions the action is being used in
	 *
	 * @return SeedDMS_Core_Workflow_Transition[]|boolean array of workflow transitions or false in case of an error
	 */
	function getTransitions() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `action`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this->_dms->getWorkflow($resArr[$i]["workflow"]), $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$resArr[$i]["id"]] = $wkftransition;
		}

		return $wkftransitions;
	} /* }}} */

	/**
	 * Remove the workflow action
	 *
	 * @return boolean true on success or false in case of an error
	 *         false is also returned if the workflow action is currently in use
	 */
	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		if($this->isUsed())
			return false;

		$db->startTransaction();

		// Delete workflow state itself
		$queryStr = "DELETE FROM `tblWorkflowActions` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		return true;
	} /* }}} */

} /* }}} */

/**
 * Class to represent a workflow transition in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Workflow_Transition { /* {{{ */
	/**
	 * @var integer id of workflow transition
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var SeedDMS_Core_Workflow workflow this transition belongs to
	 *
	 * @access protected
	 */
	var $_workflow;

	/**
	 * @var SeedDMS_Core_Workflow_State of the workflow transition
	 *
	 * @access protected
	 */
	var $_state;

	/**
	 * @var SeedDMS_Core_Workflow_State next state of the workflow transition
	 *
	 * @access protected
	 */
	var $_nextstate;

	/**
	 * @var SeedDMS_Core_Workflow_Action of the workflow transition
	 *
	 * @access protected
	 */
	var $_action;

	/**
	 * @var integer maximum of seconds allowed until this transition must be triggered
	 *
	 * @access protected
	 */
	var $_maxtime;

	/**
	 * @var SeedDMS_Core_User[] of users allowed to trigger this transaction
	 *
	 * @access protected
	 */
	var $_users;

	/**
	 * @var SeedDMS_Core_Group[] of groups allowed to trigger this transaction
	 *
	 * @access protected
	 */
	var $_groups;

	/**
	 * @var SeedDMS_Core_DMS reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	/**
	 * SeedDMS_Core_Workflow_Transition constructor.
	 * @param $id
	 * @param $workflow
	 * @param $state
	 * @param $action
	 * @param $nextstate
	 * @param $maxtime
	 */
	function __construct($id, $workflow, $state, $action, $nextstate, $maxtime) {
		$this->_id = $id;
		$this->_workflow = $workflow;
		$this->_state = $state;
		$this->_action = $action;
		$this->_nextstate = $nextstate;
		$this->_maxtime = $maxtime;
		$this->_dms = null;
	}

	/**
	 * @param $dms
	 */
	function setDMS($dms) {
		$this->_dms = $dms;
	}

	/**
	 * @return int
	 */
	function getID() { return $this->_id; }

	/**
	 * @return SeedDMS_Core_Workflow
	 */
	function getWorkflow() { return $this->_workflow; }

	/**
	 * @param SeedDMS_Core_Workflow $newWorkflow
	 * @return bool
	 */
	function setWorkflow($newWorkflow) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowTransitions` SET `workflow` = ".$newWorkflow->getID()." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_workflow = $newWorkflow;
		return true;
	} /* }}} */


	/**
	 * @return SeedDMS_Core_Workflow_State
	 */
	function getState() { return $this->_state; }

	/**
	 * @param SeedDMS_Core_Workflow_State $newState
	 * @return bool
	 */
	function setState($newState) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowTransitions` SET `state` = ".$newState->getID()." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_state = $newState;
		return true;
	} /* }}} */

	/**
	 * @return SeedDMS_Core_Workflow_State
	 */
	function getNextState() { return $this->_nextstate; }

	/**
	 * @param SeedDMS_Core_Workflow_State $newNextState
	 * @return bool
	 */
	function setNextState($newNextState) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowTransitions` SET `nextstate` = ".$newNextState->getID()." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_nextstate = $newNextState;
		return true;
	} /* }}} */

	/**
	 * @return SeedDMS_Core_Workflow_Action
	 */
	function getAction() { return $this->_action; }

	/**
	 * @param SeedDMS_Core_Workflow_Action $newAction
	 * @return bool
	 */
	function setAction($newAction) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowTransitions` SET `action` = ".$newAction->getID()." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_action = $newAction;
		return true;
	} /* }}} */

	/**
	 * @return int
	 */
	function getMaxTime() { return $this->_maxtime; }

	/**
	 * @param $maxtime
	 * @return bool
	 */
	function setMaxTime($maxtime) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblWorkflowTransitions` SET `maxtime` = ".intval($maxtime)." WHERE `id` = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_maxtime = $maxtime;
		return true;
	} /* }}} */

	/**
	 * Get all users allowed to trigger this transition
	 *
	 * @return SeedDMS_Core_User[]|bool list of users
	 */
	function getUsers() { /* {{{ */
		$db = $this->_dms->getDB();

		if($this->_users)
			return $this->_users;

		$queryStr = "SELECT * FROM `tblWorkflowTransitionUsers` WHERE `transition`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$users = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$user = new SeedDMS_Core_Workflow_Transition_User($resArr[$i]['id'], $this, $this->_dms->getUser($resArr[$i]['userid']));
			$user->setDMS($this->_dms);
			$users[$i] = $user;
		}

		$this->_users = $users;

		return $this->_users;
	} /* }}} */

	/**
	 * Get all users allowed to trigger this transition
	 *
	 * @return SeedDMS_Core_Group[]|bool list of users
	 */
	function getGroups() { /* {{{ */
		$db = $this->_dms->getDB();

		if($this->_groups)
			return $this->_groups;

		$queryStr = "SELECT * FROM `tblWorkflowTransitionGroups` WHERE `transition`=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$groups = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$group = new SeedDMS_Core_Workflow_Transition_Group($resArr[$i]['id'], $this, $this->_dms->getGroup($resArr[$i]['groupid']), $resArr[$i]['minusers']);
			$group->setDMS($this->_dms);
			$groups[$i] = $group;
		}

		$this->_groups = $groups;

		return $this->_groups;
	} /* }}} */

	/**
	 * Remove the workflow transition
	 *
	 * @return boolean true on success or false in case of an error
	 *         false is also returned if the workflow action is currently in use
	 */
	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		$db->startTransaction();

		// Delete workflow transition itself
		$queryStr = "DELETE FROM `tblWorkflowTransitions` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		return true;
	} /* }}} */

} /* }}} */

/**
 * Class to represent a user allowed to trigger a workflow transition
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Workflow_Transition_User { /* {{{ */
	/**
	 * @var integer id of workflow transition
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var object reference to the transtion this user belongs to
	 *
	 * @access protected
	 */
	var $_transition;

	/**
	 * @var object user allowed to trigger a transition
	 *
	 * @access protected
	 */
	var $_user;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 _Core_Workflow_Transition_Group
	 * @access protected
	 */
	var $_dms;

	/**
	 * SeedDMS_Core_Workflow_Transition_User constructor.
	 * @param $id
	 * @param $transition
	 * @param $user
	 */
	function __construct($id, $transition, $user) {
		$this->_id = $id;
		$this->_transition = $transition;
		$this->_user = $user;
	}

	/**
	 * @param $dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/**
	 * Get the transtion itself
	 *
	 * @return object group
	 */
	function getTransition() { /* {{{ */
		return $this->_transition;
	} /* }}} */

	/**
	 * Get the user who is allowed to trigger the transition
	 *
	 * @return object user
	 */
	function getUser() { /* {{{ */
		return $this->_user;
	} /* }}} */
} /* }}} */

/**
 * Class to represent a group allowed to trigger a workflow transition
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Workflow_Transition_Group { /* {{{ */
	/**
	 * @var integer id of workflow transition
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var object reference to the transtion this group belongs to
	 *
	 * @access protected
	 */
	var $_transition;
	
	/**
	 * @var integer number of users how must trigger the transition
	 *
	 * @access protected
	 */
	var $_numOfUsers;

	/**
	 * @var object group of users
	 *
	 * @access protected
	 */
	var $_group;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	/**
	 * SeedDMS_Core_Workflow_Transition_Group constructor.
	 * @param $id
	 * @param $transition
	 * @param $group
	 * @param $numOfUsers
	 */
	function __construct($id, $transition, $group, $numOfUsers) { /* {{{ */
		$this->_id = $id;
		$this->_transition = $transition;
		$this->_group = $group;
		$this->_numOfUsers = $numOfUsers;
	} /* }}} */

	/**
	 * @param $dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/**
	 * Get the transtion itself
	 *
	 * @return object group
	 */
	function getTransition() { /* {{{ */
		return $this->_transition;
	} /* }}} */

	/**
	 * Get the group whose user are allowed to trigger the transition
	 *
	 * @return object group
	 */
	function getGroup() { /* {{{ */
		return $this->_group;
	} /* }}} */

	/**
	 * Returns the number of users of this group needed to trigger the transition
	 *
	 * @return integer number of users
	 */
	function getNumOfUsers() { /* {{{ */
		return $this->_numOfUsers;
	} /* }}} */

} /* }}} */

/**
 * Class to represent a group allowed to trigger a workflow transition
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Workflow_Log { /* {{{ */
	/**
	 * @var integer id of workflow log
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var object document this log entry belongs to
	 *
	 * @access protected
	 */
	var $_document;

	/**
	 * @var integer version of document this log entry belongs to
	 *
	 * @access protected
	 */
	var $_version;

	/**
	 * @var object workflow
	 *
	 * @access protected
	 */
	var $_workflow;

	/**
	 * @var object user initiating this log entry
	 *
	 * @access protected
	 */
	var $_user;

	/**
	 * @var object transition
	 *
	 * @access protected
	 */
	var $_transition;

	/**
	 * @var string date
	 *
	 * @access protected
	 */
	var $_date;

	/**
	 * @var string comment
	 *
	 * @access protected
	 */
	var $_comment;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	/**
	 * SeedDMS_Core_Workflow_Log constructor.
	 * @param $id
	 * @param $document
	 * @param $version
	 * @param $workflow
	 * @param $user
	 * @param $transition
	 * @param $date
	 * @param $comment
	 */
	function __construct($id, $document, $version, $workflow, $user, $transition, $date, $comment) {
		$this->_id = $id;
		$this->_document = $document;
		$this->_version = $version;
		$this->_workflow = $workflow;
		$this->_user = $user;
		$this->_transition = $transition;
		$this->_date = $date;
		$this->_comment = $comment;
		$this->_dms = null;
	}

	/**
	 * @param $dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/**
	 * @return object
	 */
	function getTransition() { /* {{{ */
		return $this->_transition;
	} /* }}} */

	/**
	 * @return object
	 */
	function getWorkflow() { /* {{{ */
		return $this->_workflow;
	} /* }}} */

	/**
	 * @return object
	 */
	function getUser() { /* {{{ */
		return $this->_user;
	} /* }}} */

	/**
	 * @return string
	 */
	function getComment() { /* {{{ */
		return $this->_comment;
	} /* }}} */

	/**
	 * @return string
	 */
	function getDate() { /* {{{ */
		return $this->_date;
	} /* }}} */

} /* }}} */
?>
