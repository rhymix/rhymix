<?php
	/**
	 * InsertColumnTagWithoutArgument
	 * Models the <column> tag inside an XML Query file whose action is 'insert-select'
	 *
	 * @author Arnia Software
	 * @package /classes/xml/xmlquery/tags/column
	 * @version 0.1
	 */
	class InsertColumnTagWithoutArgument extends ColumnTag {
		/**
		 * constructor
		 * @param object $column
		 * @return void
		 */
		function InsertColumnTagWithoutArgument($column) {
			parent::ColumnTag($column->attrs->name);
			$dbParser = DB::getParser();
			$this->name = $dbParser->parseColumnName($this->name);
		}

		function getExpressionString(){
			return sprintf('new Expression(\'%s\')', $this->name);
		}

		function getArgument(){
			return null;
		}

	}
?>
