<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * ColumnRead class.
 */
class ColumnRead
{
	public $name;
	public $alias;
	public $ifvar;
	public $is_expression = false;
	public $is_wildcard = false;
}
