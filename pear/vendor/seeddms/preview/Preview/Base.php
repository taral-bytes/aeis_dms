<?php
/**
 * Implementation of preview base
 *
 * @category   DMS
 * @package    SeedDMS_Preview
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class for managing creation of preview images for documents.
 *
 * @category   DMS
 * @package    SeedDMS_Preview
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Preview_Base {
	/**
	 * @var string $cacheDir location in the file system where all the
	 *      cached data like thumbnails are located. This should be an
	 *      absolute path.
	 * @access public
	 */
	public $previewDir;

	/**
	 * @var array $converters list of mimetypes and commands for converting
	 * file into preview image
	 * @access protected
	 */
	protected $converters;

	/**
	 * @var object $conversionmgr 
	 * @access protected
	 */
	protected $conversionmgr;

	/**
	 * @var integer $timeout maximum time for execution of external commands
	 * @access protected
	 */
	protected $timeout;

	/**
	 * @var boolean $xsendfile set to true if mod_xѕendfile is to be used
	 * @access protected
	 */
	protected $xsendfile;

	/**
	 * @var string $lastpreviewfile will be set to the file name of the last preview
	 * @access protected
	 */
	protected $lastpreviewfile;

	function __construct($previewDir, $timeout=5, $xsendfile=true) { /* {{{ */
		if(!is_dir($previewDir)) {
			if (!SeedDMS_Core_File::makeDir($previewDir)) {
				$this->previewDir = '';
			} else {
				$this->previewDir = $previewDir;
			}
		} else {
			$this->previewDir = $previewDir;
		}
		$this->timeout = intval($timeout);
		$this->converters = array();
		$this->xsendfile = $xsendfile;
		$this->conversionmgr = null;
	} /* }}} */

	/**
	 * Run a shell command
	 *
	 * @param $cmd
	 * @param int $timeout
	 * @return array
	 * @throws Exception
	 */
	static function execWithTimeout($cmd, $timeout=5) { /* {{{ */
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
	 * Get preview dir
	 *
	 * @return string name of preview directory on disc
	 */
	public function getPreviewDir() { /* {{{ */
		return $this->previewDir;
	} /* }}} */

	/**
	 * Set a list of converters
	 *
	 * Merges the list of passed converters with the already existing ones.
	 * Existing converters will be overwritten.
	 *
	 * @param array list of converters. The key of the array contains the mimetype
	 * and the value is the command to be called for creating the preview
	 */
	function setConverters($arr) { /* {{{ */
		if(is_array($arr))
			$this->converters = $arr;
		else
			$this->converters = array();
	} /* }}} */

	/**
	 * Set conversion service manager
	 *
	 * A conversion manager is a service for converting files from one format
	 * to another format.
	 *
	 * @param object $conversionmgr
	 */
	function setConversionMgr($conversionmgr) { /* {{{ */
		$this->conversionmgr = $conversionmgr;
	} /* }}} */

	/**
	 * Enable/Disable xsendfile extension
	 *
	 * Merges the list of passed converters with the already existing ones.
	 * Existing converters will be overwritten.
	 *
	 * @param boolean $xsendfile turn on/off use of xsendfile module in apache
	 */
	function setXsendfile($xsendfile) { /* {{{ */
		$this->xsendfile = $xsendfile;
	} /* }}} */

	/**
	 * Add a list of converters
	 *
	 * Merges the list of passed converters with the already existing ones.
	 * Existing converters will be overwritten.
	 *
	 * @param array list of converters. The key of the array contains the mimetype
	 * and the value is the command to be called for creating the preview
	 */
	function addConverters($arr) { /* {{{ */
		$this->converters = array_merge($this->converters, $arr);
	} /* }}} */

	/**
	 * Check if converter for a given mimetype is set
	 *
	 * @param string $from from mimetype
	 * @param string $to   to mimetype
	 *
	 * @return boolean true if converter exists, otherwise false
	 */
	function hasConverter($from, $to='') { /* {{{ */
		if($this->conversionmgr)
			return $this->conversionmgr->hasService($from, $to);
		else
			return array_key_exists($from, $this->converters) && $this->converters[$from];
	} /* }}} */

	/**
	 * Send a file from disk to the browser
	 *
	 * This function uses either readfile() or the xѕendfile apache module if
	 * it is installed.
	 *
	 * @param string $filename
	 */
	protected function sendFile($filename) { /* {{{ */
		if($this->xsendfile && function_exists('apache_get_modules') && in_array('mod_xsendfile',apache_get_modules())) {
			header("X-Sendfile: ".$filename);
		} else {
			$size = filesize($filename);
			header("Content-Length: " . $size);
			/* Make sure output buffering is off */
			if (ob_get_level()) {
				ob_end_clean();
			}
			readfile($filename);
		}
	} /* }}} */

	/**
	 * Return path of last created preview file
	 *
	 * @return string
	 */
	public function getPreviewFile() { /* {{{ */
		return $this->lastpreviewfile;
	} /* }}} */
}

