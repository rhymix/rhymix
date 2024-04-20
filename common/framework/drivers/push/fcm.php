<?php

namespace Rhymix\Framework\Drivers\Push;

use Rhymix\Framework\HTTP;
use Rhymix\Framework\Push;
use Rhymix\Framework\Drivers\PushInterface;

/**
 * The FCM Legacy API Push driver.
 */
class FCM extends Base implements PushInterface
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
		return 'FCM Legacy API';
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
		$notification = $message->getMetadata();
		$subject = $message->getSubject();
		$content = $message->getContent();
		if ($subject !== '' || $content !== '')
		{
			$notification['title'] = $subject;
			$notification['body'] = $content;
		}
		if (count($notification))
		{
			$notification['sound'] = isset($notification['sound']) ? $notification['sound'] : 'default';
		}

		$chunked_token = array_chunk($tokens, 500);
		foreach($chunked_token as $token_unit)
		{
			$data = [
				'registration_ids' => $token_unit,
				'priority' => 'normal',
				'data' => $message->getData() ?: new \stdClass,
			];
			if (count($notification))
			{
				$data['notification'] = $notification;
			}

			$response = HTTP::request($url, 'POST', json_encode($data), $headers);
			if($response->getStatusCode() === 200)
			{
				$decoded_response = json_decode($response->getBody());
				if(!$decoded_response)
				{
					$message->addError('FCM error: Invalid Response: '. $response);
					return $output;
				}
				$results = $decoded_response->results ?: [];
				foreach($results as $i => $result)
				{
					if($result->error)
					{
						$message->addError('FCM error: '. $result->error);
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
				$message->addError('FCM error: HTTP ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());
			}
		}
		return $output;
	}
}
