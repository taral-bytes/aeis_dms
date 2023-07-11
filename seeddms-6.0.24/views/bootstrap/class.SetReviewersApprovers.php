<?php
/**
 * Implementation of SetReviewersApprovers view
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
 * Class which outputs the html page for SetReviewersApprovers view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_SetReviewersApprovers extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$content = $this->params['version'];
		$workflowmode = $this->params['workflowmode'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];

		$overallStatus = $content->getStatus();
		$owner = $document->getOwner();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("change_assignments"));

		// Retrieve a list of all users and groups that have review / approve privileges.
		$docAccess = $document->getReadAccessList($enableadminrevapp, $enableownerrevapp);

		// Retrieve list of currently assigned reviewers and approvers, along with
		// their latest status.
		$reviewStatus = $content->getReviewStatus();
		$approvalStatus = $content->getApprovalStatus();

		// Index the review results for easy cross-reference with the Approvers List.
		$reviewIndex = array("i"=>array(), "g"=>array());
		foreach ($reviewStatus as $i=>$rs) {
			if ($rs["type"]==0) {
				$reviewIndex["i"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
			} elseif ($rs["type"]==1) {
				$reviewIndex["g"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
			}
		}

		// Index the approval results for easy cross-reference with the Approvers List.
		$approvalIndex = array("i"=>array(), "g"=>array());
		foreach ($approvalStatus as $i=>$rs) {
			if ($rs["type"]==0) {
				$approvalIndex["i"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
			} elseif ($rs["type"]==1) {
				$approvalIndex["g"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
			}
		}
?>


<form action="../op/op.SetReviewersApprovers.php" method="post" name="form1">

<?php
		if($workflowmode != 'traditional_only_approval') {
			$this->contentSubHeading(getMLText("update_reviewers"));
			$this->contentContainerStart();

		if($user->getID() != $owner->getID()) {
			$res=$owner->getMandatoryReviewers();
			if($user->isAdmin())
				$res = array();
		} else
			$res=$user->getMandatoryReviewers();

		$options = [];
		foreach ($docAccess["users"] as $usr) {
			$mandatory=false;
			foreach ($res as $r) if ($r['reviewerUserID']==$usr->getID()) $mandatory=true;
			
			if ($mandatory){
				$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, array(array('disabled', 'disabled'), array('data-subtitle', getMLText('user_is_mandatory_reviewer'))));
			} elseif (isset($reviewIndex["i"][$usr->getID()])) {
				switch ($reviewIndex["i"][$usr->getID()]["status"]) {
					case S_LOG_WAITING:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), true);
						break;
					case S_LOG_USER_REMOVED:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, array(array('data-subtitle', getMLText('user_previously_removed_from_reviewers'))));
						break;
					default:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, array(array('disabled', 'disabled')));
						break;
				}
			} else {
				if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 
				$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()));
			}
		}
			/* List all mandatory reviewers */
			$extraparams = [];
			if($res) {
				$tmp = array();
				foreach ($res as $r) {
					if($r['reviewerUserID'] > 0) {
						$u = $dms->getUser($r['reviewerUserID']);
						$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
					}
				}
				if($tmp) {
					$extraparams['field_wrap'] = ['', '<div class="mandatories"><span>'.getMLText('mandatory_reviewers').':</span> '.implode(', ', $tmp)."</div>\n"];
				}
			}
			$this->formField(
				getMLText("individuals"),
				array(
					'element'=>'select',
					'id'=>'indReviewers',
					'name'=>'indReviewers[]',
					'class'=>'chzn-select',
					'multiple'=>true,
					'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_ind_reviewers')), array('data-no_results_text', getMLText('unknown_user'))),
					'options'=>$options,
				),
				$extraparams
			);

		$options = [];
		foreach ($docAccess["groups"] as $group) {
			$optopt = [];
			$grpusers = $group->getUsers();
			if(count($grpusers) == 0)
				$optopt[] = ['disabled', 'disabled'];
			$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, $optopt);
		}
		$this->formField(
			getMLText("individuals_in_groups"),
			array(
				'element'=>'select',
				'id'=>'grpIndReviewers',
				'name'=>'grpIndReviewers[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_grp_ind_reviewers')), array('data-no_results_text', getMLText('unknown_group'))),
				'options'=>$options
			)
		);

		$options = [];
		foreach ($docAccess["groups"] as $group) {
			$grpusers = $group->getUsers();
			$mandatory=false;
			foreach ($res as $r) if ($r['reviewerGroupID']==$group->getID()) $mandatory=true;
			
			if ($mandatory) {
				$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, array(array('disabled', 'disabled'), array('data-subtitle', getMLText('group_is_mandatory_reviewer'))));
			} elseif (isset($reviewIndex["g"][$group->getID()])) {
				switch ($reviewIndex["g"][$group->getID()]["status"]) {
					case S_LOG_WAITING:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), true);
						break;
					case S_LOG_USER_REMOVED:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, array(array('data-subtitle', getMLText('group_previously_removed_from_reviewers'))));
						break;
					default:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, array(array('disabled', 'disabled')));
						break;
				}
			} else {
				$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'));
			}
		}

			/* List all mandatory groups of reviewers */
			$extraparams = [];
			if($res) {
				$tmp = array();
				foreach ($res as $r) {
					if($r['reviewerGroupID'] > 0) {
						$u = $dms->getGroup($r['reviewerGroupID']);
						$tmp[] =  htmlspecialchars($u->getName());
					}
				}
				if($tmp) {
					$extraparams['field_wrap'] = ['', '<div class="mandatories"><span>'.getMLText('mandatory_reviewergroups').':</span> '.implode(', ', $tmp)."</div>\n"];
				}
			}
			$this->formField(
				getMLText("groups"),
				array(
					'element'=>'select',
					'id'=>'grpReviewers',
					'name'=>'grpReviewers[]',
					'class'=>'chzn-select',
					'multiple'=>true,
					'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_grp_reviewers')), array('data-no_results_text', getMLText('unknown_group'))),
					'options'=>$options,
				),
				$extraparams
			);
		}

		$this->contentContainerEnd();
		$this->contentSubHeading(getMLText("update_approvers"));
		$this->contentContainerStart();

		if($user->getID() != $owner->getID()) {
			$res=$owner->getMandatoryApprovers();
			if($user->isAdmin())
				$res = array();
		} else
			$res=$user->getMandatoryApprovers();

		$options = [];
		foreach ($docAccess["users"] as $usr) {

			$mandatory=false;
			foreach ($res as $r) if ($r['approverUserID']==$usr->getID()) $mandatory=true;

			if ($mandatory){
				$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, array(array('disabled', 'disabled'), array('data-subtitle', getMLText('user_is_mandatory_approver'))));
			} elseif (isset($approvalIndex["i"][$usr->getID()])) {
			
				switch ($approvalIndex["i"][$usr->getID()]["status"]) {
					case S_LOG_WAITING:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), true);
						break;
					case S_LOG_USER_REMOVED:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, array(array('data-subtitle', getMLText('user_previously_removed_from_approvers'))));
						break;
					default:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, array(array('disabled', 'disabled')));
						break;
				}
			}
			else {
				if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 
				$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()));
			}
		}

		/* List all mandatory approvers */
		$extraparams = [];
		if($res) {
			$tmp = array();
			foreach ($res as $r) {
				if($r['approverUserID'] > 0) {
					$u = $dms->getUser($r['approverUserID']);
					$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
				}
			}
			if($tmp) {
				$extraparams['field_wrap'] = ['', '<div class="mandatories"><span>'.getMLText('mandatory_approvers').':</span> '.implode(', ', $tmp)."</div>\n"];
			}
		}
		$this->formField(
			getMLText("individuals"),
			array(
				'element'=>'select',
				'id'=>'indApprovers',
				'name'=>'indApprovers[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_ind_approvers')), array('data-no_results_text', getMLText('unknown_user'))),
				'options'=>$options,
			),
			$extraparams
		);

		$options = [];
		foreach ($docAccess["groups"] as $group) {
			$optopt = [];
			$grpusers = $group->getUsers();
			if(count($grpusers) == 0)
				$optopt[] = ['disabled', 'disabled'];
			$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, $optopt);
		}
		$this->formField(
			getMLText("individuals_in_groups"),
			array(
				'element'=>'select',
				'id'=>'grpIndApprovers',
				'name'=>'grpIndApprovers[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_grp_ind_approvers')), array('data-no_results_text', getMLText('unknown_group'))),
				'options'=>$options
			)
		);

		$options = [];
		foreach ($docAccess["groups"] as $group) {
			$grpusers = $group->getUsers();
			$mandatory=false;
			foreach ($res as $r) if ($r['approverGroupID']==$group->getID()) $mandatory=true;

			if ($mandatory) {
				$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, array(array('disabled', 'disabled'), array('data-subtitle', getMLText('group_is_mandatory_approver'))));
			} elseif (isset($approvalIndex["g"][$group->getID()])) {

				switch ($approvalIndex["g"][$group->getID()]["status"]) {
					case S_LOG_WAITING:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), true);
						break;
					case S_LOG_USER_REMOVED:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, array(array('data-subtitle', getMLText('group_previously_removed_from_approvers'))));
						break;
					default:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, array(array('disabled', 'disabled')));
						break;
				}
			}
			else {
				$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'));
			}
		}

		/* List all mandatory groups of approvers */
		$extraparams = [];
		if($res) {
			$tmp = array();
			foreach ($res as $r) {
				if($r['approverGroupID'] > 0) {
					$u = $dms->getGroup($r['approverGroupID']);
					$tmp[] =  htmlspecialchars($u->getName());
				}
			}
			if($tmp) {
				$extraparams['field_wrap'] = ['', '<div class="mandatories"><span>'.getMLText('mandatory_reviewergroups').':</span> '.implode(', ', $tmp)."</div>\n"];
			}
		}
		$this->formField(
			getMLText("groups"),
			array(
				'element'=>'select',
				'id'=>'grpApprovers',
				'name'=>'grpApprovers[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_grp_approvers')), array('data-no_results_text', getMLText('unknown_group'))),
				'options'=>$options,
			),
			$extraparams
		);
		$this->contentContainerEnd();
?>
<p>
<input type='hidden' name='documentid' value='<?php echo $document->getID() ?>'/>
<input type='hidden' name='version' value='<?php echo $content->getVersion() ?>'/>
<input type="submit" class="btn btn-primary" value="<?php printMLText("update");?>">
</p>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
