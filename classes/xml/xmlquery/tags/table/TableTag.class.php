<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * TableTag
 * Models the <table> tag inside an XML Query file
 * @abstract
 *   Example
 *      <table name="modules" />
 *      <table name="documents" alias="doc" />
 *   Attributes
 *      name - name of the table - table prefix will be automatically added
 *      alias - table alias. If no value is specified, the table name will be set as default alias
 *      join_type - in case the table is part of a join clause, this specifies the type of join: left, right etc.
 *                - permitted values: 'left join','left outer join','right join','right outer join'
 *   Children
 *      Can have children of type <conditions>
 *
 * @author Arnia Sowftare
 * @package /classes/xml/xmlquery/tags/table
 * @version 0.1
 */
class TableTag
{

	/**
	 * Unescaped name
	 * @var string
	 */
	var $unescaped_name;

	/**
	 * name
	 * @var string
	 */
	var $name;

	/**
	 * alias
	 * @var string
	 */
	var $alias;

	/**
	 * Join type
	 * @example 'left join', 'left outer join', 'right join', 'right outer join'
	 * @var string
	 */
	var $join_type;

	/**
	 * Condition object
	 * @var object
	 */
	var $conditions;

	/**
	 * JoinConditionsTag
	 * @var JoinConditionsTag object
	 */
	var $conditionsTag;

	/**
	 * constructor
	 * Initialises Table Tag properties
	 * @param object $table XML <table> tag
	 * @return void
	 */
	function TableTag($table)
	{
		$dbParser = DB::getParser();

		$this->unescaped_name = $table->attrs->name;
		$this->name = $dbParser->parseTableName($table->attrs->name);

		$this->alias = $table->attrs->alias;
		if(!$this->alias)
		{
			$this->alias = $table->attrs->name;
		}

		$this->join_type = $table->attrs->type;

		$this->conditions = $table->conditions;

		if($this->isJoinTable())
		{
			$this->conditionsTag = new JoinConditionsTag($this->conditions);
		}
	}

	function isJoinTable()
	{
		$joinList = array('left join' => 1, 'left outer join' => 1, 'right join' => 1, 'right outer join' => 1);
		if(isset($joinList[$this->join_type]) && count($this->conditions))
		{
			return true;
		}
		return false;
	}

	function getTableAlias()
	{
		return $this->alias;
	}

	function getTableName()
	{
		return $this->unescaped_name;
	}

	/**
	 * Returns string for printing in PHP query cache file
	 * The string contains code for instantiation of either 
	 * a Table or a JoinTable object
	 * @return string 
	 */
	function getTableString()
	{
		$dbParser = DB::getParser();

		if($this->isJoinTable())
		{
			return sprintf('new JoinTable(\'%s\', \'%s\', "%s", %s)'
							, $dbParser->escape($this->name)
							, $dbParser->escape($this->alias)
							, $this->join_type, $this->conditionsTag->toString());
		}
		return sprintf('new Table(\'%s\'%s)'
						, $dbParser->escape($this->name)
						, $this->alias ? ', \'' . $dbParser->escape($this->alias) . '\'' : '');
	}

	function getArguments()
	{
		if(!isset($this->conditionsTag))
		{
			return array();
		}
		return $this->conditionsTag->getArguments();
	}

}
/* End of file TableTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/table/TableTag.class.php */
