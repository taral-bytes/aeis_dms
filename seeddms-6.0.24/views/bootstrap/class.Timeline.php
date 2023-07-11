<?php
/**
 * Implementation of Timeline view
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
 * Class which outputs the html page for Timeline view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Timeline extends SeedDMS_Theme_Style {

	function iteminfo() { /* {{{ */
		$dms = $this->params['dms'];
		$document = $this->params['document'];
		$version = $this->params['version'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewconverters = $this->params['previewConverters'];
		$previewwidthlist = $this->params['previewWidthList'];
		$previewwidthdetail = $this->params['previewWidthDetail'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		if($document && $version) {
				print $this->folderListHeader();
				print "<tbody>\n";
				$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidthdetail, $timeout, $xsendfile);
				if($conversionmgr)
					$previewer->setConversionMgr($conversionmgr);
				else
					$previewer->setConverters($previewconverters);
				$extracontent = array();
				$extracontent['below_title'] = $this->getListRowPath($document);
				echo $this->documentListRow($document, $previewer, 0, false, $extracontent);

				echo "</tbody>\n</table>\n";
		}
	} /* }}} */

	function data() { /* {{{ */
		$dms = $this->params['dms'];
		$skip = $this->params['skip'];
		$fromdate = $this->params['fromdate'];
		$todate = $this->params['todate'];

		if($fromdate) {
			$from = makeTsFromLongDate($fromdate.' 00:00:00');
		} else {
			$from = time()-7*86400;
		}

		if($todate) {
			$to = makeTsFromLongDate($todate.' 23:59:59');
		} else {
			$to = time()-7*86400;
		}

		if($data = $dms->getTimeline($from, $to)) {
			foreach($data as $i=>$item) {
				switch($item['type']) {
				case 'add_version':
					$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version']));
					break;
				case 'add_file':
					$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName())));
					break;
				case 'status_change':
					$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version'], 'status'=> getOverallStatusText($item['status'])));
					break;
				case 'scheduled_revision':
					$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version']));
					break;
				default:
					$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName())));
				}
				$data[$i]['msg'] = $msg;
			}
		}

		$jsondata = array();
		foreach($data as $item) {
			if($item['type'] == 'status_change')
				$classname = $item['type']."_".$item['status'];
			else
				$classname = $item['type'];
			if(!$skip || !in_array($classname, $skip)) {
				$d = makeTsFromLongDate($item['date']);
				$jsondata[] = array(
					'start'=>date('c', $d),
					'content'=>$item['msg'],
					'className'=>$classname,
					'docid'=>$item['document']->getID(),
					'version'=>isset($item['version']) ? $item['version'] : '',
					'statusid'=>isset($item['statusid']) ? $item['statusid'] : '',
					'statuslogid'=>isset($item['statuslogid']) ? $item['statuslogid'] : '',
					'fileid'=>isset($item['fileid']) ? $item['fileid'] : ''
				);
			}
		}
		header('Content-Type: application/json');
		echo json_encode($jsondata);
	} /* }}} */

	function js() { /* {{{ */
		$fromdate = $this->params['fromdate'];
		$todate = $this->params['todate'];
		$skip = $this->params['skip'];

		if($fromdate) {
			$from = makeTsFromLongDate($fromdate.' 00:00:00');
		} else {
			$from = time()-7*86400;
		}

		if($todate) {
			$to = makeTsFromLongDate($todate.' 23:59:59');
		} else {
			$to = time();
		}

		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
?>
$(document).ready(function () {
	$('#update').click(function(ev){
		ev.preventDefault();
		$.getJSON(
			'out.Timeline.php?action=data&' + $('#form1').serialize(), 
			function(data) {
				$.each( data, function( key, val ) {
					val.start = new Date(val.start);
				});
				timeline.setData(data);
				timeline.redraw();
//				timeline.setVisibleChartRange(0,0);
			}
		);
	});
});
		function onselect() {
			var sel = timeline.getSelection();
			if (sel.length) {
				if (sel[0].row != undefined) {
					var row = sel[0].row;
					console.log(timeline.getItem(sel[0].row));
					item = timeline.getItem(sel[0].row);
					$('div.ajax').trigger('update', {documentid: item.docid, version: item.version, statusid: item.statusid, statuslogid: item.statuslogid, fileid: item.fileid});
				}
			}
		}
<?php
		$this->printDeleteDocumentButtonJs();
		$timelineurl = 'out.Timeline.php?action=data&fromdate='.date('Y-m-d', $from).'&todate='.date('Y-m-d', $to).'&skip='.urldecode(http_build_query(array('skip'=>$skip)));
		$this->printTimelineJs($timelineurl, 550, ''/*date('Y-m-d', $from)*/, ''/*date('Y-m-d', $to+1)*/, $skip, 'onselect');
		$this->printClickDocumentJs();
	} /* }}} */

	function css() { /* {{{ */
?>
#timeline {
	font-size: 12px;
	line-height: 14px;
}
div.timeline-event-content {
	margin: 3px 5px;
}
div.timeline-frame {
	border-radius: 4px;
	border-color: #e3e3e3;
}

div.add_file {
	background-color: #E5D5F5;
	border-color: #AA9ABA;
}

div.status_change_2 {
	background-color: #DAF6D5;
	border-color: #AAF897;
}

div.status_change_-1 {
	background-color: #F6D5D5;
	border-color: #F89797;
}

div.status_change_-2 {
	background-color: #eee;
	border-color: #ccc;
}

div.status_change_-3 {
	background-color: #eee;
	border-color: #ccc;
}

div.timeline-event-selected {
	background-color: #fff785;
	border-color: #ffc200;
	z-index: 999;
}
<?php
		header('Content-Type: text/css');
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$fromdate = $this->params['fromdate'];
		$todate = $this->params['todate'];
		$skip = $this->params['skip'];

		if($fromdate) {
			$from = makeTsFromLongDate($fromdate.' 00:00:00');
		} else {
			$from = time()-7*86400;
		}

		if($todate) {
			$to = makeTsFromLongDate($todate.' 23:59:59');
		} else {
			$to = time();
		}

		$this->htmlAddHeader('<link href="../styles/bootstrap/timeline/timeline.css" rel="stylesheet">'."\n", 'css');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/bootstrap/timeline/timeline-min.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/bootstrap/timeline/timeline-locales.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("timeline"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->rowStart();
		$this->columnStart(4);
		$this->contentHeading(getMLText("timeline"));
		$this->contentContainerStart();
?>
<form action="../out/out.Timeline.php" class="form form-horizontal" name="form1" id="form1">
<?php
		$this->formField(
			getMLText("from"),
			$this->getDateChooser(getReadableDate($from), 'fromdate', $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("to"),
			$this->getDateChooser(getReadableDate($to), 'todate', $this->params['session']->getLanguage())
		);
		$html = '
			<input type="checkbox" name="skip[]" value="add_file" '.(($skip &&  in_array('add_file', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_add_file').'<br />
			<input type="checkbox" name="skip[]" value="status_change_0" '.(($skip && in_array('status_change_0', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_status_change_0').'<br />
			<input type="checkbox" name="skip[]" value="status_change_1" '.(($skip && in_array('status_change_1', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_status_change_1').'<br />
			<input type="checkbox" name="skip[]" value="status_change_2" '.(($skip && in_array('status_change_2', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_status_change_2').'<br />
			<input type="checkbox" name="skip[]" value="status_change_3" '.(($skip && in_array('status_change_3', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_status_change_3').'<br />
			<input type="checkbox" name="skip[]" value="status_change_4" '.(($skip && in_array('status_change_4', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_status_change_4').'<br />
			<input type="checkbox" name="skip[]" value="status_change_5" '.(($skip && in_array('status_change_5', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_status_change_5').'<br />
			<input type="checkbox" name="skip[]" value="status_change_-1" '.(($skip && in_array('status_change_-1', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_status_change_-1').'<br />
			<input type="checkbox" name="skip[]" value="status_change_-2" '.(($skip && in_array('status_change_-2', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_status_change_-2').'<br />
			<input type="checkbox" name="skip[]" value="status_change_-3" '.(($skip && in_array('status_change_-3', $skip)) ? 'checked' : '').'> '.getMLText('timeline_skip_status_change_-3').'<br />';
		$this->formField(
			getMLText("exclude_items"),
			$html
		);
		$this->formSubmit('<i class="fa fa-search"></i> '.getMLText('update'), 'update');
?>
</form>
<a href="out.TimelineFeed.php"><i class="fa fa-rss"></i> <?php printMLText('subsribe_timelinefeed'); ?></a>
<?php
		$this->contentContainerEnd();
		echo "<div class=\"ajax\" data-view=\"Timeline\" data-action=\"iteminfo\" ></div>";
		$this->columnEnd();
		$this->columnStart(8);
		$this->contentHeading(getMLText("timeline"));
		$this->printTimelineHtml(550);
		$this->columnEnd();
		$this->rowEnd();

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
