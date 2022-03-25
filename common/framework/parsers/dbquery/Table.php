<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Table class.
 */
class Table
{
	public $name;
	public $alias;
	public $ifvar;
	public $join_type;
	public $join_conditions = array();
}
