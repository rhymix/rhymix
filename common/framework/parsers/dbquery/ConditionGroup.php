<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Condition Group class.
 */
class ConditionGroup
{
	public $conditions = array();
	public $pipe = 'AND';
	public $ifvar;
}
