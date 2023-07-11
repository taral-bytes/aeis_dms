<?php
/**
 * Implementation of AddDocument view
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
 * Class which outputs the html page for AddDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_AddDocument extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$libraryfolder = $this->params['libraryfolder'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$partitionsize = $this->params['partitionsize'];
		$maxuploadsize = $this->params['maxuploadsize'];
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$enablemultiupload = $this->params['enablemultiupload'];
		header('Content-Type: application/javascript; charset=UTF-8');

		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
		if($enablelargefileupload) {
			$this->printFineUploaderJs('../op/op.UploadChunks.php', $partitionsize, $maxuploadsize, $enablemultiupload, 'userfile', 'adddocform');
		}
?>
$(document).ready(function() {
	$('#new-file').click(function(event) {
		tttttt = $("#userfile-upload-file").clone().appendTo("#userfile-upload-files").removeAttr("id");
		tttttt.children('div').children('input').val('');
		tttttt.children('div').children('span').children('input').val('');
	});
	jQuery.validator.addMethod("alternatives", function(value, element, params) {
		if(value != '')
			return true;
		var valid = false;
		$.each(params, function( index, value ) {
			if(value.val() != '' && typeof value.val() != 'undefined')
				valid = true
		});
		return valid;
	}, "<?php printMLText("js_no_file");?>");
	/* The fineuploader validation is actually checking all fields that can contain
	 * a file to be uploaded. First checks if an alternative input field is set,
	 * second loops through the list of scheduled uploads, checking if at least one
	 * file will be submitted. param[0] is the fineuploader, param[1] is the
	 * field from the dropfolder
	 */
	jQuery.validator.addMethod("fineuploader", function(value, element, params) {
console.log(params);
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
	$("#adddocform").validate({
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
				fineuploader: [ userfileuploader, $('#dropfolderfileadddocform') ]
			}
<?php
		} else {
?>
			'userfile[]': {
				require_from_group: [1, ".fileupload-group"]
//				alternatives: [$('#dropfolderfileadddocform'), $('#choosedocsearch<?= md5('librarydoc'.'adddocform') ?>')]
			},
			dropfolderfileadddocform: {
				require_from_group: [1, ".fileupload-group"]
//				alternatives: [$("#userfile"), $('#choosedocsearch<?= md5('librarydoc'.'adddocform') ?>')]
			}
<?php
		}
?>
		},
		messages: {
			name: "<?php printMLText("js_no_name");?>",
			comment: "<?php printMLText("js_no_comment");?>",
			keywords: "<?php printMLText("js_no_keywords");?>"
		},
		errorPlacement: function( error, element ) {
			if ( element.is( ":file" ) ) {
				error.appendTo( element.parent().parent().parent());
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
			$this->printKeywordChooserJs("adddocform");
			if($libraryfolder)
				$this->printDocumentChooserJs("adddocform");
			if($dropfolderdir) {
				$this->printDropFolderChooserJs("adddocform");
			}
			$this->printFileChooserJs();
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$enablemultiupload = $this->params['enablemultiupload'];
		$maxuploadsize = $this->params['maxuploadsize'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];
		$enablereceiptworkflow = $this->params['enablereceiptworkflow'];
		$enableadminreceipt = $this->params['enableadminreceipt'];
		$enableownerreceipt = $this->params['enableownerreceipt'];
		$enableselfreceipt = $this->params['enableselfreceipt'];
		$strictformcheck = $this->params['strictformcheck'];
		$nodocumentformfields = $this->params['nodocumentformfields'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$libraryfolder = $this->params['libraryfolder'];
		$dropfolderfile = $this->params['dropfolderfile'];
		$workflowmode = $this->params['workflowmode'];
		$presetexpiration = $this->params['presetexpiration'];
		$sortusersinlist = $this->params['sortusersinlist'];
		$orderby = $this->params['orderby'];
		$folderid = $folder->getId();
		$accessop = $this->params['accessobject'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/additional-methods.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');
		if($enablelargefileupload) {
			$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/fine-uploader/jquery.fine-uploader.min.js"></script>'."\n", 'js');
			$this->htmlAddHeader($this->getFineUploaderTemplate(), 'js');
		}

		$this->htmlStartPage(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true), "view_folder", $folder);
		
		$msg = getMLText("max_upload_size").": ".SeedDMS_Core_File::format_filesize($maxuploadsize);
		$this->warningMsg($msg);
		$this->contentHeading(getMLText("add_document"));

		// Retrieve a list of all users and groups that have review / approve
		// privileges.
		$docAccess = $folder->getReadAccessList($enableadminrevapp, $enableownerrevapp);

		$txt = $this->callHook('addDocumentPreForm');
		if(is_string($txt))
			echo $txt;
?>
		<form class="form-horizontal" action="../op/op.AddDocument.php" enctype="multipart/form-data" method="post" id="adddocform" name="adddocform">
		<?php echo createHiddenFieldWithKey('adddocument'); ?>
		<input type="hidden" name="folderid" value="<?php print $folderid; ?>">
		<input type="hidden" name="showtree" value="<?php echo showtree();?>">
<?php
		$this->rowStart();
		$this->columnStart(6);
		$this->contentSubHeading(getMLText("document_infos"));
		$this->contentContainerStart();
		$this->formField(
			getMLText("name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
				'required'=>false
			)
		);
		if(!$nodocumentformfields || !in_array('comment', $nodocumentformfields))
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'name'=>'comment',
				'rows'=>4,
				'cols'=>80,
				'required'=>$strictformcheck
			)
		);
		if(!$nodocumentformfields || !in_array('keywords', $nodocumentformfields))
		$this->formField(
			getMLText("keywords"),
				$this->getKeywordChooserHtml('adddocform')
		);
		$categories = $dms->getDocumentCategories();
		if($categories) {
			if(!$nodocumentformfields || !in_array('categories', $nodocumentformfields)) {
				$options = array();
				foreach($categories as $category) {
					$options[] = array($category->getID(), htmlspecialchars($category->getName()));
				}
				$this->formField(
					getMLText("categories"),
					array(
						'element'=>'select',
						'class'=>'chzn-select',
						'name'=>'categories[]',
						'multiple'=>true,
						'attributes'=>array(array('data-placeholder', getMLText('select_category'), array('data-no_results_text', getMLText('unknown_document_category')))),
						'options'=>$options
					)
				);
			}
		}
		if(!$nodocumentformfields || !in_array('sequence', $nodocumentformfields)) {
			$this->formField(getMLText("sequence"), $this->getSequenceChooser($folder->getDocuments('s')).($orderby != 's' ? "<br />".getMLText('order_by_sequence_off') : ''));
		} else {
			$minmax = $folder->getDocumentsMinMax();
			if($this->params['defaultposition'] == 'start') {
				$seq = $minmax['min'] - 1;
			} else {
				$seq = $minmax['max'] + 1;
			}
			$this->formField(
				null,
				array(
					'element'=>'input',
					'type'=>'hidden',
					'name'=>'sequence',
					'value'=>(string) $seq,
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
		if($accessop->check_controller_access('AddDocument', array('action'=>'setOwner'))) {
		$options = array();
		$allUsers = $dms->getAllUsers($sortusersinlist);
		foreach ($allUsers as $currUser) {
			if (!$currUser->isGuest())
				$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin().' - '.$currUser->getFullName()), ($currUser->getID()==$user->getID()), array(array('data-subtitle', htmlspecialchars($currUser->getEmail()))));
		}
		$this->formField(
			getMLText("owner"),
			array(
				'element'=>'select',
				'id'=>'ownerid',
				'name'=>'ownerid',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
		}
		$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_document, SeedDMS_Core_AttributeDefinition::objtype_all));
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$arr = $this->callHook('addDocumentAttribute', null, $attrdef);
				if(is_array($arr)) {
					if($arr) {
						$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
					}
				} elseif(is_string($arr)) {
					echo $arr;
				} else {
					$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, ''));
				}
			}
		}
		$arrs = $this->callHook('addDocumentAttributes', null);
		if(is_array($arrs)) {
			foreach($arrs as $arr) {
				$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
			}
		} elseif(is_string($arrs)) {
			echo $arrs;
		}

		$this->contentContainerEnd();
		if(!$nodocumentformfields || !in_array('notification', $nodocumentformfields)) {
			$this->contentSubHeading(getMLText("add_document_notify"));
			$this->contentContainerStart();

		$options = array();
		$allUsers = $dms->getAllUsers($sortusersinlist);
		foreach ($allUsers as $userObj) {
			if (!$userObj->isGuest() && $folder->getAccessMode($userObj) >= M_READ)
				$options[] = array($userObj->getID(), htmlspecialchars($userObj->getLogin() . " - " . $userObj->getFullName()));
		}
		$this->formField(
			getMLText("individuals"),
			array(
				'element'=>'select',
				'name'=>'notification_users[]',
				'class'=>'chzn-select',
				'attributes'=>array(array('data-placeholder', getMLText('select_ind_notification'))),
				'multiple'=>true,
				'options'=>$options
			)
		);
		$options = array();
		$allGroups = $dms->getAllGroups();
		foreach ($allGroups as $groupObj) {
			if ($folder->getGroupAccessMode($groupObj) >= M_READ)
				$options[] = array($groupObj->getID(), htmlspecialchars($groupObj->getName()));
		}
		$this->formField(
			getMLText("groups"),
			array(
				'element'=>'select',
				'name'=>'notification_groups[]',
				'class'=>'chzn-select',
				'attributes'=>array(array('data-placeholder', getMLText('select_grp_notification'))),
				'multiple'=>true,
				'options'=>$options
			)
		);
			$this->contentContainerEnd();
		}
		$this->columnEnd();
		$this->columnStart(6);
		$this->contentSubHeading(getMLText("version_info"));
		$this->contentContainerStart();
		if(!$nodocumentformfields || !in_array('version', $nodocumentformfields)) {
		$this->formField(
			getMLText("version"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'reqversion',
				'name'=>'reqversion',
				'value'=>1
			)
		);
		}
		$this->formField(
			getMLText("local_file"),
			$enablelargefileupload ? $this->getFineUploaderHtml() : $this->getFileChooserHtml('userfile[]', $enablemultiupload).($enablemultiupload ? '<a class="" id="new-file"><?php printMLtext("add_multiple_files") ?></a>' : '')
		);
		if($dropfolderdir) {
			$this->formField(
				getMLText("dropfolder_file"),
				$this->getDropFolderChooserHtml("adddocform", $dropfolderfile)
			);
		}
		if($libraryfolder) {
			$this->formField(
				getMLText("librarydoc"),
				$this->getDocumentChooserHtml("adddocform", M_READ, -1, null, 'librarydoc', $libraryfolder, 1)
			);
		}
		if($arr = $this->callHook('addDocumentContentFile', 'add')) {
			foreach($arr as $ar)
				if(is_array($ar)) {
					$this->formField($ar[0], $ar[1], isset($ar[2]) ? $ar[2] : null);
				} elseif(is_string($ar)) {
					echo $ar;
				}
		}
		if(!$nodocumentformfields || !in_array('version_comment', $nodocumentformfields)) {
		$this->formField(
			getMLText("comment_for_current_version"),
			array(
				'element'=>'textarea',
				'name'=>'version_comment',
				'rows'=>4,
				'cols'=>80
			)
		);
		$this->formField(
			getMLText("use_comment_of_document"),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'use_comment',
				'value'=>1
			)
		);
		}
		$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_documentcontent, SeedDMS_Core_AttributeDefinition::objtype_all));
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$arr = $this->callHook('addDocumentContentAttribute', null, $attrdef);
				if(is_array($arr)) {
					$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
				} elseif(is_string($arr)) {
					echo $arr;
				} else {
					$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, '', 'attributes_version'));
				}
			}
		}

		$arrs = $this->callHook('addDocumentContentAttributes', $folder);
		if(is_array($arrs)) {
			foreach($arrs as $arr) {
				$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
			}
		} elseif(is_string($arrs)) {
			echo $arrs;
		}

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
					foreach ($mandatoryworkflows as $workflow) {
						$options[] = array($workflow->getID(), htmlspecialchars($workflow->getName()));
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
			$this->contentContainerEnd();
			if($workflowmode == 'traditional') {
				$this->contentSubHeading(getMLText("assign_reviewers"));
				$this->contentContainerStart();

				/* List all mandatory reviewers */
				$res=$user->getMandatoryReviewers();
				$tmp = array();
				if($res) {
					foreach ($res as $r) {
						if($r['reviewerUserID'] > 0) {
							$u = $dms->getUser($r['reviewerUserID']);
							$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
						}
					}
				}

				$options = array();
				foreach ($docAccess["users"] as $usr) {
					if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 
					$mandatory=false;
					foreach ($res as $r) if ($r['reviewerUserID']==$usr->getID()) $mandatory=true;

					$option = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()), null);
					if ($mandatory) $option[] = array(array('disabled', 'disabled'));
					$options[] = $option;
				}
				$this->formField(
					getMLText("individuals"),
					array(
						'element'=>'select',
						'name'=>'indReviewers[]',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_ind_reviewers'))),
						'multiple'=>true,
						'options'=>$options
					),
					array('field_wrap'=>array('', ($tmp ? '<div class="mandatories"><span>'.getMLText('mandatory_reviewers').':</span> '.implode(', ', $tmp).'</div>' : '')))
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

				/* List all mandatory groups of reviewers */
				$tmp = array();
				if($res) {
					foreach ($res as $r) {
						if($r['reviewerGroupID'] > 0) {
							$u = $dms->getGroup($r['reviewerGroupID']);
							$tmp[] =  htmlspecialchars($u->getName());
						}
					}
				}
				$options = array();
				foreach ($docAccess["groups"] as $grp) {
				
					$mandatory=false;
					foreach ($res as $r) if ($r['reviewerGroupID']==$grp->getID()) $mandatory=true;	

					$option = array($grp->getID(), htmlspecialchars($grp->getName()), null);
					if ($mandatory || !$grp->getUsers()) $option[] = array(array('disabled', 'disabled'));
					$options[] = $option;
				}
				$this->formField(
					getMLText("groups"),
					array(
						'element'=>'select',
						'name'=>'grpReviewers[]',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_grp_reviewers'))),
						'multiple'=>true,
						'options'=>$options
					),
					array('field_wrap'=>array('', ($tmp ? '<div class="mandatories"><span>'.getMLText('mandatory_reviewergroups').':</span> '.implode(', ', $tmp).'</div>' : '')))
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
			$res=$user->getMandatoryApprovers();
			/* List all mandatory approvers */
			$tmp = array();
			if($res) {
				foreach ($res as $r) {
					if($r['approverUserID'] > 0) {
						$u = $dms->getUser($r['approverUserID']);
						$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
					}
				}
			}

			$options = array();
			foreach ($docAccess["users"] as $usr) {
				if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 

				$mandatory=false;
				foreach ($res as $r) if ($r['approverUserID']==$usr->getID()) $mandatory=true;
				
				$option = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()), null);
				if ($mandatory) $option[] = array(array('disabled', 'disabled'));
				$options[] = $option;
			}
			$this->formField(
				getMLText("individuals"),
				array(
					'element'=>'select',
					'name'=>'indApprovers[]',
					'class'=>'chzn-select',
					'attributes'=>array(array('data-placeholder', getMLText('select_ind_approvers'))),
					'multiple'=>true,
					'options'=>$options
				),
				array('field_wrap'=>array('', ($tmp ? '<div class="mandatories"><span>'.getMLText('mandatory_approvers').':</span> '.implode(', ', $tmp).'</div>' : '')))
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

			/* List all mandatory groups of approvers */
			$tmp = array();
			if($res) {
				foreach ($res as $r) {
					if($r['approverGroupID'] > 0) {
						$u = $dms->getGroup($r['approverGroupID']);
						$tmp[] =  htmlspecialchars($u->getName());
					}
				}
			}

			$options = array();
			foreach ($docAccess["groups"] as $grp) {
			
				$mandatory=false;
				foreach ($res as $r) if ($r['approverGroupID']==$grp->getID()) $mandatory=true;	

				$option = array($grp->getID(), htmlspecialchars($grp->getName()), null);
				if ($mandatory || !$grp->getUsers()) $option[] = array(array('disabled', 'disabled'));

				$options[] = $option;
			}
			$this->formField(
				getMLText("groups"),
				array(
					'element'=>'select',
					'name'=>'grpApprovers[]',
					'class'=>'chzn-select',
					'attributes'=>array(array('data-placeholder', getMLText('select_grp_approvers'))),
					'multiple'=>true,
					'options'=>$options
				),
				array('field_wrap'=>array('', ($tmp ? '<div class="mandatories"><span>'.getMLText('mandatory_approvergroups').':</span> '.implode(', ', $tmp).'</div>' : '')))
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
		} else {
		}

		if($enablereceiptworkflow) {
			$this->contentContainerEnd();
			$this->contentSubHeading(getMLText("assign_recipients"));
			$this->contentContainerStart();
			$options = array();
			foreach ($docAccess["users"] as $usr) {
				if (!$enableselfreceipt && $usr->getID()==$user->getID()) continue; 
				$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()));
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
				)
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
				)
			);

		}

		$this->contentContainerEnd();
		$this->columnEnd();
		$this->rowEnd();
		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('add_document'));
?>
		</form>
<?php
		$txt = $this->callHook('addDocumentPostForm');
		if(is_string($txt))
			echo $txt;
		$this->contentEnd();
		$this->htmlEndPage();

	} /* }}} */
}
?>
