<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Models the &lt;columns&gt; tag inside an XML Query file whose action is 'insert'
 *
 * @author Corina Udrescu (corina.udrescu@arnia.ro)
 * @package classes\xml\xmlquery\tags\column
 * @version 0.1
 */
class InsertColumnsTag
{

	/**
	 * Column list
	 *
	 * @var array value is InsertColumnTag object
	 */
	var $columns;

	/**
	 * Constructor
	 *
	 * @param array|string $xml_columns
	 * @return void
	 */
	function InsertColumnsTag($xml_columns)
	{
		$this->columns = array();

		if(!$xml_columns)
		{
			return;
		}

		if(!is_array($xml_columns))
		{
			$xml_columns = array($xml_columns);
		}

		foreach($xml_columns as $column)
		{
			if($column->name === 'query')
			{
				$this->columns[] = new QueryTag($column, TRUE);
			}
			else if(!isset($column->attrs->var) && !isset($column->attrs->default))
			{
				$this->columns[] = new InsertColumnTagWithoutArgument($column);
			}
			else
			{
				$this->columns[] = new InsertColumnTag($column);
			}
		}
	}

	/**
	 * InsertColumnTag object to string
	 *
	 * @return string
	 */
	function toString()
	{
		$output_columns = 'array(' . PHP_EOL;
		foreach($this->columns as $column)
		{
			$output_columns .= $column->getExpressionString() . PHP_EOL . ',';
		}
		$output_columns = substr($output_columns, 0, -1);
		$output_columns .= ')';
		return $output_columns;
	}

	/**
	 * Return argument list
	 *
	 * @return array
	 */
	function getArguments()
	{
		$arguments = array();
		foreach($this->columns as $column)
		{
			$arguments[] = $column->getArgument();
		}
		return $arguments;
	}

}
/* End of file InsertColumnsTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/column/InsertColumnsTag.class.php */
