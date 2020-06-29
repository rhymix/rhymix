<?php

namespace Rhymix\Framework\Exceptions;

/**
 * The DB Error exception class.
 */
class DBError extends \Rhymix\Framework\Exception
{
	public function __construct($message = '', $code = 0, $previous = null)
	{
		if ($message === '')
		{
			$message = 'DB Error';
		}
		parent::__construct($message, $code, $previous);
	}
}
