<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The NAVER Cloud Outbound Mailer mail driver.
 */
class Ncloud_Mailer extends Base implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * The API URL.
	 */
	protected static $_url = 'https://mail.apigw.ntruss.com/api/v1/mails';
	protected static $_timeout = 10;

	/**
	 * Get the human-readable name of this mail driver.
	 *
	 * @return string
	 */
	public static function getName()
	{
		return 'NAVER Cloud Outbound Mailer';
	}

	/**
	 * Get the list of configuration fields required by this mail driver.
	 *
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array('api_key', 'api_secret');
	}

	/**
	 * Get the SPF hint.
	 *
	 * @return string
	 */
	public static function getSPFHint()
	{
		return 'include:email.ncloud.com';
	}

	/**
	 * Get the DKIM hint.
	 *
	 * @return string
	 */
	public static function getDKIMHint()
	{
		return 'mailer._domainkey';
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
		return function_exists('hash_hmac');
	}

	/**
	 * Create signature for NAVER Cloud gateway server
	 *
	 * @param string $timestamp
	 * @param string $access_key
	 * @param string $secret_key
	 * @return string
	 */
	protected static function _makeSignature($timestamp, $access_key, $secret_key): string
	{
		$method = 'POST';
		$uri = '/api/v1/mails';
		$content = "$method $uri\n$timestamp\n$access_key";
		return base64_encode(hash_hmac('sha256', $content, $secret_key, true));
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
		// Prepare data for Requests.
		$data = array(
			'title' => $message->getSubject(),
			'body' => $message->getBody(),
			'senderAddress' => '',
			'senderName' => '',
			'recipients' => array(),
		);

		// Fill the sender info.
		$from = $message->message->getFrom();
		foreach($from as $email => $name)
		{
			$data['senderAddress'] = $email;
			$data['senderName'] = trim($name) ?: substr($email, 0, strpos($email, '@'));
			break;
		}

		// Fill the recipient info.
		if ($to = $message->message->getTo())
		{
			foreach($to as $email => $name)
			{
				$receiver = array();
				$receiver['address'] = $email;
				$receiver['name'] = str_replace(',', '', trim($name) ?: substr($email, 0, strpos($email, '@')));
				$receiver['type'] = "R";

				$data['recipients'][] = $receiver;
			}
		}
		if ($cc = $message->message->getCc())
		{
			foreach($cc as $email => $name)
			{
				$receiver = array();
				$receiver['address'] = $email;
				$receiver['name'] = str_replace(',', '', trim($name) ?: substr($email, 0, strpos($email, '@')));
				$receiver['type'] = "C";

				$data['recipients'][] = $receiver;
			}
		}
		if ($bcc = $message->message->getBcc())
		{
			foreach($bcc as $email => $name)
			{
				$receiver = array();
				$receiver['address'] = $email;
				$receiver['name'] = str_replace(',', '', trim($name) ?: substr($email, 0, strpos($email, '@')));
				$receiver['type'] = "B";

				$data['recipients'][] = $receiver;
			}
		}

		// Generate the NAVER cloud gateway signature.
		$timestamp = floor(microtime(true) * 1000);
		$signature = self::_makeSignature($timestamp, $this->_config['api_key'], $this->_config['api_secret']);
		$headers = array(
			'x-ncp-apigw-timestamp' => $timestamp,
			'x-ncp-iam-access-key' => $this->_config['api_key'],
			'x-ncp-apigw-signature-v2' => $signature,
		);

		// Send the API request.
		try
		{
			$request = \Rhymix\Framework\HTTP::post(self::$_url, [], $headers, [], [
				'timeout' => self::$_timeout,
				'json' => $data,
			]);
			$result = @json_decode($request->getBody()->getContents());
		}
		catch (\Exception $e)
		{
			$message->errors[] = 'NAVER Cloud Outbound Mailer: ' . $e->getMessage();
			return false;
		}

		if (isset($result->error))
		{
			$message->errors[] = 'NAVER Cloud Outbound Mailer: ' . $result->error->message . PHP_EOL . $result->error->details;
			return false;
		}
		else
		{
			return true;
		}
	}
}
