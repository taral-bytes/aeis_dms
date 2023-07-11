<?php
/**
 * Implementation of EditUserData view
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
 * Class which outputs the html page for EditUserData view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_EditUserData extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
$(document).ready( function() {
	$("#form").validate({
		rules: {
			currentpwd: {
				required: true
			},
			fullname: {
				required: true
			},
			email: {
				required: true,
				email: true
			},
			pwdconf: {
				equalTo: "#pwd"
			}
		},
		messages: {
			currentpwd: "<?php printMLText("js_no_currentpwd");?>",
			fullname: "<?php printMLText("js_no_name");?>",
			email: {
				required: "<?php printMLText("js_no_email");?>",
				email: "<?php printMLText("js_invalid_email");?>"
			},
			pwdconf: "<?php printMLText("js_unequal_passwords");?>",
		},
	});
});
<?php

		$this->printFileChooserJs();
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$enableuserimage = $this->params['enableuserimage'];
		$enablelanguageselector = $this->params['enablelanguageselector'];
		$enablethemeselector = $this->params['enablethemeselector'];
		$passwordstrength = $this->params['passwordstrength'];
		$httproot = $this->params['httproot'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("edit_user_details"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_account"), "my_account");

		$this->contentHeading(getMLText("edit_user_details"));
?>
<form class="form-horizontal" action="../op/op.EditUserData.php" enctype="multipart/form-data" method="post" id="form">
	<?php echo createHiddenFieldWithKey('edituserdata'); ?>
<?php
		$this->contentContainerStart();
		$this->formField(
			getMLText("current_password"),
			array(
				'element'=>'input',
				'type'=>'password',
				'id'=>'currentpwd',
				'name'=>'currentpwd',
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		$this->formField(
			getMLText("new_password"),
			'<input class="form-control pwd" type="password" rel="strengthbar" id="pwd" name="pwd" size="30">'
		);
		if($passwordstrength) {
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
				'id'=>'pwdconf',
				'name'=>'pwdconf',
				'autocomplete'=>'off',
			)
		);
		$this->formField(
			getMLText("name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'fullname',
				'name'=>'fullname',
				'value'=>htmlspecialchars($user->getFullName()),
			)
		);
		$this->formField(
			getMLText("email"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'email',
				'name'=>'email',
				'value'=>htmlspecialchars($user->getEmail()),
			)
		);
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'name'=>'comment',
				'rows'=>4,
				'value'=>htmlspecialchars($user->getComment()),
			)
		);

		if ($enableuserimage){	
			$this->formField(
				getMLText("user_image"),
				($user->hasImage() ? "<img src=\"".$httproot . "out/out.UserImage.php?userid=".$user->getId()."\">" : getMLText("no_user_image"))
			);
			$this->formField(
				getMLText("new_user_image"),
				$this->getFileChooserHtml('userfile', false, "image/jpeg")
			);
		}
		if ($enablelanguageselector){	
			$options = array();
			$languages = getLanguages();
			foreach ($languages as $currLang) {
				$options[] = array($currLang, getMLText($currLang), ($user->getLanguage()==$currLang));
			}
			$this->formField(
				getMLText("language"),
				array(
					'element'=>'select',
					'name'=>'language',
					'options'=>$options
				)
			);
		}
		if ($enablethemeselector){	
			$options = array();
			$themes = UI::getStyles();
			foreach ($themes as $currTheme) {
				$options[] = array($currTheme, $currTheme,($user->getTheme()==$currTheme));
			}
			$this->formField(
				getMLText("theme"),
				array(
					'element'=>'select',
					'name'=>'theme',
					'options'=>$options
				)
			);
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
