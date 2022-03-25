<?php

namespace Rhymix\Framework\Parsers\DBTable;

/**
 * Constraint class.
 */
class Constraint
{
	public $type;
	public $column;
	public $references;
	public $condition;
	public $on_delete = 'RESTRICT';
	public $on_update = 'RESTRICT';
}
