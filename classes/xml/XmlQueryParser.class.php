<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * File containing the XE 1.5 XmlQueryParserClass
 */
if(!defined('__XE_LOADED_XML_CLASS__'))
{
	define('__XE_LOADED_XML_CLASS__', 1);

	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/query/QueryTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/table/TableTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/table/HintTableTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/table/TablesTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/column/ColumnTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/column/SelectColumnTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/column/InsertColumnTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/column/InsertColumnTagWithoutArgument.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/column/UpdateColumnTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/column/SelectColumnsTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/column/InsertColumnsTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/column/UpdateColumnsTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/condition/ConditionTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/condition/ConditionsTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/condition/JoinConditionsTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/condition/ConditionGroupTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/group/GroupsTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/navigation/NavigationTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/navigation/IndexTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/tags/navigation/LimitTag.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/queryargument/QueryArgument.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/queryargument/SortQueryArgument.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/queryargument/validator/QueryArgumentValidator.class.php');
	require(_XE_PATH_ . 'classes/xml/xmlquery/queryargument/DefaultValue.class.php');
}

/**
 * New XmlQueryParser class  <br />
 * Parses XE XML query files
 *
 * @author Corina Udrescu (corina.udrescu@arnia.ro)
 * @package classes\xml
 * @version 0.1
 */
class XmlQueryParser extends XmlParser
{

	/**
	 * constructor
	 * @return void
	 */
	function XmlQueryParser()
	{

	}

	/**
	 * Create XmlQueryParser instance for Singleton
	 *
	 * @return XmlQueryParser object
	 */
	function &getInstance()
	{
		static $theInstance = NULL;
		if(!isset($theInstance))
		{
			$theInstance = new XmlQueryParser();
		}
		return $theInstance;
	}

	/**
	 * Parses an XML query file
	 *
	 * 1. Read xml file<br />
	 * 2. Check the action<br />
	 * 3. Parse and write cache file <br />
	 *
	 * @param $query_id
	 * @param $xml_file
	 * @param $cache_file
	 *
	 * @return QueryParser object
	 */
	function &parse_xml_query($query_id, $xml_file, $cache_file)
	{
		// Read xml file
		$xml_obj = $this->getXmlFileContent($xml_file);

		// insert, update, delete, select action
		$action = strtolower($xml_obj->query->attrs->action);
		if(!$action)
		{
			return;
		}

		// Write query cache file
		$parser = new QueryParser($xml_obj->query);
		FileHandler::writeFile($cache_file, $parser->toString());

		return $parser;
	}

	/**
	 * Override for parent "parse" method
	 *
	 * @param null $query_id
	 * @param null $xml_file
	 * @param null $cache_file
	 *
	 * @return void
	 */
	function parse($query_id = NULL, $xml_file = NULL, $cache_file = NULL)
	{
		$this->parse_xml_query($query_id, $xml_file, $cache_file);
	}

	/**
	 * Returns XML file contents as an object
	 * or NULL in case of error
	 *
	 * @param $xml_file
	 * @return array|NULL
	 */
	function getXmlFileContent($xml_file)
	{
		$buff = FileHandler::readFile($xml_file);
		$xml_obj = parent::parse($buff);
		if(!$xml_obj)
		{
			return;
		}
		unset($buff);
		return $xml_obj;
	}

}
/* End of file XmlQueryParser.class.php */
/* Location: ./classes/xml/XmlQueryParser.class.php */
