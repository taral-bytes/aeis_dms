<?php
if(isset($_SERVER['SEEDDMS_HOME'])) {
	ini_set('include_path', $_SERVER['SEEDDMS_HOME'].'/utils'. PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = $_SERVER['SEEDDMS_HOME'];
} else {
	ini_set('include_path', dirname($argv[0]). PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = dirname($argv[0]);
}

function usage() { /* {{{ */
	echo "Usage:".PHP_EOL;
	echo "  seeddms-createfolder [--config <file>] [-c <comment>] [-n <name>] [-s <sequence>] [-h] [-v] -F <parent id>".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  This program creates a new folder in SeedDMS.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  --config: set alternative config file.".PHP_EOL;
	echo "  -u <user>: login name of user".PHP_EOL;
	echo "  -F <parent id>: id of parent folder".PHP_EOL;
	echo "  -c <comment>: set comment for file".PHP_EOL;
	echo "  -n <name>: set name of the folder".PHP_EOL;
	echo "  -s <sequence>: set sequence of folder".PHP_EOL;
} /* }}} */

$version = "0.0.1";
$shortoptions = "F:u:c:s:n:hv";
$longoptions = array('help', 'version', 'config:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
	usage();
	exit(0);
}

/* Print help and exit */
if(isset($options['h']) || isset($options['help'])) {
	usage();
	exit(0);
}

/* Print version and exit */
if(isset($options['v']) || isset($options['verѕion'])) {
	echo $version.PHP_EOL;
	exit(0);
}

/* Set alternative config file */
if(isset($options['config'])) {
	define('SEEDDMS_CONFIG_FILE', $options['config']);
} elseif(isset($_SERVER['SEEDDMS_CONFIG_FILE'])) {
	define('SEEDDMS_CONFIG_FILE', $_SERVER['SEEDDMS_CONFIG_FILE']);
}

if(isset($options['F'])) {
	$folderid = (int) $options['F'];
} else {
	echo "Missing parent folder ID".PHP_EOL;
	usage();
	exit(1);
}

$username = '';
if(isset($options['u'])) {
	$username = $options['u'];
}

$comment = '';
if(isset($options['c'])) {
	$comment = $options['c'];
}

$sequence = 0;
if(isset($options['s'])) {
	$sequence = $options['s'];
}

$name = '';
if(isset($options['n'])) {
	$name = $options['n'];
}

include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");
include($myincpath."/inc/inc.ClassNotificationService.php");
include($myincpath."/inc/inc.ClassEmailNotify.php");
include($myincpath."/inc/inc.ClassController.php");

/* Create a global user object {{{ */
if($username) {
	if(!($user = $dms->getUserByLogin($username))) {
		echo "No such user '".$username."'.";
		exit;
	}
} else
	$user = $dms->getUser(1);

$dms->setUser($user);
/* }}} */

/* Create a global notifier object {{{ */
$notifier = new SeedDMS_NotificationService();

if(isset($GLOBALS['SEEDDMS_HOOKS']['notification'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['notification'] as $notificationObj) {
		if(method_exists($notificationObj, 'preAddService')) {
			$notificationObj->preAddService($dms, $notifier);
		}
	}
}

if($settings->_enableEmail) {
	$notifier->addService(new SeedDMS_EmailNotify($dms, $settings->_smtpSendFrom, $settings->_smtpServer, $settings->_smtpPort, $settings->_smtpUser, $settings->_smtpPassword));
}

if(isset($GLOBALS['SEEDDMS_HOOKS']['notification'])) {
	foreach($GLOBALS['SEEDDMS_HOOKS']['notification'] as $notificationObj) {
		if(method_exists($notificationObj, 'postAddService')) {
			$notificationObj->postAddService($dms, $notifier);
		}
	}
}
/* }}} */

$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	echo "Could not find specified folder".PHP_EOL;
	exit(1);
}

if ($folder->getAccessMode($user) < M_READWRITE) {
	echo "Not sufficient access rights".PHP_EOL;
	exit(1);
}

if (!is_numeric($sequence)) {
	echo "Sequence must be numeric".PHP_EOL;
	exit(1);
}

$controller = Controller::factory('AddSubFolder', array('dms'=>$dms, 'user'=>$user));
$controller->setParam('folder', $folder);
$controller->setParam('name', $name);
$controller->setParam('comment', $comment);
$controller->setParam('sequence', $sequence);
$controller->setParam('attributes', array());
$controller->setParam('notificationgroups', array());
$controller->setParam('notificationusers', array());
if(!$subFolder = $controller->run()) {
	echo "Could not add subfolder to folder".PHP_EOL;
} else {
	// Send notification to subscribers.
	if($notifier) {
		$fnl = $folder->getNotifyList();
		$snl = $subFolder->getNotifyList();
		$nl = array(
			'users'=>array_unique(array_merge($snl['users'], $fnl['users']), SORT_REGULAR),
			'groups'=>array_unique(array_merge($snl['groups'], $fnl['groups']), SORT_REGULAR)
		);

		$subject = "new_subfolder_email_subject";
		$message = "new_subfolder_email_body";
		$params = array();
		$params['name'] = $subFolder->getName();
		$params['folder_name'] = $folder->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['comment'] = $comment;
		$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$subFolder->getID();
		$params['sitename'] = $settings->_siteName;
		$params['http_root'] = $settings->_httpRoot;
		$notifier->toList($user, $nl["users"], $subject, $message, $params);
		foreach ($nl["groups"] as $grp) {
			$notifier->toGroup($user, $grp, $subject, $message, $params);
		}
	}
}

