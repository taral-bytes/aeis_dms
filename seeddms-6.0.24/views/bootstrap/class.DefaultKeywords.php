<?php
/**
 * Implementation of DefaultKeywords view
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
 * Class which outputs the html page for DefaultKeywords view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_DefaultKeywords extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
function runValidation() {
	$("#form").validate({
		rules: {
			name: {
				required: true
			},
		},
		messages: {
			name: "<?php printMLText("js_no_name");?>",
		}
	});
	$(".formk").validate({
		rules: {
			keywords: {
				required: true
			},
		},
		messages: {
			keywords: "<?php printMLText("js_no_name");?>",
		}
	});
	$(".formn").validate({
		rules: {
			keywords: {
				required: true
			},
		},
		messages: {
			keywords: "<?php printMLText("js_no_name");?>",
		}
	});
}

$(document).ready( function() {
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {categoryid: $(this).val()});
	});
});
<?php
	} /* }}} */

	function actionmenu() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selcategory = $this->params['selcategory'];

		if($selcategory && $selcategory->getId() > 0) {
?>
						<form style="display: inline-block;" method="post" action="../op/op.DefaultKeywords.php" >
						<?php echo createHiddenFieldWithKey('removecategory'); ?>
						<input type="hidden" name="categoryid" value="<?php echo $selcategory->getId(); ?>">
						<input type="hidden" name="action" value="removecategory">
						<?php $this->formSubmit('<i class="fa fa-remove"></i> '.getMLText('rm_default_keyword_category'),'','','danger');?>
						</form>
<?php
		}
	} /* }}} */

	function form() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$category = $this->params['selcategory'];

		$this->showKeywordForm($category, $user);
	} /* }}} */

	function showKeywordForm($category, $user) { /* {{{ */
		if(!$category) {
?>
			
			<form class="form-horizontal" action="../op/op.DefaultKeywords.php" method="post" id="form">
  		<?php echo createHiddenFieldWithKey('addcategory'); ?>
			<input type="hidden" name="action" value="addcategory">
<?php
			$this->contentContainerStart();
			$this->formField(
				getMLText("name"),
				array(
					'element'=>'input',
					'type'=>'text',
					'name'=>'name',
					'value'=>''
				)
			);
			$this->contentContainerEnd();
			$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('new_default_keyword_category'));
?>
			</form>
<?php
		} else {
			$owner = $category->getOwner();
			if ((!$user->isAdmin()) && ($owner->getID() != $user->getID())) return;
?>
				<form class="form-horizontal form" action="../op/op.DefaultKeywords.php" method="post">
					<?php echo createHiddenFieldWithKey('editcategory'); ?>
					<input type="hidden" name="action" value="editcategory">
					<input type="hidden" name="categoryid" value="<?= $category->getId() ?>">
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
			$this->contentHeading(getMLText("default_keywords"));
//			$this->contentContainerStart();
?>
						<?php
							$lists = $category->getKeywordLists();
							if (count($lists) == 0)
								print getMLText("no_default_keywords");
							else
								foreach ($lists as $list) {
?>
									<form class="form-inline form formn mb-3" style="display: inline-block;" method="post" action="../op/op.DefaultKeywords.php">
  								<?php echo createHiddenFieldWithKey('editkeywords'); ?>
									<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
									<input type="Hidden" name="keywordsid" value="<?php echo $list["id"]?>">
									<input type="Hidden" name="action" value="editkeywords">
									<input name="keywords" class="keywords form-control" type="text" value="<?php echo htmlspecialchars($list["keywords"]) ?>">
									<button class="btn btn-primary btn-mini btn-sm" title="<?php echo getMLText("save")?>"><i class="fa fa-save"></i> <?php echo getMLText("save")?></button>
									<!--	 <input name="action" value="removekeywords" type="Image" src="images/del.gif" title="<?php echo getMLText("delete")?>" border="0"> &nbsp; -->
									</form>
									<form style="display: inline-block;" method="post" action="../op/op.DefaultKeywords.php" >
  								<?php echo createHiddenFieldWithKey('removekeywords'); ?>
									<input type="hidden" name="categoryid" value="<?php echo $category->getID()?>">
									<input type="hidden" name="keywordsid" value="<?php echo $list["id"]?>">
									<input type="hidden" name="action" value="removekeywords">
									<button class="btn btn-danger btn-mini btn-sm" title="<?php echo getMLText("delete")?>"><i class="fa fa-remove"></i> <?php echo getMLText("delete")?></button>
									</form>
									<br>
						<?php }  ?>
				
				<div class="control-group">
					<label class="control-label"></label>
					<div class="controls">
					  <form class="form-inline formk" action="../op/op.DefaultKeywords.php" method="post">
  				  <?php echo createHiddenFieldWithKey('newkeywords'); ?>
						<input type="Hidden" name="action" value="newkeywords">
						<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
						<input type="text" class="keywords form-control" name="keywords">&nbsp;
							<?php $this->formSubmit('<i class="fa fa-save"></i> '.getMLText('new_default_keywords'),'','','primary');?>
						</form>
					</div>
				</div>

<?php
//			$this->contentContainerEnd();
		}
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$categories = $this->params['categories'];
		$selcategory = $this->params['selcategory'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("global_default_keywords"));
		$this->rowStart();
		$this->columnStart(4);
?>
<form class="form-horizontal">
<?php
		$options = array();
		$options[] = array("-1", getMLText("choose_category"));
		$options[] = array("0", getMLText("new_default_keyword_category"));
		foreach ($categories as $category) {
			$owner = $category->getOwner();
			if ($user->isAdmin() || ($owner->getID() == $user->getID()))
				$options[] = array($category->getID(), htmlspecialchars($category->getName()), $selcategory && $category->getID()==$selcategory->getID(), array(array('data-subtitle', $category->countKeywordLists().' '.getMLText('keywords'))));
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
	<div class="ajax" style="margin-bottom: 15px;" data-view="DefaultKeywords" data-action="actionmenu" <?php echo ($selcategory ? "data-query=\"categoryid=".$selcategory->getId()."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->columnStart(8);
?>
		<div class="ajax" data-view="DefaultKeywords" data-action="form" data-afterload="()=>{runValidation();}" <?php echo ($selcategory ? "data-query=\"categoryid=".$selcategory->getId()."\"" : "") ?>></div>
		</div>
<?php
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
