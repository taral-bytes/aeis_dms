<?php
/**
 * Implementation of Clipboard view
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
 * Class which outputs the html page for clipboard view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Session extends SeedDMS_Theme_Style {
	/**
	 * Returns the html needed for the clipboard list in the menu
	 *
	 * This function renders the clipboard in a way suitable to be
	 * used as a menu
	 *
	 * @param array $clipboard clipboard containing two arrays for both
	 *        documents and folders.
	 * @return string html code
	 */
	public function menuSessions() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		$sessionmgr = new SeedDMS_SessionMgr($dms->getDB());
		/* Get only sessions which has been active in the last 3600 sec. */
		$sessions = $sessionmgr->getLastAccessedSessions(date('Y-m-d H:i:s', time()-3600));
		if(!$sessions)
			return;

		if ($user->isGuest() || count($sessions) == 0) {
			return;
		}

		$c = 0;
		$menuitems['session'] = array('label'=>'', 'children'=>array());
		foreach($sessions as $session) {
			if($sesuser = $dms->getUser($session->getUser()))
				if(!$sesuser->isHidden()) {
					$c++;
					$menuitems['session']['children'][] = array('label'=>'<i class="fa fa-user"></i> '.htmlspecialchars($sesuser->getFullName()).($user->isAdmin() ? " (".getReadableDuration(time()-$session->getLastAccess()).")" : ""));
				}
		}
		if($c) {
			$menuitems['session']['label'] = getMLText('sessions')." (".$c.")";
			self::showNavigationBar($menuitems, array('right'=>true));
		}
	} /* }}} */

}
