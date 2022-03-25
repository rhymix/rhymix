<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The dummy mail driver.
 */
class Dummy extends Base implements \Rhymix\Framework\Drivers\MailInterface
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
	 * @param object $message
	 * @return bool
	 */
	public function send(\Rhymix\Framework\Mail $message)
	{
		return true;
	}
}
