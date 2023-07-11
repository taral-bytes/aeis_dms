<?php
/**
 * Implementation of an indexed document
 *
 * @category   DMS
 * @package    SeedDMS_SQLiteFTS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * @uses SeedDMS_SQLiteFTS_Document
 */
require_once('Document.php');
require_once('Field.php');


/**
 * Class for managing an indexed document.
 *
 * @category   DMS
 * @package    SeedDMS_SQLiteFTS
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_SQLiteFTS_IndexedDocument extends SeedDMS_SQLiteFTS_Document {

	/**
	 * @var string
	 */
	protected $errormsg;

	/**
	 * @var string
	 */
	protected $mimetype;

	/**
	 * @var string
	 */
	protected $cmd;

	/**
	 * Run a shell command
	 *
	 * @param $cmd
	 * @param int $timeout
	 * @return array
	 * @throws Exception
	 */
	static function execWithTimeout($cmd, $timeout=2) { /* {{{ */
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);
		$pipes = array();

		$timeout += time();
		// Putting an 'exec' before the command will not fork the command
		// and therefore not create any child process. proc_terminate will
		// then reliably terminate the cmd and not just shell. See notes of
		// https://www.php.net/manual/de/function.proc-terminate.php
		$process = proc_open('exec '.$cmd, $descriptorspec, $pipes);
		if (!is_resource($process)) {
			throw new Exception("proc_open failed on: " . $cmd);
		}
		stream_set_blocking($pipes[1], 0);
		stream_set_blocking($pipes[2], 0);
			 
		$output = $error = '';
		$timeleft = $timeout - time();
		$read = array($pipes[1], $pipes[2]);
		$write = NULL;
		$exeptions = NULL;
		do {
			$num_changed_streams = stream_select($read, $write, $exeptions, $timeleft, 200000);

			if ($num_changed_streams === false) {
				proc_terminate($process);
				throw new Exception("stream select failed on: " . $cmd);
			} elseif ($num_changed_streams > 0) {
				$output .= fread($pipes[1], 8192);
				$error .= fread($pipes[2], 8192);
			}
			$timeleft = $timeout - time();
		} while (!feof($pipes[1]) && $timeleft > 0);
 
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		if ($timeleft <= 0) {
			proc_terminate($process);
			throw new Exception("command timeout on: " . $cmd);
		} else {
			$return_value = proc_close($process);
			return array('stdout'=>$output, 'stderr'=>$error, 'return'=>$return_value);
		}
	} /* }}} */

	/**
	 * Constructor. Creates our indexable document and adds all
	 * necessary fields to it using the passed in document
	 *
	 * $convcmd can either be an array of conversion commands or
	 * an object of class SeedDMS_ConversionMgr
	 */
	public function __construct($dms, $document, $convcmd=null, $nocontent=false, $timeout=5) { /* {{{ */
		$this->errormsg = '';
		$this->cmd = '';
		$this->mimetype = '';

		$this->addField(SeedDMS_SQLiteFTS_Field::Text('title', $document->getName()));
		if($acllist = $document->getReadAccessList(1, 1, 1)) {
			$allu = [];
			foreach($acllist['users'] as $u)
				$allu[] = $u->getLogin();
			$this->addField(SeedDMS_SQLiteFTS_Field::Text('user', implode(' ', $allu)));
		}
		if($attributes = $document->getAttributes()) {
			foreach($attributes as $attribute) {
				$attrdef = $attribute->getAttributeDefinition();
				$attrdefname = 'attr_'.$attrdef->getId();
				switch($attrdef->getType()) {
				case SeedDMS_Core_AttributeDefinition::type_date:
					$this->addField(SeedDMS_SQLiteFTS_Field::Keyword($attrdefname, strtotime($attribute->getValue())));
					break;
				default:
				if($attrdef->getValueSet() != '')
					$this->addField(SeedDMS_SQLiteFTS_Field::Keyword($attrdefname, $attribute->getValue()));
				else
					$this->addField(SeedDMS_SQLiteFTS_Field::Text($attrdefname, $attribute->getValue()));
				}
			}
		}
		$owner = $document->getOwner();
		$this->addField(SeedDMS_SQLiteFTS_Field::Text('owner', $owner->getLogin()));
		$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('path', str_replace(':', 'x', $document->getFolderList())));
		if($comment = $document->getComment()) {
			$this->addField(SeedDMS_SQLiteFTS_Field::Text('comment', $comment));
		}

		if($document->isType('document')) {
			$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('document_id', 'D'.$document->getID()));
			$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('record_type', 'document'));
			/*
			if($attributes = $document->getAttributes()) {
				foreach($attributes as $attribute) {
					$attrdef = $attribute->getAttributeDefinition();
					$attrdefname = 'attr_'.$attrdef->getId(); //preg_replace('/[^a-z0-9_]/', '', strtolower($attrdef->getName()));
					switch($attrdef->getType()) {
					case SeedDMS_Core_AttributeDefinition::type_date:
						$this->addField(SeedDMS_SQLiteFTS_Field::Keyword($attrdefname, strtotime($attribute->getValue())));
						break;
					default:
					if($attrdef->getValueSet() != '')
						$this->addField(SeedDMS_SQLiteFTS_Field::Keyword($attrdefname, $attribute->getValue()));
					else
						$this->addField(SeedDMS_SQLiteFTS_Field::Text($attrdefname, $attribute->getValue()));
					}
				}
			}
			 */

			$version = $document->getLatestContent();
			if($version) {
				$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('mimetype', $version->getMimeType()));
				$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('origfilename', $version->getOriginalFileName()));
				$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('created', $document->getDate(), 'unindexed'));
				$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('modified', $version->getDate(), 'unindexed'));
				if(!$nocontent)
					$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('indexed', time(), 'unindexed'));
				if($attributes = $version->getAttributes()) {
					foreach($attributes as $attribute) {
						$attrdef = $attribute->getAttributeDefinition();
						$attrdefname = 'attr_'.$attrdef->getId(); //preg_replace('/[^a-z0-9_]/', '', strtolower($attrdef->getName()));
						switch($attrdef->getType()) {
						case SeedDMS_Core_AttributeDefinition::type_date:
							$this->addField(SeedDMS_SQLiteFTS_Field::Keyword($attrdefname, strtotime($attribute->getValue())));
							break;
						default:
						if($attrdef->getValueSet() != '')
							$this->addField(SeedDMS_SQLiteFTS_Field::Keyword($attrdefname, $attribute->getValue()));
						else
							$this->addField(SeedDMS_SQLiteFTS_Field::Text($attrdefname, $attribute->getValue()));
						}
					}
				}
			}
			if($categories = $document->getCategories()) {
				$names = array();
				foreach($categories as $cat) {
					$names[] = $cat->getName();
				}
				$this->addField(SeedDMS_SQLiteFTS_Field::Text('category', implode('#', $names)));
			}
			if($keywords = $document->getKeywords()) {
				$this->addField(SeedDMS_SQLiteFTS_Field::Text('keywords', $keywords));
			}
			if($version) {
				$status = $version->getStatus();
				$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('status', $status['status']+10));
			}
			if($version && !$nocontent) {
				$path = $dms->contentDir . $version->getPath();
				if(file_exists($path)) {
					$mimetype = $version->getMimeType();
					$this->mimetype = $mimetype;
					if(is_callable($convcmd)) {
						$result = $convcmd($document);
						if($result['content']) {
							self::setContent($result['content']);
						} elseif($result['content'] === false) {
							$this->errormsg = $result['errormsg'];
						}
						$this->cmd = $result['cmd'];
					} elseif(is_object($convcmd) && (get_class($convcmd) == 'SeedDMS_ConversionMgr')) {
						if($service = $convcmd->getService($mimetype, 'text/plain')) {
							$content = $convcmd->convert($path, $mimetype, 'text/plain');
							if($content) {
								self::setContent($content);
							} elseif($content === false) {
								$this->errormsg = 'Conversion failed';
							}
							$this->cmd = get_class($service);
						} else {
							$this->cmd = 'No service to convert '.$mimetype.' to text/plain';
						}
					} else {
						$content = '';
						$cmd = '';
						$mimeparts = explode('/', $mimetype, 2);
						if(isset($convcmd[$mimetype])) {
							$cmd = sprintf($convcmd[$mimetype], $path);
						} elseif(isset($convcmd[$mimeparts[0].'/*'])) {
							$cmd = sprintf($convcmd[$mimetype], $path);
						} elseif(isset($convcmd['*'])) {
							$cmd = sprintf($convcmd[$mimetype], $path);
						}
						if($cmd) {
							$this->cmd = $cmd;
							try {
								$content = self::execWithTimeout($cmd, $timeout);
								if($content['stdout']) {
									self::setContent($content['stdout']);
	//								$this->addField(SeedDMS_SQLiteFTS_Field::UnStored('content', $content['stdout']));
								}
								if($content['stderr']) {
									$this->errormsg = $content['stderr'];
								}
							} catch (Exception $e) {
							}
						}
					}
				}
			}
		} elseif($document->isType('folder')) {
			$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('document_id', 'F'.$document->getID()));
			$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('record_type', 'folder'));
			$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('created', $document->getDate(), 'unindexed'));
			$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('modified', $document->getDate(), 'unindexed'));
			$this->addField(SeedDMS_SQLiteFTS_Field::Keyword('indexed', time(), 'unindexed'));
		}
	} /* }}} */

	public function getErrorMsg() { /* {{{ */
		return $this->errormsg;
	} /* }}} */

	public function getMimeType() { /* {{{ */
		return $this->mimetype;
	} /* }}} */

	public function setContent($data) { /* {{{ */
		$this->addField(SeedDMS_SQLiteFTS_Field::Text('content', $data));
	} /* }}} */

	public function getCmd() { /* {{{ */
		return $this->cmd;
	} /* }}} */

	/* Use only for setting the command if e.g. an extension takes over the
	 * conversion to txt (like the office extension which uses the collabora
	 * conversion service).
	 */
	public function setCmd($cmd) { /* {{{ */
		$this->cmd = $cmd;
	} /* }}} */
}
?>
