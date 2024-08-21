<?php

namespace Rhymix\Framework\Drivers\SMS;
use Rhymix\Framework\HTTP;
use Rhymix\Framework\Security;

/**
 * The Solapi SMS driver.
 */
class SolAPI extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	const BASEURL = 'https://api.solapi.com';
	const TIMEOUT = 5;
	const APPID = 'PAOe9c8ftH8R';

	/**
	 * API specifications.
	 */
	protected static $_spec = array(
		'max_recipients'              => 1000,
		'sms_max_length'              => 90,
		'sms_max_length_in_charset'   => 'CP949',
		'lms_supported'               => true,
		'lms_supported_country_codes' => array(82),
		'lms_max_length'              => 2000,
		'lms_max_length_in_charset'   => 'CP949',
		'lms_subject_supported'       => true,
		'lms_subject_max_length'      => 40,
		'mms_supported'               => true,
		'mms_supported_country_codes' => array(82),
		'mms_max_length'              => 2000,
		'mms_max_length_in_charset'   => 'CP949',
		'mms_subject_supported'       => true,
		'mms_subject_max_length'      => 40,
		'image_allowed_types'         => array('jpg', 'gif', 'png'),
		'image_max_dimensions'        => array(2048, 2048),
		'image_max_filesize'          => 200000,
		'delay_supported'             => true,
	);

	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('api_key', 'api_secret');
	protected static $_optional_config = array();

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
		$data = [
			'messages' => [],
			'agent' => [
				'appId' => self::APPID,
			]
		];
		$images = [];
		$schedule = null;
		$status = true;

		foreach ($messages as $i => $message)
		{
			foreach ($message->to as $recipient)
			{
				$options = new \stdClass;
				$options->to = $recipient;
				$options->from = $message->from;
				$options->text = $message->content ?: $message->type;
				$options->type = $message->type;

				if ($message->delay && $message->delay > time())
				{
					$schedule = date('c', $message->delay);
				}

				if ($message->country && $message->country != 82)
				{
					$options->country = $message->country;
				}

				if ($message->subject)
				{
					$options->subject = $message->subject;
				}
				elseif ($message->type != 'SMS')
				{
					// 문자 전송 타입이 SMS이 아닐경우 subjext가 필수
					$options->subject = cut_str($message->content, 20);
				}

				if ($message->image)
				{
					if (isset($images[$message->image]))
					{
						$imageId = $images[$message->image];
					}
					else
					{
						$output = $this->_uploadImage($message->image, $message->type);
						if (!empty($output->fileId))
						{
							$imageId = $images[$message->image] = $output->fileId;
						}
						else
						{
							$imageId = null;
						}
					}

					if ($imageId)
					{
						$options->imageId = $imageId;
					}
				}

				foreach ($original->getExtraVars() as $key => $value)
				{
					$options->$key = $value;
				}

				$data['messages'][] = $options;
			}
		}

		if ($schedule)
		{
			$data['scheduledDate'] = $schedule;
		}

		// Send all messages, and record failed messages.
		$result = json_decode($this->_request('POST', '/messages/v4/send-many/detail', $data));
		if (isset($result->failedMessageList) && is_array($result->failedMessageList) && count($result->failedMessageList))
		{
			foreach ($result->failedMessageList as $fail)
			{
				$original->addError('Error while sending message to ' . $fail->to . ': ' . trim($fail->statusCode . ' ' . $fail->statusMessage));
			}
			$status = false;
		}

		return $status;
	}

	/**
	 * Create header string for authorization.
	 *
	 * @param $config
	 * @return string
	 */
	protected function _getHeader()
	{
		$date = gmdate('Y-m-d\TH:i:s\Z');
		$salt = Security::getRandom(32);
		$signature = hash_hmac('sha256', $date . $salt, $this->_config['api_secret']);
		return "HMAC-SHA256 apiKey={$this->_config['api_key']}, date={$date}, salt={$salt}, signature={$signature}";
	}

	/**
	 * Upload an image for MMS message.
	 *
	 * @param $path
	 * @param $type
	 * @return mixed
	 */
	protected function _uploadImage($path, $type)
	{
		$jsonData = new \stdClass;
		$jsonData->file = base64_encode(file_get_contents($path));
		$jsonData->type = $type;
		$url = '/storage/v1/files';
		return json_decode($this->_request('POST', $url, $jsonData));
	}

	/**
	 * Request string message.
	 * @param $method
	 * @param $url
	 * @param array|object|null $data
	 * @return string
	 */
	protected function _request($method, $url, $data = null)
	{
		$url = self::BASEURL . $url;
		$data = $data ? json_encode($data) : null;
		$headers = [
			'Authorization' => $this->_getHeader(),
			'Content-Type' => 'application/json',
		];

		$result = HTTP::request($url, $method, $data, $headers, [], ['timeout' => self::TIMEOUT]);
		return $result->getBody()->getContents();
	}
}
