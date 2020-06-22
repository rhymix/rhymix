<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Expression class.
 */
class Expression
{
	/**
	 * Instance properties.
	 */
	public $type;
	public $value;
	
	/**
	 * Constructor.
	 * 
	 * @param string $type
	 * @param mixed $value
	 */
	public function __construct(string $type, $value = null)
	{
		$this->type = $type;
		$this->value = $value;
	}
	
	/**
	 * Return the string representation of this expression.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		switch ($this->type)
		{
			case 'var':
				return ':' . $this->value;
			case 'null':
				return 'NULL';
			case 'string':
				return;
			case 'int':
				return $this->value;
			
			
		}
	}
}
