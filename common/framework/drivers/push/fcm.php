<?php

namespace Rhymix\Framework\Drivers\Push;

/**
 * The FCM (Google) Push driver.
 */
class FCM extends Base implements \Rhymix\Framework\Drivers\PushInterface
{
	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('api_key');
	protected static $_optional_config = array();
	
	/**
	 * Get the human-readable name of this Push driver.
	 * 
	 * @return string
	 */
	public static function getName(): string
	{
		return 'Android (FCM)';
	}
	
	/**
	 * Check if the current Push driver is supported on this server.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public static function isSupported(): bool
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
	public function send(\Rhymix\Framework\Push $message): bool
	{
        // TODO
        return false;
	}
}
