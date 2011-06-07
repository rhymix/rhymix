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

	require_once(_XE_PATH_.'classes/xml/xmlquery/DBParser.class.php');
	require_once(_XE_PATH_.'classes/xml/xmlquery/QueryParser.class.php');

    class XmlQueryParser extends XmlParser {
		var $dbParser;
    	
        function parse($query_id, $xml_file, $cache_file) {
        	// Read xml file
        	$xml_obj = $this->getXmlFileContent($xml_file); 
            
            // insert, update, delete, select action
            $action = strtolower($xml_obj->query->attrs->action);
            if(!$action) return;

			$parser = new QueryParser($xml_obj->query);
			
            FileHandler::writeFile($cache_file, $parser->toString());
        }
        
        // singleton
       /* function &getDBParser(){
        	static $dbParser;
        	if(!$dbParser){
        		//$oDB = &DB::getInstance();
				//$dbParser = $oDB->getParser();
				return new DBParser('"');
        	}
        	return $dbParser;
        }*/
        
        function getXmlFileContent($xml_file){
            $buff = FileHandler::readFile($xml_file);
            $xml_obj = parent::parse($buff);
            if(!$xml_obj) return;
            unset($buff);
            return $xml_obj;        	
        }
        
    	function &getDBParser(){
        	if(!$this->dbParser)
				$this->dbParser = new DBParser('"');
        	return $this->dbParser;
        }
    }
?>
