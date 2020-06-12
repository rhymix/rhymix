<?php

namespace Rhymix\Framework\Drivers\Push;

/**
 * The dummy Push driver.
 */
class Dummy extends Base implements \Rhymix\Framework\Drivers\PushInterface
{
	/**
	 * Sent messages are stored here for debugging and testing.
	 */
	protected $_sent_messages = array();
	
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
        $this->_sent_messages[] = $message;
		return true;
	}
	
	/**
	 * Get sent messages.
	 * 
	 * @return array
	 */
	public function getSentMessages(): array
	{
		return $this->_sent_messages;
	}
	
	/**
	 * Reset sent messages.
	 * 
	 * @return void
	 */
	public function resetSentMessages(): void
	{
		$this->_sent_messages = array();
	}
}
