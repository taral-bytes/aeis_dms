<?php
/**
 * Implementation of the document tests
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
class DocumentTest extends SeedDmsTest
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
    public function testGetInstance()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        /* Get the document with id 1, which must be 'Document 1' */
        $document = SeedDMS_Core_Document::getInstance(1, self::$dms);
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));
        $this->assertEquals('Document 1', $document->getName());
        /* Get a none existing document */
        $document = SeedDMS_Core_Document::getInstance(2, self::$dms);
        $this->assertNull($document);
    }

    /**
     * Test method getInstance() within a root folder
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetInstanceWithinRoot()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* The simple folder structure will create to subfolders, each
         * with 15 documents.
         */
        self::createSimpleFolderStructureWithDocuments();
        $subfolder = self::$dms->getFolderByName('Subfolder 1');
        /* Get a document in Subfolder 1 */
        $document1 = self::$dms->getDocumentByName('Document 1-1');
        /* Get a document in Subfolder 2 */
        $document2 = self::$dms->getDocumentByName('Document 2-1');

        /* Getting a document in subfolder 1 without any restrictions must succeed */
        $document = SeedDMS_Core_Document::getInstance($document1->getId(), self::$dms);
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));

        /* Make Subfolder 1 the root folder */
        self::$dms->checkWithinRootDir = true;
        self::$dms->setRootFolderID($subfolder->getId());

        /* Getting a document by id in subfolder 1 still must succeed */
        $document = SeedDMS_Core_Document::getInstance($document1->getId(), self::$dms);
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));

        /* Getting a document by id in subfolder 2 must fail */
        $document = SeedDMS_Core_Document::getInstance($document2->getId(), self::$dms);
        $this->assertNull($document);

        /* Get a document in Subfolder 1 */
        $document = self::$dms->getDocumentByName('Document 1-1');
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));

        /* Get a document in Subfolder 2 */
        $document = self::$dms->getDocumentByName('Document 2-1');
        $this->assertNull($document);
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
            ->with($this->stringContains("SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lock` FROM `tblDocuments`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse(SeedDMS_Core_Document::getInstance(1, $dms));
    }

    /**
     * Test method getDir()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDir()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $this->assertIsObject($document);
        $this->assertEquals('1/', $document->getDir());
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
        $user = self::$dms->getUser(1);
        $document = self::createDocument($folder, $user, 'Document 1');
        $this->assertIsObject($document);
        $comment = $document->getComment();
        $this->assertEquals('', $comment);
        $ret = $document->setComment('foo');
        $this->assertTrue($ret);
        $comment = $document->getComment();
        $this->assertEquals('foo', $comment);
    }

    /**
     * Test method setComment() mit sql fail
     *
     * @return void
     */
    public function testSetCommentSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `comment`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setComment('my comment'));
    }

    /**
     * Test method getKeywords() and setKeywords()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetKeywords()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->getUser(1);
        $document = self::createDocument($folder, $user, 'Document 1');
        $this->assertIsObject($document);
        $keywords = $document->getKeywords();
        $this->assertEquals('', $keywords);
        $ret = $document->setKeywords('foo bar');
        $this->assertTrue($ret);
        $keywords = $document->getKeywords();
        $this->assertEquals('foo bar', $keywords);
    }

    /**
     * Test method setKeywords() mit sql fail
     *
     * @return void
     */
    public function testSetKeywordsSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `keywords`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setKeywords('keywords'));
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
        $user = self::$dms->getUser(1);
        $document = self::createDocument($folder, $user, 'Document 1');
        $this->assertIsObject($document);
        $name = $document->getName();
        $this->assertEquals('Document 1', $name);
        $ret = $document->setName('foo');
        $this->assertTrue($ret);
        $name = $document->getName();
        $this->assertEquals('foo', $name);
    }

    /**
     * Test method setName() mit sql fail
     *
     * @return void
     */
    public function testSetNameSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `name`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setName('my name'));
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
        $user = self::$dms->getUser(1);
        $document = self::createDocument($folder, $user, 'Document 1');
        $now = time();
        /* Passing false as a time stamp will take current time stamp */
        $ret = $document->setDate(false);
        $this->assertTrue($ret);
        $date = $document->getDate();
        $this->assertEquals($now, $date);
        /* Setting a time stamp */
        $now -= 1000;
        $ret = $document->setDate($now);
        $this->assertTrue($ret);
        $date = $document->getDate();
        $this->assertEquals($now, $date);
        /* Setting a none numeric value will fail */
        $ret = $document->setDate('foo');
        $this->assertFalse($ret);
    }

    /**
     * Test method setDate() with sql fail
     *
     * @return void
     */
    public function testSetDateSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `date` = "))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setDate(null));
    }

    /**
     * Test method getDefaultAccess() and setDefaultAccess()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetDefaultAccess()
    {
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->getUser(1);
        $document = self::createDocument($folder, $user, 'Document 1');
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));
        $defaultaccess = $document->getDefaultAccess();
        $this->assertEquals(M_READ, $defaultaccess);

        /* Setting a default access out of range yields an error */
        $ret = $document->setDefaultAccess(0, true);
        $this->assertFalse($ret);

        /* Setting a default access out of range yields an error */
        $ret = $document->setDefaultAccess(M_ALL+1, true);
        $this->assertFalse($ret);

        /* Setting the default access will have no effect as long as access
         * rights are inherited. */
        $ret = $document->setDefaultAccess(M_READWRITE, true);
        $this->assertTrue($ret);
        $defaultaccess = $document->getDefaultAccess();
        $this->assertEquals(M_READ, $defaultaccess);

        /* Once inheritance of access rights is turned off, the previously
         * set default access right will take effect. */
        $ret = $document->setInheritAccess(false, true);
        $this->assertTrue($ret);
        $defaultaccess = $document->getDefaultAccess();
        $this->assertEquals(M_READWRITE, $defaultaccess);

        /* Also check if inherited access was turned off */
        $ret = $document->getInheritAccess();
        $this->assertFalse($ret);
    }

    /**
     * Test method setDefaultAccess() mit sql fail
     *
     * @return void
     */
    public function testSetDefaultAccessSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `defaultAccess`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setDefaultAccess(M_READ));
    }

    /**
     * Test method setInheritAccess() mit sql fail
     *
     * @return void
     */
    public function testSetInheritAccessSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `inheritAccess`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setInheritAccess(0));
    }

    /**
     * Test method addAccess(), removeAccess(), changeAccess(), getAccessList()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetAccess()
    {
        self::createGroupsAndUsers();
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $adminuser = self::$dms->getUser(1);
        $guestuser = self::$dms->getUser(2);
        $user = self::$dms->getUserByLogin('user-1-1');
        $this->assertIsObject($user);
        $this->assertTrue($user->isType('user'));
        $group = self::$dms->getGroupByName('Group 1');
        $this->assertIsObject($group);
        $this->assertTrue($group->isType('group'));
        $document = self::createDocument($folder, $adminuser, 'Document 1');
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));
        $defaultaccess = $document->getDefaultAccess();
        $this->assertEquals(M_READ, $defaultaccess);

        /* Turn off inheritance, otherwise the access rights have no effect */
        $ret = $document->setInheritAccess(false, true);
        $this->assertTrue($ret);

        /* Retrieving an access mode without a valid user will always return M_NONE */
        $mode = $document->getAccessMode(null);
        $this->assertEquals(M_NONE, $mode);

        /* The admin user has always unlimited access */
        $mode = $document->getAccessMode($adminuser);
        $this->assertEquals(M_ALL, $mode);

        /* Without setting any specific access, the document has a default mode M_READ */
        $mode = $document->getAccessMode($user);
        $this->assertEquals(M_READ, $mode);

        /* Access mode for group is also the default access */
        $mode = $document->getGroupAccessMode($group);
        $this->assertEquals(M_READ, $mode);

        /* Set unlimited access rights for everybody */
        $ret = $document->setDefaultAccess(M_ALL);
        $this->assertTrue($ret);
        $mode = $document->getAccessMode($user);
        $this->assertEquals(M_ALL, $mode);
        $mode = $document->getGroupAccessMode($group);
        $this->assertEquals(M_ALL, $mode);

        /* Guest still have just read access */
        $mode = $document->getAccessMode($guestuser);
        $this->assertEquals(M_READ, $mode);

        /* Add wrong access type returns false */
        $ret = $document->addAccess(M_ALL+1, $user->getId(), true);
        $this->assertFalse($ret);

        /* Add read/write access on the document for user */
        $ret = $document->addAccess(M_READWRITE, $user->getId(), true);
        $this->assertTrue($ret);
        /* Adding another access right (not matter which one) for the
         * same user yields an error
         */
        $ret = $document->addAccess(M_READ, $user->getId(), true);
        $this->assertFalse($ret);

        /* Passing an invalid second parameter will return false */
        $accesslist = $document->getAccessList(M_ANY, 5);
        $this->assertFalse($accesslist);

        /* Searching for mode == M_READ will return neither a group nor
         * the user, because the user has read&write access
         */
        $accesslist = $document->getAccessList(M_READ);
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['groups']);
        $this->assertCount(0, $accesslist['users']);

        $accesslist = $document->getAccessList(M_READWRITE);
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['groups']);
        $this->assertCount(1, $accesslist['users']);

        $accesslist = $document->getAccessList();
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['groups']);
        $this->assertCount(1, $accesslist['users']);

        /* Access mode is just read/write for the user thought the default is unlimited */
        $mode = $document->getAccessMode($user);
        $this->assertEquals(M_READWRITE, $mode);
        /* Access mode for the group is still unlimited */
        $mode = $document->getGroupAccessMode($group);
        $this->assertEquals(M_ALL, $mode);

        /* Setting default access to M_READ
         * is just a precaution to ensure the unlimeted access rights is not
         * derived from the default access which was set to M_ALL above.
         */
        $ret = $document->setDefaultAccess(M_READ);
        $this->assertTrue($ret);
        $mode = $document->getGroupAccessMode($group);
        $this->assertEquals(M_READ, $mode);

        /* Add unlimeted access on the document for group */
        $ret = $document->addAccess(M_ALL, $group->getId(), false);
        $this->assertTrue($ret);
        /* Adding another access right (not matter which one) for the
         * same group yields an error
         */
        $ret = $document->addAccess(M_READ, $group->getId(), false);
        $this->assertFalse($ret);

        $accesslist = $document->getAccessList();
        $this->assertIsArray($accesslist);
        $this->assertCount(1, $accesslist['groups']);
        $this->assertCount(1, $accesslist['users']);

        /* The group has now unlimited access rights */
        $mode = $document->getGroupAccessMode($group);
        $this->assertEquals(M_ALL, $mode);

        /* The user still has just read/write access, though the group he belongs
         * to has unlimeted rights. The specific user rights has higher priority.
         */
        $mode = $document->getAccessMode($user);
        $this->assertEquals(M_READWRITE, $mode);

        /* Remove all specific access rights for the user */
        $ret = $document->removeAccess($user->getId(), true);
        $this->assertTrue($ret);

        /* Now the group rights apply for the user, because there are no
         * specific user rights anymore.
         */
        $mode = $document->getAccessMode($user);
        $this->assertEquals(M_ALL, $mode);

        /* change unlimeted access on the document for group to none */
        $ret = $document->changeAccess(M_NONE, $group->getId(), false);
        $this->assertTrue($ret);
        $mode = $document->getAccessMode($user);
        $this->assertEquals(M_NONE, $mode);

        /* clear all access rights */
        $ret = $document->clearAccessList();
        $this->assertTrue($ret);
        $accesslist = $document->getAccessList();
        $this->assertIsArray($accesslist);
        $this->assertCount(0, $accesslist['groups']);
        $this->assertCount(0, $accesslist['users']);

        /* We are back to the default access rights */
        $mode = $document->getAccessMode($user);
        $this->assertEquals(M_READ, $mode);

    }

    /**
     * Test method addNotify(), removeNotify(), getNotifyList(), cleanNotifyList()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetNotify()
    {
        self::createGroupsAndUsers();
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $adminuser = self::$dms->getUser(1);
        $guestuser = self::$dms->getUser(2);
        $user = self::$dms->getUserByLogin('user-1-1');
        $this->assertIsObject($user);
        $this->assertTrue($user->isType('user'));
        $group = self::$dms->getGroupByName('Group 1');
        $this->assertIsObject($group);
        $this->assertTrue($group->isType('group'));
        $document = self::createDocument($folder, $adminuser, 'Document 1');
        $this->assertIsObject($document);
        $this->assertTrue($document->isType('document'));

        $notifylist = $document->getNotifyList();
        $this->assertIsArray($notifylist);
        $this->assertCount(0, $notifylist['groups']);
        $this->assertCount(0, $notifylist['users']);

        /* Add notify on the document for user */
        $ret = $document->addNotify($user->getId(), true);
        $this->assertEquals(0, $ret);

        /* Add notify on the document for group */
        $ret = $document->addNotify($group->getId(), false);
        $this->assertEquals(0, $ret);

        /* Add notify on the document for a user that does not exists */
        $ret = $document->addNotify(15, true);
        $this->assertEquals(-1, $ret);

        $notifylist = $document->getNotifyList();
        $this->assertIsArray($notifylist);
        $this->assertCount(1, $notifylist['groups']);
        $this->assertCount(1, $notifylist['users']);

        /* Setting the default access to M_NONE and turning off inheritance
         * will clean the notification list, because the notifiers have no
         * longer read access on the document and therefore will be removed
         * from the notification list.
         */
        $ret = $document->setInheritAccess(false);
        $this->assertTrue($ret);
        $ret = $document->setDefaultAccess(M_NONE);
        $this->assertTrue($ret);

        $notifylist = $document->getNotifyList();
        $this->assertIsArray($notifylist);
        $this->assertCount(0, $notifylist['groups']);
        $this->assertCount(0, $notifylist['users']);
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
        $subfolder1 = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subfolder2 = $rootfolder->addSubFolder('Subfolder 2', '', $user, 1.0);
        $document = self::createDocument($subfolder1, $user, 'Document 1');
        /* document is a descendant of root folder and subfolder 1 */
        $this->assertTrue($document->isDescendant($rootfolder));
        $this->assertTrue($document->isDescendant($subfolder1));
        /* subfolder is not a descendant of subfolder 2 */
        $this->assertFalse($document->isDescendant($subfolder2));
    }

    /**
     * Test method getParent()
     *
     * Create a new document below root folder and check if parent
     * of the document is the root folder.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetParent()
    {
        $user = self::$dms->getUser(1);
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $parent = $document->getParent();
        $this->assertIsObject($parent);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $parent);
        $this->assertEquals(1, $parent->getId());
    }

    /**
     * Test method setParent()
     *
     * Create a new document below root folder, move it to a subfolder
     * and check if parent of the document is the sub folder.
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetParent()
    {
        $user = self::$dms->getUser(1);
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 0);
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        /* Setting a null folder is not allowed */
        $ret = $document->setParent(null);
        $this->assertFalse($ret);

        /* Passed object must be a folder  */
        $ret = $document->setParent($user);
        $this->assertFalse($ret);

        $ret = $document->setParent($subfolder);
        $this->assertTrue($ret);
        $parent = $document->getParent();
        $this->assertIsObject($parent);
        $this->assertInstanceOf(SeedDMS_Core_Folder::class, $parent);
        $this->assertEquals(2, $parent->getId());
    }

    /**
     * Test method setParent() mit sql fail
     *
     * @return void
     */
    public function testSetParentSqlFail()
    {
        $document = $this->getMockedDocument();
        $rootfolder = $this->getMockedRootFolder();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `folder`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setParent($rootfolder));
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
        $this->assertIsObject($user);
        $document = self::createDocument($rootfolder, $adminuser, 'Document 1');
        /* Setting a null user is not allowed */
        $ret = $document->setOwner(null);
        $this->assertFalse($ret);

        /* Passed object must be a folder  */
        $ret = $document->setOwner($rootfolder);
        $this->assertFalse($ret);

        $res = $document->setOwner($user);
        $this->assertTrue($res);
        $owner = $document->getOwner();
        $this->assertIsObject($owner);
        $this->assertInstanceOf(SeedDMS_Core_User::class, $owner);
        $this->assertEquals($user->getId(), $owner->getId());
    }

    /**
     * Test method setOwner() mit sql fail
     *
     * @return void
     */
    public function testSetOwnerSqlFail()
    {
        $document = $this->getMockedDocument();
        $user = $this->getMockedUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `owner`"))
            ->willReturn(false);
        // SeedDMS 6 will fetch the old owner in setOwner() before setting the
        // new owner
        if(self::$dbversion['major'] == 6) {
            $db->expects($this->once())
                ->method('getResultArray')
                ->with($this->stringContains("SELECT * FROM `tblUsers` WHERE `id` = "))
                ->willReturn([]);
        }
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setOwner($user));
    }

    /**
     * Test method expires(), setExpires(), getExpires()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetExpires()
    {
        $adminuser = self::$dms->getUser(1);
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $document = self::createDocument($rootfolder, $adminuser, 'Document 1');
        $expires = $document->expires();
        $this->assertFalse($expires);
        $expires = $document->getExpires();
        $this->assertFalse($expires);
        $now = time();
        $res = $document->setExpires($now);
        $this->assertTrue($res);
        /* Setting it again will return true */
        $res = $document->setExpires($now);
        $this->assertTrue($res);
        $expires = $document->expires();
        $this->assertTrue($res);
        $expirets = $document->getExpires();
        $this->assertEquals($now, $expirets);
    }

    /**
     * Test method setExpires() mit sql fail
     *
     * @return void
     */
    public function testSetExpiresSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `expires`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setExpires(time()));
    }

    /**
     * Test method setLocked(), isLocked()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetAndIsLocked()
    {
        $adminuser = self::$dms->getUser(1);
        $rootfolder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->addUser('user1', 'user1', 'User One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($user);
        $document = self::createDocument($rootfolder, $adminuser, 'Document 1');
        $res = $document->isLocked();
        $this->assertFalse($res);
        $res = $document->setLocked($user);
        $this->assertTrue($res);
        $res = $document->isLocked();
        $this->assertTrue($res);
        $lockuser = $document->getLockingUser();
        $this->assertIsObject($lockuser);
        $this->assertInstanceOf(SeedDMS_Core_User::class, $lockuser);
        $this->assertEquals($user->getId(), $lockuser->getId());
        /* parameter passed to setLocked must be false or a user */
        $res = $document->setLocked(null);
        /* document is still locked and locking user is unchanged */
        $res = $document->isLocked();
        $this->assertTrue($res);
        $lockuser = $document->getLockingUser();
        $this->assertIsObject($lockuser);
        $this->assertInstanceOf(SeedDMS_Core_User::class, $lockuser);
        $this->assertEquals($user->getId(), $lockuser->getId());
        /* Unlock the document */
        $res = $document->setLocked(false);
        $this->assertTrue($res);
        $res = $document->isLocked();
        $this->assertFalse($res);
        $lockuser = $document->getLockingUser();
        $this->assertFalse($lockuser);
    }

    /**
     * Test method setLocked() with sql fail
     *
     * @return void
     */
    public function testSetLockedSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("DELETE FROM `tblDocumentLocks`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setLocked(false));
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
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $document = self::createDocument($folder, $adminuser, 'Document 1');
        /* The document still has sequence = 1.0 */
        $sequence = $document->getSequence();
        $this->assertEquals(1.0, $sequence);
        $ret = $document->setSequence(1.5);
        $this->assertTrue($ret);
        $sequence = $document->getSequence();
        $this->assertEquals(1.5, $sequence);
    }

    /**
     * Test method setSequence() mit sql fail
     *
     * @return void
     */
    public function testSetSequenceSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblDocuments` SET `sequence`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->setSequence(1.1));
    }

    /**
     * Test method getContentByVersion(), isLatestContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetContentByVersion()
    {
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $document = self::createDocument($folder, $adminuser, 'Document 1');
        /* Get version 1 */
        $content = $document->getContentByVersion(1);
        $this->assertIsObject($content);
        $this->assertInstanceOf(SeedDMS_Core_DocumentContent::class, $content);
        /* There is no version 2 */
        $content = $document->getContentByVersion(2);
        $this->assertNull($content);
        /* version must be numeric */
        $content = $document->getContentByVersion('foo');
        $this->assertFalse($content);
        /* Check if 1 is the latest version number */
        $ret = $document->isLatestContent(1);
        $this->assertTrue($ret);
        $ret = $document->isLatestContent(2);
        $this->assertFalse($ret);
    }

    /**
     * Test method getDocumentContent()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentContent()
    {
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $document = self::createDocument($folder, $adminuser, 'Document 1');
        /* Get version 1 */
        $content = $document->getContentByVersion(1);
        $this->assertIsObject($content);
        $this->assertInstanceOf(SeedDMS_Core_DocumentContent::class, $content);
        $again = self::$dms->getDocumentContent($content->getId());
        $this->assertIsObject($again);
        $this->assertInstanceOf(SeedDMS_Core_DocumentContent::class, $again);
        $this->assertEquals($content->getId(), $again->getId());
        $none = self::$dms->getDocumentContent(2);
        $this->assertNull($none);
    }

    /**
     * Test method getDocumentContent() with sql failure
     *
     * @return void
     */
    public function testGetDocumentContentSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblDocumentContent` WHERE `id`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $this->assertFalse($dms->getDocumentContent(1));
    }

    /**
     * Test method addDocumentLink(), getDocumentLinks(), getReverseDocumentLinks()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddAndGetDocumentLinks()
    {
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->addUser('user1', 'user1', 'User One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($user);
        $document1 = self::createDocument($folder, $adminuser, 'Document 1');
        $document2 = self::createDocument($folder, $adminuser, 'Document 2');

        /* document 1 has no links */
        $links = $document1->getDocumentLinks();
        $this->assertIsArray($links);
        $this->assertCount(0, $links);
        $links = $document1->getReverseDocumentLinks();
        $this->assertIsArray($links);
        $this->assertCount(0, $links);

        /* Adding a link to none existing target or by a none existing user fails */
        $ret = $document1->addDocumentLink(3, $user->getId(), false);
        $this->assertFalse($ret);
        $ret = $document1->addDocumentLink($document2->getId(), 4, false);
        $this->assertFalse($ret);

        /* Adding a link with a bogus target or user must fail */
        $ret = $document1->addDocumentLink('foo', 1, false);
        $this->assertFalse($ret);
        $ret = $document1->addDocumentLink(3, 'foo', false);
        $this->assertFalse($ret);

        /* Adding a link to myself must fail */
        $ret = $document1->addDocumentLink($document1->getId(), $user->getId(), false);
        $this->assertFalse($ret);

        /* Add a non public link to document 2 by user */
        $link = $document1->addDocumentLink($document2->getId(), $user->getId(), false);
        $this->assertIsObject($link);
        $this->assertInstanceOf(SeedDMS_Core_DocumentLink::class, $link);
        $links = $document1->getDocumentLinks();
        $this->assertIsArray($links);
        $this->assertCount(1, $links);
        $links = $document2->getReverseDocumentLinks();
        $this->assertIsArray($links);
        $this->assertCount(1, $links);
        /* There is one reverse link of a user */
        $links = $document2->getReverseDocumentLinks(false, $user);
        $this->assertIsArray($links);
        $this->assertCount(1, $links);
        /* There are no public reverse links */
        $links = $document2->getReverseDocumentLinks(true);
        $this->assertIsArray($links);
        $this->assertCount(0, $links);

        /* There are no public links of document 1 */
        $document1->clearCache();
        $links = $document1->getDocumentLinks(true);
        $this->assertIsArray($links);
        $this->assertCount(0, $links);

        /* There are no links by adminuser of document 1 */
        $document1->clearCache();
        $links = $document1->getDocumentLinks(false, $adminuser);
        $this->assertIsArray($links);
        $this->assertCount(0, $links);

        /* There are links by user of document 1 */
        $document1->clearCache();
        $links = $document1->getDocumentLinks(false, $user);
        $this->assertIsArray($links);
        $this->assertCount(1, $links);

        $link = $document1->getDocumentLink($links[0]->getId());
        $this->assertIsObject($link);
        $this->assertTrue($link->isType('documentlink'));
    }

    /**
     * Test method addDocumentLink(), removeDocumentLinks()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddAndRemoveDocumentLink()
    {
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->addUser('user1', 'user1', 'User One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($user);
        $document1 = self::createDocument($folder, $adminuser, 'Document 1');
        $document2 = self::createDocument($folder, $adminuser, 'Document 2');

        /* Add a non public link to document 2 by user */
        $link = $document1->addDocumentLink($document2->getId(), $user->getId(), false);
        $this->assertIsObject($link);
        $this->assertInstanceOf(SeedDMS_Core_DocumentLink::class, $link);
        $links = $document1->getDocumentLinks();
        $this->assertIsArray($links);
        $this->assertCount(1, $links);

        /* Remove the link again */
        $link = $links[0];
        $ret = $document1->removeDocumentLink($link->getId());
        $this->assertTrue($ret);
        $links = $document1->getDocumentLinks();
        $this->assertIsArray($links);
        $this->assertCount(0, $links);
    }

    /**
     * Test method addDocumentFile(), getDocumentFiles()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddAndGetDocumentFiles()
    {
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->addUser('user1', 'user1', 'User One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($user);
        $document = self::createDocument($folder, $adminuser, 'Document 1');

        /* document has no files */
        $files = $document->getDocumentFiles();
        $this->assertIsArray($files);
        $this->assertCount(0, $files);

        $filename = self::createTempFile(100);
        $file1 = $document->addDocumentFile('Attachment 1', '', $user, $filename, 'attachment1.txt', '.txt', 'plain/text');
        unlink($filename);
        $this->assertIsObject($file1);
        $this->assertInstanceOf(SeedDMS_Core_DocumentFile::class, $file1);

        $filename = self::createTempFile(100);
        $file2 = $document->addDocumentFile('Attachment 2', '', $user, $filename, 'attachment2.txt', '.txt', 'plain/text', 1);
        unlink($filename);
        $this->assertIsObject($file2);
        $this->assertInstanceOf(SeedDMS_Core_DocumentFile::class, $file2);

        /* Get all attachments */
        $files = $document->getDocumentFiles();
        $this->assertIsArray($files);
        $this->assertCount(2, $files);

        /* Get attachments for version 1 only */
        $files = $document->getDocumentFiles(1, false);
        $this->assertIsArray($files);
        $this->assertCount(1, $files);

        /* Get attachments for version 1 and version independed */
        $files = $document->getDocumentFiles(1, true);
        $this->assertIsArray($files);
        $this->assertCount(2, $files);
    }

    /**
     * Test method addDocumentFile(), removeDocumentFile()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddAndRemoveDocumentFiles()
    {
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->addUser('user1', 'user1', 'User One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($user);
        $document = self::createDocument($folder, $adminuser, 'Document 1');

        /* document has no files */
        $files = $document->getDocumentFiles();
        $this->assertIsArray($files);
        $this->assertCount(0, $files);

        $filename = self::createTempFile(100);
        $file1 = $document->addDocumentFile('Attachment 1', '', $user, $filename, 'attachment1.txt', '.txt', 'plain/text');
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        $this->assertIsObject($file1);
        $this->assertInstanceOf(SeedDMS_Core_DocumentFile::class, $file1);

        /* document has now 1 file */
        $files = $document->getDocumentFiles();
        $this->assertIsArray($files);
        $this->assertCount(1, $files);

        /* Removing a file with a none exiting or bogus id must fail */
        $ret = $document->removeDocumentFile(2);
        $this->assertFalse($ret);
        $ret = $document->removeDocumentFile('foo');
        $this->assertFalse($ret);

        $ret = $document->removeDocumentFile($files[0]->getId());
        $this->assertTrue($ret);

        $files = $document->getDocumentFiles();
        $this->assertIsArray($files);
        $this->assertCount(0, $files);
    }

    /**
     * Test method addDocument(), removeDocument()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddAndRemoveDocument()
    {
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        $user = self::$dms->addUser('user1', 'user1', 'User One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($user);
        $document = self::createDocument($folder, $adminuser, 'Document 1');
        $docid = $document->getId();

        $filename = self::createTempFile(100);
        $file1 = $document->addDocumentFile('Attachment 1', '', $user, $filename, 'attachment1.txt', '.txt', 'plain/text');
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        $this->assertIsObject($file1);
        $this->assertInstanceOf(SeedDMS_Core_DocumentFile::class, $file1);

        $ret = $document->remove();
        $this->assertTrue($ret);
        $document = self::$dms->getDocument($docid);
        $this->assertNull($document);
    }

    /**
     * Test method getUsedDiskSpace()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetUsedDiskSpace()
    {
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        /* Create a document with 1234 Bytes */
        $document = self::createDocument($folder, $adminuser, 'Document 1', 1234);
        $size = $document->getUsedDiskSpace();
        $this->assertEquals(1234, $size);
    }

    /**
     * Test method getTimeline()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetTimeline()
    {
        $adminuser = self::$dms->getUser(1);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        /* Create a document */
        $document = self::createDocument($folder, $adminuser, 'Document 1');
        /* Attach a file */
        $filename = self::createTempFile(100);
        $file1 = $document->addDocumentFile('Attachment 1', '', $adminuser, $filename, 'attachment1.txt', '.txt', 'plain/text');
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        $this->assertIsObject($file1);
        $this->assertInstanceOf(SeedDMS_Core_DocumentFile::class, $file1);

        /* Get the timeline. It must contain two entries
         * - the initial release of the document
         * - adding the attachment
         */
        $timeline = $document->getTimeLine();
        $this->assertIsArray($timeline);
        $this->assertCount(2, $timeline);
    }

    /**
     * Test method transferToUser()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testTransferToUser()
    {
        $adminuser = self::$dms->getUser(1);
        $user = self::$dms->addUser('user1', 'user1', 'User One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($user);
        $folder = SeedDMS_Core_Folder::getInstance(1, self::$dms);
        /* Create two documents */
        $document1 = self::createDocument($folder, $adminuser, 'Document 1');
        $document2 = self::createDocument($folder, $adminuser, 'Document 2');

        /* Attach a file */
        $filename = self::createTempFile(100);
        $file1 = $document1->addDocumentFile('Attachment 1', '', $adminuser, $filename, 'attachment1.txt', '.txt', 'plain/text');
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
        $this->assertIsObject($file1);
        $this->assertInstanceOf(SeedDMS_Core_DocumentFile::class, $file1);

        /* Add a non public link to document 2 */
        $link = $document1->addDocumentLink($document2->getId(), $adminuser->getId(), false);
        $this->assertIsObject($link);
        $this->assertInstanceOf(SeedDMS_Core_DocumentLink::class, $link);

        /* Transfer document to $user */
        $this->assertEquals('admin', $document1->getOwner()->getLogin());
        $links = $document1->getDocumentLinks(false, $adminuser);
        $this->assertIsArray($links);
        $this->assertCount(1, $links);

        $ret = $document1->transferToUser($user);
        $this->assertTrue($ret);
        $this->assertEquals('user1', $document1->getOwner()->getLogin());
        $links = $document1->getDocumentLinks(false, $user);
        $this->assertIsArray($links);
        $this->assertCount(1, $links);
        $files = $document1->getDocumentFiles();
        $this->assertIsArray($files);
        $this->assertCount(1, $files);
        $this->assertEquals($files[0]->getUserID(), $user->getId());
    }
}
