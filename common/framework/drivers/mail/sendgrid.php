<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The SendGrid mail driver.
 */
class SendGrid extends Base implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * The API URL.
	 */
	protected static $_url = 'https://api.sendgrid.com/v3/mail/send';
	
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
		return 'include:sendgrid.net';
	}
	
	/**
	 * Get the DKIM hint.
	 * 
	 * @return string
	 */
	public static function getDKIMHint()
	{
		return 'smtpapi._domainkey';
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
		// Check API token.
		if (!isset($this->_config['api_token']) || !$this->_config['api_token'])
		{
			$message->errors[] = 'SendGrid: Please use API key (token) instead of username and password.';
			return;
		}
		
		// Initialize the request data.
		$data = [];
		$data['personalizations'] = [];
		
		// Assemble the list of recipients.
		$to_list = [];
		if ($to = $message->message->getTo())
		{
			foreach($to as $address => $name)
			{
				$to_list[] = ['email' => $address, 'name' => $name];
			}
			$data['personalizations'][] = ['to' => $to_list];
		}
		$cc_list = [];
		if ($cc = $message->message->getCc())
		{
			foreach($cc as $address => $name)
			{
				$cc_list[] = ['email' => $address, 'name' => $name];
			}
			$data['personalizations'][] = ['cc' => $cc_list];
		}
		$bcc_list = [];
		if ($bcc = $message->message->getBcc())
		{
			foreach($bcc as $address => $name)
			{
				$bcc_list[] = ['email' => $address, 'name' => $name];
			}
			$data['personalizations'][] = ['bcc' => $bcc_list];
		}
		
		// Set the sender information.
		$from = $message->message->getFrom();
		if ($from)
		{
			$data['from']['email'] = array_first_key($from);
			if (array_first($from))
			{
				$data['from']['name'] = array_first($from);
			}
		}
		
		// Set the Reply-To address.
		$replyTo = $message->message->getReplyTo();
		if ($replyTo)
		{
			$data['reply_to'] = array_first_key($from);
		}
		
		// Set the subject.
		$data['subject'] = strval($message->getSubject()) ?: 'Title';
		
		// Set the body.
		$data['content'][0]['type'] = $message->getContentType();
		$data['content'][0]['value'] = $message->getBody();
		
		// Add attachments.
		foreach ($message->getAttachments() as $attachment)
		{
			$file_info = [];
			$file_info['filename'] = $attachment->display_filename;
			$file_info['content'] = base64_encode(file_get_contents($attachment->local_filename));
			$file_info['disposition'] = $attachment->type === 'attach' ? 'attachment' : 'inline';
			if ($attachment->type === 'embed')
			{
				$file_info['content_id'] = $attachment->cid;
			}
			$data['attachments'][] = $file_info;
		}
		
		// Prepare data and options for Requests.
		$headers = array(
			'Authorization' => 'Bearer ' . $this->_config['api_token'],
			'Content-Type' => 'application/json',
		);
		$options = array(
			'timeout' => 8,
			'useragent' => 'PHP',
		);
		
		// Send the API request.
		$request = \Requests::post(self::$_url, $headers, json_encode($data), $options);
		$response_code = intval($request->status_code);;
		
		// Parse the result.
		if (!$response_code)
		{
			$message->errors[] = 'SendGrid: Connection error: ' . $request->body;
			return false;
		}
		elseif ($response_code > 202)
		{
			$message->errors[] = 'SendGrid: Response code ' . $response_code . ': ' . $request->body;
			return false;
		}
		
		return true;
	}
}
