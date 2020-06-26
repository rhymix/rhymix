<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Query class.
 */
class Query extends VariableBase
{
	public $name;
	public $alias;
	public $type;
	public $operation;
	public $column;
	public $pipe;
	public $tables = array();
	public $columns = array();
	public $conditions = array();
	public $groupby = null;
	public $navigation = null;
	
	/**
	 * Attributes for query generation.
	 */
	protected $_prefix = '';
	protected $_args = array();
	protected $_column_list = array();
	protected $_params = array();
	
	/**
	 * Generate the query string for this query.
	 * 
	 * @param string $prefix
	 * @param array $args
	 * @param array $column_list
	 * @param bool $count_only
	 * @return string
	 */
	public function getQueryString(string $prefix = '', array $args, array $column_list = [], bool $count_only = false): string
	{
		// Save the query information.
		$this->_prefix = $prefix;
		$this->_args = $args;
		$this->_column_list = $column_list;
		$this->_params = array();
		
		// Call different internal methods depending on the query type.
		switch ($this->type)
		{
			case 'SELECT':
				$result = $this->_getSelectQueryString($count_only);
				break;
			default:
				$result = '';
		}
		
		// Reset state and return the result.
		$this->_prefix = '';
		$this->_args = array();
		$this->_column_list = array();
		return $result;
	}
	
	/**
	 * Get the query parameters to use with the query string generated above.
	 * 
	 * @return array
	 */
	public function getQueryParams()
	{
		return $this->_params;
	}
	
	/**
	 * Generate a SELECT query string.
	 * 
	 * @param bool $count_only
	 * @return string
	 */
	protected function _getSelectQueryString(bool $count_only = false): string
	{
		// Initialize the query string.
		$result = 'SELECT ';
		
		// Compose the column list.
		$columns = array();
		if ($count_only)
		{
			$result .= 'COUNT(*) AS `count`';
		}
		elseif ($this->_column_list)
		{
			$result .= implode(', ', array_map(function($str) {
				return '`' . $str . '`';
			}, $this->_column_list));
		}
		else
		{
			foreach ($this->columns as $column)
			{
				if ($column instanceof self)
				{
					$subquery = $column->getQueryString($this->_prefix, $this->_args);
					foreach ($column->getQueryParams() as $param)
					{
						$this->_params[] = $param;
					}
					$columns[] = sprintf('(%s) AS %s', $subquery, self::quoteName($column->alias));
				}
				elseif ($column->is_expression && !$column->is_wildcard)
				{
					$columns[] = $column->name . ($column->alias ? (' AS ' . self::quoteName($column->alias)) : '');
				}
				else
				{
					$columns[] = self::quoteName($column->name) . ($column->alias ? (' AS ' . self::quoteName($column->alias)) : '');
				}
			}
			$result .= implode(', ', $columns);
		}
		
		// Compose the table list.
		$tables = array();
		foreach ($this->tables as $table)
		{
			if ($table instanceof self)
			{
				$subquery = $table->getQueryString($this->_prefix, $this->_args);
				foreach ($table->getQueryParams() as $param)
				{
					$this->_params[] = $param;
				}
				$tables[] = (count($tables) ? ', ' : '') . sprintf('(%s) AS `%s`', $subquery, $table->alias);
			}
			else
			{
				$tabledef = self::quoteName($table->name) . ($table->alias ? (' AS `' . $table->alias . '`') : '');
				if ($table->join_type)
				{
					$join_where = $this->_arrangeConditions($table->join_conditions);
					if ($join_where !== '')
					{
						$tabledef = $tabledef . ' ON ' . $join_where;
					}
					$tables[] = ' ' . $table->join_type . ' ' . $tabledef;
				}
				else
				{
					$tables[] = (count($tables) ? ', ' : '') . $tabledef;
				}
			}
		}
		$result .= ' FROM ' . implode('', $tables);
		
		// Compose the WHERE clause.
		if (count($this->conditions))
		{
			$where = $this->_arrangeConditions($this->conditions);
			if ($where !== '')
			{
				$result .= ' WHERE ' . $where;
			}
		}
		
		// Compose the GROUP BY clause.
		if ($this->groupby && count($this->groupby->columns))
		{
			$columns = array();
			foreach ($this->groupby->columns as $column_name)
			{
				if (self::isValidColumnName($column_name))
				{
					$columns[] = self::quoteName($column_name);
				}
				else
				{
					$columns[] = $column_name;
				}
			}
			$result .= ' GROUP BY ' . implode(', ', $columns);
		}
		if ($this->groupby && count($this->groupby->having))
		{
			$having = $this->_arrangeConditions($this->groupby->having);
			if ($having !== '')
			{
				$result .= ' HAVING ' . $having;
			}
		}
		
		// Compose the ORDER BY clause.
		if ($this->navigation && count($this->navigation->orderby) && !$count_only)
		{
			$orderby_list = array();
			foreach ($this->navigation->orderby as $orderby)
			{
				$column_name = '';
				list($is_expression, $column_name) = $orderby->getValue($this->_args);
				if (!$column_name)
				{
					continue;
				}
				if (!$is_expression && self::isValidColumnName($column_name))
				{
					$column_name = self::quoteName($column_name);
				}
				
				if (isset($this->_args[$orderby->order_var]))
				{
					$column_order = preg_replace('/[^A-Z]/', '', strtoupper($this->_args[$orderby->order_var]));
				}
				else
				{
					$column_order = preg_replace('/[^A-Z]/', '', strtoupper($orderby->order_default));
				}
				
				$orderby_list[] = $column_name . ' ' . $column_order;
			}
			$result .= ' ORDER BY ' . implode(', ', $orderby_list);
		}
		
		// Compose the LIMIT clause.
		if ($this->navigation && $this->navigation->list_count && !$count_only)
		{
			list($is_expression, $list_count) = $this->navigation->list_count->getValue($this->_args);
			if ($list_count > 0)
			{
				if ($this->navigation->page)
				{
					list($is_expression, $page) = $this->navigation->page->getValue($this->_args);
				}
				if ($this->navigation->offset)
				{
					list($is_expression, $offset) = $this->navigation->offset->getValue($this->_args);
				}
				if ($page > 0)
				{
					$offset = $list_count * ($page - 1);
				}
				$result .= ' LIMIT ' . ($offset > 0 ? (intval($offset) . ', ') : '') . intval($list_count);
			}
		}
		
		// Return the final query string.
		return $result;
	}
	
	/**
	 * Generate a WHERE clause from a list of conditions.
	 * 
	 * @param array $conditions
	 * @return string
	 */
	protected function _arrangeConditions(array $conditions): string
	{
		// Initialize the result.
		$result = '';
		
		// Process each condition.
		foreach ($conditions as $condition)
		{
			// Subquery
			if ($condition instanceof self)
			{
				$condition_string = $this->_parseCondition($condition);
				if ($condition_string !== '')
				{
					$result .= ($result === '' ? '' : (' ' . $condition->pipe . ' ')) . $condition_string;
				}
			}
			
			// Condition group
			elseif ($condition instanceof ConditionGroup)
			{
				$condition_string = $this->_arrangeConditions($condition->conditions);
				if ($condition_string !== '')
				{
					$result .= ($result === '' ? '' : (' ' . $condition->pipe . ' ')) . '(' . $condition_string . ')';
				}
			}
			
			// Simple condition
			else
			{
				$condition_string = $this->_parseCondition($condition);
				if ($condition_string !== '')
				{
					$result .= ($result === '' ? '' : (' ' . $condition->pipe . ' ')) . $condition_string;
				}
			}
		}
		
		// Return the WHERE clause.
		return $result;
	}
	
	/**
	 * Generate each condition in a WHERE clause.
	 * 
	 * @param object $condition
	 * @return string
	 */
	protected function _parseCondition(VariableBase $condition): string
	{
		list($where, $params) = $condition->getQueryStringAndParams($this->_args, $this->_prefix);
		foreach ($params as $param)
		{
			$this->_params[] = $param;
		}
		return $where;
	}
	
	/**
	 * Quote a column name.
	 * 
	 * @param string $column_name
	 * @return string
	 */
	public static function quoteName(string $column_name): string
	{
		$columns = explode('.', $column_name);
		$columns = array_map(function($str) {
			return $str === '*' ? $str : ('`' . $str . '`');
		}, $columns);
		return implode('.', $columns);
	}
	
	/**
	 * Check if a column name is valid.
	 * 
	 * @param string $column_name
	 * @return bool
	 */
	public static function isValidColumnName(string $column_name): bool
	{
		return preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*$/i', $column_name) ? true : false;
	}
	
	/**
	 * Check if a variable is considered valid for XE compatibility.
	 * 
	 * @param mixed $var
	 * @return bool
	 */
	public static function isValidVariable($var): bool
	{
		if ($var === null || $var === '')
		{
			return false;
		}
		
		if (is_array($var))
		{
			$count = count($var);
			if ($count === 0 || ($count === 1 && reset($var) === ''))
			{
				return false;
			}
		}
		
		return true;
	}
}
