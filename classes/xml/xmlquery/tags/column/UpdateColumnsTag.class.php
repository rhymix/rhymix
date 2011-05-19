<?php 

    /**
     * @class UpdateColumnsTag
     * @author Arnia Software
     * @brief Models the <column> tag inside an XML Query file whose action is 'update'
     *
     **/

	require_once(_XE_PATH_.'classes/xml/xmlquery/tags/column/ColumnTag.class.php');
	require_once(_XE_PATH_.'classes/xml/xmlquery/tags/column/UpdateColumnTag.class.php');

	class UpdateColumnsTag{
		var $dbParser;
		var $columns;
		
		function UpdateColumnsTag($xml_columns, $dbParser) {
			$this->dbParser = $dbParser;
			
			$this->columns = array();			
			
			if(!$xml_columns) {
				$this->columns[] = new UpdateColumnTag("*", $this->dbParser);
				return;
			}
			
			if(!is_array($xml_columns)) $xml_columns = array($xml_columns); 	
						
			foreach($xml_columns as $column){
				$this->columns[] = new UpdateColumnTag($column, $this->dbParser);
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
			$arguments = array();
			foreach($this->columns as $column){
				$arguments[] = $column->getArgument();
			}
			return $arguments;
		}
		
		function getValidatorString(){
			$validator = '';
			foreach($this->columns as $column){
				$validator .= $column->getValidatorString();
			}
			return $validator;
		}		
	}

?>