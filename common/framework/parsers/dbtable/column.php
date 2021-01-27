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
	public $charset = 'utf8mb4';
	public $default_value;
	public $not_null = false;
	public $is_indexed = false;
	public $is_unique = false;
	public $is_primary_key = false;
	public $auto_increment = false;
}
