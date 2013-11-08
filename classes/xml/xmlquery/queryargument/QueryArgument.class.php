<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * QueryArgument class
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml/xmlquery/queryargument
 * @version 0.1
 */
class QueryArgument
{

	/**
	 * Argument name
	 * @var string
	 */
	var $argument_name;

	/**
	 * Variable name
	 * @var string
	 */
	var $variable_name;

	/**
	 * Argument validator
	 * @var QueryArgumentValidator
	 */
	var $argument_validator;

	/**
	 * Column name
	 * @var string
	 */
	var $column_name;

	/**
	 * Table name
	 * @var string
	 */
	var $table_name;

	/**
	 * Operation
	 * @var string
	 */
	var $operation;

	/**
	 * Ignore value
	 * @var bool
	 */
	var $ignore_value;

	/**
	 * constructor
	 * @param object $tag tag object
	 * @param bool $ignore_value
	 * @return void
	 */
	function QueryArgument($tag, $ignore_value = FALSE)
	{
		static $number_of_arguments = 0;

		$this->argument_name = $tag->attrs->var;
		if(!$this->argument_name)
		{
			$this->argument_name = str_replace('.', '_', $tag->attrs->name);
		}
		if(!$this->argument_name)
		{
			$this->argument_name = str_replace('.', '_', $tag->attrs->column);
		}

		$this->variable_name = $this->argument_name;

		$number_of_arguments++;
		$this->argument_name .= $number_of_arguments;

		$name = $tag->attrs->name;
		if(!$name)
		{
			$name = $tag->attrs->column;
		}
		if(strpos($name, '.') === FALSE)
		{
			$this->column_name = $name;
		}
		else
		{
			list($prefix, $name) = explode('.', $name);
			$this->column_name = $name;
			$this->table_name = $prefix;
		}

		if($tag->attrs->operation)
		{
			$this->operation = $tag->attrs->operation;
		}

		$this->argument_validator = new QueryArgumentValidator($tag, $this);
		$this->ignore_value = $ignore_value;
	}

	function getArgumentName()
	{
		return $this->argument_name;
	}

	function getColumnName()
	{
		return $this->column_name;
	}

	function getTableName()
	{
		return $this->table_name;
	}

	function getValidatorString()
	{
		return $this->argument_validator->toString();
	}

	function isConditionArgument()
	{
		if($this->operation)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Change QueryArgument object to string
	 * @return string
	 */
	function toString()
	{
		if($this->isConditionArgument())
		{
			// Instantiation
			$arg = sprintf("\n" . '${\'%s_argument\'} = new ConditionArgument(\'%s\', %s, \'%s\');' . "\n"
					, $this->argument_name
					, $this->variable_name
					, '$args->' . $this->variable_name
					, $this->operation
			);
			// Call methods to validate argument and ensure default value
			$arg .= $this->argument_validator->toString();

			// Prepare condition string
			$arg .= sprintf('${\'%s_argument\'}->createConditionValue();' . "\n"
					, $this->argument_name
			);

			// Check that argument passed validation, else return
			$arg .= sprintf('if(!${\'%s_argument\'}->isValid()) return ${\'%s_argument\'}->getErrorMessage();' . "\n"
					, $this->argument_name
					, $this->argument_name
			);
		}
		else
		{
			$arg = sprintf("\n" . '${\'%s_argument\'} = new Argument(\'%s\', %s);' . "\n"
					, $this->argument_name
					, $this->variable_name
					, $this->ignore_value ? 'NULL' : '$args->{\'' . $this->variable_name . '\'}');

			$arg .= $this->argument_validator->toString();

			$arg .= sprintf('if(!${\'%s_argument\'}->isValid()) return ${\'%s_argument\'}->getErrorMessage();' . "\n"
					, $this->argument_name
					, $this->argument_name
			);
		}

		// If the argument is null, skip it
		if($this->argument_validator->isIgnorable())
		{
			$arg = sprintf("if(isset(%s)) {", '$args->' . $this->variable_name)
					. $arg
					. sprintf("} else\n" . '${\'%s_argument\'} = NULL;', $this->argument_name);
		}

		return $arg;
	}

}
/* End of file QueryArgument.class.php */
/* Location: ./classes/xml/xmlquery/queryargument/QueryArgument.class.php */
