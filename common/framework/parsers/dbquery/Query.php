<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Query class.
 */
class Query extends VariableBase
{
	/**
	 * Attributes common to all queries.
	 */
	public $name;
	public $type;
	public $tables = array();
	public $index_hints = array();
	public $columns = array();
	public $conditions = array();
	public $groupby = null;
	public $navigation = null;
	public $select_distinct = false;
	public $update_duplicate = false;
	public $requires_pagination = false;
	
	/**
	 * Attributes for subqueries in the <tables> or <columns> section.
	 */
	public $alias;
	public $join_type;
	public $join_conditions = array();
	
	/**
	 * Attributes for subqueries in the <conditions> section.
	 */
	public $operation;
	public $column;
	public $pipe;
	
	/**
	 * Attributes used during query string generation.
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
	 * @param int $count_only
	 * @return string
	 */
	public function getQueryString(string $prefix = '', array $args, array $column_list = [], int $count_only = 0): string
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
			case 'INSERT':
				$result = $this->_getInsertQueryString();
				break;
			case 'UPDATE':
				$result = $this->_getUpdateQueryString();
				break;
			case 'DELETE':
				$result = $this->_getDeleteQueryString();
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
	 * Check if this query requires pagination.
	 * 
	 * @return bool
	 */
	public function requiresPagination(): bool
	{
		return $this->requires_pagination;
	}
	
	/**
	 * Generate a SELECT query string.
	 * 
	 * @param int $count_only
	 * @return string
	 */
	protected function _getSelectQueryString(int $count_only = 0): string
	{
		// Initialize the query string.
		$result = 'SELECT';
		$has_subquery_columns = false;
		
		// Compose the column list.
		if ($this->_column_list)
		{
			$column_list = implode(', ', array_map(function($str) {
				return self::quoteName($str);
			}, $this->_column_list));
		}
		else
		{
			$columns = array();
			foreach ($this->columns as $column)
			{
				if ($column->ifvar && !isset($this->_args[$column->ifvar]))
				{
					continue;
				}
				elseif ($column instanceof self)
				{
					$has_subquery_columns = true;
					$subquery_count_only = $count_only ? $count_only + 1 : 0;
					$subquery = $column->getQueryString($this->_prefix, $this->_args, [], $subquery_count_only);
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
				elseif ($column->is_wildcard && $count_only >= 1 && !$this->select_distinct)
				{
					$columns[] = '1';
				}
				else
				{
					$columns[] = self::quoteName($column->name) . ($column->alias ? (' AS ' . self::quoteName($column->alias)) : '');
				}
			}
			$column_list = implode(', ', $columns);
		}
		
		// Replace the column list if this is a count-only query.
		if ($count_only == 1)
		{
			$count_wrap = ($this->groupby || $this->select_distinct || $has_subquery_columns || preg_match('/\bDISTINCT\b/i', $column_list));
			if ($count_wrap)
			{
				$result .= ($this->select_distinct ? ' DISTINCT ' : ' ') . $column_list;
			}
			else
			{
				$result .= ' COUNT(*) AS `count`';
			}
		}
		else
		{
			$count_wrap = false;
			$result .= ($this->select_distinct ? ' DISTINCT ' : ' ') . $column_list;
		}
		
		// Compose the FROM clause.
		if (count($this->tables))
		{
			$tables = $this->_arrangeTables($this->tables);
			if ($tables !== '')
			{
				$result .= ' FROM ' . $tables;
			}
		}
		if (count($this->index_hints))
		{
			$index_hints = $this->_arrangeIndexHints($this->index_hints);
			if ($index_hints !== '')
			{
				$result .= ' ' . $index_hints;
			}
		}
		
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
		if ($this->groupby && count($this->groupby->columns) && (!$this->groupby->ifvar || isset($this->_args[$this->groupby->ifvar])))
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
		if ($this->groupby && count($this->groupby->having) && (!$this->groupby->ifvar || isset($this->_args[$this->groupby->ifvar])))
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
			$result .= ' ORDER BY ' . $this->_arrangeOrderBy($this->navigation);
		}
		
		// Compose the LIMIT/OFFSET clause.
		if ($this->navigation && $this->navigation->list_count && !$count_only)
		{
			$limit_offset = $this->_arrangeLimitOffset($this->navigation);
			if ($limit_offset !== '')
			{
				$result .= ' LIMIT ' . $limit_offset;
			}
		}
		
		// Wrap in a subquery if necesary.
		if ($count_wrap)
		{
			$result = 'SELECT COUNT(*) AS `count` FROM (' . $result . ') AS `subquery`';
		}
		
		// Return the final query string.
		return $result;
	}
	
	/**
	 * Generate a INSERT query string.
	 * 
	 * @return string
	 */
	protected function _getInsertQueryString(): string
	{
		// Initialize the query string.
		$result = 'INSERT';
		
		// Compose the INTO clause.
		if (count($this->tables))
		{
			$tables = $this->_arrangeTables($this->tables, false);
			if ($tables !== '')
			{
				$result .= ' INTO ' . $tables;
			}
		}
		
		// Process the SET clause with new values.
		$columns = array();
		foreach ($this->columns as $column)
		{
			if ($column->ifvar && !isset($this->_args[$column->ifvar]))
			{
				continue;
			}
			
			$setval_string = $this->_parseCondition($column);
			if ($setval_string !== '')
			{
				$columns[] = $setval_string;
			}
		}
		$result .= ' SET ' . implode(', ', $columns);
		
		// Process the ON DUPLICATE KEY UPDATE (upsert) clause.
		if ($this->update_duplicate && count($columns))
		{
			$result .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $columns);
			$duplicate_params = $this->_params;
			foreach ($duplicate_params as $param)
			{
				$this->_params[] = $param;
			}
		}
		
		// Return the final query string.
		return $result;
	}
	
	/**
	 * Generate a UPDATE query string.
	 * 
	 * @return string
	 */
	protected function _getUpdateQueryString(): string
	{
		// Initialize the query string.
		$result = 'UPDATE ';
		
		// Compose the INTO clause.
		if (count($this->tables))
		{
			$tables = $this->_arrangeTables($this->tables, false);
			if ($tables !== '')
			{
				$result .= $tables;
			}
		}
		
		// Compose the SET clause with updated values.
		$columns = array();
		foreach ($this->columns as $column)
		{
			if ($column->ifvar && !isset($this->_args[$column->ifvar]))
			{
				continue;
			}
			
			$setval_string = $this->_parseCondition($column);
			if ($setval_string !== '')
			{
				$columns[] = $setval_string;
			}
		}
		$result .= ' SET ' . implode(', ', $columns);
		
		// Compose the WHERE clause.
		if (count($this->conditions))
		{
			$where = $this->_arrangeConditions($this->conditions);
			if ($where !== '')
			{
				$result .= ' WHERE ' . $where;
			}
		}
		
		// Return the final query string.
		return $result;
	}
	
	/**
	 * Generate a DELETE query string.
	 * 
	 * @return string
	 */
	protected function _getDeleteQueryString(): string
	{
		// Initialize the query string.
		$result = 'DELETE';
		
		// Compose the FROM clause.
		if (count($this->tables))
		{
			$tables = $this->_arrangeTables($this->tables, false);
			if ($tables !== '')
			{
				$result .= ' FROM ' . $tables;
			}
		}
		
		// Compose the WHERE clause.
		if (count($this->conditions))
		{
			$where = $this->_arrangeConditions($this->conditions);
			if ($where !== '')
			{
				$result .= ' WHERE ' . $where;
			}
		}
		
		// Compose the ORDER BY clause.
		if ($this->navigation && count($this->navigation->orderby))
		{
			$result .= ' ORDER BY ' . $this->_arrangeOrderBy($this->navigation);
		}
		
		// Compose the LIMIT/OFFSET clause.
		if ($this->navigation && $this->navigation->list_count)
		{
			$limit_offset = $this->_arrangeLimitOffset($this->navigation);
			if ($limit_offset !== '')
			{
				$result .= ' LIMIT ' . $limit_offset;
			}
		}
		
		// Return the final query string.
		return $result;
	}
	
	/**
	 * Generate a FROM clause from a list of tables.
	 * 
	 * @param array $tables
	 * @param bool $use_aliases
	 * @return string
	 */
	protected function _arrangeTables(array $tables, bool $use_aliases = true): string
	{
		// Initialize the result.
		$result = array();
		
		// Process each table definition.
		foreach ($tables as $table)
		{
			// Skip
			if ($table->ifvar && !isset($this->_args[$table->ifvar]))
			{
				continue;
			}
			
			// Subquery
			if ($table instanceof self)
			{
				$tabledef = '(' . $table->getQueryString($this->_prefix, $this->_args) . ')';
				if ($table->alias)
				{
					$tabledef .= ' AS `' . $table->alias . '`';
				}
				foreach ($table->getQueryParams() as $param)
				{
					$this->_params[] = $param;
				}
			}
			
			// Regular table
			else
			{
				$tabledef = self::quoteName($this->_prefix . $table->name);
				if ($use_aliases && $table->alias && $table->alias !== ($this->_prefix . $table->name))
				{
					$tabledef .= ' AS `' . $table->alias . '`';
				}
			}
			
			// Add join conditions
			if ($table->join_type)
			{
				$join_where = $this->_arrangeConditions($table->join_conditions);
				if ($join_where !== '')
				{
					$tabledef = $tabledef . ' ON ' . $join_where;
				}
				$result[] = ' ' . $table->join_type . ' ' . $tabledef;
			}
			else
			{
				$result[] = (count($result) ? ', ' : '') . $tabledef;
			}
		}
		
		// Combine the result and return as a string.
		return implode('', $result);
	}
	
	/**
	 * Generate index hints.
	 * 
	 * @param array $index_hints
	 * @return string
	 */
	protected function _arrangeIndexHints(array $index_hints): string
	{
		// Initialize the index list by type.
		$index_list = [];
		
		// Group each index hint by type.
		foreach ($index_hints as $index_hint)
		{
			// Skip
			if ($index_hint->ifvar && !isset($this->_args[$index_hint->ifvar]))
			{
				continue;
			}
			
			if (!count($index_hint->target_db) || isset($index_hint->target_db['mysql']))
			{
				$key = $index_hint->hint_type ?: 'USE';
				$index_list[$key] = $index_list[$key] ?? [];
				if ($index_hint->var && isset($this->_args[$index_hint->var]))
				{
					$index_list[$key][] = self::quoteName($this->_args[$index_hint->var]);
				}
				elseif ($index_hint->index_name)
				{
					$index_list[$key][] = self::quoteName($index_hint->index_name);
				}
			}
		}
		
		// Generate a list of indexes for each group.
		$result = [];
		foreach ($index_list as $key => $val)
		{
			if (count($val))
			{
				$result[] = sprintf('%s INDEX (%s)', $key, implode(', ', $val));
			}
		}
		return implode(' ', $result);
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
			// Skip
			if ($condition->ifvar && !isset($this->_args[$condition->ifvar]))
			{
				continue;
			}
			
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
	 * Generate a ORDER BY clause from navigation settings.
	 * 
	 * @param object $navigation
	 * @return string
	 */
	protected function _arrangeOrderBy(Navigation $navigation): string
	{
		// Initialize the result.
		$result = array();
		
		// Process each column definition.
		foreach ($navigation->orderby as $orderby)
		{
			// Get the name of the column or expression to order by.
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
			
			// Get the ordering (ASC or DESC).
			if (preg_match('/^(ASC|DESC)$/i', $orderby->order_var, $matches))
			{
				$column_order = strtoupper($matches[1]);
			}
			elseif (isset($this->_args[$orderby->order_var]))
			{
				$column_order = preg_replace('/[^A-Z]/', '', strtoupper($this->_args[$orderby->order_var]));
			}
			else
			{
				$column_order = preg_replace('/[^A-Z]/', '', strtoupper($orderby->order_default));
			}
			
			$result[] = $column_name . ' ' . $column_order;
		}
		
		// Return the ORDER BY clause.
		return implode(', ', $result);
	}
	
	/**
	 * Generate a LIMIT/OFFSET clause from navigation settings.
	 * 
	 * @param object $navigation
	 * @return string
	 */
	protected function _arrangeLimitOffset(Navigation $navigation): string
	{
		// Get the list count.
		list($is_expression, $list_count) = $navigation->list_count->getValue($this->_args);
		if ($list_count <= 0)
		{
			return '';
		}
		$page = 0;
		$offset = 0;
		
		// Get the offset from the page or offset variable.
		if ($navigation->page)
		{
			list($is_expression, $page) = $navigation->page->getValue($this->_args);
		}
		if ($navigation->offset)
		{
			list($is_expression, $offset) = $navigation->offset->getValue($this->_args);
		}
		
		// If page is available, set the offset and require pagination for this query.
		if ($page > 0)
		{
			$offset = $list_count * ($page - 1);
			if ($this->type === 'SELECT')
			{
				$this->requires_pagination = true;
			}
		}
		else
		{
			$page = 1;
		}
		
		// Return the LIMIT/OFFSET clause.
		return ($offset > 0 ? (intval($offset) . ', ') : '') . intval($list_count);
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
		return preg_replace_callback('/[a-z][a-z0-9_.*]*(?!\\()\b/i', function($m) {
			$columns = explode('.', $m[0]);
			$columns = array_map(function($str) {
				return $str === '*' ? $str : ('`' . $str . '`');
			}, $columns);
			return implode('.', $columns);
		}, $column_name);
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
	 * @param bool $allow_empty_string
	 * @return bool
	 */
	public static function isValidVariable($var, $allow_empty_string = true): bool
	{
		if ($var === null || ($var === '' && !$allow_empty_string))
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
