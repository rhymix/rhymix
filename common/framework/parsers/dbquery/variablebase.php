<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * VariableBase class.
 */
class VariableBase
{
	/**
	 * Instance properties.
	 */
	public $var;
	public $default;
	
	/**
	 * Convert an operator into real SQL.
	 * 
	 * @param array $args
	 * @param string $prefix
	 * @return array
	 */
	public function getQueryStringAndParams(array $args, string $prefix = ''): array
	{
		// Return if this method is called on an invalid child class.
		if ((!isset($this->column) && !isset($this->name)) || !isset($this->operation))
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Invalid invocation of getQueryStringAndParams()');
		}
		
		// Initialze the return values.
		$where = '';
		$params = array();
		
		// Process the variable or default value.
		if ($this instanceof Query)
		{
			$is_expression = true;
			$value = '(' . $this->getQueryString($prefix, $args) . ')';
			$params = $this->getQueryParams();
		}
		elseif ($this->var && Query::isValidVariable($args[$this->var], $this instanceof ColumnWrite))
		{
			if ($args[$this->var] instanceof EmptyString || $args[$this->var] instanceof NullValue)
			{
				$this->filterValue('');
				$value = strval($args[$this->var]);
				$is_expression = true;
			}
			elseif ($args[$this->var] === '')
			{
				$this->filterValue($args[$this->var]);
				if ($this instanceof ColumnWrite)
				{
					$value = $args[$this->var];
					$is_expression = false;
				}
				else
				{
					list($is_expression, $value) = $this->getDefaultValue();
				}
			}
			else
			{
				$this->filterValue($args[$this->var]);
				$value = $args[$this->var];
				$is_expression = false;
			}
		}
		elseif ($this->default !== null)
		{
			list($is_expression, $value) = $this->getDefaultValue();
		}
		elseif ($this->not_null)
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' is not set');
		}
		elseif (!in_array($this->operation, ['null', 'notnull', 'not_null']))
		{
			return [$where, $params];
		}
		
		// Quote the column name.
		$column = Query::quoteName(isset($this->column) ? $this->column : $this->name);
		
		// Prepare the target value.
		$list_ops = array('in' => true, 'notin' => true, 'not_in' => true, 'between' => true);
		if (isset($list_ops[$this->operation]) && !$is_expression && !is_array($value) && $value !== '')
		{
			$value = explode(',', preg_replace('/[\s\']/', '', $value));
		}
		
		// Restrict operators for write queries.
		if ($this instanceof ColumnWrite && $this->operation && !in_array($this->operation, ['equal', 'plus', 'minus', 'multiply']))
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Operation ' . $this->operation . ' is not valid for column in an INSERT or UPDATE query');
		}
		
		// Apply the operator.
		switch ($this->operation)
		{
			case 'equal':
				$where = sprintf('%s = %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'notequal':
			case 'not_equal':
				$where = sprintf('%s != %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'more':
			case 'gte':
				$where = sprintf('%s >= %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'excess':
			case 'gt';
				$where = sprintf('%s > %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'less':
			case 'lte':
				$where = sprintf('%s <= %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'below':
			case 'lt';
				$where = sprintf('%s < %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'regexp';
				$where = sprintf('%s REGEXP %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'notregexp';
			case 'not_regexp';
				$where = sprintf('%s NOT REGEXP %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'like':
				$where = sprintf('%s LIKE %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = '%' . $value . '%';
				break;
			case 'like_prefix':
			case 'like_head':
				$where = sprintf('%s LIKE %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value . '%';
				break;
			case 'like_suffix':
			case 'like_tail':
				$where = sprintf('%s LIKE %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = '%' . $value;
				break;
			case 'notlike':
				$where = sprintf('%s NOT LIKE %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = '%' . $value . '%';
				break;
			case 'notlike_prefix':
			case 'notlike_head':
				$where = sprintf('%s NOT LIKE %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value . '%';
				break;
			case 'notlike_suffix':
			case 'notlike_tail':
				$where = sprintf('%s NOT LIKE %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = '%' . $value;
				break;
			case 'and':
				$where = sprintf('%s & %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = '%' . $value;
				break;
			case 'or':
				$where = sprintf('%s | %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = '%' . $value;
				break;
			case 'xor':
				$where = sprintf('%s ^ %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = '%' . $value;
				break;
			case 'not':
				$where = sprintf('%s ~ %s', $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = '%' . $value;
				break;
			case 'null':
				$where = sprintf('%s IS NULL', $column);
				break;
			case 'notnull':
			case 'not_null':
				$where = sprintf('%s IS NOT NULL', $column);
				break;
			case 'in':
				if ($is_expression)
				{
					$where = sprintf('%s IN %s', $column, $value);
				}
				else
				{
					$count = count($value);
					$placeholders = implode(', ', array_fill(0, $count, '?'));
					$where = sprintf('%s IN (%s)', $column, $placeholders);
					foreach ($value as $item)
					{
						$params[] = $item;
					}
				}
				break;
			case 'notin':
			case 'not_in':
				if ($is_expression)
				{
					$where = sprintf('%s IN %s', $column, $value);
				}
				else
				{
					$count = count($value);
					$placeholders = implode(', ', array_fill(0, $count, '?'));
					$where = sprintf('%s NOT IN (%s)', $column, $placeholders);
					foreach ($value as $item)
					{
						$params[] = $item;
					}
				}
				break;
			case 'between':
				$where = sprintf('%s BETWEEN ? AND ?', $column);
				foreach ($value as $item)
				{
					$params[] = $item;
				}
				break;
			case 'notbetween':
			case 'not_between':
				$where = sprintf('%s NOT BETWEEN ? AND ?', $column);
				foreach ($value as $item)
				{
					$params[] = $item;
				}
				break;
			case 'search':
				$keywords = preg_split('/[\s,]+/', $value, 10, \PREG_SPLIT_NO_EMPTY);
				$conditions = array();
				$placeholders = implode(', ', array_fill(0, count($keywords), '?'));
				foreach ($keywords as $item)
				{
					if (substr($item, 0, 1) === '-')
					{
						$conditions[] = sprintf('%s NOT LIKE ?', $column);
						$item = substr($item, 1);
					}
					else
					{
						$conditions[] = sprintf('%s LIKE ?', $column);
					}
					$params[] = '%' . str_replace(['\\', '_', '%'], ['\\\\', '\_', '\%'], $item) . '%';
				}
				$conditions = implode(' AND ', $conditions);
				$where = count($keywords) === 1 ? $conditions : "($conditions)";
				break;
			case 'plus':
				$where = sprintf('%s = %s + %s', $column, $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'minus':
				$where = sprintf('%s = %s - %s', $column, $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			case 'multiply':
				$where = sprintf('%s = %s * %s', $column, $column, $is_expression ? $value : '?');
				if (!$is_expression) $params[] = $value;
				break;
			default:
				$where = sprintf('%s = ?', $column);
				$params[] = $value;
		}
		
		// Return the complete condition and parameters.
		return [$where, $params];
	}
	
	/**
	 * Get the current value, falling back to the default value if necessary.
	 * 
	 * @param array $args
	 * @return array
	 */
	public function getValue(array $args)
	{
		if ($this->var && Query::isValidVariable($args[$this->var], $this instanceof ColumnWrite))
		{
			if ($args[$this->var] === '')
			{
				if ($this instanceof ColumnWrite)
				{
					$value = $args[$this->var];
					$is_expression = false;
				}
				else
				{
					list($is_expression, $value) = $this->getDefaultValue();
				}
			}
			else
			{
				$is_expression = false;
				$value = $args[$this->var];
			}
		}
		elseif ($this->default !== null)
		{
			list($is_expression, $value) = $this->getDefaultValue();
		}
		
		return [$is_expression, $value];
	}
	
	/**
	 * Get the default value of this variable.
	 * 
	 * @return array
	 */
	public function getDefaultValue()
	{
		// Get the current column name.
		$column = $this instanceof ColumnWrite ? $this->name : $this->column;
		
		// If the default value is a column name, escape it.
		if (strpos($this->default, '.') !== false && Query::isValidColumnName($this->default))
		{
			return [true, Query::quoteName($this->default)];
		}
		elseif (isset($column) && preg_match('/_srl$/', $column) && !is_numeric($this->default))
		{
			return [true, Query::quoteName($this->default)];
		}
		
		// If the default value is a function shortcut, return an appropriate value.
		switch ($this->default)
		{
			case 'ipaddress()':
				return [false, \RX_CLIENT_IP];
			case 'unixtime()':
				return [false, time()];
			case 'curdate()':
			case 'datetime()':
				return [false, date('YmdHis')];
			case 'date()':
				return [false, date('Ymd')];
			case 'time()':
				return [false, date('His')];
			case 'member_srl()':
				return [false, intval(\Rhymix\Framework\Session::getMemberSrl())];
			case 'sequence()':
				return [false, getNextSequence()];
		}
		
		// If the default value is a calculation based on the current value, return a query string.
		if (isset($column) && preg_match('/^(plus|minus|multiply)\(([0-9]+)\)$/', $this->default, $matches))
		{
			switch ($matches[1])
			{
				case 'plus':
					return [true, sprintf('%s + %d', Query::quoteName($column), $matches[2])];
				case 'minus':
					return [true, sprintf('%s - %d', Query::quoteName($column), $matches[2])];
				case 'multiply':
					return [true, sprintf('%s * %d', Query::quoteName($column), $matches[2])];
			}
		}
		
		// Otherwise, just return the literal value.
		return [false, $this->default];
	}
	
	/**
	 * Filter a value.
	 * 
	 * @param mixed $value
	 * @return void
	 */
	public function filterValue($value)
	{
		// Don't apply a filter if there is no variable.
		$column = $this instanceof ColumnWrite ? $this->name : $this->column;
		$filter = isset($this->filter) ? $this->filter : '';
		if (strval($value) === '')
		{
			$filter = '';
		}
		
		// Apply filters.
		switch ($filter)
		{
			case 'email':
			case 'email_address':
				if (!preg_match('/^[\w-]+((?:\.|\+|\~)[\w-]+)*@[\w-]+(\.[\w-]+)+$/', $value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $column . ' must contain a valid e-mail address');
				}
				break;
			case 'homepage':
			case 'url':
				if (!preg_match('/^(http|https)+(:\/\/)+[0-9a-z_-]+\.[^ ]+$/i', $value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $column . ' must contain a valid URL');
				}
				break;
			case 'userid':
			case 'user_id':
				if (!preg_match('/^[a-zA-Z]+([_0-9a-zA-Z]+)*$/', $value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $column . ' must contain a valid user ID');
				}
				break;
			case 'number':
			case 'numbers':
				if (!preg_match('/^(-?)[0-9]+(,\-?[0-9]+)*$/', is_array($value) ? implode(',', $value) : $value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $column . ' must contain a valid number');
				}
				break;
			case 'alpha':
				if (!ctype_alpha($value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $column . ' must contain only alphabets');
				}
				break;
			case 'alnum':
			case 'alpha_number':
				if (!ctype_alnum($value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $column . ' must contain only alphanumeric characters');
				}
				break;
		}
		
		// Check minimum and maximum lengths.
		$length = is_scalar($value) ? iconv_strlen($value, 'UTF-8') : (is_countable($value) ? count($value) : 1);
		if (isset($this->minlength) && $this->minlength > 0 && $length < $this->minlength)
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $column . ' must contain no less than ' . $this->minlength . ' characters');
		}
		if (isset($this->maxlength) && $this->maxlength > 0 && $length > $this->maxlength)
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $column . ' must contain no more than ' . $this->minlength . ' characters');
		}
	}
}
