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
        static $dbParser = null;
    	var $db_type;
    	
    	function XmlQueryParser($db_type = NULL){
    		$this->db_type = $db_type;
    	}
    	
    	function &getInstance($db_type = NULL){
    		static $theInstance = null;
    		if(!isset($theInstance)){
    			$theInstance = new XmlQueryParser($db_type);
    		}    		
			return $theInstance;
    	}
    	
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
       function &getDBParser(){
        	if(!$self->dbParser){
        		is_a($this,'XmlQueryParser')?$self=&$this:$self=&XmlQueryParser::getInstance();
        		if(isset($self->db_type))
        			$oDB = &DB::getInstance($self->db_type);
        		else 
        			$oDB = &DB::getInstance();
				$self->dbParser = $oDB->getParser();
        	}
        	return $self->dbParser;
        }
        
        function setDBParser($value){
            $self->dbParser = $value;
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
