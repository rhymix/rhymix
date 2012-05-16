<?php
    /**
     * @class InsertColumnTagWithoutArgument
     * @author Arnia Software
     * @brief Models the <column> tag inside an XML Query file whose action is 'insert-select'
     *
     **/

	class InsertColumnTagWithoutArgument extends ColumnTag {

		function InsertColumnTagWithoutArgument($column) {
			parent::ColumnTag($column->attrs->name);
			$dbParser = DB::getParser();
			$this->name = $dbParser->parseColumnName($this->name);
		}

		function getExpressionString(){
			var_dump($this->name);
			return sprintf('new Expression(\'%s\')', $this->name);
		}

		function getArgument(){
			return null;
		}

	}
?>