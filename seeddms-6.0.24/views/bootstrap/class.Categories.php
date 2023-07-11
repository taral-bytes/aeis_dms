<?php
/**
 * Implementation of Categories view
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
 * Class which outputs the html page for Categories view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Categories extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$selcat = $this->params['selcategory'];
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
?>
$(document).ready( function() {
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {categoryid: $(this).val()});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.Categories.php?categoryid=' + $(this).val());
	});
});
<?php
		$this->printDeleteFolderButtonJs();
		$this->printDeleteDocumentButtonJs();
		$this->printClickDocumentJs();
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$selcat = $this->params['selcategory'];
		$conversionmgr = $this->params['conversionmgr'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		if($selcat) {
			$this->contentHeading(getMLText("category_info"));
			$c = $selcat->countDocumentsByCategory();
			echo "<table class=\"table table-condensed table-sm\">\n";
			echo "<tr><td>".getMLText('document_count')."</td><td>".($c)."</td></tr>\n";
			echo "</table>";

			$documents = $selcat->getDocumentsByCategory(10);
			if($documents) {
				print $this->folderListHeader();
				print "<tbody>\n";
				$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
				if($conversionmgr)
					$previewer->setConversionMgr($conversionmgr);
				foreach($documents as $doc) {
					echo $this->documentListRow($doc, $previewer);
				}
				print "</tbody></table>";
			}
		}
	} /* }}} */

	function actionmenu() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selcat = $this->params['selcategory'];

		if($selcat && !$selcat->isUsed()) {
?>
						<form style="display: inline-block;" method="post" action="../op/op.Categories.php" >
						<?php echo createHiddenFieldWithKey('removecategory'); ?>
						<input type="hidden" name="categoryid" value="<?php echo $selcat->getID()?>">
						<input type="hidden" name="action" value="removecategory">
						<?php $this->formSubmit('<i class="fa fa-remove"></i> '.getMLText('rm_document_category'),'','','danger');?>
						</form>
<?php
		}
	} /* }}} */

	function showCategoryForm($category) { /* {{{ */
?>
				<form class="form-horizontal" style="margin-bottom: 0px;" action="../op/op.Categories.php" method="post">
				<?php if(!$category) { ?>
					<?php echo createHiddenFieldWithKey('addcategory'); ?>
					<input type="hidden" name="action" value="addcategory">
				<?php } else { ?>
					<?php echo createHiddenFieldWithKey('editcategory'); ?>
					<input type="hidden" name="action" value="editcategory">
					<input type="hidden" name="categoryid" value="<?php echo $category->getID()?>">
				<?php } ?>
<?php
			$this->contentContainerStart();
			$this->formField(
				getMLText("name"),
				array(
					'element'=>'input',
					'type'=>'text',
					'name'=>'name',
					'value'=>($category ? htmlspecialchars($category->getName()) : '')
				)
			);
			$this->contentContainerEnd();
			$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('save'));
?>
				</form>

<?php
	} /* }}} */

	function form() { /* {{{ */
		$selcat = $this->params['selcategory'];

		$this->showCategoryForm($selcat);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$categories = $this->params['categories'];
		$selcat = $this->params['selcategory'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("global_document_categories"));
		$this->rowStart();
		$this->columnStart(6);
?>
<form class="form-horizontal">
<?php
		$options = array();
		$options[] = array("-1", getMLText("choose_category"));
		$options[] = array("0", getMLText("new_document_category"));
		foreach ($categories as $category) {
			$color = substr(md5($category->getName()), 0, 6);
			$options[] = array($category->getID(), htmlspecialchars($category->getName()), $selcat && $category->getID()==$selcat->getID(), array(array('data-before-title', "<i class='fa fa-circle' style='color: #".$color.";'></i> "), array('data-subtitle', $category->countDocumentsByCategory().' '.getMLText('documents'))));
		}
		$this->formField(
			null, //getMLText("selection"),
			array(
				'element'=>'select',
				'id'=>'selector',
				'class'=>'chzn-select',
				'options'=>$options,
				'placeholder'=>getMLText('choose_category'),
			)
		);
?>
</form>
	<div class="ajax" style="margin-bottom: 15px;" data-view="Categories" data-action="actionmenu" <?php echo ($selcat ? "data-query=\"categoryid=".$selcat->getID()."\"" : "") ?>></div>
		<div class="ajax" data-view="Categories" data-action="info" <?php echo ($selcat ? "data-query=\"categoryid=".$selcat->getID()."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->columnStart(6);
?>
			<div class="ajax" data-view="Categories" data-action="form" <?php echo ($selcat ? "data-query=\"categoryid=".$selcat->getID()."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->rowEnd();

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
