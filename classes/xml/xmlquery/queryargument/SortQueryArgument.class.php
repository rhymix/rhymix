<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * SortQueryArgument class
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml/xmlquery/queryargument
 * @version 0.1
 */
class SortQueryArgument extends QueryArgument
{

	/**
	 * Change SortQueryArgument object to string
	 * @return string
	 */
	function toString()
	{
		$arg = sprintf("\n" . '${\'%s_argument\'} = new SortArgument(\'%s\', %s);' . "\n"
				, $this->argument_name
				, $this->argument_name
				, '$args->' . $this->variable_name);
		$arg .= $this->argument_validator->toString();

		$arg .= sprintf('if(!${\'%s_argument\'}->isValid()) return ${\'%s_argument\'}->getErrorMessage();' . "\n"
				, $this->argument_name
				, $this->argument_name
		);
		return $arg;
	}

}
/* End of file DefaultValue.class.php */
/* Location: ./classes/xml/xmlquery/queryargument/DefaultValue.class.php */
