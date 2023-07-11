<?php
/**
 * Implementation of ChangePassword view
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
 * Class which outputs the html page for ChangePassword view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ChangePassword extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
$(document).ready(function() {
	$('#newpassword').focus();
	$("#form1").validate({
		rules: {
			newpasswordrepeat: {
				equalTo: "#newpassword"
			}
		},
		messages: {
			newpassword: "<?php printMLText("js_no_pwd");?>",
			newpasswordrepeat: "<?php printMLText("js_pwd_not_conf");?>",
		},
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$referuri = $this->params['referuri'];
		$hash = $this->params['hash'];
		$passwordstrength = $this->params['passwordstrength'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("change_password"), "login");
		$this->globalBanner();
		$this->contentStart();
		$this->pageNavigation(getMLText("change_password"));
?>
<form class="form-horizontal" action="../op/op.ChangePassword.php" method="post" id="form1" name="form1">
	<?php echo createHiddenFieldWithKey('changepassword'); ?>
<?php
		if ($referuri) {
			echo "<input type='hidden' name='referuri' value='".$referuri."'/>";
		}
		if ($hash) {
			echo "<input type='hidden' name='hash' value='".$hash."'/>";
		}
		$this->contentContainerStart();
		$this->formField(
			getMLText("password"),
			array(
				'element'=>'input',
				'type'=>'password',
				'id'=>'newpassword',
				'name'=>'newpassword',
				'autocomplete'=>'off',
				'required'=>true,
				'class'=>'pwd',
				'attributes'=>[['rel', 'strengthbar']]
			)
		);
		if($passwordstrength > 0) {
			$this->formField(
				getMLText("password_strength"),
				'<div id="strengthbar" class="progress" style="_width: 220px; height: 30px; margin-bottom: 8px;"><div class="bar bar-danger" style="width: 0%;"></div></div>'
			);
		}
		$this->formField(
			getMLText("confirm_pwd"),
			array(
				'element'=>'input',
				'type'=>'password',
				'id'=>'newpasswordrepeat',
				'name'=>'newpasswordrepeat',
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit(getMLText('submit_password'));
?>
</form>
<p><a href="../out/out.Login.php"><?php echo getMLText("login"); ?></a></p>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
