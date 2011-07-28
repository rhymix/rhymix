<?php
	/**
	 * @class Expression
	 * @author Corina
	 * @brief Represents an expression used in select/update/insert/delete statements
	 * 
	 *  Examples (expressions are inside double square brackets):
	 *  	select [[columnA]], [[columnB as aliasB]] from tableA
	 *  	update tableA set [[columnA = valueA]] where columnB = something
	 *
	 */

	class Expression {
		var $column_name;
		
		function Expression($column_name){
			$this->column_name = $column_name;
		}
		
		function getColumnName(){
			return $this->column_name;
		}
		
		function show() {
			return false;
		}
		
		function getExpression() {
		}
	}