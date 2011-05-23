<?php 
	require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/DefaultValue.class.php');

	class QueryArgumentValidator {
		var $argument_name;
		var $default_value;
		var $notnull;
		var $filter;
		var $min_length;
		var $max_length;

		var $validator_string;
	
		function QueryArgumentValidator($tag){
			$this->argument_name = $tag->attrs->var;
			if(!$this->argument_name) $this->argument_name = $tag->attrs->name;
			if(!$this->argument_name) $this->argument_name = $tag->attrs->column;
			$this->default_value = $tag->attrs->default;
			$this->notnull = $tag->attrs->notnull;
			$this->filter = $tag->attrs->filter;
			$this->min_length = $tag->attrs->min_length;
			$this->max_length = $tag->attrs->max_length;			
		}
		
		function toString(){
			$validator = '';
			if(isset($this->default_value)){
				$this->default_value = new DefaultValue($this->argument_name, $this->default_value);
				$validator .= sprintf("$%s_argument->ensureDefaultValue(%s);\n"
					, $this->argument_name
					, $this->default_value->toString()
					);
			}			
			if($this->notnull){
				$validator .= sprintf("$%s_argument->checkNotNull();\n"
					, $this->argument_name
					);				
			}
			if($this->filter){
				$validator .= sprintf("$%s_argument->checkFilter(%s);\n"
					, $this->argument_name
					, $this->filter
					);				
			}
			if($this->min_length){
				$validator .= sprintf("$%s_argument->checkMinLength(%s);\n"
					, $this->argument_name
					, $this->min_length
					);				
			}
			if($this->max_length){
				$validator .= sprintf("$%s_argument->checkMaxLength(%s);\n"
					, $this->argument_name
					, $this->max_length
					);				
			}			
			return $validator;
		}
	}

?>