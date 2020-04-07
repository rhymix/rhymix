<?php

/**
 * Test cases for InputValidators.
 *
 * @author jbowens
 * @since May 2013
 */
class ValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests an invalid url as an option to a url bbcode.
     *
     */
    public function testInvalidOptionUrlBBCode()
    {
        $parser = new JBBCode\Parser();
        $parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());
        $parser->parse('[url=javascript:alert("HACKED!");]click me[/url]');
        $this->assertEquals('[url=javascript:alert("HACKED!");]click me[/url]',
                $parser->getAsHtml());
    }

    /**
     * Tests an invalid url as the body to a url bbcode.
     *
     */
    public function testInvalidBodyUrlBBCode()
    {
        $parser = new JBBCode\Parser();
        $parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());
        $parser->parse('[url]javascript:alert("HACKED!");[/url]');
        $this->assertEquals('[url]javascript:alert("HACKED!");[/url]', $parser->getAsHtml());
    }

    /**
     * Tests a valid url as the body to a url bbcode.
     *
     */
    public function testValidUrlBBCode()
    {
        $parser = new JBBCode\Parser();
        $parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());
        $parser->parse('[url]http://jbbcode.com[/url]');
        $this->assertEquals('<a href="http://jbbcode.com">http://jbbcode.com</a>',
                $parser->getAsHtml());
    }

    /**
     * Tests invalid CSS color values on the CssColorValidator.
     */
    public function testInvalidCssColor()
    {
        $colorValidator = new JBBCode\validators\CssColorValidator();
        $this->assertFalse($colorValidator->validate('" onclick="javascript: alert(\"gotcha!\");'));
        $this->assertFalse($colorValidator->validate('"><marquee scrollamount="100'));
    }

    /**
     * Tests invalid css colors in a color bbcode.
     *
     * @depends testInvalidCssColor
     */
    public function testInvalidColorBBCode()
    {
        $parser = new JBBCode\Parser();
        $parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());
        $parser->parse('[color=" onclick="alert(\'hey ya!\');]click me[/color]');
        $this->assertEquals('[color=" onclick="alert(\'hey ya!\');]click me[/color]',
                $parser->getAsHtml());
    }
}
