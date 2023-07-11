<?php
/**
 * Implementation of LogManagement view
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
 * Class which outputs the html page for LogManagement view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_LogManagement extends SeedDMS_Theme_Style {

	function filelist($entries, $mode) { /* {{{ */
		$accessop = $this->params['accessobject'];
		$print_header = true;
		foreach ($entries as $entry){

			if ($print_header){
				print "<form action=\"out.RemoveLog.php\" method=\"get\">\n";
				print "<table class=\"table table-condensed table-sm\">\n";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";
				print "<th>".getMLText("name")."</th>\n";
				print "<th>".getMLText("creation_date")."</th>\n";
				print "<th class=\"d-none d-lg-table-cell\">".getMLText("file_size")."</th>\n";
				print "<th></th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
				$print_header=false;
			}

			print "<tr>\n";
			print "<td><input type=\"checkbox\" name=\"logname[]\" value=\"".$entry."\"/></td>\n";
			print "<td><a href=\"out.LogManagement.php?logname=".$entry."\">".$entry."</a></td>\n";
			print "\n";
			print "<td>".getReadableDate(filectime($this->logdir.$entry))."</td>\n";
			print "<td class=\"d-none d-lg-table-cell\">".SeedDMS_Core_File::format_filesize(filesize($this->logdir.$entry))."</td>\n";
			print "<td>";

			if($accessop->check_view_access('RemoveLog')) {
				print "<a href=\"out.RemoveLog.php?mode=".$mode."&logname=".$entry."\" class=\"btn btn-danger btn-mini btn-sm\"><i class=\"fa fa-remove\"></i><span class=\"d-none d-lg-block\"> ".getMLText("rm_file")."</span></a>";
			}
			if($accessop->check_controller_access('Download', array('action'=>'log'))) {
				print "&nbsp;";
				print "<a href=\"../op/op.Download.php?logname=".$entry."\" class=\"btn btn-secondary btn-mini btn-sm\"><i class=\"fa fa-download\"></i><span class=\"d-none d-lg-block\"> ".getMLText("download")."</span></a>";
			}
			print "&nbsp;";
			echo $this->getModalBoxLink(array('target'=>'logViewer', 'remote'=>'out.LogManagement.php?logname='.$entry, 'class'=>'btn btn-primary btn-mini btn-sm', 'title'=>'<i class="fa fa-eye"></i><span class="d-none d-lg-block"> '.getMLText('view').'</span>', 'attributes'=>array('data-modal-title'=>$entry)));
			print "</td>\n";
			print "</tr>\n";
		}

		if ($print_header) printMLText("empty_list");
		else print "<tr><td><span id=\"toggleall\"><i class=\"fa fa-exchange\"></i></span></td><td colspan=\"3\"><button type=\"submit\" class=\"btn btn-danger btn-mini btn-sm\"><i class=\"fa fa-remove\"></i> ".getMLText('remove_marked_files')."</button></td></tr></table></form>\n";
	} /* }}} */

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
$(document).ready( function() {
	$('#toggleall').on('click', function(e) {
//var checkBoxes = $("input[type=checkbox]");
//checkBoxes.prop("checked", !checkBoxes.prop("checked"));
$("input[type=checkbox]").each(function () { this.checked = !this.checked; });
	});

});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
		$this->logdir = $this->params['logdir'];
		$logname = $this->params['logname'];
		$mode = $this->params['mode'];

		if(!$logname) {
		$this->htmlStartPage(getMLText("log_management"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("log_management"));

		$sections = array(
			array('default', 'Web'),
			array('webdav', 'WebDAV'),
			array('restapi', 'RestAPI'),
		);
		if($es = $this->callHook('extraSections'))
			$sections = array_merge($sections, $es);
		$entries = [];
		foreach($sections as $section) {
			$entries[$section[0]] = array();
		}

		$handle = opendir($this->logdir);
		if($handle) {
			while ($e = readdir($handle)){
				if (is_dir($this->logdir.$e)) continue;
				if (strpos($e,".log")==FALSE) continue;
				if (strcmp($e,"current.log")==0) continue;
				$section = strtok($e, '-');
				if(isset($entries[$section]))
					$entries[$section][] = $e;
				else
					$entries['default'][] = $e;
			}
			closedir($handle);

			foreach($sections as $section) {
				sort($entries[$section[0]]);
				$entries[$section[0]] = array_reverse($entries[$section[0]]);
			}
		}
?>
	<ul class="nav nav-pills" id="logtab" role="tablist">
<?php
		foreach($sections as $section)
			$this->showPaneHeader($section[0], $section[1], (!$mode || $mode == $section[0]));
?>
	</ul>
	<div class="tab-content">
<?php
		foreach($sections as $section) {
			$this->showStartPaneContent($section[0], (!$mode || $mode == $section[0]));
			$this->filelist($entries[$section[0]], $section[0]);
			$this->showEndPaneContent($section[0], $mode);
		}
?>
	</div>
<?php
		echo $this->getModalBox(array('id'=>'logViewer', 'title'=>getMLText('logfile'), 'buttons'=>array(array('title'=>getMLText('close')))));
		$this->contentEnd();
		$this->htmlEndPage();
		} elseif(file_exists($this->logdir.$logname)){
			echo $logname."<pre>\n";
			readfile($this->logdir.$logname);
			echo "</pre>\n";
		} else {
			UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
		}

	} /* }}} */
}
?>
