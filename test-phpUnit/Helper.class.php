<?php 

	class Helper {
		static function cleanQuery($query){
			$query = trim(preg_replace('/\s+/', ' ',$query));
			$query = str_replace(" , ", ', ', $query);
			$query = str_replace("( ", '(', $query);
			$query = str_replace(" )", ')', $query);
			$query = strtolower($query);		
			return $query;	
		}
                
                static function getXmlObject($xml_file){
			$xmlParser = XmlQueryParser::getInstance();
			return $xmlParser->getXmlFileContent($xml_file);
		}
                            
	}

?>