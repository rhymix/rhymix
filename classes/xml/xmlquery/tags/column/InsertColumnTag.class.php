<?php
    /**
     * @class InsertColumnTag
     * @author Arnia Software
     * @brief Models the <column> tag inside an XML Query file whose action is 'insert'
     *
     **/


	class InsertColumnTag extends ColumnTag {
		var $argument;

		function InsertColumnTag($column) {
			parent::ColumnTag($column->attrs->name);
			$dbParser = DB::getParser();
			$this->name = $dbParser->parseColumnName($this->name);
			$this->argument = new QueryArgument($column);
		}

		function getExpressionString(){
			return sprintf('new InsertExpression(\'%s\', $%s_argument)'
						, $this->name
						, $this->argument->argument_name);
		}

		function getArgument(){
			return $this->argument;
		}

	}
?>