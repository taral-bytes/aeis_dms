<?php
/**
 * Implementation of Settings view
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
 * Class which outputs the html page for Settings view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Settings extends SeedDMS_Theme_Style {

	protected function showStartPaneContent($name, $isactive) { /* {{{ */
		parent::showStartPaneContent($name, $isactive);
		$this->contentContainerStart();
		echo '<table class="table-condensed table-sm" style="table-layout: fixed;">';
		echo '<tr><td width="20%"></td><td width="80%"></td></tr>';
	} /* }}} */

	protected function showEndPaneContent($name, $currenttab) { /* {{{ */
		echo '</table>';
		$this->contentContainerEnd();
		parent::showEndPaneContent($name, $currenttab);
	} /* }}} */

	protected function getTextField($name, $value, $type='', $placeholder='') { /* {{{ */
		$html = '';
		if($type == 'textarea' || ($type != 'password' && strlen($value) > 80))
			$html .= '<textarea class="form-control input-xxlarge" name="'.$name.'">'.$value.'</textarea>';
		else {
			if(strlen($value) > 40)
				$class = 'input-xxlarge';
			elseif(strlen($value) > 30)
				$class = 'input-xlarge';
			elseif(strlen($value) > 18)
				$class = 'input-large';
			elseif(strlen($value) > 12)
				$class = 'input-medium';
			else
				$class = 'input-small';
			$html .= '<input '.($type=='password' ? 'type="password"' : ($type=='number' ? 'type="number"' : 'type="text"')).' class="form-control '.$class.'" name="'.$name.'" value="'.$value.'" placeholder="'.$placeholder.'"/>';
		}
		return $html;
	} /* }}} */

	protected function showTextField($name, $value, $type='', $placeholder='') { /* {{{ */
		echo $this->getTextField($name, $value, $type, $placeholder);
	} /* }}} */

	/**
	 * Place arbitrary html in a headline
	 *
	 * @param string $text html code to be shown as headline
	 */
	protected function showRawConfigHeadline($text) { /* {{{ */
?>
      <tr><td colspan="2"><b><?= $text ?></b></td></tr>
<?php
	} /* }}} */

	/**
	 * Place text in a headline
	 *
	 * @param string $text text to be shown as headline
	 */
	protected function showConfigHeadline($title) { /* {{{ */
		$this->showRawConfigHeadline(htmlspecialchars(getMLText($title)));
	} /* }}} */

	/**
	 * Show a text input configuration option
	 *
	 * @param string $title title of the option
	 * @param string $name name of html input field
	 * @param string $type can be 'password', 'array'
	 * @param string $placeholder placeholder for input field
	 */
	protected function isVisible($name) { /* {{{ */
		$settings = $this->params['settings'];
		if(!($hcf = $settings->_hiddenConfFields))
			return true;
		if(in_array($name, $hcf))
			return false;
		return true;
	} /* }}} */

	/**
	 * Show a text input configuration option
	 *
	 * @param string $title title of the option
	 * @param string $name name of html input field
	 * @param string $type can be 'password', 'array'
	 * @param string $placeholder placeholder for input field
	 */
	protected function showConfigText($title, $name, $type='', $placeholder='') { /* {{{ */
		$settings = $this->params['settings'];
?>
			<tr title="<?= getMLText($title."_desc") ?>">
				<td><?= getMLText($title) ?></td>
<?php
		if($type === 'array')
			$value = $settings->arrayToString($settings->{"_".$name});
		else
			$value = $settings->{"_".$name};
		echo "				<td>";
		if($this->isVisible($name))
			$this->showTextField($name, $value, ($type=='password' || $type=='textarea' ? $type : ''), $placeholder);
		else
			echo getMLText('settings_conf_field_not_editable');
		echo "</td>\n";
?>
			</tr>
<?php
	} /* }}} */

	/**
	 * Show a configuration option with arbitrary html content
	 *
	 * @param string $title title of the option
	 * @param string $rawdata html data
	 */
	protected function showConfigPlain($title, $title_desc, $rawdata) { /* {{{ */
		$settings = $this->params['settings'];
?>
      <tr title="<?= htmlspecialchars($title_desc) ?>">
				<td><?= $title ?></td>
				<td><?= $rawdata ?></td>
			</tr>
<?php
	} /* }}} */

	/**
	 * Show a checkbox configuration option
	 *
	 * @param string $title title of the option
	 * @param string $name name of html input field
	 */
	protected function showConfigCheckbox($title, $name) { /* {{{ */
		$settings = $this->params['settings'];
?>
      <tr title="<?= getMLText($title."_desc") ?>">
        <td><?= getMLText($title) ?></td>
				<td><input name="<?= $name ?>" type="checkbox" <?php if ($settings->{"_".$name}) echo "checked" ?> /></td>
      </tr>
<?php
	} /* }}} */

	protected function showConfigOption($title, $name, $values, $multiple=false, $translate=false) { /* {{{ */
		$settings = $this->params['settings'];
		$isass = count(array_filter(array_keys($values), 'is_string')) > 0;
//		var_dump($values);
//		echo $isass ? 'asso' : 'indexed';
?>
      <tr title="<?= getMLText($title."_desc") ?>">
        <td><?= getMLText($title) ?></td>
				<td>
<?php if($multiple) { ?>
					<select class="chzn-select form-control" style="width: 100%;" name="<?= $name ?>[]" multiple>
<?php } else { ?>
					<select class="chzn-select form-control" style="width: 100%;" name="<?= $name ?>">
<?php }
		foreach($values as $i=>$value) {
			$optval = trim($isass ? $i : $value);
			echo '<option value="' . $optval . '" ';
			if (($multiple && in_array($optval, $settings->{"_".$name})) || (!$multiple && $optval == $settings->{"_".$name}))
				echo "selected";
			echo '>' . ($translate ? getMLText($value) : $value). '</option>';
		}
?>
          </select>
        </td>
      </tr>
<?php
	} /* }}} */

	protected function showConfigUser($title, $name, $allowempty=false, $multiple=false, $size=0) { /* {{{ */
		$settings = $this->params['settings'];
		$dms = $this->params['dms'];
?>
      <tr title="<?= getMLText($title."_desc") ?>">
        <td><?= getMLText($title) ?></td>
				<td>
<?php
		$users = $dms->getAllUsers();
		if($users) {
			if(is_array($settings->{"_".$name}))
				$selections = $settings->{"_".$name};
			else
				$selections = explode(',', $settings->{"_".$name});
			echo "<select class=\"chzn-select\"".($allowempty ? " data-allow-clear=\"true\"" : "")."\" name=\"".$name.($multiple ? "[]" : "")."\"".($multiple ? "  multiple" : "").($size ? "  size=\"".$size."\"" : "")." data-placeholder=\"".getMLText("select_user")."\">";
			if($allowempty)
				echo "<option value=\"\"></option>";
			foreach($users as $curuser) {
				echo "<option value=\"".$curuser->getID()."\"";
				if(in_array($curuser->getID(), $selections))
					echo " selected";
				echo ">".htmlspecialchars($curuser->getLogin()." - ".$curuser->getFullName())."</option>";
			}
			echo "</select>";
		}
?>
				</td>
			</tr>
<?php
	} /* }}} */

	protected function showConfigFolder($title, $name, $allowempty=false, $multiple=false, $size=0) { /* {{{ */
		$settings = $this->params['settings'];
		$dms = $this->params['dms'];
?>
      <tr title="<?= getMLText($title."_desc") ?>">
        <td><?= getMLText($title) ?>:</td>
				<td>
<?php $this->printFolderChooserHtml($name, M_READWRITE, -1, $dms->getFolder($settings->{"_".$name}), $name);?>
				</td>
			</tr>
<?php
	} /* }}} */

	function js() { /* {{{ */
		$extmgr = $this->params['extmgr'];

		header('Content-Type: application/javascript; charset=UTF-8');
?>
function scrollToTargetAdjusted(target){
	var element = document.getElementById(target);
	var headerOffset = 60;
	var elementPosition = element.getBoundingClientRect().top;
	var offsetPosition = elementPosition + window.pageYOffset - headerOffset;

	window.scrollTo({
		top: offsetPosition,
		behavior: "smooth"
	});
}
		$(document).ready( function() {
			$('#settingstab li a').click(function(event) {
				$('#currenttab').val($(event.currentTarget).data('target').substring(1));
			});

			$('a.sendtestmail').click(function(ev){
				ev.preventDefault();
				$.ajax({url: '../op/op.Ajax.php',
					type: 'GET',
					dataType: "json",
					data: {command: 'testmail'},
					success: function(data) {
						noty({
							text: data.msg,
							type: (data.error) ? 'error' : 'success',
							dismissQueue: true,
							layout: 'topRight',
							theme: 'defaultTheme',
							timeout: 1500,
						});
						if(data.data) {
							$('#maildebug').text(data.data);
						}
					}
				}); 
			});

			$('a.scrollto').click(function(event) {
console.log($(event.currentTarget).data('target').substring(1));
				scrollToTargetAdjusted($(event.currentTarget).data('target').substring(1));
			});
		});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$users = $this->params['allusers'];
		$groups = $this->params['allgroups'];
		$settings = $this->params['settings'];
		$extmgr = $this->params['extmgr'];
		$currenttab = $this->params['currenttab'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("settings"));

		$this->rowStart();
		$this->columnStart(8);
?>
  <form action="../op/op.Settings.php" method="post" enctype="multipart/form-data" name="form0" >
	<?php echo createHiddenFieldWithKey('savesettings'); ?>
  <input type="hidden" name="action" value="saveSettings" />
	<input type="hidden" id="currenttab" name="currenttab" value="<?php echo $currenttab ? $currenttab : 'site'; ?>" />

  <ul class="nav nav-pills" id="settingstab" role="tablist">
<?php $this->showPaneHeader('site', getMLText('settings_Site'), (!$currenttab || $currenttab == 'site')); ?>
<?php $this->showPaneHeader('system', getMLText('settings_System'), ($currenttab == 'system')); ?>
<?php $this->showPaneHeader('advanced', getMLText('settings_Advanced'), ($currenttab == 'advanced')); ?>
<?php $this->showPaneHeader('extensions', getMLText('settings_Extensions'), ($currenttab == 'extensions')); ?>
	</ul>

	<div class="tab-content">
<?php
$this->showStartPaneContent('site', (!$currenttab || $currenttab == 'site'));
?>
      <!--
        -- SETTINGS - SITE - DISPLAY
			-->
<?php $this->showConfigHeadline('settings_Display'); ?>
<?php $this->showConfigText('settings_siteName', 'siteName'); ?>
<?php $this->showConfigText('settings_footNote', 'footNote'); ?>
<?php $this->showConfigCheckbox('settings_printDisclaimer', 'printDisclaimer'); ?>
<?php $this->showConfigOption('settings_available_languages', 'availablelanguages', getAvailableLanguages(), true, true); ?>
<?php $this->showConfigOption('settings_language', 'language', getAvailableLanguages(), false, true); ?>
<?php $this->showConfigText('settings_dateformat', 'dateformat'); ?>
<?php $this->showConfigText('settings_datetimeformat', 'datetimeformat'); ?>
<?php $this->showConfigOption('settings_theme', 'theme', UI::getStyles(), false, false); ?>
<?php $this->showConfigCheckbox('settings_overrideTheme', 'overrideTheme'); ?>
<?php $this->showConfigCheckbox('settings_onePageMode', 'onePageMode'); ?>
<?php $this->showConfigText('settings_previewWidthList', 'previewWidthList'); ?>
<?php $this->showConfigText('settings_previewWidthMenuList', 'previewWidthMenuList'); ?>
<?php $this->showConfigText('settings_previewWidthDropFolderList', 'previewWidthDropFolderList'); ?>
<?php $this->showConfigText('settings_previewWidthDetail', 'previewWidthDetail'); ?>
<?php $this->showConfigCheckbox('settings_showFullPreview', 'showFullPreview'); ?>
<?php $this->showConfigCheckbox('settings_convertToPdf', 'convertToPdf'); ?>
<?php $this->showConfigText('settings_maxItemsPerPage', 'maxItemsPerPage'); ?>
<?php $this->showConfigText('settings_incItemsPerPage', 'incItemsPerPage'); ?>
<?php $this->showConfigCheckbox('settings_markdownComments', 'markdownComments'); ?>

      <!--
        -- SETTINGS - SITE - EDITION
      -->
<?php $this->showConfigHeadline('settings_Edition'); ?>
<?php $this->showConfigCheckbox('settings_strictFormCheck', 'strictFormCheck'); ?>
<?php $this->showConfigCheckbox('settings_inlineEditing', 'inlineEditing'); ?>
<?php $this->showConfigOption('settings_noDocumentFormFields', 'noDocumentFormFields', array('comment', 'keywords', 'categories', 'sequence', 'expires', 'version', 'version_comment', 'notification'), true, true); ?>
<?php $this->showConfigOption('settings_noFolderFormFields', 'noFolderFormFields', array('comment', 'sequence', 'notification'), true, true); ?>
<?php $this->showConfigText('settings_viewOnlineFileTypes', 'viewOnlineFileTypes', 'array'); ?>
<?php $this->showConfigText('settings_editOnlineFileTypes', 'editOnlineFileTypes', 'array'); ?>
<?php $this->showConfigCheckbox('settings_enableConverting', 'enableConverting'); ?>
<?php $this->showConfigCheckbox('settings_enableEmail', 'enableEmail'); ?>
<?php $this->showConfigCheckbox('settings_enableUsersView', 'enableUsersView'); ?>
<?php $this->showConfigCheckbox('settings_enableFullSearch', 'enableFullSearch'); ?>
<?php $this->showConfigText('settings_maxSizeForFullText', 'maxSizeForFullText'); ?>
<?php
$fullsearchengines = array(
	'lucene'=>'settings_fullSearchEngine_vallucene',
	'sqlitefts'=>'settings_fullSearchEngine_valsqlitefts'
);
if(($kkk = $this->callHook('getFullSearchEngine')) && is_array($kkk))
	$fullsearchengines = array_merge($fullsearchengines, $kkk);
?>
<?php $this->showConfigOption('settings_fullSearchEngine', 'fullSearchEngine', $fullsearchengines, false, true); ?>
<?php $this->showConfigOption('settings_defaultSearchMethod', 'defaultSearchMethod', array('database'=>'settings_defaultSearchMethod_valdatabase', 'fulltext'=>'settings_defaultSearchMethod_valfulltext'), false, true); ?>
<?php $this->showConfigCheckbox('settings_showSingleSearchHit', 'showSingleSearchHit'); ?>
<?php $this->showConfigOption('settings_suggestTerms', 'suggestTerms', array('title','comment', 'keywords', 'content'), true, true); ?>
<?php $this->showConfigText('settings_stopWordsFile', 'stopWordsFile'); ?>
<?php $this->showConfigCheckbox('settings_enableClipboard', 'enableClipboard'); ?>
<?php $this->showConfigCheckbox('settings_enableMenuTasks', 'enableMenuTasks'); ?>
<?php $this->showConfigOption('settings_tasksInMenu', 'tasksInMenu', array('review'=>'settings_tasksInMenu_review', 'approval'=>'settings_tasksInMenu_approval', 'workflow'=>'settings_tasksInMenu_workflow', 'receipt'=>'settings_tasksInMenu_receipt', 'revision'=>'settings_tasksInMenu_revision', 'needscorrection'=>'settings_tasksInMenu_needscorrection', 'rejected'=>'settings_tasksInMenu_rejected', 'checkedout'=>'settings_tasksInMenu_checkedout'), true, true); ?>
<?php $this->showConfigCheckbox('settings_enableDropFolderList', 'enableDropFolderList'); ?>
<?php $this->showConfigCheckbox('settings_enableSessionList', 'enableSessionList'); ?>
<?php $this->showConfigCheckbox('settings_enableDropUpload', 'enableDropUpload'); ?>
<?php $this->showConfigCheckbox('settings_enableMultiUpload', 'enableMultiUpload'); ?>
<?php $this->showConfigCheckbox('settings_enableFolderTree', 'enableFolderTree'); ?>
<?php $this->showConfigOption('settings_expandFolderTree', 'expandFolderTree', array(' 0'=>'settings_expandFolderTree_val0', ' 1'=>'settings_expandFolderTree_val1', ' 2'=>'settings_expandFolderTree_val2'), false, true); ?>
<?php $this->showConfigCheckbox('settings_enableRecursiveCount', 'enableRecursiveCount'); ?>
<?php $this->showConfigText('settings_maxRecursiveCount', 'maxRecursiveCount'); ?>
<?php $this->showConfigCheckbox('settings_enableLanguageSelector', 'enableLanguageSelector'); ?>
<?php $this->showConfigCheckbox('settings_enableHelp', 'enableHelp'); ?>
<?php $this->showConfigCheckbox('settings_enableThemeSelector', 'enableThemeSelector'); ?>
<?php $this->showConfigOption('settings_sortUsersInList', 'sortUsersInList', array(' '=>'settings_sortUsersInList_val_login', 'fullname'=>'settings_sortUsersInList_val_fullname'), false, true); ?>
<?php $this->showConfigOption('settings_sortFoldersDefault', 'sortFoldersDefault', array('u'=>'settings_sortFoldersDefault_val_unsorted', 's'=>'settings_sortFoldersDefault_val_sequence', 'n'=>'settings_sortFoldersDefault_val_name'), false, true); ?>
<?php $this->showConfigOption('settings_defaultDocPosition', 'defaultDocPosition', array('end'=>'settings_defaultDocPosition_val_end', 'start'=>'settings_defaultDocPosition_val_start'), false, true); ?>
<?php $this->showConfigOption('settings_defaultFolderPosition', 'defaultFolderPosition', array('end'=>'settings_defaultDocPosition_val_end', 'start'=>'settings_defaultDocPosition_val_start'), false, true); ?>
<?php $this->showConfigFolder('settings_libraryFolder', 'libraryFolder'); ?>

      <!--
        -- SETTINGS - SITE - WEBDAV
      -->
<?php $this->showConfigHeadline('settings_webdav'); ?>
<?php $this->showConfigCheckbox('settings_enableWebdavReplaceDoc', 'enableWebdavReplaceDoc'); ?>

      <!--
        -- SETTINGS - SITE - CALENDAR
      -->
<?php $this->showConfigHeadline('settings_Calendar'); ?>
<?php $this->showConfigCheckbox('settings_enableCalendar', 'enableCalendar'); ?>
<?php $this->showConfigOption('settings_calendarDefaultView', 'calendarDefaultView', array('w'=>'week_view', 'm'=>'month_view', 'y'=>'year_view'), false, true); ?>
<?php $this->showConfigOption('settings_firstDayOfWeek', 'firstDayOfWeek', array(' 0'=>'sunday', ' 1'=>'monday', ' 2'=>'tuesday', ' 3'=>'wednesday', ' 4'=>'thursday', ' 5'=>'friday', ' 6'=>'saturday'), false, true); ?>

      <!--
        -- SETTINGS - SITE - EXTENSIONMGR
      -->
<?php $this->showConfigHeadline('settings_ExtensionMgr'); ?>
<?php $this->showConfigCheckbox('settings_enableExtensionDownload', 'enableExtensionDownload'); ?>
<?php $this->showConfigCheckbox('settings_enableExtensionImport', 'enableExtensionImport'); ?>
<?php $this->showConfigCheckbox('settings_enableExtensionImportFromRepository', 'enableExtensionImportFromRepository'); ?>
<?php
	$this->showEndPaneContent('site', $currenttab);

	$this->showStartPaneContent('system', $currenttab == 'system');
?>
     <!--
        -- SETTINGS - SYSTEM - SERVER
      -->
<?php $this->showConfigHeadline('settings_Server'); ?>
<?php $this->showConfigText('settings_rootDir', 'rootDir'); ?>
<?php $this->showConfigText('settings_baseUrl', 'baseUrl'); ?>
<?php $this->showConfigText('settings_httpRoot', 'httpRoot'); ?>
<?php $this->showConfigText('settings_contentDir', 'contentDir'); ?>
<?php $this->showConfigText('settings_backupDir', 'backupDir'); ?>
<?php $this->showConfigText('settings_cacheDir', 'cacheDir'); ?>
<?php $this->showConfigText('settings_stagingDir', 'stagingDir'); ?>
<?php $this->showConfigText('settings_luceneDir', 'luceneDir'); ?>
<?php $this->showConfigText('settings_dropFolderDir', 'dropFolderDir'); ?>
<?php $this->showConfigText('settings_checkOutDir', 'checkOutDir'); ?>
<?php $this->showConfigCheckbox('settings_createCheckOutDir', 'createCheckOutDir'); ?>
<?php $this->showConfigText('settings_repositoryUrl', 'repositoryUrl'); ?>
<?php $this->showConfigText('settings_proxyUrl', 'proxyUrl'); ?>
<?php $this->showConfigText('settings_proxyUser', 'proxyUser'); ?>
<?php $this->showConfigText('settings_proxyUPassword', 'proxyPassword', 'password'); ?>
<?php $this->showConfigCheckbox('settings_logFileEnable', 'logFileEnable'); ?>
<?php $this->showConfigOption('settings_logFileRotation', 'logFileRotation', array('h'=>'hourly', 'd'=>'daily', 'm'=>'monthly'), false, true); ?>
<?php $this->showConfigCheckbox('settings_enableLargeFileUpload', 'enableLargeFileUpload'); ?>
<?php $this->showConfigText('settings_partitionSize', 'partitionSize'); ?>
<?php $this->showConfigText('settings_maxUploadSize', 'maxUploadSize'); ?>
<?php $this->showConfigCheckbox('settings_enableXsendfile', 'enableXsendfile'); ?>
      <!--
        -- SETTINGS - SYSTEM - AUTHENTICATION
      -->
<?php $this->showConfigHeadline('settings_Authentication'); ?>
<?php $this->showConfigCheckbox('settings_enableGuestLogin', 'enableGuestLogin'); ?>
<?php $this->showConfigCheckbox('settings_enableGuestAutoLogin', 'enableGuestAutoLogin'); ?>
<?php $this->showConfigCheckbox('settings_enable2FactorAuthentication', 'enable2FactorAuthentication'); ?>
<?php $this->showConfigCheckbox('settings_restricted', 'restricted'); ?>
<?php $this->showConfigCheckbox('settings_enableUserImage', 'enableUserImage'); ?>
<?php $this->showConfigCheckbox('settings_disableSelfEdit', 'disableSelfEdit'); ?>
<?php $this->showConfigCheckbox('settings_enablePasswordForgotten', 'enablePasswordForgotten'); ?>
<?php $this->showConfigText('settings_passwordStrength', 'passwordStrength'); ?>
<?php $this->showConfigOption('settings_passwordStrengthAlgorithm', 'passwordStrengthAlgorithm', array('simple'=>'settings_passwordStrengthAlgorithm_valsimple', 'advanced'=>'settings_passwordStrengthAlgorithm_valadvanced'), false, true); ?>
<?php $this->showConfigText('settings_passwordExpiration', 'passwordExpiration'); ?>
<?php $this->showConfigText('settings_passwordHistory', 'passwordHistory'); ?>
<?php $this->showConfigText('settings_loginFailure', 'loginFailure'); ?>
<?php $this->showConfigUser('settings_autoLoginUser', 'autoLoginUser', true); ?>
<?php $this->showConfigText('settings_quota', 'quota'); ?>
<?php $this->showConfigUser('settings_undelUserIds', 'undelUserIds', true, true); ?>
<?php $this->showConfigText('settings_encryptionKey', 'encryptionKey'); ?>
<?php $this->showConfigText('settings_cookieLifetime', 'cookieLifetime'); ?>
<?php $this->showConfigOption('settings_defaultAccessDocs', 'defaultAccessDocs', array(' 0'=>'inherited', ' '.M_NONE=>'access_mode_none', ' '.M_READ=>'access_mode_read', ' '.M_READWRITE=>'access_mode_readwrite'), false, true); ?>

      <!-- TODO Connectors -->

     <!--
        -- SETTINGS - SYSTEM - DATABASE
      -->
<?php $this->showConfigHeadline('settings_Database'); ?>
<?php $this->showConfigText('settings_dbDriver', 'dbDriver'); ?>
<?php $this->showConfigText('settings_dbHostname', 'dbHostname'); ?>
<?php $this->showConfigText('settings_dbDatabase', 'dbDatabase'); ?>
<?php $this->showConfigText('settings_dbUser', 'dbUser'); ?>
<?php $this->showConfigText('settings_dbPass', 'dbPass', 'password'); ?>

     <!--
        -- SETTINGS - SYSTEM - SMTP
			-->
<?php $this->showConfigHeadline('settings_SMTP'); ?>
<?php $this->showConfigText('settings_smtpServer', 'smtpServer'); ?>
<?php $this->showConfigText('settings_smtpPort', 'smtpPort'); ?>
<?php $this->showConfigText('settings_smtpSendFrom', 'smtpSendFrom'); ?>
<?php $this->showConfigText('settings_smtpUser', 'smtpUser'); ?>
<?php $this->showConfigText('settings_smtpPassword', 'smtpPassword', 'password'); ?>
<?php $this->showConfigPlain(htmlspecialchars(getMLText('settings_smtpSendTestMail')), htmlspecialchars(getMLText('settings_smtpSendTestMail_desc')), '<a class="btn btn-secondary sendtestmail">'.getMLText('send_test_mail').'</a><div><pre id="maildebug">You will see debug messages here</pre></div>'); ?>
<?php
	$this->showEndPaneContent('system', $currenttab);

	$this->showStartPaneContent('advanced', $currenttab == 'advanced');
?>
      <!--
        -- SETTINGS - ADVANCED - DISPLAY
      -->
<?php $this->showConfigHeadline('settings_Display'); ?>
<?php $this->showConfigText('settings_siteDefaultPage', 'siteDefaultPage'); ?>
<?php $this->showConfigText('settings_rootFolderID', 'rootFolderID'); ?>
<?php $this->showConfigCheckbox('settings_useHomeAsRootFolder', 'useHomeAsRootFolder'); ?>
<?php $this->showConfigCheckbox('settings_showMissingTranslations', 'showMissingTranslations'); ?>

      <!--
        -- SETTINGS - ADVANCED - AUTHENTICATION
      -->
<?php $this->showConfigHeadline('settings_Authentication'); ?>
<?php $this->showConfigUser('settings_guestID', 'guestID', true); ?>
<?php $this->showConfigText('settings_adminIP', 'adminIP'); ?>
<?php $this->showConfigText('settings_apiKey', 'apiKey'); ?>
<?php //$this->showConfigText('settings_apiUserId', 'apiUserId'); ?>
<?php $this->showConfigUser('settings_apiUserId', 'apiUserId', true); ?>
<?php $this->showConfigText('settings_apiOrigin', 'apiOrigin'); ?>

      <!--
        -- SETTINGS - ADVANCED - EDITION
      -->
<?php $this->showConfigHeadline('settings_Edition'); ?>
<?php $this->showConfigOption('settings_workflowMode', 'workflowMode', array('traditional'=>'settings_workflowMode_valtraditional', 'traditional_only_approval'=>'settings_workflowMode_valtraditional_only_approval', 'advanced'=>'settings_workflowMode_valadvanced', 'none'=>'settings_workflowMode_valnone'), false, true); ?>
<?php $this->showConfigCheckbox('settings_enableReceiptWorkflow', 'enableReceiptWorkflow'); ?>
<?php $this->showConfigCheckbox('settings_enableReceiptReject', 'enableReceiptReject'); ?>
<?php $this->showConfigCheckbox('settings_enableRevisionWorkflow', 'enableRevisionWorkflow'); ?>
<?php $this->showConfigCheckbox('settings_enableRevisionOneVoteReject', 'enableRevisionOneVoteReject'); ?>
<?php $this->showConfigText('settings_versioningFileName', 'versioningFileName'); ?>
<?php $this->showConfigText('settings_presetExpirationDate', 'presetExpirationDate'); ?>
<?php $this->showConfigOption('settings_initialDocumentStatus', 'initialDocumentStatus', array(' '.S_RELEASED=>'settings_initialDocumentStatus_released', ' '.S_DRAFT=>'settings_initialDocumentStatus_draft'), false, true); ?>
<?php $this->showConfigCheckbox('settings_allowReviewerOnly', 'allowReviewerOnly'); ?>
<?php $this->showConfigCheckbox('settings_allowChangeRevAppInProcess', 'allowChangeRevAppInProcess'); ?>
<?php $this->showConfigCheckbox('settings_enableAdminRevApp', 'enableAdminRevApp'); ?>
<?php $this->showConfigCheckbox('settings_enableOwnerRevApp', 'enableOwnerRevApp'); ?>
<?php $this->showConfigCheckbox('settings_enableSelfRevApp', 'enableSelfRevApp'); ?>
<?php $this->showConfigCheckbox('settings_enableUpdateRevApp', 'enableUpdateRevApp'); ?>
<?php $this->showConfigCheckbox('settings_enableRemoveRevApp', 'enableRemoveRevApp'); ?>
<?php $this->showConfigCheckbox('settings_enableSelfReceipt', 'enableSelfReceipt'); ?>
<?php $this->showConfigCheckbox('settings_enableAdminReceipt', 'enableAdminReceipt'); ?>
<?php $this->showConfigCheckbox('settings_enableOwnerReceipt', 'enableOwnerReceipt'); ?>
<?php $this->showConfigCheckbox('settings_enableUpdateReceipt', 'enableUpdateReceipt'); ?>
<?php $this->showConfigCheckbox('settings_enableFilterReceipt', 'enableFilterReceipt'); ?>
<?php $this->showConfigCheckbox('settings_enableVersionDeletion', 'enableVersionDeletion'); ?>
<?php $this->showConfigCheckbox('settings_enableVersionModification', 'enableVersionModification'); ?>
<?php $this->showConfigCheckbox('settings_enableDuplicateDocNames', 'enableDuplicateDocNames'); ?>
<?php $this->showConfigCheckbox('settings_enableDuplicateSubFolderNames', 'enableDuplicateSubFolderNames'); ?>
<?php $this->showConfigCheckbox('settings_enableCancelCheckout', 'enableCancelCheckout'); ?>
<?php $this->showConfigCheckbox('settings_overrideMimeType', 'overrideMimeType'); ?>
<?php $this->showConfigCheckbox('settings_advancedAcl', 'advancedAcl'); ?>
<?php $this->showConfigCheckbox('settings_removeFromDropFolder', 'removeFromDropFolder'); ?>
<?php $this->showConfigCheckbox('settings_uploadedAttachmentIsPublic', 'uploadedAttachmentIsPublic'); ?>

      <!--
        -- SETTINGS - ADVANCED - NOTIFICATION
      -->
<?php $this->showConfigHeadline('settings_Notification'); ?>
<?php $this->showConfigCheckbox('settings_enableOwnerNotification', 'enableOwnerNotification'); ?>
<?php $this->showConfigCheckbox('settings_enableNotificationAppRev', 'enableNotificationAppRev'); ?>
<?php $this->showConfigCheckbox('settings_enableNotificationWorkflow', 'enableNotificationWorkflow'); ?>

      <!--
        -- SETTINGS - ADVANCED - SERVER
      -->
<?php $this->showConfigHeadline('settings_Server'); ?>
<?php $this->showConfigText('settings_coreDir', 'coreDir'); ?>
<?php $this->showConfigText('settings_luceneClassDir', 'luceneClassDir'); ?>
<?php $this->showConfigText('settings_extraPath', 'extraPath'); ?>
<?php $this->showConfigText('settings_contentOffsetDir', 'contentOffsetDir'); ?>
<?php $this->showConfigText('settings_maxDirID', 'maxDirID'); ?>
<?php $this->showConfigText('settings_updateNotifyTime', 'updateNotifyTime'); ?>
<?php $this->showConfigText('settings_maxExecutionTime', 'maxExecutionTime'); ?>
<?php $this->showConfigText('settings_cmdTimeout', 'cmdTimeout'); ?>
<?php $this->showConfigCheckbox('settings_enableDebugMode', 'enableDebugMode'); ?>

<?php
  foreach(array('fulltext', 'preview', 'pdf') as $target) {
		$this->showConfigHeadline($target."_converters");
		if(!empty($settings->_converters[$target])) {
			foreach($settings->_converters[$target] as $mimetype=>$cmd) {
				$this->showConfigPlain(htmlspecialchars($mimetype), htmlspecialchars($mimetype), $this->getTextField("converters[".$target."][".$mimetype."]", htmlspecialchars($cmd)));
			}
		}
		$this->showConfigPlain($this->getTextField("converters[".$target."][newmimetype]", "", '', getMLText('converter_new_mimetype')), '', $this->getTextField("converters[".$target."][newcmd]", "", "", getMLText('converter_new_cmd')));
	}
	$this->showEndPaneContent('advanced', $currenttab);

	$this->showStartPaneContent('extensions', $currenttab == 'extensions');
?>
      <!--
        -- SETTINGS - ADVANCED - DISPLAY
      -->
<?php
	foreach($extmgr->getExtensionConfiguration() as $extname=>$extconf) {
		echo '<a class="scrollto" data-target="#'.$extname.'">'.$extconf['title']."</a> ● ";
	}
	foreach($extmgr->getExtensionConfiguration() as $extname=>$extconf) {
		if($this->hasHook('processConfig'))
			$extconf = $this->callHook('processConfig', $extname, $extconf);
		if($this->isVisible($extname.'|')) {
			if($extconf['config']) {
				$this->showRawConfigHeadline("<a id=\"".$extname."\" name=\"".$extname."\"></a>".'<input type="hidden" name="extensions['.$extname.'][__disable__]" value="'.(isset($settings->_extensions[$extname]["__disable__"]) && $settings->_extensions[$extname]["__disable__"] ? '1' : '').'" /><i class="fa fa-circle'.(isset($settings->_extensions[$extname]["__disable__"]) && $settings->_extensions[$extname]["__disable__"] ? ' disabled' : ' enabled').'"></i> <span title="'.$extname.'">'.$extconf['title'].'</span>');
				foreach($extconf['config'] as $confkey=>$conf) {
					ob_start();
					if($this->isVisible($extname.'|'.$confkey)) {
						switch($conf['type']) {
							case 'checkbox':
?>
        <input type="hidden" name="<?php echo "extensions[".$extname."][".$confkey."]"; ?>" value=""><input type="checkbox" name="<?php echo "extensions[".$extname."][".$confkey."]"; ?>" value="1" <?php if(isset($settings->_extensions[$extname][$confkey]) && $settings->_extensions[$extname][$confkey]) echo 'checked'; ?> />
<?php
								break;
							case 'select':
								if(!empty($conf['options'])) {
									$selections = empty($settings->_extensions[$extname][$confkey]) ? array() : explode(",", $settings->_extensions[$extname][$confkey]);
									echo "<select class=\"chzn-select\" name=\"extensions[".$extname."][".$confkey."][]\"".(!empty($conf['multiple']) ? "  multiple" : "").(!empty($conf['size']) ? "  size=\"".$conf['size']."\"" : "")." style=\"width: 100%;\">";
									foreach($conf['options'] as $key=>$opt) {
										echo "<option value=\"".$key."\"";
										if(in_array($key, $selections))
											echo " selected";
										echo ">".htmlspecialchars(getMLText($extname.'_'.$opt, array(), $opt))."</option>";
									}
									echo "</select>";
								} elseif(!empty($conf['internal'])) {
									$selections = empty($settings->_extensions[$extname][$confkey]) ? array() : explode(",", $settings->_extensions[$extname][$confkey]);
									$allowempty = empty($conf['allow_empty']) ? false : $conf['allow_empty'];
									switch($conf['internal']) {
									case "categories":
										$categories = $dms->getDocumentCategories();
										if($categories) {
											echo "<select class=\"chzn-select\"".($allowempty ? " data-allow-clear=\"true\"" : "")."\" name=\"extensions[".$extname."][".$confkey."][]\"".(!empty($conf['multiple']) ? "  multiple" : "").(!empty($conf['size']) ? "  size=\"".$conf['size']."\"" : "")." data-placeholder=\"".getMLText("select_category")."\" style=\"width: 100%;\">";
											if($allowempty)
												echo "<option value=\"\"></option>";
											foreach($categories as $category) {
												echo "<option value=\"".$category->getID()."\"";
												if(in_array($category->getID(), $selections))
													echo " selected";
												echo ">".htmlspecialchars($category->getName())."</option>";
											}
											echo "</select>";
										}
										break;
									case "users":
										$users = $dms->getAllUsers();
										if($users) {
											echo "<select class=\"chzn-select\"".($allowempty ? " data-allow-clear=\"true\"" : "")."\" name=\"extensions[".$extname."][".$confkey."][]\"".(!empty($conf['multiple']) ? "  multiple" : "").(!empty($conf['size']) ? "  size=\"".$conf['size']."\"" : "")." data-placeholder=\"".getMLText("select_user")."\" style=\"width: 100%;\">";
											if($allowempty)
												echo "<option value=\"\"></option>";
											foreach($users as $curuser) {
												echo "<option value=\"".$curuser->getID()."\"";
												if(in_array($curuser->getID(), $selections))
													echo " selected";
												echo ">".htmlspecialchars($curuser->getLogin()." - ".$curuser->getFullName())."</option>";
											}
											echo "</select>";
										}
										break;
									case "groups":
										$recs = $dms->getAllGroups();
										if($recs) {
											echo "<select class=\"chzn-select\"".($allowempty ? " data-allow-clear=\"true\"" : "")."\" name=\"extensions[".$extname."][".$confkey."][]\"".(!empty($conf['multiple']) ? "  multiple" : "").(!empty($conf['size']) ? "  size=\"".$conf['size']."\"" : "")." data-placeholder=\"".getMLText("select_group")."\" style=\"width: 100%;\">";
											if($allowempty)
												echo "<option value=\"\"></option>";
											foreach($recs as $rec) {
												echo "<option value=\"".$rec->getID()."\"";
												if(in_array($rec->getID(), $selections))
													echo " selected";
												echo ">".htmlspecialchars($rec->getName())."</option>";
											}
											echo "</select>";
										}
										break;
									case "attributedefinitions":
										$objtype = empty($conf['objtype']) ? 0 : $conf['objtype'];
										$attrtype = empty($conf['attrtype']) ? 0 : $conf['attrtype'];
										$recs = $dms->getAllAttributeDefinitions($objtype, $attrtype);
										if($recs) {
											echo "<select class=\"chzn-select\"".($allowempty ? " data-allow-clear=\"true\"" : "")."\" name=\"extensions[".$extname."][".$confkey."][]\"".(!empty($conf['multiple']) ? "  multiple" : "").(!empty($conf['size']) ? "  size=\"".$conf['size']."\"" : "")." data-placeholder=\"".getMLText("select_attrdef")."\" data-no_results_text=\"".getMLText('unknown_attrdef')."\" style=\"width: 100%;\">";
											if($allowempty)
												echo "<option value=\"\"></option>";
											foreach($recs as $rec) {
												echo "<option value=\"".$rec->getID()."\"";
												if(in_array($rec->getID(), $selections))
													echo " selected";
												echo " data-subtitle=\"".htmlspecialchars(getAttributeObjectTypeText($rec).", ".getAttributeTypeText($rec))."\">".htmlspecialchars($rec->getName())."</option>";
											}
											echo "</select>";
										} else {
											printMLText('no_attribute_definitions');
										}
										break;
									case "workflows":
										$recs = $dms->getAllWorkflows();
										if($recs) {
											echo "<select class=\"chzn-select\"".($allowempty ? " data-allow-clear=\"true\"" : "")."\" name=\"extensions[".$extname."][".$confkey."][]\"".(!empty($conf['multiple']) ? "  multiple" : "").(!empty($conf['size']) ? "  size=\"".$conf['size']."\"" : "")." data-placeholder=\"".getMLText("select_attribute_value")."\" style=\"width: 100%;\">";
											if($allowempty)
												echo "<option value=\"\"></option>";
											foreach($recs as $rec) {
												echo "<option value=\"".$rec->getID()."\"";
												if(in_array($rec->getID(), $selections))
													echo " selected";
												echo ">".htmlspecialchars($rec->getName())."</option>";
											}
											echo "</select>";
										} else {
											printMLText('no_workflows');
										}
										break;
									case "folders":
										$this->formField(null, $this->getFolderChooserHtml("form".$extname.$confkey, M_READ, -1, $selections ? $dms->getFolder($selections[0]) : 0, 'extensions['.$extname."][".$confkey."]"));
										break;
									case "documents":
										$this->formField(null, $this->getDocumentChooserHtml("form".$extname.$confkey, M_READ, -1, $selections ? $dms->getDocument($selections[0]) : 0, 'extensions['.$extname."][".$confkey."]"));
										break;
									}
								}
								break;
							case 'hook':
								echo $this->callHook('showConfig', $confkey, $extname, $extconf);
								break;
							default:
								$this->showTextField("extensions[".$extname."][".$confkey."]", isset($settings->_extensions[$extname][$confkey]) ? $settings->_extensions[$extname][$confkey] : '', isset($conf['type']) ? $conf['type'] : '', isset($conf['placeholder']) ? $conf['placeholder'] : '');
						}
					} else {
						echo getMLText('settings_conf_field_not_editable');
					}
					$html = ob_get_clean();
					$this->showConfigPlain($conf['title'], isset($conf['help']) ? $conf['help'] : '', $html);
				}
			} else {
				/* Even no configuration exists, output the input field to enable/disable
				 * the extension. Otherwise it will be enabled each time the config is
				 * saved.
				 */
				echo '<input type="hidden" name="extensions['.$extname.'][__disable__]" value="'.(isset($settings->_extensions[$extname]["__disable__"]) && $settings->_extensions[$extname]["__disable__"] ? '1' : '').'" />'."\n";
			}
		}
	}
	$this->showEndPaneContent('extensions', $currenttab);
?>
  </div>
<?php
if(is_writeable($settings->_configFilePath)) {
	$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('save'));
}
?>
	</form>
<?php
		$this->columnEnd();
		$this->columnStart(4);
		if(!is_writeable($settings->_configFilePath)) {
			$this->warningMsg(getMLText("settings_notwritable"));
		}
		if($settings->_enableGuestLogin && $settings->_guestID) {
			$guest = $dms->getUser((int) $settings->_guestID);
			if(!$guest) {
				$this->warningMsg(getMLText("settings_invalid_guestid"));
			} elseif($guest->isDisabled()) {
				$this->warningMsg(getMLText("settings_guestid_is_disabled"));
			} elseif($guest->isAdmin()) {
				$this->warningMsg(getMLText("settings_guestid_is_admin"));
			}
		}
		$mus2 = SeedDMS_Core_File::parse_filesize(ini_get("upload_max_filesize"));
		$mus1 = SeedDMS_Core_File::parse_filesize($settings->_partitionSize);
		if($settings->_enableLargeFileUpload && $mus2 < $mus1) {
			$this->warningMsg(getMLText("settings_partionsize_below_max_filesize"));
		}

		foreach($extmgr->getExtensionConfiguration() as $extname=>$extconf) {
			if($this->hasHook('checkConfig'))
				$this->callHook('checkConfig', $extname, $extconf);
		}
		$this->columnEnd(4);
		$this->rowEnd(4);
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
