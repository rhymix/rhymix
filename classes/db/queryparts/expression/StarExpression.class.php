<?php 

	/**
	 * @class StarExpression
	 * @author Corina
	 * @brief Represents the * in 'select * from ...' statements 
	 *
	 */

	class StarExpression extends SelectExpression {
		
		function StarExpression(){
			parent::SelectExpression("*");
		}
		
		function getArgument(){
			return null;
		}
	}
?>