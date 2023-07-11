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
	echo "  seeddms-expireddocs [--config <file>] [-u <user>] [-h] [-v] [-t] [-q] [-o] [-f <email>] [-u <user>] [-w] [-b <base>] [-c] -d <days> -D <days>".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  Check for files which will expire in the next days and inform the".PHP_EOL;
	echo "  the owner and all users watching the document.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  --config=<file>: set alternative config file.".PHP_EOL;
	echo "  -u <user>: login name of user".PHP_EOL;
	echo "  -w: send mail also to all users watching the document".PHP_EOL;
	echo "  -c: list also categories for each document".PHP_EOL;
	echo "  -f <email>: set From field in notification mail".PHP_EOL;
	echo "  -b <base>: set base for links in html email. The final link will be".PHP_EOL;
	echo "             <base><httpRoot>out/out.ViewDocument.php. The default is".PHP_EOL;
	echo "             http://localhost".PHP_EOL;
	echo "  -d <days>: check till n days in the future (default 14). Days always".PHP_EOL.
	     "             start at 00:00:00 and end at 23:59:59. A value of '1' means today.".PHP_EOL;
	     "             '-d 2' will search for documents expiring today or tomorrow.".PHP_EOL;
	echo "  -D <days>: start checking in n days in the future (default 0). This value".PHP_EOL.
	     "             must be less then -d. A value of 0 means to start checking today.".PHP_EOL.
	     "             Any positive number will start checking in n days.".PHP_EOL.
	     "             A negative number will look backwards in time.".PHP_EOL.
	     "             '-d 10 -D 5' will search for documents expiring in 5 to 10 days.".PHP_EOL.
	     "             '-d 10 -D -5' will search for documents which have expired in the last 5 days".PHP_EOL.
	     "             or will expire in the next 10 days.".PHP_EOL;
	echo "  -o: list obsolete documents (default: do not list)".PHP_EOL;
	echo "  -t: run in test mode (will not send any mails)".PHP_EOL;
	echo "  -q: be quite (just output error messages)".PHP_EOL;
} /* }}} */

$version = "0.0.2";
$tableformat = "%-60s %-14s";
$tableformathtml = "<tr><td>%s</td><td>%s</td></tr>";
$baseurl = "http://localhost/";
$mailfrom = "uwe@steinman.cx";

$shortoptions = "u:d:D:f:b:wtqhvo";
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
include($myincpath."/inc/inc.Utils.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Language.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");

$LANG['de_DE']['daylyDigestMail'] = 'Tägliche Benachrichtigungsmail';
$LANG['en_GB']['daylyDigestMail'] = 'Dayly digest mail';
$LANG['de_DE']['docsExpiringInNDays'] = 'Dokumente, die in den nächsten [days] Tagen ablaufen';
$LANG['en_GB']['docsExpiringInNDays'] = 'Documents expiring in the next [days] days';
$LANG['de_DE']['docsExpiringBetween'] = 'Dokumente, die zwischen dem [start] und [end] ablaufen';
$LANG['en_GB']['docsExpiringBetween'] = 'Documents which expire between [start] and [end]';

require_once('Mail.php');
require_once('Mail/mime.php');

$usernames = array();
if(isset($options['u'])) {
	$usernames = explode(',', $options['u']);
}

$informwatcher = false;
if(isset($options['w'])) {
	$informwatcher = true;
}

$showcats = false;
if(isset($options['c'])) {
	$showcats = true;
	$tableformathtml = "<tr><td>%s</td><td>%s</td><td>%s</td></tr>";
}

$days = 14;
if(isset($options['d'])) {
	$days = (int) $options['d'];
}
$enddays = 0;
if(isset($options['D'])) {
	$enddays = (int) $options['D'];
}

if($enddays >= $days) {
	echo "Value of -D must be less then value of -d".PHP_EOL;
	exit(1);
}

if(isset($options['f'])) {
	$mailfrom = trim($options['f']);
}

if(isset($options['b'])) {
	$baseurl = trim($options['b']);
}

$showobsolete = false;
if(isset($options['o'])) {
	$showobsolete = true;
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

$startts = strtotime("midnight", time());
if(!$quite)
	echo "Checking for documents expiring between ".getLongReadableDate($startts+$enddays*86400)." and ".getLongReadableDate($startts+$days*86400-1).PHP_EOL;

$users = array();
if(!$usernames) {
	$users = $dms->getAllUsers();
} else {
	/* Create a global user object */
	foreach($usernames as $username) {
		if(!$user = $dms->getUserByLogin($username)) {
			echo "No such user with name '".$username."'".PHP_EOL;
			exit(1);
		}
		$users[] = $user;
	}
}

if (!$db->createTemporaryTable("ttstatid") || !$db->createTemporaryTable("ttcontentid")) {
	echo getMLText("internal_error_exit").PHP_EOL;
	exit;
}

foreach($users as $user) {
	$groups = $user->getGroups();
	$groupids = array();
	foreach($groups as $group)
		$groupids[] = $group->getID();
	$sendmail = false; /* Set to true if there is something to report */
	$body = "";
	$bodyhtml = "<html>".PHP_EOL."<head>".PHP_EOL."<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />".PHP_EOL."<title>SeedDMS: ".getMLText('daylyDigestMail', array(), "", $user->getLanguage())."</title>".PHP_EOL."<base href=\"".$baseurl.$settings->_httpRoot."\" />".PHP_EOL."</head>".PHP_EOL."<body>".PHP_EOL."";

	/*
	$queryStr = "SELECT `tblDocuments`.* FROM `tblDocuments`".
		"WHERE `tblDocuments`.`owner` = '".$user->getID()."' ".
		"AND `tblDocuments`.`expires` < '".($startts + $days*86400)."' ".
		"AND `tblDocuments`.`expires` > '".($startts)."'";
	 */

	$queryStr = "SELECT DISTINCT a.*, `tblDocumentStatusLog`.* FROM `tblDocuments` a ".
		"LEFT JOIN `ttcontentid` ON `ttcontentid`.`document` = `a`.`id` ".
		"LEFT JOIN `tblDocumentContent` ON `a`.`id` = `tblDocumentContent`.`document` AND `tblDocumentContent`.`version` = `ttcontentid`.`maxVersion` ".
		"LEFT JOIN `tblNotify` b ON a.`id`=b.`target` ".
		"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` AND `tblDocumentContent`.`version` = `tblDocumentStatus`.`version` ".
		"LEFT JOIN `ttstatid` ON `ttstatid`.`statusID` = `tblDocumentStatus`.`statusID` ".
		"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusLogID` = `ttstatid`.`maxLogID` ".
		"WHERE (a.`owner` = '".$user->getID()."' ".
		($informwatcher ? " OR ((b.`userID` = '".$user->getID()."' ".
		($groupids ? "or b.`groupID` in (".implode(',', $groupids).")" : "").") ".
		"AND b.`targetType` = 2) " : "").
		") AND a.`expires` < '".($startts + $days*86400)."' ".
		"AND a.`expires` > '".($startts + $enddays*86400)."' ";
	if(!$showobsolete)
		$queryStr .= "AND `tblDocumentStatusLog`.`status` != -2";

	$resArr = $db->getResultArray($queryStr);
	if (is_bool($resArr) && !$resArr) {
		echo getMLText("internal_error_exit").PHP_EOL;
		exit;
	}

	$body .= "==== ";
	$body .= getMLText('docsExpiringBetween', array('start'=>getReadableDate($startts + ($enddays)*86400), 'end'=>getReadableDate($startts + ($days)*86400)), "", $user->getLanguage()).PHP_EOL;
	$body .= "==== ";
	$body .= $user->getFullname();
	$body .= PHP_EOL.PHP_EOL;
	$bodyhtml .= "<h2>";
	$bodyhtml .= getMLText('docsExpiringBetween', array('start'=>getReadableDate($startts + ($enddays)*86400), 'end'=>getReadableDate($startts + ($days)*86400)), "", $user->getLanguage()).PHP_EOL;
	$bodyhtml .= "</h2>".PHP_EOL;
	$bodyhtml .= "<h3>";
	$bodyhtml .= $user->getFullname();
	$bodyhtml .= "</h3>".PHP_EOL;
	if (count($resArr)>0) {
		$sendmail = true;

		$body .= sprintf($tableformat.PHP_EOL, getMLText("name", array(), "", $user->getLanguage()), getMLText("expires", array(), "", $user->getLanguage()));	
		$body .= "---------------------------------------------------------------------------------".PHP_EOL;
		$bodyhtml .= "<table>".PHP_EOL;
		if($showcats)
			$bodyhtml .= sprintf($tableformathtml.PHP_EOL, getMLText("name", array(), "", $user->getLanguage()), getMLText("categories", array(), "", $user->getLanguage()), getMLText("expires", array(), "", $user->getLanguage()));	
		else
			$bodyhtml .= sprintf($tableformathtml.PHP_EOL, getMLText("name", array(), "", $user->getLanguage()), getMLText("expires", array(), "", $user->getLanguage()));	

		foreach ($resArr as $res) {
			if($doc = $dms->getDocument((int) $res['id'])) {
				$catnames = array();
				if($cats = $doc->getCategories()) {
					foreach($cats as $cat)
						$catnames[] = $cat->getName();
				}
			}
		
			$body .= sprintf($tableformat.PHP_EOL, $res["name"], (!$res["expires"] ? "-":getReadableDate($res["expires"])));
			if($showcats)
				$body .= getMLText("categories", array(), "", $user->getLanguage()).": ".implode(', ', $catnames).PHP_EOL;
			if($showcats)
				$bodyhtml .= sprintf($tableformathtml.PHP_EOL, '<a href="out/out.ViewDocument.php?documentid='.$res["id"].'">'.htmlspecialchars($res["name"]).'</a>', implode(', ', $catnames), (!$res["expires"] ? "-":getReadableDate($res["expires"])));	
			else
				$bodyhtml .= sprintf($tableformathtml.PHP_EOL, '<a href="out/out.ViewDocument.php?documentid='.$res["id"].'">'.htmlspecialchars($res["name"]).'</a>', (!$res["expires"] ? "-":getReadableDate($res["expires"])));	
		}		
		$bodyhtml .= "</table>".PHP_EOL;
		
	} else {
		$body .= getMLText("no_docs_to_look_at", array(), "", $user->getLanguage()).PHP_EOL.PHP_EOL;
		$bodyhtml .= "<p>".getMLText("no_docs_to_look_at", array(), "", $user->getLanguage())."</p>".PHP_EOL.PHP_EOL;
	}

	if($sendmail) {
		if($user->getEmail()) {
			if(!$quite) {
				echo "Send mail to ".$user->getLogin()." <".$user->getEmail().">".PHP_EOL;
			echo $body;
			echo "----------------------------".PHP_EOL.PHP_EOL.PHP_EOL;
			echo $bodyhtml;
			}

			if(!$dryrun) {
				$mime = new Mail_mime(array('eol' => PHP_EOL));

				$mime->setTXTBody($body);
				$mime->setHTMLBody($bodyhtml);

				$body = $mime->get(array(
					'text_encoding'=>'8bit',
					'html_encoding'=>'8bit',
					'head_charset'=>'utf-8',
					'text_charset'=>'utf-8',
					'html_charset'=>'utf-8'
				));
				$hdrs = $mime->headers(array('From' => $mailfrom, 'Subject' => 'SeedDMS: '.getMLText('daylyDigestMail', array(), "", $user->getLanguage()), 'Content-Type' => 'text/plain; charset=UTF-8'));

				$mail_params = array();
				if($settings->_smtpServer) {
					$mail_params['host'] = $settings->_smtpServer;
					if($settings->_smtpPort) {
						$mail_params['port'] = $settings->_smtpPort;
					}
					if($settings->_smtpUser) {
						$mail_params['auth'] = true;
						$mail_params['username'] = $settings->_smtpUser;
						$mail_params['password'] = $settings->_smtpPassword;
					}
					$mail = Mail::factory('smtp', $mail_params);
				} else {
					$mail = Mail::factory('mail');
				}
				$mail->send($user->getEmail(), $hdrs, $body);
			}
		} else {
			if(!$quite) {
				echo "User ".$user->getLogin()." has no email".PHP_EOL;
			}
		}
	} else {
		if(!$quite) {
			echo "No notification for user ".$user->getLogin()." needed".PHP_EOL;
		}
	}
}
