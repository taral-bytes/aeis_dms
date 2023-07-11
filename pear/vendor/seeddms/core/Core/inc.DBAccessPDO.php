<?php
/**
 * Implementation of database access using PDO
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
/** @noinspection PhpUndefinedClassInspection */

/**
 * Class to represent the database access for the document management
 * This class uses PDO for the actual database access.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_DatabaseAccess {
	/**
	 * @var boolean set to true for debug mode
	 */
	public $_debug;

	/**
	 * @var string name of database driver (mysql or sqlite)
	 */
	protected $_driver;

	/**
	 * @var string name of hostname
	 */
	protected $_hostname;

	/**
	 * @var int port number of database
	 */
	protected $_port;

	/**
	 * @var string name of database
	 */
	protected $_database;

	/**
	 * @var string name of database user
	 */
	protected $_user;

	/**
	 * @var string password of database user
	 */
	protected $_passw;

	/**
	 * @var object internal database connection
	 */
	private $_conn;

	/**
	 * @var boolean set to true if connection to database is established
	 */
	private $_connected;

	/**
	 * @var boolean set to true if temp. table for tree view has been created
	 */
	private  $_ttreviewid;

	/**
	 * @var boolean set to true if temp. table for approvals has been created
	 */
	private $_ttapproveid;

	/**
	 * @var boolean set to true if temp. table for doc status has been created
	 */
	private $_ttstatid;

	/**
	 * @var boolean set to true if temp. table for doc content has been created
	 */
	private $_ttcontentid;

	/**
	 * @var boolean set to true if temp. table for doc reception has been created
	 */
	private $_ttreceiptid;

	/**
	 * @var boolean set to true if temp. table for doc revision has been created
	 */
	private $_ttrevisionid;

	/**
	 * @var boolean set to true if in a database transaction
	 */
	private $_intransaction;

	/**
	 * @var string set a valid file name for logging all sql queries
	 */
	private $_logfile;

	/**
	 * @var resource file pointer of log file
	 */
	private $_logfp;

	/**
	 * @var boolean set to true if views instead of temp. tables shall be used
	 */
	private $_useviews;

	/**
	 * Return list of all database tables
	 *
	 * This function is used to retrieve a list of database tables for backup
	 *
	 * @return string[]|bool list of table names
	 */
	function TableList() { /* {{{ */
		switch($this->_driver) {
			case 'mysql':
				$sql = "SELECT `TABLE_NAME` AS `name` FROM `information_schema`.`tables` WHERE `TABLE_SCHEMA`='".$this->_database."' AND `TABLE_TYPE`='BASE TABLE'";
				break;
			case 'sqlite':
				$sql = "SELECT tbl_name AS name FROM sqlite_master WHERE type='table'";
				break;
			case 'pgsql':
				$sql = "select tablename as name from pg_catalog.pg_tables where schemaname='public'";
				break;
			default:
				return false;
		}
		$arr = $this->getResultArray($sql);
		$res = array();
		foreach($arr as $tmp)
			$res[] = $tmp['name'];
		return $res;
	}	/* }}} */

	/**
	 * Check if database has a table
	 *
	 * This function will check if the database has a table with the given table name
	 *
	 * @return bool true if table exists, otherwise false
	 */
	function hasTable($name) { /* {{{ */
		switch($this->_driver) {
			case 'mysql':
				$sql = "SELECT `TABLE_NAME` AS `name` FROM `information_schema`.`tables` WHERE `TABLE_SCHEMA`='".$this->_database."' AND `TABLE_TYPE`='BASE TABLE' AND `TABLE_NAME`=".$this->qstr($name);
				break;
			case 'sqlite':
				$sql = "SELECT tbl_name AS name FROM sqlite_master WHERE type='table' AND `tbl_name`=".$this->qstr($name);
				break;
			case 'pgsql':
				$sql = "SELECT tablename AS name FROM pg_catalog.pg_tables WHERE schemaname='public' AND tablename=".$this->qstr($name);
				break;
			default:
				return false;
		}
		$arr = $this->getResultArray($sql);
		if($arr)
			return true;
		return false;
	}	/* }}} */

	/**
	 * Return list of all database views
	 *
	 * This function is used to retrieve a list of database views
	 *
	 * @return array list of view names
	 */
	public function ViewList() { /* {{{ */
		switch($this->_driver) {
			case 'mysql':
				$sql = "select TABLE_NAME as name from information_schema.views where TABLE_SCHEMA='".$this->_database."'";
				break;
			case 'sqlite':
				$sql = "select tbl_name as name from sqlite_master where type='view'";
				break;
			case 'pgsql':
				$sql = "select viewname as name from pg_catalog.pg_views where schemaname='public'";
				break;
			default:
				return false;
		}
		$arr = $this->getResultArray($sql);
		$res = array();
		foreach($arr as $tmp)
			$res[] = $tmp['name'];
		return $res;
	}	/* }}} */

	/**
	 * Constructor of SeedDMS_Core_DatabaseAccess
	 *
	 * Sets all database parameters but does not connect.
	 *
	 * @param string $driver the database type e.g. mysql, sqlite
	 * @param string $hostname host of database server
	 * @param string $user name of user having access to database
	 * @param string $passw password of user
	 * @param bool|string $database name of database
	 */
	function __construct($driver, $hostname, $user, $passw, $database = false) { /* {{{ */
		$this->_driver = $driver;
		$tmp = explode(":", $hostname);
		$this->_hostname = $tmp[0];
		$this->_port = null;
		if(!empty($tmp[1]))
			$this->_port = $tmp[1];
		$this->_database = $database;
		$this->_user = $user;
		$this->_passw = $passw;
		$this->_connected = false;
		$this->_intransaction = 0;
		$this->_logfile = '';
		if($this->_logfile) {
			$this->_logfp = fopen($this->_logfile, 'a+');
			if($this->_logfp)
				fwrite($this->_logfp, microtime(true)."	BEGIN ".$_SERVER['REQUEST_URI']." ------------------------------------------\n");
		} else
			$this->_logfp = null;
		// $tt*****id is a hack to ensure that we do not try to create the
		// temporary table twice during a single connection. Can be fixed by
		// using Views (MySQL 5.0 onward) instead of temporary tables.
		// CREATE ... IF NOT EXISTS cannot be used because it has the
		// unpleasant side-effect of performing the insert again even if the
		// table already exists.
		//
		// See createTemporaryTable() method for implementation.
		$this->_ttreviewid = false;
		$this->_ttapproveid = false;
		$this->_ttstatid = false;
		$this->_ttcontentid = false;
		$this->_ttreceiptid = false;
		$this->_ttrevisionid = false;
		$this->_useviews = false; // turn off views, because they are much slower then temp. tables
		$this->_debug = false;
	} /* }}} */

	/**
	 * Return driver
	 *
	 * @return string name of driver as set in constructor
	 */
	public function getDriver() { /* {{{ */
		return $this->_driver;
	} /* }}} */

	/**
	 * Turn on views instead of temp. tables
	 *
	 * @param bool $onoff turn use of views instead of temp. table on/off
	 */
    function useViews($onoff) { /* {{{ */
        $this->_useviews = $onoff;
    } /* }}} */

	/**
	 * Destructor of SeedDMS_Core_DatabaseAccess
	 */
	function __destruct() { /* {{{ */
		if($this->_logfile && $this->_logfp) {
			fwrite($this->_logfp, microtime(true)."	END --------------------------------------------\n");
			fclose($this->_logfp);
		}
	} /* }}} */

	/**
	 * Set the file pointer to a log file
	 *
	 * Once it is set, all queries will be logged into this file
	 */
	function setLogFp($fp) { /* {{{ */
		$this->_logfp = $fp;
	} /* }}} */

	/**
	 * Connect to database
	 *
	 * @return boolean true if connection could be established, otherwise false
	 */
	function connect() { /* {{{ */
		switch($this->_driver) {
			case 'mysql':
			case 'mysqli':
			case 'mysqlnd':
			case 'pgsql':
				$dsn = $this->_driver.":dbname=".$this->_database.";host=".$this->_hostname;
				if($this->_port)
					$dsn .= ";port=".$this->_port;
				break;
			case 'sqlite':
				$dsn = $this->_driver.":".$this->_database;
				break;
		}
		try {
			/** @noinspection PhpUndefinedVariableInspection */
			$this->_conn = new PDO($dsn, $this->_user, $this->_passw);
			if (!$this->_conn)
				return false;

			switch($this->_driver) {
				case 'mysql':
					$this->_conn->exec('SET NAMES utf8');
//					$this->_conn->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);
					/* Turn this on if you want strict checking of default values, etc. */
					/* $this->_conn->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES'"); */
					/* The following is the default on Ubuntu 16.04 */
					/* $this->_conn->exec("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'"); */
					break;
				case 'sqlite':
					$this->_conn->exec('PRAGMA foreign_keys = ON');
					break;
			}
		} catch (Exception $e) {
			return false;
		}
		if($this->_useviews) {
			$tmp = $this->ViewList();
			foreach(array('ttreviewid', 'ttapproveid', 'ttstatid', 'ttcontentid', 'ttreceiptid', 'ttrevisionid') as $viewname) {
				if(in_array($viewname, $tmp)) {
					$this->{"_".$viewname} = true;
				}
			}
		}

		$this->_connected = true;
		return true;
	} /* }}} */

	/**
	 * Make sure a database connection exisits
	 *
	 * This function checks for a database connection. If it does not exists
	 * it will reconnect.
	 *
	 * @return boolean true if connection is established, otherwise false
	 */
	function ensureConnected() { /* {{{ */
		if (!$this->_connected) return $this->connect();
		else return true;
	} /* }}} */

	/**
	 * Sanitize String used in database operations
	 *
	 * @param string $text
	 * @return string sanitized string
	 */
	function qstr($text) { /* {{{ */
		return $this->_conn->quote($text);
	} /* }}} */

	/**
	 * Replace back ticks by '"'
	 *
	 * @param string $text
	 * @return string sanitized string
	 */
	function rbt($text) { /* {{{ */
		return str_replace('`', '"', $text);
	} /* }}} */

	/**
	 * Return sql to concat strings or fields
	 *
	 * @param array $arr list of field names or strings
	 * @return string concated string
	 */
	function concat($arr) { /* {{{ */
		switch($this->_driver) {
		case 'mysql':
			return 'concat('.implode(',', $arr).')';
			break;
		case 'pgsql':
			return implode(' || ', $arr);
			break;
		case 'sqlite':
			return implode(' || ', $arr);
			break;
		}
		return '';
	} /* }}} */

	/**
	 * Execute SQL query and return result
	 *
	 * Call this function only with sql query which return data records.
	 *
	 * @param string $queryStr sql query
	 * @param bool $retick
	 * @return array|bool data if query could be executed otherwise false
	 */
	function getResultArray($queryStr, $retick=true) { /* {{{ */
		$resArr = array();
		
		if($retick && $this->_driver == 'pgsql') {
			$queryStr = $this->rbt($queryStr);
		}

		if($this->_logfp) {
			fwrite($this->_logfp, microtime(true)."	".$queryStr."\n");
		}
		$res = $this->_conn->query($queryStr);
		if ($res === false) {
			if($this->_debug) {
				echo "error: ".$queryStr."<br />";
				print_r($this->_conn->errorInfo());
			}
			return false;
		}
		$resArr = $res->fetchAll(PDO::FETCH_ASSOC);
		
//		$res->Close();
		return $resArr;
	} /* }}} */

	/**
	 * Execute SQL query
	 *
	 * Call this function only with sql query which do not return data records.
	 *
	 * @param string $queryStr sql query
	 * @param boolean $retick replace all '`' by '"'
	 * @return boolean true if query could be executed otherwise false
	 */
	function getResult($queryStr, $retick=true) { /* {{{ */
		if($retick && $this->_driver == 'pgsql') {
			$queryStr = $this->rbt($queryStr);
		}

		if($this->_logfp) {
			fwrite($this->_logfp, microtime(true)."	".$queryStr."\n");
		}
		$res = $this->_conn->exec($queryStr);
		if($res === false) {
			if($this->_debug) {
				echo "error: ".$queryStr."<br />";
				print_r($this->_conn->errorInfo());
			}
			return false;
		} else
			return true;

		return $res;
	} /* }}} */

	function startTransaction() { /* {{{ */
		if(!$this->_intransaction) {
			$this->_conn->beginTransaction();
		}
		$this->_intransaction++;
		if($this->_logfp) {
			fwrite($this->_logfp, microtime(true)."	START ".$this->_intransaction."\n");
		}
	} /* }}} */

	function rollbackTransaction() { /* {{{ */
		if($this->_logfp) {
			fwrite($this->_logfp, microtime(true)."	ROLLBACK ".$this->_intransaction."\n");
		}
		if($this->_intransaction == 1) {
			$this->_conn->rollBack();
		}
		$this->_intransaction--;
	} /* }}} */

	function commitTransaction() { /* {{{ */
		if($this->_logfp) {
			fwrite($this->_logfp, microtime(true)."	COMMIT ".$this->_intransaction."\n");
		}
		if($this->_intransaction == 1) {
			$this->_conn->commit();
		}
		$this->_intransaction--;
	} /* }}} */

	/**
	 * Return the id of the last instert record
	 *
	 * @param string $tablename
	 * @param string $fieldname
	 * @return int id used in last autoincrement
	 */
	function getInsertID($tablename='', $fieldname='id') { /* {{{ */
		if($this->_driver == 'pgsql')
			return $this->_conn->lastInsertId('"'.$tablename.'_'.$fieldname.'_seq"');
		else
			return $this->_conn->lastInsertId();
	} /* }}} */

	function getErrorMsg() { /* {{{ */
		$info = $this->_conn->errorInfo();
		return($info[2]);
	} /* }}} */

	function getErrorNo() { /* {{{ */
		return $this->_conn->errorCode();
	} /* }}} */

	/**
	 * Create various temporary tables to speed up and simplify sql queries
	 *
	 * @param string $tableName
	 * @param bool $override
	 * @return bool
	 */
	private function __createTemporaryTable($tableName, $override=false) { /* {{{ */
		if (!strcasecmp($tableName, "ttreviewid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttreviewid` AS ".
						"SELECT `tblDocumentReviewLog`.`reviewID` AS `reviewID`, ".
						"MAX(`tblDocumentReviewLog`.`reviewLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReviewLog` ".
						"GROUP BY `tblDocumentReviewLog`.`reviewID` "; //.
//						"ORDER BY `maxLogID`";
				break;
				case 'pgsql':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttreviewid` (`reviewID` INTEGER, `maxLogID` INTEGER, PRIMARY KEY (`reviewID`));".
						"INSERT INTO `ttreviewid` SELECT `tblDocumentReviewLog`.`reviewID`, ".
						"MAX(`tblDocumentReviewLog`.`reviewLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReviewLog` ".
						"GROUP BY `tblDocumentReviewLog`.`reviewID` ";//.
//						"ORDER BY `maxLogID`";
				break;
				default:
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttreviewid` (PRIMARY KEY (`reviewID`), INDEX (`maxLogID`)) ".
						"SELECT `tblDocumentReviewLog`.`reviewID`, ".
						"MAX(`tblDocumentReviewLog`.`reviewLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReviewLog` ".
						"GROUP BY `tblDocumentReviewLog`.`reviewID` "; //.
//						"ORDER BY `maxLogID`";
			}
			if (!$this->_ttreviewid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttreviewid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP TABLE IF EXISTS `ttreviewid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttreviewid;
		}
		elseif (!strcasecmp($tableName, "ttapproveid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttapproveid` AS ".
						"SELECT `tblDocumentApproveLog`.`approveID` AS `approveID`, ".
						"MAX(`tblDocumentApproveLog`.`approveLogID`) AS `maxLogID` ".
						"FROM `tblDocumentApproveLog` ".
						"GROUP BY `tblDocumentApproveLog`.`approveID` "; //.
//						"ORDER BY `maxLogID`";
					break;
				case 'pgsql':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttapproveid` (`approveID` INTEGER, `maxLogID` INTEGER, PRIMARY KEY (`approveID`));".
						"INSERT INTO `ttapproveid` SELECT `tblDocumentApproveLog`.`approveID`, ".
						"MAX(`tblDocumentApproveLog`.`approveLogID`) AS `maxLogID` ".
						"FROM `tblDocumentApproveLog` ".
						"GROUP BY `tblDocumentApproveLog`.`approveID` "; //.
//						"ORDER BY `maxLogID`";
					break;
				default:
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttapproveid` (PRIMARY KEY (`approveID`), INDEX (`maxLogID`)) ".
						"SELECT `tblDocumentApproveLog`.`approveID`, ".
						"MAX(`tblDocumentApproveLog`.`approveLogID`) AS `maxLogID` ".
						"FROM `tblDocumentApproveLog` ".
						"GROUP BY `tblDocumentApproveLog`.`approveID` "; //.
//						"ORDER BY `maxLogID`";
			}
			if (!$this->_ttapproveid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttapproveid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP TABLE IF NOT EXISTS `ttapproveid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttapproveid;
		}
		elseif (!strcasecmp($tableName, "ttstatid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttstatid` AS ".
						"SELECT `tblDocumentStatusLog`.`statusID` AS `statusID`, ".
						"MAX(`tblDocumentStatusLog`.`statusLogID`) AS `maxLogID` ".
						"FROM `tblDocumentStatusLog` ".
						"GROUP BY `tblDocumentStatusLog`.`statusID` "; //.
//						"ORDER BY `maxLogID`";
					break;
				case 'pgsql':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttstatid` (`statusID` INTEGER, `maxLogID` INTEGER, PRIMARY KEY (`statusID`));".
						"INSERT INTO `ttstatid` SELECT `tblDocumentStatusLog`.`statusID`, ".
						"MAX(`tblDocumentStatusLog`.`statusLogID`) AS `maxLogID` ".
						"FROM `tblDocumentStatusLog` ".
						"GROUP BY `tblDocumentStatusLog`.`statusID` "; //.
//						"ORDER BY `maxLogID`";
					break;
				default:
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttstatid` (PRIMARY KEY (`statusID`), INDEX (`maxLogID`)) ".
						"SELECT `tblDocumentStatusLog`.`statusID`, ".
						"MAX(`tblDocumentStatusLog`.`statusLogID`) AS `maxLogID` ".
						"FROM `tblDocumentStatusLog` ".
						"GROUP BY `tblDocumentStatusLog`.`statusID` "; //.
//						"ORDER BY `maxLogID`";
			}
			if (!$this->_ttstatid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttstatid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP TABLE IF NOT EXISTS `ttstatid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttstatid;
		}
		elseif (!strcasecmp($tableName, "ttcontentid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttcontentid` AS ".
						"SELECT `tblDocumentContent`.`document` AS `document`, ".
						"MAX(`tblDocumentContent`.`version`) AS `maxVersion` ".
						"FROM `tblDocumentContent` ".
						"GROUP BY `tblDocumentContent`.`document` ".
						"ORDER BY `tblDocumentContent`.`document`";
					break;
				case 'pgsql':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttcontentid` (`document` INTEGER, `maxVersion` INTEGER, PRIMARY KEY (`document`)); ".
						"INSERT INTO `ttcontentid` SELECT `tblDocumentContent`.`document` AS `document`, ".
						"MAX(`tblDocumentContent`.`version`) AS `maxVersion` ".
						"FROM `tblDocumentContent` ".
						"GROUP BY `tblDocumentContent`.`document` ".
						"ORDER BY `tblDocumentContent`.`document`";
					break;
				default:
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttcontentid` (PRIMARY KEY (`document`), INDEX (`maxVersion`)) ".
						"SELECT `tblDocumentContent`.`document`, ".
						"MAX(`tblDocumentContent`.`version`) AS `maxVersion` ".
						"FROM `tblDocumentContent` ".
						"GROUP BY `tblDocumentContent`.`document` ".
						"ORDER BY `tblDocumentContent`.`document`";
			}
			if (!$this->_ttcontentid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttcontentid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP TABLE IF NOT EXISTS `ttcontentid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttcontentid;
		}
		elseif (!strcasecmp($tableName, "ttreceiptid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttreceiptid` AS ".
						"SELECT `tblDocumentReceiptLog`.`receiptID` AS `receiptID`, ".
						"MAX(`tblDocumentReceiptLog`.`receiptLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReceiptLog` ".
						"GROUP BY `tblDocumentReceiptLog`.`receiptID` ";
//						"ORDER BY `maxLogID`";
				break;
				case 'pgsql':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttreceiptid` (`receiptID` INTEGER, `maxLogID` INTEGER, PRIMARY KEY (`receiptID`));".
						"INSERT INTO `ttreceiptid` SELECT `tblDocumentReceiptLog`.`receiptID`, ".
						"MAX(`tblDocumentReceiptLog`.`receiptLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReceiptLog` ".
						"GROUP BY `tblDocumentReceiptLog`.`receiptID` ";
//						"ORDER BY `maxLogID`";
				break;
				default:
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttreceiptid` (PRIMARY KEY (`receiptID`), INDEX (`maxLogID`)) ".
						"SELECT `tblDocumentReceiptLog`.`receiptID`, ".
						"MAX(`tblDocumentReceiptLog`.`receiptLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReceiptLog` ".
						"GROUP BY `tblDocumentReceiptLog`.`receiptID` ";
//						"ORDER BY `maxLogID`";
			}
			if (!$this->_ttreceiptid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttreceiptid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DELETE FROM `ttreceiptid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttreceiptid;
		}
		elseif (!strcasecmp($tableName, "ttrevisionid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttrevisionid` AS ".
						"SELECT `tblDocumentRevisionLog`.`revisionID` AS `revisionID`, ".
						"MAX(`tblDocumentRevisionLog`.`revisionLogID`) AS `maxLogID` ".
						"FROM `tblDocumentRevisionLog` ".
						"GROUP BY `tblDocumentRevisionLog`.`revisionID` ";
//						"ORDER BY `maxLogID`";
				break;
				case 'pgsql':
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttrevisionid` (`revisionID` INTEGER, `maxLogID` INTEGER, PRIMARY KEY (`revisionID`));".
						"INSERT INTO `ttrevisionid` SELECT `tblDocumentRevisionLog`.`revisionID`, ".
						"MAX(`tblDocumentRevisionLog`.`revisionLogID`) AS `maxLogID` ".
						"FROM `tblDocumentRevisionLog` ".
						"GROUP BY `tblDocumentRevisionLog`.`revisionID` ";
//						"ORDER BY `maxLogID`";
				break;
				default:
					$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttrevisionid` (PRIMARY KEY (`revisionID`), INDEX (`maxLogID`)) ".
						"SELECT `tblDocumentRevisionLog`.`revisionID`, ".
						"MAX(`tblDocumentRevisionLog`.`revisionLogID`) AS `maxLogID` ".
						"FROM `tblDocumentRevisionLog` ".
						"GROUP BY `tblDocumentRevisionLog`.`revisionID` ";
//						"ORDER BY `maxLogID`";
			}
			if (!$this->_ttrevisionid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttrevisionid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DELETE FROM `ttrevisionid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttrevisionid;
		}
		return false;
	} /* }}} */

	/**
	 * Drop various temporary tables to enforce recreation when needed
	 *
	 * @param string $tableName
     *
	 * @return bool
	 */
    private function __dropTemporaryTable($tableName) { /* {{{ */
        $queryStr = '';
        if (!strcasecmp($tableName, "ttreviewid")) {
            $queryStr = "DROP TABLE IF EXISTS `ttreviewid`";
		}
		elseif (!strcasecmp($tableName, "ttapproveid")) {
            $queryStr = "DROP TABLE IF EXISTS `ttapproveid`";
		}
		elseif (!strcasecmp($tableName, "ttstatid")) {
            $queryStr = "DROP TABLE IF EXISTS `ttstatid`";
		}
		elseif (!strcasecmp($tableName, "ttcontentid")) {
            $queryStr = "DROP TABLE IF EXISTS `ttcontentid`";
        }
        if($queryStr) {
			if (!$this->getResult($queryStr))
                return false;
            else {
                $this->{'_'.$tableName} = false;
                return true;
            }
        }
		return false;
	} /* }}} */

	/**
	 * Create various views to speed up and simplify sql queries
	 *
	 * @param string $tableName
	 * @param bool $override
     *
	 * @return bool
	 */
	private function __createView($tableName, $override=false) { /* {{{ */
		if (!strcasecmp($tableName, "ttreviewid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE VIEW `ttreviewid` AS ".
						"SELECT `tblDocumentReviewLog`.`reviewID` AS `reviewID`, ".
						"MAX(`tblDocumentReviewLog`.`reviewLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReviewLog` ".
						"GROUP BY `tblDocumentReviewLog`.`reviewID` "; //.
				break;
				case 'pgsql':
					$queryStr = "CREATE VIEW `ttreviewid` AS ".
						"SELECT `tblDocumentReviewLog`.`reviewID` AS `reviewID`, ".
						"MAX(`tblDocumentReviewLog`.`reviewLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReviewLog` ".
						"GROUP BY `tblDocumentReviewLog`.`reviewID` ";
				break;
				default:
					$queryStr = "CREATE".($override ? " OR REPLACE" : "")." VIEW `ttreviewid` AS ".
						"SELECT `tblDocumentReviewLog`.`reviewID` AS `reviewID`, ".
						"MAX(`tblDocumentReviewLog`.`reviewLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReviewLog` ".
						"GROUP BY `tblDocumentReviewLog`.`reviewID` ";
			}
			if (!$this->_ttreviewid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttreviewid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP VIEW `ttreviewid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttreviewid;
		}
		elseif (!strcasecmp($tableName, "ttapproveid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE VIEW `ttapproveid` AS ".
						"SELECT `tblDocumentApproveLog`.`approveID` AS `approveID`, ".
						"MAX(`tblDocumentApproveLog`.`approveLogID`) AS `maxLogID` ".
						"FROM `tblDocumentApproveLog` ".
						"GROUP BY `tblDocumentApproveLog`.`approveID` "; //.
					break;
				case 'pgsql':
					$queryStr = "CREATE VIEW `ttapproveid` AS ".
						"SELECT `tblDocumentApproveLog`.`approveID` AS `approveID`, ".
						"MAX(`tblDocumentApproveLog`.`approveLogID`) AS `maxLogID` ".
						"FROM `tblDocumentApproveLog` ".
						"GROUP BY `tblDocumentApproveLog`.`approveID` ";
					break;
				default:
					$queryStr = "CREATE".($override ? " OR REPLACE" : "")." VIEW `ttapproveid` AS ".
						"SELECT `tblDocumentApproveLog`.`approveID`, ".
						"MAX(`tblDocumentApproveLog`.`approveLogID`) AS `maxLogID` ".
						"FROM `tblDocumentApproveLog` ".
						"GROUP BY `tblDocumentApproveLog`.`approveID` ";
			}
			if (!$this->_ttapproveid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttapproveid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP VIEW `ttapproveid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttapproveid;
		}
		elseif (!strcasecmp($tableName, "ttstatid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE VIEW `ttstatid` AS ".
						"SELECT `tblDocumentStatusLog`.`statusID` AS `statusID`, ".
						"MAX(`tblDocumentStatusLog`.`statusLogID`) AS `maxLogID` ".
						"FROM `tblDocumentStatusLog` ".
						"GROUP BY `tblDocumentStatusLog`.`statusID` ";
					break;
				case 'pgsql':
					$queryStr = "CREATE VIEW `ttstatid` AS ".
						"SELECT `tblDocumentStatusLog`.`statusID` AS `statusID`, ".
						"MAX(`tblDocumentStatusLog`.`statusLogID`) AS `maxLogID` ".
						"FROM `tblDocumentStatusLog` ".
						"GROUP BY `tblDocumentStatusLog`.`statusID` ";
					break;
				default:
					$queryStr = "CREATE".($override ? " OR REPLACE" : "")." VIEW `ttstatid` AS ".
						"SELECT `tblDocumentStatusLog`.`statusID`, ".
						"MAX(`tblDocumentStatusLog`.`statusLogID`) AS `maxLogID` ".
						"FROM `tblDocumentStatusLog` ".
						"GROUP BY `tblDocumentStatusLog`.`statusID` ";
			}
			if (!$this->_ttstatid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttstatid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP VIEW `ttstatid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttstatid;
		}
		elseif (!strcasecmp($tableName, "ttcontentid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE VIEW `ttcontentid` AS ".
						"SELECT `tblDocumentContent`.`document` AS `document`, ".
						"MAX(`tblDocumentContent`.`version`) AS `maxVersion` ".
						"FROM `tblDocumentContent` ".
						"GROUP BY `tblDocumentContent`.`document` ".
						"ORDER BY `tblDocumentContent`.`document`";
					break;
				case 'pgsql':
					$queryStr = "CREATE VIEW `ttcontentid` AS ".
						"SELECT `tblDocumentContent`.`document` AS `document`, ".
						"MAX(`tblDocumentContent`.`version`) AS `maxVersion` ".
						"FROM `tblDocumentContent` ".
						"GROUP BY `tblDocumentContent`.`document` ".
						"ORDER BY `tblDocumentContent`.`document`";
					break;
				default:
					$queryStr = "CREATE".($override ? " OR REPLACE" : "")." VIEW `ttcontentid` AS ".
						"SELECT `tblDocumentContent`.`document`, ".
						"MAX(`tblDocumentContent`.`version`) AS `maxVersion` ".
						"FROM `tblDocumentContent` ".
						"GROUP BY `tblDocumentContent`.`document` ".
						"ORDER BY `tblDocumentContent`.`document`";
			}
			if (!$this->_ttcontentid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttcontentid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP VIEW `ttcontentid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttcontentid;
		}
		elseif (!strcasecmp($tableName, "ttreceiptid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE VIEW `ttreceiptid` AS ".
						"SELECT `tblDocumentReceiptLog`.`receiptID` AS `receiptID`, ".
						"MAX(`tblDocumentReceiptLog`.`receiptLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReceiptLog` ".
						"GROUP BY `tblDocumentReceiptLog`.`receiptID` ";
				break;
				case 'pgsql':
					$queryStr = "CREATE VIEW `ttreceiptid` AS ".
						"SELECT `tblDocumentReceiptLog`.`receiptID` AS `receiptID`, ".
						"MAX(`tblDocumentReceiptLog`.`receiptLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReceiptLog` ".
						"GROUP BY `tblDocumentReceiptLog`.`receiptID` ";
				break;
				default:
					$queryStr = "CREATE".($override ? " OR REPLACE" : "")." VIEW `ttreceiptid` AS ".
						"SELECT `tblDocumentReceiptLog`.`receiptID`, ".
						"MAX(`tblDocumentReceiptLog`.`receiptLogID`) AS `maxLogID` ".
						"FROM `tblDocumentReceiptLog` ".
						"GROUP BY `tblDocumentReceiptLog`.`receiptID` ";
			}
			if (!$this->_ttreceiptid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttreceiptid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP VIEW `ttreceiptid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttreceiptid;
		}
		elseif (!strcasecmp($tableName, "ttrevisionid")) {
			switch($this->_driver) {
				case 'sqlite':
					$queryStr = "CREATE VIEW `ttrevisionid` AS ".
						"SELECT `tblDocumentRevisionLog`.`revisionID` AS `revisionID`, ".
						"MAX(`tblDocumentRevisionLog`.`revisionLogID`) AS `maxLogID` ".
						"FROM `tblDocumentRevisionLog` ".
						"GROUP BY `tblDocumentRevisionLog`.`revisionID` ";
				break;
				case 'pgsql':
					$queryStr = "CREATE VIEW `ttrevisionid` AS ".
						"SELECT `tblDocumentRevisionLog`.`revisionID` AS `revisionID`, ".
						"MAX(`tblDocumentRevisionLog`.`revisionLogID`) AS `maxLogID` ".
						"FROM `tblDocumentRevisionLog` ".
						"GROUP BY `tblDocumentRevisionLog`.`revisionID` ";
				break;
				default:
					$queryStr = "CREATE".($override ? " OR REPLACE" : "")." VIEW `ttrevisionid` AS ".
						"SELECT `tblDocumentRevisionLog`.`revisionID`, ".
						"MAX(`tblDocumentRevisionLog`.`revisionLogID`) AS `maxLogID` ".
						"FROM `tblDocumentRevisionLog` ".
						"GROUP BY `tblDocumentRevisionLog`.`revisionID` ";
			}
			if (!$this->_ttrevisionid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttrevisionid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DROP VIEW `ttrevisionid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttrevisionid;
		}
		return false;
	} /* }}} */

	/**
	 * Create various temporary tables or view to speed up and simplify sql queries
	 *
	 * @param string $tableName
     * @param bool $override
     *
	 * @return bool
	 */
	public function createTemporaryTable($tableName, $override=false) { /* {{{ */
		if($this->_useviews)
			return $this->__createView($tableName, $override);
		else
			return $this->__createTemporaryTable($tableName, $override);
	} /* }}} */

	/**
	 * Drop various temporary tables to force recreation when next time needed
	 *
     * @param string $tableName
     *
	 * @return bool
	 */
	public function dropTemporaryTable($tableName) { /* {{{ */
		if($this->_useviews)
			return true; // No need to recreate a view
		else
			return $this->__dropTemporaryTable($tableName);
	} /* }}} */

	/**
	 * Return sql statement for extracting the date part from a field
	 * containing a unix timestamp
	 *
	 * @param string $fieldname name of field containing the timestamp
	 * @param string $format
	 * @return string sql code
	 */
	function getDateExtract($fieldname, $format='%Y-%m-%d') { /* {{{ */
		switch($this->_driver) {
			case 'mysql':
				return "from_unixtime(`".$fieldname."`, ".$this->qstr($format).")";
				break;
			case 'sqlite':
				return "strftime(".$this->qstr($format).", `".$fieldname."`, 'unixepoch')";
				break;
			case 'pgsql':
				switch($format) {
				case '%Y-%m':
					return "to_char(to_timestamp(`".$fieldname."`), 'YYYY-MM')";
					break;
				default:
					return "to_char(to_timestamp(`".$fieldname."`), 'YYYY-MM-DD')";
					break;
				}
				break;
		}
		return '';
	} /* }}} */

	/**
	 * Return sql statement for returning the current date and time
	 * in format Y-m-d H:i:s
	 *
	 * @return string sql code
	 */
	function getCurrentDatetime($dayoffset=0) { /* {{{ */
		switch($this->_driver) {
			case 'mysql':
				if($dayoffset)
					return "DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ".$dayoffset." DAY)";
				else
					return "CURRENT_TIMESTAMP";
				break;
			case 'sqlite':
				if($dayoffset)
					return "datetime('now', '".$dayoffset." days', 'localtime')";
				else
					return "datetime('now', 'localtime')";
				break;
			case 'pgsql':
				if($dayoffset)
					return "now() + interval '".$dayoffset." day'";
				else
					return "now()";
				break;
		}
		return '';
	} /* }}} */

	/**
	 * Return sql statement for returning the current timestamp
	 *
	 * @return string sql code
	 */
	function getCurrentTimestamp() { /* {{{ */
		switch($this->_driver) {
			case 'mysql':
				return "UNIX_TIMESTAMP()";
				break;
			case 'sqlite':
				return "strftime('%s', 'now')";
				break;
			case 'pgsql':
				return "date_part('epoch',CURRENT_TIMESTAMP)::int";
				break;
		}
		return '';
	} /* }}} */

	/**
	 * Return sql statement for returning the current timestamp
	 *
	 * @param $field
	 * @return string sql code
	 */
	function castToText($field) { /* {{{ */
		switch($this->_driver) {
			case 'pgsql':
				return $field."::TEXT";
				break;
		}
		return $field;
	} /* }}} */

	/**
	 * Create an sql dump of the complete database
	 *
	 * @param resource $fp name of dump file
	 * @return bool
	 */
	function createDump($fp) { /* {{{ */
		$tables = $this->TableList('TABLES');
        foreach($tables as $table) {
            if($table == 'sqlite_sequence')
                continue;
			$query = "SELECT * FROM `".$table."`";
			$records = $this->getResultArray($query);
			fwrite($fp,"\n-- TABLE: ".$table."--\n\n");
			foreach($records as $record) {
				$values="";
				$i = 1;
				foreach ($record as $column) {
					if (is_numeric($column)) $values .= $column;
					else $values .= $this->qstr($column);

					if ($i<(count($record))) $values .= ",";
					$i++;
				}

				fwrite($fp, "INSERT INTO `".$table."` VALUES (".$values.");\n");
			}
		}
		return true;
	} /* }}} */
}
