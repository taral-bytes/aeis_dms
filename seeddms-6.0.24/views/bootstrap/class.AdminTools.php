<?php
/**
 * Implementation of AdminTools view
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
 * Class which outputs the html page for AdminTools view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_AdminTools extends SeedDMS_Theme_Style {

	static function wrapRow($content) { /* {{{ */
		return self::startRow().$content.self::endRow();
	} /* }}} */

	public function startRow() { /* {{{ */
		ob_start();
		$this->rowStart();
		return ob_get_clean();
	} /* }}} */

	public function endRow() { /* {{{ */
		ob_start();
		$this->rowEnd();
		return ob_get_clean();
	} /* }}} */

	public function rowButton($link, $icon, $label) { /* {{{ */
		ob_start();
		$this->columnStart(2);
		echo '<a href="'.$link.'" class="btn btn-medium btn-light btn-block btn-md"><i class="fa fa-'.$icon.'"></i><br />'.getMLText($label).'</a>';
		$this->columnEnd();
		return ob_get_clean();
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$logfileenable = $this->params['logfileenable'];
		$enablefullsearch = $this->params['enablefullsearch'];
		$accessop = $this->params['accessobject'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
//		$this->contentHeading(getMLText("admin_tools"));
//		$this->contentContainerStart();
?>
	<div id="admin-tools">
	<?php echo $this->callHook('beforeRows'); ?>
	<?= self::startRow(); ?>
		<?php echo $this->callHook('startOfRow', 1); ?>
<?php if($accessop->check_view_access('UsrMgr')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.UsrMgr.php", "user", "user_management"); ?>
<?php } ?>
<?php if($accessop->check_view_access('GroupMgr')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.GroupMgr.php", "group", "group_management"); ?>
<?php } ?>
<?php if($accessop->check_view_access('RoleMgr')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.RoleMgr.php", "bullseye", "role_management"); ?>
<?php } ?>
		<?php echo $this->callHook('endOfRow', 1); ?>
	<?= self::endRow(); ?>
	<?= self::startRow(); ?>
		<?php echo $this->callHook('startOfRow', 2); ?>
<?php if($accessop->check_view_access('BackupTools')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.BackupTools.php", "life-saver", "backup_tools"); ?>
<?php } ?>
<?php		
		if ($logfileenable && ($accessop->check_view_access('LogManagement')))
			echo self::rowButton($settings->_httpRoot."out/out.LogManagement.php", "list", "log_management");
?>
		<?php echo $this->callHook('endOfRow', 2); ?>
	</div>
	<?= self::startRow(); ?>
		<?php echo $this->callHook('startOfRow', 3); ?>
<?php if($accessop->check_view_access('DefaultKeywords')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.DefaultKeywords.php", "reorder", "global_default_keywords"); ?>
<?php } ?>
<?php if($accessop->check_view_access('Categories')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.Categories.php", "columns", "global_document_categories"); ?>
<?php } ?>
<?php if($accessop->check_view_access('AttributeMgr')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.AttributeMgr.php", "tags", "global_attributedefinitions"); ?>
<?php } ?>
		<?php echo $this->callHook('endOfRow', 3); ?>
	<?= self::endRow(); ?>
<?php
	if($this->params['workflowmode'] == 'advanced') {
?>
	<?= self::startRow(); ?>
		<?php echo $this->callHook('startOfRow', 4); ?>
<?php if($accessop->check_view_access('WorkflowMgr')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.WorkflowMgr.php", "sitemap", "global_workflows"); ?>
<?php } ?>
<?php if($accessop->check_view_access('WorkflowStatesMgr')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.WorkflowStatesMgr.php", "star", "global_workflow_states"); ?>
<?php } ?>
<?php if($accessop->check_view_access('WorkflowActionsMgr')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.WorkflowActionsMgr.php", "bolt", "global_workflow_actions"); ?>
<?php } ?>
		<?php echo $this->callHook('endOfRow', 4); ?>
	<?= self::endRow(); ?>
<?php
		}
		if($enablefullsearch) {
?>
	<?= self::startRow(); ?>
		<?php echo $this->callHook('startOfRow', 5); ?>
<?php if($accessop->check_view_access('Indexer')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.Indexer.php", "refresh", "update_fulltext_index"); ?>
<?php } ?>
<?php if($accessop->check_view_access('CreateIndex')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.CreateIndex.php", "search", "create_fulltext_index"); ?>
<?php } ?>
<?php if($accessop->check_view_access('IndexInfo')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.IndexInfo.php", "info-circle", "fulltext_info"); ?>
<?php } ?>
		<?php echo $this->callHook('endOfRow', 5); ?>
	<?= self::endRow(); ?>
<?php
		}
?>
	<?= self::startRow(); ?>
		<?php echo $this->callHook('startOfRow', 6); ?>
<?php if($accessop->check_view_access('Statistic')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.Statistic.php", "sitemap", "folders_and_documents_statistic"); ?>
<?php } ?>
<?php if($accessop->check_view_access('Charts')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.Charts.php", "bar-chart", "charts"); ?>
<?php } ?>
<?php if($accessop->check_view_access('ObjectCheck')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.ObjectCheck.php", "check", "objectcheck"); ?>
<?php } ?>
<?php if($accessop->check_view_access('Timeline')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.Timeline.php", "signal", "timeline"); ?>
<?php } ?>
		<?php echo $this->callHook('endOfRow', 6); ?>
	<?= self::endRow(); ?>
	<?= self::startRow(); ?>
		<?php echo $this->callHook('startOfRow', 7); ?>
<?php if($accessop->check_view_access('Settings')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.Settings.php", "wrench", "settings"); ?>
<?php } ?>
<?php if($accessop->check_view_access('ExtensionMgr')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.ExtensionMgr.php", "cogs", "extension_manager"); ?>
<?php } ?>
<?php if($accessop->check_view_access('SchedulerTaskMgr')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.SchedulerTaskMgr.php", "clock-o", "scheduler_task_mgr"); ?>
<?php } ?>
<?php if($accessop->check_view_access('Info')) { ?>
		<?= self::rowButton($settings->_httpRoot."out/out.Info.php", "info-circle", "version_info"); ?>
<?php } ?>
		<?php echo $this->callHook('endOfRow', 7); ?>
	<?= self::endRow(); ?>
	<?php echo $this->callHook('afterRows'); ?>
	</div>
<?php
//		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
