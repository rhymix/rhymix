<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Query class.
 */
class Query
{
	public $name;
	public $alias;
	public $type;
	public $tables = array();
	public $columns = array();
	public $conditions = array();
	public $groupby = null;
	public $navigation = null;
}
