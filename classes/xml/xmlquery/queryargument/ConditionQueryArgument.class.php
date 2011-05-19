<?php 
	class ConditionQueryArgument extends QueryArgument{
		var $argument_name;
		var $argument_validator;
		var $column_name;
		
		function ConditionQueryArgument($tag){
			$this->argument_name = $tag->attrs->var;
			
			$name = $tag->attrs->column;
			if(strpos($name, '.') === false) $this->column_name = $name;
			else {
            	list($prefix, $name) = explode('.', $name);
            	$this->column_name = $name;
			}			
			
			require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/validator/QueryArgumentValidator.class.php');
			require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/validator/ConditionQueryArgumentValidator.class.php');
			$this->argument_validator = new ConditionQueryArgumentValidator($tag);
		}	
		
		function getColumnName(){
			return $this->column_name;
		}
	}
?>