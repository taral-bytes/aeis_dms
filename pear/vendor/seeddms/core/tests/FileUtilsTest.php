<?php
/**
 * Implementation of the file utils tests
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
class FileUtilsTest extends SeedDmsTest
{
    /**
     * Create temporary directory
     *
     * @return void
     */
    protected function setUp(): void
    {
        self::$contentdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpunit-'.time();
        mkdir(self::$contentdir);
    }

    /**
     * Clean up at tear down
     *
     * @return void
     */
    protected function tearDown(): void
    {
        exec('rm -rf '.self::$contentdir);
    }

    /**
     * Test method format_filesize()
     *
     * @return void
     */
    public function testFormatFileSize()
    {
        $this->assertEquals('1 Byte', SeedDMS_Core_File::format_filesize(1));
        $this->assertEquals('0 Bytes', SeedDMS_Core_File::format_filesize(0));
        $this->assertEquals('1000 Bytes', SeedDMS_Core_File::format_filesize(1000));
        $this->assertEquals('1 KiB', SeedDMS_Core_File::format_filesize(1024));
        $this->assertEquals('1 KiB', SeedDMS_Core_File::format_filesize(1025));
        $this->assertEquals('2 KiB', SeedDMS_Core_File::format_filesize(2047));
        $this->assertEquals('1 MiB', SeedDMS_Core_File::format_filesize(1024*1024));
        $this->assertEquals('1 GiB', SeedDMS_Core_File::format_filesize(1024*1024*1024));
    }

    /**
     * Test method format_filesize()
     *
     * @return void
     */
    public function testParseFileSize()
    {
        $this->assertEquals(200, SeedDMS_Core_File::parse_filesize('200B'));
        $this->assertEquals(200, SeedDMS_Core_File::parse_filesize('200 B'));
        $this->assertEquals(200, SeedDMS_Core_File::parse_filesize('200'));
        $this->assertEquals(1024, SeedDMS_Core_File::parse_filesize('1K'));
        $this->assertEquals(2*1024*1024, SeedDMS_Core_File::parse_filesize('2M'));
        $this->assertEquals(3*1024*1024*1024, SeedDMS_Core_File::parse_filesize('3 G'));
        $this->assertEquals(4*1024*1024*1024*1024, SeedDMS_Core_File::parse_filesize('4 T'));
        $this->assertFalse(SeedDMS_Core_File::parse_filesize('4 t'));
        $this->assertFalse(SeedDMS_Core_File::parse_filesize('-4T'));
    }

    /**
     * Test method fileSize()
     *
     * @return void
     */
    public function testFileSize()
    {
        $filename = self::createTempFile(200, self::$contentdir);
        $this->assertEquals(200, SeedDMS_Core_File::fileSize($filename));
        /* Getting the size of a none existing file returns false */
        $this->assertFalse(SeedDMS_Core_File::fileSize('foobar'));
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
    }

    /**
     * Test method file_exists()
     *
     * @return void
     */
    public function testFileExists()
    {
        $filename = self::createTempFile(200, self::$contentdir);
        $this->assertTrue(SeedDMS_Core_File::file_exists($filename));
        $this->assertFalse(SeedDMS_Core_File::file_exists($filename.'bla'));
        $this->assertTrue(SeedDMS_Core_File::removeFile($filename));
    }

    /**
     * Test method fileExtension()
     *
     * @return void
     */
    public function testFileExtension()
    {
        $this->assertEquals('png', SeedDMS_Core_File::fileExtension('image/png'));
        $this->assertEquals('', SeedDMS_Core_File::fileExtension('image/kpng'));
        $this->assertEquals('txt', SeedDMS_Core_File::fileExtension('text/plain'));
        $this->assertEquals('md', SeedDMS_Core_File::fileExtension('text/markdown'));
    }

    /**
     * Test method moveFile()
     *
     * @return void
     */
    public function testMoveFile()
    {
        $filename = self::createTempFile(200, self::$contentdir);
        $this->assertEquals(200, SeedDMS_Core_File::fileSize($filename));
        $ret = SeedDMS_Core_File::moveFile($filename, self::$contentdir.DIRECTORY_SEPARATOR."foobar");
        $this->assertTrue($ret);
        /* Getting the file size of the old doc must fail now */
        $this->assertFalse(SeedDMS_Core_File::fileSize($filename));
        /* Getting the file size of the new doc succeds */
        $this->assertEquals(200, SeedDMS_Core_File::fileSize(self::$contentdir.DIRECTORY_SEPARATOR."foobar"));
        $this->assertTrue(SeedDMS_Core_File::removeFile(self::$contentdir.DIRECTORY_SEPARATOR."foobar"));
    }

    /**
     * Test method makeDir(), renameDir(), removeDir()
     *
     * @return void
     */
    public function testMakeRenameAndRemoveDir()
    {
        /* Create a directory and put a file into it */
        $ret = SeedDMS_Core_File::makeDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar");
        system('touch '.self::$contentdir.DIRECTORY_SEPARATOR."foobar".DIRECTORY_SEPARATOR."tt");
        /* Rename the directory */
        $ret = SeedDMS_Core_File::renameDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar", self::$contentdir.DIRECTORY_SEPARATOR."bazfoo");
        $this->assertTrue($ret);
        /* The new must exist and the old one is gone */
        $this->assertTrue(is_dir(self::$contentdir.DIRECTORY_SEPARATOR."bazfoo"));
        $this->assertFalse(is_dir(self::$contentdir.DIRECTORY_SEPARATOR."foobar"));
        $this->assertTrue(SeedDMS_Core_File::removeDir(self::$contentdir.DIRECTORY_SEPARATOR."bazfoo"));
        $this->assertFalse(SeedDMS_Core_File::removeDir(self::$contentdir.DIRECTORY_SEPARATOR."bazfoo"));
        $this->assertFalse(SeedDMS_Core_File::removeDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar"));

        /* Create a directory, a sub directory and a file */
        $ret = SeedDMS_Core_File::makeDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar");
        $this->assertTrue($ret);
        $ret = SeedDMS_Core_File::makeDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar".DIRECTORY_SEPARATOR."bazfoo");
        $this->assertTrue($ret);
        system('touch '.self::$contentdir.DIRECTORY_SEPARATOR."foobar".DIRECTORY_SEPARATOR."bazfoo".DIRECTORY_SEPARATOR."tt");
        $this->assertTrue(SeedDMS_Core_File::file_exists(self::$contentdir.DIRECTORY_SEPARATOR."foobar".DIRECTORY_SEPARATOR."bazfoo".DIRECTORY_SEPARATOR."tt"));

        $ret = SeedDMS_Core_File::removeDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar");
        $this->assertTrue($ret);
        $this->assertFalse(SeedDMS_Core_File::file_exists(self::$contentdir.DIRECTORY_SEPARATOR."foobar"));
        $this->assertFalse(SeedDMS_Core_File::file_exists(self::$contentdir.DIRECTORY_SEPARATOR."foobar".DIRECTORY_SEPARATOR."bazfoo"));
        $this->assertFalse(SeedDMS_Core_File::file_exists(self::$contentdir.DIRECTORY_SEPARATOR."foobar".DIRECTORY_SEPARATOR."bazfoo".DIRECTORY_SEPARATOR."tt"));
    }

    /**
     * Test method makeDir(), copyDir(), removeDir()
     *
     * @return void
     */
    public function testMakeCopyAndRemoveDir()
    {
        /* Create a directory and put a file into it */
        $ret = SeedDMS_Core_File::makeDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar");
        system('touch '.self::$contentdir.DIRECTORY_SEPARATOR."foobar".DIRECTORY_SEPARATOR."tt");
        /* Rename the directory */
        $ret = SeedDMS_Core_File::copyDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar", self::$contentdir.DIRECTORY_SEPARATOR."bazfoo");
        $this->assertTrue($ret);
        /* The new and the old dir must exist */
        $this->assertTrue(is_dir(self::$contentdir.DIRECTORY_SEPARATOR."bazfoo"));
        $this->assertTrue(is_dir(self::$contentdir.DIRECTORY_SEPARATOR."foobar"));
        $this->assertTrue(SeedDMS_Core_File::removeDir(self::$contentdir.DIRECTORY_SEPARATOR."bazfoo"));
        $this->assertTrue(SeedDMS_Core_File::removeDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar"));
    }

    /**
     * Test method moveDir()
     *
     * @return void
     */
    public function testMakeAndMoveDir()
    {
        /* Create a directory and put a file into it */
        $ret = SeedDMS_Core_File::makeDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar");
        system('touch '.self::$contentdir.DIRECTORY_SEPARATOR."foobar".DIRECTORY_SEPARATOR."tt");
        /* Rename the directory */
        $ret = SeedDMS_Core_File::moveDir(self::$contentdir.DIRECTORY_SEPARATOR."foobar", self::$contentdir.DIRECTORY_SEPARATOR."bazfoo");
        $this->assertTrue($ret);
        /* The new must exist and the old dir must be disappeared */
        $this->assertTrue(is_dir(self::$contentdir.DIRECTORY_SEPARATOR."bazfoo"));
        $this->assertFalse(is_dir(self::$contentdir.DIRECTORY_SEPARATOR."foobar"));
        $this->assertTrue(SeedDMS_Core_File::removeDir(self::$contentdir.DIRECTORY_SEPARATOR."bazfoo"));
        $this->assertFalse(is_dir(self::$contentdir.DIRECTORY_SEPARATOR."bazfoo"));
    }
}
