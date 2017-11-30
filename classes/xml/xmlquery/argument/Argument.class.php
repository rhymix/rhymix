<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Argument class
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml/xmlquery/argument
 * @version 0.1
 */
class Argument
{

	/**
	 * argument value
	 * @var mixed
	 */
	var $value;

	/**
	 * argument name
	 * @var string
	 */
	var $name;

	/**
	 * argument type
	 * @var string
	 */
	var $type;

	/**
	 * result of argument type check
	 * @var bool
	 */
	var $isValid;

	/**
	 * error message
	 * @var BaseObject
	 */
	var $errorMessage;

	/**
	 * column operation
	 */
	var $column_operation;

	/**
	 * Check if arg value is user submnitted or default
	 * @var mixed
	 */
	var $uses_default_value;

	/**
	 * Caches escaped and toString value so that the parsing won't happen multiple times
	 * @var mixed
	 */
	var $_value; //

	/**
	 * constructor
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */

	function __construct($name, $value)
	{
		$this->value = $value;
		$this->name = $name;
		$this->isValid = TRUE;
	}

	function getType()
	{
		if(isset($this->type))
		{
			return $this->type;
		}
		if(is_string($this->value))
		{
			return 'column_name';
		}

		return 'number';
	}

	function setColumnType($value)
	{
		$this->type = $value;
	}

	function setColumnOperation($operation)
	{
		$this->column_operation = $operation;
	}

	function getName()
	{
		return $this->name;
	}

	function getValue()
	{
		if(!isset($this->_value))
		{
			$value = $this->getEscapedValue();
			$this->_value = $this->toString($value);
		}
		return $this->_value;
	}

	function getPureValue()
	{
		return $this->value;
	}

	function getColumnOperation()
	{
		return $this->column_operation;
	}

	function getEscapedValue()
	{
		return $this->escapeValue($this->value);
	}

	function getUnescapedValue()
	{
		if($this->value === 'null')
		{
			return null;
		}
		return $this->value;
	}

	/**
	 * mixed value to string
	 * @param mixed $value
	 * @return string
	 */
	function toString($value)
	{
		if(is_array($value))
		{
			if(count($value) === 0)
			{
				return '';
			}
			if(count($value) === 1 && $value[0] === '')
			{
				return '';
			}
			return '(' . implode(',', $value) . ')';
		}
		return $value;
	}

	/**
	 * escape value
	 * @param mixed $value
	 * @return mixed
	 */
	function escapeValue($value)
	{
		$column_type = $this->getType();
		if($column_type == 'column_name')
		{
			$dbParser = DB::getParser();
			return $dbParser->parseExpression($value);
		}
		if(!isset($value))
		{
			return null;
		}

		$columnTypeList = array('date' => 1, 'varchar' => 1, 'char' => 1, 'text' => 1, 'bigtext' => 1);
		if(isset($columnTypeList[$column_type]))
		{
			if(!is_array($value))
			{
				$value = $this->_escapeStringValue($value);
			}
			else
			{
				foreach($value as $key=>$val)
				{
					$value[$key] = $this->_escapeStringValue($val);
				}
			}
		}
		if($this->uses_default_value)
		{
			return $value;
		}
		if($column_type == 'number')
		{
			if(is_array($value))
			{
				foreach($value AS $key => $val)
				{
					if(isset($val) && $val !== '')
					{
						$value[$key] = (int) $val;
					}
				}
			}
			else
			{
				$value = (int) $value;
			}
		}

		return $value;
	}

	/**
	 * escape string value
	 * @param string $value
	 * @return string
	 */
	function _escapeStringValue($value)
	{
		// Remove non-utf8 chars.
		$regex = '@((?:[\x00-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}){1,100})|([\xF0-\xF7][\x80-\xBF]{3})|([\x80-\xBF])|([\xC0-\xFF])@x';

		$value = preg_replace_callback($regex, array($this, 'utf8Replacer'), $value);
		$db = DB::getInstance();
		$value = $db->addQuotes($value);
		return '\'' . $value . '\'';
	}

	function utf8Replacer($captures)
	{
		if(strlen($captures[1]))
		{
			// Valid byte sequence. Return unmodified.
			return $captures[1];
		}
		else if(strlen($captures[2]))
		{
			// Remove user defined area
			if("\xF3\xB0\x80\x80" <= $captures[2])
			{
				return;
			}

			return $captures[2];
		}
		else
		{
			return;
		}
	}

	function isValid()
	{
		return $this->isValid;
	}

	function isColumnName()
	{
		$type = $this->getType();
		$value = $this->getUnescapedValue();
		if($type == 'column_name')
		{
			return TRUE;
		}
		if($type == 'number' && is_null($value))
		{
			return FALSE;
		}
		if($type == 'number' && !is_numeric($value) && $this->uses_default_value)
		{
			return TRUE;
		}
		return FALSE;
	}

	function getErrorMessage()
	{
		return $this->errorMessage;
	}

	function ensureDefaultValue($default_value)
	{
		if($this->value === NULL || $this->value === '')
		{
			$this->value = $default_value;
			$this->uses_default_value = TRUE;
		}
	}

	/**
	 * check filter by filter type
	 * @param string $filter_type
	 * @return void
	 */
	function checkFilter($filter_type)
	{
		if(isset($this->value) && $this->value != '')
		{
			global $lang;
			$val = $this->value;
			$key = $this->name;
			switch($filter_type)
			{
				case 'email' :
				case 'email_address' :
					if(!preg_match('/^[\w-]+((?:\.|\+|\~)[\w-]+)*@[\w-]+(\.[\w-]+)+$/is', $val))
					{
						$this->isValid = FALSE;
						$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->invalid_email, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'homepage' :
					if(!preg_match('/^(http|https)+(:\/\/)+[0-9a-z_-]+\.[^ ]+$/is', $val))
					{
						$this->isValid = FALSE;
						$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->invalid_homepage, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'userid' :
				case 'user_id' :
					if(!preg_match('/^[a-zA-Z]+([_0-9a-zA-Z]+)*$/is', $val))
					{
						$this->isValid = FALSE;
						$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->invalid_userid, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'number' :
				case 'numbers' :
					if(is_array($val))
					{
						$val = join(',', $val);
					}
					if(!preg_match('/^(-?)[0-9]+(,\-?[0-9]+)*$/is', $val))
					{
						$this->isValid = FALSE;
						$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->invalid_number, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'alpha' :
					if(!preg_match('/^[a-z]+$/is', $val))
					{
						$this->isValid = FALSE;
						$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->invalid_alpha, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'alpha_number' :
					if(!preg_match('/^[0-9a-z]+$/is', $val))
					{
						$this->isValid = FALSE;
						$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->invalid_alpha_number, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
			}
		}
	}

	function checkMaxLength($length)
	{
		if($this->value && (strlen($this->value) > $length))
		{
			global $lang;
			$this->isValid = FALSE;
			$key = $this->name;
			$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->outofrange, $lang->{$key} ? $lang->{$key} : $key));
		}
	}

	function checkMinLength($length)
	{
		if($this->value && (strlen($this->value) < $length))
		{
			global $lang;
			$this->isValid = FALSE;
			$key = $this->name;
			$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->outofrange, $lang->{$key} ? $lang->{$key} : $key));
		}
	}

	function checkNotNull()
	{
		if(!isset($this->value))
		{
			global $lang;
			$this->isValid = FALSE;
			$key = $this->name;
			$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->isnull, $lang->{$key} ? $lang->{$key} : $key));
		}
	}

}
/* End of file Argument.class.php */
/* Location: ./classes/xml/xmlquery/argument/Argument.class.php */
