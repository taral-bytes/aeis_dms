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
	echo "  seeddms-importfs [--config <file>] [-h] [-v] -F <folder id> -d <dirname>".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  This program uploads a directory recursively into a folder of SeedDMS.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  --config: set alternative config file.".PHP_EOL;
	echo "  --user: use this user for accessing seeddms.".PHP_EOL;
	echo "  --exclude: exlude files/directories by name (defaults to .svn, .gitignore).".PHP_EOL;
	echo "      This must be just the file or directory without the path.".PHP_EOL;
	echo "  --filemtime: take over modification time from file.".PHP_EOL;
	echo "  --foldermtime: take over modification time from folder.".PHP_EOL;
	echo "  --basefolder: creates the base folder".PHP_EOL;
	echo "  -F <folder id>: id of folder the file is uploaded to".PHP_EOL;
	echo "  -d <dirname>: upload this directory".PHP_EOL;
	echo "  -e <encoding>: encoding used by filesystem (defaults to iso-8859-1)".PHP_EOL;
} /* }}} */

$version = "0.0.1";
$shortoptions = "d:F:e:hv";
$longoptions = array('help', 'version', 'user:', 'basefolder', 'filemtime', 'foldermtime', 'exclude:', 'config:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
	usage();
	exit(0);
}

/* Print help and exit */
if(!$options || isset($options['h']) || isset($options['help'])) {
	usage();
	exit(0);
}

/* Print version and exit */
if(isset($options['v']) || isset($options['verѕion'])) {
	echo $version.PHP_EOL;
	exit(0);
}

/* Set encoding of names in filesystem */
$fsencoding = 'iso-8859-1';
if(isset($options['e'])) {
	$fsencoding = $options['e'];
}

/* Set alternative config file */
if(isset($options['config'])) {
	define('SEEDDMS_CONFIG_FILE', $options['config']);
} elseif(isset($_SERVER['SEEDDMS_CONFIG_FILE'])) {
	define('SEEDDMS_CONFIG_FILE', $_SERVER['SEEDDMS_CONFIG_FILE']);
}

$excludefiles = array('.', '..');
if(isset($options['exclude'])) {
	if(is_array($options['exclude']))
		$excludefiles = array_merge($excludefiles, $options['exclude']);
	else
		$excludefiles[] = $options['exclude'];
} else {
	$excludefiles[] = '.svn';
	$excludefiles[] = '.gitignore';
}

if(isset($options['user'])) {
	$userlogin = $options['user'];
} else {
	echo "Missing user".PHP_EOL;
	usage();
	exit(1);
}

/* check if base folder shall be created */
$createbasefolder = false;
if(isset($options['basefolder'])) {
	$createbasefolder = true;
}

/* check if modification time shall be taken over */
$filemtime = false;
if(isset($options['filemtime'])) {
	$filemtime = true;
}
$foldermtime = false;
if(isset($options['foldermtime'])) {
	$foldermtime = true;
}

if(isset($settings->_extraPath))
	ini_set('include_path', $settings->_extraPath. PATH_SEPARATOR .ini_get('include_path'));

if(isset($options['F'])) {
	$folderid = (int) $options['F'];
} else {
	echo "Missing folder ID".PHP_EOL;
	usage();
	exit(1);
}

$dirname = '';
if(isset($options['d'])) {
	$dirname = $options['d'];
} else {
	echo "Missing import directory".PHP_EOL;
	usage();
	exit(1);
}

include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.Utils.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Language.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");
include($myincpath."/inc/inc.ClassNotificationService.php");
include($myincpath."/inc/inc.ClassEmailNotify.php");
include($myincpath."/inc/inc.ClassController.php");

echo $settings->_contentDir.$settings->_contentOffsetDir.PHP_EOL;

/* Create a global user object */
if(!($user = $dms->getUserByLogin($userlogin))) {
	echo "User with login '".$userlogin."' does not exists.";
	exit;
}

$folder = $dms->getFolder($folderid);
if (!is_object($folder)) {
	echo "Could not find specified folder".PHP_EOL;
	exit(1);
}

if ($folder->getAccessMode($user) < M_READWRITE) {
	echo "Not sufficient access rights".PHP_EOL;
	exit(1);
}

function import_folder($dirname, $folder, $filemtime, $foldermtime) {
	global $user, $excludefiles, $fsencoding;

	$d = dir($dirname);
	$sequence = 1;
	while(false !== ($entry = $d->read())) {
		$path = $dirname.'/'.$entry;
		if(!in_array($entry, $excludefiles)) {
			$name = iconv($fsencoding, 'utf-8', basename($path));
			if(is_file($path)) {
				$filetmp = $path;

				$reviewers = array();
				$approvers = array();
				$comment = '';
				$version_comment = '';
				$reqversion = 1;
				$expires = false;
				$keywords = '';
				$categories = array();

				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mimetype = finfo_file($finfo, $path);
				$lastDotIndex = strrpos($name, ".");
				if (is_bool($lastDotIndex) && !$lastDotIndex) $filetype = ".";
				else $filetype = substr($name, $lastDotIndex);

				echo $mimetype." - ".$filetype." - ".$path.PHP_EOL;
				$res = $folder->addDocument($name, $comment, $expires, $user, $keywords,
																		$categories, $filetmp, $name,
																		$filetype, $mimetype, $sequence, $reviewers,
																		$approvers, $reqversion, $version_comment);

				if (is_bool($res) && !$res) {
					echo "Could not add document to folder".PHP_EOL;
					exit(1);
				}
				if($filemtime) {
					$newdoc = $res[0];
					$newdoc->setDate(filemtime($path));
					$lc = $newdoc->getLatestContent();
					$lc->setDate(filemtime($path));
				}
				set_time_limit(1200);
			} elseif(is_dir($path)) {
				$newfolder = $folder->addSubFolder($name, '', $user, $sequence);
				if($foldermtime) {
					$newfolder->setDate(filemtime($path));
				}
				import_folder($path, $newfolder, $filemtime, $foldermtime);
			}
			$sequence++;
		}
	}
}

if($createbasefolder) {
	if($newfolder = $folder->addSubFolder(basename($dirname), '', $user, 1)) {
		if($foldermtime) {
			$newfolder->setDate(filemtime($dirname));
		}
		import_folder($dirname, $newfolder, $filemtime, $foldermtime);
	}
} else {
	import_folder($dirname, $folder, $filemtime, $foldermtime);
}

