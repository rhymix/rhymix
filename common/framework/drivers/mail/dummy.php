<?php

namespace Rhymix\Framework\Drivers\Mail;

use Rhymix\Framework\Drivers\MailInterface;
use Rhymix\Framework\Mail;

/**
 * The dummy mail driver.
 */
class Dummy extends Base implements MailInterface
{
	/**
	 * Check if the current mail driver is supported on this server.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @return bool
	 */
	public static function isSupported()
	{
		return true;
	}

	/**
	 * Send a message.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @param Mail $message
	 * @return bool
	 */
	public function send(Mail $message)
	{
		return true;
	}
}
