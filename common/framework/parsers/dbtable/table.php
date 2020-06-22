<?php

namespace Rhymix\Framework\Parsers\DBTable;

/**
 * Table class.
 */
class Table
{
	public $name;
	public $columns = array();
	public $indexes = array();
	public $primary_key = array();
	public $constraints = array();
}
