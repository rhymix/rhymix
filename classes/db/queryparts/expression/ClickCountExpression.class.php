<?php 
	/**
	 * ClickCountExpression
	 * @author Arnia Software
	 * @package /classes/db/queryparts/expression
	 * @version 0.1
	 */
	class ClickCountExpression extends SelectExpression {
		/**
		 * click count
		 * @var boolean
		 */
		var $click_count;
	
		/**
		 * constructor
		 * @param string $column_name
		 * @param string $alias
		 * @param boolean $click_count
		 * @return void
		 */
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
		
		/**
		 * Return column expression, ex) column = column + 1
		 * @return string
		 */
		function getExpression(){
			return "$this->column_name = $this->column_name + 1";
		}
	}

?>
