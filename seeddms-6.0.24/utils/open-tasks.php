<?php
if(isset($_SERVER['SEEDDMS_HOME'])) {
	require_once($_SERVER['SEEDDMS_HOME']."/inc/inc.ClassSettings.php");
} else {
	require_once("../inc/inc.ClassSettings.php");
}
include 'Mail.php';
include 'Mail/mime.php';

function usage() { /* {{{ */
	echo "Usage:\n";
	echo "  seeddms-open-tasks [--config <file>] [-u <users>] [-r receiver] [-h] [-v] [-t] [-q] -d <days> -D <days>\n";
	echo "\n";
	echo "Description:\n";
	echo "  Check for tasks which need to be done by a user.\n";
	echo "\n";
	echo "Options:\n";
	echo "  -h, --help: print usage information and exit.\n";
	echo "  -v, --version: print version and exit.\n";
	echo "  --config=<file>: set alternative config file.\n";
	echo "  -u <users>: comma separated list of user names to check. If not set all\n";
	echo "             users will be checked.\n";
	echo "  -r <receiver>: email address where all mails are sent to. If not set the\n";
	echo "             users themselves are informed.\n";
	echo "  -f <email>: set From field in notification mail\n";
	echo "  -b <base>: set base for links in html email. The final link will be\n";
	echo "             <base><httpRoot>out/out.ViewDocument.php. The default is\n";
	echo "             http://localhost\n";
	echo "  -d <days>: check for n days in the future (default 14). Days always\n".
		   "             start and end at midnight. A value of '1' means today.\n";
	echo "  -t: run in test mode (will not send any mails)\n";
	echo "  -q: be quite (just output error messages)\n";
} /* }}} */

$version = "0.0.1";
$tableformat = "%-65s %-12s";
$tableformathtml = "<tr><td>%s</td><td>%s</td></tr>";
$baseurl = "http://localhost/";
$mailfrom = "uwe@steinman.cx";

$shortoptions = "u:d:D:f:b:a:A:c:C:m:u:r:tqhv";
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
	echo $version."\n";
	exit(0);
}

/* Set alternative config file */
if(isset($options['config'])) {
	$settings = new Settings($options['config']);
} else {
	$settings = new Settings();
}
include("inc/inc.Language.php");
include("inc/inc.Extension.php");

$LANG['de_DE']['daylyDigestMail'] = 'Tägliche Benachrichtigungsmail über ausstehende Aufgaben';
$LANG['en_GB']['daylyDigestMail'] = 'Dayly digest mail with due tasks';
$LANG['de_DE']['docsNeedToCareAbout'] = 'Dokumente, die seit mehr als [days] Tagen auf ihre Bearbeitung warten.';
$LANG['en_GB']['docsNeedToCareAbout'] = 'Documents waiting for your attention for more than [days] days.';
$LANG['en_GB']['waiting_for_review'] = 'Documents waiting for your review';
$LANG['de_DE']['waiting_for_review'] = 'Dokumente, die auf ihre Prüfung warten';
$LANG['en_GB']['waiting_for_approval'] = 'Documents waiting for your approval';
$LANG['de_DE']['waiting_for_approval'] = 'Dokumente, die auf ihre Freigabe warten';
$LANG['en_GB']['waiting_for_receipt'] = 'Documents waiting for your reception';
$LANG['de_DE']['waiting_for_receipt'] = 'Dokumente, die auf ihre Empfangsbestätigung warten';
$LANG['en_GB']['waiting_for_revision'] = 'Documents waiting for your revision';
$LANG['de_DE']['waiting_for_revision'] = 'Dokumente, die auf ihre Wiederholungsprüfung warten';
$LANG['en_GB']['duedate'] = 'Due date';
$LANG['de_DE']['duedate'] = 'Fälligkeit';

if(isset($settings->_extraPath))
	ini_set('include_path', $settings->_extraPath. PATH_SEPARATOR .ini_get('include_path'));

require_once("SeedDMS/Core.php");

$days = 14;
if(isset($options['d'])) {
	$days = (int) $options['d'];
}
$enddays = 0;
if(isset($options['D'])) {
	$enddays = (int) $options['D'];
}

if($enddays >= $days) {
	echo "Value of -D must be less then value of -d\n";
	exit(1);
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
	echo "Running in test mode will not send any mail.\n";
}
$quite = false;
if(isset($options['q'])) {
	$quite = true;
}

$showobsolete = false;

$db = new SeedDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");
$db->_debug = 1;

$dms = new SeedDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);
if(!$settings->_doNotCheckDBVersion && !$dms->checkVersion()) {
	echo "Database update needed.";
	exit;
}
$dms->setRootFolderID($settings->_rootFolderID);
$dms->setMaxDirID($settings->_maxDirID);
$dms->setEnableConverting($settings->_enableConverting);
$dms->setViewOnlineFileTypes($settings->_viewOnlineFileTypes);

$startts = strtotime("midnight", time());
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

foreach($users as $user) {
	if($user->isDisabled())
		continue;
	if(!$quite)
		echo "Checking for tasks of user ".$user->getFullName()." which are due for more than ".$days." days\n\n";

	$sendmail = false; /* Set to true if there is something to report */

	$tasks = array('approval'=>array(), 'review'=>array(), 'receipt'=>array(), 'revision'=>array());
	$resArr = $dms->getDocumentList('ApproveByMe', $user);
	if($resArr) {
		foreach ($resArr as $res) {
			$tmp = explode(' ', $res['duedate'], 2);
			if($tmp[0] < date('Y-m-d', $startts-$days*86400)) {
				$document = $dms->getDocument($res["id"]);
				if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
					$tasks['approval'][] = array('id'=>$res['id'], 'name'=>$res['name'], 'date'=>$res['duedate']);
				}
			}
		}
	}

	$resArr = $dms->getDocumentList('ReviewByMe', $user);
	if($resArr) {
		foreach ($resArr as $res) {
			$tmp = explode(' ', $res['duedate'], 2);
			if($tmp[0] < date('Y-m-d', $startts-$days*86400)) {
				$document = $dms->getDocument($res["id"]);
				if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
					$tasks['review'][] = array('id'=>$res['id'], 'name'=>$res['name'], 'date'=>$res['duedate']);
				}
			}
		}
	}

	$resArr = $dms->getDocumentList('ReceiptByMe', $user);
	if($resArr) {
		foreach ($resArr as $res) {
			$tmp = explode(' ', $res['duedate'], 2);
			if($tmp[0] < date('Y-m-d', $startts-$days*86400)) {
				$document = $dms->getDocument($res["id"]);
				if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
					$tasks['receipt'][] = array('id'=>$res['id'], 'name'=>$res['name'], 'date'=>$res['duedate']);
				}
			}
		}
	}

	$resArr = $dms->getDocumentList('ReviseByMe', $user);
	if($resArr) {
		foreach ($resArr as $res) {
			$tmp = explode(' ', $res['duedate'], 2);
			if($tmp[0] < date('Y-m-d', $startts-$days*86400)) {
				$document = $dms->getDocument($res["id"]);
				if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
					$tasks['revision'][] = array('id'=>$res['id'], 'name'=>$res['name'], 'date'=>$res['duedate']);
				}
			}
		}
	}

//	print_r($tasks);

	if (count($tasks['approval'])>0 || count($tasks['review'])>0 || count($tasks['receipt'])>0 || count($tasks['revision'])>0) {
		$bodyhead = "";
		$bodyhead .= getMLText('docsNeedToCareAbout', array('days'=>$days))."\n";
		$bodyhead .= "\n";

		$bodyhtmlhead = "<html>\n<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n<title>SeedDMS: ".getMLText('daylyDigestMail')."</title>\n<base href=\"".$baseurl.$settings->_httpRoot."\" />\n</head>\n<body>\n";
		$bodyhtmlhead .= "<h2>";
		$bodyhtmlhead .= getMLText('docsNeedToCareAbout', array('days'=>$days))."\n";
		$bodyhtmlhead .= "</h2>\n";
		$bodyhtmlhead .= "<h3>";
		$bodyhtmlhead .= "</h3>\n";

		/* Build an array of mail bodies for receiver in $bodyarr
		 * $bodyarr['email@domain.de']['plain'] = 'mail body for plain text mail'
		 * $bodyarr['email@domain.de']['html'] = 'mail body for html mail'
		 */
		$email = $user->getEmail();
		$bodyarr = array($email=>array('plain'=>'', 'html'=>''));

		foreach(array('review', 'approval', 'receipt', 'revision') as $typ) {
			if(!empty($tasks[$typ])) {
				$bodyarr[$email]['plain'] .= sprintf($tableformat."\n", getMLText('waiting_for_'.$typ, array()), getMLText("duedate", array()));	
				$bodyarr[$email]['plain'] .= "---------------------------------------------------------------------------------------\n";
				$bodyarr[$email]['html'] .= "<h3>".getMLText('waiting_for_'.$typ, array())."</h3>";	
				$bodyarr[$email]['html'] .= "<table>\n";
				$bodyarr[$email]['html'] .= sprintf($tableformathtml."\n", getMLText('name', array()), getMLText("duedate", array()));	
				foreach($tasks[$typ] as $task) {
					$bodyarr[$email]['plain'] .= sprintf($tableformat."\n", $task["name"], (!$task["date"] ? "-":$task["date"]));	
					$bodyarr[$email]['html'] .= sprintf($tableformathtml."\n", '<a href="out/out.ViewDocument.php?documentid='.$task["id"].'">'.htmlspecialchars($task["name"]).'</a>', (!$task["date"] ? "-":$task["date"]));	
				}
				$bodyarr[$email]['plain'] .= "\n";
				$bodyarr[$email]['html'] .= "</table>\n";
			}
		}
		$bodyfoot = "\n";
		$bodyhtmlfoot = "";
		
//		echo $body;
//		echo "----------------------------\n\n\n";
//		echo $bodyhtml;
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
	} else {
		if(!$quite) {
			echo "No notification needed\n";
		}
	}
}
