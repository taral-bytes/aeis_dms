<?php
/**
 * Implementation of CheckInDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2015 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for CheckInDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2015 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_CheckInDocument extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
		$this->printSelectPresetButtonJs();
		$this->printInputPresetButtonJs();
		$this->printCheckboxPresetButtonJs();
?>
$(document).ready(function() {
	$("#form1").validate({
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
			keywords: "<?php printMLText("js_no_keywords");?>"
		}
	});
	$("#form2").validate({
		messages: {
			confirm: "<?php printMLText("js_confirm_cancel_checkout");?>",
		}
	});
	$('#presetexpdate').on('change', function(ev){
		if($(this).val() == 'date')
			$('#control_expdate').show();
		else
			$('#control_expdate').hide();
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$strictformcheck = $this->params['strictformcheck'];
		$nodocumentformfields = $this->params['nodocumentformfields'];
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];
		$enablereceiptworkflow = $this->params['enablereceiptworkflow'];
		$enableselfreceipt = $this->params['enableselfreceipt'];
		$workflowmode = $this->params['workflowmode'];
		$presetexpiration = $this->params['presetexpiration'];
		$documentid = $document->getId();

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/additional-methods.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);

		if ($document->isLocked()) {

			$lockingUser = $document->getLockingUser();

			print "<div class=\"alert alert-warning\">";
			
			printMLText("update_locked_msg", array("username" => htmlspecialchars($lockingUser->getFullName()), "email" => $lockingUser->getEmail()));
			
			if ($lockingUser->getID() == $user->getID())
				printMLText("unlock_cause_locking_user");
			else if ($document->getAccessMode($user) == M_ALL)
				printMLText("unlock_cause_access_mode_all");
			else
			{
				printMLText("no_update_cause_locked");
				print "</div>";
				$this->contentEnd();
				$this->htmlEndPage();
				exit;
			}

			print "</div>";
		}

		$checkoutinfo = $document->getCheckOutInfo();
		if(!$checkoutinfo) {
			$this->errorMsg(getMLText('error_occured'));
			$this->contentEnd();
			$this->htmlEndPage();
			exit;
		}
		$info = $checkoutinfo[0];
		if($user->getID() != $info['userID'] && $document->getAccessMode($user) < M_ALL) {
			$this->errorMsg(getMLText('access_denied'));
			$this->contentEnd();
			$this->htmlEndPage();
			exit;
		}

		if ($checkoutstatus = $document->checkOutStatus()) {
			switch($checkoutstatus) {
			case 1:
				$this->warningMsg(getMLText("checkedout_file_has_disappeared"));
				break;
			case 2:
				$this->warningMsg(getMLText("checkedout_file_has_different_version"));
				break;
			case 3:
				$this->warningMsg(getMLText("checkedout_file_is_unchanged"));
				break;
			}
		}

		$this->rowStart();
		if($checkoutstatus == 0) {
			$this->columnStart(6);
			$this->contentHeading(getMLText("checkin_document"));

		$latestContent = $document->getLatestContent();
		$reviewStatus = $latestContent->getReviewStatus();
		$receiptStatus = $latestContent->getReceiptStatus();
		$approvalStatus = $latestContent->getApprovalStatus();
		if($workflowmode == 'advanced') {
			if($status = $latestContent->getStatus()) {
				if($status["status"] == S_IN_WORKFLOW) {
					$this->warningMsg("The current version of this document is in a workflow. This will be interrupted and cannot be completed if you upload a new version.");
				}
			}
		}

?>

<form class="form-horizontal" action="../op/op.CheckInDocument.php" method="post" id="form1" name="form1">
	<?php echo createHiddenFieldWithKey('checkindocument'); ?>
	<input type="hidden" name="documentid" value="<?php print $document->getID(); ?>">
<?php	
		$this->contentContainerStart();
		if(!$nodocumentformfields || !in_array('version_comment', $nodocumentformfields)) {
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'name'=>'comment',
				'rows'=>4,
				'cols'=>80
			)
		);
		}
		if(!$nodocumentformfields || !in_array('expires', $nodocumentformfields)) {
		if($presetexpiration) {
			if(!($expts = strtotime($presetexpiration)))
				$expts = false;
		} else {
			$expts = false;
		}
		$options = array();
		$options[] = array('never', getMLText('does_not_expire'));
		$options[] = array('date', getMLText('expire_by_date'), $expts);
		$options[] = array('1w', getMLText('expire_in_1w'));
		$options[] = array('1m', getMLText('expire_in_1m'));
		$options[] = array('1y', getMLText('expire_in_1y'));
		$options[] = array('2y', getMLText('expire_in_2y'));
		$this->formField(
			getMLText("preset_expires"),
			array(
				'element'=>'select',
				'id'=>'presetexpdate',
				'name'=>'presetexpdate',
				'options'=>$options
			)
		);
		$this->formField(
			getMLText("expires"),
			$this->getDateChooser(($expts ? getReadableDate($expts) : ''), "expdate", $this->params['session']->getLanguage())
		);
		}
		$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_documentcontent, SeedDMS_Core_AttributeDefinition::objtype_all));
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$arr = $this->callHook('editDocumentContentAttribute', $document, $attrdef);
				if(is_array($arr)) {
					if($arr)
						$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
				} elseif(is_string($arr)) {
					echo $arr;
				} else {
					$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, $document->getAttribute($attrdef), 'attributes_version'));
				}
			}
		}
		$arrs = $this->callHook('addDocumentContentAttributes', $document);
		if(is_array($arrs)) {
			foreach($arrs as $arr) {
				$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
			}
		} elseif(is_string($arrs)) {
			echo $arrs;
		}

		$docAccess = $document->getReadAccessList($enableadminrevapp, $enableownerrevapp);
		if($workflowmode == 'advanced') {
			$mandatoryworkflows = $user->getMandatoryWorkflows();
			if($mandatoryworkflows) {
				if(count($mandatoryworkflows) == 1) {
					$this->formField(
						getMLText("workflow"),
						htmlspecialchars($mandatoryworkflows[0]->getName()).'<input type="hidden" name="workflow" value="'.$mandatoryworkflows[0]->getID().'">'
					);
				} else {
					$options = array();
					$curworkflow = $latestContent->getWorkflow();
					foreach ($mandatoryworkflows as $workflow) {
						$options[] = array($workflow->getID(), htmlspecialchars($workflow->getName()), $curworkflow && $curworkflow->getID() == $workflow->getID());
					}
					$this->formField(
						getMLText("workflow"),
						array(
							'element'=>'select',
							'id'=>'workflow',
							'name'=>'workflow',
							'class'=>'chzn-select',
							'attributes'=>array(array('data-placeholder', getMLText('select_workflow'))),
							'options'=>$options
						)
					);
				}
			} else {
				$options = array();
				$options[] = array('', '');
				$workflows=$dms->getAllWorkflows();
				foreach ($workflows as $workflow) {
					$options[] = array($workflow->getID(), htmlspecialchars($workflow->getName()));
				}
				$this->formField(
					getMLText("workflow"),
					array(
						'element'=>'select',
						'id'=>'workflow',
						'name'=>'workflow',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_workflow'))),
						'options'=>$options
					)
				);
			}
			$this->warningMsg(getMLText("add_doc_workflow_warning"));
		} elseif($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			if($workflowmode == 'traditional') {
				$this->contentSubHeading(getMLText("assign_reviewers"));
				$res=$user->getMandatoryReviewers();
				$options = array();
				foreach ($docAccess["users"] as $usr) {
					if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 
					$mandatory=false;
					foreach ($res as $r) if ($r['reviewerUserID']==$usr->getID()) $mandatory=true;

					$option = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()), null);
					if ($mandatory) $option[] = array(array('disabled', 'disabled'));
					$options[] = $option;
				}
				$tmp = array();
				foreach($reviewStatus as $r) {
					if($r['type'] == 0) {
						if($res) {
							$mandatory=false;
							foreach ($res as $rr)
								if ($rr['reviewerUserID']==$r['required']) {
									$mandatory=true;
								}
							if(!$mandatory)
								$tmp[] = $r['required'];
						} else {
							$tmp[] = $r['required'];
						}
					}
				}
				$fieldwrap = array();
				if($tmp) {
					$fieldwrap = array('', $this->getSelectPresetButtonHtml("IndReviewers", $tmp));
				}
				/* List all mandatory reviewers */
				if($res) {
					$tmp = array();
					foreach ($res as $r) {
						if($r['reviewerUserID'] > 0) {
							$u = $dms->getUser($r['reviewerUserID']);
							$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
						}
					}
					if($tmp) {
						if(isset($fieldwrap[1]))
							$fieldwrap[1] .= '<div class="mandatories"><span>'.getMLText('mandatory_reviewers').':</span> '.implode(', ', $tmp)."</div>\n";
						else
							$fieldwrap[1] = '<div class="mandatories"><span>'.getMLText('mandatory_reviewers').':</span> '.implode(', ', $tmp)."</div>\n";
					}
				}

				$this->formField(
					getMLText("individuals"),
					array(
						'element'=>'select',
						'name'=>'indReviewers[]',
						'id'=>'IndReviewers',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_ind_reviewers'))),
						'multiple'=>true,
						'options'=>$options
					),
					array('field_wrap'=>$fieldwrap)
				);

				/* Check for mandatory reviewer without access */
				foreach($res as $r) {
					if($r['reviewerUserID']) {
						$hasAccess = false;
						foreach ($docAccess["users"] as $usr) {
							if ($r['reviewerUserID']==$usr->getID())
								$hasAccess = true;
						}
						if(!$hasAccess) {
							$noAccessUser = $dms->getUser($r['reviewerUserID']);
							$this->warningMsg(getMLText("mandatory_reviewer_no_access", array('user'=>htmlspecialchars($noAccessUser->getFullName()." (".$noAccessUser->getLogin().")"))));
						}
					}
				}

				$options = array();
				foreach ($docAccess["groups"] as $grp) {
					$options[] = array($grp->getID(), htmlspecialchars($grp->getName()));
				}
				$this->formField(
					getMLText("individuals_in_groups"),
					array(
						'element'=>'select',
						'name'=>'grpIndReviewers[]',
						'id'=>'GrpIndReviewers',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_grp_ind_reviewers'))),
						'multiple'=>true,
						'options'=>$options
					)
				);

				$options = array();
				foreach ($docAccess["groups"] as $grp) {
				
					$mandatory=false;
					foreach ($res as $r) if ($r['reviewerGroupID']==$grp->getID()) $mandatory=true;	

					$option = array($grp->getID(), htmlspecialchars($grp->getName()), null);
					if ($mandatory || !$grp->getUsers()) $option[] = array(array('disabled', 'disabled'));
					$options[] = $option;
				}
				$tmp = array();
				foreach($reviewStatus as $r) {
					if($r['type'] == 1) {
						if($res) {
							$mandatory=false;
							foreach ($res as $rr)
								if ($rr['reviewerGroupID']==$r['required']) {
									$mandatory=true;
								}
							if(!$mandatory)
								$tmp[] = $r['required'];
						} else {
							$tmp[] = $r['required'];
						}
					}
				}
				$fieldwrap = array('', '');
				if($tmp) {
					$fieldwrap = array('', $this->getSelectPresetButtonHtml("GrpReviewers", $tmp));
				}
				/* List all mandatory groups of reviewers */
				if($res) {
					$tmp = array();
					foreach ($res as $r) {
						if($r['reviewerGroupID'] > 0) {
							$u = $dms->getGroup($r['reviewerGroupID']);
							$tmp[] =  htmlspecialchars($u->getName());
						}
					}
					if($tmp) {
						$fieldwrap[1] .= '<div class="mandatories"><span>'.getMLText('mandatory_reviewergroups').':</span> '.implode(', ', $tmp)."</div>\n";
					}
				}
				$this->formField(
					getMLText("groups"),
					array(
						'element'=>'select',
						'name'=>'grpReviewers[]',
						'id'=>'GrpReviewers',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_grp_reviewers'))),
						'multiple'=>true,
						'options'=>$options
					),
					array('field_wrap'=>$fieldwrap)
				);

				/* Check for mandatory reviewer group without access */
				foreach($res as $r) {
					if ($r['reviewerGroupID']) {
						$hasAccess = false;
						foreach ($docAccess["groups"] as $grp) {
							if ($r['reviewerGroupID']==$grp->getID())
								$hasAccess = true;
						}
						if(!$hasAccess) {
							$noAccessGroup = $dms->getGroup($r['reviewerGroupID']);
							$this->warningMsg(getMLText("mandatory_reviewergroup_no_access", array('group'=>htmlspecialchars($noAccessGroup->getName()))));
						}
					}
				}
			}

			$this->contentSubHeading(getMLText("assign_approvers"));
			$options = array();
			$res=$user->getMandatoryApprovers();
			foreach ($docAccess["users"] as $usr) {
				if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 

				$mandatory=false;
				foreach ($res as $r) if ($r['approverUserID']==$usr->getID()) $mandatory=true;
				
				$option = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()), null);
				if ($mandatory) $option[] = array(array('disabled', 'disabled'));
				$options[] = $option;
			}
			$tmp = array();
			foreach($approvalStatus as $r) {
				if($r['type'] == 0) {
					if($res) {
						$mandatory=false;
						foreach ($res as $rr)
							if ($rr['approverUserID']==$r['required']) {
								$mandatory=true;
							}
						if(!$mandatory)
							$tmp[] = $r['required'];
					} else {
						$tmp[] = $r['required'];
					}
				}
			}
			$fieldwrap = array();
			if($tmp) {
				$fieldwrap = array('', $this->getSelectPresetButtonHtml("IndApprovers", $tmp));
			}
			/* List all mandatory approvers */
			if($res) {
				$tmp = array();
				foreach ($res as $r) {
					if($r['approverUserID'] > 0) {
						$u = $dms->getUser($r['approverUserID']);
						$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
					}
				}
				if($tmp) {
					$fieldwrap[1] .= '<div class="mandatories"><span>'.getMLText('mandatory_approvers').':</span> '.implode(', ', $tmp)."</div>\n";
				}
			}

			$this->formField(
				getMLText("individuals"),
				array(
					'element'=>'select',
					'name'=>'indApprovers[]',
					'id'=>'IndApprovers',
					'class'=>'chzn-select',
					'attributes'=>array(array('data-placeholder', getMLText('select_ind_approvers'))),
					'multiple'=>true,
					'options'=>$options
				),
				array('field_wrap'=>$fieldwrap)
			);

				/* Check for mandatory approvers without access */
				foreach($res as $r) {
					if($r['approverUserID']) {
						$hasAccess = false;
						foreach ($docAccess["users"] as $usr) {
							if ($r['approverUserID']==$usr->getID())
								$hasAccess = true;
						}
						if(!$hasAccess) {
							$noAccessUser = $dms->getUser($r['approverUserID']);
							$this->warningMsg(getMLText("mandatory_approver_no_access", array('user'=>htmlspecialchars($noAccessUser->getFullName()." (".$noAccessUser->getLogin().")"))));
						}
					}
				}

				$options = array();
				foreach ($docAccess["groups"] as $grp) {
					$options[] = array($grp->getID(), htmlspecialchars($grp->getName()));
				}
				$this->formField(
					getMLText("individuals_in_groups"),
					array(
						'element'=>'select',
						'name'=>'grpIndApprovers[]',
						'id'=>'GrpIndApprovers',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_grp_ind_approvers'))),
						'multiple'=>true,
						'options'=>$options
					)
				);

				$options = array();
				foreach ($docAccess["groups"] as $grp) {
				
					$mandatory=false;
					foreach ($res as $r) if ($r['approverGroupID']==$grp->getID()) $mandatory=true;	

					$option = array($grp->getID(), htmlspecialchars($grp->getName()), null);
					if ($mandatory || !$grp->getUsers()) $option[] = array(array('disabled', 'disabled'));

					$options[] = $option;
				}
				$tmp = array();
				foreach($approvalStatus as $r) {
					if($r['type'] == 1) {
						if($res) {
							$mandatory=false;
							foreach ($res as $rr)
								if ($rr['approverGroupID']==$r['required']) {
									$mandatory=true;
								}
							if(!$mandatory)
								$tmp[] = $r['required'];
						} else {
							$tmp[] = $r['required'];
						}
					}
				}
				$fieldwrap = array('', '');
				if($tmp) {
					$fieldwrap = array('', $this->getSelectPresetButtonHtml("GrpApprovers", $tmp));
				}
				/* List all mandatory groups of approvers */
				if($res) {
					$tmp = array();
					foreach ($res as $r) {
						if($r['approverGroupID'] > 0) {
							$u = $dms->getGroup($r['approverGroupID']);
							$tmp[] =  htmlspecialchars($u->getName());
						}
					}
					if($tmp) {
						$fieldwrap[1] .= '<div class="mandatories"><span>'.getMLText('mandatory_approvergroups').':</span> '.implode(', ', $tmp)."</div>\n";
					}
				}

				$this->formField(
					getMLText("groups"),
					array(
						'element'=>'select',
						'name'=>'grpApprovers[]',
						'id'=>'GrpApprovers',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_grp_approvers'))),
						'multiple'=>true,
						'options'=>$options
					),
					array('field_wrap'=>$fieldwrap)
				);

				/* Check for mandatory approver groups without access */
				foreach($res as $r) {
					if ($r['approverGroupID']) {
						$hasAccess = false;
						foreach ($docAccess["groups"] as $grp) {
							if ($r['approverGroupID']==$grp->getID())
								$hasAccess = true;
						}
						if(!$hasAccess) {
							$noAccessGroup = $dms->getGroup($r['approverGroupID']);
							$this->warningMsg(getMLText("mandatory_approvergroup_no_access", array('group'=>htmlspecialchars($noAccessGroup->getName()))));
						}
					}
				}
			$this->warningMsg(getMLText("add_doc_reviewer_approver_warning"));
		}
		if($enablereceiptworkflow) {
			$options = array();
			foreach ($docAccess["users"] as $usr) {
				if (!$enableselfreceipt && $usr->getID()==$user->getID()) continue; 
				$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()));
			}
			$tmp = array();
			foreach($receiptStatus as $r) {
				if($r['type'] == 0) {
					$tmp[] = $r['required'];
				}
			}
			$fieldwrap = array();
			if($tmp) {
				$fieldwrap = array('', $this->getSelectPresetButtonHtml("IndRecipient", $tmp));
			}
			$this->formField(
				getMLText("assign_recipients"),
				array(
					'element'=>'select',
					'name'=>'indRecipients[]',
					'id'=>'IndRecipient',
					'class'=>'chzn-select',
					'attributes'=>array(array('data-placeholder', getMLText('select_ind_recipients')), array('data-no_results_text', getMLText('unknown_owner'))),
					'multiple'=>true,
					'options'=>$options
				),
				array('field_wrap'=>$fieldwrap)
			);

			$options = array();
			foreach ($docAccess["groups"] as $grp) {
				$options[] = array($grp->getID(), htmlspecialchars($grp->getName()));
			}
			$this->formField(
				getMLText("individuals_in_groups"),
				array(
					'element'=>'select',
					'name'=>'grpIndRecipients[]',
					'id'=>'GrpIndRecipient',
					'class'=>'chzn-select',
					'attributes'=>array(array('data-placeholder', getMLText('select_grp_ind_recipients'))),
					'multiple'=>true,
					'options'=>$options
				)
			);

			$options = array();
			foreach ($docAccess["groups"] as $grp) {
				$options[] = array($grp->getID(), htmlspecialchars($grp->getName()));
			}
			$tmp = array();
			foreach($receiptStatus as $r) {
				if($r['type'] == 1) {
					$tmp[] = $r['required'];
				}
			}
			$fieldwrap = array();
			if($tmp) {
				$fieldwrap = array('', $this->getSelectPresetButtonHtml("GrpRecipient", $tmp));
			}
			$this->formField(
				getMLText("assign_recipients"),
				array(
					'element'=>'select',
					'name'=>'grpRecipients[]',
					'id'=>'GrpRecipient',
					'class'=>'chzn-select',
					'attributes'=>array(array('data-placeholder', getMLText('select_grp_recipients')), array('data-no_results_text', getMLText('unknown_owner'))),
					'multiple'=>true,
					'options'=>$options
				),
				array('field_wrap'=>$fieldwrap)
			);

		}
		$this->contentContainerEnd();
		$this->formSubmit(getMLText('checkin_document'));
?>
</form>
<?php
		$this->columnEnd();
		$this->columnStart(6);
		if(!empty($settings->_enableCancelCheckout)) {
			$this->contentHeading(getMLText("cancel_checkout_document"));
			$this->warningMsg(getMLText('cancel_checkout_warning'));
?>
<form class="form-horizontal" action="../op/op.CancelCheckOut.php" method="post" id="form2" name="form2">
	<input type="hidden" name="documentid" value="<?php print $document->getID(); ?>">
<?php
			$this->contentContainerStart();
			echo createHiddenFieldWithKey('cancelcheckout');
			$this->formField(
				getMLText("checkout_cancel_confirm"),
				array(
					'element'=>'input',
					'type'=>'checkbox',
					'name'=>'confirm',
					'value'=>1,
					'required'=>true
				)
			);
			$this->contentContainerEnd();
			$this->formSubmit(getMLText('cancel_checkout'), '', '', 'danger');
?>
</form>
<?php
		}
		$this->columnEnd();
		} else {
			$this->columnStart(12);
?>
<form action="../op/op.CancelCheckOut.php" method="post">
	<?php echo createHiddenFieldWithKey('cancelcheckout'); ?>
	<input type="hidden" name="documentid" value="<?php print $document->getID(); ?>">
	<input type="hidden" name="confirm" value="1">
	<?php $this->formSubmit(getMLText('cancel_checkout'),'','','danger');?>
</form>
<?php
			$this->columnEnd();
		}
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
