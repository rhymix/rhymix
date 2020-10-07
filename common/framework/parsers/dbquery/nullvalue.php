<?php

namespace Rhymix\Framework\Parsers\DBQuery;

/**
 * Null value class.
 */
class NullValue
{
	public function __toString(): string
	{
		return 'NULL';
	}
}
