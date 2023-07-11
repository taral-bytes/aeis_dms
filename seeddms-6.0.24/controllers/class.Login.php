<?php
/**
 * Implementation of Login controller
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
 * Class which does the busines logic when logging in
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_Login extends SeedDMS_Controller_Common {
	/**
	 * @var array $user set if user could be logged in
	 * @access protected
	 */
	static protected $user;

	public function getUser() { /* {{{ */
		return self::$user;
	} /* }}} */

	public function run() { /* {{{ */
		$dms = $this->params['dms'];
		$settings = $this->params['settings'];
		$session = $this->params['session'];
		$authenticator = $this->params['authenticator'];
		$source = isset($this->params['source']) ? $this->params['source'] : '';
		$sesstheme = $this->getParam('sesstheme');
		$referuri = $this->getParam('referuri');
		$lang = $this->getParam('lang');
		$login = $this->params['login'];
		$pwd = $this->params['pwd'];

		self::$user = null;

		/* The preLogin hook may set self::$user which will prevent any further
		 * authentication process.
		 */
		if($this->callHook('preLogin')) {
		}

		$user = self::$user;

		/* The password may only be empty if the guest user tries to log in.
		 * There is just one guest account with id $settings->_guestID which
		 * is allowed to log in without a password. All other guest accounts
		 * are treated like regular logins
		 */
		if(!$user && $settings->_enableGuestLogin && (int) $settings->_guestID) {
			$guestUser = $dms->getUser((int) $settings->_guestID);
			if($guestUser) {
				if(($login != $guestUser->getLogin())) {
					if ((!isset($pwd) || strlen($pwd)==0)) {
						$this->setErrorMsg("login_error_text");
						return false;
					}
				} else {
					$user = $guestUser;
				}
			}
		}

		/* Run any additional authentication method. The hook must return a
		 * valid user, if the authentication succeeded. If it fails, it must
		 * return false and if the hook doesn't care at all, if must return null.
		 */
		if(!$user) {
			$user = $this->callHook('authenticate', $source);
			if(false === $user) {
				if(empty($this->errormsg))
					$this->setErrorMsg("authentication_failed");
				return false;
			}
		}

		/* Deprecated: Run any additional authentication implemented in a hook */
		if(!is_object($user) && isset($GLOBALS['SEEDDMS_HOOKS']['authentication'])) {
			foreach($GLOBALS['SEEDDMS_HOOKS']['authentication'] as $authObj) {
				if(!$user && method_exists($authObj, 'authenticate')) {
					$user = $authObj->authenticate($dms, $settings, $login, $pwd);
					if(false === $user) {
						if(empty($this->errormsg))
							$this->setErrorMsg("authentication_failed");
						return false;
					}
				}
			}
		}

		$user = $authenticator->authenticate($login, $pwd);

		if(0) {
		/* Authenticate against LDAP server {{{ */
		if (!is_object($user) && isset($settings->_ldapHost) && strlen($settings->_ldapHost)>0) {
			require_once("../inc/inc.ClassLdapAuthentication.php");
			$authobj = new SeedDMS_LdapAuthentication($dms, $settings);
			$user = $authobj->authenticate($login, $pwd);
			if(!$user) {
				add_log_line('Authentication against LDAP failed for user '.$login);
			}
		} /* }}} */

		/* Authenticate against SeedDMS database {{{ */
		if(!is_object($user)) {
			require_once("../inc/inc.ClassDbAuthentication.php");
			$authobj = new SeedDMS_DbAuthentication($dms, $settings);
			$user = $authobj->authenticate($login, $pwd);
		} /* }}} */
		}

		/* If the user is still not authenticated, then exit with an error */
		if(!is_object($user)) {
			/* if counting of login failures is turned on, then increment its value */
			if($settings->_loginFailure) {
				$user = $dms->getUserByLogin($login);
				if($user) {
					$failures = $user->addLoginFailure();
					if($failures >= $settings->_loginFailure)
						$user->setDisabled(true);
				}
			}
			$this->callHook('loginFailed');
			$this->setErrorMsg("login_error_text");
			return false;
		}

		self::$user = $user;

		/* Check for other restrictions which prevent the user from login, though
		 * the authentication was successfull.
		 * Checking for a guest login the second time, makes only sense if there are
		 * more guest users and the login was done with a password and a user name
		 * unequal to 'guest'.
		 */
		$userid = $user->getID();
		if (($userid == $settings->_guestID) && (!$settings->_enableGuestLogin)) {
			$this->setErrorMsg("guest_login_disabled");
			return false;
		}

		// Check if account is disabled
		if($user->isDisabled()) {
			$this->setErrorMsg("login_disabled_text");
			return false;
		}

		// control admin IP address if required
		if ($user->isAdmin() && ($_SERVER['REMOTE_ADDR'] != $settings->_adminIP ) && ( $settings->_adminIP != "") ){
			$this->setErrorMsg("invalid_user_id");
			return false;
		}

		if($settings->_enable2FactorAuthentication) {
			if($user->getSecret()) {
				$tfa = new \RobThree\Auth\TwoFactorAuth('SeedDMS');
				if($tfa->verifyCode($user->getSecret(), $_POST['twofactauth']) !== true) {
					$this->setErrorMsg("login_error_text");
					return false;
				}
			}
		}

		/* Run any additional checks which may prevent login */
		if(false === $this->callHook('restrictLogin', $user)) {
			if(empty($this->errormsg))
				$this->setErrorMsg("login_restrictions_apply");
			return false;
		}

		/* Clear login failures if login was successful */
		$user->clearLoginFailures();

		/* Setting the theme and language and all the cookie handling is
		 * only done when authentication was requested from a weg page.
		 */
		if($source == 'web') {
			// Capture the user's language and theme settings.
			if ($lang) {
				$user->setLanguage($lang);
			} else {
				$lang = $user->getLanguage();
				if (strlen($lang)==0) {
					$lang = $settings->_language;
					$user->setLanguage($lang);
				}
			}
			if ($sesstheme) {
				$user->setTheme($sesstheme);
			}
			else {
				$sesstheme = $user->getTheme();
				/* Override the theme if the user doesn't have one or the default theme
				 * shall override it.
				 */
				if (strlen($sesstheme)==0 || !empty($settings->_overrideTheme)) {
					$sesstheme = $settings->_theme;
			//		$user->setTheme($sesstheme);
				}
			}

			// Delete all sessions that are more than 1 week or the configured
			// cookie lifetime old. Probably not the most
			// reliable place to put this check -- move to inc.Authentication.php?
			if($settings->_cookieLifetime)
				$lifetime = intval($settings->_cookieLifetime);
			else
				$lifetime = 7*86400;
			if(!$session->deleteByTime($lifetime)) {
				$this->setErrorMsg("error_occured");
				return false;
			}

			if (isset($_COOKIE["mydms_session"])) {
				/* This part will never be reached unless the session cookie is kept,
				 * but op.Logout.php deletes it. Keeping a session could be a good idea
				 * for retaining the clipboard data, but the user id in the session should
				 * be set to 0 which is not possible due to foreign key constraints.
				 * So for now op.Logout.php will delete the cookie as always
				 */
				/* Load session */
				$dms_session = $_COOKIE["mydms_session"];
				if(!$resArr = $session->load($dms_session)) {
					/* Turn off http only cookies if jumploader is enabled */
					setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot, null, false, true); //delete cookie
					header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$referuri);
					exit;
				} else {
					$session->updateAccess($dms_session);
					$session->setUser($userid);
				}
			} else {
				// Create new session in database
				if(!$id = $session->create(array('userid'=>$userid, 'theme'=>$sesstheme, 'lang'=>$lang))) {
					$this->setErrorMsg("error_occured");
					return false;
				}

				// Set the session cookie.
				if($settings->_cookieLifetime)
					$lifetime = time() + intval($settings->_cookieLifetime);
				else
					$lifetime = 0;
				setcookie("mydms_session", $id, $lifetime, $settings->_httpRoot, null, false, true);
			}
		}

		if($this->callHook('postLogin', $user)) {
		}

		return true;
	} /* }}} */
}
