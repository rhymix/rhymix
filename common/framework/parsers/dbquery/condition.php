<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Condition class.
 */
class Condition extends GenericVar
{
	public $operation;
	public $column;
	public $var;
	public $default;
	public $not_null;
	public $operator = 'AND';
}
