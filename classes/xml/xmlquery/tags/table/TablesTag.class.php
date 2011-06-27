<?php 

	class TablesTag {
		var $tables;
		
		function TablesTag($xml_tables){
			$this->tables = array();
			if(!is_array($xml_tables)) $xml_tables = array($xml_tables);
			
			if(count($xml_tables)) require_once(_XE_PATH_.'classes/xml/xmlquery/tags/table/TableTag.class.php');
			
			foreach($xml_tables as $table){
				if($table->name === 'query') $this->tables[] = new QueryTag($table, true);
				else $this->tables[] = new TableTag($table);
			}			
		}
		
		function getTables(){
			return $this->tables;
		}
		
		function toString(){
			$output_tables = 'array(' . PHP_EOL;
			foreach($this->tables as $table){
				$output_tables .= $table->getTableString() . PHP_EOL . ',';
			}
			$output_tables = substr($output_tables, 0, -1);
			$output_tables .= ')';	
			return $output_tables;					
		}
	}
?>