<?php
/**
 * Implementation of the attribute definiton tests
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

use PHPUnit\Framework\TestCase;

/**
 * Attribute definition test class
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: @package_version@
 * @link      https://www.seeddms.org
 */
class AttributeDefinitionTest extends TestCase
{

    /**
     * Create a real dms object with a mocked db
     *
     * This mock is only used if \SeedDMS_Core_DatabaseAccess::getResult() is
     * called once. This is the case for all \SeedDMS_Core_AttributeDefinition::setXXX()
     * methods like setName().
     *
     * @return \SeedDMS_Core_DMS
     */
    protected function getDmsWithMockedDb() : \SeedDMS_Core_DMS
    {
        $db = $this->createMock(\SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->once())
            ->method('getResult')
            ->with($this->stringContains("UPDATE "))
            ->willReturn(true);
        $dms = new \SeedDMS_Core_DMS($db, '');
        return $dms;
    }

    /**
     * Create a mocked dms
     *
     * @return \SeedDMS_Core_DMS
     */
    protected function getDmsMock() : \SeedDMS_Core_DMS
    {
        $dms = $this->createMock(\SeedDMS_Core_DMS::class);
        $dms->expects($this->any())
            ->method('getDocument')
            ->with(1)
            ->willReturn(true);
        $dms->expects($this->any())
            ->method('getFolder')
            ->with(1)
            ->willReturn(true);
        $dms->expects($this->any())
            ->method('getUser')
            ->will(
                $this->returnValueMap(
                    array(
                        array(1, new \SeedDMS_Core_User(1, 'admin', 'pass', 'Joe Foo', 'baz@foo.de', 'en_GB', 'bootstrap', 'My comment', \SeedDMS_Core_User::role_admin)),
                        array(2, new \SeedDMS_Core_User(2, 'admin2', 'pass', 'Joe Bar', 'bar@foo.de', 'en_GB', 'bootstrap', 'My comment', \SeedDMS_Core_User::role_admin)),
                        array(3, null)
                    )
                )
            );
        $dms->expects($this->any())
            ->method('getGroup')
            ->will(
                $this->returnValueMap(
                    array(
                        array(1, new \SeedDMS_Core_Group(1, 'admin group 1', 'My comment')),
                        array(2, new \SeedDMS_Core_Group(2, 'admin group 2', 'My comment')),
                        array(3, null)
                    )
                )
            );
        return $dms;
    }

    /**
     * Create a mock attribute definition object
     *
     * @param int     $type      type of attribute
     * @param boolean $multiple  set to true for multi value attributes
     * @param int     $minvalues minimum number of attribute values
     * @param int     $maxvalues maximum number of attribute values
     * @param string  $valueset  list of allowed values separated by the first char
     * @param string  $regex     regular expression that must match the attribute value
     *
     * @return \SeedDMS_Core_AttributeDefinition
     */
    protected function getAttributeDefinition($type, $multiple=false, $minvalues=0, $maxvalues=0, $valueset='', $regex='')
    {
        $attrdef = new \SeedDMS_Core_AttributeDefinition(1, 'foo attr', \SeedDMS_Core_AttributeDefinition::objtype_folder, $type, $multiple, $minvalues, $maxvalues, $valueset, $regex);
        return $attrdef;
    }

    /**
     * Test getId()
     *
     * @return void
     */
    public function testGetId()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        $this->assertEquals(1, $attrdef->getId());
    }

    /**
     * Test getName()
     *
     * @return void
     */
    public function testGetName()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        $this->assertEquals('foo attr', $attrdef->getName());
    }

    /**
     * Test setName()
     *
     * @return void
     */
    public function testSetName()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        /* A mocked dms is needed for updating the database */
        $attrdef->setDMS(self::getDmsWithMockedDb());
        $attrdef->setName('bar attr');
        $this->assertEquals('bar attr', $attrdef->getName());
    }

    /**
     * Test getObjType()
     *
     * @return void
     */
    public function testGetObjType()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::objtype_folder, $attrdef->getObjType());
    }

    /**
     * Test setObjType()
     *
     * @return void
     */
    public function testSetObjType()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        /* A mocked dms is needed for updating the database */
        $attrdef->setDMS(self::getDmsWithMockedDb());
        $attrdef->setObjType(\SeedDMS_Core_AttributeDefinition::objtype_document);
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::objtype_document, $attrdef->getObjType());
    }

    /**
     * Test getType()
     *
     * @return void
     */
    public function testGetType()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::type_int, $attrdef->getType());
    }

    /**
     * Test setType()
     *
     * @return void
     */
    public function testSetType()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        /* A mocked dms is needed for updating the database */
        $attrdef->setDMS(self::getDmsWithMockedDb());
        $attrdef->setType(\SeedDMS_Core_AttributeDefinition::type_string);
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::type_string, $attrdef->getType());
    }

    /**
     * Test getMultipleValues()
     *
     * @return void
     */
    public function testGetMultipleValues()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        $this->assertEquals(false, $attrdef->getMultipleValues());
    }

    /**
     * Test setMultipleValues()
     *
     * @return void
     */
    public function testSetMultipleValues()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        /* A mocked dms is needed for updating the database */
        $attrdef->setDMS(self::getDmsWithMockedDb());
        /* Toogle the current value of multiple values */
        $oldvalue = $attrdef->getMultipleValues();
        $attrdef->setMultipleValues(!$oldvalue);
        $this->assertEquals(!$oldvalue, $attrdef->getMultipleValues());
    }

    /**
     * Test getMinValues()
     *
     * @return void
     */
    public function testGetMinValues()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        $this->assertEquals(0, $attrdef->getMinValues());
    }

    /**
     * Test setMinValues()
     *
     * @return void
     */
    public function testSetMinValues()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        /* A mocked dms is needed for updating the database */
        $attrdef->setDMS(self::getDmsWithMockedDb());
        /* add 5 to value of min values */
        $oldvalue = $attrdef->getMinValues();
        $attrdef->setMinValues($oldvalue+5);
        $this->assertEquals($oldvalue+5, $attrdef->getMinValues());
    }

    /**
     * Test getMaxValues()
     *
     * @return void
     */
    public function testGetMaxValues()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        $this->assertEquals(0, $attrdef->getMaxValues());
    }

    /**
     * Test setMaxValues()
     *
     * @return void
     */
    public function testSetMaxValues()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        /* A mocked dms is needed for updating the database */
        $attrdef->setDMS(self::getDmsWithMockedDb());
        /* add 5 to value of max values */
        $oldvalue = $attrdef->getMaxValues();
        $attrdef->setMaxValues($oldvalue+5);
        $this->assertEquals($oldvalue+5, $attrdef->getMaxValues());
    }

    /**
     * Test getValueSet()
     *
     * @return void
     */
    public function testGetValueSet()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, false, 0, 0, '|foo|bar|baz');
        $this->assertEquals('|foo|bar|baz', $attrdef->getValueSet());
    }

    /**
     * Test getValueSetSeparator()
     *
     * @return void
     */
    public function testGetValueSetSeparator()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, false, 0, 0, '|foo|bar|baz');
        $this->assertEquals('|', $attrdef->getValueSetSeparator());
        /* No value set will return no separator */
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        $this->assertEmpty($attrdef->getValueSetSeparator());
        /* Even a 1 char value set will return no separator */
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, false, 0, 0, '|');
        $this->assertEmpty($attrdef->getValueSetSeparator());
        /* Multiple users or groups always use a ',' as a separator */
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_user, true);
        $this->assertEquals(',', $attrdef->getValueSetSeparator());
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_group, true);
        $this->assertEquals(',', $attrdef->getValueSetSeparator());
    }

    /**
     * Test getValueSetAsArray()
     *
     * @return void
     */
    public function testGetValueSetAsArray()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, false, 0, 0, '|foo|bar|baz  ');
        $valueset = $attrdef->getValueSetAsArray();
        $this->assertIsArray($valueset);
        $this->assertCount(3, $valueset);
        /* value set must contain 'baz' though 'baz  ' was originally set */
        $this->assertContains('baz', $valueset);
        /* No value set will return an empty array */
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string);
        $valueset = $attrdef->getValueSetAsArray();
        $this->assertIsArray($valueset);
        $this->assertEmpty($valueset);
    }

    /**
     * Test getValueSetValue()
     *
     * @return void
     */
    public function testGetValueSetValue()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, false, 0, 0, '|foo|bar|baz  ');
        $this->assertEquals('foo', $attrdef->getValueSetValue(0));
        /* Check if trimming of 'baz  ' worked */
        $this->assertEquals('baz', $attrdef->getValueSetValue(2));
        /* Getting the value of a none existing index returns false */
        $this->assertFalse($attrdef->getValueSetValue(3));

        /* Getting a value from a none existing value set returns false as well */
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string);
        $this->assertFalse($attrdef->getValueSetValue(0));
    }

    /**
     * Test setValueSet()
     *
     * @return void
     */
    public function testSetValueSet()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        /* A mocked dms is needed for updating the database */
        $attrdef->setDMS(self::getDmsWithMockedDb());
        /* add 5 to value of min values */
        $attrdef->setValueSet(' |foo|bar | baz ');
        $this->assertEquals('|foo|bar|baz', $attrdef->getValueSet());
    }

    /**
     * Test getRegex()
     *
     * @return void
     */
    public function testGetRegex()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, false, 0, 0, '', '[0-9].*');
        $this->assertEquals('[0-9].*', $attrdef->getRegex());
    }

    /**
     * Test setRegex()
     *
     * @return void
     */
    public function testSetRegex()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string);
        /* A mocked dms is needed for updating the database */
        $attrdef->setDMS(self::getDmsWithMockedDb());
        /* set a new valid regex */
        $this->assertTrue($attrdef->setRegex(' /[0-9].*/i '));
        $this->assertEquals('/[0-9].*/i', $attrdef->getRegex());
        /* set a new invalid regex will return false and keep the old regex */
        $this->assertFalse($attrdef->setRegex(' /([0-9].*/i '));
        $this->assertEquals('/[0-9].*/i', $attrdef->getRegex());
    }

    /**
     * Test setEmptyRegex()
     *
     * @return void
     */
    public function testSetEmptyRegex()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string);
        /* A mocked dms is needed for updating the database */
        $attrdef->setDMS(self::getDmsWithMockedDb());
        /* set an empty regex */
        $this->assertTrue($attrdef->setRegex(''));
    }

    /**
     * Test parseValue()
     *
     * @return void
     */
    public function testParseValue()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string);
        $value = $attrdef->parseValue('foo');
        $this->assertIsArray($value);
        $this->assertCount(1, $value);
        $this->assertContains('foo', $value);
        /* An attribute definition with multiple values will split the value by the first char */
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, true, 0, 0, '|baz|bar|foo');
        $value = $attrdef->parseValue('|bar|baz');
        $this->assertIsArray($value);
        $this->assertCount(2, $value);
        /* An attribute definition without multiple values, will treat the value as a string */
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, false, 0, 0, '|baz|bar|foo');
        $value = $attrdef->parseValue('|bar|baz');
        $this->assertIsArray($value);
        $this->assertCount(1, $value);
        $this->assertContains('|bar|baz', $value);
    }

    /**
     * Test validate()
     *
     * @TODO Instead of having a lengthy list of assert calls, this could be
     * implemented with data providers for each attribute type
     *
     * @return void
     */
    public function testValidate()
    {
        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string);
        $this->assertTrue($attrdef->validate('')); // even an empty string is valid
        $this->assertTrue($attrdef->validate('foo')); // there is no invalid string

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, false, 0, 0, '', '/[0-9]*S/');
        $this->assertFalse($attrdef->validate('foo')); // doesn't match the regex
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_regex, $attrdef->getValidationError());
        $this->assertTrue($attrdef->validate('S')); // no leading numbers needed
        $this->assertTrue($attrdef->validate('8980S')); // leading numbers are ok

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, false, 0, 0, '|foo|bar|baz', '');
        $this->assertTrue($attrdef->validate('foo')); // is part of value map
        $this->assertFalse($attrdef->validate('foz')); // is not part of value map
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_valueset, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, true, 0, 0, '|foo|bar|baz', '');
        $this->assertTrue($attrdef->validate('foo')); // is part of value map
        $this->assertFalse($attrdef->validate('')); // an empty value cannot be in the valueset
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_valueset, $attrdef->getValidationError());
        $this->assertTrue($attrdef->validate('|foo|baz')); // both are part of value map
        $this->assertFalse($attrdef->validate('|foz|baz')); // 'foz' is not part of value map
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_valueset, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_string, true, 1, 1, '|foo|bar|baz', '');
        $this->assertTrue($attrdef->validate('foo')); // is part of value map
        $this->assertFalse($attrdef->validate('')); // empty string is invalid because of min values = 1
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_min_values, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('|foo|baz')); // both are part of value map, but only value is allowed
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_max_values, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_boolean);
        $this->assertTrue($attrdef->validate(0));
        $this->assertTrue($attrdef->validate(1));
        $this->assertFalse($attrdef->validate(2));
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_boolean, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_int);
        $this->assertTrue($attrdef->validate(0));
        $this->assertTrue($attrdef->validate('0'));
        $this->assertFalse($attrdef->validate('a'));
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_int, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_date);
        $this->assertTrue($attrdef->validate('2021-09-30'));
        $this->assertTrue($attrdef->validate('1968-02-29')); // 1968 was a leap year
        $this->assertTrue($attrdef->validate('2000-02-29')); // 2000 was a leap year
        $this->assertFalse($attrdef->validate('1900-02-29')); // 1900 didn't was a leap year
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_date, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('1970-02-29')); // 1970 didn't was a leap year
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_date, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('2010/02/28')); // This has the wrong format
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_date, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('1970-00-29')); // 0 month is not allowed
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_date, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('1970-01-00')); // 0 day is not allowed
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_date, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_float);
        $this->assertTrue($attrdef->validate('0.567'));
        $this->assertTrue($attrdef->validate('1000'));
        $this->assertTrue($attrdef->validate('1000e3'));
        $this->assertTrue($attrdef->validate('1000e-3'));
        $this->assertTrue($attrdef->validate('-1000'));
        $this->assertTrue($attrdef->validate('+1000'));
        $this->assertFalse($attrdef->validate('0,567')); // wrong decimal point
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_float, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('0.56.7')); // two decimal point
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_float, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_email);
        $this->assertTrue($attrdef->validate('info@seeddms.org'));
        $this->assertTrue($attrdef->validate('info@seeddms.verylongtopleveldomain'));
        $this->assertFalse($attrdef->validate('@seeddms.org')); // no user
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_email, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('info@localhost')); // no tld
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_email, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('info@@seeddms.org')); // double @
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_email, $attrdef->getValidationError());
        $this->assertTrue($attrdef->validate('info@subsubdomain.subdomain.seeddms.org')); // multiple subdomains are ok
        $this->assertFalse($attrdef->validate('info@seeddms..org')); // double . is not allowed
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_email, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('info@s.org')); // 2nd level domain name is too short
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_email, $attrdef->getValidationError());
        $this->assertFalse($attrdef->validate('info@seeddms.o')); // top level domain name is too short
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_email, $attrdef->getValidationError());
        $this->assertTrue($attrdef->validate('info@0123456789-0123456789-0123456789-0123456789-0123456789-01234567.org')); // domain name is 63 chars long, which is the max length
        $this->assertFalse($attrdef->validate('info@0123456789-0123456789-0123456789-0123456789-0123456789-012345678.org')); // domain name is 1 char longer than 63 chars
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_email, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_url);
        $this->assertTrue($attrdef->validate('http://seeddms.org'));
        $this->assertTrue($attrdef->validate('https://seeddms.org'));
        $this->assertFalse($attrdef->validate('ftp://seeddms.org')); // ftp is not allowed
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_url, $attrdef->getValidationError());
        $this->assertTrue($attrdef->validate('http://localhost')); // no tld is just fine
        $this->assertFalse($attrdef->validate('http://localhost.o')); // tld is to short
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_url, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_user);
        $attrdef->setDMS(self::getDmsMock());
        $this->assertTrue($attrdef->validate(1));
        $this->assertFalse($attrdef->validate(3));
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_user, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_group);
        $attrdef->setDMS(self::getDmsMock());
        $this->assertTrue($attrdef->validate('1'));
        $this->assertTrue($attrdef->validate('2'));
        $this->assertFalse($attrdef->validate('3')); // there is no group with id=3
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_group, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_group, true);
        $attrdef->setDMS(self::getDmsMock());
        $this->assertTrue($attrdef->validate(',1,2'));
        $this->assertFalse($attrdef->validate(',1,2,3')); // there is no group with id=3
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_group, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_user);
        $attrdef->setDMS(self::getDmsMock());
        $this->assertTrue($attrdef->validate('1'));
        $this->assertTrue($attrdef->validate('2'));
        $this->assertFalse($attrdef->validate('3')); // there is no user with id=3
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_user, $attrdef->getValidationError());

        $attrdef = self::getAttributeDefinition(\SeedDMS_Core_AttributeDefinition::type_user, true);
        $attrdef->setDMS(self::getDmsMock());
        $this->assertTrue($attrdef->validate(',1,2'));
        $this->assertFalse($attrdef->validate(',1,2,3')); // there is no user with id=3
        $this->assertEquals(\SeedDMS_Core_AttributeDefinition::val_error_user, $attrdef->getValidationError());
    }

}
