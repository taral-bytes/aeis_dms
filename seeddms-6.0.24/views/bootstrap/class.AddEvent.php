<?php
/**
 * Implementation of AddEvent view
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
 * Class which outputs the html page for AddEvent view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_AddEvent extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
$(document).ready(function() {
	$("#form1").validate({
		rules: {
			from: {
				required: true
			},
			to: {
				required: true
			}
		},
		messages: {
			name: "<?php printMLText("js_no_name");?>",
		},
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$day = $this->params['day'];
		$year = $this->params['year'];
		$month = $this->params['month'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("calendar"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation("", "calendar");

		$this->contentHeading(getMLText("add_event"));

		if($day && $year && $month)
			$expdate = sprintf('%04d-%02d-%02d', $year, $month, $day);
		else
			$expdate = getReadableDate();
?>

<form class="form-horizontal" action="../op/op.AddEvent.php" id="form1" name="form1" method="post">
		<?php echo createHiddenFieldWithKey('addevent'); ?>

<?php
		$this->contentContainerStart();
		$this->formField(
			getMLText("from"),
			$this->getDateChooser($expdate, "from", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("to"),
			$this->getDateChooser($expdate, "to", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
				'required'=>true
			)
		);
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'name'=>'comment',
				'rows'=>4,
				'cols'=>80
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit(getMLText('add_event'));
?>

</form>

<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
