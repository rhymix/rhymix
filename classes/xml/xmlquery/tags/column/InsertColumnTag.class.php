<?php
	/**
	 * InsertColumnTag
	 * Models the <column> tag inside an XML Query file whose action is 'insert'
	 *
	 * @author Arnia Software
	 * @package /classes/xml/xmlquery/tags/column
	 * @version 0.1
	 */
	class InsertColumnTag extends ColumnTag {
		/**
		 * argument
		 * @var QueryArgument object
		 */
		var $argument;

		/**
		 * constructor
		 * @param object $column
		 * @return void
		 */
		function InsertColumnTag($column) {
			parent::ColumnTag($column->attrs->name);
			$dbParser = DB::getParser();
			$this->name = $dbParser->parseColumnName($this->name);
			$this->argument = new QueryArgument($column);
		}

		function getExpressionString(){
			return sprintf('new InsertExpression(\'%s\', ${\'%s_argument\'})'
						, $this->name
						, $this->argument->argument_name);
		}

		function getArgument(){
			return $this->argument;
		}

	}
?>
