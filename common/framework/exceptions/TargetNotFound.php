<?php

namespace Rhymix\Framework\Exceptions;

/**
 * The "target not found" exception class.
 */
class TargetNotFound extends \Rhymix\Framework\Exception
{
	public function __construct($message = '', $code = 0, $previous = null)
	{
		if ($message === '')
		{
			$message = lang('msg_not_founded');
		}
		parent::__construct($message, $code, $previous);
	}
}
