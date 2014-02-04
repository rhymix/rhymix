<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Models the &lt;column&gt; tag inside an XML Query file whose action is 'select'
 *
 * @author Corina Udrescu (corina.udrescu@arnia.ro)
 * @package classes\xml\xmlquery\tags\column
 * @version 0.1
 */
class SelectColumnTag extends ColumnTag
{

	/**
	 * Column alias
	 *
	 * @var string
	 */
	var $alias;

	/**
	 * Click count status
	 *
	 * @var bool
	 */
	var $click_count;

	/**
	 * Constructor
	 *
	 * @param string|object $column
	 * @return void
	 */
	function SelectColumnTag($column)
	{
		if($column == "*" || $column->attrs->name == '*')
		{
			parent::ColumnTag(NULL);
			$this->name = "*";
		}
		else
		{
			parent::ColumnTag($column->attrs->name);
			$dbParser = DB::getParser();
			$this->name = $dbParser->parseExpression($this->name);

			$this->alias = $column->attrs->alias;
			$this->click_count = $column->attrs->click_count;
		}
	}

	/**
	 * Returns the string to be output in the cache file
	 *
	 * A select column tag in an XML query can be used for:
	 * <ul>
	 *   <li> a star expression: SELECT *
	 *   <li> a click count expression: SELECT + UPDATE
	 *   <li> any other select expression (column name, function call etc). </li>
	 * </ul>
	 *
	 * @return string
	 */
	function getExpressionString()
	{
		if($this->name == '*')
		{
			return "new StarExpression()";
		}
		if($this->click_count)
		{
			return sprintf('new ClickCountExpression(\'%s\', %s, $args->%s)', $this->name, $this->alias ? '\'' . $this->alias . '\'' : "''", $this->click_count);
		}
		if(strpos($this->name, '$') === 0)
		{
			return sprintf('new SelectExpression($args->%s)', substr($this->name, 1));
		}
		$dbParser = DB::getParser();
		return sprintf('new SelectExpression(\'%s\'%s)', $this->name, $this->alias ? ', \'' . $dbParser->escape($this->alias) . '\'' : '');
	}

}
/* End of file SelectColumnTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/column/SelectColumnTag.class.php */
