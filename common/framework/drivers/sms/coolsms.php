<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The CoolSMS SMS driver.
 */
class CoolSMS extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	/**
	 * Maximum length of an SMS.
	 */
	protected $_maxlength_sms = 90;
	
	/**
	 * Maximum length of an LMS.
	 */
	protected $_maxlength_lms = 2000;
	
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
			// Initialize the sender.
			$sender = new \Nurigo\Api\Message($this->_config['api_key'], $this->_config['api_secret']);
			
			// Get recipients.
			$recipients = $message->getRecipientsGroupedByCountry();
			foreach ($recipients as $country => $country_recipients)
			{
				$country_recipients = array_map(function($chunk) {
					return implode(',', $chunk);
				}, array_chunk($country_recipients, 1000));
				
				foreach ($country_recipients as $recipient_number)
				{
					// Populate the options object.
					$options = new \stdClass;
					$options->from = $message->getFrom();
					$options->to = $recipient_number;
					$options->charset = 'utf8';
					$content_full = $message->getContent();
					
					// Determine the message type based on the length.
					$detected_type = $message->checkLength($content_full, $this->_maxlength_sms) ? 'SMS' : 'LMS';
					$options->type = $detected_type;
					
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
					if ($message->isForceSMS() || ($country > 0 && $country != 82))
					{
						unset($options->subject);
						unset($options->image);
						$options->country = $country;
						$options->type = 'SMS';
					}
					
					// Split the message if necessary.
					if ($options->type === 'SMS' && $detected_type !== 'SMS')
					{
						$content_split = $message->splitMessage($content_full, $this->_maxlength_sms);
					}
					elseif ($options->type !== 'SMS' && !$message->checkLength($content_full, $this->_maxlength_lms))
					{
						$content_split = $message->splitMessage($content_full, $this->_maxlength_lms);
					}
					else
					{
						$content_split = array($content_full);
					}
					
					// Send the message.
					$sent_once = false;
					foreach ($content_split as $i => $content)
					{
						// If splitting a message, don't send the subject and image more than once.
						if ($sent_once)
						{
							unset($options->subject);
							unset($options->image);
						}
						
						// Set the content and send.
						$options->text = $content;
						var_dump($options);
						$result = $sender->send($options);
						$sent_once = true;
						
						if (!$result->success_count)
						{
							$message->errors[] = 'Error while sending message ' . $i . ' of ' . count($content_split) . ' to ' . $options->to;
							return false;
						}
					}
				}
			}
			
			return true;
		}
		catch (\Nurigo\Exceptions\CoolsmsException $e)
		{
			$message->errors[] = class_basename($e) . ': ' . $e->getMessage();
			return false;
		}
	}
}
