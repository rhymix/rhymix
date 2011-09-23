<?php
	/**
	 * @class UpdateExpression
	 * @author Arnia Software
	 * @brief
	 *
	 */

	class UpdateExpression extends Expression {
		var $argument;

		function UpdateExpression($column_name, $argument){
			parent::Expression($column_name);
			$this->argument = $argument;
		}

		function getExpression($with_value = true){
			if($with_value)
				return $this->getExpressionWithValue();
			return $this->getExpressionWithoutValue();
		}

		function getExpressionWithValue(){
			$value = $this->argument->getValue();
                        $operation = $this->argument->getColumnOperation();
                        if(isset($operation))
                                return "$this->column_name = $this->column_name $operation $value";
			return "$this->column_name = $value";
		}

		function getExpressionWithoutValue(){
                        $operation = $this->argument->getColumnOperation();
                        if(isset($operation))
                                return "$this->column_name = $this->column_name $operation ?";
			return "$this->column_name = ?";
		}

		function getValue(){
			// TODO Escape value according to column type instead of variable type
			$value = $this->argument->getValue();
			if(!is_numeric($value)) return "'".$value."'";
			return $value;
		}

		function show(){
                        if(!$this->argument) return false;
                        $value = $this->argument->getValue();
			if(!isset($value)) return false;
			return true;
		}

		function getArgument(){
			return $this->argument;
		}
	}


?>