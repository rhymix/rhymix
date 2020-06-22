<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * OrderBy class.
 */
class OrderBy extends VariableBase
{
	public $var;
	public $default;
	public $order_var;
	public $order_default = 'ASC';
}
