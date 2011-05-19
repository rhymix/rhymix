<?php 

	/** 
	 * @class InsertExpression
	 * @author Arnia Software
	 * @brief 
	 *
	 */

	class InsertExpression extends Expression {
		var $value;
		
		function InsertExpression($column_name, $value){
			parent::Expression($column_name);
			$this->value = $value;
		}
		
		function getValue(){
			return $this->value;
		}
		
		function show(){
			if(!isset($this->value)) return false;
			return true;
		}
	}

?>