<?php
/**
 * Implementation of the user tests
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
 * User test class
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: @package_version@
 * @link      https://www.seeddms.org
 */
class UserTest extends SeedDmsTest
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
     * Create a mock admin user object
     *
     * @return SeedDMS_Core_User
     */
    protected function getAdminUser()
    {
        $user = new SeedDMS_Core_User(1, 'admin', 'pass', 'Joe Foo', 'baz@foo.de', 'en_GB', 'bootstrap', 'My comment', SeedDMS_Core_User::role_admin);
        return $user;
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
     * Test method setDMS() and getDMS()
     *
     * @return void
     */
    public function testSetAndGetDMS()
    {
        $user = $this->getAdminUser();
        $user->setDMS(self::$dms);
        $this->assertInstanceOf(SeedDMS_Core_DMS::class, $user->getDMS());
    }

    /**
     * Test method isType()
     *
     * @return void
     */
    public function testIsType()
    {
        $user = $this->getAdminUser();
        $this->assertTrue($user->isType('user'));
    }

    /**
     * Test method getPwd()
     *
     * @return void
     */
    public function testGetPwd()
    {
        $user = $this->getAdminUser();
        $this->assertEquals('pass', $user->getPwd());
    }

    /**
     * Test method getEmail()
     *
     * @return void
     */
    public function testGetEmail()
    {
        $user = $this->getAdminUser();
        $this->assertEquals('baz@foo.de', $user->getEmail());
    }

    /**
     * Test method getLanguage()
     *
     * @return void
     */
    public function testGetLanguage()
    {
        $user = $this->getAdminUser();
        $this->assertEquals('en_GB', $user->getLanguage());
    }

    /**
     * Test method getTheme()
     *
     * @return void
     */
    public function testGetTheme()
    {
        $user = $this->getAdminUser();
        $this->assertEquals('bootstrap', $user->getTheme());
    }

    /**
     * Test method getComment()
     *
     * @return void
     */
    public function testGetComment()
    {
        $user = $this->getAdminUser();
        $this->assertEquals('My comment', $user->getComment());
    }

    /**
     * Test method getRole()
     *
     * @return void
     */
    public function testGetRole()
    {
        $user = $this->getAdminUser();
        $this->assertEquals(1, $user->getRole());
    }

    /**
     * Test method isAdmin()
     *
     * @return void
     */
    public function testIsAdmin()
    {
        $user = $this->getAdminUser();
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isGuest());
    }

    /**
     * Test method isGuest()
     *
     * @return void
     */
    public function testIsGuest()
    {
        $user = $this->getAdminUser();
        $this->assertFalse($user->isGuest());
    }

    /**
     * Test method isHidden()
     *
     * @return void
     */
    public function testIsHidden()
    {
        $user = $this->getAdminUser();
        $this->assertFalse($user->isHidden());
    }

    /**
     * Test method getQuota()
     *
     * @return void
     */
    public function testGetQuota()
    {
        $user = $this->getAdminUser();
        $this->assertEquals(0, $user->getQuota());
    }

    /**
     * Test method getSecret()
     *
     * @return void
     */
    public function testGetSecret()
    {
        if(self::$dbversion['major'] < 6) {
            $this->markTestSkipped(
                'This test is not applicable for SeedDMS 5.'
            );
        } else {
            $user = $this->getAdminUser();
            $this->assertEquals('', $user->getSecret());
        }
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
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $this->assertIsObject($user);
        $this->assertEquals('admin', $user->getLogin());
        $user = SeedDMS_Core_User::getInstance('admin', self::$dms, 'name');
        $this->assertIsObject($user);
        $this->assertEquals('admin', $user->getLogin());
        $user = SeedDMS_Core_User::getInstance('admin', self::$dms, 'name', 'info@seeddms.org');
        $this->assertIsObject($user);
        $this->assertEquals('admin', $user->getLogin());
        /* get instance of none existing user */
        $user = SeedDMS_Core_User::getInstance('foo', self::$dms, 'name');
        $this->assertNull($user);
    }

    /**
     * Test method getAllInstances()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAllInstancesSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->exactly(2))
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblUsers` ORDER BY"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        /* Order by login */
        $users = SeedDMS_Core_User::getAllInstances('', $dms);
        $this->assertFalse($users);
        /* Order by fullname */
        $users = SeedDMS_Core_User::getAllInstances('fullname', $dms);
        $this->assertFalse($users);
    }

    /**
     * Test method getLogin()
     *
     * @return void
     */
    public function testGetLogin()
    {
        $user = $this->getAdminUser();
        $this->assertEquals('admin', $user->getLogin());
    }

    /**
     * Test method setLogin()
     *
     * @return void
     */
    public function testSetLoginSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `login`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setLogin('foo'));
    }

    /**
     * Test method getLogin() and setLogin()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetLogin()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $login = $user->getLogin();
        $ret = $user->setLogin('foo');
        $this->assertTrue($ret);
        $login = $user->getLogin();
        $this->assertEquals('foo', $login);
        $ret = $user->setLogin(' ');
        $this->assertFalse($ret);
    }

    /**
     * Test method getFullName()
     *
     * @return void
     */
    public function testGetFullName()
    {
        $user = $this->getAdminUser();
        $this->assertEquals('Joe Foo', $user->getFullName());
    }

    /**
     * Test method setFullName()
     *
     * @return void
     */
    public function testSetFullNameSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `fullName`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setFullName('foo'));
    }

    /**
     * Test method getFullName() and setFullName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetFullName()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $fullname = $user->getFullName();
        $ret = $user->setFullName('foo');
        $this->assertTrue($ret);
        $fullname = $user->getFullName();
        $this->assertEquals('foo', $fullname);
    }

    /**
     * Test method getPwd() and setPwd()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetPwd()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $pwd = $user->getPwd();
        $ret = $user->setPwd('foo');
        $this->assertTrue($ret);
        $pwd = $user->getPwd();
        $this->assertEquals('foo', $pwd);
    }

    /**
     * Test method setPwd()
     *
     * @return void
     */
    public function testSetPwdSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `pwd`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setPwd('foo'));
    }

    /**
     * Test method getPwdExpiration() and setPwdExpiration()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetPwdExpiration()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $pwdexp = $user->getPwdExpiration();
        /* Set password expiration to 'never' */
        $ret = $user->setPwdExpiration('never');
        $this->assertTrue($ret);
        $pwdexp = $user->getPwdExpiration();
        $this->assertNull($pwdexp);

        /* Set password expiration to 'now' */
        $now = date('Y-m-d H:i:s');
        $ret = $user->setPwdExpiration('now');
        $this->assertTrue($ret);
        $pwdexp = $user->getPwdExpiration();
        $this->assertEquals($now, $pwdexp);
    }

    /**
     * Test method setPwdExpiration()
     *
     * @return void
     */
    public function testSetPwdExpirationSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `pwdExpiration`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setPwdExpiration('foo'));
    }

    /**
     * Test method getEmail() and setEmail()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetEmail()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $email = $user->getEmail();
        $ret = $user->setEmail('new@seeddms.org');
        $this->assertTrue($ret);
        $email = $user->getEmail();
        $this->assertEquals('new@seeddms.org', $email);
    }

    /**
     * Test method setEmail()
     *
     * @return void
     */
    public function testSetEmailSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `email`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setEmail('foo'));
    }

    /**
     * Test method getLanguage() and setLanguage()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetLanguage()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $language = $user->getLanguage();
        $ret = $user->setLanguage('de_DE');
        $this->assertTrue($ret);
        $language = $user->getLanguage();
        $this->assertEquals('de_DE', $language);
    }

    /**
     * Test method setLanguage()
     *
     * @return void
     */
    public function testSetLanguageSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `language`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setLanguage('de_DE'));
    }

    /**
     * Test method getTheme() and setTheme()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetTheme()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $theme = $user->getTheme();
        $ret = $user->setTheme('bootstrap4');
        $this->assertTrue($ret);
        $theme = $user->getTheme();
        $this->assertEquals('bootstrap4', $theme);
    }

    /**
     * Test method setTheme()
     *
     * @return void
     */
    public function testSetThemeSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `theme`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setTheme('bootstrap'));
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
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $comment = $user->getComment();
        $ret = $user->setComment('my comment');
        $this->assertTrue($ret);
        $comment = $user->getComment();
        $this->assertEquals('my comment', $comment);
    }

    /**
     * Test method setComment()
     *
     * @return void
     */
    public function testSetCommentSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `comment`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setComment('my comment'));
    }

    /**
     * Test method getRole() and setRole()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetRole()
    {
        if(self::$dbversion['major'] < 6) {
            // SeedDMS 5 use integers for roles: 0=user, 1=admin, 2=guest
            $user = SeedDMS_Core_User::getInstance(1, self::$dms);
            // User with id=1 is the admin user in the initial database
            $role = $user->getRole();
            $this->assertEquals(SeedDMS_Core_User::role_admin, $role);
            $ret = $user->setRole(SeedDMS_Core_User::role_guest);
            $this->assertTrue($ret);
            $role = $user->getRole();
            $this->assertEquals(SeedDMS_Core_User::role_guest, $role);
            $ret = $user->setRole('');
            $this->assertFalse($ret);
        } else {
            // Starting with SeedDMS 6 a role is an object
            $user = SeedDMS_Core_User::getInstance(1, self::$dms);
            // User with id=1 is the admin user in the initial database
            $role = $user->getRole();
            $this->assertTrue($role->isAdmin());
            // SeedDMS_Core_User has an isAdmin() method too, which internally
            // uses SeedDMS_Core_Role::isAdmin()
            $this->assertTrue($user->isAdmin());
            // Get the guest role, which is supposed to have id=2 in the
            // initial database
            $guestrole = SeedDMS_Core_Role::getInstance(2, self::$dms);
            $this->assertTrue($guestrole->isGuest());
            // Assign guest role and check if the user is a guest
            $ret = $user->setRole($guestrole);
            $this->assertTrue($ret);
            $this->assertTrue($user->isGuest());
        }
    }

    /**
     * Test method setRole()
     *
     * @return void
     */
    public function testSetRoleSqlFail()
    {
        if(self::$dbversion['major'] > 5) {
            $role = $this->getAdminRole();
        }
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `role`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        if(self::$dbversion['major'] > 5) {
            $this->assertFalse($user->setRole($role));
        } else {
            $this->assertFalse($user->setRole(SeedDMS_Core_User::role_admin));
        }
    }

    /**
     * Test method setGuest()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetGuest()
    {
        if(self::$dbversion['major'] == '5') {
            $user = SeedDMS_Core_User::getInstance(1, self::$dms);
            $role = $user->getRole();
            $ret = $user->setGuest();
            $this->assertTrue($ret);
            $role = $user->getRole();
            $this->assertEquals(SeedDMS_Core_User::role_guest, $role);
        } else {
            $this->markTestSkipped(
                'This test is not applicable for SeedDMS 6.'
            );
        }
    }

    /**
     * Test method setGuest()
     *
     * @return void
     */
    public function testSetGuestSqlFail()
    {
        $dms = new SeedDMS_Core_DMS(null, '');
        if(self::$dbversion['major'] == '5') {
            $user = $this->getAdminUser();
            $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
            $db->expects($this->once())
                ->method('getResult')
                ->with($this->stringContains("UPDATE `tblUsers` SET `role`"))
                ->willReturn(false);
            $dms = new SeedDMS_Core_DMS($db, '');
            $user->setDMS($dms);
            $this->assertFalse($user->setGuest());
        } else {
            $this->markTestSkipped(
                'This test is not applicable for SeedDMS 6.'
            );
        }
    }

    /**
     * Test method setAdmin()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetAdmin()
    {
        if(self::$dbversion['major'] == '5') {
            $user = SeedDMS_Core_User::getInstance(2, self::$dms);
            $role = $user->getRole();
            $ret = $user->setAdmin();
            $this->assertTrue($ret);
            $role = $user->getRole();
            $this->assertEquals(SeedDMS_Core_User::role_admin, $role);
        } else {
            $this->markTestSkipped(
                'This test is not applicable for SeedDMS 6.'
            );
        }
    }

    /**
     * Test method setAdmin()
     *
     * @return void
     */
    public function testSetAdminSqlFail()
    {
        $dms = new SeedDMS_Core_DMS(null, '');
        if(self::$dbversion['major'] == '5') {
            $user = $this->getAdminUser();
            $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
            $db->expects($this->once())
                ->method('getResult')
                ->with($this->stringContains("UPDATE `tblUsers` SET `role`"))
                ->willReturn(false);
            $dms = new SeedDMS_Core_DMS($db, '');
            $user->setDMS($dms);
            $this->assertFalse($user->setAdmin());
        } else {
            $this->markTestSkipped(
                'This test is not applicable for SeedDMS 6.'
            );
        }
    }

    /**
     * Test method getQuota() and setQuota()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetQuota()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $quota = $user->getQuota();
        $ret = $user->setQuota(100000);
        $this->assertTrue($ret);
        $quota = $user->getQuota();
        $this->assertEquals(100000, $quota);
        /* Setting a non numeric or negative value will fail */
        $ret = $user->setQuota('foo');
        $this->assertFalse($ret);
        $ret = $user->setQuota(-100);
        $this->assertFalse($ret);
    }

    /**
     * Test method setQuota()
     *
     * @return void
     */
    public function testSetQuotaSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `quota`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setQuota(10000));
    }

    /**
     * Test method getSecret() and setSecret()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetSecret()
    {
        if(self::$dbversion['major'] < 6) {
            $this->markTestSkipped(
                'This test is not applicable for SeedDMS 5.'
            );
        } else {
            $user = SeedDMS_Core_User::getInstance(1, self::$dms);
            $secret = $user->getSecret();
            $ret = $user->setSecret('secret');
            $this->assertTrue($ret);
            $secret = $user->getSecret();
            $this->assertEquals('secret', $secret);
        }
    }

    /**
     * Test method setSecret()
     *
     * @return void
     */
    public function testSetSecretSqlFail()
    {
        if(self::$dbversion['major'] < 6) {
            $this->markTestSkipped(
                'This test is not applicable for SeedDMS 5.'
            );
        } else {
            $user = $this->getAdminUser();
            $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
            $db->expects($this->once())
                ->method('getResult')
                ->with($this->stringContains("UPDATE `tblUsers` SET `secret`"))
                ->willReturn(false);
            $dms = new SeedDMS_Core_DMS($db, '');
            $user->setDMS($dms);
            $this->assertFalse($user->setSecret('secret'));
        }
    }

    /**
     * Test method isHidden() and setHidden()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testIsAndSetHidden()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $ishidden = $user->isHidden();
        /* set hidden to true */
        $ret = $user->setHidden(true);
        $this->assertTrue($ret);
        $ishidden = $user->isHidden();
        $this->assertTrue($ishidden);
        /* set hidden to false */
        $ret = $user->setHidden(false);
        $this->assertTrue($ret);
        $ishidden = $user->isHidden();
        $this->assertFalse($ishidden);
    }

    /**
     * Test method setHidden()
     *
     * @return void
     */
    public function testSetHiddentSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `hidden`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setHidden(true));
    }

    /**
     * Test method isDisabled() and setDisabled()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testIsAndSetDisabled()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $isdisabled = $user->isDisabled();
        /* set disabled to true */
        $ret = $user->setDisabled(true);
        $this->assertTrue($ret);
        $isdisabled = $user->isDisabled();
        $this->assertTrue($isdisabled);
        /* set disabled to false */
        $ret = $user->setDisabled(false);
        $this->assertTrue($ret);
        $isdisabled = $user->isDisabled();
        $this->assertFalse($isdisabled);
    }

    /**
     * Test method setDisabled()
     *
     * @return void
     */
    public function testSetDisabledtSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `disabled`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setDisabled(true));
    }

    /**
     * Test method addLoginFailure()
     *
     * @return void
     */
    public function testAddLoginFailure()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->exactly(2))
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `loginfailures`"))
            ->willReturn(true);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertEquals(1, $user->addLoginFailure());
        $this->assertEquals(2, $user->addLoginFailure());
    }

    /**
     * Test method addLoginFailure()
     *
     * @return void
     */
    public function testAddLoginFailureSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `loginfailures`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->addLoginFailure());
    }

    /**
     * Test method clearLoginFailure()
     *
     * @return void
     */
    public function testClearLoginFailure()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->exactly(2))
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `loginfailures`"))
            ->willReturn(true);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertEquals(1, $user->addLoginFailure());
        $this->assertEquals(true, $user->clearLoginFailures());
    }

    /**
     * Test method clearLoginFailure()
     *
     * @return void
     */
    public function testClearLoginFailureSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `loginfailures`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->clearLoginFailures());
    }

    /**
     * Test method setHomeFolder() and getHomeFolder()
     *
     * @return void
     */
    public function testSetAndGetHomeFolder()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `homefolder`"))
            ->willReturn(true);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertTrue($user->setHomeFolder(1));
        $this->assertEquals(1, $user->getHomeFolder());
    }

    /**
     * Test method setHomeFolder()
     *
     * @return void
     */
    public function testSetHomeFolderSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE `tblUsers` SET `homefolder`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->setHomeFolder(1));
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
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $size = $user->getUsedDiskSpace();
        $this->assertEquals(0, $size);
    }

    /**
     * Test method getUsedDiskSpace()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetUsedDiskSpaceSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT SUM(`fileSize`) sum"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->getUsedDiskSpace());
    }

    /**
     * Test method removeFromProcesses()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testRemoveFromProcesses()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $ret = $user->removeFromProcesses($user);
        $this->assertTrue($ret);
    }

    /**
     * Test method transferDocumentsFolders()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testTransferDocumentsFolders()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        self::createSimpleFolderStructureWithDocuments();
        $newuser = self::$dms->addUser('newuser', '', 'New User', 'newuser@seeddms.org', 'en_GB', 'bootstrap', '');
        /* Transfering documents and folders to the same user returns true */
        $ret = $user->transferDocumentsFolders($user);
        $this->assertTrue($ret);
        /* A subfolder still belongs to $user */
        $subfolder = self::$dms->getFolder(2);
        $this->assertEquals($user->getId(), $subfolder->getOwner()->getId());
        /* A document still belongs to $user */
        $document = self::$dms->getDocument(1);
        $this->assertEquals($user->getId(), $document->getOwner()->getId());
        /* Transfer the documents and folders to $newuser */
        $ret = $user->transferDocumentsFolders($newuser);
        $this->assertTrue($ret);
        /* Get the folder again, because the owner has changed */
        $subfolder = self::$dms->getFolder(2);
        $this->assertEquals($newuser->getId(), $subfolder->getOwner()->getId());
        /* Get the document again, because the owner has changed */
        $document = self::$dms->getDocument(1);
        $this->assertEquals($newuser->getId(), $document->getOwner()->getId());
    }

    /**
     * Test method remove()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testRemove()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        self::createSimpleFolderStructureWithDocuments();
        $newuser = self::$dms->addUser('newuser', '', 'New User', 'newuser@seeddms.org', 'en_GB', 'bootstrap', '');
        /* removing a user without passed a new user for docs and folders will fail */
        $ret = $user->remove($newuser, null);
        $this->assertFalse($ret);

        $ret = $user->remove($newuser, $newuser);
        $this->assertTrue($ret);

        /* all documents and folders now belong to $newuser */
        $document = self::$dms->getDocument(1);
        $this->assertEquals($newuser->getId(), $document->getOwner()->getId());
        $subfolder = self::$dms->getFolder(1);
        $this->assertEquals($newuser->getId(), $subfolder->getOwner()->getId());
    }

    /**
     * Test method getDocuments()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocuments()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $documents = $user->getDocuments();
        $this->assertIsArray($documents);
        $this->assertCount(0, $documents);
    }

    /**
     * Test method getDocumentsLocked()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentsLocked()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $documents = $user->getDocumentsLocked();
        $this->assertIsArray($documents);
        $this->assertCount(0, $documents);
    }

    /**
     * Test method getDocumentLinks()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentLinks()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $links = $user->getDocumentLinks();
        $this->assertIsArray($links);
        $this->assertCount(0, $links);
    }

    /**
     * Test method getDocumentFiles()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentFiles()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $files = $user->getDocumentFiles();
        $this->assertIsArray($files);
        $this->assertCount(0, $files);
    }

    /**
     * Test method getDocumentContents()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetDocumentContents()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $contents = $user->getDocumentContents();
        $this->assertIsArray($contents);
        $this->assertCount(0, $contents);
    }

    /**
     * Test method getFolders()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetFolders()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $folders = $user->getFolders();
        $this->assertIsArray($folders);
        $this->assertCount(1, $folders);
    }

    /**
     * Test method getReviewStatus()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetReviewStatus()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $status = $user->getReviewStatus();
        $this->assertIsArray($status);
        $this->assertCount(2, $status);
        $this->assertCount(0, $status['indstatus']);
        $this->assertCount(0, $status['grpstatus']);
    }

    /**
     * Test method getApprovalStatus()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetApprovalStatus()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $status = $user->getApprovalStatus();
        $this->assertIsArray($status);
        $this->assertCount(2, $status);
        $this->assertCount(0, $status['indstatus']);
        $this->assertCount(0, $status['grpstatus']);
    }

    /**
     * Test method getWorkflowStatus()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetWorkflowStatus()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $status = $user->getWorkflowStatus();
        $this->assertIsArray($status);
        $this->assertCount(2, $status);
        $this->assertCount(0, $status['u']);
        $this->assertCount(0, $status['g']);
    }

    /**
     * Test method getWorkflowsInvolved()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetWorkflowsInvolved()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $workflows = $user->getWorkflowsInvolved();
        $this->assertIsArray($workflows);
        $this->assertCount(0, $workflows);
    }

    /**
     * Test method getMandatoryReviewers()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetMandatoryReviewers()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $reviewers = $user->getMandatoryReviewers();
        $this->assertIsArray($reviewers);
        $this->assertCount(0, $reviewers);
    }

    /**
     * Test method setMandatoryReviewer()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetMandatoryReviewer()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $newuser = self::$dms->addUser('newuser', '', 'New User', 'newuser@seeddms.org', 'en_GB', 'bootstrap', '');
        $ret = $user->setMandatoryReviewer($newuser->getId(), false);
        $this->assertTrue($ret);
        $reviewers = $user->getMandatoryReviewers();
        $this->assertIsArray($reviewers);
        $this->assertCount(1, $reviewers);
        /* $newuser is now a mandatory user of $user */
        $mandatoryreviewers = $newuser->isMandatoryReviewerOf();
        $this->assertIsArray($mandatoryreviewers);
        $this->assertCount(1, $mandatoryreviewers);
        $this->assertEquals($user->getId(), $mandatoryreviewers[0]->getId());

        $group = self::$dms->addGroup('Group', '');
        $ret = $user->setMandatoryReviewer($group->getId(), true);
        $this->assertTrue($ret);
        $reviewers = $user->getMandatoryReviewers();
        $this->assertIsArray($reviewers);
        $this->assertCount(2, $reviewers);
        /* FIXME: there is not isMandatoryReviewerOf() for groups */
    }

    /**
     * Test method getMandatoryApprovers()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetMandatoryApprovers()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $approvers = $user->getMandatoryApprovers();
        $this->assertIsArray($approvers);
        $this->assertCount(0, $approvers);
    }

    /**
     * Test method setMandatoryApprover()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetMandatoryApprover()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $newuser = self::$dms->addUser('newuser', '', 'New User', 'newuser@seeddms.org', 'en_GB', 'bootstrap', '');
        $ret = $user->setMandatoryApprover($newuser->getId(), false);
        $this->assertTrue($ret);
        $approvers = $user->getMandatoryApprovers();
        $this->assertIsArray($approvers);
        $this->assertCount(1, $approvers);
        /* $newuser is now a mandatory user of $user */
        $mandatoryapprovers = $newuser->isMandatoryApproverOf();
        $this->assertIsArray($mandatoryapprovers);
        $this->assertCount(1, $mandatoryapprovers);
        $this->assertEquals($user->getId(), $mandatoryapprovers[0]->getId());

        $group = self::$dms->addGroup('Group', '');
        $ret = $user->setMandatoryApprover($group->getId(), true);
        $this->assertTrue($ret);
        $approvers = $user->getMandatoryApprovers();
        $this->assertIsArray($approvers);
        $this->assertCount(2, $approvers);
        /* FIXME: there is not isMandatoryApproverOf() for groups */
    }

    /**
     * Test method setMandatoryWorkflow()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetMandatoryWorkflow()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $approver = self::$dms->addUser('approver', '', 'Approver', 'newuser@seeddms.org', 'en_GB', 'bootstrap', '');
        $reviewer = self::$dms->addUser('reviewer', '', 'Reviewer', 'newuser@seeddms.org', 'en_GB', 'bootstrap', '');
        $simpleworkflow = self::createSimpleWorkflow($approver);
        $traditionalworkflow = self::createWorkflow($reviewer, $approver);
        $newuser = self::$dms->addUser('newuser', '', 'New User', 'newuser@seeddms.org', 'en_GB', 'bootstrap', '');
        /* Set a single mandatory workflow */
        $ret = $newuser->setMandatoryWorkflow($simpleworkflow);
        $this->assertTrue($ret);
        $workflows = $newuser->getMandatoryWorkflows();
        $this->assertIsArray($workflows);
        $this->assertCount(1, $workflows);

        /* Set a single mandatory workflow will add it to the list of workflows */
        $ret = $newuser->setMandatoryWorkflow($traditionalworkflow);
        $this->assertTrue($ret);
        $workflows = $newuser->getMandatoryWorkflows();
        $this->assertIsArray($workflows);
        $this->assertCount(2, $workflows);

        /* Set a single mandatory workflow with setMandatoryWorkflows() will delete
         * all existing workflows and set a new list of workflows
         */
        $ret = $newuser->setMandatoryWorkflows([$simpleworkflow]);
        $this->assertTrue($ret);
        $workflows = $newuser->getMandatoryWorkflows();
        $this->assertIsArray($workflows);
        $this->assertCount(1, $workflows);

        /* Set several mandatory workflows will delete all existing workflows
         * and set new workflows.
         */
        $ret = $newuser->setMandatoryWorkflows([$simpleworkflow, $traditionalworkflow]);
        $this->assertTrue($ret);
        $workflows = $newuser->getMandatoryWorkflows();
        $this->assertIsArray($workflows);
        $this->assertCount(2, $workflows);

        /* Setting an empty list will delete all mandatory workflows */
        $ret = $newuser->setMandatoryWorkflows([]);
        $this->assertTrue($ret);
        $workflows = $newuser->getMandatoryWorkflows();
        $this->assertNull($workflows);
    }

    /**
     * Test method getMandatoryWorkflow()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetMandatoryWorkflow()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $workflow = $user->getMandatoryWorkflow();
        $this->assertNull($workflow);
    }

    /**
     * Test method getMandatoryWorkflows()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetMandatoryWorkflows()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $workflow = $user->getMandatoryWorkflows();
        $this->assertNull($workflow);
    }

    /**
     * Test method getGroups()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetGroups()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $groups = $user->getGroups();
        $this->assertIsArray($groups);
        $this->assertCount(0, $groups);
        $group = self::$dms->addGroup('Group', '');
        $ret = $user->joinGroup($group);
        $this->assertTrue($ret);
        /* Adding the user a twice to a group will fail */
        $ret = $user->joinGroup($group);
        $this->assertFalse($ret);
        /* user now belongs to two groups */
        $groups = $user->getGroups();
        $this->assertIsArray($groups);
        $this->assertCount(1, $groups);
        /* Leave the group */
        $ret = $user->leaveGroup($group);
        $this->assertTrue($ret);
        /* Leave the group again will fail */
        $ret = $user->leaveGroup($group);
        $this->assertFalse($ret);
        /* the user is no longer in any group */
        $groups = $user->getGroups();
        $this->assertIsArray($groups);
        $this->assertCount(0, $groups);
    }

    /**
     * Test method hasImage()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testHasImage()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $image = $user->hasImage();
        $this->assertFalse($image);
    }

    /**
     * Test method hasImage()
     *
     * @return void
     */
    public function testHasImageSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT COUNT(*) AS num FROM `tblUserImages` WHERE"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->hasImage());
    }

    /**
     * Test method getImage()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetImage()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $image = $user->getImage();
        $this->assertNull($image);
    }

    /**
     * Test method getImage()
     *
     * @return void
     */
    public function testGetImageSqlFail()
    {
        $user = $this->getAdminUser();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblUserImages` WHERE"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $user->setDMS($dms);
        $this->assertFalse($user->getImage());
    }

    /**
     * Test method setImage()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testSetImage()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $file = self::createTempFile(200);
        $ret = $user->setImage($file, 'text/plain');
        $this->assertTrue(SeedDMS_Core_File::removeFile($file));
        $this->assertTrue($ret);
        $ret = $user->hasImage();
        $this->assertTrue($ret);
        $image = $user->getImage();
        $this->assertIsArray($image);
        $this->assertEquals('text/plain', $image['mimeType']);
    }

    /**
     * Test method delMandatoryReviewers()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testDelMandatoryReviewers()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $ret = $user->delMandatoryReviewers();
        $this->assertTrue($ret);
    }

    /**
     * Test method delMandatoryApprovers()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testDelMandatoryApprovers()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $ret = $user->delMandatoryApprovers();
        $this->assertTrue($ret);
    }

    /**
     * Test method delMandatoryWorkflow()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testDelMandatoryWorkflow()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $ret = $user->delMandatoryWorkflow();
        $this->assertTrue($ret);
    }

    /**
     * Test method getNotifications()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetNotifications()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $notifications = $user->getNotifications();
        $this->assertIsArray($notifications);
        $this->assertCount(0, $notifications);
        $notifications = $user->getNotifications(0);
        $this->assertIsArray($notifications);
        $this->assertCount(0, $notifications);
        $notifications = $user->getNotifications(1);
        $this->assertIsArray($notifications);
        $this->assertCount(0, $notifications);
    }

    /**
     * Test method getKeywordCategories()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetKeywordCategories()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $cats = $user->getKeywordCategories();
        $this->assertIsArray($cats);
        $this->assertCount(0, $cats);
    }
}

