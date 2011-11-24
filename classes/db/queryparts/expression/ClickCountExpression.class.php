<?php 

	/**
	 * @class ClickCountExpression
	 * @author Arnia Software
	 * @brief 
	 *
	 */

	class ClickCountExpression extends SelectExpression {
		var $click_count;
	
		function ClickCountExpression($column_name, $alias = NULL, $click_count = false){
			parent::SelectExpression($column_name, $alias);
			
			if(!is_bool($click_count)){
				error_log("Click_count value for $column_name was not boolean", 0);
				$this->click_count = false;
				return;
			}
			$this->click_count = $click_count;
		}
		
		function show() {
			return $this->click_count;
		}
		
		function getExpression(){
			return "$this->column_name = $this->column_name + 1";
		}
	}

?>