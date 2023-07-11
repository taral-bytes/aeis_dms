<?php
/**
 * Implementation of EditFolder view
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
 * Class which outputs the html page for EditFolder view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_EditFolder extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
$(document).ready(function() {
	$("#form1").validate({
		messages: {
			name: "<?php printMLText("js_no_name");?>",
			comment: "<?php printMLText("js_no_comment");?>"
		},
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$attrdefs = $this->params['attrdefs'];
		$rootfolderid = $this->params['rootfolderid'];
		$strictformcheck = $this->params['strictformcheck'];
		$nofolderformfields = $this->params['nofolderformfields'];
		$orderby = $this->params['orderby'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true), "view_folder", $folder);
		$this->contentHeading(getMLText("edit_folder_props"));
?>
<form class="form-horizontal" action="../op/op.EditFolder.php" id="form1" name="form1" method="post">
		<?php echo createHiddenFieldWithKey('editfolder'); ?>
		<input type="hidden" name="folderid" value="<?php print $folder->getID();?>">
		<input type="hidden" name="showtree" value="<?php echo showtree();?>">
<?php
		$this->contentContainerStart();
		$this->formField(
			getMLText("name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'name'=>'name',
				'value'=>htmlspecialchars($folder->getName()),
				'required'=>true
			)
		);
		if(!$nofolderformfields || !in_array('comment', $nofolderformfields)) {
			$this->formField(
				getMLText("comment"),
				array(
					'element'=>'textarea',
					'name'=>'comment',
					'rows'=>4,
					'cols'=>80,
					'value'=>htmlspecialchars($folder->getComment()),
					'required'=>$strictformcheck
				)
			);
		} else {
			$this->formField(
				null,
				array(
					'element'=>'input',
					'type'=>'hidden',
					'name'=>'comment',
					'value'=>htmlspecialchars($folder->getComment()),
				)
			);
		}
		$parent = ($folder->getID() == $rootfolderid) ? false : $folder->getParent();
		if(!$nofolderformfields || !in_array('sequence', $nofolderformfields)) {
			if ($parent && $parent->getAccessMode($user) > M_READ) {
				$this->formField(getMLText("sequence"), $this->getSequenceChooser($parent->getSubFolders('s'), $folder->getID()).($orderby != 's' ? "<br />".getMLText('order_by_sequence_off') : ''));
			}
		}
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$arr = $this->callHook('editFolderAttribute', $folder, $attrdef);
				if(is_array($arr)) {
					if($arr) {
						$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
					}
				} elseif(is_string($arr)) {
					echo $arr;
				} else {
					$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, $folder->getAttribute($attrdef)));
				}
			}
		}
		$arrs = $this->callHook('addFolderAttributes', $folder);
		if(is_array($arrs)) {
			foreach($arrs as $arr) {
				$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
			}
		} elseif(is_string($arrs)) {
			echo $arrs;
		}
		$this->contentContainerEnd();
		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('save'));
?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
