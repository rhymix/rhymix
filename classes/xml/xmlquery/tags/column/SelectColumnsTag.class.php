<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Models the &lt;columns&gt; tag inside an XML Query file whose action is 'select'
 *
 * @author Corina Udrescu (corina.udrescu@arnia.ro)
 * @package classes\xml\xmlquery\tags\column
 * @version 0.1
 */
class SelectColumnsTag
{

	/**
	 * Column list
	 *
	 * @var array value is SelectColumnTag object
	 */
	var $columns;

	/**
	 * Constructor
	 *
	 * @param $xml_columns_tag
	 * @internal param \Xml_Node_ $xml_columns
	 * @return void
	 */
	function SelectColumnsTag($xml_columns_tag)
	{
		if(!$xml_columns_tag)
		{
			$xml_columns_tag = new Xml_Node_();
		}

		$xml_columns = $xml_columns_tag->column;
		$xml_queries = $xml_columns_tag->query;

		$this->columns = array();

		if(!$xml_columns)
		{
			$this->columns[] = new SelectColumnTag("*");
			return;
		}

		if(!is_array($xml_columns))
		{
			$xml_columns = array($xml_columns);
		}

		foreach($xml_columns as $column)
		{
			$this->columns[] = new SelectColumnTag($column);
		}


		if(!$xml_queries)
		{
			return;
		}

		if(!is_array($xml_queries))
		{
			$xml_queries = array($xml_queries);
		}

		foreach($xml_queries as $column)
		{
			$this->columns[] = new QueryTag($column, TRUE);
		}
	}

	/**
	 * Returns the string to be output in the cache file
	 *
	 * @return string
	 */
	function toString()
	{
		$output_columns = 'array(' . PHP_EOL;
		foreach($this->columns as $column)
		{
			if(is_a($column, 'QueryTag'))
			{
				$output_columns .= $column->toString() . PHP_EOL . ',';
			}
			else
			{
				$output_columns .= $column->getExpressionString() . PHP_EOL . ',';
			}
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
			if(is_a($column, 'QueryTag'))
			{
				$arguments = array_merge($arguments, $column->getArguments());
			}
		}
		return $arguments;
	}

}
/* End of file SelectColumnsTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/column/SelectColumnsTag.class.php */
