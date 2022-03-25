<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * ColumnWrite class.
 */
class ColumnWrite extends VariableBase
{
	public $name;
	public $operation = 'equal';
	public $var;
	public $ifvar;
	public $default;
	public $not_null;
	public $filter;
	public $minlength = 0;
	public $maxlength = 0;
}
