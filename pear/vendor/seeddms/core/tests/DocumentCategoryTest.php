<?php
/**
 * Implementation of the category tests
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
class DocumentCategoryTest extends SeedDmsTest
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
        $cat = self::$dms->addDocumentCategory('Category 1');
        $name = $cat->getName();
        $ret = $cat->setName('foo');
        $this->assertTrue($ret);
        $name = $cat->getName();
        $this->assertEquals('foo', $name);
        $ret = $cat->setName(' ');
        $this->assertFalse($ret);
    }

    /**
     * Test method addCategories(), hasCategory(), setCategory()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAddCategoryToDocument()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = SeedDMS_Core_User::getInstance(1, self::$dms);

        /* Add a new document and two categories */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $cat1 = self::$dms->addDocumentCategory('Category 1');
        $cat2 = self::$dms->addDocumentCategory('Category 2');

        /* There are no categories yet */
        $ret = $document->hasCategory($cat1);
        $this->assertFalse($ret);

        /* Not passing a category yields on error */
        $ret = $document->hasCategory(null);
        $this->assertFalse($ret);

        /* Adding a category ... */
        $ret = $document->addCategories([$cat1]);
        $this->assertTrue($ret);

        /* ... and check if it is there */
        $ret = $document->hasCategory($cat1);
        $this->assertTrue($ret);

        /* There should be one category now */
        $cats = $document->getCategories();
        $this->assertIsArray($cats);
        $this->assertCount(1, $cats);
        $this->assertEquals($cat1->getName(), $cats[0]->getName());

        /* Adding the same category shouldn't change anything */
        $ret = $document->addCategories([$cat1]);
        $this->assertTrue($ret);

        /* Check if category is used */
        $ret = $cat1->isUsed();
        $this->assertTrue($ret);
        $ret = $cat2->isUsed();
        $this->assertFalse($ret);

        /* There is one document with cat 1 but none with cat 2 */
        $docs = $cat1->getDocumentsByCategory();
        $this->assertIsArray($docs);
        $this->assertCount(1, $docs);
        $num = $cat1->countDocumentsByCategory();
        $this->assertEquals(1, $num);
        $docs = $cat2->getDocumentsByCategory();
        $this->assertIsArray($docs);
        $this->assertCount(0, $docs);
        $num = $cat2->countDocumentsByCategory();
        $this->assertEquals(0, $num);

        /* Still only one category */
        $cats = $document->getCategories();
        $this->assertIsArray($cats);
        $this->assertCount(1, $cats);

        /* Setting new categories will replace the old ones */
        $ret = $document->setCategories([$cat1, $cat2]);
        $this->assertTrue($ret);

        /* Now we have two categories */
        $cats = $document->getCategories();
        $this->assertIsArray($cats);
        $this->assertCount(2, $cats);

        /* Remove a category */
        $ret = $document->removeCategories([$cat1]);
        $this->assertTrue($ret);

        /* Removing the same category again does not harm*/
        $ret = $document->removeCategories([$cat1]);
        $this->assertTrue($ret);

        /* We are back to one category */
        $cats = $document->getCategories();
        $this->assertIsArray($cats);
        $this->assertCount(1, $cats);

        /* Remove the remaining category from the document */
        $ret = $document->removeCategories($cats);
        $this->assertTrue($ret);

        /* No category left */
        $cats = $document->getCategories();
        $this->assertIsArray($cats);
        $this->assertCount(0, $cats);

        /* Remove the category itself */
        $cats = self::$dms->getDocumentCategories();
        $this->assertIsArray($cats);
        $this->assertCount(2, $cats);
        $ret = $cat1->remove();
        $cats = self::$dms->getDocumentCategories();
        $this->assertIsArray($cats);
        $this->assertCount(1, $cats);
    }

    /**
     * Test method getCategories() with sql fail
     *
     * @return void
     */
    public function testGetCategoriesSqlFail()
    {
        $document = $this->getMockedDocument();
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblCategory` WHERE"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document->setDMS($dms);
        $this->assertFalse($document->getCategories());
    }

    /**
     * Test method addCategories() with sql fail
     *
     * @return void
     */
    public function testAddCategoriesSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        /* mock sql statement in getCategories() which is called in addCategories() */
        $db->expects($this->once())
            ->method('getResultArray')
            ->with($this->stringContains("SELECT * FROM `tblCategory` WHERE"))
            ->willReturn([]);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("INSERT INTO `tblDocumentCategory`"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document = $this->getMockedDocument();
        $document->setDMS($dms);
		$cat = new SeedDMS_Core_DocumentCategory(1, 'Category');
		$cat->setDMS($dms);
        $this->assertFalse($document->addCategories([$cat]));
    }

    /**
     * Test method removeCategories() with sql fail
     *
     * @return void
     */
    public function testRemoveCategoriesSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("DELETE FROM `tblDocumentCategory` WHERE"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document = $this->getMockedDocument();
        $document->setDMS($dms);
		$cat = new SeedDMS_Core_DocumentCategory(1, 'Category');
		$cat->setDMS($dms);
        $this->assertFalse($document->removeCategories([$cat]));
    }

    /**
     * Test method setCategories() with sql fail when deleting categories
     *
     * @return void
     */
    public function testSetCategoriesSqlFail()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("DELETE FROM `tblDocumentCategory` WHERE"))
            ->willReturn(false);
        $dms = new SeedDMS_Core_DMS($db, '');
        $document = $this->getMockedDocument();
        $document->setDMS($dms);
		$cat = new SeedDMS_Core_DocumentCategory(1, 'Category');
		$cat->setDMS($dms);
        $this->assertFalse($document->setCategories([$cat]));
    }

    /**
     * Test method setCategories() with sql fail when inserting new categories
     *
     * @return void
     */
    public function testSetCategoriesSqlFail2()
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->exactly(2))
            ->method('getResult')
            ->will(
                $this->returnValueMap(
                    array(
                        array("DELETE FROM `tblDocumentCategory` WHERE `documentID` = 1", true, true),
                        array("INSERT INTO `tblDocumentCategory`", true, false)
                    )
                )
            );
        $dms = new SeedDMS_Core_DMS($db, '');
        $document = $this->getMockedDocument();
        $document->setDMS($dms);
		$cat = new SeedDMS_Core_DocumentCategory(1, 'Category');
		$cat->setDMS($dms);
        $this->assertFalse($document->setCategories([$cat]));
    }

}

