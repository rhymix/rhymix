<?php 

	/**
	 * @class TablesTag
	 * @author Arnia Sowftare
	 * @brief Models the <tables> tag inside an XML Query file
	 *
         * @abstract
         *   Example
         *      <tables>
         *          <table name="documents" alias="doc" />
         *      </tables>
         *   Attributes
         *      None.
         *   Children
         *      Can have children of type <table> or <query>
	 */

	class TablesTag {
		var $tables;
		
		function TablesTag($xml_tables_tag){             
                    $xml_tables = $xml_tables_tag->table;
                    $xml_queries = $xml_tables_tag->query;
                    
                    $this->tables = array();
                    

                    if($xml_tables){
                        if(!is_array($xml_tables)) $xml_tables = array($xml_tables);

                        if(count($xml_tables)) require_once(_XE_PATH_.'classes/xml/xmlquery/tags/table/TableTag.class.php');

                        foreach($xml_tables as $table){
                                $this->tables[] = new TableTag($table);
                        }			
                    }
                    if(!$xml_queries) return;
                    if(!is_array($xml_queries)) $xml_queries = array($xml_queries);

                    foreach($xml_queries as $table){
                            $this->tables[] = new QueryTag($table, true);
                    }			                        
		}
		
		function getTables(){
			return $this->tables;
		}
		
		function toString(){
			$output_tables = 'array(' . PHP_EOL;
			foreach($this->tables as $table){                            
				if(is_a($table, 'QueryTag'))
					$output_tables .= $table->toString() . PHP_EOL . ',';
				else
					$output_tables .= $table->getTableString() . PHP_EOL . ',';				
			}
			$output_tables = substr($output_tables, 0, -1);
			$output_tables .= ')';	
			return $output_tables;					
		}
	}
?>