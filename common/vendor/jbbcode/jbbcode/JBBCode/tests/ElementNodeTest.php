<?php

class ElementNodeTest extends PHPUnit_Framework_TestCase
{
    /** @var  JBBCode\ElementNode */
    private $_elementNode;

    protected function setUp()
    {
        $this->_elementNode = new JBBCode\ElementNode();
    }

    public function testConstructor()
    {
        $this->assertNull($this->_elementNode->getCodeDefinition());
        $this->assertEmpty($this->_elementNode->getTagName());
        $this->assertEmpty($this->_elementNode->getAttribute());
        $this->assertEmpty($this->_elementNode->getChildren());
        $this->assertEmpty($this->_elementNode->getAsText());
        $this->assertEmpty($this->_elementNode->getAsHTML());
    }

    public function testAccept()
    {
        $mock = $this->getMock('JBBCode\NodeVisitor',
            array('visitDocumentElement', 'visitTextNode', 'visitElementNode'));
        $mock->expects($this->never())
             ->method('visitDocumentElement');
        $mock->expects($this->never())
             ->method('visitTextNode');
        $mock->expects($this->once())
             ->method('visitElementNode')
             ->with($this->equalTo($this->_elementNode));
        $this->_elementNode->accept($mock);
    }

    public function testSetCodeDefinition()
    {
        $mock = $this->getMock('JBBCode\CodeDefinition', array('getTagName'));
        $mock->expects($this->once())
             ->method('getTagName')
            ->will($this->returnValue('foo'));
        $this->_elementNode->setCodeDefinition($mock);
        $this->assertSame($mock, $this->_elementNode->getCodeDefinition());
        $this->assertEquals('foo', $this->_elementNode->getTagName());
    }

    public function testAddChild()
    {
        $mock = $this->getMock('JBBCode\ElementNode', array('setParent'));
        $mock->expects($this->once())
            ->method('setParent')
            ->with($this->equalTo($this->_elementNode));
        $this->_elementNode->addChild($mock);
        $this->assertContains($mock, $this->_elementNode->getChildren());
    }

    public function testIsTextNode()
    {
        $this->assertFalse($this->_elementNode->isTextNode());
    }

    public function testGetAsBBCode()
    {
        $builder = new JBBCode\CodeDefinitionBuilder('foo', 'bar');
        $codeDefinition = $builder->build();
        $this->_elementNode->setCodeDefinition($codeDefinition);
        $this->assertEquals('[foo][/foo]', $this->_elementNode->getAsBBCode());

        $this->_elementNode->setAttribute(array('bar' => 'baz'));
        $this->assertEquals('[foo bar=baz][/foo]', $this->_elementNode->getAsBBCode());

        /** @ticket 55 */
        $this->_elementNode->setAttribute(array(
            'bar' => 'baz',
            'foo' => 'bar'
        ));
        $this->assertEquals('[foo=bar bar=baz][/foo]', $this->_elementNode->getAsBBCode());
    }
}
