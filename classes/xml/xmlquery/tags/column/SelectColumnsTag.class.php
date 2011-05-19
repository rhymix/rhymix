<?php
	
	require_once(_XE_PATH_.'classes/xml/xmlquery/tags/column/ColumnTag.class.php');
	require_once(_XE_PATH_.'classes/xml/xmlquery/tags/column/SelectColumnTag.class.php');
	
	class SelectColumnsTag {
		var $dbParser;
		var $columns;
		
		function SelectColumnsTag($xml_columns, $dbParser){
			$this->dbParser = $dbParser;
			
			$this->columns = array();			
			
			if(!$xml_columns) {
				$this->columns[] = new SelectColumnTag("*", $this->dbParser);
				return;
			}
			
			if(!is_array($xml_columns)) $xml_columns = array($xml_columns); 	
						
			foreach($xml_columns as $column){
				$this->columns[] = new SelectColumnTag($column, $this->dbParser);
			}			
		}
		
		function toString(){
			$output_columns = 'array(' . PHP_EOL;
			foreach($this->columns as $column){
				$output_columns .= $column->getExpressionString() . PHP_EOL . ',';
			}
			$output_columns = substr($output_columns, 0, -1);
			$output_columns .= ')';	
			return $output_columns;			
		}
		
		function getArguments(){
			return array();
		}
		
		function getValidatorString(){
			return '';
		}		
	}
?>	
