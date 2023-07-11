<?php
/**
 * Implementation of TransferDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for TransferDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_TransferDocument extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$allusers = $this->params['allusers'];
		$document = $this->params['document'];
		$folder = $this->params['folder'];
		$accessobject = $this->params['accessobject'];

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("transfer_document"));
?>
<form class="form-horizontal" action="../op/op.TransferDocument.php" name="form1" method="post">
<input type="hidden" name="documentid" value="<?php print $document->getID();?>">
<?php echo createHiddenFieldWithKey('transferdocument'); ?>
<?php
		$html = '<select name="userid" class="chzn-select">';
		$owner = $document->getOwner();
		$hasusers = false; // set to true if at least one user is found
		foreach ($allusers as $currUser) {
			if ($currUser->isGuest() || ($currUser->getID() == $owner->getID()))
				continue;

			$hasusers = true;
			$html .= "<option value=\"".$currUser->getID()."\"";
			if($folder->getAccessMode($currUser) < M_READ)
				$html .= " disabled data-warning=\"".getMLText('transfer_no_read_access')."\"";
			elseif($folder->getAccessMode($currUser) < M_READWRITE)
				$html .= " data-warning=\"".getMLText('transfer_no_write_access')."\"";
			$html .= ">" . htmlspecialchars($currUser->getLogin()." - ".$currUser->getFullName());
		}
		$html .= '</select>';
		if($hasusers) {
			$this->contentContainerStart();
			$this->formField(
				getMLText("transfer_to_user"),
				$html
			);
			$this->contentContainerEnd();
			$this->formSubmit("<i class=\"fa fa-exchange\"></i> ".getMLText('transfer_document'));
		} else {
			$this->warningMsg('transfer_no_users');
		}
?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
