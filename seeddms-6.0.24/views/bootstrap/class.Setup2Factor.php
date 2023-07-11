<?php
/**
 * Implementation of Setup2Factor view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Include classes for 2-factor authentication
 */
require "vendor/autoload.php";

/**
 * Class which outputs the html page for ForcePasswordChange view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Setup2Factor extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
function checkForm()
{
	msg = new Array();

	if($("#currentpwd").val() == "") msg.push("<?php printMLText("js_no_pwd");?>");
	if($("#pwd").val() == "") msg.push("<?php printMLText("js_no_pwd");?>");
	if($("#pwd").val() != $("#pwdconf").val()) msg.push("<?php printMLText("js_pwd_not_conf");?>");
	if (msg != "") {
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}

$(document).ready( function() {
	$('body').on('submit', '#form', function(ev){
		if(checkForm()) return;
		ev.preventDefault();
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$sitename = $this->params['sitename'];

		$this->htmlStartPage(getMLText("2_factor_auth"), "forcepasswordchange");
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_account"), "my_account");
		$this->contentHeading(getMLText('2_factor_auth'));
		$this->infoMsg(getMLText('2_factor_auth_info'));
		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText('2_fact_auth_new_secret'));

		$tfa = new \RobThree\Auth\TwoFactorAuth('SeedDMS');
		$oldsecret = $user->getSecret();
		$secret = $tfa->createSecret();
?>
<form class="form-horizontal" action="../op/op.Setup2Factor.php" method="post" id="form" name="form1">
<?php
		$this->formField(
			getMLText('2_fact_auth_secret'),
			array(
				'element'=>'input',
				'type'=>'text',
				'name'=>'secret',
				'class'=>'secret',
				'value'=>htmlspecialchars($secret),
				'required'=>true
			)
		);
		$this->formSubmit(getMLText('submit_2_fact_auth'));
?>
		<div class="control-group"><label class="control-label"></label><div class="controls">
		<img src="<?php echo $tfa->getQRCodeImageAsDataUri($sitename, $secret); ?>">
		</div></div>
</form>
<?php
		if($oldsecret) {
			$this->columnEnd();
			$this->columnStart(6);
			$this->contentHeading(getMLText('2_fact_auth_current_secret'));
			echo '<div>'.$oldsecret.'</div>';
			echo '<div><img src="'.$tfa->getQRCodeImageAsDataUri($sitename, $oldsecret).'"></div>';
?>
<?php
		}

		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
