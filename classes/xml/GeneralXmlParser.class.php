<?php
    /**
     * @class GeneralXmlParser
     * @author haneul (haneul0318@gmail.com)
     * @brief Generic XML parser for XE
     * @version 0.1
     */
    class GeneralXmlParser {
	var $output = array();

    /**
    * @brief parse a given input to product a object containing parse values.
    * @param[in] $input data to be parsed
    * @return Returns an object containing parsed values or NULL in case of failure
    */
	function parse($input = '') {
	    $oParser = xml_parser_create('UTF-8');
	    xml_set_object($oParser, $this);
	    xml_set_element_handler($oParser, "_tagOpen", "_tagClosed");
	    xml_set_character_data_handler($oParser, "_tagBody");

	    xml_parse($oParser, $input);
	    xml_parser_free($oParser);

	    if(!count($this->output)) return;
	    $this->output = array_shift($this->output);

	    return $this->output;
	}

    /**
    * @brief start element handler
    * @param[in] $parse an instance of parser
    * @param[in] $node_name a name of node
    * @param[in] $attrs attributes to be set
    */
	function _tagOpen($parser, $node_name, $attrs) {
	    $obj->node_name = strtolower($node_name);
	    $obj->attrs = $attrs;
	    $obj->childNodes = array();

	    array_push($this->output, $obj);
	}

    /**
    * @brief character data handler
    *  variable in the last element of this->output
    * @param[in] $parse an instance of parser
    * @param[in] $body a data to be added
    */
	function _tagBody($parser, $body) {
	    //if(!trim($body)) return;
	    $this->output[count($this->output)-1]->body .= $body;
	}


    /**
    * @brief end element handler
    * @param[in] $parse an instance of parser
    * @param[in] $node_name name of xml node
    */
	function _tagClosed($parser, $node_name) {
	    $node_name = strtolower($node_name);
	    $cur_obj = array_pop($this->output);
	    $parent_obj = &$this->output[count($this->output)-1];

	    if($parent_obj->childNodes[$node_name]) 
        {
    		$tmp_obj = $parent_obj->childNodes[$node_name];
    		if(is_array($tmp_obj)) {
    		    array_push($parent_obj->childNodes[$node_name], $cur_obj);
    		} else {
    		    $parent_obj->childNodes[$node_name] = array();
    		    array_push($parent_obj->childNodes[$node_name], $tmp_obj);
    		    array_push($parent_obj->childNodes[$node_name], $cur_obj);
    		}
   	    } else {
    		$parent_obj->childNodes[$node_name] = $cur_obj;
	    }
	}

    }
?>
