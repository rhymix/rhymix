<?php

	/**
	 * @class SelectExpression
	 * @author Arnia Software
	 * @brief Represents an expresion that appears in the select clause
	 *
	 * @remarks
	 * 		$column_name can be:
	 *  		- a table column name
	 *  		- an sql function - like count(*)
	 *	  		- an sql expression - substr(column_name, 1, 8) or score1 + score2
	 *		$column_name is already escaped
	 */

	class SelectExpression extends Expression {
		var $column_alias;

		function SelectExpression($column_name, $alias = NULL){
			parent::Expression($column_name);
			$this->column_alias = $alias;
		}

		function getExpression() {
			return sprintf("%s%s", $this->column_name, $this->column_alias ? " as ".$this->column_alias : "");
		}

		function show() {
			return true;
		}

		function getArgument(){
			return null;
		}

                function isSubquery(){
                    return false;
                }
	}
?>