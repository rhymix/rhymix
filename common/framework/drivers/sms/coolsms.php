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
			$status = true;
			
			// Get the list of recipients.
			$recipients = $message->getRecipientsGroupedByCountry();
			
			// Group the recipients by country code.
			foreach ($recipients as $country => $country_recipients)
			{
				// Merge recipients into groups of 1000.
				$country_recipients = array_map(function($chunk) {
					return implode(',', $chunk);
				}, array_chunk($country_recipients, 1000));
				
				// Send to each set of merged recipients.
				foreach ($country_recipients as $recipient_number)
				{
					// Populate the options object.
					$options = new \stdClass;
					$options->from = $message->getFrom();
					$options->to = $recipient_number;
					$options->charset = 'utf8';
					
					// Determine when to send this message.
					if ($datetime = $message->getDelay())
					{
						if ($datetime > time())
						{
							$options->datetime = gmdate('YmdHis', $datetime + (3600 * 9));
						}
					}
					
					// Determine the message type based on the length.
					$content_full = $message->getContent();
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
						$options->type = 'MMS';
					}
					
					// If the recipient is not a Korean number, force SMS.
					if ($message->isForceSMS() || ($country > 0 && $country != 82))
					{
						unset($options->subject);
						$attachments = array();
						$options->country = $country;
						$options->type = 'SMS';
						$message->forceSMS();
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
					
					// Send all parts of the split message.
					$message_count = max(count($content_split), count($attachments));
					$last_content = 'MMS';
					for ($i = 1; $i <= $message_count; $i++)
					{
						// Get the message content.
						if ($content = array_shift($content_split))
						{
							$options->text = $last_content = $content;
						}
						else
						{
							$options->text = $last_content ?: 'MMS';
						}
						
						// Get the attachment.
						if ($attachment = array_shift($attachments))
						{
							$options->image = $attachment->local_filename;
						}
						else
						{
							unset($options->image);
						}
						
						// Determine the best message type for this combination of content and attachment.
						if (!$message->isForceSMS())
						{
							$options->type = $attachment ? 'MMS' : ($message->checkLength($content, $this->_maxlength_sms) ? 'SMS' : 'LMS');
						}
						
						// Send the current part of the message.
						$result = $sender->send($options);
						if (!$result->success_count)
						{
							$error_codes = implode(', ', $result->error_list ?: array('Unknown'));
							$message->errors[] = 'Error (' . $error_codes . ') while sending message ' . $i . ' of ' . $message_count . ' to ' . $options->to;
							$status = false;
						}
					}
				}
			}
			
			return $status;
		}
		catch (\Nurigo\Exceptions\CoolsmsException $e)
		{
			$message->errors[] = class_basename($e) . ': ' . $e->getMessage();
			return false;
		}
	}
}
