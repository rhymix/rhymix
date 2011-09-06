<?php

	class QueryArgument {
		var $argument_name;
                var $variable_name;
		var $argument_validator;
		var $column_name;
		var $operation;

		function QueryArgument($tag){
                        static $number_of_arguments = 0;

			$this->argument_name = $tag->attrs->var;
			if(!$this->argument_name) $this->argument_name = $tag->attrs->name;
			if(!$this->argument_name) $this->argument_name = str_replace('.', '_',$tag->attrs->column);

                        $this->variable_name = $this->argument_name;

                        $number_of_arguments++;
                        $this->argument_name .= $number_of_arguments;

			$name = $tag->attrs->name;
			if(!$name) $name = $tag->attrs->column;
			if(strpos($name, '.') === false) $this->column_name = $name;
			else {
                            list($prefix, $name) = explode('.', $name);
                            $this->column_name = $name;
			}

			if($tag->attrs->operation) $this->operation = $tag->attrs->operation;

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

                function isConditionArgument(){
                    if($this->operation) return true;
                    return false;
                }

		function toString(){
                    if($this->isConditionArgument()){
                            // Instantiation
                            $arg = sprintf("\n$%s_argument = new ConditionArgument('%s', %s, '%s');\n"
                                                    , $this->argument_name
                                                    , $this->argument_name
                                                    , '$args->'.$this->variable_name
                                                    , $this->operation
                                                    );
                            // Call methods to validate argument and ensure default value
                            $arg .= $this->argument_validator->toString();

                            // Prepare condition string
                            $arg .= sprintf("$%s_argument->createConditionValue();\n"
                                    , $this->argument_name
                                    );

                            // Check that argument passed validation, else return
                            $arg .= sprintf("if(!$%s_argument->isValid()) return $%s_argument->getErrorMessage();\n"
                                            , $this->argument_name
                                            , $this->argument_name
                                            );
                    }
                    else {
                            $arg = sprintf("\n$%s_argument = new Argument('%s', %s);\n"
                                                    , $this->argument_name
                                                    , $this->argument_name
                                                    , '$args->'.$this->variable_name);

                            $arg .= $this->argument_validator->toString();

                            $arg .= sprintf("if(!$%s_argument->isValid()) return $%s_argument->getErrorMessage();\n"
                                            , $this->argument_name
                                            , $this->argument_name
                                            );
                    }

                    // If the argument is null, skip it
                    if($this->argument_validator->isIgnorable()){
                        $arg = sprintf("if(isset(%s)) {", '$args->'.$this->variable_name)
                                . $arg
                                . sprintf("} else \n$%s_argument = null;", $this->argument_name);
                    }

                    return $arg;
            }

	}

?>