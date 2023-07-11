<?php
/**
 * Implementation of MyAccount view
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
 * Class which outputs the html page for MyAccount view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_MyAccount extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript');
?>
$(document).ready( function() {
	$('#qrcode').hide();
	$( "#toggleqrcode" ).click(function() {
		$('#qrcode').toggle();
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$enableuserimage = $this->params['enableuserimage'];
		$passwordexpiration = $this->params['passwordexpiration'];
		$enable2factauth = $this->params['enable2factauth'];
		$httproot = $this->params['httproot'];
		$quota = $this->params['quota'];

		$this->htmlStartPage(getMLText("my_account"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_account"), "my_account");

		if($quota > 0) {
			if(($remain = checkQuota($user)) < 0) {
				$this->warningMsg(getMLText('quota_warning', array('bytes'=>SeedDMS_Core_File::format_filesize(abs($remain)))));
			}
		}
		$this->contentHeading(getMLText("user_info"));
		$this->contentContainerStart();


		$this->rowStart();
		if ($enableuserimage){
			$this->columnStart(2);
			print ($user->hasImage() ? "<img class=\"userImage\" src=\"".$httproot . "out/out.UserImage.php?userid=".$user->getId()."\">" : getMLText("no_user_image"))."\n";
			$this->columnEnd();
			$this->columnStart(10);
		} else {
			$this->columnStart(12);
		}

		print "<table class=\"table table-condensed table-sm\">\n";
		print "<tr>\n";
		print "<td>".getMLText("name")." : </td>\n";
		print "<td>".htmlspecialchars($user->getFullName()).($user->isAdmin() ? " (".getMLText("admin").")" : "")."</td>\n";
		print "</tr>\n<tr>\n";
		print "<td>".getMLText("user_login")." : </td>\n";
		print "<td>".$user->getLogin()."</td>\n";
		print "</tr>\n<tr>\n";
		print "<td>".getMLText("email")." : </td>\n";
		print "<td>".htmlspecialchars($user->getEmail())."</td>\n";
		print "</tr>\n<tr>\n";
		print "<td>".getMLText("comment")." : </td>\n";
		print "<td>".htmlspecialchars($user->getComment())."</td>\n";
		print "</tr>\n";
		if($passwordexpiration > 0) {
			print "<tr>\n";
			print "<td>".getMLText("password_expiration")." : </td>\n";
			print "<td>".htmlspecialchars($user->getPwdExpiration())."</td>\n";
			print "</tr>\n";
		}
		print "<tr>\n";
		print "<td>".getMLText("used_discspace")." : </td>\n";
		print "<td>".SeedDMS_Core_File::format_filesize($user->getUsedDiskSpace())."</td>\n";
		print "</tr>\n";
		if($quota > 0) {
			print "<tr>\n";
			print "<td>".getMLText("quota")." : </td>\n";
			print "<td>".SeedDMS_Core_File::format_filesize($user->getQuota())."</td>\n";
			print "</tr>\n";
			if($user->getQuota() > $user->getUsedDiskSpace()) {
				$used = (int) ($user->getUsedDiskSpace()/$user->getQuota()*100.0+0.5);
				$free = 100-$used;
			} else {
				$free = 0;
				$used = 100;
			}
			print "<tr>\n";
			print "<td>\n";
			print "</td>\n";
			print "<td>\n";
?>
		<div class="progress">
			<div class="bar bar-danger" style="width: <?php echo $used; ?>%;"></div>
		  <div class="bar bar-success" style="width: <?php echo $free; ?>%;"></div>
		</div>
<?php
			print "</td>\n";
			print "</tr>\n";
			if($enable2factauth) {
				require "vendor/robthree/twofactorauth/lib/Providers/Qr/IQRCodeProvider.php";
				require "vendor/robthree/twofactorauth/lib/Providers/Qr/BaseHTTPQRCodeProvider.php";
				require "vendor/robthree/twofactorauth/lib/Providers/Qr/GoogleQRCodeProvider.php";
				require "vendor/robthree/twofactorauth/lib/Providers/Rng/IRNGProvider.php";
				require "vendor/robthree/twofactorauth/lib/Providers/Rng/MCryptRNGProvider.php";
				require "vendor/robthree/twofactorauth/lib/TwoFactorAuthException.php";
				require "vendor/robthree/twofactorauth/lib/TwoFactorAuth.php";
				$tfa = new \RobThree\Auth\TwoFactorAuth('SeedDMS');

				print "<tr>\n";
				print "<td>\n";
				echo getMLText('2_factor_auth');
				print "</td>\n";
				$secret = $user->getSecret();
				if(!$secret) {
					print "<td>\n";
					echo '<a class="btn btn-default" href="../out/out.Setup2Factor.php">'.getMLText('setup_2_fact_auth').'</a>';
					print "</td>\n";
				} else {
					print "<td>\n";
					echo '<button class="btn btn-default" id="toggleqrcode">'.getMLText('toggle_qrcode').'</button>';
					echo '<div id="qrcode">';
					echo 'Code is: '.$code = $tfa->getCode($secret)."<br />";
					echo 'Secret: '.$secret."<br />";
					echo '<img src="' . $tfa->getQRCodeImageAsDataUri('My label', $secret) . '">';
					echo "</div>";
					print "</td>\n";
				}
				print "</tr>\n";
			}
		}
		print "</table>\n";
		$this->columnEnd();
		$this->rowEnd();

		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
