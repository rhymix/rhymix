<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The Mailgun mail driver.
 */
class Mailgun extends Base implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * The API URL.
	 */
	protected static $_url = 'https://api.mailgun.net/v3';
	
	/**
	 * Get the list of configuration fields required by this mail driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array('api_domain', 'api_token');
	}
	
	/**
	 * Get the SPF hint.
	 * 
	 * @return string
	 */
	public static function getSPFHint()
	{
		return 'include:mailgun.org';
	}
	
	/**
	 * Get the DKIM hint.
	 * 
	 * @return string
	 */
	public static function getDKIMHint()
	{
		return 'mailo._domainkey';
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
				$recipients[] = $address;
			}
		}
		if ($cc = $message->message->getCc())
		{
			foreach($cc as $address => $name)
			{
				$recipients[] = $address;
			}
		}
		if ($bcc = $message->message->getBcc())
		{
			foreach($bcc as $address => $name)
			{
				$recipients[] = $address;
			}
		}
		
		// Prepare data and options for Requests.
		$boundary = str_repeat('-', 24) . substr(md5(mt_rand()), 0, 16);
		$headers = array(
			'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
		);
		$data = implode("\r\n", array(
			'--' . $boundary,
			'Content-Disposition: form-data; name="to"',
			'',
			implode(', ', $recipients),
			'--' . $boundary,
			'Content-Disposition: attachment; name="message"; filename="message.eml"',
			'Content-Type: message/rfc822',
			'Content-Transfer-Encoding: binary',
			'',
			$message->message->toString(),
			'--' . $boundary . '--',
			'',
		));
		$options = array(
			'auth' => array('api', $this->_config['api_token']),
			'timeout' => 5,
			'useragent' => 'PHP',
		);
		
		// Send the API request.
		$url = self::$_url . '/' . $this->_config['api_domain'] . '/messages.mime';
		$request = \Requests::post($url, $headers, $data, $options);
		$result = @json_decode($request->body);
		
		// Parse the result.
		if (!$result)
		{
			$message->errors[] = 'Mailgun: Connection error: ' . $request->body;
			return false;
		}
		elseif (!$result->id)
		{
			$message->errors[] = 'Mailgun: ' . $result->message;
			return false;
		}
		else
		{
			return true;
		}
	}
}
