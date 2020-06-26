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
		if (!isset($this->column) || !isset($this->operation))
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Invalid invocation of getQueryStringAndParams()');
		}
		
		// Initialze the return values.
		$where = '';
		$params = array();
		
		// Process the variable or default value.
		if ($this->var && Query::isValidVariable($args[$this->var]))
		{
			$this->filterValue($args[$this->var]);
			$is_expression = false;
			$value = $args[$this->var];
		}
		elseif ($this->default !== null)
		{
			list($is_expression, $value) = $this->getDefaultValue();
		}
		elseif ($this instanceof Query)
		{
			$is_expression = true;
			$value = '(' . $this->getQueryString($prefix, $args) . ') AS ' . Query::quoteName($this->alias);
			$params = $this->getQueryParams();
		}
		elseif ($this->not_null)
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' is not set');
		}
		else
		{
			return [$where, $params];
		}
		
		// Quote the column name.
		$column = Query::quoteName($this->column);
		
		// Prepare the target value.
		$list_ops = array('in' => true, 'notin' => true, 'not_in' => true, 'between' => true);
		if (isset($list_ops[$this->operation]) && !is_array($value) && $value !== '')
		{
			$value = explode(',', preg_replace('/[\s\']/', '', $value));
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
				$count = count($value);
				$placeholders = implode(', ', array_fill(0, $count, '?'));
				$where = sprintf('%s IN (%s)', $column, $placeholders);
				foreach ($value as $item)
				{
					$params[] = $item;
				}
				break;
			case 'notin':
			case 'not_in':
				$count = count($value);
				$placeholders = implode(', ', array_fill(0, $count, '?'));
				$where = sprintf('%s NOT IN (%s)', $column, $placeholders);
				foreach ($value as $item)
				{
					$params[] = $item;
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
		if ($this->var && Query::isValidVariable($args[$this->var]))
		{
			$is_expression = false;
			$value = $args[$this->var];
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
		// If the default value is a column name, escape it.
		if (strpos($this->default, '.') !== false && Query::isValidColumnName($this->default))
		{
			return [true, Query::quoteName($this->default)];
		}
		elseif (isset($this->column) && preg_match('/_srl$/', $this->column) && !ctype_digit($this->default))
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
			case 'date()':
				return [false, date('YmdHis')];
			case 'sequence()':
				return [false, getNextSequence()];
		}
		
		// If the default value is a calculation based on the current value, return a query string.
		if (isset($this->column) && preg_match('/^(plus|minus|multiply)\(([0-9]+)\)$/', $this->default, $matches))
		{
			switch ($matches[1])
			{
				case 'plus':
					return [true, sprintf('%s + %d', Query::quoteName($this->column), $matches[2])];
				case 'minus':
					return [true, sprintf('%s - %d', Query::quoteName($this->column), $matches[2])];
				case 'multiply':
					return [true, sprintf('%s * %d', Query::quoteName($this->column), $matches[2])];
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
		// Apply filters.
		switch (isset($this->filter) ? $this->filter : '')
		{
			case 'email':
			case 'email_address':
				if (!preg_match('/^[\w-]+((?:\.|\+|\~)[\w-]+)*@[\w-]+(\.[\w-]+)+$/', $value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' must contain a valid e-mail address');
				}
				break;
			case 'homepage':
			case 'url':
				if (!preg_match('/^(http|https)+(:\/\/)+[0-9a-z_-]+\.[^ ]+$/i', $value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' must contain a valid URL');
				}
				break;
			case 'userid':
			case 'user_id':
				if (!preg_match('/^[a-zA-Z]+([_0-9a-zA-Z]+)*$/', $value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' must contain a valid user ID');
				}
				break;
			case 'number':
			case 'numbers':
				if (!preg_match('/^(-?)[0-9]+(,\-?[0-9]+)*$/', is_array($value) ? implode(',', $value) : $value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' must contain a valid number');
				}
				break;
			case 'alpha':
				if (!ctype_alpha($value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' must contain only alphabets');
				}
				break;
			case 'alnum':
			case 'alpha_number':
				if (!ctype_alnum($value))
				{
					throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' must contain only alphanumeric characters');
				}
				break;
		}
		
		// Check minimum and maximum lengths.
		$length = iconv_strlen($value, 'UTF-8');
		if (isset($this->minlength) && $this->minlength > 0 && $length < $this->minlength)
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' must contain no less than ' . $this->minlength . ' characters');
		}
		if (isset($this->maxlength) && $this->maxlength > 0 && $length > $this->maxlength)
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' must contain no more than ' . $this->minlength . ' characters');
		}
	}
}
