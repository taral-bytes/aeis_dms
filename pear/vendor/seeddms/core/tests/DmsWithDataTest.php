<?php
/**
 * Implementation of the complex dms tests
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

use PHPUnit\Framework\SeedDmsTest;

/**
 * DMS test class
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: @package_version@
 * @link      https://www.seeddms.org
 */
class DmsWithDataTest extends SeedDmsTest
{

    /**
     * Create a real sqlite database in memory
     *
     * @return void
     */
    protected function setUp(): void
    {
        self::$dbh = self::createInMemoryDatabase();
        self::$contentdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpunit-'.time();
        mkdir(self::$contentdir);
        //      echo "Creating temp content dir: ".self::$contentdir."\n";
        self::$dms = new SeedDMS_Core_DMS(self::$dbh, self::$contentdir);
    }

    /**
     * Clean up at tear down
     *
     * @return void
     */
    protected function tearDown(): void
    {
        self::$dbh = null;
        //      echo "\nRemoving temp. content dir: ".self::$contentdir."\n";
        exec('rm -rf '.self::$contentdir);
    }

    /**
     * Test getFoldersMinMax()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetFoldersMinMax()
    {
        self::createSimpleFolderStructure();
        $rootfolder = self::$dms->getRootFolder();
        $minmax = $rootfolder->getFoldersMinMax();
        $this->assertIsArray($minmax);
        $this->assertCount(2, $minmax);
        $this->assertEquals(0.5, $minmax['min']);
        $this->assertEquals(2.0, $minmax['max']);
    }

    /**
     * Test method getFoldersMinMax()
     *
     * @return void
     */
    public function testGetFoldersMinMaxSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT min(`sequence`) AS `min`, max(`sequence`) AS `max` FROM `tblFolders`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->getFoldersMinMax());
    }

    /**
     * Test getDocumentsMinMax()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentsMinMax()
    {
        self::createSimpleFolderStructureWithDocuments();
        $subfolder = self::$dms->getFolderByName('Subfolder 1');
        $this->assertIsObject($subfolder);
        $minmax = $subfolder->getDocumentsMinMax();
        $this->assertIsArray($minmax);
        $this->assertCount(2, $minmax);
        $this->assertEquals(2.0, $minmax['min']);
        $this->assertEquals(16.0, $minmax['max']);
    }

    /**
     * Test method getDocumentsMinMax()
     *
     * @return void
     */
    public function testGetDocumentsMinMaxSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT min(`sequence`) AS `min`, max(`sequence`) AS `max` FROM `tblDocuments`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->getDocumentsMinMax());
    }

    /**
     * Test addDocument()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddDocument()
    {
        self::createSimpleFolderStructure();
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $rootfolder);
        $this->assertEquals(1, $rootfolder->getId());
        /* Add a new document */
        $filename = self::createTempFile(200);
        list($document, $res) = $rootfolder->addDocument(
            'Document 1', // name
            '', // comment
            null, // expiration
            $user, // owner
            '', // keywords
            [], // categories
            $filename, // name of file
            'file1.txt', // original file name
            '.txt', // file type
            'text/plain', // mime type
            1.0 // sequence
        );
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        $this->assertIsObject($document);
        $this->assertInstanceOf(SeedDMS_Core_Document::class, $document);
        $this->assertEquals('Document 1', $document->getName());
    }

    /**
     * Test getDocumentsExpired()
     *
     * Create two documents which will expired today and tomorrow
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentsExpiredFuture()
    {
        self::createSimpleFolderStructure();
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $rootfolder);
        $this->assertEquals(1, $rootfolder->getId());
        /* Add a new document */
        $filename = self::createTempFile(200);
        list($document, $res) = $rootfolder->addDocument(
            'Document 1', // name
            '', // comment
            mktime(23,59,59), // expiration is still today at 23:59:59
            $user, // owner
            '', // keywords
            [], // categories
            $filename, // name of file
            'file1.txt', // original file name
            '.txt', // file type
            'text/plain', // mime type
            1.0 // sequence
        );
        $this->assertIsObject($document);
        list($document, $res) = $rootfolder->addDocument(
            'Document 2', // name
            '', // comment
            mktime(23,59,59)+1, // expiration is tomorrow today at 0:00:00
            $user, // owner
            '', // keywords
            [], // categories
            $filename, // name of file
            'file1.txt', // original file name
            '.txt', // file type
            'text/plain', // mime type
            1.0 // sequence
        );
        $this->assertIsObject($document);
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        $documents = self::$dms->getDocumentsExpired(0); /* Docs expire today */
        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        $documents = self::$dms->getDocumentsExpired(date('Y-m-d')); /* Docs expire today */
        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        $documents = self::$dms->getDocumentsExpired(1); /* Docs expire till tomorrow 23:59:59 */
        $this->assertIsArray($documents);
        $this->assertCount(2, $documents);
        $documents = self::$dms->getDocumentsExpired(date('Y-m-d', time()+86400)); /* Docs expire till tomorrow 23:59:59 */
        $this->assertIsArray($documents);
        $this->assertCount(2, $documents);
        $documents = self::$dms->getDocumentsExpired(date('Y-m-d', time()+86400), $user); /* Docs expire till tomorrow 23:59:59 owned by $user */
        $this->assertIsArray($documents);
        $this->assertCount(2, $documents);
    }

    /**
     * Test getDocumentsExpired()
     *
     * Create two documents which have expired yesterday and the day before
     * yesterday
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentsExpiredPast()
    {
        self::createSimpleFolderStructure();
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $rootfolder);
        $this->assertEquals(1, $rootfolder->getId());
        /* Add a new document */
        $filename = self::createTempFile(200);
        list($document, $res) = $rootfolder->addDocument(
            'Document 1', // name
            '', // comment
            mktime(0,0,0)-1, // expiration was yesterday
            $user, // owner
            '', // keywords
            [], // categories
            $filename, // name of file
            'file1.txt', // original file name
            '.txt', // file type
            'text/plain', // mime type
            1.0 // sequence
        );
        $this->assertIsObject($document);
        list($document, $res) = $rootfolder->addDocument(
            'Document 2', // name
            '', // comment
            mktime(0,0,0)-1-86400, // expiration the day before yesterday
            $user, // owner
            '', // keywords
            [], // categories
            $filename, // name of file
            'file1.txt', // original file name
            '.txt', // file type
            'text/plain', // mime type
            1.0 // sequence
        );
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        $this->assertIsObject($document);
        $documents = self::$dms->getDocumentsExpired(0); /* No Docs expire today */
        $this->assertIsArray($documents);
        $this->assertCount(0, $documents);
        $documents = self::$dms->getDocumentsExpired(-1); /* Docs expired yesterday */
        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        $documents = self::$dms->getDocumentsExpired(-2); /* Docs expired since the day before yesterday */
        $this->assertIsArray($documents);
        $this->assertCount(2, $documents);
    }


}
