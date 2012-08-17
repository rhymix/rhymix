<?

/*
 * Copyright (c) 2003 Jose Solorzano.  All rights reserved.
 * Redistribution of source must retain this copyright notice.
 */

include ("htmlparser.inc");

/**
 * Class Html2Text. (HtmlParser example.)
 * Converts HTML to ASCII attempting to preserve
 * document structure. 
 * To use, create an instance of Html2Text passing
 * the text to convert and the desired maximum
 * number of characters per line. Then invoke 
 * convert() which returns ASCII text.
 */
class Html2Text {

    // Private fields
  
    var $iCurrentLine = "";
    var $iCurrentWord = "";
    var $iCurrentWordArray;
    var $iCurrentWordIndex;
    var $iInScript;
    var $iListLevel = 0;
    var $iHtmlText;
    var $iMaxColumns;
    var $iHtmlParser;

    // Constants

    var $TOKEN_BR       = 0;
    var $TOKEN_P        = 1;
    var $TOKEN_LI       = 2;
    var $TOKEN_AFTERLI  = 3;
    var $TOKEN_UL       = 4;
    var $TOKEN_ENDUL    = 5;
   
    function Html2Text ($aHtmlText, $aMaxColumns) {
        $this->iHtmlText = $aHtmlText;
        $this->iMaxColumns = $aMaxColumns;
    }

    function convert() {
        $this->iHtmlParser = new HtmlParser($this->iHtmlText);
        $wholeText = "";
        while (($line = $this->getLine()) !== false) {
            $wholeText .= ($line . "\r\n");
        }
        return $wholeText;
    }

    function getLine() {
        while (true) {
            if (!$this->addWordToLine($this->iCurrentWord)) {
                $retvalue = $this->iCurrentLine;
                $this->iCurrentLine = "";
                return $retvalue;
            }                
            $word = $this->getWord();
            if ($word === false) {
                if ($this->iCurrentLine == "") {
                    break;
                }
                $retvalue = $this->iCurrentLine;
                $this->iCurrentLine = "";
                $this->iInText = false;
                $this->iCurrentWord = "";
                return $retvalue;                
            }
        }
        return false;
    }

    function addWordToLine ($word) {
        if ($this->iInScript) {
            return true;
        }
        $prevLine = $this->iCurrentLine;
        if ($word === $this->TOKEN_BR) {
            $this->iCurrentWord = "";
            return false;
        }
        if ($word === $this->TOKEN_P) {
            $this->iCurrentWord = $this->TOKEN_BR;
            return false;
        }
        if ($word === $this->TOKEN_UL) {
            $this->iCurrentWord = $this->TOKEN_BR;
            return false;
        }
        if ($word === $this->TOKEN_ENDUL) {
            $this->iCurrentWord = $this->TOKEN_BR;
            return false;
        }
        if ($word === $this->TOKEN_LI) {
            $this->iCurrentWord = $this->TOKEN_AFTERLI;
            return false;
        }
        $toAdd = $word;
        if ($word === $this->TOKEN_AFTERLI) {
            $toAdd = "";
        }
        if ($prevLine != "") {
            $prevLine .= " ";
        }
        else {
            $prevLine = $this->getIndentation($word === $this->TOKEN_AFTERLI);
        }
        $candidateLine = $prevLine . $toAdd;
        if (strlen ($candidateLine) > $this->iMaxColumns && $prevLine != "") {
            return false;
        }
        $this->iCurrentLine = $candidateLine;
        return true;
    }

    function getWord() {
        while (true) {
            if ($this->iHtmlParser->iNodeType == NODE_TYPE_TEXT) {
                if (!$this->iInText) {
                    $words = $this->splitWords($this->iHtmlParser->iNodeValue);
                    $this->iCurrentWordArray = $words;
                    $this->iCurrentWordIndex = 0;
                    $this->iInText = true;
                }
                if ($this->iCurrentWordIndex < count($this->iCurrentWordArray)) {
                    $this->iCurrentWord = $this->iCurrentWordArray[$this->iCurrentWordIndex++];
                    return $this->iCurrentWord;
                }
                else {
                    $this->iInText = false;
                }
            }
            else if ($this->iHtmlParser->iNodeType == NODE_TYPE_ELEMENT) {
                if (strcasecmp ($this->iHtmlParser->iNodeName, "br") == 0) {
                    $this->iHtmlParser->parse();
                    $this->iCurrentWord = $this->TOKEN_BR;
                    return $this->iCurrentWord;
                }
                else if (strcasecmp ($this->iHtmlParser->iNodeName, "p") == 0) {
                    $this->iHtmlParser->parse();
                    $this->iCurrentWord = $this->TOKEN_P;
                    return $this->iCurrentWord;
                }
                else if (strcasecmp ($this->iHtmlParser->iNodeName, "script") == 0) {
                    $this->iHtmlParser->parse();
                    $this->iCurrentWord = "";
                    $this->iInScript = true;
                    return $this->iCurrentWord;
                }
                else if (strcasecmp ($this->iHtmlParser->iNodeName, "ul") == 0 || strcasecmp ($this->iHtmlParser->iNodeName, "ol") == 0) {
                    $this->iHtmlParser->parse();
                    $this->iCurrentWord = $this->TOKEN_UL;
                    $this->iListLevel++;
                    return $this->iCurrentWord;
                }
                else if (strcasecmp ($this->iHtmlParser->iNodeName, "li") == 0) {
                    $this->iHtmlParser->parse();
                    $this->iCurrentWord = $this->TOKEN_LI;
                    return $this->iCurrentWord;
                }
            }
            else if ($this->iHtmlParser->iNodeType == NODE_TYPE_ENDELEMENT) {
                if (strcasecmp ($this->iHtmlParser->iNodeName, "script") == 0) {
                    $this->iHtmlParser->parse();
                    $this->iCurrentWord = "";
                    $this->iInScript = false;
                    return $this->iCurrentWord;
                }
                else if (strcasecmp ($this->iHtmlParser->iNodeName, "ul") == 0 || strcasecmp ($this->iHtmlParser->iNodeName, "ol") == 0) {
                    $this->iHtmlParser->parse();
                    $this->iCurrentWord = $this->TOKEN_ENDUL;
                    if ($this->iListLevel > 0) {
                        $this->iListLevel--;
                    }
                    return $this->iCurrentWord;
                }
            }
            if (!$this->iHtmlParser->parse()) {
                break;
            }
        }
        return false;
    }

    function splitWords ($text) {
        $words = split ("[ \t\r\n]+", $text);
        for ($idx = 0; $idx < count($words); $idx++) {
            $words[$idx] = $this->htmlDecode($words[$idx]);
        }
        return $words;
    }

    function htmlDecode ($text) {
        // TBD
        return $text;
    } 

    function getIndentation ($hasLI) {
        $indent = "";
        $idx = 0;
        for ($idx = 0; $idx < ($this->iListLevel - 1); $idx++) {
            $indent .= "  ";
        }
        if ($this->iListLevel > 0) {
            $indent = $hasLI ? ($indent . "- ") : ($indent . "  ");
        }
        return $indent;
    }
}
