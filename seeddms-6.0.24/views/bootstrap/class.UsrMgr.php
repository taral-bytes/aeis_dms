<?php
/**
 * Implementation of UsrMgr view
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
 * Class which outputs the html page for UsrMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_UsrMgr extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$seluser = $this->params['seluser'];
		$strictformcheck = $this->params['strictformcheck'];

		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
		$this->printFolderChooserJs("form");
?>
function runValidation() {
	$("#form1").validate({
		rules: {
			login: {
				required: true
			},
			name: {
				required: true
			},
<?php
	if ($strictformcheck) {
?>
			comment: {
				required: true
			},
<?php
	}
?>
			email: {
				required: true,
				email: true
			},
			pwdconf: {
				equalTo: "#pwd"
			}
		},
		messages: {
			login: "<?php printMLText("js_no_login");?>",
			name: "<?php printMLText("js_no_name");?>",
<?php
	if ($strictformcheck) {
?>
			comment: "<?php printMLText("js_no_comment");?>",
<?php
	}
?>
			email: "<?php printMLText("js_no_email");?>",
			pwdconf: "<?php printMLText("js_pwd_not_conf");?>",
		}
	});
}

$(document).ready( function() {
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {userid: $(this).val()});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.UsrMgr.php?userid=' + $(this).val());
	});
});
<?php

		$this->printFileChooserJs();
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$seluser = $this->params['seluser'];
		$quota = $this->params['quota'];
		$settings = $this->params['settings'];
		$workflowmode = $this->params['workflowmode'];

		if($seluser) {
			$sessionmgr = new SeedDMS_SessionMgr($dms->getDB());

			$this->contentHeading(getMLText("user_info"));
			echo "<table class=\"table table-condensed table-sm\">\n";
			echo "<tr><td>".getMLText('discspace')."</td><td>";
			if($quota) {
				$qt = $seluser->getQuota() ? $seluser->getQuota() : $quota;
				echo SeedDMS_Core_File::format_filesize($seluser->getUsedDiskSpace())." / ".SeedDMS_Core_File::format_filesize($qt)."<br />";
				echo $this->getProgressBar($seluser->getUsedDiskSpace(), $qt);
			} else {
				echo SeedDMS_Core_File::format_filesize($seluser->getUsedDiskSpace())."<br />";
			}
			echo "</td></tr>\n";
			$documents = $seluser->getDocuments();
			echo "<tr><td>".getMLText('documents')."</td><td>".count($documents)."</td></tr>\n";
			$contents = $seluser->getDocumentContents();
			echo "<tr><td>".getMLText('document_versions')."</td><td>".count($contents)."</td></tr>\n";
			$documents = $seluser->getDocumentsLocked();
			echo "<tr><td>".getMLText('documents_locked')."</td><td>".count($documents)."</td></tr>\n";
			$links = $seluser->getDocumentLinks();
			echo "<tr><td>".getMLText('document_links')."</td><td>".count($links)."</td></tr>\n";
			$files = $seluser->getDocumentFiles();
			echo "<tr><td>".getMLText('document_files')."</td><td>".count($files)."</td></tr>\n";
			$folders = $seluser->getFolders();
			echo "<tr><td>".getMLText('folders')."</td><td>".count($folders)."</td></tr>\n";
			$categories = $seluser->getKeywordCategories();
			echo "<tr><td>".getMLText('personal_default_keywords')."</td><td>".count($categories)."</td></tr>\n";
			$dnot = $seluser->getNotifications(T_DOCUMENT);
			echo "<tr><td>".getMLText('documents_with_notification')."</td><td>".count($dnot)."</td></tr>\n";
			$fnot = $seluser->getNotifications(T_FOLDER);
			echo "<tr><td>".getMLText('folders_with_notification')."</td><td>".count($fnot)."</td></tr>\n";

			if($workflowmode == "traditional") {
				$resArr = $dms->getDocumentList('ReviewByMe', $seluser);
				if($resArr) {
					$tasks['review'] = array();
					foreach ($resArr as $res) {
						$document = $dms->getDocument($res["id"]);
						if($document && $document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
							$tasks['review'][] = array('id'=>$res['id'], 'name'=>$res['name']);
						}
					}
					echo "<tr><td>".getMLText('pending_reviews')."</td><td>".count($tasks['review'])."</td></tr>\n";
				}
			}
			if($workflowmode == "traditional" || $workflowmode == 'traditional_only_approval') {
				$resArr = $dms->getDocumentList('ApproveByMe', $seluser);
				if($resArr) {
					$tasks['approval'] = array();
					foreach ($resArr as $res) {
						$document = $dms->getDocument($res["id"]);
						if($document && $document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
							$tasks['approval'][] = array('id'=>$res['id'], 'name'=>$res['name']);
						}
					}
					echo "<tr><td>".getMLText('pending_approvals')."</td><td>".count($tasks['approval'])."</td></tr>\n";
				}
				$resArr = $seluser->isMandatoryReviewerOf();
				if($resArr) {
					echo "<tr><td>".getMLText('mandatory_reviewers')."</td><td>".count($resArr)."</td></tr>\n";
				}
				$resArr = $seluser->isMandatoryApproverOf();
				if($resArr) {
					echo "<tr><td>".getMLText('mandatory_approvers')."</td><td>".count($resArr)."</td></tr>\n";
				}
			}
			$resArr = $dms->getDocumentList('ReceiptByMe', $seluser);
			if($resArr) {
				foreach ($resArr as $res) {
					$document = $dms->getDocument($res["id"]);
					if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
						$tasks['receipt'][] = array('id'=>$res['id'], 'name'=>$res['name']);
					}
				}
				echo "<tr><td>".getMLText('pending_receipt')."</td><td>".count($tasks['receipt'])."</td></tr>\n";
			}
			$resArr = $dms->getDocumentList('ReviseByMe', $seluser);
			if($resArr) {
				foreach ($resArr as $res) {
					$document = $dms->getDocument($res["id"]);
					if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
						$tasks['revision'][] = array('id'=>$res['id'], 'name'=>$res['name']);
					}
				}
				echo "<tr><td>".getMLText('pending_revision')."</td><td>".count($tasks['revision'])."</td></tr>\n";
			}
			if($workflowmode == 'advanced') {
				$workflows = $seluser->getWorkflowsInvolved();
				echo "<tr><td>".getMLText('workflows_involded')."</td><td>".count($workflows)."</td></tr>\n";
				$workflowStatus = $seluser->getWorkflowStatus();
				if($workflowStatus['u'])
					echo "<tr><td>".getMLText('pending_workflows')."</td><td>".count($workflowStatus['u'])."</td></tr>\n";
			}
			$sessions = $sessionmgr->getUserSessions($seluser);
			if($sessions) {
				$session = array_shift($sessions);
				echo "<tr><td>".getMLText('lastaccess')."</td><td>".getLongReadableDate($session->getLastAccess())."</td></tr>\n";
			}
			// echo "<tr><td>".getMLText('network_drive')."</td><td><a href=\"http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot.'checkout/'.preg_replace('/[^A-Za-z0-9_-]/', '', $seluser->getLogin())."\">".preg_replace('/[^A-Za-z0-9_-]/', '', $seluser->getLogin())."</a></td></tr>\n";
			echo "</table>";

		}
	} /* }}} */

	function actionmenu() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$seluser = $this->params['seluser'];
		$quota = $this->params['quota'];
		$undeluserids = $this->params['undeluserids'];
		$enableemail = $this->params['enableemail'];
		$accessobject = $this->params['accessobject'];

		if($seluser) {
			$button = array(
				'label'=>getMLText('action'),
				'menuitems'=>array(
				)
			);
			if(!in_array($seluser->getID(), $undeluserids) && $accessobject->check_controller_access('UsrMgr', ['action'=>'removeuser'])) {
				$button['menuitems'][] = array('label'=>'<i class="fa fa-remove"></i> '.getMLText("rm_user"), 'link'=>'../out/out.RemoveUser.php?userid='.$seluser->getID());
			}
			if($accessobject->check_controller_access('UsrMgr', ['action'=>'removefromprocesses']))
				$button['menuitems'][] = array('label'=>'<i class="fa fa-unlink"></i> '.getMLText("rm_user_from_processes"), 'link'=>'../out/out.RemoveUserFromProcesses.php?userid='.$seluser->getID());
			if($accessobject->check_controller_access('UsrMgr', ['action'=>'transferobjects']))
				$button['menuitems'][] = array('label'=>'<i class="fa fa-share-square-o"></i> '.getMLText("transfer_objects"), 'link'=>'../out/out.TransferObjects.php?userid='.$seluser->getID());
			if($user->isAdmin() && $seluser->getID() != $user->getID())
				$button['menuitems'][] = array('label'=>'<i class="fa fa-exchange"></i> '.getMLText("substitute_user"), 'link'=>'../op/op.SubstituteUser.php?userid='.$seluser->getID()."&formtoken=".createFormKey('substituteuser'));
			if($accessobject->check_controller_access('UsrMgr', ['action'=>'sendlogindata']))
				if($enableemail)
					$button['menuitems'][] = array('label'=>'<i class="fa fa-envelope-o"></i> '.getMLText("send_login_data"), 'link'=>'../out/out.SendLoginData.php?userid='.$seluser->getID());
			if($this->hasHook('actionMenu'))
				$button['menuitems'] = $this->callHook('actionMenu', $seluser, $button['menuitems']);
			self::showButtonwithMenu($button);
		}
	} /* }}} */

	function form() { /* {{{ */
		$seluser = $this->params['seluser'];

		$this->showUserForm($seluser);
	} /* }}} */

	function showUserForm($currUser) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$users = $this->params['allusers'];
		$groups = $this->params['allgroups'];
		$roles = $this->params['allroles'];
		$passwordstrength = $this->params['passwordstrength'];
		$passwordexpiration = $this->params['passwordexpiration'];
		$httproot = $this->params['httproot'];
		$enableuserimage = $this->params['enableuserimage'];
		$undeluserids = $this->params['undeluserids'];
		$workflowmode = $this->params['workflowmode'];
		$quota = $this->params['quota'];
?>
	<form class="form-horizontal" action="../op/op.UsrMgr.php" method="post" enctype="multipart/form-data" name="form1" id="form1">
<?php
		if($currUser) {
			echo createHiddenFieldWithKey('edituser');
?>
	<input type="hidden" name="userid" id="userid" value="<?php print $currUser->getID();?>">
	<input type="hidden" name="action" value="edituser">
<?php
		} else {
			echo createHiddenFieldWithKey('adduser');
?>
	<input type="hidden" id="userid" value="0">
	<input type="hidden" name="action" value="adduser">
<?php
		}
		$this->contentContainerStart();
		$this->formField(
			getMLText("user_login"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'login',
				'name'=>'login',
				'value'=>($currUser ? htmlspecialchars($currUser->getLogin()) : '')
			)
		);
		$this->formField(
			getMLText("password"),
			'<input type="password" class="pwd form-control" rel="strengthbar'.($currUser ? $currUser->getID() : "0").'" name="pwd" id="pwd">'.(($currUser && $currUser->isGuest()) ? ' <input type="checkbox" name="clearpwd" value="1" /> '.getMLText('clear_password') : '')
		);
		if($passwordstrength > 0) {
			$this->formField(
				getMLText("password_strength"),
				'<div id="strengthbar'.($currUser ? $currUser->getID() : "0").'" class="progress" style="_width: 220px; height: 30px; margin-bottom: 8px;"><div class="bar bar-danger bg-danger" style="width: 0%;"></div></div>'
			);
		}
		$this->formField(
			getMLText("confirm_pwd"),
			array(
				'element'=>'input',
				'type'=>'password',
				'id'=>'pwdconf',
				'name'=>'pwdconf',
			)
		);
		if($passwordexpiration > 0 && (!$currUser || !$currUser->isAdmin())) {
			$options = array();
			if($currUser)
				$options[] = array('', getMLText("keep").($currUser->getPwdExpiration() ? ' ('.getLongReadableDate($currUser->getPwdExpiration()).')' : ''));
			$options[] = array('now', getMLText('now'));
			$options[] = array(date('Y-m-d H:i:s', time()+$passwordexpiration*86400), getMLText("according_settings"));
			$options[] = array('never', getMLText("never"));
			$this->formField(
				getMLText("password_expiration"),
				array(
					'element'=>'select',
					'name'=>'pwdexpiration',
					'options'=>$options
				)
			);
		}
		$this->formField(
			getMLText("user_name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
				'value'=>($currUser ? htmlspecialchars($currUser->getFullName()) : '')
			)
		);
		$this->formField(
			getMLText("email"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'email',
				'name'=>'email',
				'value'=>($currUser ? htmlspecialchars($currUser->getEmail()) : '')
			)
		);
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'name'=>'comment',
				'id'=>'comment',
				'rows'=>4,
				'cols'=>50,
				'value'=>($currUser ? htmlspecialchars($currUser->getComment()) : '')
			)
		);
		$options = array();
		foreach($roles as $role) {
			$options[] = array($role->getID(), htmlspecialchars($role->getName()), ($currUser && $currUser->getRole()->getID() == $role->getID()));
		}
		$this->formField(
			getMLText("role"),
			array(
				'element'=>'select',
				'name'=>'role',
				'options'=>$options
			)
		);
		$themes = UI::getStyles();
		$options = array();
		foreach ($themes as $currTheme) {
			$options[] = array($currTheme, $currTheme, ($currUser && ($currTheme == $currUser->getTheme())) || ($currTheme == $settings->_theme));
		}
		$this->formField(
			getMLText("theme"),
			array(
				'element'=>'select',
				'name'=>'theme',
				'options'=>$options
			)
		);
		$options = array();
		foreach($groups as $group) {
			$options[] = array($group->getID(), htmlspecialchars($group->getName()), ($currUser && $group->isMember($currUser)));
		}
		$this->formField(
			getMLText("groups"),
			array(
				'element'=>'select',
				'name'=>'groups[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'placeholder'=>getMLText('select_groups'),
				'options'=>$options
			)
		);
		$this->formField(getMLText("home_folder"), $this->getFolderChooserHtml("form".($currUser ? $currUser->getId() : '0'), M_READ, -1, $currUser ? $dms->getFolder($currUser->getHomeFolder()) : 0, 'homefolder'));

		$this->formField(
			getMLText("quota"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'quota',
				'name'=>'quota',
				'value'=>($currUser ? $currUser->getQuota() : '')
			)
		);
		if($quota > 0)
			$this->warningMsg(getMLText('current_quota', array('quota'=>SeedDMS_Core_File::format_filesize($quota))));
		else
			$this->warningMsg(getMLText('quota_is_disabled'));
		$this->formField(
			getMLText("is_hidden"),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'ishidden',
				'value'=>1,
				'checked'=>$currUser && $currUser->isHidden()
			)
		);
		$this->formField(
			getMLText("is_disabled"),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'isdisabled',
				'value'=>1,
				'checked'=>$currUser && $currUser->isDisabled()
			)
		);
		if ($enableuserimage) {
			if ($currUser) {
				$this->formField(
					getMLText("user_image"),
					($currUser->hasImage() ? "<img src=\"".$httproot."out/out.UserImage.php?userid=".$currUser->getId()."\">" : getMLText('no_user_image'))
				);
				$this->formField(
					getMLText("new_user_image"),
					$this->getFileChooserHtml('userfile', false, "image/jpeg")
				);
			} else {
				$this->formField(
					getMLText("user_image"),
					$this->getFileChooserHtml('userfile', false, "image/jpeg")
				);
			}
		}
		$options = array();
		if($currUser) {
			$substitutes = $currUser->getSubstitutes();
		} else {
			$substitutes = array();
		}
		foreach ($users as $usr) {
			if ($usr->isGuest() || ($currUser && !$usr->isAdmin() && $currUser->isAdmin()) || ($currUser && $usr->getID() == $currUser->getID()))
				continue;
			$checked=false;
			foreach ($substitutes as $r) if ($r->getID()==$usr->getID()) $checked=true;

			$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()), $checked);
		}
		$this->formField(
			getMLText("possible_substitutes"),
			array(
				'element'=>'select',
				'name'=>'substitute[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-placeholder', getMLText('select_users')), array('data-no_result_text', getMLText('unknown_owner'))),
				'options'=>$options
			)
		);
		if($workflowmode == "traditional" || $workflowmode == 'traditional_only_approval') {
			if($workflowmode == "traditional") {
				$this->contentSubHeading(getMLText("mandatory_reviewers"));
				$options = array();
				if($currUser)
					$res=$currUser->getMandatoryReviewers();
				else
					$res = array();
				foreach ($users as $usr) {
					if ($usr->isGuest() || ($currUser && $usr->getID() == $currUser->getID()))
						continue;

					$checked=false;
					foreach ($res as $r) if ($r['reviewerUserID']==$usr->getID()) $checked=true;

					$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()), $checked);
				}
				$this->formField(
					getMLText("individuals"),
					array(
						'element'=>'select',
						'name'=>'usrReviewers[]',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_users'))),
						'multiple'=>true,
						'options'=>$options
					)
				);
				$options = array();
				foreach ($groups as $grp) {

					$checked=false;
					foreach ($res as $r) if ($r['reviewerGroupID']==$grp->getID()) $checked=true;

					$options[] = array($grp->getID(), htmlspecialchars($grp->getName()), $checked);
				}
				$this->formField(
					getMLText("groups"),
					array(
						'element'=>'select',
						'name'=>'grpReviewers[]',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_groups'))),
						'multiple'=>true,
						'options'=>$options
					)
				);
			}

			$this->contentSubHeading(getMLText("mandatory_approvers"));
			$options = array();
			if($currUser)
				$res=$currUser->getMandatoryApprovers();
			else
				$res = array();
			foreach ($users as $usr) {
				if ($usr->isGuest() || ($currUser && $usr->getID() == $currUser->getID()))
					continue;

				$checked=false;
				foreach ($res as $r) if ($r['approverUserID']==$usr->getID()) $checked=true;

				$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin()." - ".$usr->getFullName()), $checked);
			}
			$this->formField(
				getMLText("individuals"),
				array(
					'element'=>'select',
					'name'=>'usrApprovers[]',
					'class'=>'chzn-select',
					'attributes'=>array(array('data-placeholder', getMLText('select_users'))),
					'multiple'=>true,
					'options'=>$options
				)
			);
			$options = array();
			foreach ($groups as $grp) {

				$checked=false;
				foreach ($res as $r) if ($r['approverGroupID']==$grp->getID()) $checked=true;

				$options[] = array($grp->getID(), htmlspecialchars($grp->getName()), $checked);
			}
			$this->formField(
				getMLText("groups"),
				array(
					'element'=>'select',
					'name'=>'grpApprovers[]',
					'class'=>'chzn-select',
					'attributes'=>array(array('data-placeholder', getMLText('select_groups'))),
					'multiple'=>true,
					'options'=>$options
				)
			);
		} elseif($workflowmode == 'advanced') {
			$workflows = $dms->getAllWorkflows();
			if($workflows) {
				$this->contentSubHeading(getMLText("workflow"));
				$options = array();
				$mandatoryworkflows = $currUser ? $currUser->getMandatoryWorkflows() : array();
				foreach ($workflows as $workflow) {
					$checked = false;
					if($mandatoryworkflows) foreach($mandatoryworkflows as $mw) if($mw->getID() == $workflow->getID()) $checked = true;
					$options[] = array($workflow->getID(), htmlspecialchars($workflow->getName()), $checked);
				}
				$this->formField(
					getMLText("workflow"),
					array(
						'element'=>'select',
						'name'=>'workflows[]',
						'class'=>'chzn-select',
						'attributes'=>array(array('data-placeholder', getMLText('select_workflow'))),
						'multiple'=>true,
						'options'=>$options
					)
				);
			}
		}
		$this->contentContainerEnd();
		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText($currUser ? "save" : "add_user"));
?>
	</form>
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$seluser = $this->params['seluser'];
		$users = $this->params['allusers'];
		$groups = $this->params['allgroups'];
		$passwordstrength = $this->params['passwordstrength'];
		$passwordexpiration = $this->params['passwordexpiration'];
		$httproot = $this->params['httproot'];
		$enableuserimage = $this->params['enableuserimage'];
		$undeluserids = $this->params['undeluserids'];
		$workflowmode = $this->params['workflowmode'];
		$quota = $this->params['quota'];
		$accessobject = $this->params['accessobject'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("user_management"));
		$this->rowStart();
		$this->columnStart(4);
?>
<form class="form-horizontal">
<?php
		$options = array();
		$options[] = array("-1", getMLText("choose_user"));
		$options[] = array("0", getMLText("add_user"));
		foreach ($users as $currUser) {
			$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin().' - '.$currUser->getFullName()), $seluser && $currUser->getID()==$seluser->getID(), array(array('data-subtitle', htmlspecialchars($currUser->getEmail()))));
		}
		$this->formField(
			null, //getMLText("selection"),
			array(
				'element'=>'select',
				'id'=>'selector',
				'class'=>'chzn-select',
				'options'=>$options,
				'placeholder'=>getMLText('select_users'),
			)
		);
?>
</form>
<?php if($accessobject->check_view_access($this, array('action'=>'actionmenu'))) { ?>
	<div class="ajax" style="margin-bottom: 15px;" data-view="UsrMgr" data-action="actionmenu" <?php echo ($seluser ? "data-query=\"userid=".$seluser->getID()."\"" : "") ?>></div>
<?php } ?>
<?php if($accessobject->check_view_access($this, array('action'=>'info'))) { ?>
	<div class="ajax" data-view="UsrMgr" data-action="info" <?php echo ($seluser ? "data-query=\"userid=".$seluser->getID()."\"" : "") ?>></div>
<?php
		}
		$this->columnEnd();
		$this->columnStart(8);
?>
<?php if($accessobject->check_view_access($this, array('action'=>'form'))) { ?>
		<div class="ajax" data-view="UsrMgr" data-action="form" data-afterload="()=>{runValidation();}" <?php echo ($seluser ? "data-query=\"userid=".$seluser->getID()."\"" : "") ?>></div>
<?php } ?>
	</div>
	<?php
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
