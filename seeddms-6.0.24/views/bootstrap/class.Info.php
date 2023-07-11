<?php
/**
 * Implementation of Info view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for Info view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Info extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$httproot = $settings->_httpRoot;
		$version = $this->params['version'];
		$availversions = $this->params['availversions'];
		$extmgr = $this->params['extmgr'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		if($availversions) {
			$newversion = '';
			foreach($availversions as $availversion) {
				if($availversion[0] == 'stable')
					$newversion = $availversion[1];
			}
			if($newversion > $version->version()) {
				$this->warningMsg(getMLText('no_current_version', array('latestversion'=>$newversion)));
			}
		} else {
			$this->warningMsg(getMLText('no_version_check'));
		}

		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText("seeddms_info"));
		$seedextensions = $extmgr->getExtensionConfiguration();
		echo "<table class=\"table table-condensed table-sm\">\n";
		echo "<thead>\n<tr>\n";
		echo "<th></th>";
		echo "<th>".getMLText("name");
		echo "</th>\n";
		echo "</tr>\n</thead>\n<tbody>\n";
		$dbversion = $dms->getDBVersion();
		echo "<tr><td></td><td></td><td>".getMLText('seeddms_version')."</td><td>".$version->version()."</td></tr>\n";
		if($user->isAdmin()) {
			echo "<tr><td></td><td></td><td>".getMLText('database_schema_version')."</td><td>".$dbversion['major'].".".$dbversion['minor'].".".$dbversion['subminor']."</td></tr>\n";
			foreach($seedextensions as $extname=>$extconf) {
				echo "<tr><td>";
				if(!$settings->extensionIsDisabled($extname))
					echo "<i class=\"fa fa-circle text-success\"></i> ";
				else
					echo "<i class=\"fa fa-circle text-danger text-error\"></i> ";
				echo "</td>";
				echo "<td width=\"32\">";
				if($extconf['icon'])
					echo "<img width=\"32\" height=\"32\" src=\"".$httproot."ext/".$extname."/".$extconf['icon']."\" alt=\"".$extname."\" title=\"".$extname."\">";
				echo "</td>";
				echo "<td>".$extname."<br />".$extconf['title']."</td><td>".$extconf['version']."</td>";
				echo "</tr>\n";
			}
		}
		echo "</tbody>\n</table>\n";
		$this->columnEnd();
		$this->columnStart(6);
		if($user->isAdmin()) {
			$this->contentHeading(getMLText("php_info"));
			echo "<table class=\"table table-condensed table-sm\">\n";
			echo "<thead>\n<tr>\n";
			echo "<th>".getMLText("name");
			echo "</th>\n";
			echo "</tr>\n</thead>\n<tbody>\n";
			echo "<tr><td>PHP</td><td>".phpversion()."</td></tr>\n";
			echo "<tr><td>Path to php.ini</td><td>".php_ini_loaded_file()."</td></tr>\n";
			echo "</tbody>\n</table>\n";

			$this->contentHeading(getMLText("installed_php_extensions"));
			$phpextensions = get_loaded_extensions(false);
			echo implode(', ', $phpextensions);

			$this->contentHeading(getMLText("missing_php_extensions"));
			$requiredext = array('zip', 'xml', 'xsl', 'json', 'intl', 'fileinfo', 'mbstring', 'curl', 'sqlite3', 'imagick', 'openssl');
			echo implode(', ', array_diff($requiredext, $phpextensions));

			$this->contentHeading(getMLText("missing_php_functions_and_classes"));
			$missingfunc = [];
			foreach(array('proc_open', 'openssl_cipher_iv_length') as $funcname) {
				if(!function_exists($funcname)) {
					$missingfunc[] = $funcname; //getMLText('func_'.$funcname."_missing")
				}
			}
			$missingclass = [];
			foreach(array('finfo') as $classname) {
				if(!class_exists($classname)) {
					$missingclass[] = $classname; //getMLText('func_'.$classname."_missing")
				}
			}
			echo '<p>'.implode(', ', $missingfunc).'</p>';
			echo '<p>'.implode(', ', $missingclass).'</p>';

			if(function_exists('apache_get_modules')) {
				$this->contentHeading(getMLText("installed_apache_extensions"));
				$apacheextensions = apache_get_modules();
				echo implode(', ', $apacheextensions);
			}

			function check_result($name, $res) {
				echo "<tr ".($res ? 'class="table-success success"' : 'class="table-danger error"')."><td>".getMLText($name)."</td><td>".getMLText($res ? 'check_passed' : 'check_failed')."</td></tr>\n";
			}
			$this->contentHeading(getMLText("check_directory_layout"));
			echo "<table class=\"table table-condensed table-sm\">\n";
			echo "<thead>\n<tr>\n";
			echo "<th>".getMLText("directory_check")."</th>\n";
			echo "<th>".getMLText("directory_check_result")."</th>\n";
			echo "</tr>\n</thead>\n<tbody>\n";
			check_result('directory_check_ext_exists', is_dir($settings->_rootDir."/ext"));
			check_result('directory_check_ext_writable', is_writable($settings->_rootDir."/ext"));
			check_result('directory_check_data_exists', is_dir($settings->_contentDir));
			check_result('directory_check_data_writable', is_writable($settings->_contentDir));
			check_result('directory_check_cache_exists', is_dir($settings->_cacheDir));
			check_result('directory_check_cache_writable', is_writable($settings->_cacheDir));
			check_result('directory_check_index_exists', is_dir($settings->_luceneDir));
			check_result('directory_check_index_writable', is_writable($settings->_luceneDir));
			check_result('directory_check_conf_writable', is_writable($settings->_configFilePath));
			$res = !str_starts_with($settings->_contentDir, $settings->_rootDir);
			check_result('directory_check_data_below_root', $res);
			echo "</tbody>\n</table>\n";

		}
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
