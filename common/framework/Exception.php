<?php

namespace Rhymix\Framework;

/**
 * The exception class.
 */
class Exception extends \Exception
{
	/**
	 * Get the file and line, skipping Rhymix framework files.
	 *
	 * This can be more helpful than just using getFile() and getLine()
	 * when the exception is thrown from a Rhymix framework file
	 * but the actual error is caused by a module or theme.
	 *
	 * @return string
	 */
	public function getUserFileAndLine(): string
	{
		$regexp = '!^' . preg_quote(\RX_BASEDIR, '!') . '(?:classes|common)/!';
		$trace = $this->getTrace();
		foreach ($trace as $frame)
		{
			if (!preg_match($regexp, $frame['file']))
			{
				return $frame['file'] . ':' . $frame['line'];
			}
		}

		return $this->getFile() . ':' . $this->getLine();
	}
}
