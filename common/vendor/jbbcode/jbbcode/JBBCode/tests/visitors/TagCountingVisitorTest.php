<?php

require_once dirname(dirname(__DIR__)) . '/visitors/TagCountingVisitor.php';

class TagCountingVisitorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  JBBCode\visitors\TagCountingVisitor */
    protected $_tagCountingVisitor;

    protected function setUp()
    {
        $this->_tagCountingVisitor = new JBBCode\visitors\TagCountingVisitor();
    }

    public function testVisitTextNode()
    {
        $mock = $this->getMock('JBBCode\TextNode', array('accept'), array(''));
        $mock->expects($this->never())
                ->method('accept');
        $this->_tagCountingVisitor->visitTextNode($mock);
    }

    /**
     * @covers JBBCode\visitors\TagCountingVisitor::getFrequency()
     * @covers JBBCode\visitors\TagCountingVisitor::visitElementNode()
     */
    public function testVisitElementNode()
    {
        $childMock = $this->getMock('JBBCode\ElementNode', array('accept'));
        $childMock->expects($this->once())
                  ->method('accept')
                  ->with($this->equalTo($this->_tagCountingVisitor));

        $mock = $this->getMock('JBBCode\ElementNode', array('getChildren', 'getTagName'));
        $mock->expects($this->once())
             ->method('getChildren')
             ->will($this->returnValue(array(
                 $childMock,
             )));
        $mock->expects($this->once())
            ->method('getTagName')
            ->will($this->returnValue('foo'));

        $this->assertEquals(0, $this->_tagCountingVisitor->getFrequency('foo'));

        $this->_tagCountingVisitor->visitElementNode($mock);
        $this->assertEquals(1, $this->_tagCountingVisitor->getFrequency('foo'));

        $mock = $this->getMock('JBBCode\ElementNode', array('getChildren', 'getTagName'));
        $mock->expects($this->once())
             ->method('getChildren')
             ->will($this->returnValue(array()));
        $mock->expects($this->once())
             ->method('getTagName')
             ->will($this->returnValue('foo'));

        $this->_tagCountingVisitor->visitElementNode($mock);
        $this->assertEquals(2, $this->_tagCountingVisitor->getFrequency('foo'));
    }

    public function testVisitDocumentElement()
    {
        $childMock = $this->getMock('JBBCode\ElementNode', array('accept'));
        $childMock->expects($this->once())
                  ->method('accept')
                  ->with($this->equalTo($this->_tagCountingVisitor));

        $mock = $this->getMock('JBBCode\DocumentElement', array('getChildren'));
        $mock->expects($this->once())
             ->method('getChildren')
             ->will($this->returnValue(array(
                 $childMock,
             )));

        $this->_tagCountingVisitor->visitDocumentElement($mock);
    }
}
