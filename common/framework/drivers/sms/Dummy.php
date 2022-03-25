<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The dummy SMS driver.
 */
class Dummy extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	/**
	 * API specifications.
	 */
	protected static $_spec = array(
		'max_recipients' => 100,
		'sms_max_length' => 90,
		'sms_max_length_in_charset' => 'CP949',
		'lms_supported' => true,
		'lms_supported_country_codes' => array(82),
		'lms_max_length' => 2000,
		'lms_max_length_in_charset' => 'CP949',
		'lms_subject_supported' => true,
		'lms_subject_max_length' => 40,
		'mms_supported' => true,
		'mms_supported_country_codes' => array(82),
		'mms_max_length' => 2000,
		'mms_max_length_in_charset' => 'CP949',
		'mms_subject_supported' => false,
		'mms_subject_max_length' => 40,
		'image_allowed_types' => array(),
		'image_max_dimensions' => array(1024, 1024),
		'image_max_filesize' => 300000,
		'delay_supported' => true,
	);
	
	/**
	 * Sent messages are stored here for debugging and testing.
	 */
	protected $_sent_messages = array();
	
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
		foreach ($messages as $message)
		{
			$this->_sent_messages[] = $message;
		}
		return true;
	}
	
	/**
	 * Get sent messages.
	 * 
	 * @return array
	 */
	public function getSentMessages()
	{
		return $this->_sent_messages;
	}
	
	/**
	 * Reset sent messages.
	 * 
	 * @return void
	 */
	public function resetSentMessages()
	{
		$this->_sent_messages = array();
	}
}
