<?php

class Argument {

	var $value;
	var $name;
	var $type;
	var $isValid;
	var $errorMessage;
	var $column_operation;
	
	var $uses_default_value; // Check if arg value is user submnitted or default
	var $_value; // Caches escaped and toString value so that the parsing won't happen multiple times;

	function Argument($name, $value) {
		$this->value = $value;
		$this->name = $name;
		$this->isValid = true;
	}

	function getType() {
		if (isset($this->type))
			return $this->type;
		if (is_string($this->value))
			return 'column_name';
		return 'number';
	}

	function setColumnType($value) {
		$this->type = $value;
	}

	function setColumnOperation($operation) {
		$this->column_operation = $operation;
	}

	function getName() {
		return $this->name;
	}

	function getValue() {
		if (!isset($this->_value)) {
			$value = $this->getEscapedValue();
			$this->_value = $this->toString($value);
		}
		return $this->_value;
	}

	function getColumnOperation() {
		return $this->column_operation;
	}

	function getEscapedValue() {
		return $this->escapeValue($this->value);
	}

	function getUnescapedValue() {
		return $this->value;
	}

	function toString($value) {
		if (is_array($value)) {
			if (count($value) === 0)
				return '';
			if (count($value) === 1 && $value[0] === '')
				return '';
			return '(' . implode(',', $value) . ')';
		}
		return $value;
	}

	function escapeValue($value) {
		$column_type = $this->getType();
		if ($column_type == 'column_name') {
			$dbParser = DB::getParser();
			return $dbParser->parseExpression($value);
		}
		if (!isset($value))
			return null;

		if (in_array($column_type, array('date', 'varchar', 'char', 'text', 'bigtext'))) {
			if (!is_array($value))
				$value = $this->_escapeStringValue($value);
			else {
				$total = count($value);
				for ($i = 0; $i < $total; $i++)
					$value[$i] = $this->_escapeStringValue($value[$i]);
				//$value[$i] = '\''.$value[$i].'\'';
			}
		}
		if($this->uses_default_value) return $value;
		if ($column_type == 'number') {
			if (is_array($value)) {
				foreach ($value AS $key => $val) {
					if (isset($val) && $val !== '') {
						$value[$key] = (int) $val;
					}
				}
			} else {
				$value = (int) $value;
			}
		}

		return $value;
	}

	function _escapeStringValue($value) {
		$db = &DB::getInstance();
		$value = $db->addQuotes($value);
		return '\'' . $value . '\'';
	}

	function isValid() {
		return $this->isValid;
	}

	function getErrorMessage() {
		return $this->errorMessage;
	}

	function ensureDefaultValue($default_value) {
		if (!isset($this->value) || $this->value == '')
		{
			$this->value = $default_value;
			$this->uses_default_value = true;
		}
	}

	function checkFilter($filter_type) {
		if (isset($this->value) && $this->value != '') {
			global $lang;
			$val = $this->value;
			$key = $this->name;
			switch ($filter_type) {
				case 'email' :
				case 'email_address' :
					if (!preg_match('/^[_0-9a-z-]+(\.[_0-9a-z-]+)*@[0-9a-z-]+(\.[0-9a-z-]+)*$/is', $val)) {
						$this->isValid = false;
						$this->errorMessage = new Object(-1, sprintf($lang->filter->invalid_email, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'homepage' :
					if (!preg_match('/^(http|https)+(:\/\/)+[0-9a-z_-]+\.[^ ]+$/is', $val)) {
						$this->isValid = false;
						$this->errorMessage = new Object(-1, sprintf($lang->filter->invalid_homepage, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'userid' :
				case 'user_id' :
					if (!preg_match('/^[a-zA-Z]+([_0-9a-zA-Z]+)*$/is', $val)) {
						$this->isValid = false;
						$this->errorMessage = new Object(-1, sprintf($lang->filter->invalid_userid, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'number' :
				case 'numbers' :
					if (is_array($val))
						$val = join(',', $val);
					if (!preg_match('/^(-?)[0-9]+(,\-?[0-9]+)*$/is', $val)) {
						$this->isValid = false;
						$this->errorMessage = new Object(-1, sprintf($lang->filter->invalid_number, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'alpha' :
					if (!preg_match('/^[a-z]+$/is', $val)) {
						$this->isValid = false;
						$this->errorMessage = new Object(-1, sprintf($lang->filter->invalid_alpha, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
				case 'alpha_number' :
					if (!preg_match('/^[0-9a-z]+$/is', $val)) {
						$this->isValid = false;
						$this->errorMessage = new Object(-1, sprintf($lang->filter->invalid_alpha_number, $lang->{$key} ? $lang->{$key} : $key));
					}
					break;
			}
		}
	}

	function checkMaxLength($length) {
		if ($this->value && (strlen($this->value) > $length)) {
			global $lang;
			$this->isValid = false;
			$key = $this->name;
			$this->errorMessage = new Object(-1, sprintf($lang->filter->outofrange, $lang->{$key} ? $lang->{$key} : $key));
		}
	}

	function checkMinLength($length) {
		if ($this->value && (strlen($this->value) < $length)) {
			global $lang;
			$this->isValid = false;
			$key = $this->name;
			$this->errorMessage = new Object(-1, sprintf($lang->filter->outofrange, $lang->{$key} ? $lang->{$key} : $key));
		}
	}

	function checkNotNull() {
		if (!isset($this->value)) {
			global $lang;
			$this->isValid = false;
			$key = $this->name;
			$this->errorMessage = new Object(-1, sprintf($lang->filter->isnull, $lang->{$key} ? $lang->{$key} : $key));
		}
	}

}

?>
