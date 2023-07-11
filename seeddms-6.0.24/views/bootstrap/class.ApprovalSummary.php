<?php
/**
 * Implementation of ApprovalSummary view
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
 * Class which outputs the html page for ApprovalSummary view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ApprovalSummary extends SeedDMS_Theme_Style {

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

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		$previewer->setConverters($previewconverters);

		$this->htmlStartPage(getMLText("approval_summary"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_documents"), "my_documents");
		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText("approval_summary"));
//		$this->contentContainerStart();

		// Get document list for the current user.
		$approvalStatus = $user->getApprovalStatus();

		// reverse order
		$approvalStatus["indstatus"]=array_reverse($approvalStatus["indstatus"],true);
		$approvalStatus["grpstatus"]=array_reverse($approvalStatus["grpstatus"],true);

		$iRev = array();	
		$printheader = true;
		foreach ($approvalStatus["indstatus"] as $st) {
			$document = $dms->getDocument($st['documentID']);
			$version = $document->getContentByVersion($st['version']);
			$moduser = $dms->getUser($st['required']);

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
					$printheader = false;
				}
			
				$txt = $this->callHook('documentListItem', $document, $previewer);
				if(is_string($txt))
					echo $txt;
				else {
					$class = $st['status'] == 1 ? ' success' : ($st['status'] == -1 ? ' error' : ( $st['status'] == -2 ? ' info' : ''));
					echo $this->documentListRowStart($document, $class);
					echo $this->documentListRow($document, $previewer, true, $st['version']);
					print "<td><small>".getApprovalStatusText($st['status'])."<br />".$st["date"]."<br />". htmlspecialchars($moduser->getFullName()) ."</small></td>";
					echo $this->documentListRowEnd($document);
				}
			}
			if ($st["status"]!=-2) {
				$iRev[] = $st["documentID"];
			}
		}
		if (!$printheader) {
			echo "</tbody>\n</table>\n";
		}else{
			printMLText("no_approval_needed");
		}

//		$this->contentContainerEnd();
		$this->columnEnd();
		$this->columnStart(6);
		$this->contentHeading(getMLText("group_approval_summary"));
//		$this->contentContainerStart();

		$printheader = true;
		foreach ($approvalStatus["grpstatus"] as $st) {
			$document = $dms->getDocument($st['documentID']);
			$version = $document->getContentByVersion($st['version']);
			$modgroup = $dms->getGroup($st['required']);

			/* Filter out those documents which already require an approval as an individual */
			if (!in_array($st["documentID"], $iRev) && $document && $version) {

				if ($printheader){
					print "<table class=\"table table-condensed table-sm\">";
					print "<thead>\n<tr>\n";
					print "<th></th>\n";
					print "<th>".getMLText("name")."</th>\n";
					print "<th>".getMLText("status")."</th>\n";
					print "<th>".getMLText("action")."</th>\n";
					print "<th>".getMLText("last_update")."</th>\n";
					print "</tr>\n</thead>\n<tbody>\n";
					$printheader = false;
				}	
			
				$txt = $this->callHook('documentListItem', $document, $previewer);
				if(is_string($txt))
					echo $txt;
				else {
					$class = $st['status'] == 1 ? ' success' : ($st['status'] == -1 ? ' error' : ( $st['status'] == -2 ? ' info' : ''));
				echo $this->documentListRowStart($document, $class);
				echo $this->documentListRow($document, $previewer, true, $st['version']);
				print "<td><small>".getApprovalStatusText($st["status"])."<br />".$st["date"]."<br />". htmlspecialchars($modgroup->getName()) ."</small></td>";
				echo $this->documentListRowEnd($document);
				}
			}
		}
		if (!$printheader) {
			echo "</tbody>\n</table>\n";
		}else{
			printMLText("no_approval_needed");
		}

//		$this->contentContainerEnd();
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
