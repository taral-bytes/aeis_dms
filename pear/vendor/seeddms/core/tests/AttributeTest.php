<?php
/**
 * Implementation of the attribute tests
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
 * Attribute and attribute definition test class
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: @package_version@
 * @link      https://www.seeddms.org
 */
class AttributeTest extends TestCase
{

    /**
     * Create a mock dms object
     *
     * @return SeedDMS_Core_DMS
     */
    protected function getMockDMS() : SeedDMS_Core_DMS
    {
        $db = $this->createMock(SeedDMS_Core_DatabaseAccess::class);
        $db->expects($this->any())
            ->method('getResult')
            ->with($this->stringContains("UPDATE "))
            ->willReturn(true);
        $dms = new SeedDMS_Core_DMS($db, '');
        return $dms;
    }

    /**
     * Create a mock attribute definition object
     *
     * @param int     $type      type of attribute
     * @param boolean $multiple  true if multiple values are allowed
     * @param int     $minvalues minimum number of required values
     * @param int     $maxvalues maximum number of required value
     * @param string  $valueset  list of allowed values separated by the first char
     * @param string  $regex     regular expression the attribute value must match
     *
     * @return SeedDMS_Core_AttributeDefinition
     */
    protected function getAttributeDefinition($type, $multiple=false, $minvalues=0, $maxvalues=0, $valueset='', $regex='')
    {
        $attrdef = new SeedDMS_Core_AttributeDefinition(1, 'foo attrdef', SeedDMS_Core_AttributeDefinition::objtype_folder, $type, $multiple, $minvalues, $maxvalues, $valueset, $regex);
        return $attrdef;
    }

    /**
     * Create a mock attribute object
     *
     * @param SeedDMS_Core_AttributeDefinition $attrdef attribute defintion of attribute
     * @param mixed                            $value   value of attribute
     *
     * @return SeedDMS_Core_Attribute
     */
    static protected function getAttribute($attrdef, $value)
    {
        $folder = new SeedDMS_Core_Folder(1, 'Folder', null, '', '', '', 0, 0, 0);
        $attribute = new SeedDMS_Core_Attribute(1, $folder, $attrdef, $value);
        $attribute->setDMS($attrdef->getDMS());
        return $attribute;
    }

    /**
     * Test getId()
     *
     * @return void
     */
    public function testGetId()
    {
        $attrdef = self::getAttributeDefinition(SeedDMS_Core_AttributeDefinition::type_int);
        $attribute = self::getAttribute($attrdef, '');
        $this->assertEquals(1, $attribute->getId());
    }

    /**
     * Test getValue()
     *
     * @return void
     */
    public function testGetValue()
    {
        $attrdef = self::getAttributeDefinition(SeedDMS_Core_AttributeDefinition::type_int);
        $attribute = self::getAttribute($attrdef, 7);
        $this->assertEquals(7, $attribute->getValue());
    }

    /**
     * Test getValueAsArray()
     *
     * @return void
     */
    public function testGetValueAsArray()
    {
        $attrdef = self::getAttributeDefinition(SeedDMS_Core_AttributeDefinition::type_int);
        $attribute = self::getAttribute($attrdef, 7);
        $this->assertIsArray($attribute->getValueAsArray());
        $this->assertCount(1, $attribute->getValueAsArray());
        $this->assertContains(7, $attribute->getValueAsArray());

        /* Test a multi value integer */
        $attrdef = self::getAttributeDefinition(SeedDMS_Core_AttributeDefinition::type_int, true);
        $attribute = self::getAttribute($attrdef, ',3,4,6');
        $value = $attribute->getValueAsArray();
        $this->assertIsArray($attribute->getValueAsArray());
        $this->assertCount(3, $attribute->getValueAsArray());
        $this->assertContains('6', $attribute->getValueAsArray());
    }

    /**
     * Test setValue()
     *
     * @return void
     */
    public function testSetValue()
    {
        $attrdef = self::getAttributeDefinition(SeedDMS_Core_AttributeDefinition::type_int);
        $attrdef->setDMS(self::getMockDMS());
        $attribute = self::getAttribute($attrdef, 0);
        $this->assertTrue($attribute->setValue(9));
        $this->assertEquals(9, $attribute->getValue());
        /* Setting an array of values for a none multi value attribute will just take the
         * element of the array.
         */
        $this->assertTrue($attribute->setValue([8,9]));
        $this->assertEquals(8, $attribute->getValue());

        $attrdef = self::getAttributeDefinition(SeedDMS_Core_AttributeDefinition::type_int, true);
        $attrdef->setDMS(self::getMockDMS());
        $attribute = self::getAttribute($attrdef, ',3,4,6');
        $attribute->setValue([8,9,10]);
        $this->assertEquals(',8,9,10', $attribute->getValue());
        $this->assertIsArray($attribute->getValueAsArray());
        $this->assertCount(3, $attribute->getValueAsArray());
        $this->assertContains('9', $attribute->getValueAsArray());
    }
}
