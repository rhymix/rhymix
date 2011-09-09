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

                var $query;
		function ConditionTag($condition){
			$this->operation = $condition->attrs->operation;
			$this->pipe = $condition->attrs->pipe;
			$dbParser = DB::getParser();
			$this->column_name = $dbParser->parseColumnName($condition->attrs->column);

			$isColumnName = strpos($condition->attrs->default, '.');
			$isColumnName = $isColumnName || strpos($condition->attrs->var, '.');

                        if($condition->node_name == 'query'){
                                $this->query = new QueryTag($condition, true);
                                $this->default_column = $this->query->toString();
                        }
			else if(($condition->attrs->var && !$isColumnName) || $isColumnName === false){
				$this->argument = new QueryArgument($condition);
				$this->argument_name = $this->argument->getArgumentName();
			}
			else {
                            if($condition->attrs->default)
				$this->default_column = "'" .  $dbParser->parseColumnName($condition->attrs->default)  . "'" ;
                            else
                                $this->default_column = "'" .  $dbParser->parseColumnName($condition->attrs->var)  . "'" ;
			}
		}

		function setPipe($pipe){
			$this->pipe = $pipe;
		}

		function getArguments(){
                    $arguments = array();
                    if($this->query)
                        $arguments = array_merge($arguments, $this->query->getArguments());
                    if($this->argument)
                        $arguments[] = $this->argument;
                    return $arguments;
		}

		function getConditionString(){
                        if($this->query){
                            return sprintf("new ConditionSubquery('%s',%s,%s%s)"
                                            , $this->column_name
                                            , $this->default_column
                                            , '"'.$this->operation.'"'
                                            , $this->pipe ? ", '" . $this->pipe . "'" : ''
                                            );
                        }
                        else if($this->default_column){
                            return sprintf("new ConditionWithoutArgument('%s',%s,%s%s)"
                                            , $this->column_name
                                            , $this->default_column
                                            , '"'.$this->operation.'"'
                                            , $this->pipe ? ", '" . $this->pipe . "'" : ''
                                            );
                        }
                        else{
                            return sprintf("new ConditionWithArgument('%s',%s,%s%s)"
                                            , $this->column_name
                                            , '$' . $this->argument_name . '_argument'
                                            , '"'.$this->operation.'"'
                                            , $this->pipe ? ", '" . $this->pipe . "'" : ''
                                            );
                        }
		}
	}
?>