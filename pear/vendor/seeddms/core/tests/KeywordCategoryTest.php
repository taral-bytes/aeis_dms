<?php
/**
 * Implementation of the keyword tests
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
class KeywordCategoryTest extends SeedDmsTest
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
     * Test method getName() and setName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetName()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $cat = self::$dms->addKeywordCategory($user->getId(), 'Category 1');
        $name = $cat->getName();
        $ret = $cat->setName('foo');
        $this->assertTrue($ret);
        $name = $cat->getName();
        $this->assertEquals('foo', $name);
        $ret = $cat->setName(' ');
        $this->assertFalse($ret);
    }

    /**
     * Test method getOwner() and setOwner()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetOwner()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $guest = SeedDMS_Core_User::getInstance(2, self::$dms);
        $cat = self::$dms->addKeywordCategory($user->getId(), 'Category 1');
        $this->assertIsObject($cat);
        $ret = $cat->setOwner($guest);
        $this->assertTrue($ret);
        $owner = $cat->getOwner();
        $this->assertEquals(2, $owner->getId());
        $ret = $cat->setOwner(null);
        $this->assertFalse($ret);
    }

    /**
     * Test method addKeywordList() and editKeywordList(), getKeywordLists(), removeKeywordList()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetSetEditAndRemoveKeywordList()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $cat = self::$dms->addKeywordCategory($user->getId(), 'Category 1');
        $this->assertIsObject($cat);
        $ret = $cat->addKeywordList('foo');
        $this->assertTrue($ret);
        $ret = $cat->addKeywordList('bar');
        $this->assertTrue($ret);
        $list = $cat->getKeywordLists();
        $this->assertIsArray($list);
        $this->assertCount(2, $list);
        $ret = $cat->editKeywordList(1, 'baz');
        $this->assertTrue($ret);

        $ret = $cat->removeKeywordList(1);
        $this->assertTrue($ret);
        $list = $cat->getKeywordLists();
        $this->assertIsArray($list);
        $this->assertCount(1, $list);
    }

    /**
     * Test method addKeywordCategory() and remove()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAndAndRemoveKeywordCategory()
    {
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);
        $cat = self::$dms->addKeywordCategory($user->getId(), 'Category 1');
        $this->assertIsObject($cat);
        $ret = $cat->addKeywordList('foo');
        $this->assertTrue($ret);
        $ret = $cat->addKeywordList('bar');
        $this->assertTrue($ret);
        $ret = $cat->remove();
        $this->assertTrue($ret);
    }
}
