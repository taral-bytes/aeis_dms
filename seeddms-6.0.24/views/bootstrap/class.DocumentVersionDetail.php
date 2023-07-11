<?php
/**
 * Implementation of DocumentVersionDetail view
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
 * Class which outputs the html page for DocumentVersionDetail view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_DocumentVersionDetail extends SeedDMS_Theme_Style {

	/**
	 * Output a single attribute in the document info section
	 *
	 * @param object $attribute attribute
	 */
	protected function printAttribute($attribute) { /* {{{ */
		$attrdef = $attribute->getAttributeDefinition();
?>
		    <tr>
					<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
					<td><?php echo $this->getAttributeValue($attribute); ?></td>
		    </tr>
<?php
	} /* }}} */

	function preview() { /* {{{ */
		$dms = $this->params['dms'];
		$document = $this->params['document'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];
		$showfullpreview = $this->params['showFullPreview'];
		$converttopdf = $this->params['convertToPdf'];
		$pdfconverters = $this->params['pdfConverters'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$version = $this->params['version'];
		if(!$showfullpreview)
			return;

		$txt = $this->callHook('preDocumentPreview', $version);
		if(is_string($txt))
			echo $txt;
		$txt = $this->callHook('documentPreview', $version);
		if(is_string($txt))
			echo $txt;
		else {
			switch($version->getMimeType()) {
			case 'audio/mpeg':
			case 'audio/mp3':
			case 'audio/ogg':
			case 'audio/wav':
				$this->contentHeading(getMLText("preview"));
	?>
			<audio controls style="width: 100%;">
			<source  src="../op/op.ViewOnline.php?documentid=<?php echo $version->getDocument()->getID(); ?>&version=<?php echo $version->getVersion(); ?>" type="audio/mpeg">
			</audio>
	<?php
				break;
			case 'video/webm':
			case 'video/mp4':
			case 'video/avi':
			case 'video/msvideo':
			case 'video/x-msvideo':
			case 'video/x-matroska':
				$this->contentHeading(getMLText("preview"));
	?>
			<video controls style="width: 100%;">
			<source  src="../op/op.ViewOnline.php?documentid=<?php echo $version->getDocument()->getID(); ?>&version=<?php echo $version->getVersion(); ?>" type="video/mp4">
			</video>
	<?php
				break;
			case 'application/pdf':
				$this->contentHeading(getMLText("preview"));
	?>
				<iframe src="../pdfviewer/web/viewer.html?file=<?php echo urlencode('../../op/op.ViewOnline.php?documentid='.$version->getDocument()->getID().'&version='.$version->getVersion()); ?>" width="100%" height="700px"></iframe>
	<?php
				break;
			case 'image/svg+xml':
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
				$this->contentHeading(getMLText("preview"));
	?>
				<img src="../op/op.ViewOnline.php?documentid=<?php echo $version->getDocument()->getID(); ?>&version=<?php echo $version->getVersion(); ?>" width="100%">
	<?php
				break;
			default:
				$txt = $this->callHook('additionalDocumentPreview', $version);
				if(is_string($txt))
					echo $txt;
				break;
			}
		}
		$txt = $this->callHook('postDocumentPreview', $version);
		if(is_string($txt))
			echo $txt;

		if($converttopdf) {
			$pdfpreviewer = new SeedDMS_Preview_PdfPreviewer($cachedir, $timeout, $xsendfile);
			if($conversionmgr)
				$pdfpreviewer->setConversionMgr($conversionmgr);
			else
				$pdfpreviewer->setConverters($pdfconverters);
			if($pdfpreviewer->hasConverter($version->getMimeType())) {
				$this->contentHeading(getMLText("preview_pdf"));
?>
				<iframe src="../pdfviewer/web/viewer.html?file=<?php echo urlencode('../../op/op.PdfPreview.php?documentid='.$version->getDocument()->getID().'&version='.$version->getVersion()); ?>" width="100%" height="700px"></iframe>
<?php
			}
		}
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$accessop = $this->params['accessobject'];
		$version = $this->params['version'];
		$accessop = $this->params['accessobject'];
		$viewonlinefiletypes = $this->params['viewonlinefiletypes'];
		$enableversionmodification = $this->params['enableversionmodification'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewwidthdetail = $this->params['previewWidthDetail'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$status = $version->getStatus();
		$reviewStatus = $version->getReviewStatus();
		$approvalStatus = $version->getApprovalStatus();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->rowStart();
		$this->columnStart(4);
		$this->contentHeading(getMLText("document_infos"));
//		$this->contentContainerStart();
?>
<table class="table table-condensed table-sm">
<tr>
<td><?php printMLText("owner");?>:</td>
<td>
<?php
		$owner = $document->getOwner();
		print "<a class=\"infos\" href=\"mailto:".htmlspecialchars($owner->getEmail())."\">".htmlspecialchars($owner->getFullName())."</a>";
?>
</td>
</tr>
<?php
		if($document->getComment()) {
?>
<tr>
<td><?php printMLText("comment");?>:</td>
<td><?php print htmlspecialchars($document->getComment());?></td>
</tr>
<?php
		}
?>
<tr>
<td><?php printMLText("used_discspace");?>:</td>
<td><?php print SeedDMS_Core_File::format_filesize($document->getUsedDiskSpace());?></td>
</tr>
<tr>
<tr>
<td><?php printMLText("creation_date");?>:</td>
<td><?php print getLongReadableDate($document->getDate()); ?></td>
</tr>
<?php
		if($document->expires()) {
?>
		<tr>
		<td><?php printMLText("expires");?>:</td>
		<td><?php print getReadableDate($document->getExpires()); ?></td>
		</tr>
<?php
		}
		if($document->getKeywords()) {
?>
<tr>
<td><?php printMLText("keywords");?>:</td>
<td><?php print htmlspecialchars($document->getKeywords());?></td>
</tr>
<?php
		}
		if ($document->isLocked()) {
			$lockingUser = $document->getLockingUser();
?>
<tr>
	<td><?php printMLText("lock_status");?>:</td>
	<td><?php printMLText("lock_message", array("email" => $lockingUser->getEmail(), "username" => htmlspecialchars($lockingUser->getFullName())));?></td>
</tr>
<?php
		}
?>
</tr>
<?php
		$attributes = $document->getAttributes();
		if($attributes) {
			foreach($attributes as $attribute) {
				$arr = $this->callHook('showDocumentAttribute', $document, $attribute);
				if(is_array($arr)) {
					echo "<tr>";
					echo "<td>".$arr[0].":</td>";
					echo "<td>".$arr[1]."</td>";
					echo "</tr>";
				} elseif(is_string($arr)) {
					echo $arr;
				} else {
					$this->printAttribute($attribute);
				}
			}
		}
?>
</table>
<?php
//		$this->contentContainerEnd();
		$this->preview();
		$this->columnEnd();
		$this->columnStart(8);

		// verify if file exists
		$file_exists=file_exists($dms->contentDir . $version->getPath());

		$this->contentHeading(getMLText("details_version", array ("version" => $version->getVersion())));
		$this->contentContainerStart();
		print "<table class=\"table table-condensed\">";
		print "<thead>\n<tr>\n";
		print "<th colspan=\"2\">".htmlspecialchars($version->getOriginalFileName())."</th>\n";
//		print "<th width='25%'>".getMLText("comment")."</th>\n";
		print "<th width='20%'>".getMLText("status")."</th>\n";
		print "<th width='25%'></th>\n";
		print "</tr>\n</thead>\n<tbody>\n";
		print "<tr>\n";
		print "<td><ul class=\"unstyled\">";

		print "</ul>";
		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidthdetail, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);
		$previewer->createPreview($version);
		if ($file_exists) {
			if ($viewonlinefiletypes && (in_array(strtolower($version->getFileType()), $viewonlinefiletypes) || in_array(strtolower($version->getMimeType()), $viewonlinefiletypes))) {
				print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$version->getDocument()->getId()."&version=". $version->getVersion()."\">";
			} else {
				print "<a href=\"../op/op.Download.php?documentid=".$version->getDocument()->getId()."&version=".$version->getVersion()."\">";
			}
		}
		if($previewer->hasPreview($version)) {
			print("<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$version->getVersion()."&width=".$previewwidthdetail."\" title=\"".htmlspecialchars($version->getMimeType())."\">");
		} else {
			print "<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"".$this->getMimeIcon($version->getFileType())."\" title=\"".htmlspecialchars($version->getMimeType())."\">";
		}
		if ($file_exists) {
			print "</a>";
		}
		print "</td>\n";

		print "<td><ul class=\"unstyled\">\n";
		print "<li>".getMLText('version').": ".$version->getVersion()."</li>\n";

		if ($file_exists)
			print "<li>". SeedDMS_Core_File::format_filesize($version->getFileSize()) .", ".htmlspecialchars($version->getMimeType())."</li>";
		else print "<li><span class=\"warning\">".getMLText("document_deleted")."</span></li>";

		$updatingUser = $version->getUser();
		print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".htmlspecialchars($updatingUser->getEmail())."\">".htmlspecialchars($updatingUser->getFullName())."</a></li>";
		print "<li>".getLongReadableDate($version->getDate())."</li>";

		print "</ul>\n";
		$txt = $this->callHook('showVersionComment', $version);
		if($txt) {
			echo $txt;
		} else {
			if($version->getComment())
				print "<p style=\"font-style: italic;\">".htmlspecialchars($version->getComment())."</p>";
		}
		print "<ul class=\"actions unstyled\">\n";
		$attributes = $version->getAttributes();
		if($attributes) {
			foreach($attributes as $attribute) {
				$arr = $this->callHook('showDocumentContentAttribute', $version, $attribute);
				if(is_array($arr)) {
					print "<li>".$arr[0].": ".$arr[1]."</li>\n";
				} else {
					$attrdef = $attribute->getAttributeDefinition();
					print "<li>".htmlspecialchars($attrdef->getName()).": ".htmlspecialchars(implode(', ', $attribute->getValueAsArray()))."</li>\n";
				}
			}
		}
		print "</ul></td>\n";

		print "<td width='10%'>";
		print getOverallStatusText($status["status"]);
		if ( $status["status"]==S_DRAFT_REV || $status["status"]==S_DRAFT_APP || $status["status"]==S_IN_WORKFLOW || $status["status"]==S_EXPIRED ){
			print "<br><span".($document->hasExpired()?" class=\"warning\" ":"").">".(!$document->getExpires() ? getMLText("does_not_expire") : getMLText("expires").": ".getReadableDate($document->getExpires()))."</span>";
		}
		print "</td>";

		print "<td>";

		//if (($document->getAccessMode($user) >= M_READWRITE)) {
		if ($file_exists){
			print "<ul class=\"actions unstyled\">";
			if($accessop->check_controller_access('Download', array('action'=>'run')))
				print "<li><a href=\"../op/op.Download.php?documentid=".$document->getID()."&version=".$version->getVersion()."\" title=\"".htmlspecialchars($version->getMimeType())."\"><i class=\"fa fa-download\"></i> ".getMLText("download")."</a>";
			if ($viewonlinefiletypes && (in_array(strtolower($version->getFileType()), $viewonlinefiletypes) || in_array(strtolower($version->getMimeType()), $viewonlinefiletypes)))
				if($accessop->check_controller_access('ViewOnline', array('action'=>'run')))
					print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$document->getID()."&version=".$version->getVersion()."\"><i class=\"fa fa-star\"></i> " . getMLText("view_online") . "</a>";
			print "</ul>";
		}

		print "<ul class=\"actions unstyled\">";
		if ($file_exists){
			if($accessop->mayEditVersion($version->getDocument())) {
				print "<li><a href=\"../out/out.EditOnline.php?documentid=".$document->getId()."&version=".$version->getVersion()."\"><i class=\"fa fa-edit\"></i>".getMLText("edit_version")."</a></li>";
			}
		}
		if($accessop->mayRemoveVersion($version->getDocument())) {
			print "<li><a href=\"out.RemoveVersion.php?documentid=".$document->getID()."&version=".$version->getVersion()."\"><i class=\"fa fa-remove\"></i> ".getMLText("rm_version")."</a></li>";
		}
		if($accessop->mayOverrideStatus($version->getDocument())) {
			print "<li><a href='../out/out.OverrideContentStatus.php?documentid=".$document->getID()."&version=".$version->getVersion()."'><i class=\"fa fa-align-justify\"></i>".getMLText("change_status")."</a></li>";
		}
		if($accessop->mayEditComment($version->getDocument())) {
			print "<li><a href=\"out.EditComment.php?documentid=".$document->getID()."&version=".$version->getVersion()."\"><i class=\"fa fa-comment\"></i> ".getMLText("edit_comment")."</a></li>";
		}
		if($accessop->mayEditAttributes($version->getDocument())) {
			print "<li><a href=\"out.EditAttributes.php?documentid=".$document->getID()."&version=".$version->getVersion()."\"><i class=\"fa fa-edit\"></i> ".getMLText("edit_attributes")."</a></li>";
		}
		print "</ul>";

		echo "</td>";
		print "</tr></tbody>\n</table>\n";

		$this->contentContainerEnd();

		$this->rowStart();
		$this->columnStart(6);

		if (is_array($reviewStatus) && count($reviewStatus)>0) { /* {{{ */

			print "<legend>".getMLText('reviewers')."</legend>";
			print "<table class=\"table table-condensed\">\n";
			print "<tr>\n";
			print "<td><b>".getMLText("name")."</b></td>\n";
			print "<td><b>".getMLText("last_update")."</b></td>\n";
//			print "<td width='25%'><b>".getMLText("comment")."</b></td>";
			print "<td><b>".getMLText("status")."</b></td>\n";
			print "</tr>\n";

			foreach ($reviewStatus as $r) {
				$required = null;
				switch ($r["type"]) {
					case 0: // Reviewer is an individual.
						$required = $dms->getUser($r["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_user")." '".$r["required"]."'";
						}
						else {
							$reqName = "<i class=\"fa fa-user\"></i> ".htmlspecialchars($required->getFullName()." (".$required->getLogin().")");
						}
						break;
					case 1: // Reviewer is a group.
						$required = $dms->getGroup($r["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_group")." '".$r["required"]."'";
						}
						else {
							$reqName = "<i class=\"fa fa-group\"></i> ".htmlspecialchars($required->getName());
						}
						break;
				}
				print "<tr".($r['status'] == 1 ? ' class="success"' : ($r['status'] == -1 ? ' class="error"' : '')).">\n";
				print "<td>".$reqName."</td>\n";
				print "<td><i style=\"font-size: 80%;\">".getLongReadableDate($r["date"])." - ";
				/* $updateUser is the user who has done the review */
				$updateUser = $dms->getUser($r["userID"]);
				print (is_object($updateUser) ? htmlspecialchars($updateUser->getFullName()." (".$updateUser->getLogin().")") : "unknown user id '".$r["userID"]."'")."</i><br />";
				print htmlspecialchars($r["comment"]);
				if($r['file']) {
					echo "<br />";
					echo "<a href=\"../op/op.Download.php?documentid=".$documentid."&reviewlogid=".$r['reviewLogID']."\" class=\"btn btn-mini\"><i class=\"fa fa-download\"></i> ".getMLText('download')."</a>";
				}
				print "</td>\n";
				print "<td>".getReviewStatusText($r["status"])."</td>\n";
				print "</tr>\n";
			}
			print "</table>\n";
		} /* }}} */

		$this->columnEnd();
		$this->columnStart(6);

		if (is_array($approvalStatus) && count($approvalStatus)>0) { /* {{{ */

			print "<legend>".getMLText('approvers')."</legend>";
			print "<table class=\"table table-condensed\">\n";
			print "<tr>\n";
			print "<td><b>".getMLText("name")."</b></td>\n";
			print "<td><b>".getMLText("last_update")."</b></td>\n";
//			print "<td width='25%'><b>".getMLText("comment")."</b></td>";
			print "<td><b>".getMLText("status")."</b></td>\n";
			print "</tr>\n";

			foreach ($approvalStatus as $a) {
				$required = null;
				switch ($a["type"]) {
					case 0: // Approver is an individual.
						$required = $dms->getUser($a["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_user")." '".$a["required"]."'";
						}
						else {
							$reqName = "<i class=\"fa fa-user\"></i> ".htmlspecialchars($required->getFullName()." (".$required->getLogin().")");
						}
						break;
					case 1: // Approver is a group.
						$required = $dms->getGroup($a["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_group")." '".$a["required"]."'";
						}
						else {
							$reqName = "<i class=\"fa fa-group\"></i> ".htmlspecialchars($required->getName());
						}
						break;
				}
				print "<tr".($a['status'] == 1 ? ' class="success"' : ($a['status'] == -1 ? ' class="error"' : ($a['status'] == -2 ? ' class=""' : ''))).">\n";
				print "<td>".$reqName."</td>\n";
				print "<td><i style=\"font-size: 80%;\">".getLongReadableDate($a["date"])." - ";
				/* $updateUser is the user who has done the approval */
				$updateUser = $dms->getUser($a["userID"]);
				print (is_object($updateUser) ? htmlspecialchars($updateUser->getFullName()." (".$updateUser->getLogin().")") : "unknown user id '".$a["userID"]."'")."</i><br />";	
				print htmlspecialchars($a["comment"]);
				if($a['file']) {
					echo "<br />";
					echo "<a href=\"../op/op.Download.php?documentid=".$documentid."&approvelogid=".$a['approveLogID']."\" class=\"btn btn-mini\"><i class=\"fa fa-download\"></i> ".getMLText('download')."</a>";
				}
				echo "</td>\n";
				print "<td>".getApprovalStatusText($a["status"])."</td>\n";
				print "</tr>\n";
			}
			print "</table>\n";
		} /* }}} */

		$this->columnEnd();
		$this->rowEnd();

		/* Get attachments exclusively for this version, without those
		 * attached to the document
		 */
		$files = $document->getDocumentFiles($version->getVersion(), false);
		/* Do the regular filtering by isPublic and access rights */
		$files = SeedDMS_Core_DMS::filterDocumentFiles($user, $files);

		if (count($files) > 0) { /* {{{ */
			$this->contentHeading(getMLText("linked_files"));
			$this->contentContainerStart();

			$documentid = $document->getID();

			print "<table class=\"table\">";
			print "<thead>\n<tr>\n";
			print "<th width='20%'></th>\n";
			print "<th width='20%'>".getMLText("file")."</th>\n";
			print "<th width='40%'>".getMLText("comment")."</th>\n";
			print "<th width='20%'></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";

			foreach($files as $file) {

				$file_exists=file_exists($dms->contentDir . $file->getPath());

				$responsibleUser = $file->getUser();

				print "<tr>";
				print "<td>";
				$previewer->createPreview($file, $previewwidthdetail);
				if($file_exists) {
					if ($viewonlinefiletypes && (in_array(strtolower($file->getFileType()), $viewonlinefiletypes) || in_array(strtolower($file->getMimeType()), $viewonlinefiletypes))) {
						print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $file->getID()."\">";
					} else {
						print "<a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\">";
					}
				}
				if($previewer->hasPreview($file)) {
					print("<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"../op/op.Preview.php?documentid=".$document->getID()."&file=".$file->getID()."&width=".$previewwidthdetail."\" title=\"".htmlspecialchars($file->getMimeType())."\">");
				} else {
					print "<img class=\"mimeicon\" src=\"".$this->getMimeIcon($file->getFileType())."\" title=\"".htmlspecialchars($file->getMimeType())."\">";
				}
				if($file_exists) {
					print "</a>";
				}
				print "</td>";
				
				print "<td><ul class=\"unstyled\">\n";
				print "<li>".htmlspecialchars($file->getName())."</li>\n";
				print "<li>".htmlspecialchars($file->getOriginalFileName())."</li>\n";
				if ($file_exists)
					print "<li>".SeedDMS_Core_File::format_filesize(filesize($dms->contentDir . $file->getPath())) ." bytes, ".htmlspecialchars($file->getMimeType())."</li>";
				else print "<li>".htmlspecialchars($file->getMimeType())." - <span class=\"warning\">".getMLText("document_deleted")."</span></li>";

				print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".htmlspecialchars($responsibleUser->getEmail())."\">".htmlspecialchars($responsibleUser->getFullName())."</a></li>";
				print "<li>".getLongReadableDate($file->getDate())."</li>";
				if($file->getVersion())
					print "<li>".getMLText('linked_to_this_version')."</li>";
				print "</ul></td>";
				print "<td>".htmlspecialchars($file->getComment())."</td>";
			
				print "<td><ul class=\"unstyled actions\">";
				if ($file_exists) {
					print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\"><i class=\"fa fa-download\"></i>".getMLText('download')."</a></li>";
					if ($viewonlinefiletypes && (in_array(strtolower($file->getFileType()), $viewonlinefiletypes) || in_array(strtolower($file->getMimeType()), $viewonlinefiletypes))) {
						print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $file->getID()."\"><i class=\"fa fa-star\"></i>" . getMLText("view_online") . "</a></li>";
					}
				} else print "<li><img class=\"mimeicon\" src=\"images/icons/".$this->getMimeIcon($file->getFileType())."\" title=\"".htmlspecialchars($file->getMimeType())."\">";
				echo "</ul><ul class=\"unstyled actions\">";
				if (($document->getAccessMode($user) == M_ALL)||($file->getUserID()==$user->getID())) {
					print "<li><a href=\"out.RemoveDocumentFile.php?documentid=".$documentid."&fileid=".$file->getID()."\"><i class=\"fa fa-remove\"></i>".getMLText("delete")."</a></li>";
					print "<li><a href=\"out.EditDocumentFile.php?documentid=".$documentid."&fileid=".$file->getID()."\"><i class=\"fa fa-edit\"></i>".getMLText("edit")."</a></li>";
				}
				print "</ul></td>";		
				
				print "</tr>";
			}
			print "</tbody>\n</table>\n";	

			$this->contentContainerEnd();
		} /* }}} */

		if($user->isAdmin() || $user->getId() == $document->getOwner()->getId()) {
			$this->contentHeading(getMLText("status"));
			$this->contentContainerStart();
			$statuslog = $version->getStatusLog();
			echo "<table class=\"table table-condensed\"><thead>";
			echo "<th>".getMLText('date')."</th><th>".getMLText('status')."</th><th>".getMLText('user')."</th><th>".getMLText('comment')."</th></tr>\n";
			echo "</thead><tbody>";
			foreach($statuslog as $entry) {
				if($suser = $dms->getUser($entry['userID']))
					$fullname = $suser->getFullName();
				else
					$fullname = "--";
				echo "<tr><td>".getLongReadableDate($entry['date'])."</td><td>".getOverallStatusText($entry['status'])."</td><td>".$fullname."</td><td>".$entry['comment']."</td></tr>\n";
			}
			print "</tbody>\n</table>\n";
			$this->contentContainerEnd();

			$wkflogs = $version->getWorkflowLog();
			if($wkflogs) {
				$this->contentHeading(getMLText("workflow_summary"));
				$this->contentContainerStart();
				echo "<table class=\"table table-condensed\"><thead>";
				echo "<th>".getMLText('date')."</th><th>".getMLText('action')."</th><th>".getMLText('user')."</th><th>".getMLText('comment')."</th></tr>\n";
				echo "</thead><tbody>";
				foreach($wkflogs as $wkflog) {
					echo "<tr>";
					echo "<td>".$wkflog->getDate()."</td>";
					echo "<td>".$wkflog->getTransition()->getAction()->getName()."</td>";
					$loguser = $wkflog->getUser();
					echo "<td>".$loguser->getFullName()."</td>";
					echo "<td>".$wkflog->getComment()."</td>";
					echo "</tr>";
				}
				print "</tbody>\n</table>\n";
				$this->contentContainerEnd();
			}
			$this->rowStart();
			/* Check for an existing review log, even if the workflowmode
			 * is set to traditional_only_approval. There may be old documents
			 * that still have a review log if the workflow mode has been
			 * changed afterwards.
			 */
			if($version->getReviewStatus(10)) {
				$this->columnStart(6);
				$this->printProtocol($version, 'review');
				$this->columnEnd();
			}
			if($version->getApprovalStatus(10)) {
				$this->columnStart(6);
				$this->printProtocol($version, 'approval');
				$this->columnEnd();
			}
			$this->rowEnd();
			if($version->getReceiptStatus()) {
			$this->rowStart();
			$this->columnStart(12);
			$this->printProtocol($version, 'receipt');
			$this->columnEnd();
			$this->rowEnd();
			}
			if($version->getRevisionStatus()) {
			$this->rowStart();
			$this->columnStart(12);
			$this->printProtocol($version, 'revision');
			$this->columnEnd();
			$this->rowEnd();
			}
		}
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
