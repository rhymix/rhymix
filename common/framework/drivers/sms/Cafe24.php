<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The Cafe24 SMS driver.
 */
class Cafe24 extends Base implements \Rhymix\Framework\Drivers\SMSInterface
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
		'lms_subject_max_length' => 50,
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
			$data['user_id'] = $this->_config['api_user'];
			$data['secure'] = $this->_config['api_key'];
			$data['nointeractive'] = 1;
			if ($message->type === 'LMS')
			{
				$data['smsType'] = 'L';
			}
			
			// Sender and recipient
			$from = explode('-', \Rhymix\Framework\Korea::formatPhoneNumber($message->from));
			$data['sphone1'] = $from[0];
			$data['sphone2'] = $from[1];
			if (isset($from[2]))
			{
				$data['sphone3'] = $from[2];
			}
			$data['rphone'] = implode(',', array_map(function($num) {
				return \Rhymix\Framework\Korea::formatPhoneNumber($num);
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
				$data['rdate'] = gmdate('Ymd', $message->delay + (3600 * 9));
				$data['rtime'] = gmdate('His', $message->delay + (3600 * 9));
			}
			
			// Send!
			$url = 'https://sslsms.cafe24.com/sms_sender.php';
			$result = \FileHandler::getRemoteResource($url, $data, 5, 'POST');
			if(strval($result) === '')
			{
				$original->addError('Unknown API error while sending message ' . ($i + 1) . ' of ' . count($messages));
				$status = false;
			}
			else
			{
				$result = explode(',', $result);
				if ($result[0] !== 'success' && $result[0] !== 'reserved')
				{
					$original->addError('API error ' . $result[0] . ' while sending message ' . ($i + 1) . ' of ' . count($messages));
					$status = false;
				}
			}
		}
		
		return $status;
	}
}
