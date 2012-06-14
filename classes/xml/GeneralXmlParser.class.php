<?php
/**
 * GeneralXmlParser class
 * Generic XML parser for XE
 * @author NHN (developers@xpressengine.com)
 * @package /classes/xml
 * @version 0.1
 */
class GeneralXmlParser {
	/**
	 * result of parse
	 * @var array
	 */
	var $output = array();

	/**
	* Parse a given input to product a object containing parse values.
	* @param string $input data to be parsed
	* @return array|NULL Returns an object containing parsed values or NULL in case of failure
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
	* Start element handler
	* @param resource $parser an instance of parser
	* @param string $node_name a name of node
	* @param array $attrs attributes to be set
	* @return void
	*/
	function _tagOpen($parser, $node_name, $attrs) {
	    $obj->node_name = strtolower($node_name);
	    $obj->attrs = $attrs;
	    $obj->childNodes = array();

	    array_push($this->output, $obj);
	}

	/**
	* Character data handler
	* Variable in the last element of this->output
	* @param resource $parse an instance of parser
	* @param string $body a data to be added
	* @return void
	*/
	function _tagBody($parser, $body) {
	    //if(!trim($body)) return;
	    $this->output[count($this->output)-1]->body .= $body;
	}


	/**
	* End element handler
	* @param resource $parse an instance of parser
	* @param string $node_name name of xml node
	* @return void
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
