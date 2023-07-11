<?php
/**
 * Implementation of the document link tests
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
class DocumentLinkTest extends SeedDmsTest
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
    public function testGetMockedDocumentLink()
    {
        $user = self::getMockedUser();
        $document1 = self::getMockedDocument(1, 'Document 1');
        $document2 = self::getMockedDocument(2, 'Document 2');
        $link = new SeedDMS_Core_DocumentLink(1, $document1, $document2, $user, true);
        $this->assertIsObject($link);
        $this->assertTrue($link->isType('documentlink'));

        $document = $link->getDocument();
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));
        $this->assertEquals('Document 1', $document->getName());

        $document = $link->getTarget();
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));
        $this->assertEquals('Document 2', $document->getName());

        $ispublic = $link->isPublic();
        $this->assertTrue($ispublic);
    }

    /**
     * Test method addDocumentLink(), getDocumentLink()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddDocumentLink()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $document1 = self::createDocument($rootfolder, $user, 'Document 1');
        $this->assertIsObject($document1);
        $document2 = self::createDocument($rootfolder, $user, 'Document 2');
        $this->assertIsObject($document2);
        $link = $document1->addDocumentLink($document2->getId(), $user->getId(), true);
        $this->assertIsObject($link);
        $this->assertTrue($link->isType('documentlink'));

        $document = $link->getDocument();
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));
        $this->assertEquals('Document 1', $document->getName());

        $document = $link->getTarget();
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));
        $this->assertEquals('Document 2', $document->getName());

        $ispublic = $link->isPublic();
        $this->assertTrue($ispublic);

        $luser = $link->getUser();
        $this->assertIsObject($luser);
        $this->assertTrue($luser->isType('user'));
    }
}
