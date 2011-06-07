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
		
		function getExpression(){
			return $this->getExpressionWithValue();
		}
		
		function getExpressionWithValue(){
			$value = $this->argument->getValue();
			return "$this->column_name = $value";
		}
		
		function getExpressionWithoutValue(){
			return "$this->column_name = ?";
		}
		
		function getValue(){
			// TODO Escape value according to column type instead of variable type
			$value = $this->argument->getValue();
			if(!is_numeric($value)) return "'".$value."'";
			return $value;
		}
		
		function show(){
			if(!$this->argument->getValue()) return false;
			return true;
		}
	}


?>