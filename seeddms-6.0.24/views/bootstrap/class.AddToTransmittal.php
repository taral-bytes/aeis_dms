<?php
/**
 * Implementation of AddToTransmittal view
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
 * Class which outputs the html page for AddToTransmittal view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_AddToTransmittal extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$transmittals = $this->params['transmittals'];
		$content = $this->params['version'];

		$this->htmlStartPage(getMLText("my_documents"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_documents"), "my_documents");
		$this->contentHeading(getMLText("add_to_transmittal"));
?>
<form class="form-horizontal" action="../op/op.AddToTransmittal.php" name="form1" method="post">
<input type="hidden" name="documentid" value="<?php print $content->getDocument()->getID();?>">
<input type="hidden" name="version" value="<?php print $content->getVersion();?>">
<input type="hidden" name="action" value="addtotransmittal">
<?php echo createHiddenFieldWithKey('addtotransmittal'); ?>
<?php
		$this->contentContainerStart();
		$options = array();
		foreach ($transmittals as $transmittal) {
			$options[] = array($transmittal->getID(), htmlspecialchars($transmittal->getName()));
		}
		$this->formField(
			getMLText("transmittal"),
			array(
				'element'=>'select',
				'id'=>'assignTo',
				'name'=>'assignTo',
				'class'=>'chzn-select',
				'options'=>$options,
				'placeholder'=>getMLText('choose_transmittal'),
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit('<i class="fa fa-plus"></i> '.getMLText('add'));
?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
