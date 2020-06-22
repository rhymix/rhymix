<?php

namespace Rhymix\Framework\Parsers\DBTable;

/**
 * Column class.
 */
class Column
{
	public $name;
	public $type;
	public $xetype;
	public $size;
	public $utf8mb4 = true;
	public $default_value;
	public $not_null = false;
	public $is_primary_key = false;
	public $auto_increment = false;
}
