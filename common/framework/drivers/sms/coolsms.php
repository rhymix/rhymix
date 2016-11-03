<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The CoolSMS SMS driver.
 */
class CoolSMS extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
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
	 * Get the list of API types supported by this mail driver.
	 * 
	 * @return array
	 */
	public static function getAPITypes()
	{
		return array();
	}
	
	/**
	 * Send a message.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param object $message
	 * @return bool
	 */
	public function send(\Rhymix\Framework\SMS $message)
	{
		try
		{
			$recipients = $message->getRecipientsWithCountry();
			foreach ($recipients as $recipient)
			{
				// Populate the options object.
				$options = new \stdClass;
				$options->from = $message->getFrom();
				$options->to = $recipient->number;
				$options->text = $message->getContent();
				$options->charset = 'UTF-8';
				
				// Determine the message type based on the length.
				$options->type = $this->_isShort($options->text) ? 'SMS' : 'LMS';
				
				// If the message has a subject, it must be an LMS.
				if ($subject = $message->getSubject())
				{
					$options->subject = $subject;
					$options->type = 'LMS';
				}
				
				// If the message has an attachment, it must be an MMS.
				if ($attachments = $message->getAttachments())
				{
					$image = reset($attachments);
					$options->image = $image->local_filename;
					$options->type = 'MMS';
				}
				
				// If the recipient is not a Korean number, force SMS.
				if ($recipient->country && $recipient->country != 82)
				{
					unset($options->subject);
					unset($options->image);
					$options->text = $this->_cutShort($options->text);
					$options->type = 'SMS';
				}
				
				// Send the message.
				$sender = new \Nurigo\Api\Message($this->_config['api_key'], $this->_config['api_secret']);
				$result = $sender->send($options);
				return (isset($result->success_count) && $result->success_count > 0) ? true : false;
			}
		}
		catch (\Nurigo\Exceptions\CoolsmsException $e)
		{
			$message->errors[] = class_basename($e) . ': ' . $e->getMessage();
			return false;
		}
	}
	
	/**
	 * Check if a message is short enough to fit in an SMS.
	 * 
	 * @param string $message
	 * @param int $maxlength (optional)
	 * @return bool
	 */
	protected function _isShort($message, $maxlength = 90)
	{
		$message = @iconv('UTF-8', 'CP949//IGNORE', $message);
		return strlen($message) <= $maxlength;
	}
	
	/**
	 * Cut a message to fit in an SMS.
	 * 
	 * @param string $message
	 * @param int $maxlength (optional)
	 * @return string
	 */
	protected function _cutShort($message, $maxlength = 90)
	{
		$message = @iconv('UTF-8', 'CP949//IGNORE', $message);
		while (strlen($message) > $maxlength)
		{
			$message = iconv_substr($message, 0, iconv_strlen($str, 'CP949') - 1, 'CP949');
		}
		return iconv('CP949', 'UTF-8', $message);
	}
}
