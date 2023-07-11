<?php
/**
 * Implementation of conversion service exec class
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once("inc/inc.ClassConversionServiceBase.php");

/**
 * Implementation of conversion service exec class
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_ConversionServiceExec extends SeedDMS_ConversionServiceBase {
	/**
	 * shell cmd
	 */
	public $cmd;

	/**
	 * timeout
	 */
	public $timeout;

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

		$orgtimeout = $timeout;
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
			throw new Exception("command timeout after ".$orgtimeout." secs on: " . $cmd);
		} else {
			$return_value = proc_close($process);
			return array('stdout'=>$output, 'stderr'=>$error, 'return'=>$return_value);
		}
	} /* }}} */

	public function __construct($from, $to, $cmd, $timeout=5) {
		parent::__construct();
		$this->from = $from;
		$this->to = $to;
		$this->cmd = $cmd;
		$this->timeout = ((int) $timeout) ? (int) $timeout : 5;
	}

	public function getInfo() {
		return "Convert with command '".$this->cmd."'";
	}

	public function getAdditionalParams() { /* {{{ */
		/* output format is an image and the command has a placeholder for the
		 * width of the converted image, then allow to set the width.
		 */
		if(substr($this->to, 0, 6) == 'image/' && strpos($this->cmd, '%w') !== false)
			return [
				['name'=>'width', 'type'=>'number', 'description'=>'Width of converted image'],
			];
		return [];
	} /* }}} */

	/**
	 * Convert by running an external command
	 *
	 * The command was set when calling the constructor. The command may
	 * either write a file or to stdout, depending on the placeholder '%o'
	 * either exists or not in the command. If $target is null, but the
	 * command writes to a file, it will create a temporary file, write
	 * ot it and read the content back to be returned by the method.
	 */
	public function convert($infile, $target = null, $params = array()) {
		/* if no %f placeholder is found, we assume output to stdout */
		$tostdout = strpos($this->cmd, '%o') === false;

		$format = '';
		switch($this->to) {
		case 'image/gif':
			$format = 'gif';
			break;
		case 'image/jpg':
		case 'image/jpeg':
			$format = 'jpg';
			break;
		case 'image/png':
			$format = 'png';
			break;
		case 'application/pdf':
			$format = 'pdf';
			break;
		}
		$start = microtime(true);
		$hastempfile = false;
		if(!$target && !$tostdout) {
			$tmpfile = tempnam(sys_get_temp_dir(), 'convert');
			/* Some programms (e.g. unoconv) need the output file to have the
			 * right extension. Without an extension it will add one by itself.
			 */
			if($format)
				rename($tmpfile, $tmpfile .= '.'.$format);
			$hastempfile = true;
		} else
			$tmpfile = $target;
		/* %s was just added because the commands to convert to text/plain used
		 * it instead of %f for the input file
		 * %f = input file
		 * %o = output file
		 * %m = mime type
		 * %w = width
		 */
		$cmd = str_replace(array('%w', '%f', '%s', '%if', '%o', '%m'), array(!empty($params['width']) ? (int) $params['width'] : '150', $infile, $infile, $format, $tmpfile, $this->from), $this->cmd);
		try {
			$ret = self::execWithTimeout($cmd, $this->timeout);
		} catch(Exception $e) {
			if($hastempfile)
				unlink($tmpfile);
			$this->success = false;
			if($this->logger) {
				$this->logger->log('Conversion from '.$this->from.' to '.$this->to.' with cmd "'.$this->cmd.'" failed: '.$e->getMessage(), PEAR_LOG_ERR);
			}
			return false;
		}
		$end = microtime(true);
		if($this->logger) {
			$this->logger->log('Conversion from '.$this->from.' to '.$this->to.' with cmd "'.$this->cmd.'" took '.($end-$start).' sec.', PEAR_LOG_DEBUG);
		}
		if($tostdout) {
			if(!$target) {
				return $ret['stdout'];
			} else {
				return file_put_contents($tmpfile, $ret['stdout']);
			}
		} else {
			if(!$target) {
				$content = file_get_contents($tmpfile);
				unlink($tmpfile);
				return $content;
			} else {
				return true;
			}
		}
	}
}

