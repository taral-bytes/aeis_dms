<?php
/**
 * Implementation of Indexer view
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
 * Class for processing a single folder
 *
 * SeedDMS_View_Indexer_Process_Folder::process() is used as a callable when
 * iterating over all folders recursively.
 */
class SeedDMS_View_Indexer_Process_Folder { /* {{{ */
	protected $forceupdate;

	protected $fulltextservice;

	public function __construct($fulltextservice, $forceupdate) { /* {{{ */
		$this->fulltextservice = $fulltextservice;
		$this->forceupdate = $forceupdate;
		$this->numdocs = $this->fulltextservice->Indexer()->count();
	} /* }}} */

	public function process($folder, $depth=0) { /* {{{ */
		$lucenesearch = $this->fulltextservice->Search();
		echo "<div class=\"folder\" style=\"margin-left: ".(($depth+0)*18)."px\"><i class=\"fa fa-folder\"></i> ".$folder->getId().":".htmlspecialchars($folder->getFolderPathPlain());
		/* If the document wasn't indexed before then just add it */
		if(($this->numdocs == 0) || !($hit = $lucenesearch->getFolder($folder->getId()))) {
			echo " <span id=\"status_F".$folder->getID()."\" class=\"indexme indexstatus\" data-docid=\"F".$folder->getID()."\">".getMLText('index_waiting')."</span>";
		} else {
			/* Check if the attribute indexed is set or has a value older
			 * than the lastet content. Documents without such an attribute
			 * where added when a new document was added to the dms. In such
			 * a case the document content  wasn't indexed.
			 */
			try {
				$indexed = (int) $hit->getDocument()->getFieldValue('indexed');
			} catch (/* Zend_Search_Lucene_ */Exception $e) {
				$indexed = 0;
			}
			if($indexed >= $folder->getDate() && !$this->forceupdate) {
				echo "<span id=\"status_F".$folder->getID()."\" class=\"indexstatus\" data-docid=\"F".$folder->getID()."\">".getMLText('index_document_unchanged')."</span>";
			} else {
				$this->fulltextservice->Indexer()->delete($hit->id);
				echo " <span id=\"status_F".$folder->getID()."\" class=\"indexme indexstatus\" data-docid=\"F".$folder->getID()."\">".getMLText('index_waiting')."</span>";
			}
		}
		echo "</div>";

		$documents = $folder->getDocuments();
		if($documents) {
//			echo "<div class=\"folder\">".htmlspecialchars($folder->getFolderPathPlain())."</div>";
			foreach($documents as $document) {
				echo "<div class=\"document\" style=\"margin-left: ".(($depth+2)*18)."px\">".$document->getId().":".htmlspecialchars($document->getName());
				/* If the document wasn't indexed before then just add it */
				if(($this->numdocs == 0) || !($hit = $lucenesearch->getDocument($document->getId()))) {
					echo " <span id=\"status_D".$document->getID()."\" class=\"indexme indexstatus\" data-docid=\"D".$document->getID()."\">".getMLText('index_waiting')."</span>";
				} else {
					/* Check if the attribute indexed is set or has a value older
					 * than the lastet content. Documents without such an attribute
					 * where added when a new document was added to the dms. In such
					 * a case the document content  wasn't indexed.
					 */
					try {
						$indexed = (int) $hit->getDocument()->getFieldValue('indexed');
					} catch (/* Zend_Search_Lucene_ */Exception $e) {
						$indexed = 0;
					}
					$content = $document->getLatestContent();
					if($indexed >= $content->getDate() && !$this->forceupdate) {
						echo "<span id=\"status_D".$document->getID()."\" class=\"indexstatus\" data-docid=\"D".$document->getID()."\">".getMLText('index_document_unchanged')."</span>";
					} else {
						$this->fulltextservice->Indexer()->delete($hit->id);
						echo " <span id=\"status_D".$document->getID()."\" class=\"indexme indexstatus\" data-docid=\"D".$document->getID()."\">".getMLText('index_waiting')."</span>";
					}
				}
				echo "</div>";
			}
		}
	} /* }}} */
} /* }}} */

/**
 * Class which outputs the html page for Indexer view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Indexer extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		header('Content-Type: application/javascript; charset=UTF-8');
?>
var queue_count = 0;          // Number of functions being called
var funcArray = [];     // Array of functions waiting
var MAX_REQUESTS = 5;   // Max requests
var CALL_WAIT = 20;        // 100ms
var docstoindex = 0; // total number of docs to index

function check_queue() {
		// Check if count doesn't exceeds or if there aren't any functions to call
//		console.log('Queue has ' + funcArray.length + '/' + docstoindex + ' items');
//		console.log('Currently processing ' + queue_count + ' requests (' + $.active + ')');
    if(queue_count >= MAX_REQUESTS) {
			setTimeout(function() { check_queue() }, CALL_WAIT);
			return;
		}
		if(funcArray.length == 0) {
			return;
		}
		var command = '';
		docid = funcArray.pop();
		$('#status_'+docid).html('<?= getMLText('index_processing') ?>');
		if(docid[0] == 'F') {
			command = 'indexfolder';
		} else {
			command = 'indexdocument';
		}
		$.ajax({url: '../op/op.Ajax.php',
			type: 'GET',
			dataType: "json",
			data: {command: command, id: docid.substring(1)},
			beforeSend: function() {
				queue_count++;            // Add request to the counter
				$('.queue-bar').css('width', (queue_count*100/MAX_REQUESTS)+'%');
				$('.queue-bar').text(queue_count + '/' + MAX_REQUESTS);
			},
			error: function(xhr, textstatus) {
				noty({
					text: textstatus,
					type: 'error',
					dismissQueue: true,
					layout: 'topRight',
					theme: 'defaultTheme',
					timeout: 5000,
				});
			},
			success: function(data) {
				// console.log('success ' + data.data);
				if(data.success) {
					if(data.cmd)
						$('#status_'+data.data).html('<?= getMLText('index_done') ?>');
					else
						$('#status_'+data.data).html('<?= getMLText('index_done').' ('.getMLText('index_no_content').')' ?>');
				} else {
					$('#update_messages').append('<div><strong>Docid: ' + data.data + ' (' + data.mimetype + ')</strong><br />' + 'Cmd: ' + data.cmd + '<br />' + data.message+'</div>');
					$('#status_'+data.data).html('<?= getMLText('index_error') ?>');
					noty({
						text: '<p><strong>Docid: ' + data.data + ' (' + data.mimetype + ')</strong></p>' + '<p>Cmd: ' + data.cmd + '</p>' + data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 25000,
					});
				}
			},
			complete: function(xhr, textstatus) {
				queue_count--;        // Substract request to the counter
				$('.queue-bar').css('width', (queue_count*100/MAX_REQUESTS)+'%');
				$('.total-bar').css('width', (100 - (funcArray.length+queue_count)*100/docstoindex)+'%');
				$('.total-bar').text(Math.round(100 - (funcArray.length+queue_count)*100/docstoindex)+' %');
				if(funcArray.length+queue_count == 0)
					$('.total-bar').addClass('bar-success');
			}
		});
		setTimeout(function() { check_queue() }, CALL_WAIT);
}

$(document).ready( function() {
	$('.tree-toggle').click(function () {
		$(this).parent().children('ul.tree').toggle(200);
	});

	$('.indexme').each(function(index) {
		var element = $(this);
		var docid = element.data('docid');
		element.html('<?= getMLText('index_pending') ?>');
    funcArray.push(docid);
	});
	docstoindex = funcArray.length;
	check_queue();  // First call to start polling. It will call itself each 100ms
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$fulltextservice = $this->params['fulltextservice'];
		$forceupdate = $this->params['forceupdate'];
		$folder = $this->params['folder'];
		$this->converters = $this->params['converters'];
		$this->timeout = $this->params['timeout'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText("update_fulltext_index"));
		if($fulltextservice) {
			$index = $fulltextservice->Indexer();
?>
<style type="text/css">
div.document {line-height: 20px;}
div.document:hover {background-color: #eee;}
div.folder {font-weight: bold; line-height: 20px; margin-top: 10px;}
.indexstatus {font-weight: bold; float: right;}
.progress {margin-bottom: 2px;}
.bar-legend {text-align: right; font-size: 85%; margin-bottom: 15px;}
</style>
		<div>
			<div class="progress">
				<div class="progress-bar bar total-bar" role="progressbar" style="width: 100%;"></div>
			</div>
			<div class="bar-legend"><?= getMLText('overall_indexing_progress') ?></div>
		</div>
		<div>
			<div class="progress">
				<div class="progress-bar bar queue-bar" role="progressbar" style="width: 100%;"></div>
			</div>
			<div class="bar-legend"><?= getMLText('indexing_tasks_in_queue') ?></div>
		</div>
<?php
		$folderprocess = new SeedDMS_View_Indexer_Process_Folder($fulltextservice, $forceupdate);
		call_user_func(array($folderprocess, 'process'), $folder, -1);
		$tree = new SeedDMS_FolderTree($folder, array($folderprocess, 'process'));
		$this->columnEnd();
		$this->columnStart(6);
		$this->contentHeading(getMLText("update_fulltext_messages"));
		echo '<div id="update_messages">';
		echo '</div>';
		$this->columnEnd();
		$this->rowEnd();

		$index->commit();
		$index->optimize();
		} else {
			$this->warningMsg(getMLText('fulltextsearch_disabled'));
		}
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
