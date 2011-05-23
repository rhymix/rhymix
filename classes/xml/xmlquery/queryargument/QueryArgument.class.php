<?php 

	class QueryArgument {
		var $argument_name;
		var $argument_validator;
		var $column_name;
		
		function QueryArgument($tag){
			$this->argument_name = $tag->attrs->var;
			
			$name = $tag->attrs->name;
			if(!$name) $name = $tag->attrs->column;
			if(strpos($name, '.') === false) $this->column_name = $name;
			else {
            	list($prefix, $name) = explode('.', $name);
            	$this->column_name = $name;
			}
			
			if(!$this->argument_name) $this->argument_name = $tag->attrs->name;
			if(!$this->argument_name) $this->argument_name = $tag->attrs->column;
			
			require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/validator/QueryArgumentValidator.class.php');
			$this->argument_validator = new QueryArgumentValidator($tag);
			
		}
		
		function getArgumentName(){
			return $this->argument_name;
		}
		
		function getColumnName(){
			return $this->column_name;
		}
		
		function getValidatorString(){
			return $this->argument_validator->toString();
		}
		
		function toString(){
			$arg = sprintf("\n$%s_argument = new Argument('%s', %s);\n"
						, $this->argument_name
						, $this->argument_name
						, '$args->'.$this->argument_name);
			$arg .= $this->argument_validator->toString();
			$arg .= sprintf("if(!$%s_argument->isValid()) return $%s_argument->getErrorMessage();\n"
					, $this->argument_name
					, $this->argument_name
					);
			return $arg;
		}
		
	}

?>