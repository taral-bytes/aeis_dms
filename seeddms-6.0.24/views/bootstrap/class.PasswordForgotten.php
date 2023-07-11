<?php
/**
 * Implementation of PasswordForgotten view
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
 * Class which outputs the html page for PasswordForgotten view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_PasswordForgotten extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
$(document).ready(function() {
	$("#form1").validate({
		rules: {
			email: {
				email: true
			}
		},
		messages: {
			login: "<?php printMLText("js_no_login");?>",
			email: "<?php printMLText("js_no_email");?>"
		},
	});
});
document.form1.email.focus();
<?php
	} /* }}} */

	function show() { /* {{{ */
		$referrer = $this->params['referrer'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("password_forgotten"), "passwordforgotten");
		$this->globalBanner();
		$this->contentStart();
		$this->pageNavigation(getMLText("password_forgotten"));
?>

<form class="form-horizontal" action="../op/op.PasswordForgotten.php" method="post" id="form1" name="form1">
<?php
		if ($referrer) {
			echo "<input type='hidden' name='referuri' value='".$referrer."'/>";
		}
		$this->infoMsg(getMLText("password_forgotten_text"));
		$this->contentContainerStart();
		$this->formField(
			getMLText("user_login"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'login',
				'name'=>'login',
				'placeholder'=>'login',
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		$this->formField(
			getMLText("email"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'email',
				'name'=>'email',
				'placeholder'=>'email',
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit(getMLText('submit_password_forgotten'));
?>
</form>
<p><a href="../out/out.Login.php"><?php echo getMLText("login"); ?></a></p>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
