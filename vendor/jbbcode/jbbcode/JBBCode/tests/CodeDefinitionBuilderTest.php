<?php

class CodeDefinitionBuilderTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var CodeDefinitionBuilderStub
     */
    private $_builder;

    protected function setUp()
    {
        $this->_builder = new CodeDefinitionBuilderStub('foo', 'bar');
    }

    public function testConstructor()
    {
        $codeDefinition = $this->_builder->build();
        $this->assertInstanceOf('JBBCode\CodeDefinition', $codeDefinition);
        $this->assertEquals('foo', $codeDefinition->getTagName());
        $this->assertEquals('bar', $codeDefinition->getReplacementText());
    }

    public function testSetTagName()
    {
        $this->assertSame($this->_builder, $this->_builder->setTagName('baz'));
        $this->assertEquals('baz', $this->_builder->build()->getTagName());
    }

    public function testSetReplacementText()
    {
        $this->assertSame($this->_builder, $this->_builder->setReplacementText('baz'));
        $this->assertEquals('baz', $this->_builder->build()->getReplacementText());
    }

    public function testSetUseOption()
    {
        $this->assertFalse($this->_builder->build()->usesOption());
        $this->assertSame($this->_builder, $this->_builder->setUseOption(true));
        $this->assertTrue($this->_builder->build()->usesOption());
    }

    public function testSetParseContent()
    {
        $this->assertTrue($this->_builder->build()->parseContent());
        $this->assertSame($this->_builder, $this->_builder->setParseContent(false));
        $this->assertFalse($this->_builder->build()->parseContent());
    }

    public function testSetNestLimit()
    {
        $this->assertEquals(-1, $this->_builder->build()->getNestLimit());
        $this->assertSame($this->_builder, $this->_builder->setNestLimit(1));
        $this->assertEquals(1, $this->_builder->build()->getNestLimit());
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider invalidNestLimitProvider
     */
    public function testSetInvalidNestLimit($limit)
    {
        $this->_builder->setNestLimit($limit);
    }

    public function testSetOptionValidator()
    {
        $this->assertEmpty($this->_builder->getOptionValidators());
        $urlValidator = new JBBCode\validators\UrlValidator();
        $this->assertSame($this->_builder, $this->_builder->setOptionValidator($urlValidator));
        $this->assertArrayHasKey('foo', $this->_builder->getOptionValidators());
        $this->assertContains($urlValidator, $this->_builder->getOptionValidators());

        $otherUrlValidator = new JBBCode\validators\UrlValidator();
        $this->assertSame($this->_builder, $this->_builder->setOptionValidator($otherUrlValidator, 'url'));
        $this->assertArrayHasKey('url', $this->_builder->getOptionValidators());
        $this->assertContains($urlValidator, $this->_builder->getOptionValidators());
        $this->assertContains($otherUrlValidator, $this->_builder->getOptionValidators());
    }

    public function testSetBodyValidator()
    {
        $this->assertNull($this->_builder->getBodyValidator());
        $validator = new JBBCode\validators\UrlValidator();
        $this->assertSame($this->_builder, $this->_builder->setBodyValidator($validator));
        $this->assertSame($validator, $this->_builder->getBodyValidator());
    }

    /**
     * @depends testSetOptionValidator
     */
    public function testRemoveOptionValidator()
    {
        $this->assertSame($this->_builder, $this->_builder->removeOptionValidator());
        $this->assertEmpty($this->_builder->getOptionValidators());
        $this->_builder->setOptionValidator(new JBBCode\validators\UrlValidator());
        $this->assertSame($this->_builder, $this->_builder->removeOptionValidator());
        $this->assertEmpty($this->_builder->getOptionValidators());
    }

    /**
     * @depends testSetBodyValidator
     */
    public function testRemoveBodyValidator()
    {
        $this->assertSame($this->_builder, $this->_builder->removeBodyValidator());
        $this->assertNull($this->_builder->getBodyValidator());
        $this->_builder->setOptionValidator(new JBBCode\validators\UrlValidator());
        $this->assertSame($this->_builder, $this->_builder->removeBodyValidator());
        $this->assertNull($this->_builder->getBodyValidator());
    }

    public function invalidNestLimitProvider()
    {
        return array(
            array(-2),
            array(null),
            array(false),
        );
    }
}

class CodeDefinitionBuilderStub extends \JBBCode\CodeDefinitionBuilder
{

    /**
     * @return \JBBCode\InputValidator
     */
    public function getBodyValidator()
    {
        return $this->bodyValidator;
    }

    /**
     * @return \JBBCode\InputValidator[]
     */
    public function getOptionValidators()
    {
        return $this->optionValidator;
    }
}
