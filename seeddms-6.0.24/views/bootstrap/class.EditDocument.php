<?php
/**
 * Implementation of EditDocument view
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
 * Class which outputs the html page for EditDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_EditDocument extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
		$this->printKeywordChooserJs('form1');
?>
$(document).ready( function() {
	$("#form1").validate({
		messages: {
			name: "<?php printMLText("js_no_name");?>",
			comment: "<?php printMLText("js_no_comment");?>",
			keywords: "<?php printMLText("js_no_keywords");?>"
		}
	});
	$('#presetexpdate').on('change', function(ev){
		if($(this).val() == 'date')
			$('#control_expdate').show();
		else
			$('#control_expdate').hide();
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$attrdefs = $this->params['attrdefs'];
		$strictformcheck = $this->params['strictformcheck'];
		$nodocumentformfields = $this->params['nodocumentformfields'];
		$orderby = $this->params['orderby'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);

		$this->contentHeading(getMLText("edit_document_props"));

		if($document->expires())
			$expdate = getReadableDate($document->getExpires());
		else
			$expdate = '';
?>
<form class="form-horizontal" action="../op/op.EditDocument.php" name="form1" id="form1" method="post">
		<?php echo createHiddenFieldWithKey('editdocument'); ?>
	<input type="hidden" name="documentid" value="<?php echo $document->getID() ?>">
<?php
		$this->contentContainerStart();
		$this->formField(
			getMLText("name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'name'=>'name',
				'value'=>htmlspecialchars($document->getName()),
				'required'=>true
			)
		);
		if(!$nodocumentformfields || !in_array('comment', $nodocumentformfields)) {
			$this->formField(
				getMLText("comment"),
				array(
					'element'=>'textarea',
					'name'=>'comment',
					'rows'=>4,
					'cols'=>80,
					'value'=>htmlspecialchars($document->getComment()),
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
					'value'=>htmlspecialchars($document->getComment()),
				)
			);
		}
		if(!$nodocumentformfields || !in_array('keywords', $nodocumentformfields)) {
			$this->formField(
				getMLText("keywords"),
				$this->getKeywordChooserHtml('form1', $document->getKeywords())
			);
		} else {
			$this->formField(
				null,
				array(
					'element'=>'input',
					'type'=>'hidden',
					'name'=>'keywords',
					'value'=>htmlspecialchars($document->getKeywords()),
				)
			);
		}
		$categories = $dms->getDocumentCategories();
		if($categories) {
			if(!$nodocumentformfields || !in_array('categories', $nodocumentformfields)) {
				$options = array();
				foreach($categories as $category) {
					$options[] = array($category->getID(), $category->getName(), in_array($category, $document->getCategories()));
				}
				$this->formField(
					getMLText("categories"),
					array(
						'element'=>'select',
						'class'=>'chzn-select',
						'name'=>'categories[]',
						'multiple'=>true,
						'attributes'=>array(array('data-placeholder', getMLText('select_category'), array('data-no_results_text', getMLText('unknown_document_category')))),
						'options'=>$options
					)
				);
			} else {
				$categories = $document->getCategories();
				foreach($categories as $category) {
					$this->formField(
						null,
						array(
							'element'=>'input',
							'type'=>'hidden',
							'name'=>'categories[]',
							'value'=>htmlspecialchars($category->getId()),
						)
					);
				}
			}
		}
		if(!$nodocumentformfields || !in_array('expires', $nodocumentformfields)) {
		$options = array();
		$options[] = array('never', getMLText('does_not_expire'));
		$options[] = array('date', getMLText('expire_by_date'), $expdate != '');
		$options[] = array('1w', getMLText('expire_in_1w'));
		$options[] = array('1m', getMLText('expire_in_1m'));
		$options[] = array('1y', getMLText('expire_in_1y'));
		$options[] = array('2y', getMLText('expire_in_2y'));
		$this->formField(
			getMLText("preset_expires"),
			array(
				'element'=>'select',
				'id'=>'presetexpdate',
				'name'=>'presetexpdate',
				'options'=>$options
			)
		);
		$this->formField(
			getMLText("expires"),
			$this->getDateChooser($expdate, "expdate", $this->params['session']->getLanguage())
		);
		} else {
			$this->formField(
				null,
				array(
					'element'=>'input',
					'type'=>'hidden',
					'name'=>'expdate',
					'value'=>$expdate,
				)
			);
		}
		if(!$nodocumentformfields || !in_array('sequence', $nodocumentformfields)) {
		if ($folder->getAccessMode($user) > M_READ) {
			$this->formField(getMLText("sequence"), $this->getSequenceChooser($folder->getDocuments('s'), $document->getID()).($orderby != 's' ? "<br />".getMLText('order_by_sequence_off') : ''));
		}
		}
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$arr = $this->callHook('editDocumentAttribute', $document, $attrdef);
				if(is_array($arr)) {
					if($arr) {
						$this->formField($arr[0], $arr[1], isset($arr[2]) ? $arr[2] : null);
					}
				} elseif(is_string($arr)) {
					echo $arr;
				} else {
					$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, $document->getAttribute($attrdef)));
				}
			}
		}
		$arrs = $this->callHook('addDocumentAttributes', $document);
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
