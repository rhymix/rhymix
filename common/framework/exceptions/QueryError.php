<?php

namespace Rhymix\Framework\Exceptions;

/**
 * The Query Error exception class.
 */
class QueryError extends DBError
{
	public function __construct($message = '', $code = 0, $previous = null)
	{
		if ($message === '')
		{
			$message = 'Query Error';
		}
		parent::__construct($message, $code, $previous);
	}
}
