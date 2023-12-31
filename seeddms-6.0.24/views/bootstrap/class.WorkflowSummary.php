<?php
/**
 * Implementation of WorkflowSummary view
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
 * Class which outputs the html page for WorkflowSummary view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_WorkflowSummary extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));

		$this->printDeleteDocumentButtonJs();
		$this->printClickDocumentJs();
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$this->htmlStartPage(getMLText("my_documents"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_documents"), "my_documents");

		$this->contentHeading(getMLText("workflow_user_summary"));

		// Get document list for the current user.
		$workflowStatus = $user->getWorkflowStatus();

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		$previewer->setConverters($previewconverters);

		$printheader=true;
		$iRev = array();
		foreach ($workflowStatus["u"] as $st) {
			$document = $dms->getDocument($st['document']);
			if($document)
				$version = $document->getContentByVersion($st['version']);
			$moduser = $dms->getUser($st['userid']);

			if ($document && $version) {
			
				if ($printheader){
					print "<table class=\"table table-condensed table-sm\">";
					print "<thead>\n<tr>\n";
					print "<th></th>\n";
					print "<th>".getMLText("name")."</th>\n";
					print "<th>".getMLText("status")."</th>\n";
					print "<th>".getMLText("action")."</th>\n";
					print "<th>".getMLText("last_update")."</th>\n";
					print "</tr>\n</thead>\n<tbody>\n";
					$printheader=false;
				}
			
				echo $this->documentListRowStart($document, $class);
				echo $this->documentListRow($document, $previewer, true, $st['version']);
				print "<td><small>".getLongReadableDate($st["date"])."<br />". htmlspecialchars($moduser->getFullName()) ."</small></td>";
				echo $this->documentListRowEnd($document);
				$iRev[] = $document->getId();
			}
		}
		if (!$printheader) {
			echo "</tbody>\n</table>";
		} else {
			printMLText("no_docs_to_look_at");
		}

		$this->contentHeading(getMLText("workflow_group_summary"));

		$printheader=true;
		foreach ($workflowStatus["g"] as $st) {
			$document = $dms->getDocument($st['document']);
			if($document)
				$version = $document->getContentByVersion($st['version']);
			$modgroup = $dms->getGroup($st['groupid']);

			if (!in_array($st["document"], $iRev) && $document && $version) {
			
				if ($printheader){
					print "<table class=\"table table-condensed table-sm\">";
					print "<thead>\n<tr>\n";
					print "<th></th>\n";
					print "<th>".getMLText("name")."</th>\n";
					print "<th>".getMLText("status")."</th>\n";
					print "<th>".getMLText("action")."</th>\n";
					print "<th>".getMLText("last_update")."</th>\n";
					print "</tr>\n</thead>\n<tbody>\n";
					$printheader=false;
				}
			
				echo $this->documentListRowStart($document, $class);
				echo $this->documentListRow($document, $previewer, true, $st['version']);
				print "<td><small>".getLongReadableDate($st["date"])."<br />". htmlspecialchars($modgroup->getName()) ."</small></td>";
				echo $this->documentListRowEnd($document);
				$iRev[] = $document->getId();
			}
		}
		if (!$printheader) {
			echo "</tbody>\n</table>";
		}else{
			printMLText("no_docs_to_look_at");
		}

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
