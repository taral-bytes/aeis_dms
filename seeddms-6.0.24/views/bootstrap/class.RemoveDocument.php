<?php
/**
 * Implementation of RemoveDocument view
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
 * Class which outputs the html page for RemoveDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RemoveDocument extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		parent::show();
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("rm_document"));
		if ($document->isCheckedOut()) {
			$msg = getMLText('document_is_checked_out_remove');
			$this->warningMsg($msg);
		}

		$this->warningMsg(getMLText("confirm_rm_document", array ("documentname" => htmlspecialchars($document->getName()))));
?>
<form action="../op/op.RemoveDocument.php" name="form1" method="post">
<input type="Hidden" name="documentid" value="<?php print $document->getID();?>">
<?php echo createHiddenFieldWithKey('removedocument'); ?>
<p><?php $this->formSubmit('<i class="fa fa-remove"></i> '.getMLText('rm_document'),'','','danger');?></p>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
