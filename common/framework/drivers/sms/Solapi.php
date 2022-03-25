<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The Solapi SMS driver.
 */
class SolAPI extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	const appId = 'PAOe9c8ftH8R';
	
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
		'image_max_filesize'          => 300000,
		'delay_supported'             => true,
	);

	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('api_key', 'api_secret');
	protected static $_optional_config = array('sender_key');

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
		$groupArray = array();
		$groupMessage = false;
		if(count($messages) > 1)
		{
			$groupMessage = true;
		}
		foreach ($messages as $i => $message)
		{
			if (count($message->to) > 1 && !$groupMessage)
			{
				$groupMessage = true;
			}
			$options = new \stdClass;
			if ($this->_config['sender_key'])
			{
				$options->sender_key = $this->_config['sender_key'];
				$options->type = 'CTA';
			}
			else
			{
				$options->type = $message->type;
			}
			$options->from = $message->from;
			$options->to = $message->to;
			$options->text = $message->content ?: $message->type;
			if ($message->delay && $message->delay > time())
			{
				$options->datetime = gmdate('YmdHis', $message->delay + (3600 * 9));
			}
			if ($message->country && $message->country != 82)
			{
				$options->country = $message->country;
			}
			if ($message->subject)
			{
				$options->subject = $message->subject;
			}
			else
			{
				if($message->type != 'SMS')
				{
					// 문자 전송 타입이 SMS이 아닐경우 subjext가 필수
					$options->subject = cut_str($message->content, 20);
				}
			}
			if ($message->image)
			{
				$output = $this->uploadImage($message->image, $message->type);
				$options->imageId = $output->fileId;
			}
			$groupArray[] = $options;
		}
		
		if($groupMessage)
		{
			$jsonObject = new \stdClass();
			$jsonObject->messages = json_encode($groupArray);
			$groupId = $this->createGroup();
			if(!$groupId)
			{
				return false;
			}

			$result = json_decode($this->request("PUT", "messages/v4/groups/{$groupId}/messages", $jsonObject));
			if(!$result || $result->errorCode)
			{
				return false;
			}

			$result = json_decode($this->request("POST", "messages/v4/groups/{$groupId}/send"));
			if (!$result || $result->status != 'SENDING')
			{
				return false;
			}
		}
		else
		{
			// simpleMessage 를 사용 할 경우 to가 array 타입이면 문자 전송이 되지 않아 string 으로 요청
			$groupArray[0]->to = $groupArray[0]->to[0];
			$simpleObject = new \stdClass();
			$simpleObject->message = $groupArray[0];
			$simpleObject->agent = new \stdClass();
			$simpleObject->agent->appId = self::appId;

			$result = json_decode($this->request("POST", "messages/v4/send", $simpleObject));
			if(!$result || $result->errorCode)
			{
				return false;
			}
		}

		return true;
	}
	
	/**
	 * Create header string for http protocol
	 * @param $config
	 * @return string
	 */
	private function getHeader()
	{
		date_default_timezone_set('Asia/Seoul');
		$date = date('Y-m-d\TH:i:s.Z\Z', time());
		$salt = uniqid();
		$signature = hash_hmac('sha256', $date . $salt, $this->_config['api_secret']);
		return "HMAC-SHA256 apiKey={$this->_config['api_key']}, date={$date}, salt={$salt}, signature={$signature}";
	}

	/**
	 * Create message group
	 * @return string : group id
	 */
	private function createGroup()
	{
		$args = new \stdClass();
		$args->appId = self::appId;
		$result = $this->request("POST", 'messages/v4/groups', $args);
		$groupId = json_decode($result)->groupId;
		return $groupId;
	}

	/**
	 * Upload to image for MMS message.
	 * @param $imageDir
	 * @param $type
	 * @return mixed
	 */
	private function uploadImage($imageDir, $type)
	{
		$path = $imageDir;
		$data = file_get_contents($path);
		$imageData = base64_encode($data);
		$jsonData = new \stdClass();
		$jsonData->file = $imageData;
		$jsonData->type = $type;
		$url = "storage/v1/files";
		return json_decode($this->request('POST', $url, $jsonData));
	}

	/**
	 * Request string message.
	 * @param $method
	 * @param $url
	 * @param bool $data
	 * @return bool|string
	 */
	private function request($method, $url, $data = false)
	{
		$url = 'https://api.solapi.com/' . $url;
		
		if(!$data)
		{
			$data = null;
		}
		else
		{
			$data = json_encode($data);
		}
		$result = \FileHandler::getRemoteResource($url, $data, 3, $method, 'application/json', array('Authorization' => $this->getHeader()));
		
		return $result;
	}
}
