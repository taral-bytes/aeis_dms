<?php
/**
 * Implementation of UserList view
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
 * Class which outputs the html page for UserList view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_UserList extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
		$(document).ready(function(){
			$("#myInput").on("keyup", function() {
				var value = $(this).val().toLowerCase();
				$("#myTable tbody tr").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
			});
		});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$allUsers = $this->params['allusers'];
		$httproot = $this->params['httproot'];
		$quota = $this->params['quota'];
		$pwdexpiration = $this->params['pwdexpiration'];
		$accessobject = $this->params['accessobject'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation("", "admin_tools");
		$this->contentHeading(getMLText("user_list"));

		$sessionmgr = new SeedDMS_SessionMgr($dms->getDB());
?>

	<input type="text" id="myInput" class="form-control" placeholder="<?= getMLText('type_to_filter'); ?>">
	<table id="myTable" class="table table-condensed table-sm">
		<thead><tr><th></th><th><?php printMLText('name'); ?></th><th><?php printMLText('groups'); ?></th><th><?php printMLText('role'); ?></th><th><?php printMLText('discspace'); ?></th><th><?php printMLText('authentication'); ?></th><th></th></tr></thead><tbody>
<?php
		foreach ($allUsers as $currUser) {
			echo "<tr".($currUser->isDisabled() ? " class=\"error\"" : "").">";
			echo "<td>";
			if ($currUser->hasImage())
				print "<img width=\"100\" src=\"".$this->html_url('UserImage', array('userid'=>$currUser->getId()))."\" >";
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars($currUser->getFullName())." (".htmlspecialchars($currUser->getLogin()).")<br />";
			echo "<a href=\"mailto:".htmlspecialchars($currUser->getEmail())."\">".htmlspecialchars($currUser->getEmail())."</a><br />";
			echo "<small>".htmlspecialchars($currUser->getComment())."</small>";
			echo "</td>";
			echo "<td>";
			$groups = $currUser->getGroups();
			if (count($groups) != 0) {
				for ($j = 0; $j < count($groups); $j++)	{
					print htmlspecialchars($groups[$j]->getName());
					if ($j +1 < count($groups))
						print ", ";
				}
			}
			echo "</td>";
			echo "<td>";
			echo htmlspecialchars($currUser->getRole()->getName());
			echo "</td>";
			echo "<td>";
			echo SeedDMS_Core_File::format_filesize($currUser->getUsedDiskSpace());
			if($quota) {
				echo " / ";
				$qt = $currUser->getQuota() ? $currUser->getQuota() : $quota;
				echo SeedDMS_Core_File::format_filesize($qt)."<br />";
				echo $this->getProgressBar($currUser->getUsedDiskSpace(), $qt);
			}
			echo "</td>";
			echo "<td>";
			if($pwdexpiration) {
				$now = new DateTime();
				$expdate = new DateTime($currUser->getPwdExpiration());
				$diff = $now->diff($expdate);
				if($expdate > $now) {
					printf(getMLText('password_expires_in_days'), $diff->format('%a'));
					echo " (".$expdate->format('Y-m-d H:i:sP').")";
				} else {
					printMLText("password_expired");
				}
			}
			$sessions = $sessionmgr->getUserSessions($currUser);
			if($sessions) {
				foreach($sessions as $session) {
					echo "<br />".getMLText('lastaccess').": ".getLongReadableDate($session->getLastAccess());
				}
			}
			echo "</td>";
			echo "<td>";
			if($accessobject->check_view_access(array('UsrMgr', 'RemoveUser'))) {
				echo "<div class=\"list-action\">";
				echo $this->html_link('UsrMgr', array('userid'=>$currUser->getID()), array(), '<i class="fa fa-edit"></i>', false);
				echo $this->html_link('RemoveUser', array('userid'=>$currUser->getID()), array(), '<i class="fa fa-remove"></i>', false);
				echo "</div>";
			}
			echo "</td>";
			echo "</tr>";
		}
		echo "</tbody></table>";

		echo '<a class="btn btn-primary" href="../op/op.UserListCsv.php">'.getMLText('export_user_list_csv').'</a>';
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
