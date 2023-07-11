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
	echo "  seeddms-docs-to-receipt [--config <file>] [-u <user>] [-h] [-v] [-t] [-q] [-o] [-f <email>] [-w] [-b <base>] [-c] -d <days> -D <days>".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  Check for open receptions in the next days and inform the".PHP_EOL;
	echo "  the recepient.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  --config=<file>: set alternative config file.".PHP_EOL;
	echo "  -u <user>: login name of user".PHP_EOL;
	echo "  -t: run in test mode (will not send any mails)".PHP_EOL;
	echo "  -q: be quite (just output error messages)".PHP_EOL;
} /* }}} */

$version = "0.0.1";
$tableformat = "%-65s";
$tableformathtml = "<tr><td>%s</td></tr>";
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

$days = 14;
if(isset($options['d'])) {
	$days = (int) $options['d'];
}

if(isset($options['f'])) {
	$mailfrom = trim($options['f']);
}

if(isset($options['b'])) {
	$baseurl = trim($options['b']);
}

$username = '';
if(isset($options['u'])) {
	$username = trim($options['u']);
}

$receiver = '';
if(isset($options['r'])) {
	$receiver = trim($options['r']);
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

if($username) {
	$users = array();
	$tmp = explode(',', $username);
	foreach($tmp as $t) {
		if($u = $dms->getUserByLogin($t))
			$users[] = $u;
	}
} else {
	$users = $dms->getAllUsers();
}
if(!$users) {
	echo "No users specified or available.";
	exit;
}

$bodyhead = "";
$bodyhead .= getMLText('documents_to_receipt', array('days'=>$days))."\n";
$bodyhead .= "\n";

$bodyhtmlhead = "<html>\n<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n<title>SeedDMS: ".getMLText('daylyDigestMail')."</title>\n<base href=\"".$baseurl.$settings->_httpRoot."\" />\n</head>\n<body>\n";
$bodyhtmlhead .= "<h2>";
$bodyhtmlhead .= getMLText('documents_to_receipt', array('days'=>$days))."\n";
$bodyhtmlhead .= "</h2>\n";
$bodyhtmlhead .= "<h3>";
$bodyhtmlhead .= "</h3>\n";
$bodyfoot = "\n";
$bodyhtmlfoot = "";

$bodyarr = array();
foreach($users as $user) {
	if($user->isDisabled())
		continue;
	if(!$quite)
		echo "Checking for receptions of user ".$user->getFullName()." which are due for more than ".$days." days\n";

	$sendmail = false; /* Set to true if there is something to report */

	$docs = $dms->getDocumentList('ReceiptByMe', $user);
	if($docs === false) {
		echo "Getting documents failed!".PHP_EOL;
		exit(1);
	} elseif($docs) {
		echo "Found ".count($docs)." documents\n\n";

		/* Build an array of mail bodies for receiver in $bodyarr
		 * $bodyarr['email@domain.de']['plain'] = 'mail body for plain text mail'
		 * $bodyarr['email@domain.de']['html'] = 'mail body for html mail'
		 */
		$email = $user->getEmail();
		$bodyarr[$email] = array('plain'=>'', 'html'=>'');

		$bodyarr[$email]['plain'] .= sprintf($tableformat."\n", getMLText('document'));	
		$bodyarr[$email]['plain'] .= "---------------------------------------------------------------------------------------\n";
		$bodyarr[$email]['html'] .= "<h3>".getMLText('document')."</h3>";	
		$bodyarr[$email]['html'] .= "<table>\n";
		$bodyarr[$email]['html'] .= sprintf($tableformathtml."\n", getMLText('name', array()));	
		foreach($docs as $document) {
			$bodyarr[$email]['plain'] .= sprintf($tableformat."\n", $document['name']);	
			$bodyarr[$email]['html'] .= sprintf($tableformathtml."\n", '<a href="out/out.ViewDocument.php?documentid='.$document['id'].'">'.htmlspecialchars($document['name']).'</a>');	
		}
		$bodyarr[$email]['plain'] .= "\n";
		$bodyarr[$email]['html'] .= "</table>\n";

	}
}

foreach($bodyarr as $address => $msg) {
	if(!$quite) {
		echo "\n=== Send mail to ".trim($address)." =====================================================\n";
		echo $bodyhead;
		echo $bodyarr[$address]['plain'];
		echo $bodyfoot;
	} else {
	}

	if(!$dryrun) {
		$mime = new Mail_mime(array('eol' => "\n"));

		$mime->setTXTBody($bodyhead.$msg['plain'].$bodyfoot);
		$mime->setHTMLBody($bodyhtmlhead.$msg['html'].$bodyhtmlfoot);

		$body = $mime->get(array(
			'text_encoding'=>'8bit',
			'html_encoding'=>'8bit',
			'head_charset'=>'utf-8',
			'text_charset'=>'utf-8',
			'html_charset'=>'utf-8'
		));
		$hdrs = $mime->headers(array('From' => $mailfrom, 'Subject' => 'SeedDMS: '.getMLText('daylyDigestMail').($receiver ? " (".trim($address).")" : ""), 'Content-Type' => 'text/plain; charset=UTF-8'));

		$mail = Mail::factory('mail');
		if($receiver)
			$mail->send($receiver, $hdrs, $body);
		else
			$mail->send(trim($address), $hdrs, $body);
	}
}

