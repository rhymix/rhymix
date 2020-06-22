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
	 * Get the value of this variable.
	 * 
	 * @return Expression
	 */
	public function getValue(): Expression
	{
		if ($this->var)
		{
			return new Expression('var', $this->var);
		}
		else
		{
			return $this->getDefaultValue();
		}
	}
	
	/**
	 * Get the default value of this variable.
	 * 
	 * @return Expression
	 */
	public function getDefaultValue(): Expression
	{
		// If the default value is not set, return null.
		$val = $this->default;
		if ($val === null)
		{
			return new Expression('null');
		}
		
		// If the default value is a function shortcut, return an appropriate value.
		switch ($val)
		{
			case 'ipaddress()':
				return new Expression('string', \RX_CLIENT_IP);
			case 'unixtime()':
				return new Expression('string', time());
			case 'curdate()':
			case 'date()':
				return new Expression('string', date('YmdHis'));
			case 'sequence()':
				return new Expression('int', getNextSequence());
		}
		
		// If the default value is a calculation based on the current value, return a query string.
		if (isset($this->column) && preg_match('/^(plus|minus|multiply)\(([0-9]+)\)$/', $val, $matches))
		{
			
		}
		
		// If the default value is a column name, return the column name.20
		if (\Rhymix\Framework\Parsers\DBQueryParser::isValidColumnName($val))
		{
			
		}
		
		// Otherwise, return the literal value.
		return new Expression('string', $val);
	}
}
