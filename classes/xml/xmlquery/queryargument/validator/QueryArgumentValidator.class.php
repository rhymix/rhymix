<?php
	class QueryArgumentValidator {
		var $argument_name;
		var $default_value;
		var $notnull;
		var $filter;
		var $min_length;
		var $max_length;

		var $validator_string;

		var $argument;

		function QueryArgumentValidator($tag, $argument){
			$this->argument = $argument;
			$this->argument_name = $this->argument->getArgumentName();

			$this->default_value = $tag->attrs->default;
			$this->notnull = $tag->attrs->notnull;
			$this->filter = $tag->attrs->filter;
			$this->min_length = $tag->attrs->min_length;
			$this->max_length = $tag->attrs->max_length;
		}

                function isIgnorable(){
                    if(isset($this->default_value) || isset($this->notnull)) return false;
                    return true;
                }

		function toString(){
			$validator = '';
			if(isset($this->default_value)){
                                $this->default_value = new DefaultValue($this->argument_name, $this->default_value);
                                if($this->default_value->isSequence())
                                        $validator .= '$db = &DB::getInstance(); $sequence = $db->getNextSequence(); ';
                                if($this->default_value->isOperation())
                                        $validator .= sprintf("$%s_argument->setColumnOperation('%s');\n"
                                                , $this->argument_name
                                                , $this->default_value->getOperation()
                                                );
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
				$validator .= sprintf("$%s_argument->checkFilter('%s');\n"
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