<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Xml_Node_ class
 * Element node or attribute node.
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml
 * @version 0.1
 */
class Xml_Node_
{

	/** In PHP5 this will silence E_STRICT warnings
	 * for undeclared properties.
	 * No effect in PHP4
	 */
	function __get($name)
	{
		return NULL;
	}

}

/**
 * XmlParser class
 * Class parsing a given xmlrpc request and creating a data object
 * @remarks <pre>{ 
 * This class may drops unsupported xml lanuage attributes when multiple language attributes are given.
 * For example, if 'xml:lang='ko, en, ch, jp..' is given in a xml file, only ko will be left ignoring all other language
 * attributes when kor is only supported language. It seems to work fine now but we did not scrutinze any potential side effects,
 * }</pre>
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml
 * @version 0.1
 */
class XeXmlParser
{

	/**
	 * Xml parser
	 * @var resource
	 */
	var $oParser = NULL;

	/**
	 * Input xml
	 * @var string
	 */
	var $input = NULL;

	/**
	 * Output object in array
	 * @var array
	 */
	var $output = array();

	/**
	 * The default language type
	 * @var string
	 */
	var $lang = "en";

	/**
	 * Load a xml file specified by a filename and parse it to Return the resultant data object
	 * @param string $filename a file path of file
	 * @return array Returns a data object containing data extracted from a xml file or NULL if a specified file does not exist
	 */
	function loadXmlFile($filename)
	{
		if(!file_exists($filename))
		{
			return;
		}
		$buff = FileHandler::readFile($filename);

		$oXmlParser = new self();
		return $oXmlParser->parse($buff);
	}

	/**
	 * Parse xml data to extract values from it and construct data object
	 * @param string $input a data buffer containing xml data
	 * @param mixed $arg1 ???
	 * @param mixed $arg2 ???
	 * @return array Returns a resultant data object or NULL in case of error
	 */
	function parse($input = '', $arg1 = NULL, $arg2 = NULL)
	{
		// Save the compile starting time for debugging
		$start = microtime(true);

		$this->lang = Context::getLangType();

		$this->input = $input ? $input : $GLOBALS['HTTP_RAW_POST_DATA'];
		$this->input = str_replace(array('', ''), array('', ''), $this->input);

		// extracts a supported language
		preg_match_all("/xml:lang=\"([^\"].+)\"/i", $this->input, $matches);

		// extracts the supported lanuage when xml:lang is used
		if(count($matches[1]) && $supported_lang = array_unique($matches[1]))
		{
			$tmpLangList = array_flip($supported_lang);
			// if lang of the first log-in user doesn't exist, apply en by default if exists. Otherwise apply the first lang.
			if(!isset($tmpLangList[$this->lang]))
			{
				if(isset($tmpLangList['en']))
				{
					$this->lang = 'en';
				}
				else
				{
					$this->lang = array_shift($supported_lang);
				}
			}
			// uncheck the language if no specific language is set.
		}
		else
		{
			$this->lang = '';
		}

		$this->oParser = xml_parser_create('UTF-8');

		xml_set_object($this->oParser, $this);
		xml_set_element_handler($this->oParser, "_tagOpen", "_tagClosed");
		xml_set_character_data_handler($this->oParser, "_tagBody");

		xml_parse($this->oParser, $this->input);
		xml_parser_free($this->oParser);

		if(!count($this->output))
		{
			return;
		}

		$output = array_shift($this->output);
		// Save compile starting time for debugging
		$GLOBALS['__xmlparse_elapsed__'] += microtime(true) - $start;

		return $output;
	}

	/**
	 * Start element handler.
	 * @param resource $parse an instance of parser
	 * @param string $node_name a name of node
	 * @param array $attrs attributes to be set
	 * @return array
	 */
	function _tagOpen($parser, $node_name, $attrs)
	{
		$obj = new Xml_Node_();
		$obj->node_name = strtolower($node_name);
		$obj->attrs = $this->_arrToAttrsObj($attrs);

		$this->output[] = $obj;
	}

	/**
	 * Character data handler
	 * Variable in the last element of this->output
	 * @param resource $parse an instance of parser
	 * @param string $body a data to be added
	 * @return void
	 */
	function _tagBody($parser, $body)
	{
		//if(!trim($body)) return;
		$this->output[count($this->output) - 1]->body .= $body;
	}

	/**
	 * End element handler
	 * @param resource $parse an instance of parser
	 * @param string $node_name name of xml node
	 * @return void
	 */
	function _tagClosed($parser, $node_name)
	{
		$node_name = strtolower($node_name);
		$cur_obj = array_pop($this->output);
		$parent_obj = &$this->output[count($this->output) - 1];
		if($this->lang && $cur_obj->attrs->{'xml:lang'} && $cur_obj->attrs->{'xml:lang'} != $this->lang)
		{
			return;
		}
		if($this->lang && $parent_obj->{$node_name}->attrs->{'xml:lang'} && $parent_obj->{$node_name}->attrs->{'xml:lang'} != $this->lang)
		{
			return;
		}

		if(isset($parent_obj->{$node_name}))
		{
			$tmp_obj = $parent_obj->{$node_name};
			if(is_array($tmp_obj))
			{
				$parent_obj->{$node_name}[] = $cur_obj;
			}
			else
			{
				$parent_obj->{$node_name} = array($tmp_obj, $cur_obj);
			}
		}
		else
		{
			if(!is_object($parent_obj))
			{
				$parent_obj = (object) $parent_obj;
			}

			$parent_obj->{$node_name} = $cur_obj;
		}
	}

	/**
	 * Method to transfer values in an array to a data object       
	 * @param array $arr data array 
	 * @return Xml_Node_ object
	 */
	function _arrToAttrsObj($arr)
	{
		$output = new Xml_Node_();
		foreach($arr as $key => $val)
		{
			$key = strtolower($key);
			$output->{$key} = $val;
		}
		return $output;
	}

}

/**
 * Alias to XmlParser for backward compatibility.
 */
if (!class_exists('XmlParser'))
{
	class_alias('XeXmlParser', 'XmlParser');
}

/* End of file XmlParser.class.php */
/* Location: ./classes/xml/XmlParser.class.php */
