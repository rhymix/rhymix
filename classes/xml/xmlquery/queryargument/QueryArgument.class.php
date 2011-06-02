<?php 

	class QueryArgument {
		var $argument_name;
		var $argument_validator;
		var $column_name;
		var $operation;
		var $ignoreValue;
		
		function QueryArgument($tag){
			// HACK (this is for backwords compatibility - there are many xml files that have variable names (var) given with .)
			// eg. var = point.memeber_srl (getMemberList query from point module)
			$this->argument_name = str_replace('.', '_',$tag->attrs->var);
			if(!$this->argument_name) $this->ignoreValue = true;
			else $this->ignoreValue = false;
			

			
			if(!$this->argument_name) $this->argument_name = $tag->attrs->name;
			if(!$this->argument_name) $this->argument_name = str_replace('.', '_',$tag->attrs->column);
			
			$name = $tag->attrs->name;
			if(!$name) $name = $tag->attrs->column;
			if(strpos($name, '.') === false) $this->column_name = $name;
			else {
            	list($prefix, $name) = explode('.', $name);
            	$this->column_name = $name;
			}		
			
			if($tag->attrs->operation) $this->operation = $tag->attrs->operation;

			// If we work with ConditionArgument, check if default value exists, and if yes, create argument
			if($this->operation && $tag->attrs->default) $this->ignoreValue = false;			
			
			require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/validator/QueryArgumentValidator.class.php');
			$this->argument_validator = new QueryArgumentValidator($tag, $this);
			
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
			if($this->operation)
				$arg = sprintf("\n$%s_argument = new ConditionArgument('%s', %s, '%s');\n"
							, $this->argument_name
							, $this->argument_name
							, $this->ignoreValue ? 'null' : '$args->'.$this->argument_name
							, $this->operation
							);
							
			else
				$arg = sprintf("\n$%s_argument = new Argument('%s', %s);\n"
							, $this->argument_name
							, $this->argument_name
							, $this->ignoreValue ? 'null' :  '$args->'.$this->argument_name);
							
							
			$arg .= $this->argument_validator->toString();
			
			if($this->operation){
				$arg .= sprintf("$%s_argument->createConditionValue();\n"
					, $this->argument_name
					);
			}
			
			$arg .= sprintf("if(!$%s_argument->isValid()) return $%s_argument->getErrorMessage();\n"
					, $this->argument_name
					, $this->argument_name
					);
			return $arg;
		}
		
	}

?>