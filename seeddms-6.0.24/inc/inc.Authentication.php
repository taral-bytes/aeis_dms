<?php
/**
 * Do authentication of users and session management
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once("inc.ClassSession.php");
require_once("inc.ClassAccessOperation.php");

$refer = $_SERVER["REQUEST_URI"];
if (!strncmp("/op", $refer, 3)) {
	$refer="";
} else {
	$refer = urlencode($refer);
}

/* Check if this is a ajax call. In that case do not redirect to any page */
$isajax = isset($_GET['action']) && ($_GET['action'] != 'show');

if (!isset($_COOKIE["mydms_session"])) {
	if($settings->_enableGuestLogin && $settings->_enableGuestAutoLogin) {
		$session = new SeedDMS_Session($db);
		if(!$dms_session = $session->create(array('userid'=>$settings->_guestID, 'theme'=>$settings->_theme, 'lang'=>$settings->_language))) {
			if(!$isajax)
				header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
			exit;
		}
		$resArr = $session->load($dms_session);
	}	elseif($settings->_autoLoginUser) {
		if(!($user = $dms->getUser($settings->_autoLoginUser))/* || !$user->isGuest()*/) {
			if(!$isajax)
				header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
			exit;
		}
		$theme = $user->getTheme();
		if (strlen($theme)==0 || !empty($settings->_overrideTheme)) {
			$theme = $settings->_theme;
//			$user->setTheme($theme);
		}
		$lang = $user->getLanguage();
		if (strlen($lang)==0) {
			$lang = $settings->_language;
			$user->setLanguage($lang);
		}
		$session = new SeedDMS_Session($db);
		if(!$dms_session = $session->create(array('userid'=>$user->getID(), 'theme'=>$theme, 'lang'=>$lang))) {
			if(!$isajax)
				header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
			exit;
		}
		$resArr = $session->load($dms_session);
	} else {
		if(!$isajax)
			header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
		exit;
	}
} else {
	/* Load session */
	$dms_session = $_COOKIE["mydms_session"];
	$session = new SeedDMS_Session($db);
	if(!$resArr = $session->load($dms_session)) {
		setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot); //delete cookie
		if(!$isajax)
			header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
		exit;
	}
}

/* Update last access time */
if((int)$resArr['lastAccess']+60 < time())
	$session->updateAccess($dms_session);

/* Load user data */
$user = $dms->getUser($resArr["userID"]);
if (!is_object($user)) {
	setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot); //delete cookie
	if(!$isajax)
		header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
	exit;
}

$origuser = null;
if($resArr["su"] && $su = $dms->getUser($resArr["su"])) {
	if($user->isAdmin() || $user->maySwitchToUser($su)) {
		$origuser = $user;
		$user = $su;
	} else {
	//	$session->resetSu();
	}
}
$theme = $resArr["theme"];
$lang = $resArr["language"];

$dms->setUser($user);
if($settings->_useHomeAsRootFolder && !$user->isAdmin() && $user->getHomeFolder()) {
	$dms->checkWithinRootDir = true;
	$dms->setRootFolderID($user->getHomeFolder());
}
$role = $user->getRole();
$dms->noReadForStatus = $role->getNoAccess();

/* Include additional language file for view
 * This file must set $LANG[xx][]
 */
if(file_exists($settings->_rootDir . "view/".$theme."/languages/" . $lang . "/lang.inc")) {
	include $settings->_rootDir . "view/".$theme."/languages/" . $lang . "/lang.inc";
}

/* if this is a ajax call, then exit early as the rest of the script is irrelevant */
if($isajax)
	return;

/* Check if password needs to be changed because it expired. If it needs
 * to be changed redirect to out/out.ForcePasswordChange.php. Do this
 * check only if password expiration is turned on, we are not on the
 * page to change the password or the page that changes the password, the
 * current user is not admin, and no user substitution has occured. */

if (!$user->isAdmin() && $origuser == null) {
	if($settings->_passwordExpiration > 0) {
		if(basename($_SERVER['SCRIPT_NAME']) != 'out.ForcePasswordChange.php' && basename($_SERVER['SCRIPT_NAME']) != 'op.EditUserData.php' && basename($_SERVER['SCRIPT_NAME']) != 'op.Logout.php') {
			$pwdexp = $user->getPwdExpiration();
			if($pwdexp && substr($pwdexp, 0, 10) != '0000-00-00') {
				$pwdexpts = strtotime($pwdexp); // + $pwdexp*86400;
				if($pwdexpts > 0 && $pwdexpts < time()) {
					header("Location: ../out/out.ForcePasswordChange.php");
					exit;
				}
			}
		}
	}
}

/* Check if secret is set for 2-factor authentication. Redirect to Setup2Factor.php
 * if secret is not set and 2-factor authentication is turned on. Also check if
 * already on the page Setup2Factor.php and no user substiation has occured.
 */
if($settings->_enable2FactorAuthentication && $settings->_guestID != $user->getID() && $settings->_autoLoginUser != $user->getID() && $origuser == null && $user->getSecret() == '') {
	if(basename($_SERVER['SCRIPT_NAME']) != 'out.Setup2Factor.php' && basename($_SERVER['SCRIPT_NAME']) != 'op.Setup2Factor.php') {
		header("Location: ../out/out.Setup2Factor.php");
		exit;
	}
}

/* Update cookie lifetime */
if($settings->_cookieLifetime) {
	$lifetime = time() + intval($settings->_cookieLifetime);
	setcookie("mydms_session", $dms_session, $lifetime, $settings->_httpRoot, null, false, true);
}
