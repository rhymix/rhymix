<?php
	/**
	 * UpdateColumnsTag
	 * Models the <column> tag inside an XML Query file whose action is 'update'
	 *
	 * @author Arnia Software
	 * @package /classes/xml/xmlquery/tags/column
	 * @version 0.1
	 */
	class UpdateColumnsTag{
		/**
		 * Column list
		 * @var array value is UpdateColumnTag object
		 */
		var $columns;

		/**
		 * constructor
		 * @param array|string $xml_columns
		 * @return void
		 */
		function UpdateColumnsTag($xml_columns) {
			$this->columns = array();

			if(!is_array($xml_columns)) $xml_columns = array($xml_columns);

			foreach($xml_columns as $column){
				if($column->name === 'query') $this->columns[] = new QueryTag($column, true);
				else $this->columns[] = new UpdateColumnTag($column);
			}
		}

		/**
		 * UpdateColumnTag object to string
		 * @return string
		 */
		function toString(){
			$output_columns = 'array(' . PHP_EOL;
			foreach($this->columns as $column){
				$output_columns .= $column->getExpressionString() . PHP_EOL . ',';
			}
			$output_columns = substr($output_columns, 0, -1);
			$output_columns .= ')';
			return $output_columns;
		}

		/**
		 * Return argument list
		 * @return array
		 */
		function getArguments(){
			$arguments = array();
			foreach($this->columns as $column){
				$arguments[] = $column->getArgument();
			}
			return $arguments;
		}

	}

?>
