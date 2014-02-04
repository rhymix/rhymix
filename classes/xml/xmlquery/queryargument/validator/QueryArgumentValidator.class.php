<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * QueryArgumentValidator class
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml/xmlquery/queryargument/validator
 * @version 0.1
 */
class QueryArgumentValidator
{

	/**
	 * Argument name
	 * @var string
	 */
	var $argument_name;

	/**
	 * Default value
	 * @var string
	 */
	var $default_value;

	/**
	 * Notnull status setting, if value should be not null, this value is 'notnull'
	 * @var string
	 */
	var $notnull;

	/**
	 * Filter for value type, for example number
	 * @var string
	 */
	var $filter;

	/**
	 * Minimum length for value
	 * @var int
	 */
	var $min_length;

	/**
	 * Maximum length for value
	 * @var int
	 */
	var $max_length;
	var $validator_string;

	/**
	 * Query argument for validate
	 * @var QueryArgument object
	 */
	var $argument;

	/**
	 * constructor
	 * @param Xml_Node_ $tag tag object by Query xml file parse
	 * @param QueryArgument $argument
	 * @return void
	 */
	function QueryArgumentValidator($tag, $argument)
	{
		$this->argument = $argument;
		$this->argument_name = $this->argument->getArgumentName();

		$this->default_value = $tag->attrs->default;
		$this->notnull = $tag->attrs->notnull;
		$this->filter = $tag->attrs->filter;
		$this->min_length = $tag->attrs->min_length;
		$this->max_length = $tag->attrs->max_length;
	}

	function isIgnorable()
	{
		if(isset($this->default_value) || isset($this->notnull))
		{
			return FALSE;
		}
		return TRUE;
	}

	function toString()
	{
		$validator = '';
		if($this->filter)
		{
			$validator .= sprintf('${\'%s_argument\'}->checkFilter(\'%s\');' . "\n"
					, $this->argument_name
					, $this->filter
			);
		}
		if($this->min_length)
		{
			$validator .= sprintf('${\'%s_argument\'}->checkMinLength(%s);' . "\n"
					, $this->argument_name
					, $this->min_length
			);
		}
		if($this->max_length)
		{
			$validator .= sprintf('${\'%s_argument\'}->checkMaxLength(%s);' . "\n"
					, $this->argument_name
					, $this->max_length
			);
		}
		if(isset($this->default_value))
		{
			$this->default_value = new DefaultValue($this->argument_name, $this->default_value);
			if($this->default_value->isSequence())
				$validator .= '$db = DB::getInstance(); $sequence = $db->getNextSequence(); ';
			if($this->default_value->isOperation())
			{
				$validator .= sprintf('${\'%s_argument\'}->setColumnOperation(\'%s\');' . "\n"
						, $this->argument_name
						, $this->default_value->getOperation()
				);
			}
			$validator .= sprintf('${\'%s_argument\'}->ensureDefaultValue(%s);' . "\n"
					, $this->argument_name
					, $this->default_value->toString()
			);
		}
		if($this->notnull)
		{
			$validator .= sprintf('${\'%s_argument\'}->checkNotNull();' . "\n"
					, $this->argument_name
			);
		}
		return $validator;
	}

}
/* End of file QueryArgumentValidator.class.php */
/* Location: ./classes/xml/xmlquery/queryargument/validator/QueryArgumentValidator.class.php */
