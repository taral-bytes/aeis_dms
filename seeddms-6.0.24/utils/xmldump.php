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
	echo "  seeddms-xmldump [-h] [-v] [--config <file>]".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  This program creates an xml dump of the whole or parts of the dms.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  --config: set alternative config file.".PHP_EOL;
	echo "  --folder: set start folder.".PHP_EOL;
	echo "  --skip-root: do not export the root folder itself.".PHP_EOL;
	echo "  --sections <sections>: comma seperated list of sections to export.".PHP_EOL;
	echo "  --maxsize: maximum size of files to be included in output".PHP_EOL;
	echo "    (defaults to 100000)".PHP_EOL;
	echo "  --contentdir: directory where all document versions are stored".PHP_EOL;
	echo "    which are larger than maxsize.".PHP_EOL;
} /* }}} */

function wrapWithCData($text) { /* {{{ */
	if(preg_match("/[<>&]/", $text))
		return("<![CDATA[".$text."]]>");
	else
		return $text;
} /* }}} */

$version = "0.0.1";
$shortoptions = "hv";
$longoptions = array('help', 'version', 'skip-root', 'config:', 'folder:', 'maxsize:', 'contentdir:', 'sections:');
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

/* Set maximum size of files included in xml file */
if(isset($options['maxsize'])) {
	$maxsize = intval($options['maxsize']);
} else {
	$maxsize = 100000;
}

/* Set directory for file largen than maxsize */
if(isset($options['contentdir'])) {
	if(file_exists($options['contentdir'])) {
		$contentdir = $options['contentdir'];
		if(substr($contentdir, -1, 1) != DIRECTORY_SEPARATOR)
			$contentdir .= DIRECTORY_SEPARATOR;
	} else {
		echo "Directory ".$options['contentdir']." does not exists".PHP_EOL;
		exit(1);
	}
} else {
	$contentdir = '';
}

$sections = array();
if(isset($options['sections'])) {
	$sections = explode(',', $options['sections']);
}

$folderid = 0;
if(isset($options['folder'])) {
	$folderid = intval($options['folder']);
}

$skiproot = false;
if(isset($options['skip-root'])) {
	$skiproot = true;
}

$statistic = array(
	'documents'=>0,
	'folders'=>0,
	'roles'=>0,
	'users'=>0,
	'groups'=>0,
	'attributedefinitions'=>0,
	'keywordcategories'=>0,
	'documentcategories'=>0,
	'transmittals'=>0,
	'workflows'=>0,
	'workflowactions'=>0,
	'workflowstates'=>0,
);

function dumplog($version, $type, $logs, $indent) { /* {{{ */
	global $dms, $contentdir, $maxsize;

	$document = $version->getDocument();
	switch($type) {
	case 'approval':
		$type2 = 'approve';
		break;
	default:
		$type2 = $type;
	}
	echo $indent."   <".$type."s>\n";
	$curid = 0;
	foreach($logs as $a) {
		if($a[$type2.'ID'] != $curid) {
			if($curid != 0) {
				echo $indent."    </".$type.">\n";
			}
			echo $indent."    <".$type." id=\"".$a[$type2.'ID']."\">\n";
			echo $indent."     <attr name=\"type\">".$a['type']."</attr>\n";
			echo $indent."     <attr name=\"required\">".$a['required']."</attr>\n";
		}
		echo $indent."     <".$type."log id=\"".$a[$type2.'LogID']."\">\n";
		echo $indent."      <attr name=\"user\">".$a['userID']."</attr>\n";
		echo $indent."      <attr name=\"status\">".$a['status']."</attr>\n";
		echo $indent."      <attr name=\"comment\">".wrapWithCData($a['comment'])."</attr>\n";
		echo $indent."      <attr name=\"date\" format=\"Y-m-d H:i:s\">".$a['date']."</attr>\n";
		if(!empty($a['file'])) {
			$filename = $dms->contentDir . $document->getDir().'r'.(int) $a[$type2.'LogID'];
			if(file_exists($filename)) {
				echo $indent."      <data length=\"".filesize($filename)."\"";
				if(filesize($filename) < $maxsize) {
					echo ">\n";
					echo chunk_split(base64_encode(file_get_contents($filename)), 76, "\n");
					echo $indent."      </data>\n";
				} else {
					echo " fileref=\"".$filename."\" />\n";
					if($contentdir) {
						copy($filename, $contentdir.$document->getID()."-R-".$a[$type2.'LogID']);
					} else {
						echo "Warning: ".$type." log file (size=".filesize($filename).") will be missing from output\n";
					}
				}
			}
		}
		echo $indent."     </".$type."log>\n";
		$curid = $a[$type2.'ID'];
	}
	if($curid != 0)
		echo $indent."    </".$type.">\n";
	echo $indent."   </".$type."s>\n";
} /* }}} */

function dumpNotifications($notifications, $indent) { /* {{{ */
	if($notifications) {
		if($notifications['groups'] || $notifications['users']) {
			echo $indent." <notifications>\n";
			if($notifications['users']) {
				foreach($notifications['users'] as $user) {
					echo $indent."  <user id=\"".$user->getID()."\" />\n";
				}
			}
			if($notifications['groups']) {
				foreach($notifications['groups'] as $group) {
					echo $indent."  <group id=\"".$group->getID()."\" />\n";
				}
			}
			echo $indent." </notifications>\n";
		}
	}
} /* }}} */

function tree($folder, $parent=null, $indent='', $skipcurrent=false) { /* {{{ */
	global $sections, $statistic, $index, $dms, $maxsize, $contentdir;

	if(!$sections || in_array('folders', $sections)) {
	if(!$skipcurrent) {
		echo $indent."<folder id=\"".$folder->getId()."\"";
		if($parent)
			echo " parent=\"".$parent->getID()."\"";
		echo ">\n";
		echo $indent." <attr name=\"name\">".wrapWithCData($folder->getName())."</attr>\n";
		echo $indent." <attr name=\"date\" format=\"Y-m-d H:i:s\">".date('Y-m-d H:i:s', $folder->getDate())."</attr>\n";
		echo $indent." <attr name=\"defaultaccess\">".$folder->getDefaultAccess()."</attr>\n";
		echo $indent." <attr name=\"inheritaccess\">".$folder->inheritsAccess()."</attr>\n";
		echo $indent." <attr name=\"sequence\">".$folder->getSequence()."</attr>\n";
		echo $indent." <attr name=\"comment\">".wrapWithCData($folder->getComment())."</attr>\n";
		echo $indent." <attr name=\"owner\">".$folder->getOwner()->getId()."</attr>\n";
		if($attributes = $folder->getAttributes()) {
			foreach($attributes as $attribute) {
				$attrdef = $attribute->getAttributeDefinition();
				echo $indent." <attr type=\"user\" attrdef=\"".$attrdef->getID()."\">".wrapWithCData($attribute->getValue())."</attr>\n";
			}
		}
		$notifications = $folder->getNotifyList();
		dumpNotifications($notifications, $indent);

		/* getAccessList() returns also inherited access. So first check
		 * if inheritsAccess is set and don't output any acls in that case.
		 * There could be acls of the folder, which will be visible once the
		 * inheritsAccess is turned off. Those entries will be lost in the
		 * xml output.
		 */
		if(!$folder->inheritsAccess()) {
			$accesslist = $folder->getAccessList();
			if($accesslist['users'] || $accesslist['groups']) {
			echo $indent." <acls>\n";
			foreach($accesslist['users'] as $acl) {
				echo $indent."  <acl type=\"user\"";
				$user = $acl->getUser();
				echo " user=\"".$user->getID()."\"";
				echo " mode=\"".$acl->getMode()."\"";
				echo "/>\n";
			}
			foreach($accesslist['groups'] as $acl) {
				echo $indent."  <acl type=\"group\"";
				$group = $acl->getGroup();
				echo $indent." group=\"".$group->getID()."\"";
				echo $indent." mode=\"".$acl->getMode()."\"";
				echo "/>\n";
			}
			echo $indent." </acls>\n";
			}
		}
		echo $indent."</folder>\n";
		$statistic['folders']++;
		$parentfolder = $folder;
	} else {
		$parentfolder = null;
	}
	$subfolders = $folder->getSubFolders();
	if($subfolders) {
		foreach($subfolders as $subfolder) {
			tree($subfolder, $parentfolder, $indent);
		}
	}
	}

	if(!$sections || in_array('documents', $sections)) {
	$documents = $folder->getDocuments();
	if($documents) {
		foreach($documents as $document) {
			$owner = $document->getOwner();
			/* parent folder is only set if it is no skipped */
			echo $indent."<document id=\"".$document->getId()."\"".(!$skipcurrent ? " folder=\"".$folder->getID()."\"" : "");
			if($document->isLocked())
				echo " locked=\"true\"";
			echo ">\n";
			echo $indent." <attr name=\"name\">".wrapWithCData($document->getName())."</attr>\n";
			echo $indent." <attr name=\"date\" format=\"Y-m-d H:i:s\">".date('Y-m-d H:i:s', $document->getDate())."</attr>\n";
			if($document->getExpires())
				echo $indent." <attr name=\"expires\" format=\"Y-m-d H:i:s\">".date('Y-m-d H:i:s', $document->getExpires())."</attr>\n";
			echo $indent." <attr name=\"owner\">".$owner->getId()."</attr>\n";
			if($document->getKeywords())
				echo $indent." <attr name=\"keywords\">".wrapWithCData($document->getKeywords())."</attr>\n";
			echo $indent." <attr name=\"defaultaccess\">".$document->getDefaultAccess()."</attr>\n";
			echo $indent." <attr name=\"inheritaccess\">".$document->inheritsAccess()."</attr>\n";
			echo $indent." <attr name=\"sequence\">".$document->getSequence()."</attr>\n";
			if($document->isLocked()) {
				$user = $document->getLockingUser();
				echo $indent." <attr name=\"lockedby\">".$user->getId()."</attr>\n";
			}
			echo $indent." <attr name=\"comment\">".wrapWithCData($document->getComment())."</attr>\n";
			if($attributes = $document->getAttributes()) {
				foreach($attributes as $attribute) {
					$attrdef = $attribute->getAttributeDefinition();
					echo $indent." <attr type=\"user\" attrdef=\"".$attrdef->getID()."\">".wrapWithCData($attribute->getValue())."</attr>\n";
				}
			}

			/* getAccessList() returns also inherited access. So first check
			 * if inheritsAccess is set and don't output any acls in that case.
			 * There could be acls of the folder, which will be visible once the
			 * inheritsAccess is turned off. Those entries will be lost in the
			 * xml output.
			 */
			if(!$document->inheritsAccess()) {
				$accesslist = $document->getAccessList();
				if($accesslist['users'] || $accesslist['groups']) {
				echo $indent." <acls>\n";
				foreach($accesslist['users'] as $acl) {
					echo $indent."  <acl type=\"user\"";
					$user = $acl->getUser();
					echo " user=\"".$user->getID()."\"";
					echo " mode=\"".$acl->getMode()."\"";
					echo "/>\n";
				}
				foreach($accesslist['groups'] as $acl) {
					echo $indent."  <acl type=\"group\"";
					$group = $acl->getGroup();
					echo $indent." group=\"".$group->getID()."\"";
					echo $indent." mode=\"".$acl->getMode()."\"";
					echo "/>\n";
				}
				echo $indent." </acls>\n";
				}
			}

			$cats = $document->getCategories();
			if($cats) {
				echo $indent." <categories>\n";
				foreach($cats as $cat) {
					echo $indent."  <category id=\"".$cat->getId()."\"/>\n";
				}
				echo $indent." </categories>\n";
			}

			$versions = $document->getContent();
			if($versions) {
				echo $indent." <versions>\n";
				foreach($versions as $version) {
					$owner = $version->getUser();
					echo $indent."  <version version=\"".$version->getVersion()."\">\n";
					echo $indent."   <attr name=\"mimetype\">".$version->getMimeType()."</attr>\n";
					echo $indent."   <attr name=\"date\" format=\"Y-m-d H:i:s\">".date('Y-m-d H:i:s', $version->getDate())."</attr>\n";
					echo $indent."   <attr name=\"filetype\">".$version->getFileType()."</attr>\n";
					echo $indent."   <attr name=\"comment\">".wrapWithCData($version->getComment())."</attr>\n";
					echo $indent."   <attr name=\"owner\">".$owner->getId()."</attr>\n";
					echo $indent."   <attr name=\"orgfilename\">".wrapWithCData($version->getOriginalFileName())."</attr>\n";
					echo $indent."   <attr name=\"revisiondate\" format=\"Y-m-d\">".$version->getRevisionDate()."</attr>\n";
					if($attributes = $version->getAttributes()) {
						foreach($attributes as $attribute) {
							$attrdef = $attribute->getAttributeDefinition();
							echo $indent."   <attr type=\"user\" attrdef=\"".$attrdef->getID()."\">".wrapWithCData($attribute->getValue())."</attr>\n";
						}
					}
					if($statuslog = $version->getStatusLog()) {
						echo $indent."   <status id=\"".$statuslog[0]['statusID']."\">\n";
						foreach($statuslog as $entry) {
							echo $indent."    <statuslog>\n";
							echo $indent."     <attr name=\"status\">".$entry['status']."</attr>\n";
							echo $indent."     <attr name=\"comment\">".wrapWithCData($entry['comment'])."</attr>\n";
							echo $indent."     <attr name=\"date\" format=\"Y-m-d H:i:s\">".$entry['date']."</attr>\n";
							echo $indent."     <attr name=\"user\">".$entry['userID']."</attr>\n";
							echo $indent."    </statuslog>\n";
						}
						echo $indent."   </status>\n";
					}
					$approvalStatus = $version->getApprovalStatus(300);
					if($approvalStatus) {
						dumplog($version, 'approval', $approvalStatus, $indent);
					}
					$reviewStatus = $version->getReviewStatus(300);
					if($reviewStatus) {
						dumplog($version, 'review', $reviewStatus, $indent);
					}
					$receiptStatus = $version->getReceiptStatus(300);
					if($receiptStatus) {
						dumplog($version, 'receipt', $receiptStatus, $indent);
					}
					$revisionStatus = $version->getRevisionStatus(300);
					if($revisionStatus) {
						dumplog($version, 'revision', $revisionStatus, $indent);
					}
					$workflow = $version->getWorkflow();
					if($workflow) {
						$workflowstate = $version->getWorkflowState();
						echo $indent."   <workflow id=\"".$workflow->getID()."\" state=\"".$workflowstate->getID()."\"></workflow>\n";
					}
					$wkflogs = $version->getWorkflowLog();
					if($wkflogs) {
						echo $indent."   <workflowlogs>\n";
						foreach($wkflogs as $wklog) {
							echo $indent."    <workflowlog>\n";
							echo $indent."     <attr name=\"date\" format=\"Y-m-d H:i:s\">".$wklog->getDate()."</attr>\n";
							echo $indent."     <attr name=\"workflow\">".$wklog->getWorkflow()->getID()."</attr>\n";
							echo $indent."     <attr name=\"transition\">".$wklog->getTransition()->getID()."</attr>\n";
							$loguser = $wklog->getUser();
							echo $indent."     <attr name=\"user\">".$loguser->getID()."</attr>\n";
							echo $indent."     <attr name=\"comment\">".wrapWithCData($wklog->getComment())."</attr>\n";
							echo $indent."    </workflowlog>\n";
						}
						echo $indent."   </workflowlogs>\n";
					}
					if(file_exists($dms->contentDir . $version->getPath())) {
						echo $indent."   <data length=\"".filesize($dms->contentDir . $version->getPath())."\"";
						if(filesize($dms->contentDir . $version->getPath()) < $maxsize) {
							echo ">\n";
							echo chunk_split(base64_encode(file_get_contents($dms->contentDir . $version->getPath())), 76, "\n");
							echo $indent."   </data>\n";
						} else {
							echo " fileref=\"".$document->getID()."-".$version->getVersion().$version->getFileType()."\" />\n";
							if($contentdir) {
								copy($dms->contentDir . $version->getPath(), $contentdir.$document->getID()."-".$version->getVersion().$version->getFileType());
							} else {
								echo "Warning: version content (size=".filesize($dms->contentDir . $version->getPath()).") will be missing from output\n";
							}
						}
					} else {
						echo $indent."   <!-- ".$dms->contentDir . $version->getPath()." not found -->\n";
						echo $indent."   <data length=\"0\"></data>\n";
					}
					echo $indent."  </version>\n";
				}
				echo $indent." </versions>\n";
			}

			$files = $document->getDocumentFiles();
			if($files) {
				echo $indent." <files>\n";
				foreach($files as $file) {
					$owner = $file->getUser();
					echo $indent."  <file id=\"".$file->getId()."\">\n";
					echo $indent."   <attr name=\"name\">".wrapWithCData($file->getName())."</attr>\n";
					echo $indent."   <attr name=\"mimetype\">".$file->getMimeType()."</attr>\n";
					echo $indent."   <attr name=\"date\" format=\"Y-m-d H:i:s\">".date('Y-m-d H:i:s', $file->getDate())."</attr>\n";
					echo $indent."   <attr name=\"filetype\">".wrapWithCData($file->getFileType())."</attr>\n";
					echo $indent."   <attr name=\"version\">".$file->getVersion()."</attr>\n";
					echo $indent."   <attr name=\"public\">".($file->isPublic() ? 1 : 0)."</attr>\n";
					echo $indent."   <attr name=\"owner\">".$owner->getId()."</attr>\n";
					echo $indent."   <attr name=\"comment\">".wrapWithCData($file->getComment())."</attr>\n";
					echo $indent."   <attr name=\"orgfilename\">".wrapWithCData($file->getOriginalFileName())."</attr>\n";
					if(file_exists($dms->contentDir . $file->getPath())) {
						echo $indent."   <data length=\"".filesize($dms->contentDir . $file->getPath())."\"";
						if(filesize($dms->contentDir . $file->getPath()) < $maxsize) {
							echo ">\n";
							echo chunk_split(base64_encode(file_get_contents($dms->contentDir . $file->getPath())), 76, "\n");
							echo $indent."   </data>\n";
						} else {
							echo " fileref=\"".$document->getID()."-A-".$file->getID().$file->getFileType()."\" />\n";
							if($contentdir) {
								copy($dms->contentDir . $file->getPath(), $contentdir.$document->getID()."-A-".$file->getID().$file->getFileType());
							} else {
								echo "Warning: file content (size=".filesize($dms->contentDir . $file->getPath()).") will be missing from output\n";
							}
						}
					} else {
						echo $indent."   <!-- ".$dms->contentDir . $version->getID()." not found -->\n";
					}
					echo $indent."  </file>\n";
				}
				echo $indent." </files>\n";
			}
			$links = $document->getDocumentLinks();
			if($links) {
				echo $indent." <links>\n";
				foreach($links as $link) {
					$owner = $link->getUser();
					$target = $link->getTarget();
					echo $indent."  <link id=\"".$link->getId()."\">\n";
					echo $indent."   <attr name=\"target\">".$target->getId()."</attr>\n";
					echo $indent."   <attr name=\"owner\">".$owner->getId()."</attr>\n";
					echo $indent."   <attr name=\"public\">".$link->isPublic()."</attr>\n";
					echo $indent."  </link>\n";
				}
				echo $indent." </links>\n";
			}
			$notifications = $document->getNotifyList();
			dumpNotifications($notifications, $indent);

			echo $indent."</document>\n";
			$statistic['documents']++;
		}
	}
	}
} /* }}} */

include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");
include($myincpath."/inc/inc.ClassSettings.php");
include($myincpath."/inc/inc.Acl.php");

if(!$folderid) {
	$folderid = $settings->_rootFolderID;
}

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
echo "<dms dbversion=\"".implode('.', array_slice($dms->getDBVersion(), 1, 3))."\" date=\"".date('Y-m-d H:i:s')."\">\n";

/* Dump roles {{{ */
if(!$sections || in_array('roles', $sections)) {
	/**
	 * Show tree of acos
	 *
	 */
	function aco_tree($aro=null, $aco=null, $indent='') { /* {{{ */
		$tchildren = $aco->getChildren();
		$html = '';
		if($tchildren) {
			foreach($tchildren as $child) {
				if($child->getPermission($aro)) {
					$html .= $indent."<aco id=\"".$child->getID()."\" alias=\"".$child->getAlias()."\">\n";
					$html .= $indent." <attr name=\"permission\">".((int) $child->getPermission($aro))."</attr>\n";
					//$html .= $indent." <attr name=\"alias\">".$child->getAlias()."</attr>\n";
					//$html .= $indent." <attr name=\"aro\">".($aro ? $aro->getID() : 0)."</attr>\n";
					if($thtml = aco_tree($aro, $child, $indent.' ')) {
						$html .= $thtml;
					}
					$html .= $indent."</aco>\n";
				} else {
					if($thtml = aco_tree($aro, $child, $indent.' ')) {
						$html .= $indent."<aco id=\"".$child->getID()."\" alias=\"".$child->getAlias()."\">\n";
						//$html .= $indent." <attr name=\"alias\">".$child->getAlias()."</attr>\n";
						$html .= $thtml;
						$html .= $indent."</aco>\n";
					}
				}
			}
		}
		return $html;
	} /* }}} */

$roles = $dms->getAllRoles();
if($roles) {
	echo "<roles>\n";
	foreach ($roles as $role) {
		echo " <role id=\"".$role->getId()."\">\n";
		echo "  <attr name=\"name\">".wrapWithCData($role->getName())."</attr>\n";
		echo "  <attr name=\"role\">".$role->getRole()."</attr>\n";
		echo "  <attr name=\"noaccess\">".implode(',', $role->getNoAccess())."</attr>\n";
		$aro = SeedDMS_Aro::getInstance($role, $dms);
		if($acos = SeedDMS_Aco::getRoot($dms)) {
			foreach($acos as $aco) {
				echo "  <aco id=\"".$aco->getID()."\" alias=\"".$aco->getAlias()."\">\n";
				if($aco->getPermission($aro)) {
					echo "   <attr name=\"permission\">".((int) $aco->getPermission($aro))."</attr>\n";
//					echo "   <attr name=\"alias\">".$aco->getAlias()."</attr>\n";
//					echo "   <attr name=\"aro\">".($aro ? $aro->getID() : 0)."</attr>\n";
				} else {
//					echo "   <attr name=\"alias\">".$aco->getAlias()."</attr>\n";
				}
				if($html = aco_tree($aro, $aco, '   '))
					echo $html;
				echo "  </aco>\n";
			}
		}
		echo " </role>\n";
		$statistic['roles']++;
	}
	echo "</roles>\n";
}
}
/* }}} */

/* Dump users {{{ */
if(!$sections || in_array('users', $sections)) {
$users = $dms->getAllUsers();
if($users) {
	echo "<users>\n";
	foreach ($users as $user) {
		echo " <user id=\"".$user->getId()."\">\n";
		echo "  <attr name=\"login\">".wrapWithCData($user->getLogin())."</attr>\n";
		echo "  <attr name=\"pwd\">".wrapWithCData($user->getPwd())."</attr>\n";
		echo "  <attr name=\"email\">".wrapWithCData($user->getEmail())."</attr>\n";
		echo "  <attr name=\"fullname\">".wrapWithCData($user->getFullName())."</attr>\n";
		echo "  <attr name=\"comment\">".wrapWithCData($user->getComment())."</attr>\n";
		echo "  <attr name=\"language\">".$user->getLanguage()."</attr>\n";
		echo "  <attr name=\"theme\">".$user->getTheme()."</attr>\n";
		echo "  <attr name=\"role\">".$user->getRole()->getID()."</attr>\n";
		echo "  <attr name=\"hidden\">".$user->isHidden()."</attr>\n";
		echo "  <attr name=\"disabled\">".$user->isDisabled()."</attr>\n";
		echo "  <attr name=\"pwdexpiration\">".$user->getPwdExpiration()."</attr>\n";
		echo "  <attr name=\"homefolder\">".$user->getHomeFolder()."</attr>\n";
		echo "  <attr name=\"secret\">".$user->getSecret()."</attr>\n";
		if($image = $user->getImage()) {
			echo "  <image id=\"".$image['id']."\">\n";
			echo "   <attr name=\"mimetype\">".$image['mimeType']."</attr>\n";
			/* image data is already base64 coded */
			echo "   <data>".$image['image']."</data>\n";
			echo "  </image>\n";
		}
		if($mreviewers = $user->getMandatoryReviewers()) {
			echo "  <mandatory_reviewers>\n";
			foreach($mreviewers as $mreviewer) {
				if((int) $mreviewer['reviewerUserID'])
					echo "   <user id=\"".$mreviewer['reviewerUserID']."\"></user>\n";
				elseif((int) $mreviewer['reviewerGroupID'])
					echo "   <group id=\"".$mreviewer['reviewerGroupID']."\"></group>\n";
			}
			echo "  </mandatory_reviewers>\n";
		}
		if($mapprovers = $user->getMandatoryApprovers()) {
			echo "  <mandatory_approvers>\n";
			foreach($mapprovers as $mapprover) {
				if((int) $mapprover['approverUserID'])
					echo "   <user id=\"".$mapprover['approverUserID']."\"></user>\n";
				elseif((int) $mapprover['approverGroupID'])
					echo "   <group id=\"".$mapprover['approverGroupID']."\"></group>\n";
			}
			echo "  </mandatory_approvers>\n";
		}
		if($substitutes = $user->getSubstitutes()) {
			echo "  <substitutes>\n";
			foreach($substitutes as $substitute) {
				echo "   <substitute user=\"".$substitute->getID()."\" />\n";
			}
			echo "  </substitutes>\n";
		}
		if($mworkflows = $user->getMandatoryWorkflows()) {
			echo "  <mandatory_workflows>\n";
			foreach($mworkflows as $mworkflow) {
					echo "   <workflow id=\"".$mworkflow->getID()."\"></workflow>\n";
			}
			echo "  </mandatory_workflows>\n";
		}
		echo " </user>\n";
		$statistic['users']++;
	}
	echo "</users>\n";
}
}
/* }}} */

/* Dump groups {{{ */
if(!$sections || in_array('groups', $sections)) {
$groups = $dms->getAllGroups();
if($groups) {
	echo "<groups>\n";
	foreach ($groups as $group) {
		echo " <group id=\"".$group->getId()."\">\n";
		echo "  <attr name=\"name\">".wrapWithCData($group->getName())."</attr>\n";
		echo "  <attr name=\"comment\">".wrapWithCData($group->getComment())."</attr>\n";
		$users = $group->getUsers();
		if($users) {
			echo "  <users>\n";
			foreach ($users as $user) {
				echo "   <user user=\"".$user->getId()."\"/>\n";
			}
			echo "  </users>\n";
		}
		echo " </group>\n";
		$statistic['groups']++;
	}
	echo "</groups>\n";
}
}
/* }}} */

/* Dump keywordcategories {{{ */
if(!$sections || in_array('keywordcategories', $sections)) {
$categories = $dms->getAllKeywordCategories();
if($categories) {
	echo "<keywordcategories>\n";
	foreach($categories as $category) {
		$owner = $category->getOwner();
		echo " <keywordcategory id=\"".$category->getId()."\">\n";
		echo "  <attr name=\"name\">".wrapWithCData($category->getName())."</attr>\n";
		echo "  <attr name=\"owner\">".$owner->getId()."</attr>\n";
		if($keywords = $category->getKeywordLists()) {
			echo "  <keywords>\n";
			foreach($keywords as $keyword) {
				echo "   <keyword id=\"".$keyword['id']."\">\n";
				echo "    <attr name=\"name\">".wrapWithCData($keyword['keywords'])."</attr>\n";
				echo "   </keyword>\n";
			}
			echo "  </keywords>\n";
		}
		echo " </keywordcategory>\n";
		$statistic['keywordcategories']++;
	}
	echo "</keywordcategories>\n";
}
}
/* }}} */

/* Dump documentcategories {{{ */
if(!$sections || in_array('documentcategories', $sections)) {
$categories = $dms->getDocumentCategories();
if($categories) {
	echo "<documentcategories>\n";
	foreach($categories as $category) {
		echo " <documentcategory id=\"".$category->getId()."\">\n";
		echo "  <attr name=\"name\">".wrapWithCData($category->getName())."</attr>\n";
		echo " </documentcategory>\n";
		$statistic['documentcategories']++;
	}
	echo "</documentcategories>\n";
}
}
/* }}} */

/* Dump attributedefinition {{{ */
if(!$sections || in_array('attributedefinition', $sections)) {
$attrdefs = $dms->getAllAttributeDefinitions();
if($attrdefs) {
	echo "<attrіbutedefinitions>\n";
	foreach ($attrdefs as $attrdef) {
		echo " <attributedefinition id=\"".$attrdef->getID()."\" objtype=\"";
		switch($attrdef->getObjType()) {
			case SeedDMS_Core_AttributeDefinition::objtype_all:
				echo "all";
				break;
			case SeedDMS_Core_AttributeDefinition::objtype_folder:
				echo "folder";
				break;
			case SeedDMS_Core_AttributeDefinition::objtype_document:
				echo "document";
				break;
			case SeedDMS_Core_AttributeDefinition::objtype_documentcontent:
				echo "documentcontent";
				break;
		}
		echo "\">\n";
		echo "  <attr name=\"name\">".$attrdef->getName()."</attr>\n";
		echo "  <attr name=\"multiple\">".$attrdef->getMultipleValues()."</attr>\n";
		echo "  <attr name=\"valueset\">".wrapWithCData($attrdef->getValueSet())."</attr>\n";
		echo "  <attr name=\"type\">".$attrdef->getType()."</attr>\n";
		echo "  <attr name=\"minvalues\">".$attrdef->getMinValues()."</attr>\n";
		echo "  <attr name=\"maxvalues\">".$attrdef->getMaxValues()."</attr>\n";
		echo "  <attr name=\"regex\">".wrapWithCData($attrdef->getRegex())."</attr>\n";
		echo " </attributedefinition>\n";
		$statistic['attributedefinitions']++;
	}
	echo "</attrіbutedefinitions>\n";
}
}
/* }}} */

/* Dump workflows {{{ */
if(!$sections || in_array('workflows', $sections)) {
$workflowstates = $dms->getAllWorkflowStates();
if($workflowstates) {
	echo "<workflowstates>\n";
	foreach ($workflowstates as $workflowstate) {
		echo " <workflowstate id=\"".$workflowstate->getID()."\">\n";
		echo "  <attr name=\"name\">".$workflowstate->getName()."</attr>\n";
		echo "  <attr name=\"documentstate\">".$workflowstate->getDocumentStatus()."</attr>\n";
		echo " </workflowstate>\n";
		$statistic['workflowstates']++;
	}
	echo "</workflowstates>\n";
}
$workflowactions = $dms->getAllWorkflowActions();
if($workflowactions) {
	echo "<workflowactions>\n";
	foreach ($workflowactions as $workflowaction) {
		echo " <workflowaction id=\"".$workflowaction->getID()."\">\n";
		echo "  <attr name=\"name\">".$workflowaction->getName()."</attr>\n";
		echo " </workflowaction>\n";
		$statistic['workflowactions']++;
	}
	echo "</workflowactions>\n";
}
$workflows = $dms->getAllWorkflows();
if($workflows) {
	echo "<workflows>\n";
	foreach ($workflows as $workflow) {
		echo " <workflow id=\"".$workflow->getID()."\">\n";
		echo "  <attr name=\"name\">".$workflow->getName()."</attr>\n";
		echo "  <attr name=\"initstate\">".$workflow->getInitState()->getID()."</attr>\n";
		echo "  <attr name=\"layoutdata\">".wrapWithCData($workflow->getLayoutData())."</attr>\n";
		if($transitions = $workflow->getTransitions()) {
			echo "  <transitions>\n";
			foreach($transitions as $transition) {
				echo "   <transition id=\"".$transition->getID()."\">\n";
				echo "    <attr name=\"startstate\">".$transition->getState()->getID()."</attr>\n";
				echo "    <attr name=\"nextstate\">".$transition->getNextState()->getID()."</attr>\n";
				echo "    <attr name=\"action\">".$transition->getAction()->getID()."</attr>\n";
				echo "    <attr name=\"maxtime\">".$transition->getMaxTime()."</attr>\n";
				if($transusers = $transition->getUsers()) {
					echo "    <users>\n";
					foreach($transusers as $transuser) {
						echo "     <user id=\"".$transuser->getUser()->getID()."\"></user>\n";
					}
					echo "    </users>\n";
				}
				if($transgroups = $transition->getGroups()) {
					echo "    <groups>\n";
					foreach($transgroups as $transgroup) {
						echo "     <group id=\"".$transgroup->getGroup()->getID()."\" numofusers=\"".$transgroup->getNumOfUsers()."\"></group>\n";
					}
					echo "    </groups>\n";
				}
				echo "   </transition>\n";
			}
			echo "  </transitions>\n";
		}
		echo " </workflow>\n";
		$statistic['workflows']++;
	}
	echo "</workflows>\n";
}
}
/* }}} */

/* Dump folders and documents {{{ */
$folder = $dms->getFolder($folderid);
if($folder) {
	tree($folder, null, '', $skiproot);
}
/* }}} */

/* Dump transmittals {{{ */
if(!$sections || in_array('transmittals', $sections)) {
$transmittals = $dms->getAllTransmittals();
if($transmittals) {
	echo "<transmittals>\n";
	foreach($transmittals as $transmittal) {
		echo " <transmittal id=\"".$transmittal->getID()."\">\n";
		echo "  <attr name=\"name\">".$transmittal->getName()."</attr>\n";
		echo "  <attr name=\"owner\">".$transmittal->getUser()->getID()."</attr>\n";
		echo "  <attr name=\"comment\">".$transmittal->getComment()."</attr>\n";
		if($items = $transmittal->getItems()) {
			echo "  <items>\n";
			foreach($items as $item) {
				echo "   <item id=\"".$item->getID()."\">\n";
				$content = $item->getContent();
				echo "    <attr name=\"document\">".$content->getDocument()->getID()."</attr>\n";
				echo "    <attr name=\"version\">".$content->getVersion()."</attr>\n";
				echo "    <attr name=\"date\" format=\"Y-m-d H:i:s\">".$item->getDate()."</attr>\n";
				echo "   </item>\n";
			}
			echo "  </items>\n";
		}
		echo " </transmittal>\n";
		$statistic['transmittals']++;
	}
	echo "</transmittals>\n";
}
}
/* }}} */

/* Dump statistics {{{ */
echo "<statistics>\n";
echo " <command><![CDATA[".implode(" ", $argv)."]]></command>\n";
foreach($statistic as $type=>$count)
	echo " <".$type.">".$count."</".$type.">\n";
echo "</statistics>\n";
/* }}} */

echo "</dms>\n";
?>
