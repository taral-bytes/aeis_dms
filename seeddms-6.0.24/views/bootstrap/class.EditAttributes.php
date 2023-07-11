<?php
/**
 * Implementation of EditAttributes view
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
 * Class which outputs the html page for EditAttributes view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_EditAttributes extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$version = $this->params['version'];
		$attrdefs = $this->params['attrdefs'];

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);

		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText("edit_attributes"));
?>
<form class="form-horizontal" action="../op/op.EditAttributes.php" name="form1" method="POST">
	<?php echo createHiddenFieldWithKey('editattributes'); ?>
	<input type="hidden" name="documentid" value="<?php print $document->getID();?>">
	<input type="hidden" name="version" value="<?php print $version->getVersion();?>">

<?php
		if($attrdefs) {
			$this->contentContainerStart();
			foreach($attrdefs as $attrdef) {
				$arr = $this->callHook('editDocumentContentAttribute', $version, $attrdef);
				if(is_array($arr)) {
					if($arr) {
						$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
					}
				} elseif(is_string($arr)) {
					echo $arr;
				} else {
					$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, $version->getAttribute($attrdef)));
				}
			}
			$arrs = $this->callHook('addDocumentContentAttributes', $version);
			if(is_array($arrs)) {
				foreach($arrs as $arr) {
					$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
				}
			} elseif(is_string($arrs)) {
				echo $arrs;
			}
			$this->contentContainerEnd();
			$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('save'));
		} else {
			$this->warningMsg(getMLText('no_attributes_defined'));
		}
		$this->columnEnd();
		$this->columnStart(6);
?>
	<div class="ajax" data-view="ViewDocument" data-action="preview" <?php echo ($document ? "data-query=\"documentid=".$document->getID()."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->rowEnd();
?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
