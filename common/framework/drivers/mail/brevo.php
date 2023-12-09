<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The Brevo mail driver.
 */
class Brevo extends Base implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * The API URL.
	 */
	protected static $_url = 'https://api.brevo.com/v3/smtp/email';

	/**
	 * Get the list of configuration fields required by this mail driver.
	 *
	 * @return array
	 */
	public static function getRequiredConfig(): array
	{
		return ['api_key'];
	}

	/**
	 * Get the SPF hint.
	 *
	 * @return string
	 */
	public static function getSPFHint(): string
	{
		return 'include:spf.brevo.com';
	}

	/**
	 * Get the DKIM hint.
	 *
	 * @return string
	 */
	public static function getDKIMHint(): string
	{
		return 'mail._domainkey';
	}

	/**
	 * Check if the current mail driver is supported on this server.
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
	public function send(\Rhymix\Framework\Mail $message): bool
	{
		// Prepare data for Requests.
		$format_callback = function(string $address, ?string $name): array { return ['email' => $address, 'name' => $name]; };
		$from = $message->message->getFrom();
		$to = $message->message->getTo();
		$data = [
			'sender' => ['email' => array_first_key($from), 'name' => array_first($from)],
			'to' => array_map($format_callback, array_keys($to), array_values($to)),
			'subject' => $message->message->getSubject(),
			'htmlContent' => $message->message->getBody(),
		];
		if ($cc = $message->message->getCc())
		{
			$data['cc'] = array_map($format_callback, array_keys($cc), array_values($cc));
		}
		if ($bcc = $message->message->getBcc())
		{
			$data['bcc'] = array_map($format_callback, array_keys($bcc), array_values($bcc));
		}
		if ($reply_to = $message->message->getReplyTo())
		{
			$data['replyTo'] = ['email' => array_first_key($reply_to)];
		}
		foreach ($message->getAttachments() as $attachment)
		{
			$data['attachment'][] = [
				'content' => base64_encode(file_get_contents($attachment->local_filename)),
				'name' => $attachment->display_filename ?: $attachment->cid,
			];
		}
		
		// Prepare headers and options for Requests.
		$headers = [
			'api-key' => $this->_config['api_key'],
			'Content-Type' => 'application/json',
			'User-Agent' => 'PHP',
		];
		$options = [
			'timeout' => 8,
		];
		
		// Send the API request.
		$request = \Rhymix\Framework\HTTP::post(self::$_url, $data, $headers, [], $options);
		$status_code = $request->getStatusCode();
		$result = @json_decode($request->getBody()->getContents());

		// Parse the result.
		if (!$result)
		{
			$message->errors[] = 'Brevo: Connection error: ' . $request->getBody()->getContents();
			return false;
		}
		elseif ($status_code === 400)
		{
			$message->errors[] = 'Brevo: Bad request: ' . $result->message;
			return false;
		}
		else
		{
			return true;
		}
	}
}
