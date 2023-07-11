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
	echo "  seeddms-adddoc [--config <file>] [-c <comment>] [-k <keywords>] [-s <number>] [-n <name>] [-V <version>] [-s <sequence>] [-t <mimetype>] [-a <attribute=value>] [-h] [-v] -F <folder id> -D <document id> -f <filename>".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  This program uploads a file into a folder or updates a document of SeedDMS.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  --config: set alternative config file.".PHP_EOL;
	echo "  -F <folder id>: id of folder the file is uploaded to".PHP_EOL;
	echo "  -D <document id>: id of document the file is uploaded to.".PHP_EOL;
	echo "     This will only be used if no folder id is given.".PHP_EOL;
	echo "  -c <comment>: set comment for document. See [1].".PHP_EOL;
	echo "  -C <comment>: set comment for version".PHP_EOL;
	echo "  -k <keywords>: set keywords for file. See [1].".PHP_EOL;
	echo "  -K <categories>: set categories for file. See [1].".PHP_EOL;
	echo "  -s <number>: set sequence for file (used for ordering files within a folder. See [1].".PHP_EOL;
	echo "  -n <name>: set name of file".PHP_EOL;
	echo "  -V <version>: set version of file (defaults to 1). See [2].".PHP_EOL;
	echo "  -u <user>: login name of user".PHP_EOL;
	echo "  -f <filename>: upload this file".PHP_EOL;
	echo "  -s <sequence>: set sequence of file. See [1]".PHP_EOL;
	echo "  -t <mimetype> set mimetype of file manually. Do not do that unless you know".PHP_EOL;
	echo "      what you do. If not set, the mimetype will be determined automatically.".PHP_EOL;
	echo "  -a <attribute=value>: Set a document attribute; can occur multiple times. See [1].".PHP_EOL;
	echo "  -A <attribute=value>: Set a version attribute; can occur multiple times.".PHP_EOL;
	echo PHP_EOL;
	echo "[1] This option applies only if a new document is uploaded. It has no effect".PHP_EOL."    if a new document version is uploaded.".PHP_EOL;
	echo "[2] If a new document version is uploaded it defaults to the next version number.".PHP_EOL;
} /* }}} */

$version = "0.0.1";
$shortoptions = "D:F:c:C:k:K:s:V:u:f:n:t:a:A:hv";
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

/* Set parent folder or document */
$folderid = $documentid = 0;
if(isset($options['F'])) {
	$folderid = (int) $options['F'];
} else {
	if(isset($options['D'])) {
		$documentid = (int) $options['D'];
	} else {
		echo "Missing folder/document ID".PHP_EOL;
		usage();
		exit(1);
	}
}

/* Set comment of document */
$comment = '';
if(isset($options['c'])) {
	$comment = $options['c'];
}

/* Set comment of version */
$version_comment = '';
if(isset($options['C'])) {
	$version_comment = $options['C'];
}

/* Set keywords */
$keywords = '';
if(isset($options['k'])) {
	$keywords = $options['k'];
}

$sequence = 0;
if(isset($options['s'])) {
	$sequence = $options['s'];
}

$name = '';
if(isset($options['n'])) {
	$name = $options['n'];
}

$username = '';
if(isset($options['u'])) {
	$username = $options['u'];
}

$filename = '';
if(isset($options['f'])) {
	$filename = $options['f'];
} else {
	usage();
	exit(1);
}

$mymimetype = '';
if(isset($options['t'])) {
	$mymimetype = $options['t'];
}

$reqversion = 0;
if(isset($options['V'])) {
	$reqversion = $options['V'];
}
if($reqversion<1)
	$reqversion=1;

include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.LogInit.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");
include($myincpath."/inc/inc.ClassController.php");

	/* Parse categories {{{ */
	$categories = array();
	if(isset($options['K'])) {
		$categorynames = explode(',', $options['K']);
		foreach($categorynames as $categoryname) {
			$cat = $dms->getDocumentCategoryByName($categoryname);
			if($cat) {
				$categories[] = $cat;
			} else {
				echo "Category '".$categoryname."' not found".PHP_EOL;
			}
		}
	} /* }}} */

	/* Parse document attributes. {{{ */
	$document_attributes = array();
	if (isset($options['a'])) {
		$docattr = array();
		if (is_array($options['a'])) {
			$docattr = $options['a'];
		} else {
			$docattr = array($options['a']);
		}

		foreach ($docattr as $thisAttribute) {
			$attrKey = strstr($thisAttribute, '=', true);
			$attrVal = substr(strstr($thisAttribute, '='), 1);
			if (empty($attrKey) || empty($attrVal)) {
				echo "Document attribute $thisAttribute not understood".PHP_EOL;
				exit(1);
			}
			$attrdef = $dms->getAttributeDefinitionByName($attrKey);
			if (!$attrdef) {
				echo "Document attribute $attrKey unknown".PHP_EOL;
				exit(1);
			}
			$document_attributes[$attrdef->getID()] = $attrVal;
		}
	} /* }}} */

	/* Parse version attributes. {{{ */
	$version_attributes = array();
	if (isset($options['A'])) {
		$verattr = array();
		if (is_array($options['A'])) {
			$verattr = $options['A'];
		} else {
			$verattr = array($options['A']);
		}

		foreach ($verattr as $thisAttribute) {
			$attrKey = strstr($thisAttribute, '=', true);
			$attrVal = substr(strstr($thisAttribute, '='), 1);
			if (empty($attrKey) || empty($attrVal)) {
				echo "Version attribute $thisAttribute not understood".PHP_EOL;
				exit(1);
			}
			$attrdef = $dms->getAttributeDefinitionByName($attrKey);
			if (!$attrdef) {
				echo "Version attribute $attrKey unknown".PHP_EOL;
				exit(1);
			}
			$version_attributes[$attrdef->getID()] = $attrVal;
		}
	} /* }}} */

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

/* Check if file is readable {{{ */
if(is_readable($filename)) {
	if(filesize($filename)) {
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if(!$mymimetype) {
			$mymimetype = $finfo->file($filename);
		}
		$filetype = "." . pathinfo($filename, PATHINFO_EXTENSION);
	} else {
		echo "File '".$filename."' has zero size".PHP_EOL;
		exit(1);
	}
} else {
	echo "File '".$filename."' is not readable".PHP_EOL;
	exit(1);
}
/* }}} */

$folder = null;
$document = null;
if($folderid) {
	$folder = $dms->getFolder($folderid);

	if (!is_object($folder)) {
		echo "Could not find specified folder".PHP_EOL;
		exit(1);
	}

	if ($folder->getAccessMode($user) < M_READWRITE) {
		echo "Not sufficient access rights".PHP_EOL;
		exit(1);
	}
} elseif($documentid) {
	$document = $dms->getDocument($documentid);

	if (!is_object($document)) {
		echo "Could not find specified document".PHP_EOL;
		exit(1);
	}

	if ($document->getAccessMode($user) < M_READWRITE) {
		echo "Not sufficient access rights".PHP_EOL;
		exit(1);
	}
}

if (!is_numeric($sequence)) {
	echo "Sequence must be numeric".PHP_EOL;
	exit(1);
}

$expires = false;

if(!$name)
	$name = basename($filename);
$filetmp = $filename;

$reviewers = array();
$approvers = array();

if($folder) {
	$controller = Controller::factory('AddDocument', array('dms'=>$dms, 'user'=>$user));
	$controller->setParam('documentsource', 'script');
	$controller->setParam('folder', $folder);
	$controller->setParam('fulltextservice', $fulltextservice);
	$controller->setParam('name', $name);
	$controller->setParam('comment', $comment);
	$controller->setParam('expires', $expires);
	$controller->setParam('keywords', $keywords);
	$controller->setParam('categories', $categories);
	$controller->setParam('owner', $user);
	$controller->setParam('userfiletmp', $filetmp);
	$controller->setParam('userfilename', basename($filename));
	$controller->setParam('filetype', $filetype);
	$controller->setParam('userfiletype', $mymimetype);
	$minmax = $folder->getDocumentsMinMax();
	if($settings->_defaultDocPosition == 'start')
		$controller->setParam('sequence', $minmax['min'] - 1);
	else
		$controller->setParam('sequence', $minmax['max'] + 1);
	$controller->setParam('reviewers', $reviewers);
	$controller->setParam('approvers', $approvers);
	$controller->setParam('reqversion', $reqversion);
	$controller->setParam('versioncomment', $version_comment);
	$controller->setParam('attributes', $document_attributes);
	$controller->setParam('attributesversion', $version_attributes);
	$controller->setParam('workflow', null);
	$controller->setParam('notificationgroups', array());
	$controller->setParam('notificationusers', array());
	$controller->setParam('maxsizeforfulltext', $settings->_maxSizeForFullText);
	$controller->setParam('defaultaccessdocs', $settings->_defaultAccessDocs);

	if(!$document = $controller->run()) {
		echo "Could not add document to folder".PHP_EOL;
		exit(1);
	}
} elseif($document) {
	$controller = Controller::factory('UpdateDocument', array('dms'=>$dms, 'user'=>$user));
	$controller->setParam('folder', $document->getFolder());
	$controller->setParam('document', $document);
	$controller->setParam('index', $index);
	$controller->setParam('indexconf', $indexconf);
	$controller->setParam('comment', $comment);
	$controller->setParam('userfiletmp', $filetmp);
	$controller->setParam('userfilename', $filename);
	$controller->setParam('filetype', $filetype);
	$controller->setParam('userfiletype', $mimetype);
	$controller->setParam('reviewers', $reviewers);
	$controller->setParam('approvers', $approvers);
	$controller->setParam('attributes', $version_attributes);
	$controller->setParam('workflow', null);

	if(!$content = $controller->run()) {
		echo "Could not add version to document".PHP_EOL;
		exit(1);
	}
}
