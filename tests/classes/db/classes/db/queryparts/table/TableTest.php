<?php

 /**
 * Test class for Table.
 */
class TableTest extends CubridTest
{
    /**
     * @var Table
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Table('"xe_member"', '"m"');
    }

    protected function tearDown()
    {
    }

    public function testToString()
    {
        $this->assertEquals('"xe_member" as "m"', $this->object->toString());
    }

    public function testGetName()
    {
        $this->assertEquals('"xe_member"', $this->object->getName());
    }

    public function testGetAlias()
    {
        $this->assertEquals('"m"', $this->object->getAlias());
    }

    public function testIsJoinTable()
    {
       $this->assertEquals(false, $this->object->isJoinTable());
    }
}
?>
