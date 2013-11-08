<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * QueryTag class
 *
 * @author Arnia Software
 * @package /classes/xml/xmlquery/tags/query
 * @version 0.1
 */
class QueryTag
{

	/**
	 * Action for example, 'select', 'insert', 'delete'...
	 * @var string
	 */
	var $action;

	/**
	 * Query id
	 * @var string
	 */
	var $query_id;

	/**
	 * Priority
	 * @var string
	 */
	var $priority;

	/**
	 * column type list
	 * @var array
	 */
	var $column_type;

	/**
	 * Query stdClass object
	 * @var object
	 */
	var $query;

	/**
	 * Columns in xml tags
	 * @var object
	 */
	var $columns;

	/**
	 * Tables in xml tags
	 * @var object
	 */
	var $tables;

	/**
	 * Subquery in xml tags
	 * @var object
	 */
	var $subquery;

	/**
	 * Conditions in xml tags
	 * @var object
	 */
	var $conditions;

	/**
	 * Groups in xml tags
	 * @var object
	 */
	var $groups;

	/**
	 * Navigation in xml tags
	 * @var object
	 */
	var $navigation;

	/**
	 * Arguments in xml tags
	 * @var object
	 */
	var $arguments;

	/**
	 * PreBuff
	 * @var string
	 */
	var $preBuff;

	/**
	 * Buff
	 * @var string
	 */
	var $buff;

	/**
	 * Subquery status
	 * @var bool
	 */
	var $isSubQuery;

	/**
	 * Join type
	 * @var string
	 */
	var $join_type;

	/**
	 * alias
	 * @var string
	 */
	var $alias;

	/**
	 * constructor
	 * @param object $query
	 * @param bool $isSubQuery
	 * @return void
	 */
	function QueryTag($query, $isSubQuery = FALSE)
	{
		$this->action = $query->attrs->action;
		$this->query_id = $query->attrs->id;
		$this->priority = $query->attrs->priority;
		$this->query = $query;
		$this->isSubQuery = $isSubQuery;
		if($this->isSubQuery)
		{
			$this->action = 'select';
		}
		if($query->attrs->alias)
		{
			$dbParser = DB::getParser();
			$this->alias = $dbParser->escape($query->attrs->alias);
		}
		$this->join_type = $query->attrs->join_type;

		$this->getColumns();
		$tables = $this->getTables();
		$this->setTableColumnTypes($tables);
		$this->getSubquery(); // Used for insert-select
		$this->getConditions();
		$this->getGroups();
		$this->getNavigation();

		$this->getPrebuff();
		$this->getBuff();
	}

	function show()
	{
		return TRUE;
	}

	function getQueryId()
	{
		return $this->query->attrs->query_id ? $this->query->attrs->query_id : $this->query->attrs->id;
	}

	function getPriority()
	{
		return $this->query->attrs->priority;
	}

	function getAction()
	{
		return $this->query->attrs->action;
	}

	function setTableColumnTypes($tables)
	{
		$query_id = $this->getQueryId();
		if(!isset($this->column_type[$query_id]))
		{
			$table_tags = $tables->getTables();
			$column_type = array();
			foreach($table_tags as $table_tag)
			{
				if(is_a($table_tag, 'TableTag'))
				{
					$table_name = $table_tag->getTableName();
					$table_alias = $table_tag->getTableAlias();
					$tag_column_type = QueryParser::getTableInfo($query_id, $table_name);
					$column_type[$table_alias] = $tag_column_type;
				}
			}
			$this->column_type[$query_id] = $column_type;
		}
	}

	function getColumns()
	{
		if($this->action == 'select')
		{
			return $this->columns = new SelectColumnsTag($this->query->columns);
		}
		else if($this->action == 'insert' || $this->action == 'insert-select')
		{
			return $this->columns = new InsertColumnsTag($this->query->columns->column);
		}
		else if($this->action == 'update')
		{
			return $this->columns = new UpdateColumnsTag($this->query->columns->column);
		}
		else if($this->action == 'delete')
		{
			return $this->columns = null;
		}
	}

	function getPrebuff()
	{
		if($this->isSubQuery)
		{
			return;
		}
		// TODO Check if this work with arguments in join clause
		$arguments = $this->getArguments();

		$prebuff = '';
		foreach($arguments as $argument)
		{
			if(isset($argument))
			{
				$arg_name = $argument->getArgumentName();
				if($arg_name)
				{
					unset($column_type);
					$prebuff .= $argument->toString();

					$table_alias = $argument->getTableName();
					if(isset($table_alias))
					{
						if(isset($this->column_type[$this->getQueryId()][$table_alias][$argument->getColumnName()]))
						{
							$column_type = $this->column_type[$this->getQueryId()][$table_alias][$argument->getColumnName()];
						}
					}
					else
					{
						$current_tables = $this->column_type[$this->getQueryId()];
						$column_name = $argument->getColumnName();
						foreach($current_tables as $current_table)
						{
							if(isset($current_table[$column_name]))
							{
								$column_type = $current_table[$column_name];
							}
						}
					}

					if(isset($column_type))
					{
						$prebuff .= sprintf('if(${\'%s_argument\'} !== null) ${\'%s_argument\'}->setColumnType(\'%s\');' . "\n"
								, $arg_name
								, $arg_name
								, $column_type);
					}
				}
			}
		}
		$prebuff .= "\n";

		return $this->preBuff = $prebuff;
	}

	function getBuff()
	{
		$buff = '';
		if($this->isSubQuery)
		{
			$buff = 'new Subquery(';
			$buff .= "'" . $this->alias . '\', ';
			$buff .= ($this->columns ? $this->columns->toString() : 'null' ) . ', ' . PHP_EOL;
			$buff .= $this->tables->toString() . ',' . PHP_EOL;
			$buff .= $this->conditions->toString() . ',' . PHP_EOL;
			$buff .= $this->groups->toString() . ',' . PHP_EOL;
			$buff .= $this->navigation->getOrderByString() . ',' . PHP_EOL;
			$limit = $this->navigation->getLimitString();
			$buff .= $limit ? $limit : 'null' . PHP_EOL;
			$buff .= $this->join_type ? "'" . $this->join_type . "'" : '';
			$buff .= ')';

			$this->buff = $buff;
			return $this->buff;
		}

		$buff .= '$query = new Query();' . PHP_EOL;
		$buff .= sprintf('$query->setQueryId("%s");%s', $this->query_id, "\n");
		$buff .= sprintf('$query->setAction("%s");%s', $this->action, "\n");
		$buff .= sprintf('$query->setPriority("%s");%s', $this->priority, "\n");
		$buff .= $this->preBuff;
		if($this->columns)
		{
			$buff .= '$query->setColumns(' . $this->columns->toString() . ');' . PHP_EOL;
		}

		$buff .= '$query->setTables(' . $this->tables->toString() . ');' . PHP_EOL;
		if($this->action == 'insert-select')
		{
			$buff .= '$query->setSubquery(' . $this->subquery->toString() . ');' . PHP_EOL;
		}
		$buff .= '$query->setConditions(' . $this->conditions->toString() . ');' . PHP_EOL;
		$buff .= '$query->setGroups(' . $this->groups->toString() . ');' . PHP_EOL;
		$buff .= '$query->setOrder(' . $this->navigation->getOrderByString() . ');' . PHP_EOL;
		$buff .= '$query->setLimit(' . $this->navigation->getLimitString() . ');' . PHP_EOL;

		$this->buff = $buff;
		return $this->buff;
	}

	function getTables()
	{
		if($this->query->index_hint && ($this->query->index_hint->attrs->for == 'ALL' || Context::getDBType() == strtolower($this->query->index_hint->attrs->for)))
		{
			return $this->tables = new TablesTag($this->query->tables, $this->query->index_hint);
		}
		else
		{
			return $this->tables = new TablesTag($this->query->tables);
		}
	}

	function getSubquery()
	{
		if($this->query->query)
		{
			$this->subquery = new QueryTag($this->query->query, true);
		}
	}

	function getConditions()
	{
		return $this->conditions = new ConditionsTag($this->query->conditions);
	}

	function getGroups()
	{
		if($this->query->groups)
		{
			return $this->groups = new GroupsTag($this->query->groups->group);
		}
		else
		{
			return $this->groups = new GroupsTag(NULL);
		}
	}

	function getNavigation()
	{
		return $this->navigation = new NavigationTag($this->query->navigation);
	}

	function toString()
	{
		return $this->buff;
	}

	function getTableString()
	{
		return $this->buff;
	}

	function getConditionString()
	{
		return $this->buff;
	}

	function getExpressionString()
	{
		return $this->buff;
	}

	function getArguments()
	{
		$arguments = array();
		if($this->columns)
		{
			$arguments = array_merge($arguments, $this->columns->getArguments());
		}
		if($this->action == 'insert-select')
		{
			$arguments = array_merge($arguments, $this->subquery->getArguments());
		}
		$arguments = array_merge($arguments, $this->tables->getArguments());
		$arguments = array_merge($arguments, $this->conditions->getArguments());
		$arguments = array_merge($arguments, $this->navigation->getArguments());
		return $arguments;
	}

}
/* End of file QueryTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/navigation/QueryTag.class.php */
