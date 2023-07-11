<?php
/**
 * Implementation of an extension management.
 *
 * SeedDMS can be extended by extensions. Extension usually implement
 * hook.
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2011 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent an extension manager
 *
 * This class provides some very basic methods to manage extensions.
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2011 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Extension_Mgr {
	/**
	 * @var string $extdir directory where extensions are located
	 * @access protected
	 */
	protected $extdir;

	/**
	 * @var string $reposurl url for fetching list of extensions in repository
	 * @access protected
	 */
	protected $reposurl;

	/**
	 * @var array[] $extconf configuration of all extensions
	 * @access protected
	 */
	protected $extconf;

	/**
	 * @var string $cachedir directory where cached extension configuration
	 *      is stored
	 * @access protected
	 */
	protected $cachedir;

	/**
	 * @var array $configcache cached result of checkExtensionByName()
	 * @access protected
	 */
	protected $configcache;

	/**
	 * @var string[] $errmsg list of error message from last operation
	 * @access protected
	 */
	protected $errmsgs;

	/*
	 * Name of json file containg available extension from repository
	 */
	const repos_list_file = 'repository.json';

	/**
	 * Compare two version
	 *
	 * This functions compares two version in the format x.x.x with x being
	 * an integer
	 *
	 * @param string $ver1
	 * @param string $ver2
	 * @return int -1 if $ver1 < $ver2, 0 if $ver1 == $ver2, 1 if $ver1 > $ver2
	 */
	static public function cmpVersion($ver1, $ver2) { /* {{{ */
		$tmp1 = explode('.', $ver1);
		$tmp2 = explode('.', $ver2);
		if(intval($tmp1[0]) < intval($tmp2[0])) {
			return -1;
		} elseif(intval($tmp1[0]) > intval($tmp2[0])) {
			return 1;
		} else {
			if(intval($tmp1[1]) < intval($tmp2[1])) {
				return -1;
			} elseif(intval($tmp1[1]) > intval($tmp2[1])) {
				return 1;
			} else {
				if(intval($tmp1[2]) < intval($tmp2[2])) {
					return -1;
				} elseif(intval($tmp1[2]) > intval($tmp2[2])) {
					return 1;
				} else {
					return 0;
				}
			}
		}
	} /* }}} */

	/**
	 * Constructor of extension manager
	 *
	 * Reads the configuration of all extensions and creates the
	 * configuration file if it does not exist and the extension dir
	 * is given
	 */
	public function __construct($extdir = '', $cachedir = '', $reposurl = '', $proxyurl='', $proxyuser='', $proxypass='') { /* {{{ */
		$this->configcache = [];
		$this->cachedir = $cachedir;
		$this->extdir = $extdir;
		$this->reposurl = $reposurl;
		$this->proxyurl = $proxyurl;
		$this->proxyuser = $proxyuser;
		$this->proxypass = $proxypass;
		$this->extconf = array();
		if($extdir) {
			if(!file_exists($this->getExtensionsConfFile())) {
				$this->createExtensionConf();
			}
			if(@include($this->getExtensionsConfFile())) {
				if(!empty($EXT_CONF)) {
					$this->extconf = $EXT_CONF;
				}
			}
		}
	} /* }}} */

	public function getRepositoryUrl() { /* {{{ */
		return $this->reposurl;
	} /* }}} */

	private function getStreamContext() { /* {{{ */
		if(!$this->proxyurl)
			return null;
		$url = parse_url($this->proxyurl);
		$opts = [
			$url['scheme'] => [
				'proxy' => 'tcp://'.$url['host'].($url['port'] ? ':'.$url['port'] : ''),
				'request_fulluri' => true,
			]
		];
		if($this->proxyuser && $this->proxypass) {
			$auth = base64_encode($this->proxyurl.':'.$this->proxypass);
			$opts[$url['scheme']] = [
				'header' => [
					'Proxy-Authorization: Basic '.$auth
				]
			];
		}

		$context = stream_context_create($opts);
		return $context;
	} /* }}} */

	protected function getExtensionsConfFile() { /* {{{ */
		return $this->cachedir."/extensions.php";
	} /* }}} */

	/**
	 * Get the configuration of extensions
	 *
	 * @return array[]
	 */
	public function getExtensionConfiguration() { /* {{{ */
		return $this->extconf;
	} /* }}} */

	/**
	 * Check if extension directory is writable
	 *
	 * @return boolean
	 */
	public function isWritableExtDir() { /* {{{ */
		return is_writable($this->extdir);
	} /* }}} */

	/**
	 * Create the cached file containing extension information
	 *
	 * This function will always create a file, even if no extensions
	 * are installed.
	 *
	 * @return boolean true on success, false on error
	 */
	public function createExtensionConf() { /* {{{ */
		$extensions = self::getExtensions();
		$fp = @fopen(self::getExtensionsConfFile(), "w");
		if($fp) {
			if($extensions) {
				foreach($extensions as $_ext) {
					if(file_exists($this->extdir . "/" . $_ext . "/conf.php")) {
						$content = file_get_contents($this->extdir . "/" . $_ext . "/conf.php");
						fwrite($fp, $content);
					}
				}
			}
			fclose($fp);
			return true;
		} else {
			return false;
		}
	} /* }}} */

	/**
	 * Get names of locally installed extensions by scanning the extension dir
	 *
	 * @return string[] list of extension names
	 */
	protected function getExtensions() { /* {{{ */
		$extensions = array();
		if(file_exists($this->extdir)) {
			$handle = opendir($this->extdir);
			while ($entry = readdir($handle) ) {
				if ($entry == ".." || $entry == ".")
					continue;
				else if (is_dir($this->extdir ."/". $entry))
					array_push($extensions, $entry);
			}
			closedir($handle);

			asort($extensions);
		}
		return $extensions;
	} /* }}} */

	static protected function Zip($source, $destination, $include_dir = false) { /* {{{ */

		if (!extension_loaded('zip') || !file_exists($source)) {
			return false;
		}

		if (file_exists($destination)) {
			unlink ($destination);
		}

		$zip = new ZipArchive();
		if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
			return false;
		}
		$source = str_replace('\\', '/', realpath($source));

		if (is_dir($source) === true) {
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

			if ($include_dir) {
				$arr = explode("/",$source);
				$maindir = $arr[count($arr)- 1];

				$source = "";
				for ($i=0; $i < count($arr) - 1; $i++) { 
					$source .= '/' . $arr[$i];
				}

				$source = substr($source, 1);

				$zip->addEmptyDir($maindir);
			}

			foreach ($files as $file) {
				$file = str_replace('\\', '/', $file);

				// Ignore "." and ".." folders
				if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
					continue;
				// Ignore all files and directories starting with a '.'
				// but keep an .htaccess file
				if( !preg_match('#\\.htaccess$#', $file) && preg_match('#/\\.#', $file) )
					continue;

				$file = realpath($file);

				if (is_dir($file) === true) {
					$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
				} else if (is_file($file) === true) {
					$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
				}
			}
		} else if (is_file($source) === true) {
			$zip->addFromString(basename($source), file_get_contents($source));
		}

		return $zip->close();
	} /* }}} */

	/**
	 * Create zip archive of an extension
	 *
	 * @param string $extname name of extension
	 * @param string $version version of extension (x.y.z)
	 * @return string name of temporary file with archive
	 */
	public function createArchive($extname, $version) { /* {{{ */
		if(!is_dir($this->extdir ."/". $extname))
			return false;

		$tmpfile = $this->cachedir."/".$extname."-".$version.".zip";

		if(!SeedDMS_Extension_Mgr::Zip($this->extdir."/".$extname, $tmpfile)) {
			return false;
		}
//		$cmd = "cd ".$this->extdir."/".$extname."; zip -r ".$tmpfile." .";
//		exec($cmd);

		return $tmpfile;
	} /* }}} */

	/**
	 * Migrate database tables of extension if one exists
	 *
	 * @param string $extname name of extension
	 * @param SeedDMS_Core_DMS $dms
	 * @return boolean true on success, false on error
	 */
	public function migrate($extname, $settings, $dms, $logger) { /* {{{ */
		if(!isset($this->extconf[$extname]))
			return false;
		$extconf = $this->extconf[$extname];
		$ret = null;
		if(isset($extconf['class']) && isset($extconf['class']['file']) && isset($extconf['class']['name'])) {
			$classfile = $settings->_rootDir."/ext/".$extname."/".$extconf['class']['file'];
			if(file_exists($classfile)) {
				require_once($classfile);
				$obj = new $extconf['class']['name']($settings, $dms, $logger);
				if(method_exists($obj, 'migrate'))
					$ret = $obj->migrate();
			}
		}
		return $ret;
	} /* }}} */

	public function checkExtensionByDir($dir, $options = array()) { /* {{{ */
		$this->errmsgs = array();

		if(!file_exists($dir)) {
			if(!file_exists($this->extdir.'/'.$dir))
				return false;
			else
				$dir = $this->extdir.'/'.$dir;
		}
		if(!file_exists($dir."/conf.php")) {
			$this->errmsgs[] = "Missing extension configuration";
			return false;
		}
		include($dir."/conf.php");
		if(!isset($EXT_CONF)) {
			$this->errmsgs[] = "Missing \$EXT_CONF in configuration";
			return false;
		}
		$extname = key($EXT_CONF);
		if(!$extname || !preg_match('/[a-zA-Z_]*/', $extname)) {
			$this->errmsgs[] = "Extension has invalid or no name";
			return false;
		}

		$extconf = $EXT_CONF[$extname];

		if(!empty($extconf['language']['file']) && !file_exists($dir."/".$extconf['language']['file'])) {
			$this->errmsgs[] = "Missing language file";
		}
		if(!empty($extconf['class']['file']) && !file_exists($dir."/".$extconf['class']['file'])) {
			$this->errmsgs[] = "Missing class file";
		}
		if(!empty($extconf['icon']) && !file_exists($dir."/".$extconf['icon'])) {
			$this->errmsgs[] = "Missing icon file";
		}

		if($this->errmsgs)
			return false;

		return self::checkExtensionByName($extname, $extconf, $options);
	}

	/**
	 * Check content of extension directory or configuration of extension
	 *
	 * @param string|array $dir full path to extension directory or extension name
	 * or an array containing the configuration.
	 * @param array $options array with options elements 'noconstraints' and 'nofiles'.
	 * Set 'noconstraints' to true if
	 * constraints to local seeddms installation shall not be checked. Set 'nofiles'
	 * to true to turn off checking of files
	 * @return boolean true if check was successful, otherwise false
	 */
	public function checkExtensionByName($extname, $extconf, $options=array()) { /* {{{ */
		if(isset($this->configcache[$extname])) {
			return $this->configcache[$extname];
		}

		$this->errmsgs = array();

		if(!isset($extconf['constraints']['depends']['seeddms'])) {
			$this->errmsgs[] = "Missing dependency on SeedDMS";
		}
		if(!isset($extconf['constraints']['depends']['php'])) {
			$this->errmsgs[] = "Missing dependency on PHP";
		}
		if(!isset($extconf['version'])) {
			$this->errmsgs[] = "Missing version information";
		}
		if(!isset($extconf['title'])) {
			$this->errmsgs[] = "Missing title";
		}
		if(!isset($extconf['author'])) {
			$this->errmsgs[] = "Missing author";
		}

		if(!isset($options['noconstraints']) || $options['noconstraints'] == false) {
			if(isset($extconf['constraints']['depends'])) {
				foreach($extconf['constraints']['depends'] as $dkey=>$dval) {
					switch($dkey) {
					case 'seeddms':
						$version = new SeedDMS_Version;
						if(is_array($dval)) {
							$fullfill = false;
							foreach($dval as $ddval) {
								$tmp = explode('-', $ddval, 2);
								if(self::cmpVersion($tmp[0], $version->version()) > 0 || ($tmp[1] && self::cmpVersion($tmp[1], $version->version()) < 0))
									; // No within version range
								else
									$fullfill = true;
							}
							if(!$fullfill)
								$this->errmsgs[] = sprintf("Incorrect SeedDMS version (needs version \"%s\")", implode('" or "', $dval));
						} elseif(is_string($dval)) {
							$tmp = explode('-', $dval, 2);
							if(self::cmpVersion($tmp[0], $version->version()) > 0 || ($tmp[1] && self::cmpVersion($tmp[1], $version->version()) < 0))
								$this->errmsgs[] = sprintf("Incorrect SeedDMS version (needs version %s)", $extconf['constraints']['depends']['seeddms']);
						}
						break;
					case 'php':
						$tmp = explode('-', $dval, 2);
						if(self::cmpVersion($tmp[0], phpversion()) > 0 || ($tmp[1] && self::cmpVersion($tmp[1], phpversion()) < 0))
							$this->errmsgs[] = sprintf("Incorrect PHP version (needs version %s)", $dval);
						break;
					case 'phpext':
						if(is_array($dval) && $dval) {
							$extlist = get_loaded_extensions();
							foreach($dval as $d) {
								if(!in_array($d, $extlist))
									$this->errmsgs[] = sprintf("Missing php extension '%s'", $d);
							}
						}
						break;
					case 'cmd':
						if(is_array($dval) && $dval) {
							foreach($dval as $d) {
								if(!commandExists($d))
									$this->errmsgs[] = sprintf("Missing command '%s'", $d);
							}
						}
						break;
					default:
						$tmp = explode('-', $dval, 2);
						if(isset($this->extconf[$dkey]['version'])) {
							if(self::cmpVersion($tmp[0], $this->extconf[$dkey]['version']) > 0 || ($tmp[1] && self::cmpVersion($tmp[1], $this->extconf[$dkey]['version']) < 0))
								$this->errmsgs[] = sprintf("Incorrect version of extension '%s' (needs version '%s' but provides '%s')", $dkey, $dval, $this->extconf[$dkey]['version']);
						} else {
							$this->errmsgs[] = sprintf("Missing extension or version for '%s'", $dkey);
						}
						break;
					}
				}
			}
		}

		if($this->errmsgs)
			$this->configcache[$extname] = false;
		else
			$this->configcache[$extname] = true;

		return $this->configcache[$extname];
	} /* }}} */

	static protected function rrmdir($dir) { /* {{{ */
		if (is_dir($dir)) { 
			$objects = scandir($dir); 
			foreach ($objects as $object) { 
				if ($object != "." && $object != "..") { 
					if (filetype($dir."/".$object) == "dir") self::rrmdir($dir."/".$object); else unlink($dir."/".$object); 
				} 
			} 
			reset($objects); 
			rmdir($dir); 
		} 
	} /* }}} */

	/**
	 * Update an extension
	 *
	 * This function will replace an existing extension or add a new extension
	 * The passed file has to be zipped content of the extension directory not
	 * including the directory itself.
	 *
	 * @param string $file name of extension archive
	 * @return boolean true on success, othewise false
	 */
	public function updateExtension($file) { /* {{{ */
		/* unzip the extension in a temporary directory */
		$newdir = addDirSep($this->cachedir)."ext.new";
		/* First remove a left over from a previous extension */
		if(file_exists($newdir)) {
			self::rrmdir($newdir);
		}
		if(!mkdir($newdir, 0755)) {
			$this->errmsgs[] = "Cannot create temp. extension directory";
			return false;
		}
		$zip = new ZipArchive;
		$res = $zip->open($file);
		if ($res === TRUE) {
			$zip->extractTo($newdir);
			$zip->close();
		} else {
			$this->errmsgs[] = "Cannot open extension file";
			return false;
		}
//		$cmd = "cd ".$newdir."; unzip ".$file;
//		exec($cmd);

		/* Check if extension is complete and fullfills the constraints */
		if(!self::checkExtensionByDir($newdir)) {
			self::rrmdir($newdir);
			return false;
		}

		include($newdir."/conf.php");
		$extname = key($EXT_CONF);

		/* Create the target directory */
		if(!is_dir($this->extdir)) {
			if(!mkdir($this->extdir, 0755)) {
				$this->errmsgs[] = "Cannot create extension directory";
				self::rrmdir($newdir);
				return false;
			}
		} elseif(is_dir($this->extdir ."/". $extname)) {
			$this->rrmdir($this->extdir ."/". $extname);
		}
		/* Move the temp. created ext directory to the final location */
		/* rename() may fail if dirs are moved from one device to another.
		 * See https://bugs.php.net/bug.php?id=54097
		 *
		 * exec("mv ".escapeshellarg($newdir)." ".escapeshellarg($this->extdir ."/". $extname));
		 *
		 * It's also sufficient to just copy the extracted archive to the final
		 * location and leave the extracted archive in place. The next time an
		 * extension is imported the last extracted archive will be removed.
		 */
//		if(!rename($newdir, $this->extdir ."/". $extname)) {
		if(false === exec('mv '.escapeshellarg($newdir).' '.escapeshellarg($this->extdir."/".$extname))) {
			/* If copy didn't succeed, then there is probably nothing to delete,
			 * but do it anyway, just to be sure not just parts of the extension
			 * has been copied.
			 */
			$this->rrmdir($this->extdir ."/". $extname);
			return false;
		}

		return true;
	} /* }}} */

	/**
	 * Get list of extensions from cached repository index
	 *
	 * This function returns the whole repository index file separated in
	 * single lines. Each line is either a comment if it starts with an '#'
	 * or a json encoded array containing the extension configuration.
	 *
	 * Run SeedDMS_Extension_Mgr::updateExtensionList() to ensure the
	 * currently cached extension list file is up to date.
	 *
	 * @return string[] list of json strings or comments
	 */
	public function getRawExtensionList() { /* {{{ */
		if(file_exists($this->cachedir."/".self::repos_list_file)) {
			return file($this->cachedir."/".self::repos_list_file);
		} else {
			return array();
		}
	} /* }}} */

	/**
	 * Get list of extensions from cached repository index
	 *
	 * This function reads the cache respository index and returns
	 * a list of extension configurations. Only the most recent version
	 * of an extension will be included.
	 *
	 * Run SeedDMS_Extension_Mgr::updateExtensionList() to ensure the
	 * currently cached extension list file is up to date.
	 *
	 * @return array[] list of extension configurations
	 */
	public function getExtensionList() { /* {{{ */
		$list = self::getRawExtensionList();
		$result = array();
		$vcache = array(); // keep highest version of extension
		foreach($list as $e) {
			if($e[0] != '#' && trim($e)) {
				if($re = json_decode($e, true)) {
					if(!isset($result[$re['name']])) {
						$result[$re['name']] = $re;
						$vcache[$re['name']] = $re['version'];
					} elseif(self::cmpVersion($re['version'], $vcache[$re['name']]) > 0) {
						$result[$re['name']] = $re;
						$vcache[$re['name']] = $re['version'];
					}
				}
			}
		}
		return $result;
	} /* }}} */

	/**
	 * Get list of version of an extension from cached repository index
	 *
	 * This function reads the cache respository index and returns
	 * a list of extension configurations. Only those extensions will
	 * be included which maches the given name.
	 *
	 * Run SeedDMS_Extension_Mgr::updateExtensionList() to ensure the
	 * currently cached extension list file is up to date.
	 *
	 * @return array[] list of extension configurations
	 */
	public function getExtensionListByName($extname) { /* {{{ */
		$list = self::getRawExtensionList();
		$result = array();
		foreach($list as $e) {
			if($e[0] != '#') {
				$re = json_decode($e, true);
				if($re['name'] == $extname) {
					$result[$re['version']] = $re;
				}
			}
		}
		uksort($result, function($a, $b){return SeedDMS_Extension_Mgr::cmpVersion($b, $a);});
		return $result;
	} /* }}} */

	/**
	 * Import list of extension from repository
	 *
	 * @param boolean $force force download even if file already exists
	 */
	public function updateExtensionList($version='', $force=false) { /* {{{ */
		if($this->reposurl) {
			if(!file_exists($this->cachedir."/".self::repos_list_file) || $force) {
				if(false !== ($file = @file_get_contents($this->reposurl.($version ? '?seeddms_version='.$version : ''), false, $this->getStreamContext()))) {
					if(is_array($http_response_header)) {
						$parts=explode(' ',$http_response_header[0]);
						if(count($parts)>1) //HTTP/1.0 <code> <text>
							if(intval($parts[1]) != 200) {
								$this->errmsgs[] = 'Getting extension list returned http code ('.$parts[1].')';
								return false;
							}
					}
					if(!$file) {
						$this->errmsgs[] = 'Extension list is empty';
						return false;
					}
					file_put_contents($this->cachedir."/".self::repos_list_file, $file);
				} else {
					$this->errmsgs[] = 'Could not fetch extension list';
					return false;
				}
			}
			return true;
		} else {
			return false;
		}
	} /* }}} */

	/**
	 * Download an extension from the repository
	 *
	 * @param string $file filename of extension
	 */
	public function getExtensionFromRepository($file) { /* {{{ */
		$content = file_get_contents($this->reposurl."/".$file, false, $this->getStreamContext());
		$tmpfile = tempnam(sys_get_temp_dir(), '');
		file_put_contents($tmpfile, $content);
		return $tmpfile;
	} /* }}} */

	/**
	 * Return last error message
	 *
	 * @return string
	 */
	public function getErrorMsg() { /* {{{ */
		if($this->errmsgs)
			return $this->errmsgs[0];
		else
			return '';
	} /* }}} */

	/**
	 * Return all error messages
	 *
	 * @return string[]
	 */
	public function getErrorMsgs() { /* {{{ */
		return $this->errmsgs;
	} /* }}} */
}
