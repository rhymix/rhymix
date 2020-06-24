<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The ApiStore SMS driver.
 */
class ApiStore extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	/**
	 * API specifications.
	 */
	protected static $_spec = array(
		'max_recipients' => 500,
		'sms_max_length' => 90,
		'sms_max_length_in_charset' => 'CP949',
		'lms_supported' => true,
		'lms_supported_country_codes' => array(82),
		'lms_max_length' => 2000,
		'lms_max_length_in_charset' => 'CP949',
		'lms_subject_supported' => true,
		'lms_subject_max_length' => 60,
		'mms_supported' => false,
		'delay_supported' => true,
	);
	
	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('api_user', 'api_key');
	
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
	 * Store the last response.
	 */
	protected $_last_response = '';
	
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
		
		foreach ($messages as $i => $message)
		{
			$data = array();
			$data['send_phone'] = $message->from;
			$data['dest_phone'] = implode(',', $message->to);
			$data['msg_body'] = strval($message->content);
			if ($message->type !== 'SMS' && $message->subject)
			{
				$data['subject'] = $message->subject;
			}
		
			$result = $this->_apiCall(sprintf('message/%s', strtolower($message->type)), $data);
			if (!$result)
			{
				$message->errors[] = 'ApiStore API returned invalid response: ' . $this->_getLastResponse();
				$status = false;
			}
			if ($result->result_message !== 'OK')
			{
				$message->errors[] = 'ApiStore API error: ' . $result->result_code . ' ' . $result->result_message;
			}
		}
		
		return $status;
	}
	
	/**
	 * API call.
	 * 
	 * @param string $url
	 * @param array $data
	 * @param string $method (optional)
	 * @return object|false
	 */
	protected function _apiCall(string $url, array $data, string $method = 'POST')
	{
		// Build the request URL.
		if ($data['version'])
		{
			$version = $data['version'];
			unset($data['version']);
		}
		else
		{
			$version = 1;
		}
		$url = sprintf('http://api.apistore.co.kr/ppurio/%d/%s/%s', $version, trim($url, '/'), $this->_config['api_user']);
		
		// Set the API key in the header.
		$headers = array(
			'x-waple-authorization' => $this->_config['api_key'],
		);
		
		// Send the API reqeust.
		if ($method === 'GET')
		{
			if ($data)
			{
				$url .= '?' . http_build_query($data);
			}
			$this->_last_response = \FileHandler::getRemoteResource($url, null, 5, $method, null, $headers) ?: '';
		}
		else
		{
			$this->_last_response = \FileHandler::getRemoteResource($url, $data, 5, $method, null, $headers) ?: '';
		}
		$result = @json_decode($this->_last_response);
		return $result ?: false;
	}
	
	/**
	 * Fetch the last API response.
	 * 
	 * @return string
	 */
	protected function _getLastResponse()
	{
		return $this->_last_response;
	}
}
