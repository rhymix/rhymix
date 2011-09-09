<?php

    /**
     * @class UpdateColumnTag
     * @author Arnia Software
     * @brief Models the <column> tag inside an XML Query file whose action is 'update'
     *
     **/



	class UpdateColumnTag extends ColumnTag {
		var $argument;

		function UpdateColumnTag($column) {
			parent::ColumnTag($column->attrs->name);
			$dbParser = DB::getParser();
			$this->name = $dbParser->parseColumnName($this->name);
			$this->argument = new QueryArgument($column);
		}

		function getExpressionString(){
			return sprintf('new UpdateExpression(\'%s\', $%s_argument)'
						, $this->name
						, $this->argument->argument_name);
		}

		function getArgument(){
			return $this->argument;
		}
	}

?>