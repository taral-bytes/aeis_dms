<?php
/**
 * Implementation of ExtensionMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for ExtensionMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ExtensionMgr extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
		$(document).ready( function() {
			$('body').on('click', 'a.download', function(ev){
//			$('a.download').click(function(ev){
				var element = $(this);
				$('#'+element.data('extname')+'-download').submit();
/*
				var element = $(this);
				ev.preventDefault();
				$.ajax({url: '../op/op.ExtensionMgr.php',
					type: 'POST',
					dataType: "json",
					data: {action: 'download', 'formtoken': '<?= createFormKey('extensionmgr') ?>', 'extname': element.data('extname')},
					success: function(data) {
						noty({
							text: data.msg,
							type: (data.error) ? 'error' : 'success',
							dismissQueue: true,
							layout: 'topRight',
							theme: 'defaultTheme',
							timeout: 1500,
						});
					}
				});
*/
			});
			$('body').on('click', 'a.toggle', function(ev){
//			$('a.toggle').click(function(ev){
				var element = $(this);
				ev.preventDefault();
				$.ajax({url: '../op/op.ExtensionMgr.php',
					type: 'POST',
					dataType: "json",
					data: {action: 'toggle', 'formtoken': '<?= createFormKey('extensionmgr') ?>', 'extname': element.data('extname')},
					success: function(data) {
						noty({
							text: data.msg,
							type: (data.error) ? 'error' : 'success',
							dismissQueue: true,
							layout: 'topRight',
							theme: 'defaultTheme',
							timeout: 1500,
						});
						$('div.ajax').trigger('update');
					}
				});
			});

			$('body').on('click', 'a.import', function(ev){
				var element = $(this);
				$('#'+element.data('extname')+'-import').submit();
			});

			$("#extensionfilter").on("keyup", function() {
				var value = $(this).val().toLowerCase();
				$("#extensionlist tbody tr").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
			});
		});
		/* This function is called after the list of installed extension is updated */
		function filterList() {
			var value = $("#extensionfilter").val().toLowerCase();
			$("#extensionlist tbody tr").filter(function() {
				$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
		}
<?php

		$this->printFileChooserJs();
	} /* }}} */

	function info_versions() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$extmgr = $this->params['extmgr'];
		$extname = $this->params['extname'];
		$extconf = $extmgr->getExtensionConfiguration();

		echo "<table class=\"table\">\n";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";
		print "<th>".getMLText('name')."</th>\n";
		print "<th>".getMLText('version')."</th>\n";
		print "<th></th>\n";
		print "</tr></thead><tbody>\n";
		$list = $extmgr->getExtensionListByName($extname);
		foreach($list as $re) {
			$extmgr->checkExtensionByName($re['name'], $re);
			$checkmsgs = $extmgr->getErrorMsgs();
			$needsupdate = !isset($extconf[$re['name']]) || SeedDMS_Extension_Mgr::cmpVersion($re['version'], $extconf[$re['name']]['version']) > 0;
			echo "<tr";
			if(isset($extconf[$re['name']])) {
				if($needsupdate)
					echo " class=\"table-warning warning\"";
				else
					echo " class=\"table-success success\"";
			}
			echo ">";
			echo "<td width=\"32\">".($re['icon-data'] ? '<img width="32" height="32" alt="'.$re['name'].'" title="'.$re['name'].'" src="'.$re['icon-data'].'">' : '')."</td>";
			echo "<td>".$re['title']."<br /><small>".$re['description']."</small>";
			echo "<br /><small>".getMLText('author').": ".$re['author']['name'].", ".$re['author']['company']."</small>";
			if($checkmsgs)
				echo "<div><img src=\"".$this->getImgPath("attention.gif")."\"> ".implode('<br /><img src="'.$this->getImgPath("attention.gif").'"> ', $checkmsgs)."</div>";
			echo "</td>";
			echo "<td nowrap>".$re['version']."<br /><small>".$re['releasedate']."</small></td>";
			echo "<td nowrap>";
			echo "<div class=\"list-action\">";
			if(!$checkmsgs && $extmgr->isWritableExtDir())
				echo "<form style=\"display: inline-block; margin: 0px;\" method=\"post\" action=\"../op/op.ExtensionMgr.php\" id=\"".$re['name']."-import\">".createHiddenFieldWithKey('extensionmgr')."<input type=\"hidden\" name=\"action\" value=\"import\" /><input type=\"hidden\" name=\"currenttab\" value=\"repository\" /><input type=\"hidden\" name=\"url\" value=\"".$re['filename']."\" /><a class=\"import\" data-extname=\"".$re['name']."\" title=\"".getMLText('import_extension')."\"><i class=\"fa fa-download\"></i></a></form>";
			echo "</div>";
			echo "</td>";
			echo "</tr>";
		}
		echo "</tbody></table>\n";
	} /* }}} */

	function info_changelog() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$extmgr = $this->params['extmgr'];
		$extname = $this->params['extname'];
		$extconf = $extmgr->getExtensionConfiguration();

		$list = $extmgr->getExtensionListByName($extname);
		/* Just show the changelog of the latest version */
		$re = array_shift($list);
		echo '<div style="white-space: pre-wrap; font-family: monospace; padding: 0px;">'.$re['changelog']."</div>";
	} /* }}} */

	function changelog() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$extdir = $this->params['extdir'];
		$extmgr = $this->params['extmgr'];
		$extname = $this->params['extname'];
		$extconf = $extmgr->getExtensionConfiguration();

		if(isset($extconf[$extname])) {
			$extconf = $extconf[$extname];
			if(!empty($extconf['changelog']) && file_exists($extdir."/".$extname."/".$extconf['changelog'])) {
				echo '<div style="white-space: pre-wrap; font-family: monospace; padding: 0px;">'.file_get_contents($extdir."/".$extname."/".$extconf['changelog'])."</div>";
			}
		}
	} /* }}} */

	function readme() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$session = $this->params['session'];
		$extdir = $this->params['extdir'];
		$extmgr = $this->params['extmgr'];
		$extname = $this->params['extname'];
		$extconf = $extmgr->getExtensionConfiguration();

		if(isset($extconf[$extname])) {
			$extconf = $extconf[$extname];
			$Parsedown = new Parsedown();
			if(file_exists($extdir."/".$extname."/README.".$session->getLanguage().".md")) {
				echo $Parsedown->text(file_get_contents($extdir."/".$extname."/README.".$session->getLanguage().".md"));
			} elseif(file_exists($extdir."/".$extname."/README.md")) {
				echo $Parsedown->text(file_get_contents($extdir."/".$extname."/README.md"));
			}
		}
	} /* }}} */

	function installedList() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$session = $this->params['session'];
		$httproot = $this->params['httproot'];
		$extmgr = $this->params['extmgr'];
		$extdir = $this->params['extdir'];
		$extconf = $extmgr->getExtensionConfiguration();

		echo "<table id=\"extensionlist\" class=\"table\">\n";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";
		print "<th>".getMLText('name')."</th>\n";
		print "<th>".getMLText('version')."</th>\n";
		print "<th></th>\n";
		print "</tr></thead><tbody>\n";
		$errmsgs = array();
		foreach($extconf as $extname=>$extconf) {
			$errmsgs = array();
			if(!$settings->extensionIsDisabled($extname)) {
//			if(!isset($extconf['disable']) || $extconf['disable'] == false) {
				if(!$extmgr->checkExtensionByDir($extname))
					echo "<tr class=\"table-danger error\" ref=\"".$extname."\">";
				else
					echo "<tr class=\"table-success success\" ref=\"".$extname."\">";
			} else {
				echo "<tr class=\"table-warning warning\" ref=\"".$extname."\">";
			}
			$errmsgs = $extmgr->getErrorMsgs();
			echo "<td width=\"32\">";
			if($extconf['icon'])
				echo "<img width=\"32\" height=\"32\" src=\"".$httproot."ext/".$extname."/".$extconf['icon']."\" alt=\"".$extname."\" title=\"".$extname."\">";
			echo "</td>";
			echo "<td>".$extconf['title'];
			echo "<br /><small>".$extconf['description']."</small>";
			echo "<br /><small>".getMLText('author').": <a href=\"mailto:".htmlspecialchars($extconf['author']['email'])."\">".$extconf['author']['name']."</a>, ".$extconf['author']['company']."</small>";
			if($errmsgs)
				echo "<div><img src=\"".$this->getImgPath("attention.gif")."\"> ".implode('<br /><img src="'.$this->getImgPath("attention.gif").'"> ', $errmsgs)."</div>";
			echo "</td>";
			echo "<td nowrap>".$extconf['version'];
			echo "<br /><small>".$extconf['releasedate']."</small>";
			echo "</td>";
			echo "<td nowrap>";
			echo "<div class=\"list-action\">";
			if(file_exists($extdir."/".$extname."/README.".$session->getLanguage().".md") || file_exists($extdir."/".$extname."/README.md")) {
				echo $this->getModalBoxLink(array('target'=>'extensionReadme', 'remote'=>'out.ExtensionMgr.php?action=readme&extensionname='.$extname, 'class'=>'', 'title'=>'<i class="fa fa-question"></i>', 'attributes'=>array('title'=>getMLText('show_extension_readme'))));
			}
			if(!empty($extconf['changelog']) && file_exists($extdir."/".$extname."/".$extconf['changelog'])) {
				echo $this->getModalBoxLink(array('target'=>'extensionChangelog', 'remote'=>'out.ExtensionMgr.php?action=changelog&extensionname='.$extname, 'class'=>'', 'title'=>'<i class="fa fa-reorder"></i>', 'attributes'=>array('title'=>getMLText('show_extension_changelog'))));
			}
			if($extconf['config'])
				echo "<a href=\"../out/out.Settings.php?currenttab=extensions#".$extname."\" title=\"".getMLText('configure_extension')."\"><i class=\"fa fa-cogs\"></i></a>";
			if($settings->_enableExtensionDownload)
				echo "<form style=\"display: inline-block; margin: 0px;\" method=\"post\" action=\"../op/op.ExtensionMgr.php\" id=\"".$extname."-download\">".createHiddenFieldWithKey('extensionmgr')."<input type=\"hidden\" name=\"action\" value=\"download\" /><input type=\"hidden\" name=\"extname\" value=\"".$extname."\" /><a class=\"download\" data-extname=\"".$extname."\" title=\"".getMLText('download_extension')."\"><i class=\"fa fa-download\"></i></a></form>";
			if(is_writeable($settings->_configFilePath)) {
				if(!$settings->extensionIsDisabled($extname)) {
					echo ' <a href="#" class="toggle" data-extname="'.$extname.'" title="'.getMLText('disable_extension').'"><i class="fa fa-check"</i></a>';
				} else {
					echo ' <a href="#" class="toggle" data-extname="'.$extname.'" title="'.getMLText('enable_extension').'"><i class="fa fa-minus"></i></a>';
				}
			}
			echo "</div>";
			echo "</td>";
			echo "</tr>\n";
		}
		echo "</tbody></table>\n";
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$httproot = $this->params['httproot'];
		$extdir = $this->params['extdir'];
		$version = $this->params['version'];
		$extmgr = $this->params['extmgr'];
		$extconf = $extmgr->getExtensionConfiguration();
		$currenttab = $this->params['currenttab'];
	
		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("extension_manager"));
		$this->rowStart();
		$this->columnStart(4);
		if($settings->_enableExtensionImport && $extmgr->isWritableExtDir()) {
?>
		<form class="form-horizontal" method="post" enctype="multipart/form-data" action="../op/op.ExtensionMgr.php">
			<?= createHiddenFieldWithKey('extensionmgr') ?>
			<input type="hidden" name="action" value="upload" />
<?php
			$this->formField(
				getMLText("extension_archive"),
				$this->getFileChooserHtml('userfile', false)
			);
			$this->formSubmit("<i class=\"fa fa-upload\"></i> ".getMLText('import_extension'));
?>
		</form>
<?php
		} else {
			if($settings->_enableExtensionImport)
				echo "<div class=\"alert alert-warning\">".getMLText('extension_mgr_no_upload')."</div>";
			else
				echo "<div class=\"alert alert-warning\">".getMLText('extension_mgr_upload_disabled')."</div>";
		}
		if(!is_writeable($settings->_configFilePath)) {
			$this->warningMsg(getMLText("extension_mgr_no_toggle"));
		}
		$this->columnEnd();
		$this->columnStart(8);
?>
		<ul class="nav nav-pills" id="extensionstab" role="tablist">
<?php $this->showPaneHeader('installed', getMLText('extension_mgr_installed'), (!$currenttab || $currenttab == 'installed')); ?>
<?php $this->showPaneHeader('repository', getMLText('extension_mgr_repository'), ($currenttab == 'repository')); ?>
		</ul>
		<div class="tab-content">
			<?php $this->showStartPaneContent('installed', (!$currenttab || $currenttab == 'installed')); ?>
			<input id="extensionfilter" class="form-control" type="text" placeholder="<?= getMLText('type_to_filter'); ?>">
	<div class="ajax" data-view="ExtensionMgr" data-action="installedList" data-afterload="()=>{filterList();}"></div>
<?php
//		$this->installedList();
?>
<form action="../op/op.ExtensionMgr.php" name="form1" method="post">
  <?php echo createHiddenFieldWithKey('extensionmgr'); ?>
	<input type="hidden" name="action" value="refresh" />
	<p><?php $this->formSubmit("<i class=\"fa fa-refresh\"></i> " . getMLText('refresh'));?></p>
</form>
			<?php $this->showEndPaneContent('installed', $currenttab); ?>

			<?php $this->showStartPaneContent('repository', ($currenttab == 'repository')); ?>
<?php
		if($extmgr->getRepositoryUrl()) {
		echo "<table class=\"table\">\n";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";
		print "<th>".getMLText('name')."</th>\n";
		print "<th>".getMLText('version')."</th>\n";
		print "<th></th>\n";
		print "</tr></thead><tbody>\n";
		$list = $extmgr->getExtensionList();
		foreach($list as $en=>$re) {
			if(!$re)
				continue;
			$extmgr->checkExtensionByName($en, $re);
			$checkmsgs = $extmgr->getErrorMsgs();
			$needsupdate = !isset($extconf[$re['name']]) || SeedDMS_Extension_Mgr::cmpVersion($re['version'], $extconf[$re['name']]['version']) > 0;
			echo "<tr";
			if(isset($extconf[$re['name']])) {
				if($needsupdate)
					echo " class=\"table-warning warning\"";
				else
					echo " class=\"table-success success\"";
			}
			echo ">";
			echo "<td width=\"32\">".($re['icon-data'] ? '<img width="32" height="32" alt="'.$re['name'].'" title="'.$re['name'].'" src="'.$re['icon-data'].'">' : '')."</td>";
			echo "<td>".$re['title'];
			echo "<br /><small>".$re['description']."</small>";
			echo "<br /><small>".getMLText('author').": ".$re['author']['name'].", ".$re['author']['company']."</small>";
			if($checkmsgs)
				echo "<div><img src=\"".$this->getImgPath("attention.gif")."\"> ".implode('<br /><img src="'.$this->getImgPath("attention.gif").'"> ', $checkmsgs)."</div>";
			echo "</td>";
			echo "<td nowrap>".$re['version']."<br /><small>".$re['releasedate']."</small></td>";
			echo "<td nowrap>";
			echo "<div class=\"list-action\">";
			echo $this->getModalBoxLink(array('target'=>'extensionInfo', 'remote'=>'out.ExtensionMgr.php?action=info_versions&extensionname='.$re['name'], 'class'=>'', 'title'=>'<i class="fa fa-list-ol"></i>'));
			echo $this->getModalBoxLink(array('target'=>'extensionChangelog', 'remote'=>'out.ExtensionMgr.php?action=info_changelog&extensionname='.$re['name'], 'class'=>'', 'title'=>'<i class="fa fa-reorder"></i>'));
			if(!$checkmsgs && $settings->_enableExtensionImportFromRepository && $extmgr->isWritableExtDir())
				echo "<form style=\"display: inline-block; margin: 0px;\" method=\"post\" action=\"../op/op.ExtensionMgr.php\" id=\"".$re['name']."-import\">".createHiddenFieldWithKey('extensionmgr')."<input type=\"hidden\" name=\"action\" value=\"import\" /><input type=\"hidden\" name=\"currenttab\" value=\"repository\" /><input type=\"hidden\" name=\"url\" value=\"".$re['filename']."\" /><a class=\"import\" data-extname=\"".$re['name']."\" title=\"".getMLText('import_extension')."\"><i class=\"fa fa-download\"></i></a></form>";
			echo "</div>";
			echo "</td>";
			echo "</tr>";
		}
		echo "</tbody></table>\n";
?>
				<div>
					<form method="post" action="../op/op.ExtensionMgr.php">
					<?= createHiddenFieldWithKey('extensionmgr'); ?>
					<input type="hidden" name="action" value="getlist" />
					<input type="hidden" name="currenttab" value="repository" />
					<input type="hidden" name="forceupdate" value="1" />
          <?php $this->formSubmit("<i class=\"fa fa-refresh\"></i> " . getMLText('force_update'));?>
					</form>
				</div>
<?php
		}
		$this->showEndPaneContent('repository', $currenttab);
?>
		</div>
<?php
		$this->columnEnd();
		$this->rowEnd();
		echo $this->getModalBox(array('id'=>'extensionInfo', 'title'=>getMLText('extension_version_list'), 'content'=>'<p>'.getMLText('extension_loading').'</p>', 'buttons'=>array(array('title'=>getMLText('close')))));
		echo $this->getModalBox(array('id'=>'extensionChangelog', 'title'=>getMLText('extension_changelog'), 'content'=>'<p>'.getMLText('changelog_loading').'</p>', 'buttons'=>array(array('title'=>getMLText('close')))));
		echo $this->getModalBox(array('id'=>'extensionReadme', 'title'=>getMLText('extension_readme'), 'content'=>'<p>'.getMLText('readme_loading').'</p>', 'buttons'=>array(array('title'=>getMLText('close')))));
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
