<?php
include("../views/bootstrap/class.Bootstrap.php");

class SeedDMS_View_Install extends SeedDMS_Theme_Style {
	protected function printError($error) { /* {{{ */
		print "<div class=\"alert alert-error\">\n";
		print $error;
		print "</div>";
	} /* }}} */

	protected function printWarning($error) { /* {{{ */
		print "<div class=\"alert alert-warning\">";
		print $error;
		print "</div>";
	} /* }}} */

	protected function printCheckError($resCheck) { /* {{{ */
		$hasError = false;
		foreach($resCheck as $keyRes => $paramRes) {
			if(isset($paramRes['type']) && $paramRes['type'] == 'error')
				$hasError = true;
			$errorMes = getMLText("settings_$keyRes"). " : " . getMLText("settings_".$paramRes["status"]);

			if (isset($paramRes["currentvalue"]))
				$errorMes .= "<br/> =&gt; " . getMLText("settings_currentvalue") . " : " . $paramRes["currentvalue"];
			if (isset($paramRes["suggestionvalue"]))
				$errorMes .= "<br/> =&gt; " . getMLText("settings_suggestionvalue") . " : " . $paramRes["suggestionvalue"];
			if (isset($paramRes["suggestion"]))
				$errorMes .= "<br/> =&gt; " . getMLText("settings_".$paramRes["suggestion"]);
			if (isset($paramRes["systemerror"]))
				$errorMes .= "<br/> =&gt; " . $paramRes["systemerror"];

			if(isset($paramRes['type']) && $paramRes['type'] == 'error')
				$this->printError($errorMes);
			else
				$this->printWarning($errorMes);
		}

		return $hasError;
	} /* }}} */

	protected function openDBConnection($settings) { /* {{{ */
		switch($settings->_dbDriver) {
			case 'mysql':
			case 'mysqli':
			case 'mysqlnd':
			case 'pgsql':
				$tmp = explode(":", $settings->_dbHostname);
				$dsn = $settings->_dbDriver.":dbname=".$settings->_dbDatabase.";host=".$tmp[0];
				if(isset($tmp[1]))
					$dsn .= ";port=".$tmp[1];
				break;
			case 'sqlite':
				$dsn = $settings->_dbDriver.":".$settings->_dbDatabase;
				break;
		}
		$connTmp = new PDO($dsn, $settings->_dbUser, $settings->_dbPass);
		return $connTmp;
	} /* }}} */

	public function intro() { /* {{{ */
		$this->htmlStartPage("INSTALL");
		$this->globalBanner();
		$this->contentStart();
		$this->contentHeading("SeedDMS Installation for version ".SEEDDMS_VERSION);
		$this->contentContainerStart();
echo "<h2>".getMLText('settings_install_welcome_title')."</h2>";
echo "<div style=\"width: 600px;\">".getMLText('settings_install_welcome_text')."</div>";
echo '<p><a href="install.php">' . getMLText("settings_start_install") . '</a></p>';
		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */

	public function install($msg) { /* {{{ */
		$settings = $this->params['settings'];
		$configDir = $this->params['configdir'];

		$this->htmlStartPage("INSTALL");
		$this->globalBanner();
		$this->contentStart();
		$this->contentHeading("SeedDMS Installation for version ".SEEDDMS_VERSION);
		if(isset($msg))
			$this->warningMsg($msg);
		$this->contentContainerStart();


		/**
		 * Show phpinfo
		 */
		if (isset($_GET['phpinfo'])) {
			echo '<a href="install.php">' . getMLText("back") . '</a>';
			phpinfo();
			$this->contentContainerEnd();
			$this->contentEnd();
			$this->htmlEndPage();
			exit();
		}

		/**
		 * check if ENABLE_INSTALL_TOOL shall be removed
		 */
		if (isset($_GET['disableinstall'])) { /* {{{ */
			if(file_exists($configDir."/ENABLE_INSTALL_TOOL")) {
				if(unlink($configDir."/ENABLE_INSTALL_TOOL")) {
					echo getMLText("settings_install_disabled");
					echo "<br/><br/>";
					echo '<a href="../out/out.Settings.php">' . getMLText("settings_more_settings") .'</a>';
				} else {
					echo getMLText("settings_cannot_disable");
					echo "<br/><br/>";
					echo '<a href="install.php">' . getMLText("back") . '</a>';
				}
			} else {
				echo getMLText("settings_cannot_disable");
				echo "<br/><br/>";
				echo '<a href="install.php">' . getMLText("back") . '</a>';
			}
			$this->contentContainerEnd();
			$this->contentEnd();
			$this->htmlEndPage();
			exit();
		} /* }}} */

		/**
		 * Check System
		 */
		if ($this->printCheckError( $settings->checkSystem())) { /* {{{ */
			if (function_exists("apache_get_version")) {
				echo "<br/>Apache version: " . apache_get_version();
			}

			echo "<br/>PHP version: " . phpversion();

			echo "<br/>PHP include path: " . ini_get('include_path');

			echo '<br/>';
			echo '<br/>';
			echo '<a href="' . $httpRoot . 'install/install.php">' . getMLText("refresh") . '</a>';
			echo ' - ';
			echo '<a href="' . $httpRoot . 'install/install.php?phpinfo">' . getMLText("version_info") . '</a>';

			exit;
		} /* }}} */

		if (isset($_POST["action"])) $action=$_POST["action"];
		else if (isset($_GET["action"])) $action=$_GET["action"];
		else $action=NULL;

		$showform = true;
		if ($action=="setSettings") { /* {{{ */
			/**
			 * Get Parameters
			 */
			$settings->_rootDir = $_POST["rootDir"];
			$settings->_httpRoot = $_POST["httpRoot"];
			$settings->_contentDir = $_POST["contentDir"];
			$settings->_luceneDir = $_POST["luceneDir"];
			$settings->_stagingDir = $_POST["stagingDir"];
			$settings->_cacheDir = $_POST["cacheDir"];
			$settings->_extraPath = $_POST["extraPath"];
			$settings->_dbDriver = $_POST["dbDriver"];
			$settings->_dbHostname = $_POST["dbHostname"];
			$settings->_dbDatabase = $_POST["dbDatabase"];
			$settings->_dbUser = $_POST["dbUser"];
			$settings->_dbPass = $_POST["dbPass"];
			$settings->_coreDir = ''; //$_POST["coreDir"];
			$settings->_luceneClassDir = ''; //$_POST["luceneClassDir"];

			if(isset($settings->_extraPath))
				ini_set('include_path', $settings->_extraPath. PATH_SEPARATOR .ini_get('include_path'));

			/**
			 * Check Parameters, require version 3.3.x
			 */
			$hasError = $this->printCheckError( $settings->check(substr(str_replace('.', '', SEEDDMS_VERSION), 0,2)));

			if (!$hasError) {
				// Create database
				if (isset($_POST["createDatabase"])) {
					$createOK = false;
					$errorMsg = "";

					$connTmp = $this->openDBConnection($settings);
					if ($connTmp) {
						// read SQL file
						if ($settings->_dbDriver=="mysql")
							$queries = file_get_contents("create_tables-innodb.sql");
						elseif($settings->_dbDriver=="sqlite")
							$queries = file_get_contents("create_tables-sqlite3.sql");
						elseif($settings->_dbDriver=="pgsql")
							$queries = file_get_contents("create_tables-postgres.sql");
						else
							die();

						// generate SQL query
						$queries = explode(";", $queries);

						// execute queries
						foreach($queries as $query) {
						// var_dump($query);
							$query = trim($query);
							if (!empty($query)) {
								$connTmp->exec($query);

								if ($connTmp->errorCode() != 0) {
									$errorMsg .= $connTmp->errorInfo()[2] . "<br/>";
								}
							}
						}
					}

					// error ?
					if (empty($errorMsg))
						$createOK = true;

					$connTmp = null;

					// Show error
					if (!$createOK) {
						echo $errorMsg;
						$hasError = true;
					}
				} // create database

				if (!$hasError) {

					// Save settings
					$settings->save();

					$needsupdate = false;
					$connTmp = $this->openDBConnection($settings);
					if ($connTmp) {
						switch($settings->_dbDriver) {
							case 'mysql':
							case 'mysqli':
							case 'mysqlnd':
							case 'sqlite':
								$sql = 'select * from `tblVersion`';
								break;
							case 'pgsql':
								$sql = 'select * from "tblVersion"';
								break;
						}
						$res = $connTmp->query($sql);
						if($res) {
							if($rec = $res->fetch(PDO::FETCH_ASSOC)) {
								$updatedirs = array();
								$d = dir(".");
								while (false !== ($entry = $d->read())) {
									if(preg_match('/update-([0-9.]*)/', $entry, $matches)) {
										$updatedirs[] = $matches[1];
									}
								}
								$d->close();

								echo "Your current database schema has version ".$rec['major'].'.'.$rec['minor'].'.'.$rec['subminor'].". Please run all (if any)<br />of the update scripts below in the listed order.<br /><br />";
								$connTmp = null;

								if($updatedirs) {
									asort($updatedirs);
									foreach($updatedirs as $updatedir) {
										if($updatedir > $rec['major'].'.'.$rec['minor'].'.'.$rec['subminor']) {
											$needsupdate = true;
											print "<h3>Database update to version ".$updatedir." needed</h3>";
											if(file_exists('update-'.$updatedir.'/update.txt')) {
												print "<p>Please read the comments on updating this version. <a href=\"update-".$updatedir."/update.txt\" target=\"_blank\">Read now</a></p>";
											}
											print "<p>Run the <a href=\"update.php?version=".$updatedir."\">update script</a>.</p>";
										}
									}
								} else {
									print "<p>Your current database is up to date.</p>";
								}
							}
							if(!$needsupdate) {
								echo getMLText("settings_install_success");
								echo "<br/><br/>";
								echo getMLText("settings_delete_install_folder");
								echo "<br/><br/>";
								echo '<a href="install.php?disableinstall=1">' . getMLText("settings_disable_install") . '</a>';
								echo "<br/><br/>";

								echo '<a href="../out/out.Settings.php">' . getMLText("settings_more_settings") .'</a>';
								$showform = false;
							}
						} else {
							print "<p>You does not seem to have a valid database. The table tblVersion is missing.</p>";
						}
					}
				}
			}

			// Back link
			echo '<br/>';
			echo '<br/>';
		//	echo '<a href="' . $httpRoot . '/install/install.php">' . getMLText("back") . '</a>';

		} /* }}} */

		if($showform) { /* {{{ */

			/**
			 * Set parameters
			 */
		?>
	<form action="install.php" method="post" enctype="multipart/form-data">
	<input type="Hidden" name="action" value="setSettings">
			<table>
				<!-- SETTINGS - SYSTEM - SERVER -->
				<tr ><td><b> <?php printMLText("settings_Server");?></b></td> </tr>
				<tr title="<?php printMLText("settings_rootDir_desc");?>">
					<td><?php printMLText("settings_rootDir");?>:</td>
					<td><input type="text" name="rootDir" value="<?php echo $settings->_rootDir ?>" size="100" /></td>
				</tr>
				<tr title="<?php printMLText("settings_httpRoot_desc");?>">
					<td><?php printMLText("settings_httpRoot");?>:</td>
					<td><input type="text" name="httpRoot" value="<?php echo $settings->_httpRoot ?>" size="100" /></td>
				</tr>
				<tr title="<?php printMLText("settings_contentDir_desc");?>">
					<td><?php printMLText("settings_contentDir");?>:</td>
					<td><input type="text" name="contentDir" value="<?php echo $settings->_contentDir ?>" size="100" style="background:yellow" /></td>
				</tr>
				<tr title="<?php printMLText("settings_luceneDir_desc");?>">
					<td><?php printMLText("settings_luceneDir");?>:</td>
					<td><input type="text" name="luceneDir" value="<?php echo $settings->_luceneDir ?>" size="100" style="background:yellow" /></td>
				</tr>
				<tr title="<?php printMLText("settings_stagingDir_desc");?>">
					<td><?php printMLText("settings_stagingDir");?>:</td>
					<td><input type="text" name="stagingDir" value="<?php echo $settings->_stagingDir ?>" size="100" style="background:yellow" /></td>
				</tr>
				<tr title="<?php printMLText("settings_cacheDir_desc");?>">
					<td><?php printMLText("settings_cacheDir");?>:</td>
					<td><input type="text" name="cacheDir" value="<?php echo $settings->_cacheDir ?>" size="100" style="background:yellow" /></td>
				</tr>
<!--
				<tr title="<?php printMLText("settings_coreDir_desc");?>">
					<td><?php printMLText("settings_coreDir");?>:</td>
					<td><input type="text" name="coreDir" value="<?php echo $settings->_coreDir ?>" size="100" /></td>
				</tr>
				<tr title="<?php printMLText("settings_luceneClassDir_desc");?>">
					<td><?php printMLText("settings_luceneClassDir");?>:</td>
					<td><input type="text" name="luceneClassDir" value="<?php echo $settings->_luceneClassDir ?>" size="100" /></td>
				</tr>
-->
				<tr title="<?php printMLText("settings_extraPath_desc");?>">
					<td><?php printMLText("settings_extraPath");?>:</td>
					<td><input type="text" name="extraPath" value="<?php echo $settings->_extraPath ?>" size="100" /></td>
				</tr>

				<!-- SETTINGS - SYSTEM - DATABASE -->
				<tr ><td><b> <?php printMLText("settings_Database");?></b></td> </tr>
				<tr title="<?php printMLText("settings_dbDriver_desc");?>">
					<td><?php printMLText("settings_dbDriver");?>:</td>
					<td><input type="text" name="dbDriver" value="<?php echo $settings->_dbDriver ?>" /></td>
				</tr>
				<tr title="<?php printMLText("settings_dbHostname_desc");?>">
					<td><?php printMLText("settings_dbHostname");?>:</td>
					<td><input type="text" name="dbHostname" value="<?php echo $settings->_dbHostname ?>" /></td>
				</tr>
				<tr title="<?php printMLText("settings_dbDatabase_desc");?>">
					<td><?php printMLText("settings_dbDatabase");?>:</td>
					<td><input type="text" name="dbDatabase" value="<?php echo $settings->_dbDatabase ?>" style="background:yellow" /></td>
				</tr>
				<tr title="<?php printMLText("settings_dbUser_desc");?>">
					<td><?php printMLText("settings_dbUser");?>:</td>
					<td><input type="text" name="dbUser" value="<?php echo $settings->_dbUser ?>" style="background:yellow" /></td>
				</tr>
				<tr title="<?php printMLText("settings_dbPass_desc");?>">
					<td><?php printMLText("settings_dbPass");?>:</td>
					<td><input name="dbPass" value="<?php echo $settings->_dbPass ?>" type="password" style="background:yellow" /></td>
				</tr>
				<tr><td></td></tr>
				<tr><td></td></tr>
				<tr>
					<td><?php printMLText("settings_createdatabase");?>:</td>
					<td><input name="createDatabase" type="checkbox" style="background:yellow"/></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" class="btn btn-primary" value="<?php printMLText("apply");?>" /></td>
				</tr>
			</table>

	</form>
<?php

		} /* }}} */

		// just remove info for web page installation
		$settings->_printDisclaimer = false;
		$settings->_footNote = false;

		// end of the page
		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */

	public function update() { /* {{{ */
		$settings = $this->params['settings'];

		$this->htmlStartPage('Database update');
		$this->globalBanner();
		$this->contentStart();
		$this->contentHeading("SeedDMS Installation for version ".$_GET['version']);
		$this->contentContainerStart();

$sqlfile = "update.sql";
switch($settings->_dbDriver) {
	case 'mysql':
	case 'mysqli':
	case 'mysqlnd':
		$tmp = explode(":", $settings->_dbHostname);
		$dsn = $settings->_dbDriver.":dbname=".$settings->_dbDatabase.";host=".$tmp[0];
		if(isset($tmp[1]))
			$dsn .= ";port=".$tmp[1];
		break;
	case 'sqlite':
		$dsn = $settings->_dbDriver.":".$settings->_dbDatabase;
		if(file_exists('update-'.$_GET['version'].'/update-sqlite3.sql'))
			$sqlfile = "update-sqlite3.sql";
		break;
	case 'pgsql':
		$tmp = explode(":", $settings->_dbHostname);
		$dsn = $settings->_dbDriver.":dbname=".$settings->_dbDatabase.";host=".$tmp[0];
		if(isset($tmp[1]))
			$dsn .= ";port=".$tmp[1];
		if(file_exists('update-'.$_GET['version'].'/update-postgres.sql'))
			$sqlfile = "update-postgres.sql";
}
$db = new PDO($dsn, $settings->_dbUser, $settings->_dbPass);
if (!$db) {
	die;
}

$errorMsg = '';
$res = $db->query('select * from tblVersion');
$recs = $res->fetchAll(PDO::FETCH_ASSOC);
if(!empty($recs)) {
	$rec = $recs[0];
	if($_GET['version'] > $rec['major'].'.'.$rec['minor'].'.'.$rec['subminor']) {

		if(file_exists('update-'.$_GET['version'].'/'.$sqlfile)) {
			$queries = file_get_contents('update-'.$_GET['version'].'/'.$sqlfile);
			$queries = explode(";", $queries);

			// execute queries
			if($queries) {
				echo "<h3>Updating database schema</h3>";
				foreach($queries as $query) {
					$query = trim($query);
					if (!empty($query)) {
						echo $query."<br />";
						if(false === $db->exec($query)) {
							$e = $db->ErrorInfo();
							$errorMsg .= $e[2] . "<br/>";
						}
					}
				}
			}
		} else {
			echo "<p>SQL file for update missing!</p>";
		}
	} else {
		echo "<p>Database schema already up to date.</p>";
	}


	if(!$errorMsg) {
		if(file_exists('update-'.$_GET['version'].'/update.php')) {
			echo "<h3>Running update script</h3>";
			include('update-'.$_GET['version'].'/update.php');
		}
	} else {
		echo "<h3>Error Messages</h3>";
		echo $errorMsg;
	}
	echo "<p><a href=\"install.php\">Go back to installation and recheck.</a></p>";
} else {
	echo "<p>Could not determine database schema version.</p>";
}
$db = null;

// just remove info for web page installation
$settings->_printDisclaimer = false;
$settings->_footNote = false;
// end of the page
		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
