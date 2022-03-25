<?php

namespace JBBCode;

class DocumentElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentElement
     */
    private $_documentElement;

    protected function setUp()
    {
        $this->_documentElement = new DocumentElement();
    }

    public function testGetTagName()
    {
        $this->assertEquals('Document', $this->_documentElement->getTagName());
    }

    public function testGetAsText()
    {
        $this->assertEmpty($this->_documentElement->getAsText());
        $mock = $this->getMock('JBBCode\ElementNode', array('getAsText'));
        $mock->expects($this->once())
             ->method('getAsText')
             ->will($this->returnValue('foo'));
        $this->_documentElement->addChild($mock);
        $this->assertEquals('foo', $this->_documentElement->getAsText());
    }

    public function testGetAsHTML()
    {
        $this->assertEmpty($this->_documentElement->getAsHTML());
        $mock = $this->getMock('JBBCode\ElementNode', array('getAsHTML'));
        $mock->expects($this->once())
             ->method('getAsHTML')
             ->will($this->returnValue('<strong>foo</strong>'));
        $this->_documentElement->addChild($mock);
        $this->assertEquals('<strong>foo</strong>', $this->_documentElement->getAsHTML());
    }

    public function testGetAsBBCode()
    {
        $this->assertEmpty($this->_documentElement->getAsBBCode());
        $mock = $this->getMock('JBBCode\ElementNode', array('getAsBBCOde'));
        $mock->expects($this->once())
             ->method('getAsBBCode')
             ->will($this->returnValue('[b]foo[/b]'));
        $this->_documentElement->addChild($mock);
        $this->assertEquals('[b]foo[/b]', $this->_documentElement->getAsBBCode());
    }

    public function testAccept()
    {
        $mock = $this->getMock('JBBCode\NodeVisitor',
            array('visitDocumentElement', 'visitTextNode', 'visitElementNode'));
        $mock->expects($this->once())
             ->method('visitDocumentElement')
             ->with($this->equalTo($this->_documentElement));
        $mock->expects($this->never())
             ->method('visitTextNode');
        $mock->expects($this->never())
             ->method('visitElementNode');
        $this->_documentElement->accept($mock);
    }

    public function testIsTextNode()
    {
        $this->assertFalse($this->_documentElement->isTextNode());
    }
}
