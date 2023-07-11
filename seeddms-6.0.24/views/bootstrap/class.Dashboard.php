<?php
/**
 * Implementation of Dashboard view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2023 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which outputs the html page for Dashboard view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2023 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Dashboard extends SeedDMS_Theme_Style {

	protected function printList($documents, $previewer) { /* {{{ */
			$txt = $this->callHook('folderListPreContent', null, [], $documents);
			if(is_string($txt))
				echo $txt;
			$i = 0;
			$txt = $this->callHook('folderListHeader', null, '', '');
			if(is_string($txt)) {
				echo $txt;
			} elseif(is_array($txt)) {
				print "<table id=\"viewfolder-table\" class=\"table table-condensed table-sm table-hover\">";
				print "<thead>\n<tr>\n";
				foreach($txt as $headcol)
					echo "<th>".$headcol."</th>\n";
				print "</tr>\n</thead>\n";
			} else {
				echo $this->folderListHeader();
			}
			print "<tbody>\n";

			foreach($documents as $document) {
				$document->verifyLastestContentExpriry();
				$txt = $this->callHook('documentListItem', $document, $previewer, false, 'dashboard');
				if(is_string($txt))
					echo $txt;
				else {
					echo $this->documentListRow($document, $previewer);
				}
			}

			$txt = $this->callHook('folderListFooter', null);
			if(is_string($txt))
				echo $txt;
			else
				echo "</tbody>\n</table>\n";
	} /* }}} */

	public function newdocuments() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		echo $this->contentHeading(getMLText('new_documents'));
		$documents = $dms->getLatestChanges('newdocuments', mktime(0, 0, 0)-7*86400, time());
		if (count($documents) > 0) {
			$this->printList($documents, $previewer);
		}
	} /* }}} */

	public function updateddocuments() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		echo $this->contentHeading(getMLText('updated_documents'));
		$documents = $dms->getLatestChanges('updateddocuments', mktime(0, 0, 0)-7*86400, time());
		if (count($documents) > 0) {
			$this->printList($documents, $previewer);
		}
	} /* }}} */

	public function status() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		echo $this->contentHeading(getMLText('status_change'));
		$documents = $dms->getLatestChanges('statuschange', mktime(0, 0, 0)-7*86400, time());
		if (count($documents) > 0) {
			$this->printList($documents, $previewer);
		}
	} /* }}} */

	function js() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
		$this->printDeleteDocumentButtonJs();
		/* Add js for catching click on document in one page mode */
		$this->printClickDocumentJs();
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$this->htmlStartPage(getMLText("calendar"));
		$this->globalNavigation();
		$this->contentStart();

		$this->rowStart();
		$this->columnStart(4);
?>
		<div class="ajax" data-view="Dashboard" data-action="newdocuments"></div>
<?php
		$this->columnEnd();
		$this->columnStart(4);
?>
		<div class="ajax" data-view="Dashboard" data-action="updateddocuments"></div>
<?php
		$this->columnEnd();
		$this->columnStart(4);
?>
		<div class="ajax" data-view="Dashboard" data-action="status"></div>
<?php
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */

}
