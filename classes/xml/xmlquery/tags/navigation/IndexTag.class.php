<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * IndexTag class
 *
 * @author Arnia Software
 * @package /classes/xml/xmlquery/tags/navigation
 * @version 0.1
 */
class IndexTag
{

	/**
	 * argument name
	 * @var string
	 */
	var $argument_name;

	/**
	 * QueryArgument object
	 * @var QueryArgument
	 */
	var $argument;

	/**
	 * Default value
	 * @var string
	 */
	var $default;

	/**
	 * Sort order
	 * @var string
	 */
	var $sort_order;

	/**
	 * Sort order argument
	 * @var SortQueryArgument object
	 */
	var $sort_order_argument;

	/**
	 * constructor
	 * @param object $index
	 * @return void
	 */
	function IndexTag($index)
	{
		$this->argument_name = $index->attrs->var;

		// Sort index - column by which to sort
		//$dbParser = new DB(); $dbParser = &$dbParser->getParser();
		//$index->attrs->default = $dbParser->parseExpression($index->attrs->default);
		$this->default = $index->attrs->default;
		$this->argument = new QueryArgument($index);

		// Sort order - asc / desc
		$this->sort_order = $index->attrs->order;
		$sortList = array('asc' => 1, 'desc' => 1);
		if(!isset($sortList[$this->sort_order]))
		{
			$arg = new Xml_Node_();
			$arg->attrs = new Xml_Node_();
			$arg->attrs->var = $this->sort_order;
			$arg->attrs->default = 'asc';
			$this->sort_order_argument = new SortQueryArgument($arg);
			$this->sort_order = '$' . $this->sort_order_argument->getArgumentName() . '_argument';
		}
		else
		{
			$this->sort_order = '"' . $this->sort_order . '"';
		}
	}

	function toString()
	{
		return sprintf('new OrderByColumn(${\'%s_argument\'}, %s)', $this->argument->getArgumentName(), $this->sort_order);
	}

	function getArguments()
	{
		$arguments = array();
		$arguments[] = $this->argument;
		if($this->sort_order_argument)
		{
			$arguments[] = $this->sort_order_argument;
		}
		return $arguments;
	}

}
/* End of file IndexTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/navigation/IndexTag.class.php */
