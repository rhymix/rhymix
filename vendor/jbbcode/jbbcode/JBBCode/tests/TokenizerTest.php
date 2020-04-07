<?php

/**
 * Test cases testing the functionality of the Tokenizer. The tokenizer
 * is used by the parser to make parsing simpler.
 *
 * @author jbowens
 */
class TokenizerTest extends PHPUnit_Framework_TestCase
{
    public function testEmptyString()
    {
        $tokenizer = new JBBCode\Tokenizer('');
        $this->assertFalse($tokenizer->hasNext());
        $this->assertNull($tokenizer->current());
        $this->assertNull($tokenizer->next());
        $this->assertEmpty($tokenizer->toString());
    }

    public function testHasNext()
    {
        $tokenizer = new JBBCode\Tokenizer('');
        $this->assertFalse($tokenizer->hasNext());

        $tokenizer = new JBBCode\Tokenizer('[');
        $this->assertTrue($tokenizer->hasNext());
        $tokenizer->next();
        $this->assertFalse($tokenizer->hasNext());
    }

    public function testNext()
    {
        $tokenizer = new JBBCode\Tokenizer('[');
        $this->assertEquals('[', $tokenizer->next());
        $this->assertNull($tokenizer->next());
    }

    public function testCurrent()
    {
        $tokenizer = new JBBCode\Tokenizer('[');
        $this->assertNull($tokenizer->current());
        $tokenizer->next();
        $this->assertEquals('[', $tokenizer->current());
    }

    public function testStepBack()
    {
        $tokenizer = new JBBCode\Tokenizer('');
        $tokenizer->stepBack();
        $this->assertFalse($tokenizer->hasNext());

        $tokenizer = new JBBCode\Tokenizer('[');
        $this->assertTrue($tokenizer->hasNext());
        $this->assertEquals('[', $tokenizer->next());
        $this->assertFalse($tokenizer->hasNext());
        $tokenizer->stepBack();
        $this->assertTrue($tokenizer->hasNext());
        $this->assertEquals('[', $tokenizer->next());
    }

    public function testRestart()
    {
        $tokenizer = new JBBCode\Tokenizer('');
        $tokenizer->restart();
        $this->assertFalse($tokenizer->hasNext());

        $tokenizer = new JBBCode\Tokenizer('[');
        $tokenizer->next();
        $tokenizer->restart();
        $this->assertTrue($tokenizer->hasNext());
    }

    public function testToString()
    {
        $tokenizer = new JBBCode\Tokenizer('[');
        $this->assertEquals('[', $tokenizer->toString());
        $tokenizer->next();
        $this->assertEmpty($tokenizer->toString());
    }

    /**
     * @param string[] $tokens
     * @dataProvider tokenProvider()
     */
    public function testTokenize($tokens)
    {
        $string = implode('', $tokens);
        $tokenizer = new JBBCode\Tokenizer($string);
        $this->assertEquals($string, $tokenizer->toString());

        $this->assertTrue($tokenizer->hasNext());
        $this->assertNull($tokenizer->current());

        foreach ($tokens as $token) {
            $this->assertEquals($token, $tokenizer->next());
        }

        $this->assertNull($tokenizer->next());
        $this->assertFalse($tokenizer->hasNext());
    }

    public function tokenProvider()
    {
        return array(
            array(
                array('foo'),
            ),
            array(
                array('foo', '[', 'b', ']', 'bar'),
            ),
            array(
                array('[', 'foo', ']'),
            ),
        );
    }
}
