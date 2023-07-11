<?php
/**
 * Implementation of the document file tests
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
 * Group test class
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: @package_version@
 * @link      https://www.seeddms.org
 */
class DocumentFileTest extends SeedDmsTest
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
        self::$dbversion = self::$dms->getDBVersion();
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
     * Test method getInstance()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetMockedDocumentFile()
    {
        $user = self::getMockedUser();
        $document1 = self::getMockedDocument(1, 'Document 1');
        $file = new SeedDMS_Core_DocumentFile(1, $document1, $user->getId(), 'comment', time(), '', '.txt', 'text/plain', 'test.txt', 'name', 1, true);
        $this->assertIsObject($file);
        $this->assertTrue($file->isType('documentfile'));

        $document = $file->getDocument();
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));
        $this->assertEquals('Document 1', $document->getName());

        $ispublic = $file->isPublic();
        $this->assertTrue($ispublic);

        $comment = $file->getComment();
        $this->assertEquals('comment', $comment);

        $filetype = $file->getFileType();
        $this->assertEquals('.txt', $filetype);

        $mimetype = $file->getMimeType();
        $this->assertEquals('text/plain', $mimetype);

        $name = $file->getName();
        $this->assertEquals('name', $name);

        $origfilename = $file->getOriginalFileName();
        $this->assertEquals('test.txt', $origfilename);

        $version = $file->getVersion();
        $this->assertEquals(1, $version);

        $accessmode = $file->getAccessMode($user);
        $this->assertEquals(M_READ, $accessmode);
    }

    /**
     * Test method setComment() mit sql fail
     *
     * @return void
     */
    public function testSetCommentSqlFail()
    {
        $user = self::getMockedUser();
        $document = $this->getMockedDocument();
        $file = new SeedDMS_Core_DocumentFile(1, $document, $user->getId(), 'comment', time(), '', '.txt', 'text/plain', 'test.txt', 'name', 1, true);
        $this->assertIsObject($file);
        $this->assertTrue($file->isType('documentfile'));

        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocumentFiles` SET `comment`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($file->setComment('my comment'));
    }

    /**
     * Test method setName() mit sql fail
     *
     * @return void
     */
    public function testSetNameSqlFail()
    {
        $user = self::getMockedUser();
        $document = $this->getMockedDocument();
        $file = new SeedDMS_Core_DocumentFile(1, $document, $user->getId(), 'comment', time(), '', '.txt', 'text/plain', 'test.txt', 'name', 1, true);
        $this->assertIsObject($file);
        $this->assertTrue($file->isType('documentfile'));

        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocumentFiles` SET `name`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($file->setName('my name'));
    }

    /**
     * Test method setDate() mit sql fail
     *
     * @return void
     */
    public function testSetDateSqlFail()
    {
        $user = self::getMockedUser();
        $document = $this->getMockedDocument();
        $file = new SeedDMS_Core_DocumentFile(1, $document, $user->getId(), 'comment', time(), '', '.txt', 'text/plain', 'test.txt', 'name', 1, true);
        $this->assertIsObject($file);
        $this->assertTrue($file->isType('documentfile'));

        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocumentFiles` SET `date`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($file->setDate());
    }

    /**
     * Test method setVersion() mit sql fail
     *
     * @return void
     */
    public function testSetVersionSqlFail()
    {
        $user = self::getMockedUser();
        $document = $this->getMockedDocument();
        $file = new SeedDMS_Core_DocumentFile(1, $document, $user->getId(), 'comment', time(), '', '.txt', 'text/plain', 'test.txt', 'name', 1, true);
        $this->assertIsObject($file);
        $this->assertTrue($file->isType('documentfile'));

        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocumentFiles` SET `version`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($file->setVersion(1));
    }

    /**
     * Test method setPublic() mit sql fail
     *
     * @return void
     */
    public function testSetPublicnSqlFail()
    {
        $user = self::getMockedUser();
        $document = $this->getMockedDocument();
        $file = new SeedDMS_Core_DocumentFile(1, $document, $user->getId(), 'comment', time(), '', '.txt', 'text/plain', 'test.txt', 'name', 1, true);
        $this->assertIsObject($file);
        $this->assertTrue($file->isType('documentfile'));

        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocumentFiles` SET `public`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($file->setPublic(true));
    }

    /**
     * Test method addDocumentFile(), getDocumentFile()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddDocumentFile()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $this->assertIsObject($document);
        $tmpfile = self::createTempFile();
        $file = $document->addDocumentFile('attachment.txt', 'comment', $user, $tmpfile, 'attachment.txt', '.txt', 'text/plain', 0, true);
        $this->assertTrue(SeedDMS_Core_File::removeFile($tmpfile));
        $this->assertIsObject($file);
        $this->assertTrue($file->isType('documentfile'));

        $files = $document->getDocumentFiles();
        $this->assertIsArray($files);
        $this->assertCount(1, $files);

        $file = $files[0];

        $document = $file->getDocument();
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));
        $this->assertEquals('Document 1', $document->getName());

        $ispublic = $file->isPublic();
        $this->assertTrue($ispublic);

        $luser = $file->getUser();
        $this->assertIsObject($luser);
        $this->assertTrue($luser->isType('user'));

        $ret = $file->setComment('new comment');
        $this->assertTrue($ret);
        $comment = $file->getComment();
        $this->assertEquals('new comment', $comment);

        $ret = $file->setName('new name');
        $this->assertTrue($ret);
        $name = $file->getName();
        $this->assertEquals('new name', $name);

        $now = time();
        $ret = $file->setDate($now);
        $this->assertTrue($ret);
        $date = $file->getDate();
        $this->assertEquals($now, $date);

        $ret = $file->setDate('fail');
        $this->assertFalse($ret);

        $ret = $file->setVersion(2);
        $this->assertTrue($ret);
        $version = $file->getVersion();
        $this->assertEquals(2, $version);

        $ret = $file->setVersion('fail');
        $this->assertFalse($ret);

        $ret = $file->setPublic(true);
        $this->assertTrue($ret);
        $ispublic = $file->isPublic();
        $this->assertEquals(1, $ispublic);


    }
}
