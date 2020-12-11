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
	 * @param array $tokens
	 * @return \stdClass
	 */
	public function send(\Rhymix\Framework\Push $message, array $tokens): \stdClass
	{
		$output = new \stdClass;
		$output->success = [];
		$output->invalid = [];
		$output->needUpdate = [];

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
		if($message->getClickAction())
		{
			$notification['click_action'] = $message->getClickAction();
		}

		$chunked_token = array_chunk($tokens, 1000);
		foreach($chunked_token as $token_unit)
		{
			$data = json_encode(array(
				'registration_ids' => $token_unit,
				'notification' => $notification,
				'priority' => 'normal',
				'data' => $message->getData() ?: new \stdClass,
			));

			$response = \FileHandler::getRemoteResource($url, $data, 5, 'POST', 'application/json', $headers);
			if($response)
			{
				$decoded_response = json_decode($response);
				if(!$decoded_response)
				{
					$message->addError('FCM return invalid json : '. $response);
					return $output;
				}
				$results = $decoded_response->results ?: [];
				foreach($results as $i => $result)
				{
					if($result->error)
					{
						$message->addError('FCM error code: '. $result->error);
						$output->invalid[$token_unit[$i]] = $token_unit[$i];
					}
					else if($result->message_id && $result->registration_id)
					{
						$output->needUpdate[$token_unit[$i]] = $result->registration_id;
					}
					else
					{
						$output->success[$token_unit[$i]] = $result->message_id;
					}
				}
			}
			else
			{
				$message->addError('FCM return empty response.');
			}
		}
		return $output;
	}
}
