<?php 
	/**
	 * StarExpression
	 * Represents the * in 'select * from ...' statements 
	 *
	 * @author Corina
	 * @package /classes/db/queryparts/expression
	 * @version 0.1
	 */
	class StarExpression extends SelectExpression {
		/**
		 * constructor, set the column to asterisk
		 * @return void
		 */
		function StarExpression(){
			parent::SelectExpression("*");
		}
		
		function getArgument(){
			return null;
		}

		function getArguments(){
		    // StarExpression has no arguments
			return array();
		}
	}
?>
