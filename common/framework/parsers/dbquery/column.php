<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Column class.
 */
class Column
{
	public $name;
	public $alias;
	public $is_expression = false;
	public $is_wildcard = false;
}
