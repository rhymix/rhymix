<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Models the &lt;column&gt; tag inside an XML Query file whose action is 'insert'
 *
 * @author Corina Udrescu (corina.udrescu@arnia.ro)
 * @package classes\xml\xmlquery\tags\column
 * @version 0.1
 */
class InsertColumnTag extends ColumnTag
{

	/**
	 * Argument
	 *
	 * @var QueryArgument object
	 */
	var $argument;

	/**
	 * Constructor
	 *
	 * @param object $column
	 *
	 * @return void
	 */
	function InsertColumnTag($column)
	{
		parent::ColumnTag($column->attrs->name);
		$dbParser = DB::getParser();
		$this->name = $dbParser->parseColumnName($this->name);
		$this->argument = new QueryArgument($column);
	}

	/**
	 * Returns the string to be output in the cache file
	 * used for instantiating an InsertExpression when a
	 * query is executed
	 *
	 * @return string
	 */
	function getExpressionString()
	{
		return sprintf('new InsertExpression(\'%s\', ${\'%s_argument\'})'
						, $this->name
						, $this->argument->argument_name);
	}

	/**
	 * Returns the QueryArgument object associated with this INSERT statement
	 *
	 * @return QueryArgument
	 */
	function getArgument()
	{
		return $this->argument;
	}

}
/* End of file InsertColumnTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/column/InsertColumnTag.class.php */
