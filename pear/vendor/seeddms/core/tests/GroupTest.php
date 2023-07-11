<?php
/**
 * Implementation of the group tests
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
class GroupTest extends SeedDmsTest
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
     * Create a mock group object
     *
     * @return SeedDMS_Core_Group
     */
    protected function getMockGroup()
    {
        $user = $this->getMockBuilder(SeedDMS_Core_Group::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()->getMock();
        return $user;
    }

    /**
     * Create a mock group object
     *
     * @return SeedDMS_Core_Group
     */
    protected function getGroup()
    {
        $group = new SeedDMS_Core_Group(1, 'foogroup', 'My comment');
        return $group;
    }

    /**
     * Create a mock regular user object
     *
     * @return SeedDMS_Core_User
     */
    protected function getUser()
    {
        $user = new SeedDMS_Core_User(2, 'user', 'pass', 'Joe Baz', 'joe@foo.de', 'en_GB', 'bootstrap', 'My comment', SeedDMS_Core_User::role_user);
        return $user;
    }

    /**
     * Test method isType()
     *
     * @return void
     */
    public function testIsType()
    {
        $group = $this->getGroup();
        $this->assertTrue($group->isType('group'));
    }

    /**
     * Test method getName()
     *
     * @return void
     */
    public function testGetName()
    {
        $group = $this->getGroup();
        $this->assertEquals('foogroup', $group->getName());
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
        $group = self::$dms->addGroup('Group', '');
        $ret = $group->setName('foo');
        $this->assertTrue($ret);
        $name = $group->getName();
        $this->assertEquals('foo', $name);
        /* Setting an empty name must fail */
        $ret = $group->setName(' ');
        $this->assertFalse($ret);
    }

    /**
     * Test method setName()
     *
     * @return void
     */
    public function testSetNameSqlFail()
    {
        $group = $this->getGroup();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblGroups` SET `name`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $group->setDMS($dms);
        $this->assertFalse($group->setName('my name'));
    }

    /**
     * Test method getComment()
     *
     * @return void
     */
    public function testGetComment()
    {
        $group = $this->getGroup();
        $this->assertEquals('My comment', $group->getComment());
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
        $group = self::$dms->addGroup('Group', '');
        $ret = $group->setComment('foo');
        $this->assertTrue($ret);
        $comment = $group->getComment();
        $this->assertEquals('foo', $comment);
    }

    /**
     * Test method setComment()
     *
     * @return void
     */
    public function testSetCommentSqlFail()
    {
        $group = $this->getGroup();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblGroups` SET `comment`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $group->setDMS($dms);
        $this->assertFalse($group->setComment('my comment'));
    }

    /**
     * Test method getUsers()
     *
     * @return void
     */
    public function testGetUsersSqlFail()
    {
        $group = $this->getGroup();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT `tblUsers`.* FROM `tblUsers`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $group->setDMS($dms);
        $this->assertFalse($group->getUsers());
    }

    /**
     * Test method addUser(), isMember(), and removeUser()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddAndRemoveUser()
    {
        $group = self::$dms->addGroup('Group', '');
        if(self::$dms->version[0] == '5')
            $role = SeedDMS_Core_User::role_user;
        else {
            $role = SeedDMS_Core_Role::getInstance(3, self::$dms);
            $this->assertIsObject($role);
            $this->assertEquals($role->getRole(), SeedDMS_Core_Role::role_user);
        }
        $user1 = self::$dms->addUser('joe', 'pass', 'Joe Foo', 'joe@foo.de', 'en_GB', 'bootstrap', 'My comment', $role);
        $user2 = self::$dms->addUser('sally', 'pass', 'Sally Foo', 'sally@foo.de', 'en_GB', 'bootstrap', 'My comment', $role);

        /* Add user1 and user2. user2 is also a manager */
        $ret = $group->addUser($user1);
        $this->assertTrue($ret);
        $ret = $group->addUser($user2, true);
        $this->assertTrue($ret);

        $users = $group->getUsers();
        $this->assertIsArray($users);
        $this->assertCount(2, $users);

        $ret = $group->removeUser($user1);
        $this->assertTrue($ret);
        $users = $group->getUsers();
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
    }

    /**
     * Test method isMember(), toggleManager()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testIsMember()
    {
        $group = self::$dms->addGroup('Group', '');
        $user1 = self::$dms->addUser('joe', 'pass', 'Joe Foo', 'joe@foo.de', 'en_GB', 'bootstrap', 'My comment');
        $user2 = self::$dms->addUser('sally', 'pass', 'Sally Foo', 'sally@foo.de', 'en_GB', 'bootstrap', 'My comment');

        /* Add user1 and user2. user2 is also a manager */
        $ret = $group->addUser($user1);
        $this->assertTrue($ret);
        $ret = $group->addUser($user2, true);
        $this->assertTrue($ret);

        /* user1 is a member but not a manager */
        $ret = $group->isMember($user1);
        $this->assertTrue($ret);
        $ret = $group->isMember($user1, true);
        $this->assertFalse($ret);

        /* user2 is a member and a manager */
        $ret = $group->isMember($user2, true);
        $this->assertTrue($ret);
    }

    /**
     * Test method toggleManager()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testToggleManager()
    {
        $group = self::$dms->addGroup('Group', '');
        $user1 = self::$dms->addUser('joe', 'pass', 'Joe Foo', 'joe@foo.de', 'en_GB', 'bootstrap', 'My comment');

        /* Add user1 */
        $ret = $group->addUser($user1);
        $this->assertTrue($ret);

        /* user1 is a member but not a manager */
        $ret = $group->isMember($user1);
        $this->assertTrue($ret);
        $ret = $group->isMember($user1, true);
        $this->assertFalse($ret);

        /* Toggle manager mode of user 1 and check again */
        $ret = $group->toggleManager($user1);
        $ret = $group->isMember($user1, true);
        $this->assertTrue($ret);
    }

    /**
     * Test method getUsers()
     *
     * @return void
     */
    public function testGetUsers()
    {
        $group = $this->getGroup();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        if(self::$dbversion['major'] == 6) {
            $db->expects($this->exactly(2))
                ->method('getResultArray')
                ->withConsecutive([$this->stringContains("`tblGroupMembers`.`groupID` = '".$group->getId()."'")], [$this->stringContains("SELECT * FROM `tblRoles` WHERE `id` =")])
                ->willReturnOnConsecutiveCalls(array(array('id'=>2, 'login'=>'user', 'pwd'=>'pass', 'fullName'=>'Joe Baz', 'email'=>'joe@foo.de', 'language'=>'en_GB', 'theme'=>'bootstrap', 'comment'=>'', 'role'=>SeedDMS_Core_User::role_user, 'hidden'=>0, 'role'=>1)), array('id'=>1, 'name'=>'role', 'role'=>1, 'noaccess'=>''));
        } else {
            $db->expects($this->once())
                ->method('getResultArray')
                ->with($this->stringContains("`tblGroupMembers`.`groupID` = '".$group->getId()."'"))
                ->willReturn(array(array('id'=>2, 'login'=>'user', 'pwd'=>'pass', 'fullName'=>'Joe Baz', 'email'=>'joe@foo.de', 'language'=>'en_GB', 'theme'=>'bootstrap', 'comment'=>'', 'role'=>SeedDMS_Core_User::role_user, 'hidden'=>0, 'role'=>1)));
        }
        $dms = new SeedDMS_Core_DMS($db, '');

        $group->setDMS($dms);
        $users = $group->getUsers();
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
    }

    /**
     * Test method getManagers()
     *
     * @return void
     */
    public function testGetManagers()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        if(self::$dbversion['major'] == 6) {
            $db->expects($this->exactly(2))
                ->method('getResultArray')
                ->withConsecutive([$this->stringContains("`manager` = 1")], [$this->stringContains("SELECT * FROM `tblRoles` WHERE `id` =")])
                ->willReturnOnConsecutiveCalls(array(array('id'=>2, 'login'=>'user', 'pwd'=>'pass', 'fullName'=>'Joe Baz', 'email'=>'joe@foo.de', 'language'=>'en_GB', 'theme'=>'bootstrap', 'comment'=>'', 'role'=>SeedDMS_Core_User::role_user, 'hidden'=>0, 'role'=>1)), array('id'=>1, 'name'=>'role', 'role'=>1, 'noaccess'=>''));
        } else {
            $db->expects($this->once())
                ->method('getResultArray')
                ->with($this->stringContains('`manager` = 1'))
                ->willReturn(array(array('id'=>2, 'login'=>'user', 'pwd'=>'pass', 'fullName'=>'Joe Baz', 'email'=>'joe@foo.de', 'language'=>'en_GB', 'theme'=>'bootstrap', 'comment'=>'', 'role'=>SeedDMS_Core_User::role_user, 'hidden'=>0)));
        }
        $dms = new SeedDMS_Core_DMS($db, '');

        $group = $this->getGroup();
        $group->setDMS($dms);
        $managers = $group->getManagers();
        $this->assertIsArray($managers);
        $this->assertCount(1, $managers);
    }

    /**
     * Test method getNotifications()
     *
     * @return void
     */
    public function testGetNotifications()
    {
        $group = $this->getGroup();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("WHERE `tblNotify`.`groupID` = ".$group->getId()))
            ->willReturn(array(array('target'=>2, 'targetType'=>'0', 'userID'=>0, 'groupID'=>$group->getId())));
        $dms = new SeedDMS_Core_DMS($db, '');
        $group->setDMS($dms);
        $notifications = $group->getNotifications();
        $this->assertIsArray($notifications);
        $this->assertCount(1, $notifications);
        $this->assertInstanceOf(SeedDMS_Core_Notification::class, $notifications[0]);
    }

    /**
     * Test method getNotifications() with target type
     *
     * @return void
     */
    public function testGetNotificationsWithTargetType()
    {
        $group = $this->getGroup();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("WHERE `tblNotify`.`groupID` = ".$group->getId()." AND `tblNotify`.`targetType` = 1"))
            ->willReturn(array(array('target'=>2, 'targetType'=>'1', 'userID'=>0, 'groupID'=>$group->getId())));
        $dms = new SeedDMS_Core_DMS($db, '');
        $group->setDMS($dms);
        $notifications = $group->getNotifications(1);
        $this->assertIsArray($notifications);
        $this->assertCount(1, $notifications);
        $this->assertInstanceOf(SeedDMS_Core_Notification::class, $notifications[0]);
    }


}
