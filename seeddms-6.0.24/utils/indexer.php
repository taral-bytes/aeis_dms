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
	echo "  seeddms-indexer [-h] [-v] [-c] [--config <file>]".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  This program recreates or updates the full text index of SeedDMS.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  -c: recreate index.".PHP_EOL;
	echo "  --no-log: do not log.".PHP_EOL;
	echo "  --config: set alternative config file.".PHP_EOL;
} /* }}} */

$version = "0.0.3";
$shortoptions = "hvc";
$longoptions = array('help', 'version', 'config:', 'no-log');
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

$config['log'] = true;
$config['verbosity'] = 3;
$config['stats'] = true;

/* Set alternative config file */
if(isset($options['config'])) {
	define('SEEDDMS_CONFIG_FILE', $options['config']);
} elseif(isset($_SERVER['SEEDDMS_CONFIG_FILE'])) {
	define('SEEDDMS_CONFIG_FILE', $_SERVER['SEEDDMS_CONFIG_FILE']);
}

/* recreate index */
$recreate = false;
if(isset($options['c'])) {
	$recreate = true;
}

include($myincpath."/inc/inc.Settings.php");
if(empty($options['no-log'])) {
	$config['log'] = false;
	include($myincpath."/inc/inc.LogInit.php");
}
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");

function tree($dms, $fulltextservice, $folder, $indent='', $numdocs) { /* {{{ */
	global $settings, $themes, $config, $stats;

	$index = $fulltextservice->Indexer();
	$lucenesearch = $fulltextservice->Search();

	$prefix = $themes->black(($config['verbosity'] >= 3 ? $indent : '')."D ".$folder->getId().":".$folder->getName()." ");
	if(($numdocs == 0) || !($hit = $lucenesearch->getFolder($folder->getId()))) {
		try {
			$idoc = $fulltextservice->IndexedDocument($folder, true);
			if(isset($GLOBALS['SEEDDMS_HOOKS']['indexFolder'])) {
				foreach($GLOBALS['SEEDDMS_HOOKS']['indexFolder'] as $hookObj) {
					if (method_exists($hookObj, 'preIndexFolder')) {
						$hookObj->preIndexDocument(null, $folder, $idoc);
					}
				}
			}
			if($index->addDocument($idoc)) {
				echo $prefix.$themes->green(" (Folder added)").PHP_EOL;
				$stats['folder']['add']++;
			} else {
				$stats['folder']['error']++;
				echo $prefix.$themes->error(" (Failed)").PHP_EOL;
			}
		} catch(Exception $e) {
			$stats['folder']['error']++;
			echo $prefix.$themes->error(" (Timeout)").PHP_EOL;
		}
	} else {
		try {
			$indexed = (int) $hit->getDocument()->getFieldValue('indexed');
		} catch (Exception $e) {
			$indexed = 0;
		}
		if($indexed >= $folder->getDate()) {
			if($config['verbosity'] >= 3)
				echo $prefix.$themes->italic(" (Folder unchanged)").PHP_EOL;
			$stats['folder']['unchanged']++;
		} else {
			$index->delete($hit->id);
			try {
				$idoc = $fulltextservice->IndexedDocument($folder, true);
				if(isset($GLOBALS['SEEDDMS_HOOKS']['indexDocument'])) {
					foreach($GLOBALS['SEEDDMS_HOOKS']['indexDocument'] as $hookObj) {
						if (method_exists($hookObj, 'preIndexDocument')) {
							$hookObj->preIndexDocument(null, $folder, $idoc);
						}
					}
				}
				if($index->addDocument($idoc)) {
					echo $prefix.$themes->green(" (Folder updated)").PHP_EOL;
					$stats['folder']['update']++;
				} else {
					$stats['folder']['error']++;
					echo $prefix.$themes->error(" (Failed)").PHP_EOL;
				}
			} catch(Exception $e) {
				$stats['folder']['error']++;
				echo $prefix.$themes->error(" (Timeout)").PHP_EOL;
			}
		}
	}

	$subfolders = $folder->getSubFolders();
	foreach($subfolders as $subfolder) {
		tree($dms, $fulltextservice, $subfolder, $indent.'  ', $numdocs);
	}

	$documents = $folder->getDocuments();
	foreach($documents as $document) {
		$prefix = $themes->black(($config['verbosity'] >= 3 ? $indent : '')."  ".$document->getId().":".$document->getName()." ");
		if(($numdocs == 0) || !($hit = $lucenesearch->getDocument($document->getId()))) {
			try {
				$idoc = $fulltextservice->IndexedDocument($document, true);
				if(isset($GLOBALS['SEEDDMS_HOOKS']['indexDocument'])) {
					foreach($GLOBALS['SEEDDMS_HOOKS']['indexDocument'] as $hookObj) {
						if (method_exists($hookObj, 'preIndexDocument')) {
							$hookObj->preIndexDocument(null, $document, $idoc);
						}
					}
				}
				if($index->addDocument($idoc)) {
					echo $prefix.$themes->green(" (Document added)").PHP_EOL;
					$stats['document']['add']++;
				} else {
					$stats['document']['error']++;
					echo $prefix.$themes->error(" (Failed)").PHP_EOL;
				}
			} catch(Exception $e) {
				$stats['document']['error']++;
				echo $prefix.$themes->error(" (Timeout)").PHP_EOL;
			}
		} else {
			try {
				$indexed = (int) $hit->getDocument()->getFieldValue('indexed');
			} catch (Exception $e) {
				$indexed = 0;
			}
			$content = $document->getLatestContent();
			if($indexed >= $content->getDate()) {
				if($config['verbosity'] >= 3)
					echo $prefix.$themes->italic(" (Document unchanged)").PHP_EOL;
				$stats['document']['unchanged']++;
			} else {
				$index->delete($hit->id);
				try {
					$idoc = $fulltextservice->IndexedDocument($document, true);
					if(isset($GLOBALS['SEEDDMS_HOOKS']['indexDocument'])) {
						foreach($GLOBALS['SEEDDMS_HOOKS']['indexDocument'] as $hookObj) {
							if (method_exists($hookObj, 'preIndexDocument')) {
								$hookObj->preIndexDocument(null, $document, $idoc);
							}
						}
					}
					if($index->addDocument($idoc)) {
						echo $prefix.$themes->green(" (Document updated)").PHP_EOL;
						$stats['document']['update']++;
					} else {
						$stats['document']['error']++;
						echo $prefix.$themes->error(" (Failed)").PHP_EOL;
					}
				} catch(Exception $e) {
					$stats['document']['error']++;
					echo $prefix.$themes->error(" (Timeout)").PHP_EOL;
				}
			}
		}
	}
} /* }}} */

$themes = new \AlecRabbit\ConsoleColour\Themes();

$index = $fulltextservice->Indexer($recreate);
if(!$index) {
	echo $themes->error("Could not create index.").PHP_EOL;
	exit(1);
}

$stats['folder']['add'] = 0;
$stats['folder']['unchanged'] = 0;
$stats['folder']['update'] = 0;
$stats['folder']['error'] = 0;
$stats['document']['add'] = 0;
$stats['document']['unchanged'] = 0;
$stats['document']['update'] = 0;
$stats['document']['error'] = 0;
$stats['time']['total'] = time();
$numdocs = $fulltextservice->Indexer()->count();
$dms->usecache = true;
$folder = $dms->getFolder($settings->_rootFolderID);
/* if numdocs is 0, then there is no need to check if a document/folder is already
 * indexed. That speeds up the indexing.
 */
tree($dms, $fulltextservice, $folder,'', $numdocs);

$index->commit();
$index->optimize();
$stats['time']['total'] = time()-$stats['time']['total'];

echo PHP_EOL;
echo $themes->black("Total Time: ".$stats['time']['total'].' sec.').PHP_EOL;
echo $themes->black("Documents").PHP_EOL;
echo $themes->black("  added: ".$stats['document']['add']).PHP_EOL;
echo $themes->black("  updated: ".$stats['document']['update']).PHP_EOL;
echo $themes->black("  unchanged: ".$stats['document']['unchanged']).PHP_EOL;
echo $themes->black("  error: ".$stats['document']['error']).PHP_EOL;
echo $themes->black("Folders").PHP_EOL;
echo $themes->black("  added: ".$stats['folder']['add']).PHP_EOL;
echo $themes->black("  updated: ".$stats['folder']['update']).PHP_EOL;
echo $themes->black("  unchanged: ".$stats['folder']['unchanged']).PHP_EOL;
echo $themes->black("  error: ".$stats['folder']['error']).PHP_EOL;
