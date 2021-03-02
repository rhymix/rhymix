<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * GroupBy class.
 */
class IndexHint
{
	public $target_db = array();
	public $hint_type = '';
	public $index_name = '';
	public $table_name = '';
	public $var;
	public $ifvar;
}
