<?php
if(isset($_SERVER['SEEDDMS_HOME'])) {
	ini_set('include_path', $_SERVER['SEEDDMS_HOME'].'/utils'. PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = $_SERVER['SEEDDMS_HOME'];
} else {
	ini_set('include_path', dirname(realpath($argv[0])). PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = dirname(realpath($argv[0]));
}

function usage() { /* {{{ */
	echo "Usage:".PHP_EOL;
	echo "  seeddms-delete [--config <file>] [-h] [-v] -f <folder id> -e <folder id> -d <document id>".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  This program deletes a folder or document.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  --config: set alternative config file.".PHP_EOL;
	echo "  -f <folder id>: id of folder to be deleted".PHP_EOL;
	echo "  -e <folder id>: id of folder to be emptied".PHP_EOL;
	echo "  -d <document id>: id of document to be deleted".PHP_EOL;
	echo "  -u <user>: login name of user".PHP_EOL;
	echo PHP_EOL;
	echo "If the user is not given the user with id 1 will be used.".PHP_EOL;
	echo "The options -d, -e and -f can be passed multiple times or the option value".PHP_EOL;
	echo "can be a comma separated list of ids.".PHP_EOL;
} /* }}} */

$version = "0.0.1";
$shortoptions = "e:f:d:u:hv";
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

/* Set folders to be deleted */
$folderids = array();
if(isset($options['f'])) {
	if(is_string($options['f']))
		$folderids = explode(',', $options['f']);
	else
		$folderids = $options['f'];
}

/* Set folders to be emptied */
$emptyids = array();
if(isset($options['e'])) {
	if(is_string($options['e']))
		$emptyids = explode(',', $options['e']);
	else
		$emptyids = $options['e'];
}

/* Set documents to be deleted */
$documentids = array();
if(isset($options['d'])) {
	if(is_string($options['d']))
		$documentids = explode(',', $options['d']);
	else
		$documentids = $options['d'];
}

if(!$documentids && !$folderids && !$emptyids) {
	echo "Neither folder ids nor document ids were given".PHP_EOL;
	usage();
	exit(1);
}

$username = '';
if(isset($options['u'])) {
	$username = $options['u'];
}

include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");
include($myincpath."/inc/inc.ClassNotificationService.php");
include($myincpath."/inc/inc.Notification.php");
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

foreach($folderids as $folderid) {
	$folder = $dms->getFolder($folderid);

	if (!is_object($folder)) {
		echo "Could not find folder with id ".$folderid.PHP_EOL;
	} else {

		if ($folder->getAccessMode($user) < M_READWRITE) {
			echo "Not sufficient access rights on folder with id ".$folderid.PHP_EOL;
		} else {
			$controller = Controller::factory('RemoveFolder', array('dms'=>$dms, 'user'=>$user));
			$controller->setParam('folder', $folder);
			$controller->setParam('fulltextservice', $fulltextservice);
			if(!$document = $controller->run()) {
				echo "Could not remove folder with id ".$folderid.PHP_EOL;
			} else {
				echo "Folder with id ".$folderid." removed.".PHP_EOL;
			}
		}
	}
}

foreach($emptyids as $folderid) {
	$folder = $dms->getFolder($folderid);

	if (!is_object($folder)) {
		echo "Could not find folder with id ".$folderid.PHP_EOL;
	}

	if ($folder->getAccessMode($user) < M_READWRITE) {
		echo "Not sufficient access rights on folder with id ".$folderid.PHP_EOL;
	}

	$controller = Controller::factory('EmptyFolder', array('dms'=>$dms, 'user'=>$user));
	$controller->setParam('folder', $folder);
	$controller->setParam('fulltextservice', $fulltextservice);
	if(!$document = $controller->run()) {
		echo "Could not empty folder with id ".$folderid.PHP_EOL;
	} else {
		echo "Folder with id ".$folderid." emptied.".PHP_EOL;
	}
}

foreach($documentids as $documentid) {
	$document = $dms->getDocument($documentid);

	if (!is_object($document)) {
		echo "Could not find specified document with id ".$documentid.PHP_EOL;
	}

	if ($document->getAccessMode($user) < M_READWRITE) {
		echo "Not sufficient access rights on document with id ".$documentid.PHP_EOL;
	}

	$controller = Controller::factory('RemoveDocument', array('dms'=>$dms, 'user'=>$user));
	$controller->setParam('document', $document);
	$controller->setParam('fulltextservice', $fulltextservice);
	if(!$document = $controller->run()) {
		echo "Could not remove document with id ".$documentid.PHP_EOL;
	} else {
		echo "Document with id ".$documentid." removed.".PHP_EOL;
	}
}

