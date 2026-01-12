<?php

namespace Rhymix\Framework\Drivers\Mail;

use Rhymix\Framework\Drivers\MailInterface;
use Rhymix\Framework\HTTP;
use Rhymix\Framework\Mail;

/**
 * The SparkPost mail driver.
 */
class SparkPost extends Base implements MailInterface
{
	/**
	 * The API URL.
	 */
	protected static $_url = 'https://api.sparkpost.com/api/v1/transmissions';

	/**
	 * Get the list of configuration fields required by this mail driver.
	 *
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array('api_token');
	}

	/**
	 * Get the SPF hint.
	 *
	 * @return string
	 */
	public static function getSPFHint()
	{
		return 'include:sparkpostmail.com';
	}

	/**
	 * Get the DKIM hint.
	 *
	 * @return string
	 */
	public static function getDKIMHint()
	{
		return '********._domainkey';
	}

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
		// Assemble the list of recipients.
		$recipients = array();
		if ($to = $message->message->getTo())
		{
			foreach($to as $address => $name)
			{
				$recipients[] = array('address' => array('name' => $name, 'email' => $address));
			}
		}
		if ($cc = $message->message->getCc())
		{
			foreach($cc as $address => $name)
			{
				$recipients[] = array('address' => array('name' => $name, 'email' => $address));
			}
		}
		if ($bcc = $message->message->getBcc())
		{
			foreach($bcc as $address => $name)
			{
				$recipients[] = array('address' => array('name' => $name, 'email' => $address));
			}
		}

		// Prepare data and options for Requests.
		$headers = array(
			'Authorization' => $this->_config['api_token'],
			'Content-Type' => 'application/json',
			'User-Agent' => 'PHP',
		);
		$data = json_encode(array(
			'options' => array(
				'transactional' => true,
			),
			'recipients' => $recipients,
			'content' => array(
				'email_rfc822' => $message->message->toString(),
			),
		));
		$settings = array(
			'timeout' => 5,
		);

		// Send the API request.
		$request = HTTP::post(self::$_url, $data, $headers, [], $settings);
		$result = @json_decode($request->getBody()->getContents());

		// Parse the result.
		if (!$result)
		{
			$message->errors[] = 'SparkPost: Connection error: ' . $request->body;
			return false;
		}
		elseif ($result->errors)
		{
			foreach ($result->errors as $error)
			{
				$message->errors[] = 'SparkPost: ' . $error->message . ': ' . $error->description . ' (code ' . $error->code . ')';
			}
		}

		if ($result->results)
		{
			return $result->results->total_accepted_recipients > 0 ? true : false;
		}
		else
		{
			return false;
		}
	}
}
