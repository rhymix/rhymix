<?php

namespace Rhymix\Framework\Drivers\Push;

use message;
use stdClass;

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
	 * @param array $tokens
	 * @return bool
	 */
	public function send(\Rhymix\Framework\Push $message, array $tokens): bool
	{
		$status = true;

		$url = 'https://fcm.googleapis.com/fcm/send';
		$api_key = $this->_config['api_key'];
		$headers = array(
			'Authorization' => 'key=' . $api_key,
			'Content-Type' => 'application/json',
		);

		// Set notification
		$notification = [];
		$notification['title'] = $message->getSubject();
		$notification['body'] = $message->getContent();

		foreach($tokens as $i => $token)
		{
			$data = json_encode(array(
				'registration_ids' => [$token],
				'notification' => $notification,
				'priority' => 'normal',
				'data' => $message->getData() ?: new stdClass,
			));

			$result = \FileHandler::getRemoteResource($url, $data, 5, 'POST', 'application/json', $headers);
			if($result)
			{
				$error = json_decode($result)->error_code;
				if($error)
				{
					$message->addError('FCM error code: '. $error);
					$status = false;
				}
			}
			else
			{
				$message->addError('FCM return empty response.');
				$status = false;
			}
		}
		return $status;
	}
}
