<?php

/**
 * Test cases for the default bbcode set.
 *
 * @author jbowens
 * @since May 2013
 */
class DefaultCodeDefinitionSetTest extends PHPUnit_Framework_TestCase
{
    public function testGetCodeDefinitions()
    {
        $dcds = new JBBCode\DefaultCodeDefinitionSet();
        $definitions = $dcds->getCodeDefinitions();
        $this->assertInternalType('array', $definitions);

        $parser = new JBBCode\Parser();

        $this->assertFalse($parser->codeExists('b'));
        $this->assertFalse($parser->codeExists('i'));
        $this->assertFalse($parser->codeExists('u'));
        $this->assertFalse($parser->codeExists('url', true));
        $this->assertFalse($parser->codeExists('img'));
        $this->assertFalse($parser->codeExists('img', true));
        $this->assertFalse($parser->codeExists('color', true));

        $parser->addCodeDefinitionSet($dcds);

        $this->assertTrue($parser->codeExists('b'));
        $this->assertTrue($parser->codeExists('i'));
        $this->assertTrue($parser->codeExists('u'));
        $this->assertTrue($parser->codeExists('url', true));
        $this->assertTrue($parser->codeExists('img'));
        $this->assertTrue($parser->codeExists('img', true));
        $this->assertTrue($parser->codeExists('color', true));
    }
}
