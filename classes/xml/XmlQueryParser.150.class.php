<?php
    /**
     * @class NewXmlQueryParser
     * @author NHN (developers@xpressengine.com)
     * @brief case to parse XE xml query
     * @version 0.1
     *
     * @todo need to support extend query such as subquery, union
     * @todo include info about column types for parsing user input
     **/

    if(!defined('__XE_LOADED_XML_CLASS__')){
        define('__XE_LOADED_XML_CLASS__', 1);

        require(_XE_PATH_.'classes/xml/xmlquery/tags/query/QueryTag.class.php');

        require(_XE_PATH_.'classes/xml/xmlquery/tags/table/TableTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/table/HintTableTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/table/TablesTag.class.php');

	require(_XE_PATH_.'classes/xml/xmlquery/tags/column/ColumnTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/column/SelectColumnTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/column/InsertColumnTag.class.php');
		require(_XE_PATH_.'classes/xml/xmlquery/tags/column/InsertColumnTagWithoutArgument.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/column/UpdateColumnTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/column/SelectColumnsTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/column/InsertColumnsTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/column/UpdateColumnsTag.class.php');

        require(_XE_PATH_.'classes/xml/xmlquery/tags/condition/ConditionTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/condition/ConditionsTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/condition/JoinConditionsTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/condition/ConditionGroupTag.class.php');

        require(_XE_PATH_.'classes/xml/xmlquery/tags/group/GroupsTag.class.php');

        require(_XE_PATH_.'classes/xml/xmlquery/tags/navigation/NavigationTag.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/tags/navigation/IndexTag.class.php');
	require(_XE_PATH_.'classes/xml/xmlquery/tags/navigation/LimitTag.class.php');

        require(_XE_PATH_.'classes/xml/xmlquery/queryargument/QueryArgument.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/queryargument/SortQueryArgument.class.php');
	require(_XE_PATH_.'classes/xml/xmlquery/queryargument/validator/QueryArgumentValidator.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/queryargument/DefaultValue.class.php');
    }


    class XmlQueryParser extends XmlParser {

        function XmlQueryParser(){
    	}

    	function &getInstance(){
            static $theInstance = null;
            if(!isset($theInstance)){
                    $theInstance = new XmlQueryParser();
            }
            return $theInstance;
    	}

        function &parse_xml_query($query_id, $xml_file, $cache_file)
	{
            // Read xml file
            $xml_obj = $this->getXmlFileContent($xml_file);

            // insert, update, delete, select action
            $action = strtolower($xml_obj->query->attrs->action);
            if(!$action) return;

	    // Write query cache file
            $parser = new QueryParser($xml_obj->query);
            FileHandler::writeFile($cache_file, $parser->toString());

	    return $parser;
        }

        function parse($query_id = NULL, $xml_file = NULL, $cache_file = NULL)
	{
	    $this->parse_xml_query($query_id, $xml_file, $cache_file);
	}

        function getXmlFileContent($xml_file){
			$buff = FileHandler::readFile($xml_file);
            $xml_obj = parent::parse($buff);
            if(!$xml_obj) return;
            unset($buff);
            return $xml_obj;
        }
    }
?>
