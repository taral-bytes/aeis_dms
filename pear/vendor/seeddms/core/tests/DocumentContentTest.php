<?php
/**
 * Implementation of the document content tests
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
class DocumentContentTest extends SeedDmsTest
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
     * Test method getContent(), getContentByVersion(), getLatestContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetContent()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);
        $version = $document->getContentByVersion(1);
        $this->assertIsObject($version);
        $this->assertEquals($version->getId(), $lcontent->getId());
        $content = $document->getContent();
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
        $this->assertEquals($version->getId(), $content[0]->getId());
    }

    /**
     * Test method getContent() mit sql fail
     *
     * @return void
     */
    public function testGetContentSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblDocumentContent` WHERE `document` "))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->getContent());
    }

    /**
     * Test method getContentByVersion() mit sql fail
     *
     * @return void
     */
    public function testGetContentByVersionSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblDocumentContent` WHERE `document` "))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->getContentByVersion(1));
    }

    /**
     * Test method getLatestContent() mit sql fail
     *
     * @return void
     */
    public function testGetLatestContentSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblDocumentContent` WHERE `document` "))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->getLatestContent());
    }

    /**
     * Test method removeContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testRemoveContent()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);

        /* Removing the only version will fail */
        $ret = $document->removeContent($lcontent);
        $this->assertFalse($ret);

        /* Add a new version */
        $filename = self::createTempFile(300);
        $result = $document->addContent('', $user, $filename, 'file2.txt', '.txt', 'text/plain');
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        $this->assertIsObject($result);
        $this->assertIsObject($result->getContent());

        /* Second trial to remove a version. Now it succeeds because it is not
         * the last version anymore.
         */
        $ret = $document->removeContent($lcontent);
        $this->assertTrue($ret);

        /* The latest version is now version 2 */
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);
        $this->assertEquals(2, $lcontent->getVersion());

        /* There is only 1 version left */
        $contents = $document->getContent();
        $this->assertIsArray($contents);
        $this->assertCount(1, $contents);
    }

    /**
     * Test method isType()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testIsType()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);
        $ret = $lcontent->isType('documentcontent');
        $this->assertTrue($ret);
    }

    /**
     * Test method getUser(), getDocument()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testVarious()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);
        $ret = $lcontent->isType('documentcontent');
        $this->assertTrue($ret);
        $doc = $lcontent->getDocument();
        $this->assertEquals($document->getId(), $doc->getId());
        $u = $lcontent->getUser();
        $this->assertEquals($user->getId(), $u->getId());
        $filetype = $lcontent->getFileType();
        $this->assertEquals('.txt', $filetype);
        $origfilename = $lcontent->getOriginalFileName();
        $this->assertEquals('file1.txt', $origfilename);
    }

    /**
     * Test method getComment(), setComment()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetComment()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);
        $comment = $lcontent->getComment();
        $this->assertEquals('', $comment);
        $ret = $lcontent->setComment('Document content comment');
        $this->assertTrue($ret);
        /* Retrieve the document content from the database again */
        $content = self::$dms->getDocumentContent($lcontent->getId());
        $comment = $content->getComment();
        $this->assertEquals('Document content comment', $comment);
    }

    /**
     * Test method getDate(), setDate()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetDate()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);
        $date = $lcontent->getDate();
        $this->assertIsInt($date);
        $this->assertGreaterThanOrEqual(time(), $date);

        /* Set date as timestamp */
        $ret = $lcontent->setDate($date-1000);
        $this->assertTrue($ret);
        /* Retrieve the document content from the database again */
        $content = self::$dms->getDocumentContent($lcontent->getId());
        $newdate = $content->getDate();
        $this->assertEquals($date-1000, $newdate);

        /* Set date in Y-m-d H:i:s format */
        $date = time()-500;
        $ret = $lcontent->setDate(date('Y-m-d H:i:s', $date));
        $this->assertTrue($ret);
        /* Retrieve the document content from the database again */
        $content = self::$dms->getDocumentContent($lcontent->getId());
        $newdate = $content->getDate();
        $this->assertEquals($date, $newdate);

        /* Not passing a date will set the current date/time */
        $date = time();
        $ret = $lcontent->setDate();
        $this->assertTrue($ret);
        /* Retrieve the document content from the database again */
        $content = self::$dms->getDocumentContent($lcontent->getId());
        $newdate = $content->getDate();
        $this->assertEquals($date, $newdate);
    }

    /**
     * Test method getFileSize(), setFileSize()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetFileSize()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1', 200);
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);
        $filesize = $lcontent->getFileSize();
        $this->assertEquals(200, $filesize);

        /* Intentially corrupt the file size */
        $db = self::$dms->getDb();
        $ret = $db->getResult("UPDATE `tblDocumentContent` SET `fileSize` = 300  WHERE `document` = " . $document->getID() . " AND `version` = " . $lcontent->getVersion());
        $this->assertTrue($ret);

        $corcontent = self::$dms->getDocumentContent($lcontent->getId());
        $filesize = $corcontent->getFileSize();
        $this->assertEquals(300, $filesize);

        /* Repair filesize by calling setFileSize() */
        $ret = $corcontent->setFileSize();
        $this->assertTrue($ret);
        $filesize = $corcontent->getFileSize();
        $this->assertEquals(200, $filesize);
    }

    /**
     * Test method getChecksum(), setChecksum()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetChecksum()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1', 200);
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);
        $orgchecksum = $lcontent->getChecksum();
        $this->assertIsString($orgchecksum);
        $this->assertEquals(32, strlen($orgchecksum));

        /* Intentially corrupt the checksum */
        $db = self::$dms->getDb();
        $ret = $db->getResult("UPDATE `tblDocumentContent` SET `checksum` = 'foobar'  WHERE `document` = " . $document->getID() . " AND `version` = " . $lcontent->getVersion());
        $this->assertTrue($ret);

        $corcontent = self::$dms->getDocumentContent($lcontent->getId());
        $checksum = $corcontent->getChecksum();
        $this->assertEquals('foobar', $checksum);

        /* Repair filesize by calling setChecksum() */
        $ret = $corcontent->setChecksum();
        $this->assertTrue($ret);
        $checksum = $corcontent->getChecksum();
        $this->assertEquals($orgchecksum, $checksum);
    }

    /**
     * Test method getStatus(), setStatus(), getStatusLog()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetStatus()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1', 200);
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);

        $status = $lcontent->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_RELEASED, $status['status']);

        $statuslog = $lcontent->getStatusLog();
        $this->assertIsArray($statuslog);
        $this->assertCount(1, $statuslog);

        /* Missing update user returns false */
        $ret = $lcontent->setStatus(S_OBSOLETE, '', null);
        $this->assertFalse($ret);

        /* A status out of range returns false */
        $ret = $lcontent->setStatus(9, '', $user);
        $this->assertFalse($ret);

        /* A wrong date returns false */
        $ret = $lcontent->setStatus(S_OBSOLETE, '', $user, '2021-02-29 10:10:10');
        $this->assertFalse($ret);

        $ret = $lcontent->setStatus(S_OBSOLETE, 'No longer valid', $user, date('Y-m-d H:i:s'));
        $status = $lcontent->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_OBSOLETE, $status['status']);

        /* Status log has now 2 entries */
        $statuslog = $lcontent->getStatusLog();
        $this->assertIsArray($statuslog);
        $this->assertCount(2, $statuslog);

        /* Add the 'onSetStatus' callback */
        $callret = '';
        $callback = function ($param, $content, $updateuser, $oldstatus, $newstatus) use (&$callret) {
            $callret = $oldstatus.' to '.$newstatus; 
            return $param;
        };
        /* Because the callback will return false, the status will not be set */
        self::$dms->setCallback('onSetStatus', $callback, false);
        /* Trying to go back to status released with a callback returning false */
        $ret = $lcontent->setStatus(S_RELEASED, 'Valid again', $user);
        $status = $lcontent->getStatus();
        $this->assertIsArray($status);
        /* Status is still S_OBSOLETE because the callback returned false */
        $this->assertEquals(S_OBSOLETE, $status['status']);
        $this->assertEquals(S_OBSOLETE.' to '.S_RELEASED, $callret);

        /* Do it again, but this time the callback returns true */
        self::$dms->setCallback('onSetStatus', $callback, true);
        /* Trying to go back to status released with a callback returning true */
        $ret = $lcontent->setStatus(S_RELEASED, 'Valid again', $user);
        $status = $lcontent->getStatus();
        $this->assertIsArray($status);
        /* Status updated to S_RELEASED because the callback returned true */
        $this->assertEquals(S_RELEASED, $status['status']);
        $this->assertEquals(S_OBSOLETE.' to '.S_RELEASED, $callret);

        /* Status log has now 3 entries */
        $statuslog = $lcontent->getStatusLog();
        $this->assertIsArray($statuslog);
        $this->assertCount(3, $statuslog);

        /* Get just the last entry */
        $statuslog = $lcontent->getStatusLog(1);
        $this->assertIsArray($statuslog);
        $this->assertCount(1, $statuslog);
        $this->assertEquals('Valid again', $statuslog[0]['comment']);
    }

    /**
     * Test method getMimeType(), setMimeType()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetMimeType()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1', 200);
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);

        $ret = $lcontent->setMimeType('text/csv');
        $this->assertTrue($ret);

        /* Retrieve the document content from the database again */
        $content = self::$dms->getDocumentContent($lcontent->getId());
        $this->assertIsObject($content);
        $this->assertEquals('text/csv', $content->getMimeType());
    }

    /**
     * Test method getFileType(), setFileType()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetFileType()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1', 200);
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);

        $ret = $lcontent->setMimeType('text/css');
        $this->assertTrue($ret);

        $ret = $lcontent->setFileType();
        $this->assertTrue($ret);

        /* Retrieve the document content from the database again */
        $content = self::$dms->getDocumentContent($lcontent->getId());
        $this->assertIsObject($content);
        $this->assertEquals('.css', $content->getFileType());

        /* Also get the file content to ensure the renaming of the file
         * on disc has succeeded.
         */
        $c = file_get_contents(self::$dms->contentDir.$lcontent->getPath());
        $this->assertEquals(200, strlen($c));
    }

    /**
     * Test method replaceContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testReplaceContent()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $guest = self::$dms->getUser(2);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1', 200);
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);

        $filename = self::createTempFile(300);
        /* Not using the same user yields an error */
        $ret = $document->replaceContent(1, $guest, $filename, 'file1.txt', '.txt', 'text/plain');
        $this->assertFalse($ret);
        /* Not using the same orig. file name yields an error */
        $ret = $document->replaceContent(1, $user, $filename, 'file2.txt', '.txt', 'text/plain');
        $this->assertFalse($ret);
        /* Not using the same file type yields an error */
        $ret = $document->replaceContent(1, $user, $filename, 'file1.txt', '.csv', 'text/plain');
        $this->assertFalse($ret);
        /* Not using the same mime type yields an error */
        $ret = $document->replaceContent(1, $user, $filename, 'file1.txt', '.txt', 'text/csv');
        $this->assertFalse($ret);

        /* Setting version to 0 will replace the latest version */
        $ret = $document->replaceContent(0, $user, $filename, 'file1.txt', '.txt', 'text/plain');
        $this->assertTrue($ret);

        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));

        /* Retrieve the document content from the database again */
        $newcontent = $document->getLatestContent();
        $this->assertIsObject($newcontent);
        $this->assertEquals('text/plain', $newcontent->getMimeType());
        /* File size has grown from 200 to 300 bytes */
        $filesize = $newcontent->getFileSize();
        $this->assertEquals(300, $filesize);
        /* Still version 1 */
        $version = $newcontent->getVersion();
        $this->assertEquals(1, $version);
    }

    /**
     * Test method replaceContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAccessMode()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $guest = self::$dms->getUser(2);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1', 200);
        $this->assertIsObject($document);
        $lcontent = $document->getLatestContent();
        $this->assertIsObject($lcontent);

        /* Access rights on a document content are always M_READ unless the callback
         * onCheckAccessDocumentContent is implemented */
        $mode = $lcontent->getAccessMode($user);
        $this->assertEquals(M_READ, $mode);
    }
}
