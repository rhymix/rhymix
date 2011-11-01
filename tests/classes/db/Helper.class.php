<?php 

	class Helper {
		static function cleanString($query){
			$query = trim(preg_replace('/\s+/', ' ',$query));
                        $query = preg_replace('/\t+/', '',$query);
			$query = str_replace(" , ", ', ', $query);
                        $query = str_replace(" ,", ',', $query);
			$query = str_replace("( ", '(', $query);
			$query = str_replace(" )", ')', $query);
                        $query = str_replace(array("\r", "\r\n", "\n"), '*', $query);
			$query = strtolower($query);		
			return $query;	
		}              
                
                static function getXmlObject($xml_file){
			$xmlParser = XmlQueryParser::getInstance();
			return $xmlParser->getXmlFileContent($xml_file);
		}
                            
	}

?>