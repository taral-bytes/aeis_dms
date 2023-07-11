<?php
/**
 * Implementation of the low level database tests
 *
 * PHP version 7
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   @package_version@
 * @link      https://www.seeddms.org
 */

namespace PHPUnit\Framework;

use PHPUnit\Framework\SeedDmsTest;

require_once('SeedDmsBase.php');

/**
 * Low level Database test class
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: @package_version@
 * @link      https://www.seeddms.org
 */
class DatabaseTest extends SeedDmsTest
{

    /**
     * Create a sqlite database in memory
     *
     * @return void
     */
    protected function setUp(): void
    {
        self::$dbh = self::createInMemoryDatabase();
    }

    /**
     * Clean up at tear down
     *
     * @return void
     */
    protected function tearDown(): void
    {
        self::$dbh = null;
    }

    /**
     * Check if connection to database exists
     *
     * @return void
     */
    public function testIsConnected()
    {
        $this->assertTrue(self::$dbh->ensureConnected());
    }

    /**
     * Test for number of tables in database
     *
     * @return void
     */
    public function testTableList()
    {
        $tablelist = self::$dbh->TableList();
        $this->assertIsArray($tablelist);
        // There are just 42 tables in SeedDMS5 and 55 tables in SeedDMS6,
        // but one additional
        // table 'sqlite_sequence'
        $dms = new \SeedDMS_Core_DMS(null, '');
        if($dms->version[0] == '5')
            $this->assertCount(43, $tablelist);
        else
            $this->assertCount(56, $tablelist);
    }

    /**
     * Test createTemporaryTable()
     *
     * @return void
     */
    public function testCreateTemporaryTable()
    {
        foreach (['ttreviewid', 'ttapproveid', 'ttstatid', 'ttcontentid'] as $temp) {
            $ret = self::$dbh->createTemporaryTable($temp);
            $rec = self::$dbh->getResultArray("SELECT * FROM `".$temp."`");
            $this->assertIsArray($rec);
        }
        /* Running it again will not harm */
        foreach (['ttreviewid', 'ttapproveid', 'ttstatid', 'ttcontentid'] as $temp) {
            $ret = self::$dbh->createTemporaryTable($temp);
            $rec = self::$dbh->getResultArray("SELECT * FROM `".$temp."`");
            $this->assertIsArray($rec);
        }
        /* Running it again and overwrite the old table contents */
        foreach (['ttreviewid', 'ttapproveid', 'ttstatid', 'ttcontentid'] as $temp) {
            $ret = self::$dbh->createTemporaryTable($temp, true);
            $rec = self::$dbh->getResultArray("SELECT * FROM `".$temp."`");
            $this->assertIsArray($rec);
        }
    }

    /**
     * Test createTemporaryTable() based on views
     *
     * @return void
     */
    public function testCreateTemporaryTableBasedOnViews()
    {
        self::$dbh->useViews(true);
        foreach (['ttreviewid', 'ttapproveid', 'ttstatid', 'ttcontentid'] as $temp) {
            $ret = self::$dbh->createTemporaryTable($temp);
            $rec = self::$dbh->getResultArray("SELECT * FROM `".$temp."`");
            $this->assertIsArray($rec);
        }
        $viewlist = self::$dbh->ViewList();
        $this->assertIsArray($viewlist);
        $this->assertCount(4, $viewlist);

        /* Running it again will not harm */
        foreach (['ttreviewid', 'ttapproveid', 'ttstatid', 'ttcontentid'] as $temp) {
            $ret = self::$dbh->createTemporaryTable($temp);
            $rec = self::$dbh->getResultArray("SELECT * FROM `".$temp."`");
            $this->assertIsArray($rec);
        }
        /* Running it again and replace the old view */
        foreach (['ttreviewid', 'ttapproveid', 'ttstatid', 'ttcontentid'] as $temp) {
            $ret = self::$dbh->createTemporaryTable($temp, true);
            $rec = self::$dbh->getResultArray("SELECT * FROM `".$temp."`");
            $this->assertIsArray($rec);
        }
    }

    /**
     * Test for number of views in database
     *
     * @return void
     */
    public function testViewList()
    {
        $viewlist = self::$dbh->ViewList();
        $this->assertIsArray($viewlist);
        // There are 0 views
        $this->assertCount(0, $viewlist);
    }

    /**
     * Test getDriver()
     *
     * @return void
     */
    public function testGetDriver()
    {
        $driver = self::$dbh->getDriver();
        $this->assertEquals('sqlite', $driver);
    }

    /**
     * Test rbt()
     *
     * @return void
     */
    public function testRbt()
    {
        $str = self::$dbh->rbt("SELECT * FROM `tblUsers`");
        $this->assertEquals('SELECT * FROM "tblUsers"', $str);
    }

    /**
     * Test if table tblFolders has root folder
     *
     * @return void
     */
    public function testInitialRootFolder()
    {
        $this->assertTrue(self::$dbh->hasTable('tblFolders'));
        $query = 'SELECT * FROM `tblFolders`';
        $recs = self::$dbh->getResultArray($query);
        $this->assertIsArray($recs);
        $this->assertCount(1, $recs);
    }

    /**
     * Test if table tblUsers has two initial users
     *
     * @return void
     */
    public function testInitialUsers()
    {
        $this->assertTrue(self::$dbh->hasTable('tblUsers'));
        $query = 'SELECT * FROM `tblUsers`';
        $recs = self::$dbh->getResultArray($query);
        $this->assertIsArray($recs);
        $this->assertCount(2, $recs);
    }

    /**
     * Test getCurrentDatetime()
     *
     * @return void
     */
    public function testGetCurrentDatetime()
    {
        $query = 'SELECT '.self::$dbh->getCurrentDatetime().' as a';
        $recs = self::$dbh->getResultArray($query);
        $now = date('Y-m-d H:i:s');
        $this->assertIsArray($recs);
        $this->assertEquals($now, $recs[0]['a'], 'Make sure php.ini has the proper timezone configured');
    }

    /**
     * Test getCurrentTimestamp()
     *
     * @return void
     */
    public function testGetCurrentTimestamp()
    {
        $query = 'SELECT '.self::$dbh->getCurrentTimestamp().' as a';
        $recs = self::$dbh->getResultArray($query);
        $now = time();
        $this->assertIsArray($recs);
        $this->assertEquals($now, $recs[0]['a'], 'Make sure php.ini has the proper timezone configured');
    }

    /**
     * Test concat()
     *
     * @return void
     */
    public function testConcat()
    {
        $query = 'SELECT '.self::$dbh->concat(["'foo'", "'baz'", "'bar'"]).' as a';
        $recs = self::$dbh->getResultArray($query);
        $this->assertIsArray($recs);
        $this->assertEquals('foobazbar', $recs[0]['a']);
    }

    /**
     * Test qstr()
     *
     * @return void
     */
    public function testQstr()
    {
        $str = self::$dbh->qstr("bar");
        $this->assertEquals("'bar'", $str);
    }

    /**
     * Test getResult() if the sql fails
     *
     * @return void
     */
    public function testGetResultSqlFail()
    {
        $ret = self::$dbh->getResult("UPDATE FOO SET `name`='foo'");
        $this->assertFalse($ret);
        $errmsg = self::$dbh->getErrorMsg();
        $this->assertStringContainsString('no such table: FOO', $errmsg);
    }

    /**
     * Test getResultArray() if the sql fails
     *
     * @return void
     */
    public function testGetResultArraySqlFail()
    {
        $ret = self::$dbh->getResultArray("SELECT * FROM FOO");
        $this->assertFalse($ret);
        $errmsg = self::$dbh->getErrorMsg();
        $this->assertStringContainsString('no such table: FOO', $errmsg);
    }

    /**
     * Test logging into file
     *
     * @return void
     */
    public function testLogging()
    {
        $fp = fopen('php://memory', 'r+');
        self::$dbh->setLogFp($fp);
        $sql = "SELECT * FROM `tblUsers`";
        $ret = self::$dbh->getResultArray($sql);
        $this->assertIsArray($ret);
        fseek($fp, 0);
        $contents = fread($fp, 200);
        /* Check if sql statement was logged into file */
        $this->assertStringContainsString($sql, $contents);
        fclose($fp);
    }

    /**
     * Test createDump()
     *
     * @return void
     */
    public function testCreateDump()
    {
        $fp = fopen('php://memory', 'r+');
        $ret = self::$dbh->createDump($fp);
        $this->assertTrue($ret);
        $stat = fstat($fp);
        $this->assertIsArray($stat);
        $dms = new \SeedDMS_Core_DMS(null, '');
        if($dms->version[0] == '5')
            $this->assertEquals(1724, $stat['size']);
        else
            $this->assertEquals(2272, $stat['size']);
//        fseek($fp, 0);
//        echo fread($fp, 200);
        fclose($fp);
    }
}

