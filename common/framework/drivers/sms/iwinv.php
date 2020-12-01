<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The iwinv SMS driver.
 */
class iwinv extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	/**
	 * API endpoint URL.
	 */
	const API_URL = 'https://sms.service.iwinv.kr/send/';
	
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
		'image_allowed_types'         => array('jpg'),
		'image_max_dimensions'        => array(2048, 2048),
		'image_max_filesize'          => 100000,
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
		$status = true;
		$curl = null;
		
		foreach ($messages as $i => $message)
		{
			// Authentication
			$headers = array(
				'Content-Type: multipart/form-data',
				'secret: ' . base64_encode($this->_config['api_key'] . '&' . $this->_config['api_secret']),
			);
			
			// Sender and recipient
			$data = array();
			$data['from'] = str_replace('-', '', \Rhymix\Framework\Korea::formatPhoneNumber($message->from));
			$data['to'] = array_map(function($num) {
				return str_replace('-', '', \Rhymix\Framework\Korea::formatPhoneNumber($num));
			}, $message->to);
			if (count($data['to']) === 1)
			{
				$data['to'] = array_first($data['to']);
			}
			
			// Subject and content
			if ($message->type === 'LMS' && $message->subject)
			{
				$data['title'] = $message->subject;
			}
			$data['text'] = $message->content;
			
			// Image attachment
			if ($message->image)
			{
				$data['image'] = curl_file_create(realpath($message->image));
			}
			
			// Set delay
			if ($message->delay && $message->delay > time() + 900)
			{
				$data['date'] = gmdate('Y-m-d H:i:s', $message->delay + (3600 * 9));
			}
			
			// We need to use curl because Filehandler::getRemoteResource() doesn't work with this API for some reason.
			if (!$curl)
			{
				$curl = curl_init();
			}
			curl_setopt($curl, \CURLOPT_URL, self::API_URL);
			curl_setopt($curl, \CURLOPT_TIMEOUT, 5);
			curl_setopt($curl, \CURLOPT_POST, 1);
			curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, \CURLOPT_POSTFIELDS, $data);
			curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);
			$result = curl_exec($curl);
			$err = curl_error($curl);
			
			// Check the result.
			if ($err)
			{
				$original->addError('API error while sending message ' . ($i + 1) . ' of ' . count($messages) . ': ' . $err);
				$status = false;
			}
			elseif (trim($result) === '')
			{
				$original->addError('Unknown API error while sending message ' . ($i + 1) . ' of ' . count($messages));
				$status = false;
			}
			elseif (trim($result) !== '00')
			{
				$original->addError('API error ' . trim($result) . ' while sending message ' . ($i + 1) . ' of ' . count($messages));
				$status = false;
			}
		}
		
		if ($curl)
		{
			@curl_close($curl);
		}
		
		return $status;
	}
}
