<?php

    /**
     * @class UpdateColumnsTag
     * @author Arnia Software
     * @brief Models the <column> tag inside an XML Query file whose action is 'update'
     *
     **/

	class UpdateColumnsTag{
		var $columns;

		function UpdateColumnsTag($xml_columns) {
			$this->columns = array();

			if(!is_array($xml_columns)) $xml_columns = array($xml_columns);

			foreach($xml_columns as $column){
				if($column->name === 'query') $this->columns[] = new QueryTag($column, true);
				else $this->columns[] = new UpdateColumnTag($column);
			}
		}

		function toString(){
			$output_columns = 'array(' . PHP_EOL;
			foreach($this->columns as $column){
				$output_columns .= $column->getExpressionString() . PHP_EOL . ',';
			}
			$output_columns = substr($output_columns, 0, -1);
			$output_columns .= ')';
			return $output_columns;
		}

		function getArguments(){
			$arguments = array();
			foreach($this->columns as $column){
				$arguments[] = $column->getArgument();
			}
			return $arguments;
		}

	}

?>