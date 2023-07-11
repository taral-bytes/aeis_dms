<?php
/**
 * Implementation of ObjectCheck view
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
 * Class which outputs the html page for ObjectCheck view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ObjectCheck extends SeedDMS_Theme_Style {

	protected function printListHeader($resArr, $previewer, $order=false) { /* {{{ */
		print "<table class=\"table table-condensed table-sm\">";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";
		if($order) {
			$orderby = ''; //$this->params['orderby'];
			$orderdir = ''; //$this->params['orderdir'];

			print "<th><a data-action=\"".$order."\" data-orderby=\"n\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("name")."</a> ".($orderby == 'n' || $orderby == '' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')." &middot; <a data-action=\"".$order."\" data-orderby=\"u\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("last_update")."</a> ".($orderby == 'u' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')." &middot; <a data-action=\"".$order."\" data-orderby=\"e\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("expires")."</a> ".($orderby == 'e' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')."</th>\n";
		} else
			print "<th>".getMLText("name")."</th>\n";
		if($order)
			print "<th><a data-action=\"".$order."\" data-orderby=\"s\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("status")."</a>".($orderby == 's' ? " ".($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')."</th>\n";
		else
			print "<th>".getMLText("status")."</th>\n";
		print "<th>".getMLText("action")."</th>\n";
		print "</tr>\n</thead>\n<tbody>\n";
	} /* }}} */

	protected function printListFooter() { /* {{{ */
		echo "</tbody>\n</table>";
	} /* }}} */

	protected function printList($resArr, $previewer, $order=false) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		$this->printListHeader($resArr, $previewer, $order);
		$noaccess = 0;
		foreach ($resArr as $document) {
			$document->verifyLastestContentExpriry();

			if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
				$txt = $this->callHook('documentListItem', $document, $previewer, false);
				if(is_string($txt))
					echo $txt;
				else
					echo $this->documentListRow($document, $previewer, false);
			} else {
				$noaccess++;
			}
		}
		$this->printListFooter();

		if($noaccess) {
			$this->warningMsg(getMLText('list_contains_no_access_docs', array('count'=>$noaccess)));
		}
	} /* }}} */

	function listRepair() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$repair = $this->params['repair'];
		$objects = $this->params['repairobjects'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];

		$this->contentHeading(getMLText("objectcheck"));

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		if($objects) {
			if($repair) {
				$this->warningMsg(getMLText('repairing_objects'));
			}
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th></th>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("status")."</th>\n";
			print "<th>".getMLText("action")."</th>\n";
			print "<th>".getMLText("error")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			$needsrepair = false;
			foreach($objects as $object) {
				if($object['object']->isType('document')) {
					$document = $object['object'];
					if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
						$txt = $this->callHook('documentListItem', $document, $previewer, false);
						if(is_string($txt))
							echo $txt;
						else
							echo $this->documentListRow($document, $previewer, true);
						echo "<td>".$object['msg'];
						if($repair)
							$document->repair();
						echo "</td>";
						$needsrepair = true;
						echo $this->documentListRowEnd($document);
					}
				} elseif($object['object']->isType('documentcontent')) {
					$document = $object['object']->getDocument();
					if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
						echo $this->documentListRowStart($document);
						$txt = $this->callHook('documentListItem', $document, $previewer, true, $object['object']->getVersion());
						if(is_string($txt))
							echo $txt;
						else
							echo $this->documentListRow($document, $previewer, true, $object['object']->getVersion());
						echo "<td>".$object['msg']."</td>";
						echo $this->documentListRowEnd($document);
					}
				} elseif($object['object']->isType('folder')) {
					$folder = $object['object'];
					if($folder->getAccessMode($user) >= M_READ) {
						echo $this->folderListRowStart($folder);
						$txt = $this->callHook('folderListItem', $folder, true);
						if(is_string($txt))
							echo $txt;
						else
							echo $this->folderListRow($folder, true);
						echo "<td>".$object['msg'];
						if($repair)
							$folder->repair();
						echo "</td>";
						echo $this->folderListRowEnd($folder);
						$needsrepair = true;
					}
				}
			}
			print "</tbody></table>\n";

			if($needsrepair && $repair == 0) {
				echo '<div class="repair"><a class="btn btn-primary" href="out.ObjectCheck.php?list=listRepair&repair=1">'.getMLText('do_object_repair').'</a></div>';
			}
		}
	} /* }}} */

	function listUnlinkedFolders() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$unlinkedfolders = $this->params['unlinkedfolders'];

		$this->contentHeading(getMLText("unlinked_folders"));
		if($unlinkedfolders) {
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("id")."</th>\n";
			print "<th>".getMLText("parent_folder")."</th>\n";
			print "<th>".getMLText("error")."</th>\n";
//			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($unlinkedfolders as $error) {
				echo "<tr>";
				echo "<td>".$error['name']."</td>";
				echo "<td>".$error['id']."</td>";
				echo "<td>".$error['parent']."</td>";
				echo "<td>".$error['msg']."</td>";
//				echo "<td><a class=\"btn btn-primary btn-mini btn-sm movefolder\" source=\"".$error['id']."\" dest=\"".$rootfolder->getID()."\" formtoken=\"".createFormKey('movefolder')."\" title=\"".getMLText("move_into_rootfolder")."\">".getMLText('move')."</a> </td>";
				echo "</tr>";
			}
			print "</tbody></table>\n";
		}
	} /* }}} */

	function listUnlinkedDocuments() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$rootfolder = $this->params['rootfolder'];
		$unlinkeddocuments = $this->params['unlinkeddocuments'];

		$this->contentHeading(getMLText("unlinked_documents"));
		if($unlinkeddocuments) {
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("id")."</th>\n";
			print "<th>".getMLText("parent_folder")."</th>\n";
			print "<th>".getMLText("error")."</th>\n";
//			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($unlinkeddocuments as $error) {
				echo "<tr>";
				echo "<td>".$error['name']."</td>";
				echo "<td>".$error['id']."</td>";
				echo "<td>".$error['parent']."</td>";
				echo "<td>".$error['msg']."</td>";
//				echo "<td><a class=\"btn btn-primary btn-mini btn-sm movedocument\" source=\"".$error['id']."\" dest=\"".$rootfolder->getID()."\" formtoken=\"".createFormKey('movedocument')."\" title=\"".getMLText("move_into_rootfolder")."\">".getMLText('move')."</a> </td>";
				echo "</tr>";
			}
			print "</tbody></table>\n";
		}
	} /* }}} */

	function listUnlinkedContent() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$unlinkedcontent = $this->params['unlinkedcontent'];
		$unlink = $this->params['unlink'];

		$this->contentHeading(getMLText("unlinked_content"));
		if($unlink) {
			echo "<p>".getMLText('unlinking_objects')."</p>";
		}

		if($unlinkedcontent) {
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($unlinkedcontent as $version) {
				$doc = $version->getDocument();
				print "<tr><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				if($unlink) {
					$doc->removeContent($version);
				}
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($unlink == 0) {
				echo '<p><a href="out.ObjectCheck.php?unlink=1">'.getMLText('do_object_unlink').'</a></p>';
			}
		}

	} /* }}} */

	function listMissingFileSize() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$nofilesizeversions = $this->params['nofilesizeversions'];
		$repair = $this->params['repair'];

		$this->contentHeading(getMLText("missing_filesize"));
		if($nofilesizeversions) {
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($nofilesizeversions as $version) {
				$doc = $version->getDocument();
				$class = $msg = '';
				if($repair) {
					if($version->setFileSize()) {
						$msg = getMLText('repaired');
						$class = ' class="success"';
					} else {
						$msg = getMLText('not_repaired');
						$class = ' class="error"';
					}
				}
				print "<tr".$class."><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				echo "<td>";
				echo $msg;
				echo "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($repair == 0) {
				echo '<div class="repair"><a class="btn btn-primary" data-action="listMissingFileSize">'.getMLText('do_object_setfilesize').'</a></div>';
			}
		}

	} /* }}} */

	function listMissingChecksum() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$nochecksumversions = $this->params['nochecksumversions'];
		$repair = $this->params['repair'];

		$this->contentHeading(getMLText("missing_checksum"));

		if($nochecksumversions) {
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($nochecksumversions as $version) {
				$doc = $version->getDocument();
				$class = $msg = '';
				if($repair) {
					if($version->setChecksum()) {
						$msg = getMLText('repaired');
						$class = ' class="success"';
					} else {
						$msg = getMLText('not_repaired');
						$class = ' class="error"';
					}
				}
				print "<tr".$class."><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				echo "<td>";
				echo $msg;
				echo "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($repair == 0) {
				echo '<div class="repair"><a class="btn btn-primary" data-action="listMissingChecksum">'.getMLText('do_object_setchecksum').'</a></div>';
			}
		}
	} /* }}} */

	function listWrongFiletype() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$wrongfiletypeversions = $this->params['wrongfiletypeversions'];
		$repair = $this->params['repair'];

		$this->contentHeading(getMLText("wrong_filetype"));

		if($wrongfiletypeversions) {
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th>".getMLText("filetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($wrongfiletypeversions as $version) {
				$doc = $version->getDocument();
				$class = $msg = '';
				if($repair) {
					if($version->setFiletype()) {
						$msg = getMLText('repaired');
						$class = ' class="success"';
					} else {
						$msg = getMLText('not_repaired');
						$class = ' class="error"';
					}
				}
				print "<tr".$class."><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td><td>".$version->getFileType()."</td>";
				echo "<td>";
				echo $msg;
				echo "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($repair == 0) {
				echo '<div class="repair"><a class="btn btn-primary" data-action="listWrongFiletype">'.getMLText('do_object_setfiletype').'</a></div>';
			}
		}
	} /* }}} */

	function listDuplicateContent() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$duplicateversions = $this->params['duplicateversions'];

		$this->contentHeading(getMLText("duplicate_content"));

		if($duplicateversions) {
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th>".getMLText("duplicates")."</th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($duplicateversions as $rec) {
				$version = $rec['content'];
				$doc = $version->getDocument();
				print "<tr>";
				print "<td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				print "<td>";
				foreach($rec['duplicates'] as $duplicate) {
					$dupdoc = $duplicate->getDocument();
					print "<a href=\"../out/out.ViewDocument.php?documentid=".$dupdoc->getID()."\">".$dupdoc->getID()."/".$duplicate->getVersion()."</a>";
					echo "<br />";
				}
				print "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
		}
} /* }}} */

	function listDuplicateSequence() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$repairfolder = $this->params['repairfolder'];
		$duplicatesequences = $this->params['duplicatesequences'];

		$this->contentHeading(getMLText("duplicate_sequences"));

		if($duplicatesequences) {
			print "<table class=\"table table-condensed\">";
			print "<thead><tr>\n";
			print "<th></th>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("owner")."</th>\n";
			print "<th>".getMLText("actions")."</th>\n";
			print "<th></th>\n";
			print "</tr></thead>\n<tbody>\n";
			foreach($duplicatesequences as $fld) {
				echo $this->folderListRowStart($fld);
				$txt = $this->callHook('folderListItem', $fld, true, 'viewfolder');
				if(is_string($txt))
					echo $txt;
				else {
					echo $this->folderListRow($fld, true);
				}
				echo "<td>";
				if($repairfolder && ($fld->getId() == $repairfolder->getId())) {
					if($fld->reorderDocuments())
						echo "Ok";
					else
						echo "Error";
				} else
					echo "<a class=\"btn btn-primary btn-mini btn-sm reorder\" data-action=\"listDuplicateSequence\" data-repairfolderid=\"".$fld->getId()."\" title=\"".getMLText("reorder_documents_in_folder")."\">".getMLText('reorder')."</a>";
				echo "</td>";
				echo $this->folderListRowEnd($fld);
			}
			print "</tbody></table>";
		}
} /* }}} */

	function listDocsInRevisionNoAccess() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$docsinrevision = $this->params['docsinrevision'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$this->contentHeading(getMLText("docs_in_revision_no_access"));

		if($docsinrevision) {
			$this->printList($docsinrevision, $previewer);
		}
	} /* }}} */

	function listDocsWithMissingRevisionDate() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$docsmissingrevsiondate = $this->params['docsmissingrevsiondate'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$this->contentHeading(getMLText("docs_with_missing_revision_date"));

		if($docsmissingrevsiondate) {
			$this->printList($docsmissingrevsiondate, $previewer);
		}
	} /* }}} */

	function listDocsInReceptionNoAccess() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$docsinreception = $this->params['docsinreception'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$this->contentHeading(getMLText("docs_in_revision_no_access"));

		if($docsinreception) {
			$this->printList($docsinreception, $previewer, 'listDocsInReceptionNoAccess');
		}
	} /* }}} */

	function listProcessesWithoutUserGroup($process, $ug) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$processwithoutusergroup = $this->params['processwithoutusergroup'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$repair = $this->params['repair'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$this->contentHeading(getMLText($process."s_without_".$ug));

		if($processwithoutusergroup[$process][$ug]) {
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("process")."</th>\n";
			print "<th>".getMLText("user_group")."</th>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("userid_groupid")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($processwithoutusergroup[$process][$ug] as $rec) {
				print "<tr>";
				print "<td>".$process."</td>";
				print "<td>".$ug."</td>";
				print "<td><a href=\"../out/out.ViewDocument.php?documentid=".$rec['documentID']."\">".$rec['name']."</a></td><td>".$rec['version']."</td>";
				print "<td>".$rec['required']."</td>";
				print "<td><a class=\"repair\" data-action=\"list".ucfirst($process)."Without".ucfirst($ug)."\" data-required=\"".$rec['required']."\">".getMLText('delete')."</a></td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			return count($processwithoutusergroup[$process][$ug]);
		}
		return false;
	} /* }}} */

	function listReviewWithoutUser() { /* {{{ */
		$this->listProcessesWithoutUserGroup('review', 'user');
	} /* }}} */

	function listReviewWithoutGroup() { /* {{{ */
		$this->listProcessesWithoutUserGroup('review', 'group');
	} /* }}} */

	function listApprovalWithoutUser() { /* {{{ */
		$this->listProcessesWithoutUserGroup('approval', 'user');
	} /* }}} */

	function listApprovalWithoutGroup() { /* {{{ */
		$this->listProcessesWithoutUserGroup('approval', 'group');
	} /* }}} */

	function listReceiptWithoutUser() { /* {{{ */
		if($this->listProcessesWithoutUserGroup('receipt', 'user')) {
			echo '<div class="repair"><a data-action="listReceiptWithoutUser">'.getMLText('do_object_repair').'</a>';
		}
	} /* }}} */

	function listReceiptWithoutGroup() { /* {{{ */
		$this->listProcessesWithoutUserGroup('receipt', 'group');
	} /* }}} */

	function listRevisionWithoutUser() { /* {{{ */
		$this->listProcessesWithoutUserGroup('revision', 'user');
	} /* }}} */

	function listRevisionWithoutGroup() { /* {{{ */
		$this->listProcessesWithoutUserGroup('revision', 'group');
	} /* }}} */

	function js() { /* {{{ */
		$user = $this->params['user'];
		$folder = $this->params['folder'];

		header('Content-Type: application/javascript; charset=UTF-8');

		$this->printDeleteFolderButtonJs();
		$this->printDeleteDocumentButtonJs();
		$this->printClickDocumentJs();
?>
$(document).ready( function() {
	$('body').on('click', 'ul.sidenav li a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {orderby: $(this).data('orderby')});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.ObjectCheck.php?list=' + $(this).data('action'));
	});
	$('body').on('click', 'div.repair a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {repair: 1});
	});
	$('body').on('click', 'a.repair', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {repair: 1, required: $(this).data('required')});
	});
	$('body').on('click', 'a.reorder', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {repair: 1, repairfolderid: $(this).data('repairfolderid')});
	});
	$('body').on('click', 'table th a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {orderby: $(this).data('orderby'), orderdir: $(this).data('orderdir')});
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$listtype = $this->params['listtype'];
		$unlinkedcontent = $this->params['unlinkedcontent'];
		$unlinkedfolders = $this->params['unlinkedfolders'];
		$unlinkeddocuments = $this->params['unlinkeddocuments'];
		$nofilesizeversions = $this->params['nofilesizeversions'];
		$nochecksumversions = $this->params['nochecksumversions'];
		$duplicateversions = $this->params['duplicateversions'];
		$duplicatesequences = $this->params['duplicatesequences'];
		$docsinrevision = $this->params['docsinrevision'];
		$docsinreception = $this->params['docsinreception'];
		$processwithoutusergroup = $this->params['processwithoutusergroup'];
		$docsmissingrevsiondate = $this->params['docsmissingrevsiondate'];
		$wrongfiletypeversions = $this->params['wrongfiletypeversions'];
		$repair = $this->params['repair'];
		$unlink = $this->params['unlink'];
		$setfilesize = $this->params['setfilesize'];
		$setchecksum = $this->params['setchecksum'];
		$rootfolder = $this->params['rootfolder'];
		$repairobjects = $this->params['repairobjects'];
		$this->enableClipboard = $this->params['enableclipboard'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->rowStart();
		$this->columnStart(3);
		$this->contentHeading(getMLText("object_check_critical"));
		$menuitems = [];
		$menuitems[] = array('label'=>getMLText('objectcheck'), 'badge'=>count($repairobjects), 'attributes'=>array(array('data-href', "#all_documents"), array('data-action', "listRepair")));
		$menuitems[] = array('label'=>getMLText('unlinked_folders'), 'badge'=>count($unlinkedfolders), 'attributes'=>array(array('data-href', "#unlinked_folders"), array('data-action', "listUnlinkedFolders")));
		$menuitems[] = array('label'=>getMLText('unlinked_documents'), 'badge'=>count($unlinkeddocuments), 'attributes'=>array(array('data-href', "#unlinked_documents"), array('data-action', "listUnlinkedDocuments")));
		$menuitems[] = array('label'=>getMLText('unlinked_content'), 'badge'=>count($unlinkedcontent), 'attributes'=>array(array('data-href', "#unlinked_content"), array('data-action', "listUnlinkedContent")));
		$menuitems[] = array('label'=>getMLText('missing_filesize'), 'badge'=>count($nofilesizeversions), 'attributes'=>array(array('data-href', "#missing_filesize"), array('data-action', "listMissingFileSize")));
		$menuitems[] = array('label'=>getMLText('missing_checksum'), 'badge'=>count($nochecksumversions), 'attributes'=>array(array('data-href', "#missing_checksum"), array('data-action', "listMissingChecksum")));
		$menuitems[] = array('label'=>getMLText('wrong_filetype'), 'badge'=>count($wrongfiletypeversions), 'attributes'=>array(array('data-href', "#wrong_filetype"), array('data-action', "listWrongFiletype")));
		self::showNavigationListWithBadges($menuitems);

		$this->contentHeading(getMLText("object_check_warning"));
		$menuitems = [];
		$menuitems[] = array('label'=>getMLText('duplicate_content'), 'badge'=>count($duplicateversions), 'attributes'=>array(array('data-href', "#duplicate_content"), array('data-action', "listDuplicateContent")));
		$menuitems[] = array('label'=>getMLText('duplicate_sequences'), 'badge'=>count($duplicatesequences), 'attributes'=>array(array('data-href', "#duplicate_sequences"), array('data-action', "listDuplicateSequence")));
		$menuitems[] = array('label'=>getMLText('docs_in_revision_no_access'), 'badge'=>count($docsinrevision), 'attributes'=>array(array('data-href', "#inrevision_no_access"), array('data-action', "listDocsInRevisionNoAccess")));
		$menuitems[] = array('label'=>getMLText('docs_in_reception_no_access'), 'badge'=>count($docsinreception), 'attributes'=>array(array('data-href', "#inreception_no_access"), array('data-action', "listDocsInReceptionNoAccess")));
		$menuitems[] = array('label'=>getMLText('docs_with_missing_revision_date'), 'badge'=>count($docsmissingrevsiondate), 'attributes'=>array(array('data-href', "#missing_revision_date"), array('data-action', "listDocsWithMissingRevisionDate")));
		foreach(array('review', 'approval', 'receipt', 'revision') as $process) {
			foreach(array('user', 'group') as $ug) {
				$menuitems[] = array('label'=>getMLText($process."s_without_".$ug), 'badge'=>count($processwithoutusergroup[$process][$ug]), 'attributes'=>array(array('data-href', "#".$process.'_without_'.$ug), array('data-action', "list".ucfirst($process).'Without'.ucfirst($ug))));
			}
		}
		self::showNavigationListWithBadges($menuitems);
		$this->columnEnd();
		$this->columnStart(9);

		echo '<div id="kkkk" class="ajax" data-view="ObjectCheck" data-action="'.($listtype ? $listtype : 'listRepair').'"></div>';

		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
