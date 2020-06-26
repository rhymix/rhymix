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
	 * @return array
	 */
	public function getQueryStringAndParams(array $args): array
	{
		// Return if this method is called on an invalid child class.
		if (!isset($this->column) || !isset($this->operation))
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Invalid invocation of getQueryStringAndParams()');
		}
		
		// Process the variable or default value.
		if ($this->var && isset($args[$this->var]) && !empty($args[$this->var]))
		{
			$this->filterValue($args[$this->var]);
			$value = $args[$this->var];
		}
		elseif ($this->default !== null)
		{
			$value = $this->getDefaultValue();
		}
		elseif ($this->not_null)
		{
			throw new \Rhymix\Framework\Exceptions\QueryError('Variable ' . $this->var . ' for column ' . $this->column . ' is not set');
		}
		else
		{
			return ['', []];
		}
		
		// Quote the column name.
		$column = Query::quoteName($this->column);
		$where = '';
		$params = array();
		
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
				$where = sprintf('%s = ?', $column);
				$params[] = $value;
				break;
			case 'like':
				$where = sprintf('%s LIKE ?', $column);
				$params[] = '%' . $value . '%';
				break;
			case 'like_prefix':
			case 'like_head':
				$where = sprintf('%s LIKE ?', $column);
				$params[] = $value . '%';
				break;
			case 'like_suffix':
			case 'like_tail':
				$where = sprintf('%s LIKE ?', $column);
				$params[] = '%' . $value;
				break;
			case 'notlike':
				$where = sprintf('%s NOT LIKE ?', $column);
				$params[] = '%' . $value . '%';
				break;
			case 'notlike_prefix':
			case 'notlike_head':
				$where = sprintf('%s NOT LIKE ?', $column);
				$params[] = $value . '%';
				break;
			case 'notlike_suffix':
			case 'notlike_tail':
				$where = sprintf('%s NOT LIKE ?', $column);
				$params[] = '%' . $value;
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
		}
		
		// Return the complete condition and parameters.
		return [$where, $params];
	}
	
	/**
	 * Get the default value of this variable.
	 * 
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		// If the default value is a column name, escape it.
		if (preg_match('/^[a-z0-9_]+(?:\.[a-z0-9_]+)+$/', $this->default))
		{
			return Query::quoteName($this->default);
		}
		elseif (isset($this->column) && preg_match('/_srl$/', $this->column) && !ctype_digit($this->default))
		{
			return Query::quoteName($this->default);
		}
		
		// If the default value is a function shortcut, return an appropriate value.
		switch ($this->default)
		{
			case 'ipaddress()':
				return "'" . \RX_CLIENT_IP . "'";
			case 'unixtime()':
				return time();
			case 'curdate()':
			case 'date()':
				return "'" . date('YmdHis') . "'";
			case 'sequence()':
				return getNextSequence();
		}
		
		// If the default value is a calculation based on the current value, return a query string.
		if (isset($this->column) && preg_match('/^(plus|minus|multiply)\(([0-9]+)\)$/', $this->default, $matches))
		{
			switch ($matches[1])
			{
				case 'plus':
					return sprintf('%s + %d', Query::quoteName($this->column), $matches[2]);
				case 'minus':
					return sprintf('%s - %d', Query::quoteName($this->column), $matches[2]);
				case 'multiply':
					return sprintf('%s * %d', Query::quoteName($this->column), $matches[2]);
			}
		}
		
		// Otherwise, just return the literal value.
		return $this->default;
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
