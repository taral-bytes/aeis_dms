<?php
/**
 * Implementation of UpdateDocument view
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
 * Class which outputs the html page for UpdateDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_UpdateDocument extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$partitionsize = $this->params['partitionsize'];
		$maxuploadsize = $this->params['maxuploadsize'];
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
		$this->printDropFolderChooserJs("form1");
		$this->printSelectPresetButtonJs();
		$this->printInputPresetButtonJs();
		$this->printCheckboxPresetButtonJs();
		if($enablelargefileupload)
			$this->printFineUploaderJs('../op/op.UploadChunks.php', $partitionsize, $maxuploadsize, false);
		$this->printFileChooserJs();
?>
$(document).ready( function() {
	jQuery.validator.addMethod("alternatives", function(value, element, params) {
		if(value == '' && params.val() == '')
			return false;
		return true;
	}, "<?php printMLText("js_no_file");?>");
	/* The fineuploader validation is actually checking all fields that can contain
	 * a file to be uploaded. First checks if an alternative input field is set,
	 * second loops through the list of scheduled uploads, checking if at least one
	 * file will be submitted.
	 */
	jQuery.validator.addMethod("fineuploader", function(value, element, params) {
		if(params[1].val() != '')
			return true;
		uploader = params[0];
		arr = uploader.getUploads();
		for(var i in arr) {
			if(arr[i].status == 'submitted')
				return true;
		}
		return false;
	}, "<?php printMLText("js_no_file");?>");
	$("#form1").validate({
		debug: false,
		ignore: ":hidden:not(.do_validate)",
<?php
		if($enablelargefileupload) {
?>
		submitHandler: function(form) {
			/* fileuploader may not have any files if drop folder is used */
			if(userfileuploader.getUploads().length)
				userfileuploader.uploadStoredFiles();
			else
				form.submit();
		},
<?php
		}
?>
		rules: {
<?php
		if($enablelargefileupload) {
?>
			'userfile-fine-uploader-uuids': {
				fineuploader: [ userfileuploader, $('#dropfolderfileform1') ]
			}
<?php
		} else {
?>
			userfile: {
				require_from_group: [1, ".fileupload-group"]
//				alternatives: $('#dropfolderfileform1')
			},
			dropfolderfileform1: {
				require_from_group: [1, ".fileupload-group"]
//				alternatives: $('#userfile')
			}
<?php
		}
?>
		},
		messages: {
			comment: "<?php printMLText("js_no_comment");?>",
		},
		errorPlacement: function( error, element ) {
			if ( element.is( ":file" ) ) {
				error.appendTo( element.parent().parent().parent());
console.log(element);
			} else {
				error.appendTo( element.parent());
			}
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
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$strictformcheck = $this->params['strictformcheck'];
		$nodocumentformfields = $this->params['nodocumentformfields'];
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$maxuploadsize = $this->params['maxuploadsize'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];
		$enablereceiptworkflow = $this->params['enablereceiptworkflow'];
		$enableselfreceipt = $this->params['enableselfreceipt'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$workflowmode = $this->params['workflowmode'];
		$presetexpiration = $this->params['presetexpiration'];
		$documentid = $document->getId();

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/additional-methods.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');
		if($enablelargefileupload) {
			$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/fine-uploader/jquery.fine-uploader.min.js"></script>'."\n", 'js');
			$this->htmlAddHeader($this->getFineUploaderTemplate(), 'js');
		}

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("update_document"));

		if ($document->isLocked()) {

			$lockingUser = $document->getLockingUser();

			$html = '';
			$html .= getMLText("update_locked_msg", array("username" => htmlspecialchars($lockingUser->getFullName()), "email" => $lockingUser->getEmail())).' ';
			
			if ($lockingUser->getID() == $user->getID())
				$html .= getMLText("unlock_cause_locking_user");
			else if ($document->getAccessMode($user) == M_ALL)
				$html .= getMLText("unlock_cause_access_mode_all");
			else
			{
				$html .= getMLText("no_update_cause_locked");
				$this->warningMsg($html);
				$this->contentEnd();
				$this->htmlEndPage();
				exit;
			}

			$this->warningMsg($html);
		}

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

		$msg = getMLText("max_upload_size").": ".SeedDMS_Core_File::format_filesize($maxuploadsize);
		$this->warningMsg($msg);

		if ($document->isCheckedOut()) {
			$msg = getMLText('document_is_checked_out_update');
			$this->warningMsg($msg);
		}

?>

<form class="form-horizontal" action="../op/op.UpdateDocument.php" enctype="multipart/form-data" method="post" name="form1" id="form1">
	<?php echo createHiddenFieldWithKey('updatedocument'); ?>
	<input type="hidden" name="documentid" value="<?php print $document->getID(); ?>">
<?php
		$this->contentContainerStart();
		$this->formField(
			getMLText("local_file"),
			$enablelargefileupload ? $this->getFineUploaderHtml() : $this->getFileChooserHtml('userfile', false)
		);
		if($dropfolderdir) {
			$this->formField(
				getMLText("dropfolder_file"),
				$this->getDropFolderChooserHtml("form1")
			);
		}
		if($arr = $this->callHook('addDocumentContentFile', 'update')) {
			foreach($arr as $ar)
				if(is_array($ar)) {
					$this->formField($ar[0], $ar[1], isset($ar[2]) ? $ar[2] : null);
				} elseif(is_string($ar)) {
					echo $ar;
				}
		}
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
			$this->contentContainerEnd();
			if($settings->_initialDocumentStatus == S_RELEASED)
				$this->warningMsg(getMLText("add_doc_workflow_warning"));
		} elseif($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			if($workflowmode == 'traditional') {
				$this->contentContainerEnd();
				$this->contentSubHeading(getMLText("assign_reviewers"));
				$this->contentContainerStart();
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
				$this->contentContainerEnd();
			}

			$this->contentSubHeading(getMLText("assign_approvers"));
			$this->contentContainerStart();
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
			$this->contentContainerEnd();
			$this->warningMsg(getMLText("add_doc_reviewer_approver_warning"));
		} else {
			$this->contentContainerEnd();
		}
		if($enablereceiptworkflow) {
			$this->contentSubHeading(getMLText("assign_recipients"));
			$this->contentContainerStart();
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
				getMLText("individuals"),
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
				getMLText("groups"),
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

			$this->contentContainerEnd();
		}
		$this->formSubmit(getMLText('update_document'));
?>
</form>

<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
