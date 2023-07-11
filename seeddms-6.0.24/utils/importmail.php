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
	echo "  seeddms-importmail [--config <file>] [-h] [-v] [-t] --user <seeddms user> --imap-urn <urn of mailbox> --imap-user <imap-user> --imap-password <-|password> [--eml] [--mode|-m <mode>] [--delete] [--attribute <header-name:attr-name>] [--subfolder] [--inform-sender] [--type <type of attachments>] -F <folder id> -d <dirname>".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  This program accesses a mail box via imap and imports all mail".PHP_EOL;
	echo "  attachments or the complete mail in rfc822 format.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  -t: just check for mails without import.".PHP_EOL;
	echo "  --config: set alternative config file.".PHP_EOL;
	echo "  --user: use this user for accessing seeddms.".PHP_EOL;
	echo "  --imap-urn: mailbox of imap server, {hostname[:port][flags]}mailbox_name".PHP_EOL;
	echo "    e.g. '{mail.mydomain.com:993/imap/ssl/novalidate-cert}INBOX".PHP_EOL;
	echo "    'flags' is something like '/imap' for an imap mail box without".PHP_EOL;
	echo "    ssl encryption, '/imap/ssl' for an imap mailbox with ssl encryption or".PHP_EOL;
	echo "    '/imap/ssl/novalidate-cert' if an invalid certificate is accepted".PHP_EOL;
	echo "    'mailbox_name' is the name of the mailbox or INBOX for the default mail box".PHP_EOL;
	echo "    For a full explaination of the urn check".PHP_EOL;
	echo "    http://php.net/manual/de/function.imap-open.php".PHP_EOL;
	echo "  --imap-user: use this user for imap access.".PHP_EOL;
	echo "  --imap-password: set password for imap access ('-' for stdin).".PHP_EOL;
	echo "  --imap-config: Read imap configuration from file (urn, user, password).".PHP_EOL;
	echo "  --eml: save whole mail in rfc822 format instead of extracting attachments.".PHP_EOL;
	echo "  -m, --mode: can be 'all', 'new', 'unseen', 'today', 'since:<date>', 'on:<date>".PHP_EOL;
	echo "    <date> can be any parsable date by strtotime(), e.g. -3days".PHP_EOL;
	echo "  -F <folder id>: id of folder the file is uploaded to".PHP_EOL;
	echo "  --delete: delete mails after successful import (default is to set 'seen')".PHP_EOL;
	echo "  --attribute: <header field name>:<attribute name>".PHP_EOL;
	echo "    save mail header field into attribute. If --subfolder is set, the header fields are".PHP_EOL;
	echo "    saved in attributes attached to the folder of the mail, otherwise they are attached".PHP_EOL;
	echo "    to the documents containing the attachment.".PHP_EOL;
	echo "    <header field name> is the name of the mail header field in lower case".PHP_EOL;
	echo "    (e.g. subject, toaddress, fromaddress, date, message_id)".PHP_EOL;
	echo "    <attribute_name> is the name of the seeddms attribute".PHP_EOL;
	echo "  --inform-sender: inform the sender about the result of the import".PHP_EOL;
	echo PHP_EOL;
	echo "If attachments are imported the following options are available too:".PHP_EOL;
	echo "  --subfolder: create subfolder for each mail".PHP_EOL;
	echo "  --type: set type of message to extract (can be set more than once).".PHP_EOL;
	echo "    Common types are:".PHP_EOL;
	echo "    JPEG: jpeg images".PHP_EOL;
	echo "    PNG: png images".PHP_EOL;
	echo "    GIF: gif images".PHP_EOL;
	echo "    TIFF: tiff images".PHP_EOL;
	echo "    MPEG: mpeg files (e.g. mp3)".PHP_EOL;
 	echo "    PDF: PDF files".PHP_EOL;
 	echo "    VND.OPENXMLFORMATS-OFFICEDOCUMENT.SPREADSHEETML.SHEET: Excel xml files".PHP_EOL;
 	echo "    VND.OPENXMLFORMATS-OFFICEDOCUMENT.WORDPROCESSINGML.DOCUMENT: MS Word xml files".PHP_EOL;
 	echo "    X-XZ: xz compressed files".PHP_EOL;
 	echo "    GZIP: gzip compressed files".PHP_EOL;
 	echo "    ZIP: zip compressed files".PHP_EOL;
 	echo "    VND.DEBIAN.BINARY-PACKAGE: debian package files".PHP_EOL;
 	echo "    VND.OASIS.OPENDOCUMENT.TEXT: Open document type files".PHP_EOL;
 	echo "    RTF: rtf files".PHP_EOL;
 	echo "    MSWORD: MS Word doc files".PHP_EOL;
 	echo "    PGP-SIGNATURE: pgp signatures".PHP_EOL;
	echo PHP_EOL;
	echo "Example:".PHP_EOL;
	echo "  seeddms-importmail --config=/home/www-data/seeddms/conf/settings.xml --imap-urn \"{mail.mydomain.com:993/imap/ssl/novalidate-cert}INBOX\" --imap-user admin --imap-password - --mode=\"since:-2 days\" --user=admin -F 8376 --subfolder --attribute=\"toaddress:Mail: To\" --attribute=\"fromaddress:Mail: From\" --attribute=\"subject:Mail: Subject\" --attribute=\"date:Mail: Date\" --type=\\!PGP-SIGNATURE".PHP_EOL;
	echo PHP_EOL;
	echo "  This will import all attachments of mails received within the last two days. It creates".PHP_EOL;
	echo "  a folder for each mail (below folder 8376) and puts all attachments into it.".PHP_EOL;
	echo "  The folder will get 4 attributes containing the subject, fromaddress, toaddress and date.".PHP_EOL;
	echo "  The attributes have to exist already. Attachments of type PGP-SIGNATURE will not be".PHP_EOL;
	echo "  imported. The password for the mailbox is read from stdin.".PHP_EOL;
	echo PHP_EOL;
	echo "  seeddms-importmail --config=/home/www-data/seeddms/conf/settings.xml --imap-urn \"{mail.mydomain.com:993/imap/ssl/novalidate-cert}INBOX\" --imap-user admin --imap-password - --mode=\"on:2016-06-07\" --user=admin -F 8376 --eml".PHP_EOL;
	echo "  This will import all mails in rfc822 format received on 7th of June 2016.".PHP_EOL;
} /* }}} */

$g_config = array();
$version = "0.0.1";
$shortoptions = "d:F:p:m:b:thv";
$longoptions = array('help', 'version', 'imap-urn:', 'config:', 'user:', 'imap-user:', 'imap-password:', 'imap-config:', 'type::', 'mode:', 'delete', 'subfolder', 'inform-sender', 'attribute:', 'base-url:', 'eml');
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

/* Check for test mode */
$g_config['dryrun'] = false;
if(isset($options['t'])) {
	$g_config['dryrun'] = true;
}

/* Check if complete mail is saved as eml file */
$g_config['saveeml'] = false;
if(isset($options['eml'])) {
	$g_config['saveeml'] = true;
}

/* Create subfolder */
$g_config['createsubfolder'] = false;
if(isset($options['subfolder'])) {
	$g_config['createsubfolder'] = true;
}

/* Inform sender */
$g_config['inform_sender'] = false;
if(isset($options['inform-sender'])) {
	$g_config['inform_sender'] = true;
}

/* Base url */
$g_config['base_url'] = '';
if(isset($options['b']) || isset($options['base-url'])) {
	if(isset($options['b']))
		$g_config['base_url'] = rtrim($options['b'], '/');
	else
		$g_config['base_url'] = rtrim($options['base-url'], '/');
}

/* Check for test mode */
$g_config['deletemails'] = false;
if(isset($options['delete'])) {
	$g_config['deletemails'] = true;
}

/* set mode for retrieving emails */
$g_config['mode'] = '';
if(isset($options['m']) || isset($options['mode'])) {
	if(isset($options['m']))
		$g_config['mode'] = $options['m'];
	else
		$g_config['mode'] = $options['mode'];
}

/* set attributes being set from mail header */
$g_config['attributes'] = array();
if(isset($options['attribute'])) {
	if(is_string($options['attribute']))
		$g_config['attributes'][] = $options['attribute'];
	else
		$g_config['attributes'] = $options['attribute'];
}

$g_config['inc_subtypes'] = array();
$g_config['exc_subtypes'] = array();
if(!empty($options['type'])) {
	if(is_string($options['type'])) {
		if($options['type'][0] == '!')
			$g_config['exc_subtypes'][] = substr($options['type'], 1);
		else
			$g_config['inc_subtypes'][] = $options['type'];
	} else {
		foreach($options['type'] as $type) {
			if($type[0] == '!')
				$g_config['exc_subtypes'][] = substr($type, 1);
			else
				$g_config['inc_subtypes'][] = $type;
		}
	}
}

/* Set alternative config file */
if(isset($options['config'])) {
	define('SEEDDMS_CONFIG_FILE', $options['config']);
} elseif(isset($_SERVER['SEEDDMS_CONFIG_FILE'])) {
	define('SEEDDMS_CONFIG_FILE', $_SERVER['SEEDDMS_CONFIG_FILE']);
}

/* Read imap data from config file */
if(isset($options['imap-config'])) {
	if(file_exists($options['imap-config'])) {
		$lines = file($options['imap-config']);
		foreach($lines as $line) {
			$line = trim($line);
			if($line[0] != '#') {
				list($key, $value) = explode('=', $line, 2);
				switch($key) {
				case 'urn':
				case 'user':
				case 'password':
					$g_config['imap-'.$key] = trim($value);
					break;
				}
			}
		}
	} else {
		echo "Cannot open imap config file".PHP_EOL;
		exit(1);
	}
}

/* Set urn of imap server */
/* e.g. {host:port/imap/ssl/novalidate-cert}folder */
if(!isset($g_config['imap-urn'])) {
	if(isset($options['imap-urn'])) {
		$g_config['imap-urn'] = $options['imap-urn'];
	} else {
		usage();
		exit(1);
	}
}

if(!isset($g_config['imap-user'])) {
	if(isset($options['imap-user'])) {
		$g_config['imap-user'] = $options['imap-user'];
	} else {
		usage();
		exit(1);
	}
}

/* Set password for imap */
if(!isset($g_config['imap-password'])) {
	if(isset($options['p']) || isset($options['imap-password'])) {
		$password = isset($options['p']) ? $options['p'] : (isset($options['imap-password']) ? $options['imap-password'] : '');
		if($password == '-') {
			$oldStyle = shell_exec('stty -g');
			echo "Please enter password: ";
			shell_exec('stty -echo');
			$line = fgets(STDIN);
			$g_config['imap-password'] = rtrim($line);
			shell_exec('stty ' . $oldStyle);
			echo PHP_EOL;
		} else {
			$g_config['imap-password'] = $password;
		}
	} else {
		$g_config['imap-password'] = '';
	}
}

if(isset($options['F'])) {
	$folderid = (int) $options['F'];
} else {
	if(!$g_config['dryrun']) {
		echo "Missing folder ID".PHP_EOL;
		usage();
		exit(1);
	}
}

if(isset($options['user'])) {
	$userlogin = $options['user'];
} else {
	usage();
	exit(1);
}

include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");
include($myincpath."/inc/inc.ClassNotificationService.php");
include($myincpath."/inc/inc.ClassEmailNotify.php");
include($myincpath."/inc/inc.ClassController.php");

/* Create a global user object */
if(!($g_config['dms_user'] = $dms->getUserByLogin($userlogin))) {
	echo "User with login '".$userlogin."' does not exists.";
	exit(1);
}

$dms->setUser($g_config['dms_user']);
/* $user must be set globally because the controller needs it */
$user = $g_config['dms_user'];

/* Check if import folder exists and is writable */
$dms_folder = $dms->getFolder($folderid);
if (!is_object($dms_folder)) {
	echo "Could not find specified folder".PHP_EOL;
	exit(1);
}

if ($dms_folder->getAccessMode($g_config['dms_user']) < M_READWRITE) {
	echo "Not sufficient access rights".PHP_EOL;
	exit(1);
}
$g_config['dms_folder'] = $dms_folder;

/* Check if all attributes exist {{{ */
$g_config['attrmap'] = array();
if($g_config['attributes']) {
	foreach($g_config['attributes'] as $tmp) {
		$hdr_attr = explode(':', $tmp, 2);
		if(count($hdr_attr) == 2) {
			if(in_array($hdr_attr[0], array('subject', 'toaddress', 'fromaddress', 'date', 'message_id'))) {
				if($attrdef = $dms->getAttributeDefinitionByName($hdr_attr[1])) {
					if($g_config['createsubfolder']) {
						$ot = $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder;
					} else {
						$ot = $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document;
					}
					$t = $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_string;
					if($ot) {
						if($t) {
							$g_config['attrmap'][strtolower($hdr_attr[0])] = $attrdef;
						} else {
							echo "Attribute '".$hdr_attr[1]."' has incorrect type".PHP_EOL;
						}
					} else {
						echo "Attribute '".$hdr_attr[1]."' has incorrect object type".PHP_EOL;
					}
				} else {
					echo "Unknown attribute name '".$hdr_attr[1]."' in attribute mapping".PHP_EOL;
				}
			} else {
				echo "Unknown header field '".strtolower($hdr_attr[0])."' in attribute mapping".PHP_EOL;
			}
		}
	}
} /* }}} */

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

function getpart($mbox,$mid,$p,$partno) { /* {{{ */
	// $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
	global $g_config, $attachments;

	$htmlmsg = $plainmsg = '';
	if((!isset($g_config['inc_subtypes']) && !isset($g_config['exc_subtypes'])) || (!$g_config['inc_subtypes'] && !$g_config['exc_subtypes']) || in_array($p->subtype, $g_config['inc_subtypes']) || !in_array($p->subtype, $g_config['exc_subtypes'])) {
		// DECODE DATA
		$data = ($partno)?
			imap_fetchbody($mbox,$mid,$partno):  // multipart
			imap_body($mbox,$mid);  // simple
		// Any part may be encoded, even plain text messages, so check everything.
		if ($p->encoding==4)
			$data = quoted_printable_decode($data);
		elseif ($p->encoding==3)
			$data = base64_decode($data);

		// PARAMETERS
		// get all parameters, like charset, filenames of attachments, etc.
		$params = array();
		if (isset($p->parameters))
			foreach ($p->parameters as $x)
				$params[strtolower($x->attribute)] = $x->value;
		if (isset($p->dparameters))
			foreach ($p->dparameters as $x)
				$params[strtolower($x->attribute)] = $x->value;

		// ATTACHMENT
		// Any part with a filename is an attachment,
		// so an attached text file (type 0) is not mistaken as the message.
		if (isset($params['filename']) || isset($params['name'])) {
			// filename may be given as 'Filename' or 'Name' or both
			$filename = (!empty($params['filename'])) ? $params['filename'] : $params['name'];
			// filename may be encoded, so see imap_mime_header_decode()
			$attachments[] = array('filename'=>$filename, 'type'=>$p->subtype, 'data'=>$data);  // this is a problem if two files have same name
		}

		// TEXT
		if ($p->type==0 && $data) {
			// Messages may be split in different parts because of inline attachments,
			// so append parts together with blank row.
			if (strtolower($p->subtype)=='plain')
				$plainmsg .= trim($data) .PHP_EOL.PHP_EOL;
			else
				$htmlmsg .= $data ."<br><br>";
			$charset = $params['charset'];  // assume all parts are same charset
		}

		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		elseif ($p->type==2 && $data) {
			$plainmsg .= $data.PHP_EOL.PHP_EOL;
		}
	}

	// SUBPART RECURSION
	if (isset($p->parts)) {
		foreach ($p->parts as $partno0=>$p2)
			getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
	}
} /* }}} */

/**
 * Convert a quoted printable and converts it into utf-8
 *
 * @param string $item
 * @return string recoded string in utf-8
 */
function rfc2047_decode($item) { /* {{{ */
	$text = '';
	$elements = imap_mime_header_decode($item);
	foreach ($elements as $element) {
		switch(strtoupper($element->charset)) {
		case 'DEFAULT':
		case 'UTF-8':
			$text .= $element->text;
			break;
		default:
			$text .= iconv($element->charset, 'UTF-8', $element->text);
		}
	}
	return $text;
} /* }}} */

function import_mail($mbox, $msgids) { /* {{{ */
	global $g_config;
	global $dms, $user;
	global $settings, $indexconf;
	global $attachments;

	$attachments = array();
	if($settings->_enableFullSearch) {
		$index = $indexconf['Indexer']::open($settings->_luceneDir);
		$indexconf['Indexer']::init($settings->_stopWordsFile);
	} else {
		$index = null;
		$indexconf = null;
	}


	echo "Processing ".count($msgids)." mails".PHP_EOL;
	foreach($msgids as $mid) {
		$header = imap_header($mbox, $mid);
		echo PHP_EOL;
		echo "Reading msg ".$header->message_id.PHP_EOL;
		echo " Subject: ".rfc2047_decode($header->subject).PHP_EOL;
		echo " From: ".rfc2047_decode($header->fromaddress).PHP_EOL;
		echo " To: ".rfc2047_decode($header->toaddress).PHP_EOL;
		echo " Date: ".$header->date.PHP_EOL;

		if($g_config['saveeml']) {
			if(!$g_config['dryrun']) {
				$f = $g_config['dms_folder'];
				if($f) {
					$headers = imap_fetchheader($mbox, $mid, FT_PREFETCHTEXT);
					$body = imap_body($mbox, $mid);
					$filetmp = tempnam(sys_get_temp_dir(), 'IMPMAIL');
					file_put_contents($filetmp, $headers . PHP_EOL . $body);

					$name = rfc2047_decode($header->subject);
					$from = rfc2047_decode($header->fromaddress);
					$reviewers = array();
					$approvers = array();
					$comment = '';
					$version_comment = '';
					$reqversion = 1;
					$expires = false;
					$keywords = '';
					$categories = array();
					$attributes = array();
					if($g_config['attrmap']) {
						foreach($g_config['attrmap'] as $hdrfield=>$attrdef) {
							$attributes[$attrdef->getID()] = rfc2047_decode($header->{$hdrfield});
						}
					}
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mimetype = finfo_file($finfo, $filetmp);
					$filetype = ".eml";

					$controller = Controller::factory('AddDocument', array('dms'=>$dms, 'user'=>$user));
					$controller->setParam('documentsource', 'script');
					$controller->setParam('folder', $f);
					$controller->setParam('index', $index);
					$controller->setParam('indexconf', $indexconf);
					$controller->setParam('name', $name);
					$controller->setParam('comment', $comment);
					$controller->setParam('expires', $expires);
					$controller->setParam('keywords', $keywords);
					$controller->setParam('categories', $categories);
					$controller->setParam('owner', $g_config['dms_user']);
					$controller->setParam('userfiletmp', $filetmp);
					$controller->setParam('userfilename', $name);
					$controller->setParam('filetype', $filetype);
					$controller->setParam('userfiletype', $mimetype);
					$minmax = $f->getDocumentsMinMax();
					if($settings->_defaultDocPosition == 'start')
						$controller->setParam('sequence', $minmax['min'] - 1);
					else
						$controller->setParam('sequence', $minmax['max'] + 1);
					$controller->setParam('reviewers', $reviewers);
					$controller->setParam('approvers', $approvers);
					$controller->setParam('reqversion', $reqversion);
					$controller->setParam('versioncomment', $version_comment);
					$controller->setParam('attributes', $attributes);
					$controller->setParam('attributesversion', array());
					$controller->setParam('workflow', null);
					$controller->setParam('notificationgroups', array());
					$controller->setParam('notificationusers', array());
					$controller->setParam('maxsizeforfulltext', $settings->_maxSizeForFullText);
					$controller->setParam('defaultaccessdocs', $settings->_defaultAccessDocs);
					if(!$document = $controller->run()) {
						echo "(could not be added, ".$controller->getErrorMsg().")";
					} else {
						echo "(added)";
						if(!$g_config['dryrun'] && $g_config['deletemails'])
							imap_delete($mbox, $mid);
					}
					if($g_config['inform_sender']) {
						
					}
				}
			}
		} else {
			$from = rfc2047_decode($header->fromaddress);
			$attachments = array();
	//			print_r($header);
			$s = imap_fetchstructure($mbox,$mid);
	//			print_r($s);
			if($s) {
				if (!isset($s->parts))  // simple
					getpart($mbox,$mid,$s,0);  // pass 0 as part-number
				else {  // multipart: cycle through each part
					foreach ($s->parts as $partno0=>$p)
						getpart($mbox,$mid,$p,$partno0+1);
				}
				if($attachments) {
					echo " Attachments:".PHP_EOL;
					$sequence = 1;
					$f = null;
					if(!$g_config['dryrun']) {
						if($g_config['createsubfolder']) {
							$attributes = array();
							if($g_config['attrmap']) {
								foreach($g_config['attrmap'] as $hdrfield=>$attrdef) {
									$attributes[$attrdef->getID()] = rfc2047_decode($header->{$hdrfield});
								}
							}
							$f = $g_config['dms_folder']->addSubFolder(rfc2047_decode($header->subject), '', $g_config['dms_user'], 0, $attributes);

						} else {
							$f = $g_config['dms_folder'];
						}
					} else {
						$f = true; // fake $f, to list at least the attachments
					}
					if($f) {
						$controller = Controller::factory('AddDocument', array('dms'=>$dms, 'user'=>$user));
						foreach($attachments as $attachment) {
							echo "  ".$attachment['type'].": ".rfc2047_decode($attachment['filename'])." ";

							if(!$g_config['dryrun']) {
								$name = rfc2047_decode($attachment['filename']);
								$filetmp = tempnam(sys_get_temp_dir(), 'IMPMAIL');
								file_put_contents($filetmp, $attachment['data']);

								$reviewers = array();
								$approvers = array();
								$comment = '';
								$version_comment = '';
								$reqversion = 1;
								$expires = false;
								$keywords = '';
								$categories = array();
								$attributes = array();
								if(!$g_config['createsubfolder']) {
									if($g_config['attrmap']) {
										foreach($g_config['attrmap'] as $hdrfield=>$attrdef) {
											$attributes[$attrdef->getID()] = rfc2047_decode($header->{$hdrfield});
										}
									}
								}

								$finfo = finfo_open(FILEINFO_MIME_TYPE);
								$mimetype = finfo_file($finfo, $filetmp);
								echo "(".$mimetype.") ";
								$lastDotIndex = strrpos($name, ".");
								if (is_bool($lastDotIndex) && !$lastDotIndex) $filetype = ".";
								else $filetype = substr($name, $lastDotIndex);

								$controller->setParam('documentsource', 'script');
								$controller->setParam('folder', $f);
								$controller->setParam('index', $index);
								$controller->setParam('indexconf', $indexconf);
								$controller->setParam('name', $name);
								$controller->setParam('comment', $comment);
								$controller->setParam('expires', $expires);
								$controller->setParam('keywords', $keywords);
								$controller->setParam('categories', $categories);
								$controller->setParam('owner', $g_config['dms_user']);
								$controller->setParam('userfiletmp', $filetmp);
								$controller->setParam('userfilename', $name);
								$controller->setParam('filetype', $filetype);
								$controller->setParam('userfiletype', $mimetype);
								$minmax = $f->getDocumentsMinMax();
								if($settings->_defaultDocPosition == 'start')
									$controller->setParam('sequence', $minmax['min'] - 1);
								else
									$controller->setParam('sequence', $minmax['max'] + 1);
								$controller->setParam('reviewers', $reviewers);
								$controller->setParam('approvers', $approvers);
								$controller->setParam('reqversion', $reqversion);
								$controller->setParam('versioncomment', $version_comment);
								$controller->setParam('attributes', $attributes);
								$controller->setParam('attributesversion', array());
								$controller->setParam('workflow', null);
								$controller->setParam('notificationgroups', array());
								$controller->setParam('notificationusers', array());
								$controller->setParam('maxsizeforfulltext', $settings->_maxSizeForFullText);
								$controller->setParam('defaultaccessdocs', $settings->_defaultAccessDocs);
								if(!$document = $controller->run()) {
									echo "(could not be added, ".$controller->getErrorMsg().")";
									$infosubject = $settings->_siteName.": Importing mail attachment failed";
									$infobody = "Attachment ".rfc2047_decode($attachment['filename'])." (".$mimetype.", ".$attachment['type'].") could not be imported.".PHP_EOL.$controller->getErrorMsg();
								} else {
									echo "(added)";
									$infosubject = $settings->_siteName.": Importing mail attachment succeded";
									$infobody = "Attachment ".rfc2047_decode($attachment['filename'])." (".$mimetype.", ".$attachment['type'].") imported.";
									$infobody .= PHP_EOL.PHP_EOL;
									$infobody .= $g_config['base_url'].'/'.$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
								}
								if($g_config['inform_sender']) {
									mail($from, $infosubject, $infobody);
								}
								$sequence++;
							}
							echo PHP_EOL;
						}
					}
					if(!$g_config['dryrun'] && $g_config['deletemails'])
						imap_delete($mbox, $mid);
				}
			}
		}
	}
} /* }}} */

/*
$host = 'mail.mmk-hagen.de';
$port = '993';
$ssl = 'ssl/novalidate-cert';
$folder = $g_config['imapfolder'];
$urn = "{"."$host:$port/imap/$ssl"."}$folder";
*/

//echo $urn;
$options = 0;
if($g_config['dryrun'])
	$options = OP_READONLY;
$mbox = imap_open($g_config['imap-urn'], $g_config['imap-user'], $g_config['imap-password'], $options);
if(!$mbox) {
	echo "Error opening Mailbox".PHP_EOL;
	exit;
}
$msgids = array();
switch($g_config['mode']) {
case 'today':
	$msgids = imap_search($mbox, 'ON '.date('d-M-Y'));
	break;
case 'unseen':
	$msgids = imap_search($mbox, 'UNSEEN');
	break;
case 'new':
	$msgids = imap_search($mbox, 'NEW');
	break;
default:
	if(substr($g_config['mode'], 0, 6) == 'since:') {
		$tmp = explode(':', $g_config['mode'], 2);
		if($tt = strtotime($tmp[1])) {
			$msgids = imap_search($mbox, 'SINCE '.date('d-M-Y', $tt));
		}
	} elseif(substr($g_config['mode'], 0, 3) == 'on:') {
		$tmp = explode(':', $g_config['mode'], 2);
		if($tt = strtotime($tmp[1])) {
			$msgids = imap_search($mbox, 'ON '.date('d-M-Y', $tt));
		}
	} elseif(substr($g_config['mode'], 0, 7) == 'before:') {
		$tmp = explode(':', $g_config['mode'], 2);
		if($tt = strtotime($tmp[1])) {
			$msgids = imap_search($mbox, 'BEFORE '.date('d-M-Y', $tt));
		}
	} else {
		$msgids = imap_search($mbox, 'ALL');
	}
}
if($msgids) {
	import_mail($mbox, $msgids);
	imap_expunge($mbox);
}
imap_close($mbox);

