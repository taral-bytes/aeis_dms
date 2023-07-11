<?php
/**
 * Implementation of UserListCsv controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for export a list of all users as csv
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_UserListCsv extends SeedDMS_Controller_Common {

	public function run() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$group = $this->params['group'];

		if($group) {
			$allUsers = $group->getUsers();
		} else {
			$allUsers = $dms->getAllUsers($settings->_sortUsersInList);
		}
		$m = 0;
		foreach($allUsers as $u) {
			$m = max($m, count($u->getGroups()));
		}
		$fp = fopen("php://temp/maxmemory", 'r+');
		$header = array('login', 'passenc', 'name', 'email', 'comment', 'role', 'quota', 'homefolder', 'hidden', 'disabled');
		for($i=1; $i<=$m; $i++)
			$header[] = 'group_'.$i;
		fputcsv($fp, $header, ';');
		foreach($allUsers as $u) {
			$data = array($u->getLogin(), $u->getPwd(), $u->getFullName(), $u->getEmail(), $u->getComment(), $u->isAdmin() ? 'admin' : ($u->isGuest() ? 'guest' : 'user'), $u->getQuota(), $u->getHomeFolder() ? $u->getHomeFolder() : '', $u->isHidden() ? '1' : 0, $u->isDisabled() ? '1' : '0');
			foreach($u->getGroups() as $g)
				$data[] = $g->getName();
			fputcsv($fp, $data, ';');
		}
		$efilename = 'userlist-'.date('Ymd-His').'.csv';
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=\"" . $efilename . "\"; filename*=UTF-8''".$efilename);
//		header("Content-Length: " . filesize($name));
		fseek($fp, 0);
		fpassthru($fp);
		fclose($fp);
		return true;
	} /* }}} */

}
