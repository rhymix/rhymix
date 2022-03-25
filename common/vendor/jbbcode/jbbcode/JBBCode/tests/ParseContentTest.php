<?php

/**
 * Test cases for the code definition parameter that disallows parsing
 * of an element's content.
 *
 * @author jbowens
 */
class ParseContentTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var JBBCode\Parser
     */
    private $_parser;

    protected function setUp()
    {
        $this->_parser = new JBBCode\Parser();
        $this->_parser->addCodeDefinitionSet(new JBBcode\DefaultCodeDefinitionSet());
    }

    /**
     * Tests that when a bbcode is created with parseContent = false,
     * its contents actually are not parsed.
     */
    public function testSimpleNoParsing()
    {
        $this->_parser->addBBCode('verbatim', '{param}', false, false);

        $this->_parser->parse('[verbatim]plain text[/verbatim]');
        $this->assertEquals('plain text', $this->_parser->getAsHtml());

        $this->_parser->parse('[verbatim][b]bold[/b][/verbatim]');
        $this->assertEquals('[b]bold[/b]', $this->_parser->getAsHtml());
    }

    public function testNoParsingWithBufferText()
    {
        $this->_parser->addBBCode('verbatim', '{param}', false, false);

        $this->_parser->parse('buffer text[verbatim]buffer text[b]bold[/b]buffer text[/verbatim]buffer text');
        $this->assertEquals('buffer textbuffer text[b]bold[/b]buffer textbuffer text', $this->_parser->getAsHtml());
    }

    /**
     * Tests that when a tag is not closed within an unparseable tag,
     * the BBCode output does not automatically close that tag (because
     * the contents were not parsed).
     */
    public function testUnclosedTag()
    {
        $this->_parser->addBBCode('verbatim', '{param}', false, false);

        $this->_parser->parse('[verbatim]i wonder [b]what will happen[/verbatim]');
        $this->assertEquals('i wonder [b]what will happen', $this->_parser->getAsHtml());
        $this->assertEquals('[verbatim]i wonder [b]what will happen[/verbatim]', $this->_parser->getAsBBCode());
    }

    /**
     * Tests that an unclosed tag with parseContent = false ends cleanly.
     */
    public function testUnclosedVerbatimTag()
    {
        $this->_parser->addBBCode('verbatim', '{param}', false, false);

        $this->_parser->parse('[verbatim]yo this [b]text should not be bold[/b]');
        $this->assertEquals('yo this [b]text should not be bold[/b]', $this->_parser->getAsHtml());
    }

    /**
     * Tests a malformed closing tag for a verbatim block.
     */
    public function testMalformedVerbatimClosingTag()
    {
        $this->_parser->addBBCode('verbatim', '{param}', false, false);
        $this->_parser->parse('[verbatim]yo this [b]text should not be bold[/b][/verbatim');
        $this->assertEquals('yo this [b]text should not be bold[/b][/verbatim', $this->_parser->getAsHtml());
    }

    /**
     * Tests an immediate end after a verbatim.
     */
    public function testVerbatimThenEof()
    {
        $parser = new JBBCode\Parser();
        $parser->addBBCode('verbatim', '{param}', false, false);
        $parser->parse('[verbatim]');
        $this->assertEquals('', $parser->getAsHtml());
    }
}
