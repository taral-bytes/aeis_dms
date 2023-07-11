<?php
/**
 * Implementation of the dms tests
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
class DmsTest extends SeedDmsTest
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
     * Create a mock admin role object (only used for SeedDMS 6)
     *
     * @return SeedDMS_Core_User
     */
    protected function getAdminRole()
    {
        $role = new SeedDMS_Core_Role(1, 'admin', SeedDMS_Core_Role::role_admin);
        return $role;
    }

    /**
     * Test checkIfEqual()
     *
     * @return void
     */
    public function testCheckIfEqual()
    {
        $user1 = new SeedDMS_Core_User(1, 'user 1', '', '', '', '', '', '', 1);
        $group1 = new SeedDMS_Core_Group(1, 'group 1', '');
        $group1n = new SeedDMS_Core_Group(1, 'group 1n', '');
        $group1c = clone $group1;
        $group2 = new SeedDMS_Core_Group(2, 'group 1', '');
        $dms = new SeedDMS_Core_DMS(null, '');
        $this->assertFalse($dms->checkIfEqual($group1, $user1)); // different classes
        $this->assertFalse($dms->checkIfEqual($group1, $group2)); // different id
        $this->assertTrue($dms->checkIfEqual($group1, $group1c)); // a clone is always equal
        $this->assertTrue($dms->checkIfEqual($group1, $group1n)); // different instances but same id is sufficient to be equal
    } /* }}} */

    /**
     * Test checkDate()
     *
     * @return void
     */
    public function testCheckDate()
    {
        $dms = new SeedDMS_Core_DMS(null, '');
        $this->assertTrue($dms->checkDate('2020-02-28 10:12:34'));
        $this->assertTrue($dms->checkDate('2020-02-29 10:12:34')); // a leap year
        $this->assertFalse($dms->checkDate('2020-02-30 10:12:34')); // feb has never 30 days
        $this->assertFalse($dms->checkDate('2021-02-29 10:12:34')); // not a leap year
        $this->assertFalse($dms->checkDate('2020-02-28 24:12:34')); // hour is out of range
        $this->assertFalse($dms->checkDate('2020-02-28 23:60:34')); // minute is out of range
        $this->assertFalse($dms->checkDate('2020-02-28 23:59:60')); // second is out of range
        $this->assertFalse($dms->checkDate('2020-02-28 23:59:')); // second is missing
        $this->assertTrue($dms->checkDate('2020-02-28', 'Y-m-d')); // just checking the date
        $this->assertFalse($dms->checkDate('28.2.2020', 'd.m.Y')); // month must be 01-12
        $this->assertTrue($dms->checkDate('28.2.2020', 'd.n.Y')); // month must be 1-12
        $this->assertFalse($dms->checkDate('28.02.2020', 'd.n.Y')); // month must be 1-12
    } /* }}} */

    /**
     * Test getClassname()
     *
     * @return void
     */
    public function testGetClassName()
    {
        /* Do not mess up the global instance self::$dms, but create my own */
        $dms = new SeedDMS_Core_DMS(null, '');
        $this->assertEquals('SeedDMS_Core_Folder', $dms->getClassname('folder'));
        $this->assertEquals('SeedDMS_Core_Document', $dms->getClassname('document'));
        $this->assertEquals('SeedDMS_Core_DocumentContent', $dms->getClassname('documentcontent'));
        $this->assertEquals('SeedDMS_Core_User', $dms->getClassname('user'));
        $this->assertEquals('SeedDMS_Core_Group', $dms->getClassname('group'));
        $this->assertFalse($dms->getClassname('foo'));
    }

    /**
     * Test setClassname()
     *
     * @return void
     */
    public function testSetClassName()
    {
        /* Do not mess up the global instance self::$dms, but create my own */
        $dms = new SeedDMS_Core_DMS(null, '');
        $this->assertEquals('SeedDMS_Core_Folder', $dms->setClassname('folder', 'MyNewFolderClass'));
        $this->assertEquals('MyNewFolderClass', $dms->getClassname('folder'));
        $this->assertEquals('MyNewFolderClass', $dms->setClassname('folder', 'MySuperNewFolderClass'));
        $this->assertFalse($dms->setClassname('foo', 'MyNewFolderClass'));
    }

    /**
     * Test addCallback()
     *
     * @return void
     */
    public function testAddCallback()
    {
        /* Do not mess up the global instance self::$dms, but create my own */
        $dms = new SeedDMS_Core_DMS(null, '');
        /* Add a closure as a callback is just fine */
        $this->assertTrue(
            $dms->addCallback(
                'onPostSomething', function () {
                }
            )
        );
        /* An empty callback will make addCallback() fail */
        $this->assertFalse(
            $dms->addCallback(
                '', function () {
                }
            )
        );
        /* Passing a class method is ok */
        $this->assertTrue($dms->addCallback('onPostSomething', 'DmsTest::testAddCallback'));
        /* Passing a none existing class mehtod makes addCallback() fail */
        $this->assertFalse($dms->addCallback('onPostSomething', 'DmsTest::thisMethodDoesNotExist'));
    }

    /**
     * Test for hasCallback
     *
     * @return void
     */
    public function testHasCallback()
    {
        /* Do not mess up the global instance self::$dms, but create my own */
        $dms = new SeedDMS_Core_DMS(null, '');
        /* Add a closure as a callback is just fine */
        $this->assertTrue(
            $dms->addCallback(
                'onPostSomething', function () {
                }
            )
        );
        $this->assertTrue($dms->hasCallback('onPostSomething'));
        $this->assertFalse($dms->hasCallback('thisOneDoesNotExist'));
    }

    /**
     * Test for getDecorators
     *
     * @return void
     */
    public function testGetDecorators()
    {
        /* Do not mess up the global instance self::$dms, but create my own */
        $dms = new SeedDMS_Core_DMS(null, '');
        $this->assertFalse($dms->getDecorators('folder'));
    }

    /**
     * Test for addDecorator
     *
     * @return void
     */
    public function testaddDecorator()
    {
        /* Do not mess up the global instance self::$dms, but create my own */
        $dms = new SeedDMS_Core_DMS(null, '');
        $this->assertTrue($dms->addDecorator('folder', 'MyNewDecorator'));
        $decorators = $dms->getDecorators('folder');
        $this->assertIsArray($decorators);
        $this->assertCount(1, $decorators);
    }

    /**
     * Test getDb()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDb()
    {
        $this->assertEquals(self::$dbh, self::$dms->getDb());
    }

    /**
     * Test getDBVersion()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDbVersion()
    {
        $version = self::$dms->getDBVersion();
        $this->assertCount(4, $version);
        $this->assertGreaterThanOrEqual(5, $version['major']);
        $this->assertGreaterThanOrEqual(0, $version['minor']);
    }

    /**
     * Test getDBVersionFailMissingTable()
     *
     * This method checks if getDBVersion() returns false if the table
     * list of the database does not contain the table 'tblVersion'
     *
     * @return void
     */
    public function testGetDbVersionFailMissingTable()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('TableList')
            ->willReturn(['tblFolders', 'tblDocuments']);
        $dms = new SeedDMS_Core_DMS($db, '');
        $version = $dms->getDBVersion();
        $this->assertFalse($version);
    }

    /**
     * Test getDBVersionSqlFail()
     *
     * This method checks if getDBVersion() returns false if the sql
     * for selecting the records in table 'tblVersion' fail
     *
     * @return void
     */
    public function testGetDbVersionSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblVersion` ORDER BY `major`,`minor`,`subminor` LIMIT 1")
            ->willReturn(false);
        $db->expects($this->once())
            ->method('TableList')
            ->willReturn(['tblVersion', 'tblFolders', 'tblDocuments']);
        $dms = new SeedDMS_Core_DMS($db, '');
        $version = $dms->getDBVersion();
        $this->assertFalse($version);
    }

    /**
     * Test getDBVersionNoRecord()
     *
     * This method checks if getDBVersion() returns false a table 'tblVersion'
     * exists but has no record
     *
     * @return void
     */
    public function testGetDbVersionNoRecord()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblVersion` ORDER BY `major`,`minor`,`subminor` LIMIT 1")
            ->willReturn(array());
        $db->expects($this->once())
            ->method('TableList')
            ->willReturn(['tblVersion', 'tblFolders', 'tblDocuments']);
        $dms = new SeedDMS_Core_DMS($db, '');
        $version = $dms->getDBVersion();
        $this->assertFalse($version);
    }

    /**
     * Test checkVersion()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testCheckVersion()
    {
        $this->assertTrue(self::$dms->checkVersion());
    }

    /**
     * Test checkVersionFail()
     *
     * This method checks if checkVersion() returns false if the version
     * in table 'tblVersion' does not match the version in the class variable
     * $version. To make this method independant of version changes, the
     * current version is taken from SeedDMS_Core_DMS::version and modified
     * in order to differ from the version stored in the database.
     *
     * @return void
     */
    public function testcheckVersionFail()
    {
        $verstr = (new SeedDMS_Core_DMS(null, ''))->version;
        $verarr = explode('.', $verstr);
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblVersion` ORDER BY `major`,`minor`,`subminor` LIMIT 1")
            ->willReturn([['major'=>$verarr[0], 'minor'=>$verarr[1]+1]]);
        $db->expects($this->once())
            ->method('TableList')
            ->willReturn(['tblVersion', 'tblFolders', 'tblDocuments']);
        $dms = new SeedDMS_Core_DMS($db, '');
        $version = $dms->checkVersion();
        $this->assertFalse($version);
    }

    /**
     * Test checkVersionSqlFail()
     *
     * This method checks if checkVersion() returns false if the sql
     * for selecting the records in table 'tblVersion' fail
     *
     * @return void
     */
    public function testcheckVersionSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblVersion` ORDER BY `major`,`minor`,`subminor` LIMIT 1")
            ->willReturn(false);
        $db->expects($this->once())
            ->method('TableList')
            ->willReturn(['tblVersion', 'tblFolders', 'tblDocuments']);
        $dms = new SeedDMS_Core_DMS($db, '');
        $version = $dms->checkVersion();
        $this->assertFalse($version);
    }

    /**
     * Test checkVersionFailMissingTable()
     *
     * This method checks if checkVersion() returns false if the table
     * list of the database does not contain the table 'tblVersion'
     *
     * @return void
     */
    public function testCheckVersionFailMissingTable()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('TableList')
            ->willReturn(['tblFolders', 'tblDocuments']);
        $dms = new SeedDMS_Core_DMS($db, '');
        $version = $dms->checkVersion();
        $this->assertTrue($version); // A missing table tblVersion returns true!
    }

    /**
     * Test checkVersionNoRecord()
     *
     * This method checks if checkVersion() returns false a table 'tblVersion'
     * exists but has no record
     *
     * @return void
     */
    public function testCheckVersionNoRecord()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblVersion` ORDER BY `major`,`minor`,`subminor` LIMIT 1")
            ->willReturn(array());
        $db->expects($this->once())
            ->method('TableList')
            ->willReturn(['tblVersion', 'tblFolders', 'tblDocuments']);
        $dms = new SeedDMS_Core_DMS($db, '');
        $version = $dms->checkVersion();
        $this->assertFalse($version);
    }

    /**
     * Test setRootFolderID()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetRootFolderID()
    {
        /* Setting the same root folder is ok */
        $oldid = self::$dms->setRootFolderID(1);
        $this->assertEquals(1, $oldid);
        /* Setting a none existing root folder id will not change the root folder */
        $oldid = self::$dms->setRootFolderID(2);
        $this->assertFalse($oldid);
        /* Make sure the old root folder is still set */
        $rootfolder = self::$dms->getRootFolder();
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $rootfolder);
        $this->assertEquals(1, $rootfolder->getId());
    }

    /**
     * Test getRootFolder()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetRootFolder()
    {
        $rootfolder = self::$dms->getRootFolder();
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $rootfolder);
        $this->assertEquals(1, $rootfolder->getId());
    }

    /**
     * Test setUser()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetUser()
    {
        $user = self::$dms->getUser(1);
        $olduser = self::$dms->setUser($user); // returns null because there is no old user
        $this->assertNull($olduser);
        $olduser = self::$dms->setUser($user); // second call will return the user set before
        $this->assertIsObject($olduser);
        $olduser = self::$dms->setUser(null); // old user is still an object
        $this->assertIsObject($olduser);
        $olduser = self::$dms->setUser(8); // invalid user
        $this->assertFalse($olduser);
    }

    /**
     * Test getLoggedInUser()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetLoggedInUser()
    {
        $olduser = self::$dms->getLoggedInUser(); // initially this is set to null
        $this->assertNull($olduser);
        $user = self::$dms->getUser(1);
        self::$dms->setUser($user);
        $olduser = self::$dms->getLoggedInUser();
        $this->assertEquals($olduser->getId(), $user->getId());
    }

    /**
     * Test getDocument()
     *
     * As there is currently no document, getDocument() must return null.
     * If false was returned it would indicated an sql error.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocument()
    {
        $document = self::$dms->getDocument(1);
        $this->assertNull($document);
    }

    /**
     * Test getDocumentsByUser()
     *
     * As there is currently no document, getDocumentsByUser() must return
     * an empty array.
     * If false was returned it would indicated an sql error.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentsByUser()
    {
        $documents = self::$dms->getDocumentsByUser(self::$dms->getUser(1));
        $this->assertIsArray($documents);
        $this->assertCount(0, $documents);
    }

    /**
     * Test getDocumentsLockedByUser()
     *
     * As there is currently no document, getDocumentsLockedByUser() must return
     * an empty array.
     * If false was returned it would indicated an sql error.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentsLockedByUser()
    {
        $documents = self::$dms->getDocumentsLockedByUser(self::$dms->getUser(1));
        $this->assertIsArray($documents);
        $this->assertCount(0, $documents);
    }

    /**
     * Test makeTimeStamp()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testMakeTimeStamp()
    {
        /* Assert correct date */
        $this->assertEquals(0, self::$dms->makeTimeStamp(1, 0, 0, 1970, 1, 1));
        $this->assertEquals(68166000, self::$dms->makeTimeStamp(0, 0, 0, 1972, 2, 29));
        /* Assert incorrect dates */
        $this->assertFalse(self::$dms->makeTimeStamp(0, 0, 0, 1970, 13, 1), 'Incorrect month not recognized');
        $this->assertFalse(self::$dms->makeTimeStamp(0, 0, 0, 1970, 1, 32), 'Incorrect day in january not recognized');
        $this->assertFalse(self::$dms->makeTimeStamp(0, 0, 0, 1970, 4, 31), 'Incorrect day in april not recognized');
        $this->assertFalse(self::$dms->makeTimeStamp(0, 0, 0, 1970, 2, 29), 'Incorrect day in february not recognized');
        $this->assertFalse(self::$dms->makeTimeStamp(24, 0, 0, 1970, 1, 1), 'Incorrect hour not recognized');
        $this->assertFalse(self::$dms->makeTimeStamp(0, 60, 0, 1970, 1, 1), 'Incorrect minute not recognized');
        $this->assertFalse(self::$dms->makeTimeStamp(0, 0, 60, 1970, 1, 1), 'Incorrect second not recognized');
    }

    /**
     * Test search()
     *
     * Just search the root folder in different ways. Because the initial database
     * does not have any documents, this method will test various ways to
     * find the root folder 'DMS' with id=1
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSearchRootFolder()
    {
        /* searching for folders/documents in any field */
        $result = self::$dms->search(
            array(
                'query'=>'DMS'
            )
        );
        $this->assertEquals(1, $result['totalFolders']);
        $this->assertCount(1, $result['folders']);
        $this->assertEquals(0, $result['totalDocs']);
        $this->assertCount(0, $result['docs']);

        /* searching for folders in any field */
        $result = self::$dms->search(
            array(
                'query'=>'DMS',
                'mode'=>0x2
            )
        );
        $this->assertEquals(1, $result['totalFolders']);
        $this->assertCount(1, $result['folders']);
        $this->assertEquals(0, $result['totalDocs']);
        $this->assertCount(0, $result['docs']);

        /* searching for documents in any field will not return any folders*/
        $result = self::$dms->search(
            array(
                'query'=>'DMS',
                'mode'=>0x1
            )
        );
        $this->assertEquals(0, $result['totalFolders']);
        $this->assertCount(0, $result['folders']);
        $this->assertEquals(0, $result['totalDocs']);
        $this->assertCount(0, $result['docs']);

        /* searching for folders with a bogus name may not return any folders */
        $result = self::$dms->search(
            array(
                'query'=>'foo',
                'mode'=>0x2
            )
        );
        $this->assertEquals(0, $result['totalFolders']);
        $this->assertCount(0, $result['folders']);

        /* searching for folders by its id */
        $result = self::$dms->search(
            array(
                'query'=>'1',
                'mode'=>0x2
            )
        );
        $this->assertEquals(1, $result['totalFolders']);
        $this->assertCount(1, $result['folders']);

        /* searching for folders by an unknown id */
        $result = self::$dms->search(
            array(
                'query'=>'2',
                'mode'=>0x2
            )
        );
        $this->assertEquals(0, $result['totalFolders']);
        $this->assertCount(0, $result['folders']);

        /* searching for folders with two terms ANDed, but only one matches */
        $result = self::$dms->search(
            array(
                'query'=>'DMS foo',
                'mode'=>0x2,
                'logicalmode'=>'AND',
            )
        );
        $this->assertEquals(0, $result['totalFolders']);
        $this->assertCount(0, $result['folders']);

        /* searching for folders with two terms ORed, but only one matches */
        $result = self::$dms->search(
            array(
                'query'=>'DMS foo',
                'mode'=>0x2,
                'logicalmode'=>'OR',
            )
        );
        $this->assertEquals(1, $result['totalFolders']);
        $this->assertCount(1, $result['folders']);

        /* searching for folders with two terms ANDed, both match, but in different fields (name and id) */
        $result = self::$dms->search(
            array(
                'query'=>'DMS 1',
                'mode'=>0x2,
                'logicalmode'=>'AND',
            )
        );
        $this->assertEquals(1, $result['totalFolders']);
        $this->assertCount(1, $result['folders']);

        /* searching for folders with two terms ANDed, both match, but in different fields (name and id). But only one field is searched. */
        $result = self::$dms->search(
            array(
                'query'=>'DMS 1',
                'mode'=>0x2,
                'logicalmode'=>'AND',
                'searchin'=>array(2,3), // name, comment
            )
        );
        $this->assertEquals(0, $result['totalFolders']);
        $this->assertCount(0, $result['folders']);

        /* searching for folders below a start folder will not find the folder 'DMS'
         * anymore, because the start folder itself will not be found.
         */
        $result = self::$dms->search(
            array(
                'query'=>'DMS',
                'mode'=>0x2,
                'startFolder'=>self::$dms->getRootFolder()
            )
        );
        $this->assertEquals(0, $result['totalFolders']);
        $this->assertCount(0, $result['folders']);

        /* Restrict search to the owner of the folder 'DMS'
         */
        $result = self::$dms->search(
            array(
                'query'=>'DMS',
                'mode'=>0x2,
                'owner'=>self::$dms->getUser(1)
            )
        );
        $this->assertEquals(1, $result['totalFolders']);
        $this->assertCount(1, $result['folders']);

        /* Restrict search to user who does not own a document
         */
        $result = self::$dms->search(
            array(
                'query'=>'DMS',
                'mode'=>0x2,
                'owner'=>self::$dms->getUser(2)
            )
        );
        $this->assertEquals(0, $result['totalFolders']);
        $this->assertCount(0, $result['folders']);

        /* Restrict search to a list of owners (in this case all users)
         */
        $result = self::$dms->search(
            array(
                'query'=>'DMS',
                'mode'=>0x2,
                'owner'=>self::$dms->getAllUsers()
            )
        );
        $this->assertEquals(1, $result['totalFolders']);
        $this->assertCount(1, $result['folders']);

    }

    /**
     * Test getFolder()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetFolder()
    {
        $folder = self::$dms->getFolder(1);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $folder);
        $this->assertEquals(1, $folder->getId());
    }

    /**
     * Test getFolderByName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetFolderByName()
    {
        $folder = self::$dms->getFolderByName('DMS');
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $folder);
        $this->assertEquals(1, $folder->getId());
        $folder = self::$dms->getFolderByName('FOO');
        $this->assertNull($folder);
    }

    /**
     * Test checkFolders()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testCheckFolders()
    {
        $errors = self::$dms->checkFolders();
        $this->assertIsArray($errors);
        $this->assertCount(0, $errors);
    }

    /**
     * Test checkFoldersSqlFail()
     *
     * This test catches the case when the sql statement for getting all
     * folders fails.
     *
     * @return void
     */
    public function testCheckFoldersSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblFolders`")
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->checkFolders());
    }

    /**
     * Test checkFoldersFailNoParent()
     *
     * This test catches the case when a folder's parent is not present
     *
     * @return void
     */
    public function testCheckFoldersFailNoParent()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblFolders`")
            ->willReturn(
                array(
                array('id'=>1, 'name'=>'DMS', 'parent'=>0, 'folderList'=>''),
                array('id'=>5, 'name'=>'Subfolder', 'parent'=>3, 'folderList'=>':1:'),
                )
            );
        $dms = new SeedDMS_Core_DMS($db, '');
        $errors = $dms->checkFolders();
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors); // there should be 1 error
        $this->assertArrayHasKey(5, $errors); // folder with id=5 has the wrong parent
        $this->assertEquals('Missing parent', $errors[5]['msg']);
    }

    /**
     * Test checkFoldersFailWrongFolderList()
     *
     * This test catches the case when a folder's parent is not present
     *
     * @return void
     */
    public function testCheckFoldersFailWrongFolderList()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblFolders`")
            ->willReturn(
                array(
                array('id'=>1, 'name'=>'DMS', 'parent'=>0, 'folderList'=>''),
                array('id'=>5, 'name'=>'Subfolder', 'parent'=>1, 'folderList'=>':1:2:'),
                )
            );
        $dms = new SeedDMS_Core_DMS($db, '');
        $errors = $dms->checkFolders();
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors); // there should be 1 error
        $this->assertArrayHasKey(5, $errors); // folder with id=5 has the wrong parent
        $this->assertStringContainsString('Wrong folder list', $errors[5]['msg']);
    }

    /**
    /**
     * Test checkDocuments()
     *
     * The intitial database does not have any documents which makes this
     * test less usefull.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testCheckDocuments()
    {
        $errors = self::$dms->checkDocuments();
        $this->assertIsArray($errors);
        $this->assertCount(0, $errors);
    }

    /**
     * Test checkDocumentsSqlFoldersFail()
     *
     * This test catches the case when the sql statement for getting all
     * folders fails.
     *
     * @return void
     */
    public function testCheckDocumentsSqlFoldersFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblFolders`")
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->checkDocuments());
    }

    /**
     * Test checkDocumentsSqlDocumentsFail()
     *
     * This test catches the case when the sql statement for getting all
     * documents fails, after getting all folders succeeded.
     *
     * @return void
     */
    public function testCheckDocumentsSqlDocumentsFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->exactly(2))
            ->method('getResultArray')
            ->will(
                $this->returnValueMap(
                    array(
                        array("SELECT * FROM `tblFolders`", true, array(
                            array('id'=>1, 'name'=>'DMS', 'parent'=>0, 'folderList'=>'')
                        )),
                        array("SELECT * FROM `tblDocuments`", true, false)
                    )
                )
            );
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->checkDocuments());
    }

    /**
     * Test checkDocumentsFailNoParent()
     *
     * This test catches the case when a documents's parent is not present
     *
     * @return void
     */
    public function testCheckDocumentsFailNoParent()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->exactly(2))
            ->method('getResultArray')
            ->will(
                $this->returnValueMap(
                    array(
                        array("SELECT * FROM `tblFolders`", true, array(
                            array('id'=>1, 'name'=>'DMS', 'parent'=>0, 'folderList'=>''),
                            array('id'=>5, 'name'=>'Subfolder', 'parent'=>1, 'folderList'=>':1:'),
                        )),
                        array("SELECT * FROM `tblDocuments`", true, array(
                            array('id'=>1, 'name'=>'Document 1', 'folder'=>1, 'folderList'=>':1:'),
                            array('id'=>2, 'name'=>'Document 2', 'folder'=>2, 'folderList'=>':1:5:'),
                        ))
                    )
                )
            );
        $dms = new SeedDMS_Core_DMS($db, '');
        $errors = $dms->checkDocuments();
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors); // there should be 1 error
        $this->assertArrayHasKey(2, $errors); // document with id=2 has the wrong parent
        $this->assertEquals('Missing parent', $errors[2]['msg']);
    }

    /**
     * Test checkDocumentsFailWrongFolderList()
     *
     * This test catches the case when a documents's parent is not present
     *
     * @return void
     */
    public function testCheckDocumentsFailWrongFolderList()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->exactly(2))
            ->method('getResultArray')
            ->will(
                $this->returnValueMap(
                    array(
                        array("SELECT * FROM `tblFolders`", true, array(
                            array('id'=>1, 'name'=>'DMS', 'parent'=>0, 'folderList'=>''),
                            array('id'=>5, 'name'=>'Subfolder', 'parent'=>1, 'folderList'=>':1:'),
                        )),
                        array("SELECT * FROM `tblDocuments`", true, array(
                            array('id'=>1, 'name'=>'Document 1', 'folder'=>1, 'folderList'=>':1:'),
                            array('id'=>2, 'name'=>'Document 2', 'folder'=>5, 'folderList'=>':1:2:'),
                        ))
                    )
                )
            );
        $dms = new SeedDMS_Core_DMS($db, '');
        $errors = $dms->checkDocuments();
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors); // there should be 1 error
        $this->assertArrayHasKey(2, $errors); // document with id=2 has the wrong parent
        $this->assertStringContainsString('Wrong folder list', $errors[2]['msg']);
    }

    /**
     * Test getUser()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetUser()
    {
        $user = self::$dms->getUser(1);
        $this->assertInstanceOf(SeedDMS_Core_User::class, $user);
        $this->assertEquals(1, $user->getId());
    }

    /**
     * Test getUserByLogin()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetUserByLogin()
    {
        $user = self::$dms->getUserByLogin('admin');
        $this->assertInstanceOf(SeedDMS_Core_User::class, $user);
        $this->assertEquals('admin', $user->getLogin());
    }

    /**
     * Test getUserByEmail()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetUserByEmail()
    {
        $user = self::$dms->getUserByEmail('info@seeddms.org');
        $this->assertInstanceOf(SeedDMS_Core_User::class, $user);
        $this->assertEquals('admin', $user->getLogin());
    }

    /**
     * Test getAllUsers()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAllUsers()
    {
        $users = self::$dms->getAllUsers();
        $this->assertIsArray($users);
        $this->assertCount(2, $users);
    }

    /**
     * Test addUser()
     *
     * Add a new user and retrieve it afterwards. Also check if the number
     * of users has increased by one. Add a user with the same name a
     * second time and check if it returns false.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddUser()
    {
        /* Adding a new user */
        $user = self::$dms->addUser('new user', 'pwd', 'Full Name', 'newuser@seeddms.org', 'en_GB', 'bootstrap', 'with comment');
        $this->assertIsObject($user);
        $this->assertEquals('new user', $user->getLogin());
        $this->assertEquals('with comment', $user->getComment());

        /* Adding a user with the same login must fail */
        $user = self::$dms->addUser('new user', 'pwd', 'Full Name', 'newuser@seeddms.org', 'en_GB', 'bootstrap', 'with comment');
        $this->assertFalse($user);

        /* There should be 3 users now */
        $users = self::$dms->getAllUsers();
        $this->assertIsArray($users);
        $this->assertCount(3, $users);

        /* Check if setting the password expiration to 'now' works */
        $now = date('Y-m-d H:i:s');
        $user = self::$dms->addUser('new user pwdexpiration 1', 'pwd', 'Full Name', 'newuser@seeddms.org', 'en_GB', 'bootstrap', 'with comment', '', false, false, 'now');
        $this->assertEquals($now, $user->getPwdExpiration());
        $now = date('Y-m-d H:i:s');
        $user = self::$dms->addUser('new user pwdexpiration 2', 'pwd', 'Full Name', 'newuser@seeddms.org', 'en_GB', 'bootstrap', 'with comment', '', false, false, $now);
        $this->assertEquals($now, $user->getPwdExpiration());
    }

    /**
     * Test addUserWithPostAddHook()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddUserWithPostAddHook()
    {
        /* Add the 'onPostAddUser' callback */
        $ret = 0;
        $callback = function ($param, $user) use (&$ret) {
            $ret = 1; 
        };
        self::$dms->addCallback('onPostAddUser', $callback, 1);
        /* Adding a new user */
        $user = self::$dms->addUser('new user', 'pwd', 'Full Name', 'newuser@seeddms.org', 'en_GB', 'bootstrap', 'with comment');
        $this->assertIsObject($user);
        $this->assertEquals('new user', $user->getLogin());
        $this->assertEquals(1, $ret);
    }

    /**
     * Test addUser() with sql failure
     *
     * @return void
     */
    public function testAddUserSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("INSERT INTO `tblUsers`"))
            ->willReturn(false);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblUsers` WHERE `login` = ")
            ->willReturn([]);
        $dms = new SeedDMS_Core_DMS($db, '');
        if(self::$dbversion['major'] < 6)
            $role = 1;
        else
            $role = $this->getAdminRole();
        $user = $dms->addUser('new user', 'pwd', 'Full Name', 'newuser@seeddms.org', 'en_GB', 'bootstrap', 'with comment', $role);
        $this->assertFalse($user);
    }

    /**
     * Test getGroup()
     *
     * Get a group by its id
     *
     * @return void
     */
    public function testGetGroup()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblGroups` WHERE `id` = 1")
            ->willReturn([['id'=>1, 'name'=>'foo', 'comment'=>'']]);
        $dms = new SeedDMS_Core_DMS($db, '');
        $group = $dms->getGroup(1);
        $this->assertIsObject($group);
        $this->assertEquals(1, $group->getId());
    }

    /**
     * Test getGroupByName()
     *
     * Get a group by its name
     *
     * qstr must be mocked because it is used in the sql statement to quote
     * the name.
     *
     * @return void
     */
    public function testGetGroupByName()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblGroups` WHERE `name` = 'foo'")
            ->willReturn([['id'=>1, 'name'=>'foo', 'comment'=>'']]);
        $db->expects($this->once())
            ->method('qstr')
            ->will(
                $this->returnCallback(
                    function ($a) {
                        return "'".$a."'";
                    }
                )
            );
        $dms = new SeedDMS_Core_DMS($db, '');
        $group = $dms->getGroupByName('foo');
        $this->assertIsObject($group);
        $this->assertEquals('foo', $group->getName());
    }

    /**
     * Test getAllGroups()
     *
     * The intitial database does not have any groups
     *
     * @return void
     */
    public function testGetAllGroups()
    {
        $groups = self::$dms->getAllGroups();
        $this->assertIsArray($groups);
        $this->assertCount(0, $groups);
    }

    /**
     * Test addGroup()
     *
     * Add a new group and retrieve it afterwards. Also check if the number
     * of groups has increased by one. Add a group with the same name a
     * second time and check if it returns false.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddGroup()
    {
        /* Adding a new group */
        $group = self::$dms->addGroup('new group', 'with comment');
        $this->assertIsObject($group);
        $this->assertEquals('new group', $group->getName());
        /* Adding a group with the same name must fail */
        $group = self::$dms->addGroup('new group', 'with comment');
        $this->assertFalse($group);
        /* There should be one group now */
        $groups = self::$dms->getAllGroups();
        $this->assertIsArray($groups);
        $this->assertCount(1, $groups);
    }

    /**
     * Test addGroupWithPostAddHook()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddGroupWithPostAddHook()
    {
        /* Add the 'onPostAddGroup' callback */
        $ret = 0;
        $callback = function ($param, $group) use (&$ret) {
            $ret = 1; 
        };
        self::$dms->addCallback('onPostAddGroup', $callback, 1);
        /* Adding a new group */
        $group = self::$dms->addGroup('new group', 'with comment');
        $this->assertIsObject($group);
        $this->assertEquals('new group', $group->getName());
        $this->assertEquals(1, $ret);
    }

    /**
     * Test addGroup() with sql failure
     *
     * @return void
     */
    public function testAddGroupSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("INSERT INTO `tblGroups`"))
            ->willReturn(false);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblGroups` WHERE `name` = ")
            ->willReturn([]);
        $dms = new SeedDMS_Core_DMS($db, '');
        $group = $dms->addGroup('new group', 'with comment');
        $this->assertFalse($group);
    }

    /**
     * Test getAllKeywordCategories()
     *
     * The intitial database does not have any keyword categories
     *
     * @return void
     */
    public function testGetAllKeywordCategories()
    {
        $cats = self::$dms->getAllKeywordCategories();
        $this->assertIsArray($cats);
        $this->assertCount(0, $cats);
        /* Even passing bogus ids is handled propperly */
        $cats = self::$dms->getAllKeywordCategories(['kk', '0', 3, true]);
        $this->assertIsArray($cats);
        $this->assertCount(0, $cats);
    }

    /**
     * Test getAllUserKeywordCategories()
     *
     * Method getAllUserKeywordCategories() actually uses
     * getAllKeywordCategories()
     *
     * The intitial database does not have any keyword categories
     *
     * @return void
     */
    public function testGetAllUserKeywordCategories()
    {
        $cats = self::$dms->getAllUserKeywordCategories(1);
        $this->assertIsArray($cats);
        $this->assertCount(0, $cats);
        /* Passing a none existing user id will return an empty array */
        $cats = self::$dms->getAllUserKeywordCategories(3);
        $this->assertIsArray($cats);
        $this->assertCount(0, $cats);
        /* Passing an invalid user id will return false */
        $cats = self::$dms->getAllUserKeywordCategories(0);
        $this->assertFalse($cats);
    }

    /**
     * Test getAllKeywordCategories() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAllKeywordCategoriesSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblKeywordCategories`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $cats = $dms->getAllKeywordCategories();
        $this->assertFalse($cats);
    }

    /**
     * Test addKeywordCategory()
     *
     * Add a new keyword category and retrieve it afterwards. Also check if the
     * number of keyword categories has increased by one. Add a keyword category
     * with the same name a second time and check if it returns false.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddKeywordCategory()
    {
        /* Adding a new keyword category */
        $cat = self::$dms->addKeywordCategory(1, 'new category');
        $this->assertIsObject($cat);
        $this->assertEquals('new category', $cat->getName());

        /* Adding a keyword category for the same user and with the same name must fail */
        $cat = self::$dms->addKeywordCategory(1, 'new category');
        $this->assertFalse($cat);

        /* Adding a keyword category with a non existing user id must fail */
        $cat = self::$dms->addKeywordCategory(0, 'new category');
        $this->assertFalse($cat);

        /* Adding a keyword category with an empty name must fail */
        $cat = self::$dms->addKeywordCategory(1, ' ');
        $this->assertFalse($cat);

        /* Adding a keyword category with a non existing user id must fail */
        //      $cat = self::$dms->addKeywordCategory(3, 'new category');
        //      $this->assertFalse($cat);

        /* There should be 1 keyword category now */
        $cats = self::$dms->getAllKeywordCategories();
        $this->assertIsArray($cats);
        $this->assertCount(1, $cats);
    }

    /**
     * Test addKeywordCategoryWithPostAddHook()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddKeywordCategoryWithPostAddHook()
    {
        /* Add the 'onPostAddKeywordCategory' callback */
        $ret = 0;
        $callback = function ($param, $cat) use (&$ret) {
            $ret = 1; 
        };
        self::$dms->addCallback('onPostAddKeywordCategory', $callback, 1);
        /* Adding a new keyword category */
        $cat = self::$dms->addKeywordCategory(1, 'new category');
        $this->assertIsObject($cat);
        $this->assertEquals('new category', $cat->getName());
        $this->assertEquals(1, $ret);
    }

    /**
     * Test addKeywordCategory() with sql failure
     *
     * @return void
     */
    public function testAddKeywordCategorySqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("INSERT INTO `tblKeywordCategories`"))
            ->willReturn(false);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblKeywordCategories` WHERE `name` ="))
            ->willReturn([]);
        $dms = new SeedDMS_Core_DMS($db, '');
        $cat = $dms->addKeywordCategory(1, 'new category');
        $this->assertFalse($cat);
    }

    /**
     * Test getKeywordCategory()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetKeywordCategory()
    {
        $cat = self::$dms->addKeywordCategory(1, 'new category');
        $cat = self::$dms->getKeywordCategory(1);
        $this->assertInstanceOf(SeedDMS_Core_Keywordcategory::class, $cat);
        $this->assertEquals(1, $cat->getId());
        /* Return false if the id is invalid */
        $cat = self::$dms->getKeywordCategory(0);
        $this->assertFalse($cat);
        /* Return null if the keyword category with the id does not exist */
        $cat = self::$dms->getKeywordCategory(2);
        $this->assertNull($cat);
    }

    /**
     * Test getKeywordCategory() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetKeywordCategorySqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblKeywordCategories` WHERE `id` = 1")
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $cat = $dms->getKeywordCategory(1);
        $this->assertFalse($cat);
    }

    /**
     * Test getKeywordCategoryByName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetKeywordCategoryByName()
    {
        $cat = self::$dms->addKeywordCategory(1, 'new category');
        $cat = self::$dms->getKeywordCategory(1);
        $this->assertInstanceOf(SeedDMS_Core_Keywordcategory::class, $cat);
        $this->assertEquals(1, $cat->getId());
        /* Return false if the user id is invalid */
        $cat = self::$dms->getKeywordCategoryByName('new category', 0);
        $this->assertFalse($cat);
        /* Return null if the keyword category with the passed name does not exist */
        $cat = self::$dms->getKeywordCategoryByName('foo', 1);
        $this->assertNull($cat);
        /* Return category if the keyword category with the passed name exists */
        $cat = self::$dms->getKeywordCategoryByName('new category', 1);
        $this->assertIsObject($cat);
        $this->assertInstanceOf(SeedDMS_Core_Keywordcategory::class, $cat);
    }

    /**
     * Test getKeywordCategoryByName() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetKeywordCategoryByNameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblKeywordCategories` WHERE `name` ="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $cat = $dms->getKeywordCategoryByName('foo', 1);
        $this->assertFalse($cat);
    }

    /**
     * Test getDocumentCategories()
     *
     * The intitial database does not have any document categories
     *
     * @return void
     */
    public function testGetDocumentCategories()
    {
        $cats = self::$dms->getDocumentCategories();
        $this->assertIsArray($cats);
        $this->assertCount(0, $cats);
    }

    /**
     * Test getDocumentCategories() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentCategoriesNameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblCategory`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $cats = $dms->getDocumentCategories();
        $this->assertFalse($cats);
    }

    /**
     * Test getDocumentCategory()
     *
     * The intitial database does not have any document categories
     *
     * @return void
     */
    public function testGetDocumentCategory()
    {
        /* Adding a new keyword category */
        $cat = self::$dms->addDocumentCategory('new category');
        $this->assertIsObject($cat);
        $this->assertEquals('new category', $cat->getName());

        $cat = self::$dms->getDocumentCategory($cat->getId());
        $this->assertIsObject($cat);

        /* Return false if the id is out of range */
        $cat = self::$dms->getDocumentCategory(0);
        $this->assertFalse($cat);

        /* Return null if the keyword category with the id does not exist */
        $cat = self::$dms->getDocumentCategory(2);
        $this->assertNull($cat);
    }

    /**
     * Test getDocumentCategory() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentCategorySqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with("SELECT * FROM `tblCategory` WHERE `id` = 1")
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $cat = $dms->getDocumentCategory(1);
        $this->assertFalse($cat);
    }

    /**
     * Test getDocumentCategoryByName()
     *
     * The intitial database does not have any document categories
     *
     * @return void
     */
    public function testGetDocumentCategoryByName()
    {
        /* Adding a new keyword category with leading and trailing spaces*/
        $cat = self::$dms->addDocumentCategory(' new category ');
        $this->assertIsObject($cat);
        $this->assertEquals('new category', $cat->getName());

        $cat = self::$dms->getDocumentCategoryByName($cat->getName());
        $this->assertIsObject($cat);

        $cat = self::$dms->getDocumentCategoryByName(' ');
        $this->assertFalse($cat);
    }

    /**
     * Test getDocumentCategoryByName() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentCategoryByNameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblCategory` WHERE `name`="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $cat = $dms->getDocumentCategoryByName('foo');
        $this->assertFalse($cat);
    }

    /**
     * Test addDocumentCategory()
     *
     * Add a new keyword category and retrieve it afterwards. Also check if the
     * number of keyword categories has increased by one. Add a keyword category
     * with the same name a second time and check if it returns false.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddDocumentCategory()
    {
        /* Adding a new keyword category */
        $cat = self::$dms->addDocumentCategory('new category');
        $this->assertIsObject($cat);
        $this->assertEquals('new category', $cat->getName());

        /* Adding a document category with the same name must fail */
        $cat = self::$dms->addDocumentCategory('new category');
        $this->assertFalse($cat);

        /* Adding a document category with an empty name must fail */
        $cat = self::$dms->addDocumentCategory(' ');
        $this->assertFalse($cat);

        /* There should be 1 document category now */
        $cats = self::$dms->getDocumentCategories();
        $this->assertIsArray($cats);
        $this->assertCount(1, $cats);
    }

    /**
     * Test addDocumentCategoryWithPostAddHook()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddDocumentCategoryWithPostAddHook()
    {
        /* Add the 'onPostAddDocumentCategory' callback */
        $ret = 0;
        $callback = function ($param, $group) use (&$ret) {
            $ret = 1; 
        };
        self::$dms->addCallback('onPostAddDocumentCategory', $callback, 1);
        /* Adding a new group */
        $cat = self::$dms->addDocumentCategory('new category');
        $this->assertIsObject($cat);
        $this->assertEquals('new category', $cat->getName());
        $this->assertEquals(1, $ret);
    }

    /**
     * Test addDocumentCategory() with sql failure
     *
     * @return void
     */
    public function testAddDocumentCategorySqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("INSERT INTO `tblCategory`"))
            ->willReturn(false);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblCategory` WHERE `name`="))
            ->willReturn([]);
        $dms = new SeedDMS_Core_DMS($db, '');
        $cat = $dms->addDocumentCategory('new category');
        $this->assertFalse($cat);
    }

    /**
     * Test getAttributeDefinition() with a none existing workflow
     *
     * The intitial database does not have any workflows
     *
     * @return void
     */
    public function testGetAttributeDefinitionNoExists()
    {
        $workflow = self::$dms->getAttributeDefinition(1);
        $this->assertNull($workflow);
        /* Passing an id not a numeric value returns false */
        $workflow = self::$dms->getAttributeDefinition('foo');
        $this->assertFalse($workflow);
        /* Passing an id out of range returns false */
        $workflow = self::$dms->getAttributeDefinition(0);
        $this->assertFalse($workflow);
    }

    /**
     * Test getAttributeDefinition() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAttributeDefinitionSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblAttributeDefinitions` WHERE `id` ="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $attrdef = $dms->getAttributeDefinition(1);
        $this->assertFalse($attrdef);
    }

    /**
     * Test getAttributeDefinitionByName() with a none existing workflow
     *
     * The intitial database does not have any workflows
     *
     * @return void
     */
    public function testGetAttributeDefinitionByNameNoExists()
    {
        $workflow = self::$dms->getAttributeDefinitionByName('foo');
        $this->assertNull($workflow);
    }

    /**
     * Test getAttributeDefinitionByName() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAttributeDefinitionByNameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblAttributeDefinitions` WHERE `name` ="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $attrdef = $dms->getAttributeDefinitionByName('foo');
        $this->assertFalse($attrdef);
    }

    /**
     * Test getAllAttributeDefinitions()
     *
     * The intitial database does not have any attribute definitions
     *
     * @return void
     */
    public function testGetAllAttributeDefinitions()
    {
        $attrdefs = self::$dms->getAllAttributeDefinitions();
        $this->assertIsArray($attrdefs);
        $this->assertCount(0, $attrdefs);
    }

    /**
     * Test getAllAttributeDefinitions() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAllAttributeDefinitionsSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblAttributeDefinitions`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $attrdef = $dms->getAllAttributeDefinitions();
        $this->assertFalse($attrdef);
    }

    /**
     * Test addAttributeDefinition()
     *
     * Add a new group and retrieve it afterwards. Also check if the number
     * of groups has increased by one. Add a group with the same name a
     * second time and check if it returns false.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddAttributeDefinition()
    {
        /* Adding a new attribute definition */
        $attrdef = self::$dms->addAttributeDefinition('new attribute definition', SeedDMS_Core_AttributeDefinition::objtype_folder, SeedDMS_Core_AttributeDefinition::type_int, false, 0, 0, '', '');
        $this->assertIsObject($attrdef);
        $this->assertEquals('new attribute definition', $attrdef->getName());
        /* Get the new attribute definition by its id */
        $newattrdef = self::$dms->getAttributeDefinition($attrdef->getId());
        $this->assertIsObject($newattrdef);
        $this->assertEquals($attrdef->getId(), $newattrdef->getId());
        /* Get the new attribute definition by its name */
        $newattrdef = self::$dms->getAttributeDefinitionByName('new attribute definition');
        $this->assertIsObject($newattrdef);
        $this->assertEquals($attrdef->getId(), $newattrdef->getId());
        /* Adding an attribute definition with the same name must fail */
        $attrdef = self::$dms->addAttributeDefinition('new attribute definition', SeedDMS_Core_AttributeDefinition::objtype_folder, SeedDMS_Core_AttributeDefinition::type_int, false, 0, 0, '', '');
        $this->assertFalse($attrdef);
        /* Adding an attribute definition with an empty name must fail */
        $attrdef = self::$dms->addAttributeDefinition(' ', SeedDMS_Core_AttributeDefinition::objtype_folder, SeedDMS_Core_AttributeDefinition::type_int, false, 0, 0, '', '');
        $this->assertFalse($attrdef);
        /* Adding an attribute definition with an invalid object type must fail */
        $attrdef = self::$dms->addAttributeDefinition('no object type', -1, SeedDMS_Core_AttributeDefinition::type_int, false, 0, 0, '', '');
        $this->assertFalse($attrdef);
        /* Adding an attribute definition without a type must fail */
        $attrdef = self::$dms->addAttributeDefinition('no type', SeedDMS_Core_AttributeDefinition::objtype_folder, 0, 0, '', '');
        $this->assertFalse($attrdef);
        /* There should be one attribute definition now */
        $attrdefs = self::$dms->getAllAttributeDefinitions();
        $this->assertIsArray($attrdefs);
        $this->assertCount(1, $attrdefs);
        /* There should be one attribute definition of object type folder now */
        $attrdefs = self::$dms->getAllAttributeDefinitions(SeedDMS_Core_AttributeDefinition::objtype_folder);
        $this->assertIsArray($attrdefs);
        $this->assertCount(1, $attrdefs);
        /* The object type can also be passed as an array */
        $attrdefs = self::$dms->getAllAttributeDefinitions([SeedDMS_Core_AttributeDefinition::objtype_folder]);
        $this->assertIsArray($attrdefs);
        $this->assertCount(1, $attrdefs);
        /* Adding more attribute definitions of different object type */
        $attrdef = self::$dms->addAttributeDefinition('new attribute definition all', SeedDMS_Core_AttributeDefinition::objtype_all, SeedDMS_Core_AttributeDefinition::type_int, false, 0, 0, '', '');
        $this->assertIsObject($attrdef);
        $this->assertEquals('new attribute definition all', $attrdef->getName());
        $attrdef = self::$dms->addAttributeDefinition('new attribute definition document', SeedDMS_Core_AttributeDefinition::objtype_all, SeedDMS_Core_AttributeDefinition::type_int, false, 0, 0, '', '');
        $this->assertIsObject($attrdef);
        $this->assertEquals('new attribute definition document', $attrdef->getName());
        $attrdef = self::$dms->addAttributeDefinition('new attribute definition documentcontent', SeedDMS_Core_AttributeDefinition::objtype_all, SeedDMS_Core_AttributeDefinition::type_int, false, 0, 0, '', '');
        $this->assertIsObject($attrdef);
        $this->assertEquals('new attribute definition documentcontent', $attrdef->getName());
        /* There should be four attribute definitions now */
        $attrdefs = self::$dms->getAllAttributeDefinitions();
        $this->assertIsArray($attrdefs);
        $this->assertCount(4, $attrdefs);
    }

    /**
     * Test getAllWorkflows()
     *
     * The intitial database does not have any workflows
     *
     * @return void
     */
    public function testGetAllWorkflows()
    {
        $workflows = self::$dms->getAllWorkflows();
        $this->assertIsArray($workflows);
        $this->assertCount(0, $workflows);
    }

    /**
     * Test getAllWorkflows() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAllWorkflowsSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblWorkflows`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $workflows = $dms->getAllWorkflows();
        $this->assertFalse($workflows);
    }

    /**
     * Test getWorkflow() with a none existing workflow
     *
     * The intitial database does not have any workflows
     *
     * @return void
     */
    public function testGetWorkflowNoExists()
    {
        $workflow = self::$dms->getWorkflow(1);
        $this->assertNull($workflow);
        /* Passing an id not a numeric value returns false */
        $workflow = self::$dms->getWorkflow('foo');
        $this->assertFalse($workflow);
        /* Passing an id out of range returns false */
        $workflow = self::$dms->getWorkflow(0);
        $this->assertFalse($workflow);
    }

    /**
     * Test getWorkflow() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetWorkflowSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblWorkflows` WHERE `id`="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $workflow = $dms->getWorkflow(1);
        $this->assertFalse($workflow);
    }

    /**
     * Test getWorkflowByName() with a none existing workflow
     *
     * The intitial database does not have any workflows
     *
     * @return void
     */
    public function testGetWorkflowByNameNoExists()
    {
        $workflow = self::$dms->getWorkflowByName('foo');
        $this->assertNull($workflow);
    }

    /**
     * Test getWorkflowByName() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetWorkflowByNameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblWorkflows` WHERE `name`="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $workflow = $dms->getWorkflowByName('foo');
        $this->assertFalse($workflow);
    }

    /**
     * Test addWorkflow()
     *
     * Add a new workflow and retrieve it afterwards. Also check if the number
     * of workflows has increased by one. Add a workflow with the same name a
     * second time and check if it returns false.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddWorkflow()
    {
        /* Adding a new workflow */
        $workflowstate = self::$dms->addWorkflowState('new workflow state', S_RELEASED);
        $workflow = self::$dms->addWorkflow('new workflow', $workflowstate);
        $this->assertIsObject($workflow);
        $this->assertEquals('new workflow', $workflow->getName());
        /* Adding a workflow with the same name must fail */
        $workflow = self::$dms->addWorkflow('new workflow', $workflowstate);
        $this->assertFalse($workflow);
        /* Adding a workflow with an empty name must fail */
        $workflow = self::$dms->addWorkflow(' ', $workflowstate);
        $this->assertFalse($workflow);
        /* There should be one workflow now */
        $workflows = self::$dms->getAllWorkflows();
        $this->assertIsArray($workflows);
        $this->assertCount(1, $workflows);
    }

    /**
     * Test getAllWorkflowStates()
     *
     * The intitial database does not have any workflow states
     *
     * @return void
     */
    public function testGetAllWorkflowStates()
    {
        $states = self::$dms->getAllWorkflowStates();
        $this->assertIsArray($states);
        $this->assertCount(0, $states);
    }

    /**
     * Test getAllWorkflowStates() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAllWorkflowStatesSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblWorkflowStates`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $states = $dms->getAllWorkflowStates();
        $this->assertFalse($states);
    }

    /**
     * Test getWorkflowState() with a none existing workflow state
     *
     * The intitial database does not have any workflow states
     *
     * @return void
     */
    public function testGetWorkflowStateNoExists()
    {
        $workflowstate = self::$dms->getWorkflowState(1);
        $this->assertNull($workflowstate);
        /* Passing an id not a numeric value returns false */
        $workflowstate = self::$dms->getWorkflowState('foo');
        $this->assertFalse($workflowstate);
        /* Passing an id out of range returns false */
        $workflowstate = self::$dms->getWorkflowState(0);
        $this->assertFalse($workflowstate);
    }

    /**
     * Test getWorkflowState() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetWorkflowStateSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblWorkflowStates` WHERE `id` ="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $state = $dms->getWorkflowState(1);
        $this->assertFalse($state);
    }

    /**
     * Test getWorkflowStateByName() with a none existing workflow state
     *
     * The intitial database does not have any workflow states
     *
     * @return void
     */
    public function testGetWorkflowStateByNameNoExists()
    {
        $workflowstate = self::$dms->getWorkflowStateByName('foo');
        $this->assertNull($workflowstate);
    }

    /**
     * Test getWorkflowStateByName() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetWorkflowStateByNameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblWorkflowStates` WHERE `name`="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $state = $dms->getWorkflowStateByName('foo');
        $this->assertFalse($state);
    }

    /**
     * Test addWorkflowState()
     *
     * Add a new workflow state and retrieve it afterwards. Also check if the number
     * of workflow states has increased by one. Add a workflow state with the same name a
     * second time and check if it returns false.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddWorkflowState()
    {
        /* Adding a new workflow state */
        $workflowstate = self::$dms->addWorkflowState('new workflow state', S_RELEASED);
        $this->assertIsObject($workflowstate);
        $this->assertEquals('new workflow state', $workflowstate->getName());
        /* Adding a workflow state with the same name must fail */
        $workflowstate = self::$dms->addWorkflowState('new workflow state', S_RELEASED);
        $this->assertFalse($workflowstate);
        /* Adding a workflow state with an empty name must fail */
        $workflowstate = self::$dms->addWorkflowState(' ', S_RELEASED);
        $this->assertFalse($workflowstate);
        /* There should be one workflow state now */
        $workflowstates = self::$dms->getAllWorkflowStates();
        $this->assertIsArray($workflowstates);
        $this->assertCount(1, $workflowstates);
    }

    /**
     * Test getAllWorkflowActions()
     *
     * The intitial database does not have any workflow actions
     *
     * @return void
     */
    public function testGetAllWorkflowActions()
    {
        $actions = self::$dms->getAllWorkflowActions();
        $this->assertIsArray($actions);
        $this->assertCount(0, $actions);
    }

    /**
     * Test getAllWorkflowActions() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAllWorkflowActionsSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblWorkflowActions`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $actions = $dms->getAllWorkflowActions();
        $this->assertFalse($actions);
    }

    /**
     * Test getWorkflowAction() with a none existing workflow
     *
     * The intitial database does not have any workflow actions
     *
     * @return void
     */
    public function testGetWorkflowActionNoExists()
    {
        $workflowaction = self::$dms->getWorkflowAction(1);
        $this->assertNull($workflowaction);
        /* Passing an id not a numeric value returns false */
        $workflowaction = self::$dms->getWorkflowAction('foo');
        $this->assertFalse($workflowaction);
        /* Passing an id out of range returns false */
        $workflowaction = self::$dms->getWorkflowAction(0);
        $this->assertFalse($workflowaction);
    }

    /**
     * Test getWorkflowAction() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetWorkflowActionSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblWorkflowActions` WHERE `id` ="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $action = $dms->getWorkflowAction(1);
        $this->assertFalse($action);
    }

    /**
     * Test getWorkflowActionByName() with a none existing workflow action
     *
     * The intitial database does not have any workflow actions
     *
     * @return void
     */
    public function testGetWorkflowActionByNameNoExists()
    {
        $workflowaction = self::$dms->getWorkflowActionByName('foo');
        $this->assertNull($workflowaction);
    }

    /**
     * Test getWorkflowActionByName() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetWorkflowActionByNameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblWorkflowActions` WHERE `name` ="))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $action = $dms->getWorkflowActionByName('foo');
        $this->assertFalse($action);
    }

    /**
     * Test addWorkflowAction()
     *
     * Add a new workflow state and retrieve it afterwards. Also check if the number
     * of workflow states has increased by one. Add a workflow state with the same name a
     * second time and check if it returns false.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddWorkflowAction()
    {
        /* Adding a new workflow action */
        $workflowaction = self::$dms->addWorkflowAction('new workflow action', S_RELEASED);
        $this->assertIsObject($workflowaction);
        $this->assertEquals('new workflow action', $workflowaction->getName());
        /* Adding a workflow action with the same name must fail */
        $workflowaction = self::$dms->addWorkflowAction('new workflow action', S_RELEASED);
        $this->assertFalse($workflowaction);
        /* Adding a workflow action with an empty name must fail */
        $workflowaction = self::$dms->addWorkflowAction(' ', S_RELEASED);
        $this->assertFalse($workflowaction);
        /* There should be one workflow action now */
        $workflowactions = self::$dms->getAllWorkflowActions();
        $this->assertIsArray($workflowactions);
        $this->assertCount(1, $workflowactions);
    }

    /**
     * Test getStatisticalData()
     *
     * @return void
     */
    public function testGetStatisticalData()
    {
        /* There should one folder (root folder) */
        $data = self::$dms->getStatisticalData('foldersperuser');
        $this->assertIsArray($data);
        $this->assertEquals(1, $data[0]['total']);
        /* There should be no documents */
        foreach (array('docsperuser', 'docspermimetype', 'docspercategory', 'docspermonth', 'docsperstatus', 'docsaccumulated', 'sizeperuser') as $type) {
            $data = self::$dms->getStatisticalData($type);
            $this->assertIsArray($data);
            $this->assertCount(0, $data);
        }
        /* Passing an unknown name returns an empty array */
        $data = self::$dms->getStatisticalData('foo');
        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    /**
     * Test getStatisticalDataFail()
     *
     * Check if getStatisticalData() fails if the sql statements fail
     *
     * @return void
     */
    public function testGetStatisticalDataFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->any())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT "))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        foreach (array('foldersperuser', 'docsperuser', 'docspermimetype', 'docspercategory', 'docspermonth', 'docsperstatus', 'docsaccumulated', 'sizeperuser') as $type) {
            $data = $dms->getStatisticalData($type);
            $this->assertFalse($data);
        }
    }

    /**
     * Test createPasswordRequest() and checkPasswordRequest()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testCreateAndCheckAndDeletePasswordRequest()
    {
        $user = self::$dms->getUser(1);
        $hash = self::$dms->createPasswordRequest($user);
        $this->assertIsString($hash);
        $user = self::$dms->checkPasswordRequest($hash);
        $this->assertIsObject($user);
        $this->assertEquals(1, $user->getId());
        /* Check a non existing hash */
        $user = self::$dms->checkPasswordRequest('foo');
        $this->assertFalse($user);
        /* Delete the hash */
        $ret = self::$dms->deletePasswordRequest($hash);
        $this->assertTrue($ret);
        /* Checking the hash again must return false, because it was deleted */
        $user = self::$dms->checkPasswordRequest($hash);
        $this->assertFalse($user);
    }

    /**
     * Test method checkPasswordRequest() with sql failure
     *
     * @return void
     */
    public function testCheckPasswordRequestSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblUserPasswordRequest`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->checkPasswordRequest('foo'));
    }

    /**
     * Test method deletePasswordRequest() with sql failure
     *
     * @return void
     */
    public function testDeletePasswordRequestSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("DELETE FROM `tblUserPasswordRequest`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->deletePasswordRequest('foo'));
    }

    /**
     * Test getTimeline()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetTimeline()
    {
        $timeline = self::$dms->getTimeline();
        $this->assertIsArray($timeline);
    }

    /**
     * Test getUnlinkedDocumentContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetUnlinkedDocumentContent()
    {
        $contents = self::$dms->getUnlinkedDocumentContent();
        $this->assertIsArray($contents);
        $this->assertCount(0, $contents);
    }

    /**
     * Test getNoFileSizeDocumentContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetNoFileSizeDocumentContent()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $contents = self::$dms->getNoFileSizeDocumentContent();
        $this->assertIsArray($contents);
        $this->assertCount(0, $contents);
        /* Manipulate the file size right in the database */
        $dbh = self::$dms->getDB();
        $ret = $dbh->getResult("UPDATE `tblDocumentContent` SET `fileSize` = 0");
        $this->assertTrue($ret);
        $contents = self::$dms->getNoFileSizeDocumentContent();
        $this->assertIsArray($contents);
        $this->assertCount(1, $contents);
    }

    /**
     * Test getNoFileSizeDocumentContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetNoFileSizeDocumentContentSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblDocumentContent` WHERE `fileSize`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->getNoFileSizeDocumentContent());
    }

    /**
     * Test getNoChecksumDocumentContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetNoChecksumDocumentContent()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $contents = self::$dms->getNoChecksumDocumentContent();
        $this->assertIsArray($contents);
        $this->assertCount(0, $contents);
        /* Manipulate the checksum right in the database */
        $dbh = self::$dms->getDB();
        $ret = $dbh->getResult("UPDATE `tblDocumentContent` SET `checksum` = null");
        $this->assertTrue($ret);
        $contents = self::$dms->getNoChecksumDocumentContent();
        $this->assertIsArray($contents);
        $this->assertCount(1, $contents);
    }

    /**
     * Test getNoChecksumDocumentContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetNoChecksumDocumentContentSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblDocumentContent` WHERE `checksum`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->getNoChecksumDocumentContent());
    }

    /**
     * Test getDuplicateDocumentContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDuplicateDocumentContent()
    {
        $contents = self::$dms->getDuplicateDocumentContent();
        $this->assertIsArray($contents);
        $this->assertCount(0, $contents);
    }

    /**
     * Test getDuplicateDocumentContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDuplicateDocumentContentWithDuplicates()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $document1 = self::createDocument($rootfolder, $user, 'Document 1');

        $filename = self::createTempFile(200);
        list($document2, $res) = $rootfolder->addDocument(
            'Documet 2', // name
            '', // comment
            null, // no expiration
            $user, // owner
            '', // keywords
            [], // categories
            $filename, // name of file
            'file1.txt', // original file name
            '.txt', // file type
            'text/plain', // mime type
            1.0 // sequence
        );
        list($document3, $res) = $rootfolder->addDocument(
            'Documet 3', // name
            '', // comment
            null, // no expiration
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

        $contents = self::$dms->getDuplicateDocumentContent();
        $this->assertIsArray($contents);
        $this->assertCount(1, $contents);
    }

    /**
     * Test method getDuplicateDocumentContent() with sql failure
     *
     * @return void
     */
    public function testGetDuplicateDocumentContentSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT a.*, b.`id` as dupid FROM `tblDocumentContent`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->getDuplicateDocumentContent());
    }

    /**
     * Test getNotificationsByUser()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetNotificationsByUser()
    {
        $user = self::$dms->getUser(1);
        $notifications = self::$dms->getNotificationsByUser($user, 0);
        $this->assertIsArray($notifications);
        $this->assertCount(0, $notifications);
        $notifications = self::$dms->getNotificationsByUser($user, 1);
        $this->assertIsArray($notifications);
        $this->assertCount(0, $notifications);
    }

    /**
     * Test getNotificationsByGroup()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetNotificationsByGroup()
    {
        $group = self::$dms->addGroup('new group', 'with comment');
        $this->assertIsObject($group);
        $notifications = self::$dms->getNotificationsByGroup($group, 0);
        $this->assertIsArray($notifications);
        $this->assertCount(0, $notifications);
        $notifications = self::$dms->getNotificationsByGroup($group, 1);
        $this->assertIsArray($notifications);
        $this->assertCount(0, $notifications);
    }

    /**
     * Test getDocumentsExpired()
     *
     * Check if getDocumentsExpired() fails if the parameters are wrong
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentsExpiredFail()
    {
        $documents = self::$dms->getDocumentsExpired(false);
        $this->assertFalse($documents);
        $documents = self::$dms->getDocumentsExpired('2021-04');
        $this->assertFalse($documents);
        $documents = self::$dms->getDocumentsExpired('2021-01-32');
        $this->assertFalse($documents);
        $documents = self::$dms->getDocumentsExpired('2021-01-31'); // valid date
        $this->assertIsArray($documents);
    }

    /**
     * Test method getDocumentsExpired() with sql failure
     *
     * @return void
     */
    public function testGetDocumentsExpiredSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('createTemporaryTable')
            ->with('ttstatid')
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->getDocumentsExpired(1));
    }

    /**
     * Test getDocumentByName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentByName()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $rootfolder);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $subfolder);

        /* Add a new document */
        $filename = self::createTempFile(200);
        list($document, $res) = $subfolder->addDocument(
            'Document 1', // name
            '', // comment
            null, // no expiration
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
        /* Search without a parent folder restriction */
        $document = self::$dms->getDocumentByName('Document 1');
        $this->assertInstanceOf(SeedDMS_Core_Document::class, $document);
        /* Searching in the root folder will return no document */
        $document = self::$dms->getDocumentByName('Document 1', $rootfolder);
        $this->assertNull($document);
        /* Searching in the sub folder will return the document */
        $document = self::$dms->getDocumentByName('Document 1', $subfolder);
        $this->assertInstanceOf(SeedDMS_Core_Document::class, $document);
        /* Searching for an empty name returns false */
        $document = self::$dms->getDocumentByName('  ');
        $this->assertFalse($document);
    }

    /**
     * Test getDocumentByName() with sql failure
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentByNameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document = $dms->getDocumentByName('foo');
        $this->assertFalse($document);
    }

    /**
     * Test getDocumentByOriginalFilename()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentByOriginalFilename()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $rootfolder);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $subfolder);

        /* Add a new document */
        $filename = self::createTempFile(200);
        list($document, $res) = $subfolder->addDocument(
            'Document 1', // name
            '', // comment
            null, // no expiration
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
        /* Search without a parent folder restriction */
        $document = self::$dms->getDocumentByOriginalFilename('file1.txt');
        $this->assertInstanceOf(SeedDMS_Core_Document::class, $document);
        /* Searching in the root folder will return no document */
        $document = self::$dms->getDocumentByOriginalFilename('file1.txt', $rootfolder);
        $this->assertNull($document);
        /* Searching in the sub folder will return the document */
        $document = self::$dms->getDocumentByOriginalFilename('file1.txt', $subfolder);
        $this->assertInstanceOf(SeedDMS_Core_Document::class, $document);
        /* Searching for an empty name returns false */
        $document = self::$dms->getDocumentByOriginalFilename('  ');
        $this->assertFalse($document);
    }

    /**
     * Test method getDocumentByOriginalFilename() with sql failure
     *
     * @return void
     */
    public function testGetDocumentByOriginalFilenameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('createTemporaryTable')
            ->with('ttcontentid')
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->getDocumentByOriginalFilename(1));
    }

    /**
     * Test getDocumentList()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentList()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $rootfolder);

        /* Add a new document */
        $filename = self::createTempFile(200);
        list($document, $res) = $rootfolder->addDocument(
            'Document 1', // name
            '', // comment
            null, // no expiration
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

        /* Add a second new document */
        $filename = self::createTempFile(200);
        list($document, $res) = $rootfolder->addDocument(
            'Document 2', // name
            '', // comment
            mktime(0, 0, 0), // expires today
            $user, // owner
            '', // keywords
            [], // categories
            $filename, // name of file
            'file2.txt', // original file name
            '.txt', // file type
            'text/plain', // mime type
            1.0 // sequence
        );
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        $this->assertIsObject($document);

        $documents = self::$dms->getDocumentList('MyDocs', $user);
        $this->assertIsArray($documents);
        $this->assertCount(2, $documents);
        /* All documents expiring from 1 year ago till today */
        $documents = self::$dms->getDocumentList('ExpiredOwner', $user);
        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        /* All documents expiring today */
        $documents = self::$dms->getDocumentList('ExpiredOwner', $user, 0);
        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        /* All documents expiring tomorrow */
        $documents = self::$dms->getDocumentList('ExpiredOwner', $user, date("Y-m-d", time()+86400));
        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        /* Get unknown list */
        $documents = self::$dms->getDocumentList('foo', $user);
        $this->assertFalse($documents);
    }

    /**
     * Test search()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSearch()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $rootfolder);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $subfolder);

        /* Add a new document to the subfolder*/
        $filename = self::createTempFile(200);
        list($document, $res) = $subfolder->addDocument(
            'Document 1', // name
            '', // comment
            null, // no expiration
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
        /* Add a new document to the root folder. All documents expire. The first
         * expires today, the second expires tomorrow, etc.
         */
        for ($i=2; $i<=30; $i++) {
            $filename = self::createTempFile(200);
            list($document, $res) = $rootfolder->addDocument(
                'Document '.$i, // name
                '', // comment
                mktime(0, 0, 0)+($i-2)*86400, // expires in $i-2 days
                $user, // owner
                '', // keywords
                [], // categories
                $filename, // name of file
                'file'.$i.'.txt', // original file name
                '.txt', // file type
                'text/plain', // mime type
                1.0+$i // sequence
            );
            $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        }
        $hits = self::$dms->search(['query'=>'Document']);
        $this->assertIsArray($hits);
        $this->assertCount(5, $hits);
        $this->assertEquals(30, $hits['totalDocs']);
        $this->assertCount(30, $hits['docs']);
        /* Limit number of documents to 10 */
        $hits = self::$dms->search(['query'=>'Document', 'limit'=>10]);
        $this->assertIsArray($hits);
        $this->assertCount(5, $hits);
        $this->assertEquals(30, $hits['totalDocs']);
        $this->assertCount(10, $hits['docs']);
        /* Same number of documents if startFolder is the root folder */
        $hits = self::$dms->search(['query'=>'Document', 'startFolder'=>$rootfolder]);
        $this->assertIsArray($hits);
        $this->assertCount(5, $hits);
        $this->assertEquals(30, $hits['totalDocs']);
        $this->assertCount(30, $hits['docs']);
        /* There is just one document below the sub folder */
        $hits = self::$dms->search(['query'=>'Document', 'startFolder'=>$subfolder]);
        $this->assertIsArray($hits);
        $this->assertCount(5, $hits);
        $this->assertEquals(1, $hits['totalDocs']);
        /* Get documents with a given expiration date in the future
         * All documents in subfolder, but not the one in the root folder
         */
        $expts = mktime(0, 0, 0);
        $expstart = [
            'year'=>date('Y', $expts),
            'month'=>date('m', $expts),
            'day'=>date('d', $expts),
            'hour'=>date('H', $expts),
            'minute'=>date('i', $expts),
            'second'=>date('s', $expts)
        ];
        $hits = self::$dms->search(['query'=>'Document', 'expirationstartdate'=>$expstart]);
        $this->assertIsArray($hits);
        $this->assertCount(5, $hits);
        $this->assertEquals(29, $hits['totalDocs']);
        /* Get documents with a given expiration date in the future, starting tomorrow
         * All documents in subfolder - 1
         */
        $expts = mktime(0, 0, 0)+86400;
        $expstart = [
            'year'=>date('Y', $expts),
            'month'=>date('m', $expts),
            'day'=>date('d', $expts),
            'hour'=>date('H', $expts),
            'minute'=>date('i', $expts),
            'second'=>date('s', $expts)
        ];
        $hits = self::$dms->search(['query'=>'Document', 'expirationstartdate'=>$expstart]);
        $this->assertIsArray($hits);
        $this->assertCount(5, $hits);
        $this->assertEquals(28, $hits['totalDocs']);
        /* Get documents expire today or tomorrow
         * 2 documents in subfolder 
         */
        $expts = mktime(0, 0, 0);
        $expstart = [
            'year'=>date('Y', $expts),
            'month'=>date('m', $expts),
            'day'=>date('d', $expts),
            'hour'=>date('H', $expts),
            'minute'=>date('i', $expts),
            'second'=>date('s', $expts)
        ];
        $expts += 1*86400;
        $expstop = [
            'year'=>date('Y', $expts),
            'month'=>date('m', $expts),
            'day'=>date('d', $expts),
            'hour'=>date('H', $expts),
            'minute'=>date('i', $expts),
            'second'=>date('s', $expts)
        ];
        $hits = self::$dms->search(['query'=>'Document', 'expirationstartdate'=>$expstart, 'expirationenddate'=>$expstop]);
        $this->assertIsArray($hits);
        $this->assertCount(5, $hits);
        $this->assertEquals(2, $hits['totalDocs']);
        /* Get documents expire before and tomorrow
         * 2 documents in subfolder 
         */
        $expts = mktime(0, 0, 0); // Start of today
        $expts += 1*86400; // Start of tomorrow
        $expstop = [
            'year'=>date('Y', $expts),
            'month'=>date('m', $expts),
            'day'=>date('d', $expts),
            'hour'=>date('H', $expts),
            'minute'=>date('i', $expts),
            'second'=>date('s', $expts)
        ];
        $hits = self::$dms->search(['query'=>'Document', 'expirationenddate'=>$expstop]);
        $this->assertIsArray($hits);
        $this->assertCount(5, $hits);
        $this->assertEquals(2, $hits['totalDocs']);
    }

}

