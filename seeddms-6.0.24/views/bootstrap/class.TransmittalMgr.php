<?php
/**
 * Implementation of TransmittalMgr view
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
 * Class which outputs the html page for TransmittalMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_TransmittalMgr extends SeedDMS_Theme_Style {

	use TransmittalDeleteButton;
	use TransmittalUpdateButton;

	function js() { /* {{{ */
		$showtree = $this->params['showtree'];
		$onepage = $this->params['onepage'];

		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors', 'cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
		$this->printDeleteDocumentButtonJs();
		$this->printDeleteItemButtonJs();
		$this->printUpdateItemButtonJs();
		if($onepage)
			$this->printClickDocumentJs();
?>
function runValidation() {
	$("#form1").validate({
		rules: {
			name: {
				required: true
			},
		},
		messages: {
			name: "<?php printMLText("js_no_name");?>",
		}
	});
}
$(document).ready( function() {
	$('body').on('click', '.selecttransmittal', function(ev){
		ev.preventDefault();
		$('div.ajax').trigger('update', {transmittalid: $(ev.currentTarget).data('transmittalid')});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.TransmittalMgr.php?transmittalid=' + $(ev.currentTarget).data('transmittalid'));
	});
});
<?php
	} /* }}} */

	protected function showTransmittalForm($transmittal) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
?>
	<form action="../op/op.TransmittalMgr.php" method="post" enctype="multipart/form-data" name="form<?php print $transmittal ? $transmittal->getID() : '0';?>" id="form1">
<?php
		if($transmittal) {
			echo createHiddenFieldWithKey('edittransmittal');
?>
	<input type="hidden" name="transmittalid" value="<?php print $transmittal->getID();?>">
	<input type="hidden" name="action" value="edittransmittal">
<?php
		} else {
			echo createHiddenFieldWithKey('addtransmittal');
?>
	<input type="hidden" name="action" value="addtransmittal">
<?php
		}
		$this->contentContainerStart();
		$this->formField(
			getMLText("name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
				'value'=>($transmittal ? htmlspecialchars($transmittal->getName()) : '')
			)
		);
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'id'=>'comment',
				'name'=>'comment',
				'rows'=>4,
				'value'=>($transmittal ? htmlspecialchars($transmittal->getComment()) : '')
			)
		);
		$this->contentContainerEnd();
		if($transmittal && $accessop->check_controller_access('TransmittalMgr', array('action'=>'edittransmittal')) || !$transmittal && $accessop->check_controller_access('TransmittalMgr', array('action'=>'addtransmittal'))) {
			$this->formSubmit("<i class=\"fa fa-save\"></i> ".($transmittal ? getMLText('save') : getMLText('add_transmittal')));
		}
?>
	</form>
<?php
	} /* }}} */

	function form() { /* {{{ */
		$seltransmittal = $this->params['seltransmittal'];

		$this->showTransmittalForm($seltransmittal);
	} /* }}} */

	protected function showTransmittalItems($seltransmittal) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
		$cachedir = $this->params['cachedir'];
		$timeout = $this->params['timeout'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		$previewer->setConverters($previewconverters);

		if($seltransmittal) {
			$items = $seltransmittal->getItems();
			if($items) {
				print "<table class=\"table table-condensed table-sm\">";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";
				print "<th>".getMLText("name")."</th>\n";
				print "<th>".getMLText("status")."</th>\n";
				print "<th>".getMLText("document")."</th>\n";
				print "<th>".getMLText("action")."</th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
				foreach($items as $item) {
					if($content = $item->getContent()) {
						$document = $content->getDocument();
						$latestcontent = $document->getLatestContent();
						if ($document->getAccessMode($user) >= M_READ) {
//							echo "<tr id=\"table-row-transmittalitem-".$item->getID()."\">";
							echo $this->documentListRowStart($document);
							echo $this->documentListRow($document, $previewer, true, $content->getVersion());
							echo "<td><div class=\"list-action\">";
							$this->printDeleteItemButton($item, getMLText('transmittalitem_removed'));
							if($latestcontent->getVersion() != $content->getVersion())
								$this->printUpdateItemButton($item, getMLText('transmittalitem_updated', array('prevversion'=>$content->getVersion(), 'newversion'=>$latestcontent->getVersion())));
							echo "</div></td>";
							echo $this->documentListRowEnd($document);
						}
					} else {
						echo "<tr id=\"table-row-transmittalitem-".$item->getID()."\">";
						echo "<td colspan=\"5\">content ist weg</td>";
						echo "</tr>";
					}
				}
				print "</tbody>\n</table>\n";
				print "<a class=\"btn btn-primary\" href=\"../op/op.TransmittalDownload.php?transmittalid=".$seltransmittal->getID()."\">".getMLText('download')."</a>";
			}
		}
	} /* }}} */

	function items() { /* {{{ */
		$seltransmittal = $this->params['seltransmittal'];

		$this->showTransmittalItems($seltransmittal);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
		$seltransmittal = $this->params['seltransmittal'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("my_transmittals"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_transmittals"), "my_documents");
		$this->contentHeading(getMLText("my_transmittals"));
		$this->rowStart();
		$this->columnStart(4);

		$transmittals = $dms->getAllTransmittals($user);

		if ($transmittals){
			print "<table class=\"table table-condensed table-sm\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("comment")."</th>\n";
			print "<th>".getMLText("transmittal_size")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($transmittals as $transmittal) {
				print "<tr>\n";
				print "<td>".$transmittal->getName()."</td>";
				print "<td>".$transmittal->getComment()."</td>";
				$items = $transmittal->getItems();
				print "<td>".count($items)." <em>(".SeedDMS_Core_File::format_filesize($transmittal->getSize()).")</em></td>";
				print "<td>";
				print "<div class=\"list-action\">";
				print "<a class=\"selecttransmittal\" data-transmittalid=\"".$transmittal->getID()."\" href=\"../out/out.TransmittalMgr.php?transmittalid=".$transmittal->getID()."\" title=\"".getMLText("edit_transmittal_props")."\"><i class=\"fa fa-edit\"></i></a>";
				if($transmittal && $accessop->check_controller_access('TransmittalMgr', array('action'=>'removetransmittal'))) {
					print "<a data-transmittalid=\"".$transmittal->getID()."\" href=\"../out/out.RemoveTransmittal.php?transmittalid=".$transmittal->getID()."\" title=\"".getMLText("rm_transmittal")."\"><i class=\"fa fa-remove\"></i></a>";
				}
				print "</div>";
				print "</td>";
				print "</tr>\n";
			}
			print "</tbody>\n</table>\n";
		}

		$this->columnEnd();
		$this->columnStart(8);
		if($accessop->check_view_access($this, array('action'=>'form'))) {
?>
		<div class="ajax" data-view="TransmittalMgr" data-action="form" data-afterload="()=>{runValidation();}" <?php echo ($seltransmittal ? "data-query=\"transmittalid=".$seltransmittal->getID()."\"" : "") ?>></div>
<?php
		}
		if($accessop->check_view_access($this, array('action'=>'items'))) {
?>
		<div class="ajax" data-view="TransmittalMgr" data-action="items" <?php echo ($seltransmittal ? "data-query=\"transmittalid=".$seltransmittal->getID()."\"" : "") ?>></div>
<?php
		}
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
