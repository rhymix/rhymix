<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Condition class.
 */
class Condition extends VariableBase
{
	public $operation;
	public $column;
	public $var;
	public $ifvar;
	public $default;
	public $not_null;
	public $filter;
	public $minlength = 0;
	public $maxlength = 0;
	public $pipe = 'AND';
}
