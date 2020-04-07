<?php

require_once dirname(dirname(__DIR__)) . '/visitors/SmileyVisitor.php';

class SmileyVisitorTest extends PHPUnit_Framework_TestCase
{
    /** @var \JBBCode\visitors\SmileyVisitor */
    private $_smileyVisitor;

    protected function setUp()
    {
        $this->_smileyVisitor = new \JBBCode\visitors\SmileyVisitor();
    }

    public function testVisitDocumentElement()
    {
        $childMock = $this->getMock('JBBCode\ElementNode', array('accept'));
        $childMock->expects($this->once())
                  ->method('accept')
                  ->with($this->equalTo($this->_smileyVisitor));

        $mock = $this->getMock('JBBCode\DocumentElement', array('getChildren'));
        $mock->expects($this->once())
             ->method('getChildren')
             ->will($this->returnValue(array(
                 $childMock,
             )));

        $this->_smileyVisitor->visitDocumentElement($mock);
    }

    public function testVisitElementNode()
    {
        $builder = new \JBBCode\CodeDefinitionBuilder('foo', 'bar');
        $builder->setParseContent(false);

        $mock = $this->getMock('JBBCode\DocumentElement', array('getChildren', 'getCodeDefinition'));
        $mock->expects($this->never())
            ->method('getChildren');
        $mock->expects($this->once())
            ->method('getCodeDefinition')
            ->will($this->returnValue(
                $builder->build()
            ));
        $this->_smileyVisitor->visitElementNode($mock);

        $childMock = $this->getMock('JBBCode\ElementNode', array('accept', 'parseContent'));
        $childMock->expects($this->once())
                ->method('accept')
                ->with($this->equalTo($this->_smileyVisitor));

        $mock = $this->getMock('JBBCode\DocumentElement', array('getChildren', 'getCodeDefinition'));
        $mock->expects($this->once())
            ->method('getChildren')
            ->will($this->returnValue(array(
                $childMock,
            )));
        $mock->expects($this->once())
            ->method('getCodeDefinition')
            ->will($this->returnValue($builder->setParseContent(true)->build()));
        $this->_smileyVisitor->visitElementNode($mock);
    }

    public function testVisitTextNodeEmpty()
    {
        $textNode = new JBBCode\TextNode('');
        $textNode->accept($this->_smileyVisitor);
        $this->assertEmpty($textNode->getValue());
    }

    /**
     * @param $string
     * @dataProvider smileyProvider()
     */
    public function testVisitTextNode($string)
    {
        $textNode = new JBBCode\TextNode($string);
        $textNode->accept($this->_smileyVisitor);
        $this->assertNotFalse(strpos($textNode->getValue(), '<img src="/smiley.png" alt=":)" />'));
    }

    public function smileyProvider()
    {
        return array(
            array( ':)'),
            array( ':) foo'),
            array( 'foo :)'),
            array( 'foo :) bar'),
        );
    }
}
