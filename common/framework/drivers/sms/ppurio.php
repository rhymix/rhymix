<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The Ppurio SMS driver.
 */
class Ppurio extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	/**
	 * API specifications.
	 */
	protected static $_spec = array(
		'max_recipients' => 1000,
		'sms_max_length' => 90,
		'sms_max_length_in_charset' => 'CP949',
		'lms_supported' => true,
		'lms_supported_country_codes' => array(82),
		'lms_max_length' => 2000,
		'lms_max_length_in_charset' => 'CP949',
		'lms_subject_supported' => true,
		'lms_subject_max_length' => 30,
		'mms_supported' => false,
		'delay_supported' => true,
	);
	
	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('api_user');
	
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

		foreach ($messages as $i => $message)
		{
			// Authentication and basic information
			$data = array();
			$data['userid'] = $this->_config['api_user'];
			
			// Sender and recipient
			$data['callback'] = preg_replace('/[^0-9]/', '', $message->from);
			$data['phone'] = implode('|', array_map(function($num) {
				return preg_replace('/[^0-9]/', '', $num);
			}, $message->to));
			
			// Subject and content
			if ($message->type === 'LMS' && $message->subject)
			{
				$data['subject'] = $message->subject;
			}
			$data['msg'] = $message->content;
			
			// Set delay
			if ($message->delay && $message->delay > time() + 600)
			{
				$data['appdate'] = gmdate('YmdHis', $message->delay + (3600 * 9));
			}
			
			// Send!
			$url = 'https://www.ppurio.com/api/send_utf8_json.php';
			$result = \FileHandler::getRemoteResource($url, $data, 5, 'POST');
			if(strval($result) === '')
			{
				$original->addError('Unknown API error while sending message ' . ($i + 1) . ' of ' . count($messages));
				$status = false;
			}
			else
			{
				$result = @json_decode($result);
				if ($result->result !== 'ok')
				{
					$original->addError('API error (' . $result->result . ') while sending message ' . ($i + 1) . ' of ' . count($messages));
					$status = false;
				}
			}
		}
		
		return $status;
	}
}
