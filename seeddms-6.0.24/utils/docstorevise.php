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
	echo "  seeddms-docs-to-revise [--config <file>] [-u <user>] [-h] [-v] [-t] [-q] [-o] [-f <email>] [-w] [-b <base>] [-c] -d <days> -D <days>".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  Check for documents which need to be revised in the next days and inform the".PHP_EOL;
	echo "  the revisor.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  --config=<file>: set alternative config file.".PHP_EOL;
	echo "  -u <user>: login name of user".PHP_EOL;
	echo "  -t: run in test mode (will not send any mails)".PHP_EOL;
	echo "  -q: be quite (just output error messages)".PHP_EOL;
} /* }}} */

$version = "0.0.2";
$tableformat = " %-10s %5d %2d %2d %-60s";
$tableformathtml = "<tr><td>%s</td><td>%s</td></tr>";
$baseurl = "http://localhost/";
$mailfrom = "uwe@steinman.cx";

$shortoptions = "u:tqhv";
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
include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Language.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");

$usernames = array();
if(isset($options['u'])) {
	$usernames = explode(',', $options['u']);
}

$dryrun = false;
if(isset($options['t'])) {
	$dryrun = true;
	echo "Running in test mode will not send any mail.".PHP_EOL;
}
$quite = false;
if(isset($options['q'])) {
	$quite = true;
}

$docs = $dms->getDocumentList('DueRevision', null, false, 's');
if($docs === false) {
	echo "Getting documents failed!".PHP_EOL;
	exit(1);
}
$body = '';
if (count($docs)>0) {
	$body .= sprintf($tableformat.PHP_EOL, getMLText("revisiondate", array(), ""), "ID", "V", "S", getMLText("name", array(), ""));	
	$body .= "---------------------------------------------------------------------------------".PHP_EOL;
	foreach($docs as $res)
		$body .= sprintf($tableformat.PHP_EOL, (!$res["revisiondate"] ? "-":substr($res["revisiondate"], 0, 10)), $res["id"], $res['version'], $res['status'], $res["name"]);
} else {
	$body .= getMLText("no_docs_to_look_at", array(), "").PHP_EOL.PHP_EOL;
}
echo $body;

