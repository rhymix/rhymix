<?php 

	/**
	 * @class ConditionTag
	 * @author Corina
	 * @brief Models the <condition> tag inside an XML Query file. Base class. 
	 *
	 */

	class ConditionTag {
		var $operation;
		var $column_name;
		
		var $pipe;
		var $argument_name;
		var $argument;
		var $default_column;
				
		function ConditionTag($condition){
			$this->operation = $condition->attrs->operation;
			$this->pipe = $condition->attrs->pipe;
			$dbParser = XmlQueryParser::getDBParser();
			$this->column_name = $dbParser->parseColumnName($condition->attrs->column);
			
			$isColumnName = strpos($condition->attrs->default, '.');
			
			if($condition->attrs->var || $isColumnName === false){
				require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/QueryArgument.class.php');			
				//$this->argument_name = $condition->attrs->var;
				
				$this->argument = new QueryArgument($condition);	
				$this->argument_name = $this->argument->getArgumentName();			
			}
			//else if($isColumnName === false){
			//	$this->default_column = $condition->attrs->default;
			//}
			else {
				$this->default_column = $dbParser->parseColumnName($condition->attrs->default);	
			}
		}
		
		function setPipe($pipe){
			$this->pipe = $pipe;
		}
		
		function getArgument(){
			return $this->argument;
		}
		
		function getConditionString(){
			return sprintf("new Condition('%s',%s,%s%s)"
									, $this->column_name
									, $this->default_column ? "'" . $this->default_column . "'" :  '$' . $this->argument_name . '_argument'
									, '"'.$this->operation.'"'
									, $this->pipe ? ", '" . $this->pipe . "'" : ''
									);
		}		
	}
?>