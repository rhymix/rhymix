<?php

namespace Rhymix\Framework\Exceptions;

/**
 * The "must login" exception class.
 */
class MustLogin extends \Rhymix\Framework\Exception
{
	public function __construct($message = '', $code = 0, $previous = null)
	{
		if ($message === '')
		{
			$message = lang('msg_not_logged');
		}
		parent::__construct($message, $code, $previous);
	}
}
