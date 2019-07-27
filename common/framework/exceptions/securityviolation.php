<?php

namespace Rhymix\Framework\Exceptions;

/**
 * The "security violation" exception class.
 */
class SecurityViolation extends \Rhymix\Framework\Exception
{
	public function __construct($message = '', $code = 0, $previous = null)
	{
		if ($message === '')
		{
			$message = lang('msg_security_violation');
		}
		parent::__construct($message, $code, $previous);
	}
}
