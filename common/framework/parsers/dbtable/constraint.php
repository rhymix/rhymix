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
	public $on_update;
	public $on_delete;
}
