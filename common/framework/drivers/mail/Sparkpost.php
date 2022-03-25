<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The SparkPost mail driver.
 */
class SparkPost extends Base implements \Rhymix\Framework\Drivers\MailInterface
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
	 * @param object $message
	 * @return bool
	 */
	public function send(\Rhymix\Framework\Mail $message)
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
		$options = array(
			'timeout' => 5,
			'useragent' => 'PHP',
		);
		
		// Send the API request.
		$request = \Requests::post(self::$_url, $headers, $data, $options);
		$result = @json_decode($request->body);
		
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
