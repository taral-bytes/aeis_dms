<?php
/**
 * Implementation of the folder tests
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
class FolderTest extends SeedDmsTest
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
     * Test method getInstance()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetInstanceRootFolder()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $this->assertIsObject($folder);
        $this->assertEquals('DMS', $folder->getName());
        /* get instance of none existing folder */
        $folder = SeedDMS_Core_Folder::getInstance(2, self::$dms);
        $this->assertNull($folder);
    }

    /**
     * Test method isType()
     *
     * @return void
     */
    public function testIsType()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $this->assertTrue($folder->isType('folder'));
    }

    /**
     * Test method getInstance()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetInstance()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subsubfolder = $subfolder->addSubFolder('Subsubfolder 1', '', $user, 1.0);
        /* Get the folder with id 2, which must be 'Subfolder 1' */
        $folder = SeedDMS_Core_Folder::getInstance(2, self::$dms);
        $this->assertIsObject($folder);
        $this->assertEquals('Subfolder 1', $folder->getName());
        /* Get a none existing folder */
        $folder = SeedDMS_Core_Folder::getInstance(4, self::$dms);
        $this->assertNull($folder);
    }

    /**
     * Test method getInstance()
     *
     * @return void
     */
    public function testGetInstanceSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblFolders`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse(SeedDMS_Core_Folder::getInstance(1, $dms));
    }

    /**
     * Test method getInstanceByName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetInstanceByName()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subsubfolder = $subfolder->addSubFolder('Subsubfolder 1', '', $user, 1.0);
        /* Search for it anywhere in the folder hierarchy */
        $folder = SeedDMS_Core_Folder::getInstanceByName('Subsubfolder 1', null, self::$dms);
        $this->assertIsObject($folder);
        $this->assertEquals('Subsubfolder 1', $folder->getName());
        /* Search for it within 'Subfolder 1' will find it */
        $folder = SeedDMS_Core_Folder::getInstanceByName('Subsubfolder 1', $subfolder, self::$dms);
        $this->assertIsObject($folder);
        $this->assertEquals('Subsubfolder 1', $folder->getName());
        /* Search for it within root folder will not find it */
        $folder = SeedDMS_Core_Folder::getInstanceByName('Subsubfolder 1', $rootfolder, self::$dms);
        $this->assertNull($folder);
    }

    /**
     * Test method getInstanceByName()
     *
     * @return void
     */
    public function testGetInstanceByNameSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblFolders`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse(SeedDMS_Core_Folder::getInstanceByName('foo', null, $dms));
    }

    /**
     * Test method getName() and setName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetName()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $name = $folder->getName();
        $this->assertEquals('DMS', $name);
        $ret = $folder->setName('foo');
        $this->assertTrue($ret);
        $name = $folder->getName();
        $this->assertEquals('foo', $name);
    }

    /**
     * Test method setName()
     *
     * @return void
     */
    public function testSetNameSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblFolders` SET `name`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->setName('foo'));
    }

    /**
     * Test method getComment() and setComment()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetComment()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $comment = $folder->getComment();
        $this->assertEquals('DMS root', $comment);
        $ret = $folder->setComment('foo');
        $this->assertTrue($ret);
        $comment = $folder->getComment();
        $this->assertEquals('foo', $comment);
    }

    /**
     * Test method setComment()
     *
     * @return void
     */
    public function testSetCommentSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblFolders` SET `comment`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->setComment('foo'));
    }

    /**
     * Test method getSequence() and setSequence()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetSequence()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        /* The root folder's sequence in the initial database is 0.0 */
        $sequence = $folder->getSequence();
        $this->assertEquals(0.0, $sequence);
        $ret = $folder->setSequence(1.5);
        $this->assertTrue($ret);
        $sequence = $folder->getSequence();
        $this->assertEquals(1.5, $sequence);
    }

    /**
     * Test method setSequence()
     *
     * @return void
     */
    public function testSetSequenceSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblFolders` SET `sequence`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->setSequence(0.0));
    }

    /**
     * Test method getDate() and setDate()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetDate()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $now = time();
        /* Passing false as a time stamp will take current time stamp */
        $ret = $folder->setDate(false);
        $this->assertTrue($ret);
        $date = $folder->getDate();
        $this->assertEquals($now, $date);
        /* Setting a time stamp */
        $now -= 1000;
        $ret = $folder->setDate($now);
        $this->assertTrue($ret);
        $date = $folder->getDate();
        $this->assertEquals($now, $date);
        /* Setting a none numeric value will fail */
        $ret = $folder->setDate('foo');
        $this->assertFalse($ret);
    }

    /**
     * Test method setDate()
     *
     * @return void
     */
    public function testSetDateSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblFolders` SET `date`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->setDate(time()));
    }

    /**
     * Test method getParent()
     *
     * Get parent of root folder which is always null.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetParentRootFolder()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $parent = $folder->getParent();
        $this->assertNull($parent);
    }

    /**
     * Test method getParent()
     *
     * Create a new subfolder below root folder and check if parent
     * of the folder is the root folder.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetParent()
    {
        $adminuser = self::$dms->getUser(1);
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $adminuser, 0);
        $parent = $subfolder->getParent();
        $this->assertIsObject($parent);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $parent);
        $this->assertEquals(1, $parent->getId());
    }

    /**
     * Test method setParent() on root folder
     *
     * Moving the root folder will always fail
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetParentRootFolder()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $ret = $folder->setParent(1);
        $this->assertFalse($ret);
    }

    /**
     * Test method getOwner()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetOwner()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $owner = $folder->getOwner();
        $this->assertIsObject($owner);
        $this->assertInstanceOf(SeedDMS_Core_User::class, $owner);
        $this->assertEquals(1, $owner->getId());
    }

    /**
     * Test method setOwner()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetOwner()
    {
        $adminuser = self::$dms->getUser(1);
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->addUser('user1', 'user1', 'User One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $adminuser, 0);
        $res = $subfolder->setOwner($user);
        $this->assertTrue($res);
        $owner = $subfolder->getOwner();
        $this->assertIsObject($owner);
        $this->assertInstanceOf(SeedDMS_Core_User::class, $owner);
        $this->assertEquals($user->getId(), $owner->getId());
    }

    /**
     * Test method setOwner()
     *
     * @return void
     */
    public function testSetOwnerSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $user = new SeedDMS_Core_User(1, 'admin', 'pass', 'Joe Foo', 'baz@foo.de', 'en_GB', 'bootstrap', 'My comment', SeedDMS_Core_User::role_admin);
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblFolders` SET `owner`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->setOwner($user));
    }

    /**
     * Test method getDefaultAccess()
     *
     * The default access is always M_READ unless it was set differently
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDefaultAccess()
    {
        $adminuser = self::$dms->getUser(1);
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $accessmode = $rootfolder->getDefaultAccess();
        $this->assertEquals(M_READ, $accessmode);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $adminuser, 0);
        $accessmode = $subfolder->getDefaultAccess();
        $this->assertEquals(M_READ, $accessmode);
    }

    /**
     * Test method setDefaultAccess()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetDefaultAccess()
    {
        $adminuser = self::$dms->getUser(1);
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $adminuser, 0);
        /* Setting the default access to something != M_READ will not have
         * any effect as long as inheritage of access rights is turned on.
         */
        $subfolder->setDefaultAccess(M_READWRITE, true);
        $accessmode = $subfolder->getDefaultAccess();
        $this->assertEquals(M_READ, $accessmode);
        /* Turning inheritage off will use the default access */
        $subfolder->setInheritAccess(false, true);
        $accessmode = $subfolder->getDefaultAccess();
        $this->assertEquals(M_READWRITE, $accessmode);
    }

    /**
     * Test method setDefaultAccess()
     *
     * @return void
     */
    public function testSetDefaultAccessSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblFolders` SET `defaultAccess`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->setDefaultAccess(M_NONE));
    }

    /**
     * Test method setInheritAccess()
     *
     * @return void
     */
    public function testSetInheritAccessSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblFolders` SET `inheritAccess`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->setInheritAccess(true));
    }

    /**
     * Test method hasSubFolders() on root folder and after adding
     * new subfolders.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testHasSubFolders()
    {
        $user = self::$dms->getUser(1);
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $ret = $rootfolder->hasSubFolders();
        $this->assertIsInt($ret);
        $this->assertEquals(0, $ret);
        $subfolder1 = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subfolder2 = $rootfolder->addSubFolder('Subfolder 2', '', $user, 1.0);
        $ret = $rootfolder->hasSubFolders();
        $this->assertIsInt($ret);
        $this->assertEquals(2, $ret);
        /* hasSubFolderByName() just returns true or false */
        $ret = $rootfolder->hasSubFolderByName('Subfolder 1');
        $this->assertTrue($ret);
        $ret = $rootfolder->hasSubFolderByName('Subfolder 3');
        $this->assertFalse($ret);
    }

    /**
     * Test method hasSubFolders with sql fail()
     *
     * @return void
     */
    public function testHasSubFoldersSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT count(*) as c FROM `tblFolders`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->hasSubFolders());
    }

    /**
     * Test method hasSubFolderByName with sql fail()
     *
     * @return void
     */
    public function testHasSubFolderByNameSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT count(*) as c FROM `tblFolders`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->hasSubFolderByName('foo'));
    }

    /**
     * Test method getSubFolders() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetSubFoldersRootOnly()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $folders = $folder->getSubFolders();
        $this->assertIsArray($folders);
        $this->assertCount(0, $folders);
    }

    /**
     * Test method getSubFolders() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetSubFolders()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $subfolder1 = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subfolder2 = $rootfolder->addSubFolder('Subfolder 2', '', $user, 1.0);
        $folders = $rootfolder->getSubFolders();
        $this->assertIsArray($folders);
        $this->assertCount(2, $folders);

        /* Get sub folders order by name descending */
        $rootfolder->clearCache(); // Force retrieving sub folders from database
        $folders = $rootfolder->getSubFolders('n', 'desc', 1, 0);
        $this->assertIsArray($folders);
        $this->assertCount(1, $folders);
        $this->assertEquals('Subfolder 2', $folders[0]->getName());

        /* Get sub folders order by name descending with an offset of 1 */
        $rootfolder->clearCache(); // Force retrieving sub folders from database
        $folders = $rootfolder->getSubFolders('n', 'desc', 1, 1);
        $this->assertIsArray($folders);
        $this->assertCount(1, $folders);
        $this->assertEquals('Subfolder 1', $folders[0]->getName());

        /* Get sub folders order by sequence ascending */
        $rootfolder->clearCache(); // Force retrieving sub folders from database
        $folders = $rootfolder->getSubFolders('s', 'asc', 1, 0);
        $this->assertIsArray($folders);
        $this->assertCount(1, $folders);
        $this->assertEquals('Subfolder 2', $folders[0]->getName());

        /* Get sub folders order by sequence ascending with a bogus offset */
        $rootfolder->clearCache(); // Force retrieving sub folders from database
        $folders = $rootfolder->getSubFolders('s', 'asc', 0, 4);
        $this->assertIsArray($folders);
        $this->assertCount(2, $folders);
        $this->assertEquals('Subfolder 2', $folders[0]->getName());
    }

    /**
     * Test method getSubFolders()
     *
     * @return void
     */
    public function testGetSubFoldersSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblFolders`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->getSubFolders());
    }

    /**
     * Test method isDescendant()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testIsDescendant()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subsubfolder = $subfolder->addSubFolder('Subsubfolder 1', '', $user, 1.0);
        /* subsubfolder is a descendant of root folder */
        $this->assertTrue($subsubfolder->isDescendant($rootfolder));
        /* subfolder is not a descendant of subsubfolder */
        $this->assertFalse($subfolder->isDescendant($subsubfolder));
    }

    /**
     * Test method isSubFolder()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testIsSubFolder()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subsubfolder = $subfolder->addSubFolder('Subsubfolder 1', '', $user, 1.0);
        $this->assertTrue($rootfolder->isSubFolder($subsubfolder));
        $this->assertFalse($subsubfolder->isSubFolder($subfolder));
    }

    /**
     * Test method setParent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetParent()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subsubfolder = $subfolder->addSubFolder('Subsubfolder 1', '', $user, 1.0);
        /* Add a new document for folderList checking afterwards */
        $document = self::createDocument($subsubfolder, $user, 'Document 1');
        $folderlist = $subsubfolder->getFolderList();
        $this->assertEquals(':1:2:', $folderlist);
        $folderlist = $document->getFolderList();
        $this->assertEquals(':1:2:3:', $folderlist);
        /* Making $subsubfolder parent of $subfolder will fail, because
         * $subfolder is a parent of $subsubfolder
         */
        $this->assertFalse($subfolder->setParent($subsubfolder));
        /* Moving $subsubfolder into rool folder is possible */
        $this->assertTrue($subsubfolder->setParent($rootfolder));
        /* Root folder has now two children */
        $children = $rootfolder->getSubFolders();
        $this->assertIsArray($children);
        $this->assertCount(2, $children);
        /* Move the folder will have changed the folder list. Check it */
        $errors = self::$dms->checkFolders();
        $this->assertIsArray($errors);
        $this->assertCount(0, $errors);
        $errors = self::$dms->checkDocuments();
        $this->assertIsArray($errors);
        $this->assertCount(0, $errors);
    }

    /**
     * Test method getPath() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetPathRootOnly()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $path = $folder->getPath();
        $this->assertIsArray($path);
        $this->assertCount(1, $path);
        /* The only folder in the path is the root folder itself */
        $this->assertEquals(1, $path[0]->getId());
    }

    /**
     * Test method getPath() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetPath()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subsubfolder = $subfolder->addSubFolder('Subsubfolder 1', '', $user, 1.0);
        $path = $subsubfolder->getPath();
        $this->assertIsArray($path);
        $this->assertCount(3, $path);
    }

    /**
     * Test method getFolderPathPlain() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetFolderPathPlain()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $path = $folder->getFolderPathPlain();
        $this->assertIsString($path);
        $this->assertEquals('/ DMS', $path);
    }

    /**
     * Test method hasDocuments() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testHasDocuments()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->getUser(1);
        $documents = $folder->hasDocuments();
        $this->assertIsInt($documents);
        $this->assertEquals(0, $documents);
        /* Add a new document for calling hasDocuments() afterwards */
        $document = self::createDocument($folder, $user, 'Document 1');
        $documents = $folder->hasDocuments();
        $this->assertIsInt($documents);
        $this->assertEquals(1, $documents);
    }

    /**
     * Test method hasDocuments()
     *
     * @return void
     */
    public function testHasDokumentsSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT count(*) as c FROM `tblDocuments`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->hasDocuments());
    }

    /**
     * Test method hasDocumentByName() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testHasDocumentByName()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->getUser(1);
        $res = $folder->hasDocumentByName('foo');
        $this->assertFalse($res);
        /* Add a new document for calling hasDocumentByName() afterwards */
        $document = self::createDocument($folder, $user, 'Document 1');
        $res = $folder->hasDocumentByName('Document 1');
        $this->assertTrue($res);
    }

    /**
     * Test method hasDocumentByName()
     *
     * @return void
     */
    public function testHasDokumentByNameSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT count(*) as c FROM `tblDocuments`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->hasDocumentByName('foo'));
    }

    /**
     * Test method getDocuments() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocuments()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->getUser(1);
        $documents = $folder->getDocuments();
        $this->assertIsArray($documents);
        $this->assertCount(0, $documents);
        /* Add a new document for calling getDocuments() afterwards */
        $folder->clearCache();
        $document = self::createDocument($folder, $user, 'Document 1');
        $document = self::createDocument($folder, $user, 'Document 2');
        $documents = $folder->getDocuments();
        $this->assertIsArray($documents);
        $this->assertCount(2, $documents);
        $folder->clearCache();
        /* sort by name asc, limit 1, offset 0 */
        $documents = $folder->getDocuments('n', 'asc', 1);
        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        $this->assertEquals('Document 1', $documents[0]->getName());
        $folder->clearCache();
        /* sort by name desc, limit 1, offset 0 */
        $documents = $folder->getDocuments('n', 'desc', 1);
        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        $this->assertEquals('Document 2', $documents[0]->getName());
        $folder->clearCache();
        /* sort by name asc, limit 1, offset 1 */
        $documents = $folder->getDocuments('n', 'asc', 1, 1);
        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        $this->assertEquals('Document 2', $documents[0]->getName());
    }

    /**
     * Test method getDocuments()
     *
     * @return void
     */
    public function testGetDokumentsSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lock` FROM `tblDocuments`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->getDocuments());
    }

    /**
     * Test method countChildren() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testCountChildren()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->getUser(1);
        $count = $folder->countChildren($user, 0);
        $this->assertIsArray($count);
        $this->assertCount(4, $count);
        $this->assertEquals(0, $count['folder_count']);
        $this->assertEquals(0, $count['document_count']);
        /* Add some folders and documents */
        $this->createSimpleFolderStructure();
        $document = self::createDocument($folder, $user, 'Document 1');
        $count = $folder->countChildren($user, 6);
        $this->assertIsArray($count);
        $this->assertCount(4, $count);
        $this->assertEquals(5, $count['folder_count']);
        $this->assertEquals(1, $count['document_count']);
    }

    /**
     * Test method emptyFolder() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testEmptyFolder()
    {
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->getUser(1);
        /* Add some folders and documents */
        $this->createSimpleFolderStructure();
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $res = $rootfolder->emptyFolder();
        $this->assertTrue($res);
    }

    /**
     * Test method emptyFolder() on root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testEmptyFolderWithCallback()
    {
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->getUser(1);
        /* Add some folders and documents */
        $this->createSimpleFolderStructure();
        $document = self::createDocument($rootfolder, $user, 'Document 1');

        /* Add the 'onPostAddUser' callback */
        $msgs = [];
        $callback = function ($param, $object) use (&$msgs) {
            $msgs[] = $param." ".$object->getName(). " (".$object->getId().")"; 
        };
        self::$dms->addCallback('onPreRemoveFolder', $callback, 'onPreRemoveFolder');
        self::$dms->addCallback('onPostRemoveFolder', $callback, 'onPostRemoveFolder');
        self::$dms->addCallback('onPreRemoveDocument', $callback, 'onPreRemoveDocument');
        self::$dms->addCallback('onPostRemoveDocument', $callback, 'onPostRemoveDocument');
        self::$dms->addCallback('onPreEmptyFolder', $callback, 'onPreEmptyFolder');
        self::$dms->addCallback('onPostEmptyFolder', $callback, 'onPostEmptyFolder');

        $res = $rootfolder->emptyFolder();
        $this->assertTrue($res);
        $this->assertIsArray($msgs);
        $this->assertCount(14, $msgs); // 5 folders x 2 callbacks + 1 document x 2 callbacks + 2 emptyFolder callbacks
    }

    /**
     * Test method getAccessList()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAccessList()
    {
        /* Add some folders and documents */
        $this->createSimpleFolderStructure();
        $this->createGroupsAndUsers();

        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->getUser(1);
        $this->assertIsObject($user);
        $group = self::$dms->getGroup(1);
        $this->assertIsObject($group);
        $subfolder = self::$dms->getFolderByName('Subfolder 1');
        $this->assertIsObject($subfolder);
        $subsubfolder = self::$dms->getFolderByName('Subsubfolder 1');
        $this->assertIsObject($subsubfolder);

        /* Adding an access rule will have no effect until the inheritance
         * is turned off.
         */
        $subfolder->addAccess(M_NONE, $user->getId(), true);
        $subfolder->addAccess(M_READWRITE, $group->getId(), false);
        $accesslist = $subfolder->getAccessList();
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['users']);
        $this->assertCount(0, $accesslist['groups']);
        /* Turn inheritance off */
        $res = $subfolder->setInheritAccess(false);
        $this->assertTrue($res);
        /* Now the access rules on $subfolder take effect */
        $accesslist = $subfolder->getAccessList();
        $this->assertIsArray($accesslist);
        $this->assertCount(1, $accesslist['users']);
        $this->assertCount(1, $accesslist['groups']);
        /* get list of users/groups which no access */
        $accesslist = $subfolder->getAccessList(M_NONE, O_EQ);
        $this->assertIsArray($accesslist);
        $this->assertCount(1, $accesslist['users']);
        $this->assertCount(0, $accesslist['groups']);
        /* get list of users/groups which read+write access */
        $accesslist = $subfolder->getAccessList(M_READWRITE, O_EQ);
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['users']);
        $this->assertCount(1, $accesslist['groups']);
        /* get list of users/groups which have at least read access */
        $accesslist = $subfolder->getAccessList(M_READ, O_GTEQ);
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['users']);
        $this->assertCount(1, $accesslist['groups']);
        /* get list of users/groups which have at least unlimited access */
        $accesslist = $subfolder->getAccessList(M_ALL, O_GTEQ);
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['users']);
        $this->assertCount(0, $accesslist['groups']);
        /* Subsubfolder 1 inherits from Subfolder 1 */
        $accesslist = $subsubfolder->getAccessList();
        $this->assertIsArray($accesslist);
        $this->assertCount(1, $accesslist['users']);
        $this->assertCount(1, $accesslist['groups']);
        /* clear the access list */
        $res = $subfolder->clearAccessList();
        $this->assertTrue($res);
        $accesslist = $subfolder->getAccessList();
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['users']);
        $this->assertCount(0, $accesslist['groups']);
        /* calling getAccessList() on the $subsubfolder will still return
         * the user and group, because the getParent() call in getAccessList()
         * will not return the same instance like $subfolder. Hence calling
         * $subfolder->clearAccessList() won't clear the accesslist of $subsubfolder's
         * parent. You would have to explicitly
         * clear acceslist of $subsubfolder's parent.
        $res = $subsubfolder->getParent()->clearAccessList();
        $this->assertTrue($res);
        $accesslist = $subsubfolder->getAccessList();
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['users']);
        $this->assertCount(0, $accesslist['groups']);
         */
    }

    /**
     * Test method addAccess()
     *
     * @return void
     */
    public function testAddAccessWrongMode()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->addAccess(M_ANY, 1, true));
    }

    /**
     * Test method addAccess()
     *
     * @return void
     */
    public function testAddAccessSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("INSERT INTO `tblACLs`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->addAccess(M_NONE, 1, true));
    }

    /**
     * Test method getAccessMode()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAccessMode()
    {
        /* Add some folders and documents */
        $this->createSimpleFolderStructureWithDocuments();
        $this->createGroupsAndUsers();

        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $admin = self::$dms->getUser(1);
        $this->assertIsObject($admin);
        $this->assertTrue($admin->isAdmin());
        $guest = self::$dms->getUser(2);
        $this->assertTrue($guest->isGuest());
        $user = self::$dms->getUser(3);
        $this->assertIsObject($user);
        if(self::$dms->version[0] == '5')
            $this->assertTrue($user->getRole() == SeedDMS_Core_User::role_user);
        else
            $this->assertTrue($user->getRole()->getRole() == SeedDMS_Core_Role::role_user);
        $joe = self::$dms->getUser(4);
        $this->assertIsObject($joe);
        if(self::$dms->version[0] == '5')
            $this->assertTrue($joe->getRole() == SeedDMS_Core_User::role_user);
        else
            $this->assertTrue($joe->getRole()->getRole() == SeedDMS_Core_Role::role_user);
        $sally = self::$dms->getUser(6);
        $this->assertIsObject($sally);
        if(self::$dms->version[0] == '5')
            $this->assertTrue($sally->getRole() == SeedDMS_Core_User::role_user);
        else
            $this->assertTrue($sally->getRole()->getRole() == SeedDMS_Core_Role::role_user);
        $group = self::$dms->getGroup(1);
        $this->assertIsObject($group);
        /* add guest and joe to group */
        if(!$group->isMember($guest)) {
            $res = $guest->joinGroup($group);
            $this->assertTrue($res);
        }
        if(!$group->isMember($joe)) {
            $res = $joe->joinGroup($group);
            $this->assertTrue($res);
        }

        $subfolder1 = self::$dms->getFolderByName('Subfolder 1');
        $this->assertIsObject($subfolder1);
        $subsubfolder = self::$dms->getFolderByName('Subsubfolder 1');
        $this->assertIsObject($subsubfolder);
        $subfolder2 = self::$dms->getFolderByName('Subfolder 2');
        $this->assertIsObject($subfolder2);
        $subfolder3 = self::$dms->getFolderByName('Subfolder 3');
        $this->assertIsObject($subfolder3);
        $res = $subfolder3->setOwner($sally);
        $this->assertTrue($res);

        /* Setup Subfolder 1:
         * no inheritance, user has read-write access, group has unlimited access,
         * default is no access
         */
        $res = $subfolder1->setInheritAccess(false);
        $this->assertTrue($res);
        $res = $subfolder1->setDefaultAccess(M_NONE);
        $this->assertTrue($res);
        $res = $subfolder1->addAccess(M_READWRITE, $user->getId(), true);
        $this->assertTrue($res);
        $res = $subfolder1->addAccess(M_ALL, $group->getId(), false);
        $this->assertTrue($res);

        /* Admin has always access mode M_ALL */
        $mode = $subfolder1->getAccessMode($admin);
        $this->assertEquals(M_ALL, $mode);
        /* Guest has max read access, though it's group has any access */
        $mode = $subfolder1->getAccessMode($guest);
        $this->assertEquals(M_READ, $mode);
        /* Joe has any access, because it's group has any access */
        $mode = $subfolder1->getAccessMode($joe);
        $this->assertEquals(M_ALL, $mode);
        /* Sally has no access, because it has no explicit access right and the
         * default access is M_NONE.
         */
        $mode = $subfolder1->getAccessMode($sally);
        $this->assertEquals(M_NONE, $mode);

        /* Subfolder 3 inherits from the root folder, but sally is the owner */
        $mode = $subfolder3->getAccessMode($sally);
        $this->assertEquals(M_ALL, $mode);
        /* joe has just read access which is the default inherited from root */
        $mode = $subfolder3->getAccessMode($joe);
        $this->assertEquals(M_READ, $mode);

    }

    /**
     * Test method getFolderList()
     *
     * @return void
     */
    public function testGetFolderListSqlFail()
    {
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT `folderList` FROM `tblFolders`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $rootfolder->setDMS($dms);
        $this->assertFalse($rootfolder->getFolderList());
    }

}
