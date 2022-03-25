<?php

namespace JBBCode;

/**
 * This Tokenizer is used while constructing the parse tree. The tokenizer
 * handles splitting the input into brackets and miscellaneous text. The
 * parser is then built as a FSM ontop of these possible inputs.
 *
 * @author jbowens
 */
class Tokenizer
{

    /** @var integer[] the positions of tokens found during parsing */
    protected $tokens = array();

    /** @var integer the number of the current token */
    protected $i = -1;

    /**
     * Constructs a tokenizer from the given string. The string will be tokenized
     * upon construction.
     *
     * @param string $str the string to tokenize
     */
    public function __construct($str)
    {
        $strLen = strlen($str);
        $position = 0;

        while ($position < $strLen) {
            $offset = strcspn($str, '[]', $position);
            //Have we hit a single ']' or '['?
            if ($offset == 0) {
                $this->tokens[] = $str[$position];
                $position++;
            } else {
                $this->tokens[] = substr($str, $position, $offset);
                $position += $offset;
            }
        }
    }

    /**
     * Returns true if there is another token in the token stream.
     * @return boolean
     */
    public function hasNext()
    {
        return isset($this->tokens[$this->i + 1]);
    }

    /**
     * Advances the token stream to the next token and returns the new token.
     * @return null|string
     */
    public function next()
    {
        if (!$this->hasNext()) {
            return null;
        } else {
            return $this->tokens[++$this->i];
        }
    }

    /**
     * Retrieves the current token.
     * @return null|string
     */
    public function current()
    {
        if ($this->i < 0) {
            return null;
        } else {
            return $this->tokens[$this->i];
        }
    }

    /**
     * Moves the token stream back a token.
     */
    public function stepBack()
    {
        if ($this->i > -1) {
            $this->i--;
        }
    }

    /**
     * Restarts the tokenizer, returning to the beginning of the token stream.
     */
    public function restart()
    {
        $this->i = -1;
    }

    /**
     * toString method that returns the entire string from the current index on.
     * @return string
     */
    public function toString()
    {
        return implode('', array_slice($this->tokens, $this->i + 1));
    }
}
