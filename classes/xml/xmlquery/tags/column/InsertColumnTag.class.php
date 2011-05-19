<?php 
    /**
     * @class InsertColumnTag
     * @author Arnia Software
     * @brief Models the <column> tag inside an XML Query file whose action is 'insert'
     *
     **/


	class InsertColumnTag extends ColumnTag {
		var $argument;
		
		function InsertColumnTag($column, $dbParser) {
			parent::ColumnTag($column->attrs->name, $dbParser);
			$this->name = $this->dbParser->parseColumnName($this->name);
			require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/QueryArgument.class.php');
			$this->argument = new QueryArgument($column);
		}
		
		function toString(){
			$output_columns = 'array(' . PHP_EOL;
			foreach($this->argument as $argument){
				$output_columns .= $argument->getExpressionString() . PHP_EOL . ',';
			}
			$output_columns = substr($output_columns, 0, -1);
			$output_columns .= ')';	
			return $output_columns;			
		}
		
		function getExpressionString(){
			return sprintf('new InsertExpression(\'%s\', $%s_argument->getValue())'
						, $this->name
						, $this->argument->argument_name);	
		}	
		
		function getArgument(){
			return $this->argument;
		}
		
		function getValidatorString(){
			return $this->argument->getValidatorString();
		}			
		
	}
?>