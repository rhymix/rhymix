<?php

namespace Rhymix\Framework\Drivers\Push;

use Rhymix\Framework\HTTP;
use Rhymix\Framework\Push;
use Rhymix\Framework\Drivers\PushInterface;

/**
 * The FCM HTTP v1 API Push driver.
 */
class FCMv1 extends Base implements PushInterface
{
	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('service_account');
	protected static $_optional_config = array();

	/**
	 * Get the human-readable name of this Push driver.
	 *
	 * @return string
	 */
	public static function getName(): string
	{
		return 'FCM HTTP v1 API';
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
	 * @param array $tokens
	 * @return \stdClass
	 */
	public function send(Push $message, array $tokens): \stdClass
	{
		return new \stdClass;
	}
}
