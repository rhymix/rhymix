<?php

class ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var JBBCode\Parser
     */
    private $_parser;

    protected function setUp()
    {
        $this->_parser = new JBBCode\Parser();
        $this->_parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());
    }

    public function testAddCodeDefinition()
    {
        $parser = new JBBCode\Parser();

        $this->assertFalse($parser->codeExists('foo', true));
        $this->assertFalse($parser->codeExists('foo', false));
    }

    public function testAddBBCode()
    {
        $parser = new JBBCode\Parser();

        $this->assertFalse($parser->codeExists('foo', true));
        $this->assertFalse($parser->codeExists('foo', false));

        $this->assertSame($parser, $parser->addBBCode('foo', 'bar', true));

        $this->assertTrue($parser->codeExists('foo', true));
        $this->assertFalse($parser->codeExists('foo', false));

        $this->assertSame($parser, $parser->addBBCode('foo', 'bar', true));

        $this->assertTrue($parser->codeExists('foo', true));
        $this->assertFalse($parser->codeExists('foo', false));

        $this->assertSame($parser, $parser->addBBCode('foo', 'bar', false));

        $this->assertTrue($parser->codeExists('foo', true));
        $this->assertTrue($parser->codeExists('foo', false));
    }

    /**
     * Check for empty strings being the result of empty input
     */
    public function testParseEmptyString()
    {
        $parser = $this->_parser->parse('');
        $this->assertEmpty($parser->getAsBBCode());
        $this->assertEmpty($parser->getAsText());
        $this->assertEmpty($parser->getAsHTML());
    }

    /**
     * Test for artifacts of previous parses
     */
    public function testParseContentCleared()
    {
        $parser = $this->_parser->parse('foo');

        $this->assertEquals('foo', $parser->getAsText());
        $this->assertEquals('foo', $parser->getAsHTML());
        $this->assertEquals('foo', $parser->getAsBBCode());

        $parser->parse('bar');

        $this->assertEquals('bar', $parser->getAsText());
        $this->assertEquals('bar', $parser->getAsHTML());
        $this->assertEquals('bar', $parser->getAsBBCode());
    }

    /**
     * @param string $code
     * @param string[] $expected
     * @dataProvider textCodeProvider
     */
    public function testParse($code, $expected)
    {
        $parser = $this->_parser->parse($code);
        $this->assertEquals($expected['text'], $parser->getAsText());
        $this->assertEquals($expected['html'], $parser->getAsHTML());
        $this->assertEquals($expected['bbcode'], $parser->getAsBBCode());
    }

    public function textCodeProvider()
    {
        return array(
            array(
                'foo',
                array(
                    'text' => 'foo',
                    'html' => 'foo',
                    'bbcode' => 'foo',
                )
            ),
            array(
                '[b]this is bold[/b]',
                array(
                    'text' => 'this is bold',
                    'html' => '<strong>this is bold</strong>',
                    'bbcode' => '[b]this is bold[/b]',
                )
            ),
            array(
                '[b]this is bold',
                array(
                    'text' => 'this is bold',
                    'html' => '<strong>this is bold</strong>',
                    'bbcode' => '[b]this is bold[/b]',
                )
            ),
            array(
                'buffer text [b]this is bold[/b] buffer text',
                array(
                    'text' => 'buffer text this is bold buffer text',
                    'html' => 'buffer text <strong>this is bold</strong> buffer text',
                    'bbcode' => 'buffer text [b]this is bold[/b] buffer text',
                )
            ),
            array(
                'this is some text with [b]bold tags[/b] and [i]italics[/i] and things like [u]that[/u].',
                array(
                    'text' => 'this is some text with bold tags and italics and things like that.',
                    'html' => 'this is some text with <strong>bold tags</strong> and <em>italics</em> and things like <u>that</u>.',
                    'bbcode' => 'this is some text with [b]bold tags[/b] and [i]italics[/i] and things like [u]that[/u].',
                )
            ),
            array(
                'This contains a [url=http://jbbcode.com]url[/url] which uses an option.',
                array(
                    'text' => 'This contains a url which uses an option.',
                    'html' => 'This contains a <a href="http://jbbcode.com">url</a> which uses an option.',
                    'bbcode' => 'This contains a [url=http://jbbcode.com]url[/url] which uses an option.',
                )
            ),
            array(
                'This doesn\'t use the url option [url]http://jbbcode.com[/url].',
                array(
                    'text' => 'This doesn\'t use the url option http://jbbcode.com.',
                    'html' => 'This doesn\'t use the url option <a href="http://jbbcode.com">http://jbbcode.com</a>.',
                    'bbcode' => 'This doesn\'t use the url option [url]http://jbbcode.com[/url].',
                )
            ),
        );
    }
}
