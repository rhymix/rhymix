<?php

class TextNodeTest extends PHPUnit_Framework_TestCase
{
    /** @var  JBBCode\TextNode */
    private $_textNode;

    protected function setUp()
    {
        $this->_textNode = new JBBCode\TextNode('');
    }

    public function accept()
    {
        $mock = $this->getMock('JBBCode\NodeVisitor',
            array('visitDocumentElement', 'visitTextNode', 'visitElementNode'));
        $mock->expects($this->never())
             ->method('visitDocumentElement');
        $mock->expects($this->once())
             ->method('visitTextNode')
             ->with($this->equalTo($this->_textNode));
        $mock->expects($this->never())
             ->method('visitElementNode');
        $this->_textNode->accept($mock);
    }

    public function testIsTextNode()
    {
        $this->assertTrue($this->_textNode->isTextNode());
    }
}
