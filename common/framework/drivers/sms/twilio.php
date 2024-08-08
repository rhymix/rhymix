<?php

namespace Rhymix\Framework\Drivers\SMS;
use Rhymix\Framework\HTTP;
use Rhymix\Framework\Storage;
use RHymix\Framework\URL;

/**
 * The Twilio SMS driver.
 */
class Twilio extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	/**
	 * API Base URL.
	 */
	const BASEURL = 'https://api.twilio.com/2010-04-01';

	/**
	 * API specifications.
	 */
	protected static $_spec = array(
		'max_recipients' => 100,
		'sms_max_length' => 70,
		'sms_max_length_in_charset' => 'UTF-16LE',
		'lms_supported' => true,
		'lms_supported_country_codes' => array(),
		'lms_max_length' => 1600,
		'lms_max_length_in_charset' => 'UTF-16LE',
		'lms_subject_supported' => false,
		'lms_subject_max_length' => 0,
		'mms_supported' => true,
		'mms_supported_country_codes' => array(),
		'mms_max_length' => 1600,
		'mms_max_length_in_charset' => 'UTF-16LE',
		'mms_subject_supported' => false,
		'mms_subject_max_length' => 0,
		'image_allowed_types' => array('jpg', 'gif', 'png'),
		'image_max_dimensions' => array(2048, 2048),
		'image_max_filesize' => 5000000,
		'delay_supported' => true,
	);

	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = ['account_sid', 'auth_token'];
	protected static $_optional_config = [];

	/**
	 * Check if the current SMS driver is supported on this server.
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
	 * @param array $messages
	 * @param object $original
	 * @return bool
	 */
	public function send(array $messages, \Rhymix\Framework\SMS $original)
	{
		$status = true;
		$url = sprintf('%s/Accounts/%s/Messages.json', self::BASEURL, $this->_config['account_sid']);
		$settings = [
			'auth' => [$this->_config['account_sid'], $this->_config['auth_token']],
			'timeout => 10',
		];

		foreach ($messages as $i => $message)
		{
			$from = '+' . preg_replace('/[^0-9]/', '', $message->from);
			foreach ($message->to as $recipient)
			{
				$data = [];
				$data['To'] = sprintf('+%s%s', $message->country ?: 82, preg_replace('/[^0-9]/', '', $recipient));
				$data['From'] = $from;
				$data['Body'] = $message->content;
				if ($message->delay && $message->delay > time())
				{
					$data['SendAt'] = gmdate('Y-m-d\TH:i:s\Z', $message->delay);
				}
				if ($message->type === 'MMS')
				{
					$data['SendAsMms'] = true;
				}
				if ($message->image && Storage::isFile($message->image))
				{
					$media_url = URL::fromServerPath($message->image);
					if ($media_url)
					{
						$data['MediaUrl'][] = $media_url;
					}
				}

				$request = HTTP::request($url, 'POST', $data, [], [], $settings);
				$status_code = $request->getStatusCode();
				if ($status_code < 200 || $status_code >= 400)
				{
					$response = $request->getBody()->getContents();
					if ($response)
					{
						$response = json_decode($response);
						$error_code = $response->error_code;
						$error_message = $response->error_message;
					}
					else
					{
						$error_code = null;
						$error_message = null;
					}
					$original->addError('Error (' . $status_code . ') while sending message ' . ($i + 1) . ' of ' . count($messages) .
						' to ' . $data['To'] . ': ' . trim($error_code . ' ' . $error_message));
					$status = false;
				}
			}
		}

		return $status;
	}
}
