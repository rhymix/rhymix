<?php
    /**
     * @class GeneralXmlParser
     * @author haneul (haneul0318@gmail.com)
     * @brief XE에서 쓰는 XmlParser보다 좀더 범용으로 쓸 수 있는 Parser 
     * @version 0.1
     */
    class GeneralXmlParser {
	var $output = array();

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
         * @brief 태그 오픈
         **/
	function _tagOpen($parser, $node_name, $attrs) {
	    $obj->node_name = strtolower($node_name);
	    $obj->attrs = $attrs;
	    $obj->childNodes = array();

	    array_push($this->output, $obj);
	}

	function _tagBody($parser, $body) {
	    //if(!trim($body)) return;
	    $this->output[count($this->output)-1]->body .= $body;
	}

	/**
	 * @brief 태그 닫음
	 **/
	function _tagClosed($parser, $node_name) {
	    $node_name = strtolower($node_name);
	    $cur_obj = array_pop($this->output);
	    $parent_obj = &$this->output[count($this->output)-1];

	    if($parent_obj->childNodes[$node_name]) {
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
