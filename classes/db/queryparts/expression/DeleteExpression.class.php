<?php 
	/**
	 * @class DeleteExpression
	 * @author Arnia Software
	 * @brief 
	 *
	 */

	// TODO Fix this class 
	class DeleteExpression extends Expression {
		var $value;
		
		function DeleteExpression($column_name, $value){
			parent::Expression($column_name);
			$this->value = $value;
		}
		
		function getExpression(){
			return "$this->column_name = $this->value";
		}
		
		function getValue(){
			// TODO Escape value according to column type instead of variable type
			if(!is_numeric($this->value)) return "'".$this->value."'";
			return $this->value;
		}
		
		function show(){
			if(!$this->value) return false;
			return true;
		}
	}


?>