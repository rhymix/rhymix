<?php

namespace Rhymix\Framework;

/**
 * The SMS class.
 */
class SMS
{
	/**
	 * Instance properties.
	 */
	public $driver = null;
	protected $caller = '';
	protected $from = null;
	protected $to = array();
	protected $subject = '';
	protected $content = '';
	protected $attachments = array();
	protected $extra_vars = array();
	protected $delay_timestamp = 0;
	protected $force_sms = false;
	protected $allow_split_sms = true;
	protected $allow_split_lms = true;
	protected $errors = array();
	protected $sent = false;
	
	/**
	 * Static properties.
	 */
	public static $default_driver = null;
	public static $custom_drivers = array();
	
	/**
	 * Set the default driver.
	 * 
	 * @param object $driver
	 * @return void
	 */
	public static function setDefaultDriver(Drivers\SMSInterface $driver)
	{
		self::$default_driver = $driver;
	}
	
	/**
	 * Get the default driver.
	 * 
	 * @return object
	 */
	public static function getDefaultDriver()
	{
		if (!self::$default_driver)
		{
			$default_driver = config('sms.type');
			$default_driver_class = '\Rhymix\Framework\Drivers\SMS\\' . $default_driver;
			if (class_exists($default_driver_class))
			{
				$default_driver_config = config('sms.' . $default_driver) ?: array();
				self::$default_driver = $default_driver_class::getInstance($default_driver_config);
			}
			else
			{
				self::$default_driver = Drivers\SMS\Dummy::getInstance(array());
			}
		}
		return self::$default_driver;
	}
	
	/**
	 * Add a custom mail driver.
	 */
	public static function addDriver(Drivers\SMSInterface $driver)
	{
		self::$custom_drivers[] = $driver;
	}
	
	/**
	 * Get the list of supported mail drivers.
	 * 
	 * @return array
	 */
	public static function getSupportedDrivers()
	{
		$result = array();
		foreach (Storage::readDirectory(__DIR__ . '/drivers/sms', false) as $filename)
		{
			$driver_name = substr($filename, 0, -4);
			$class_name = '\Rhymix\Framework\Drivers\SMS\\' . $driver_name;
			if ($class_name::isSupported())
			{
				$result[$driver_name] = array(
					'name' => $class_name::getName(),
					'required' => $class_name::getRequiredConfig(),
					'optional' => $class_name::getOptionalConfig(),
					'api_types' => $class_name::getAPITypes(),
					'api_spec' => $class_name::getAPISpec(),
				);
			}
		}
		foreach (self::$custom_drivers as $driver)
		{
			if ($driver->isSupported())
			{
				$result[strtolower(class_basename($driver))] = array(
					'name' => $driver->getName(),
					'required' => $driver->getRequiredConfig(),
					'optional' => $driver->getOptionalConfig(),
					'api_types' => $driver->getAPITypes(),
					'api_spec' => $class_name::getAPISpec(),
				);
			}
		}
		ksort($result);
		return $result;
	}
	
	/**
	 * The constructor.
	 */
	public function __construct()
	{
		$this->driver = self::getDefaultDriver();
		$this->from = trim(preg_replace('/[^0-9]/', '', config('sms.default_from'))) ?: null;
		$this->allow_split_sms = (config('sms.allow_split.sms') !== false);
		$this->allow_split_lms = (config('sms.allow_split.lms') !== false);
	}
	
	/**
	 * Set the sender's phone number.
	 *
	 * @param string $number Phone number
	 * @return bool
	 */
	public function setFrom($number)
	{
		$this->from = preg_replace('/[^0-9]/', '', $number);
		return true;
	}
	
	/**
	 * Get the sender's phone number.
	 *
	 * @return string|null
	 */
	public function getFrom()
	{
		return $this->from;
	}
	
	/**
	 * Add a recipient.
	 *
	 * @param string $number Phone number
	 * @param string $country Country code (optional)
	 * @return bool
	 */
	public function addTo($number, $country = 0)
	{
		$this->to[] = (object)array(
			'number' => preg_replace('/[^0-9]/', '', $number),
			'country' => intval(preg_replace('/[^0-9]/', '', $country)),
		);
		return true;
	}
	
	/**
	 * Get the list of recipients without country codes.
	 *
	 * @return array
	 */
	public function getRecipients()
	{
		return array_map(function($recipient) {
			return $recipient->number;
		}, $this->to);
	}
	
	/**
	 * Get the list of recipients with country codes.
	 *
	 * @return array
	 */
	public function getRecipientsWithCountry()
	{
		return $this->to;
	}
	
	/**
	 * Get the list of recipients grouped by country code.
	 *
	 * @return array
	 */
	public function getRecipientsGroupedByCountry()
	{
		$result = array();
		foreach ($this->to as $recipient)
		{
			$result[$recipient->country][] = $recipient->number;
		}
		return $result;
	}
	
	/**
	 * Set the subject.
	 *
	 * @param string $subject
	 * @return bool
	 */
	public function setSubject($subject)
	{
		$this->subject = utf8_trim(utf8_clean($subject));
		return true;
	}
	
	/**
	 * Get the subject.
	 *
	 * @return string
	 */
	public function getSubject()
	{
		return $this->subject;
	}
	
	/**
	 * Set the subject (alias to setSubject).
	 *
	 * @param string $subject
	 * @return bool
	 */
	public function setTitle($subject)
	{
		return $this->setSubject($subject);
	}
	
	/**
	 * Get the subject (alias to getSubject).
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getSubject();
	}
	
	/**
	 * Set the content.
	 *
	 * @param string $content
	 * @return bool
	 */
	public function setBody($content)
	{
		$this->content = utf8_trim(utf8_clean($content));
		$this->content = strtr($this->content, array("\r\n" => "\n"));
		return true;
	}
	
	/**
	 * Get the content.
	 * 
	 * @return string
	 */
	public function getBody()
	{
		return $this->content;
	}
	
	/**
	 * Set the content (alias to setBody).
	 *
	 * @param string $content
	 * @return void
	 */
	public function setContent($content)
	{
		return $this->setBody($content);
	}
	
	/**
	 * Get the content (alias to getBody).
	 * 
	 * @return string
	 */
	public function getContent()
	{
		return $this->getBody();
	}
	
	/**
	 * Attach a file.
	 *
	 * @param string $local_filename
	 * @param string $display_filename (optional)
	 * @return bool
	 */
	public function attach($local_filename, $display_filename = null)
	{
		if ($display_filename === null)
		{
			$display_filename = basename($local_filename);
		}
		if (!Storage::exists($local_filename))
		{
			return false;
		}
		
		$this->attachments[] = (object)array(
			'type' => 'mms',
			'local_filename' => $local_filename,
			'display_filename' => $display_filename,
			'cid' => null,
		);
		return true;
	}
	
	/**
	 * Get the list of attachments to this message.
	 * 
	 * @return array
	 */
	public function getAttachments()
	{
		return $this->attachments;
	}
	
	/**
	 * Set an extra variable.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setExtraVar($key, $value)
	{
		$this->extra_vars[$key] = $value;
	}
	
	/**
	 * Get an extra variable.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function getExtraVar($key)
	{
		return isset($this->extra_vars[$key]) ? $this->extra_vars[$key] : null;
	}
	
	/**
	 * Get all extra variables.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function getExtraVars()
	{
		return $this->extra_vars;
	}
	
	/**
	 * Set all extra variables.
	 * 
	 * @param array $vars
	 * @return void
	 */
	public function setExtraVars(array $vars)
	{
		$this->extra_vars = $vars;
	}
	
	/**
	 * Delay sending the message.
	 * 
	 * Delays (in seconds) less than 1 year will be treated as relative to the
	 * current time. Greater values will be interpreted as a Unix timestamp.
	 * 
	 * This feature may not be implemented by all drivers.
	 * 
	 * @param int $when Unix timestamp
	 * @return bool
	 */
	public function setDelay($when)
	{
		if ($when <= (86400 * 365))
		{
			$when = time() + $when;
		}
		if ($when <= time())
		{
			$when = 0;
		}
		
		$this->delay_timestamp = intval($when);
		return true;
	}
	
	/**
	 * Get the Unix timestamp of when to send the message.
	 * 
	 * This method always returns a Unix timestamp, even if the original value
	 * was given as a relative delay.
	 * 
	 * This feature may not be implemented by all drivers.
	 * 
	 * @return int
	 */
	public function getDelay()
	{
		return $this->delay_timestamp;
	}
	
	/**
	 * Force this message to use SMS (not LMS or MMS).
	 * 
	 * @return void
	 */
	public function forceSMS()
	{
		$this->force_sms = true;
	}
	
	/**
	 * Unforce this message to use SMS (not LMS or MMS).
	 * 
	 * @return void
	 */
	public function unforceSMS()
	{
		$this->force_sms = false;
	}
	
	/**
	 * Check if this message is forced to use SMS.
	 * 
	 * @return bool
	 */
	public function isForceSMS()
	{
		return $this->force_sms;
	}
	
	/**
	 * Allow this message to be split into multiple SMS.
	 * 
	 * @return void
	 */
	public function allowSplitSMS()
	{
		$this->allow_split_sms = true;
	}
	
	/**
	 * Allow this message to be split into multiple LMS.
	 * 
	 * @return void
	 */
	public function allowSplitLMS()
	{
		$this->allow_split_lms = true;
	}
	
	/**
	 * Disallow this message to be split into multiple SMS.
	 * 
	 * @return void
	 */
	public function disallowSplitSMS()
	{
		$this->allow_split_sms = false;
	}
	
	/**
	 * Disallow this message to be split into multiple LMS.
	 * 
	 * @return void
	 */
	public function disallowSplitLMS()
	{
		$this->allow_split_lms = false;
	}
	
	/**
	 * Check if splitting this message into multiple SMS is allowed.
	 * 
	 * @return bool
	 */
	public function isSplitSMSAllowed()
	{
		return $this->allow_split_sms;
	}
	
	/**
	 * Check if splitting this message into multiple LMS is allowed.
	 * 
	 * @return bool
	 */
	public function isSplitLMSAllowed()
	{
		return $this->allow_split_lms;
	}
	
	/**
	 * Send the message.
	 * 
	 * @return bool
	 */
	public function send()
	{
		// Get caller information.
		$backtrace = debug_backtrace(0);
		if(count($backtrace) && isset($backtrace[0]['file']))
		{
			$this->caller = $backtrace[0]['file'] . ($backtrace[0]['line'] ? (' line ' . $backtrace[0]['line']) : '');
		}
		
		$output = \ModuleHandler::triggerCall('sms.send', 'before', $this);
		if(!$output->toBool())
		{
			$this->errors[] = $output->getMessage();
			return false;
		}
		
		if (config('sms.default_force') && config('sms.default_from'))
		{
			$this->setFrom(config('sms.default_from'));
		}
		
		try
		{
			if ($this->driver)
			{
				$messages = $this->_formatSpec($this->driver->getAPISpec());
				if (count($messages))
				{
					$this->sent = $this->driver->send($messages, $this) ? true : false;
				}
				else
				{
					$this->errors[] = 'No recipients selected';
					$this->sent = false;
				}
			}
			else
			{
				$this->errors[] = 'No SMS driver selected';
				$this->sent = false;
			}
		}
		catch(\Exception $e)
		{
			$this->errors[] = class_basename($e) . ': ' . $e->getMessage();
			$this->sent = false;
		}
		
		$output = \ModuleHandler::triggerCall('sms.send', 'after', $this);
		if(!$output->toBool())
		{
			$this->errors[] = $output->getMessage();
		}
		
		return $this->sent;
	}
	
	/**
	 * Check if the message was sent.
	 * 
	 * @return bool
	 */
	public function isSent()
	{
		return $this->sent;
	}
	
	/**
	 * Get caller information.
	 * 
	 * @return string
	 */
	public function getCaller()
	{
		return $this->caller;
	}
	
	/**
	 * Get errors.
	 * 
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}
	
	/**
	 * Add an error message.
	 * 
	 * @param string $message
	 * @return void
	 */
	public function addError($message)
	{
		$this->errors[] = $message;
	}
	
	/**
	 * Format the current message according to an API spec.
	 * 
	 * @param array $spec API specifications
	 * @return array
	 */
	protected function _formatSpec(array $spec)
	{
		// Initialize the return array.
		$result = array();
		
		// Get the list of recipients.
		$recipients = $this->getRecipientsGroupedByCountry();
		
		// Group the recipients by country code.
		foreach ($recipients as $country_code => $country_recipients)
		{
			// Merge recipients into groups.
			if ($spec['max_recipients'] > 1)
			{
				$country_recipients = array_chunk($country_recipients, $spec['max_recipients']);
			}
			
			// Send to each set of merged recipients.
			foreach ($country_recipients as $recipient_numbers)
			{
				// Populate the item.
				$item = new \stdClass;
				$item->type = 'SMS';
				$item->from = $this->getFrom();
				$item->to = $recipient_numbers;
				$item->country = $country_code;
				if ($spec['delay_supported'])
				{
					$item->delay = $this->getDelay() ?: 0;
				}
				
				// Get message content.
				$subject = $this->getSubject();
				$content = $this->getContent();
				$attachments = $attachments = $this->getAttachments();
				
				// Determine the message type.
				if (!$this->isForceSMS() && ($spec['lms_supported'] || $spec['mms_supported']))
				{
					// Check attachments, subject, and message length.
					if ($spec['mms_supported'] && count($attachments))
					{
						$item->type = 'MMS';
					}
					elseif ($spec['lms_supported'] && $subject)
					{
						$item->subject = $subject;
						$item->type = 'LMS';
					}
					elseif ($spec['lms_supported'] && $this->_getLengthInCharset($content, $spec['sms_max_length_in_charset']) > $spec['sms_max_length'])
					{
						$item->type = 'LMS';
					}
					else
					{
						$item->type = 'SMS';
					}
					
					// Check the country code.
					if ($item->type === 'MMS' && $country_code && is_array($spec['mms_supported_country_codes']) && !in_array($country_code, $spec['mms_supported_country_codes']))
					{
						$item->type = 'LMS';
					}
					if ($item->type === 'LMS' && $country_code && is_array($spec['lms_supported_country_codes']) && !in_array($country_code, $spec['lms_supported_country_codes']))
					{
						$item->type = 'SMS';
					}
				}
				
				// Remove subject and attachments if the message type is SMS.
				if ($item->type === 'SMS')
				{
					if ($subject)
					{
						$content = $subject . "\n" . $content;
						unset($item->subject);
					}
					$attachments = array();
				}
				
				// If message subject is not supported, prepend it to the content instead.
				if (isset($item->subject) && $item->subject && !$spec[strtolower($item->type) . '_subject_supported'])
				{
					$content = $item->subject . "\n" . $content;
					unset($item->subject);
				}
				elseif (isset($item->subject) && $item->subject && $this->_getLengthInCharset($item->subject, $spec[strtolower($item->type) . '_max_length_in_charset']) > $spec[strtolower($item->type) . '_subject_max_length'])
				{
					$subject_parts = $this->_splitString($item->subject, $spec[strtolower($item->type) . '_subject_max_length'], $spec[strtolower($item->type) . '_max_length_in_charset']);
					$subject_short = array_shift($subject_parts);
					$subject_remainder = utf8_trim(substr($item->subject, strlen($subject_short)));
					$item->subject = $subject_short;
					$content = $subject_remainder . "\n" . $content;
				}
				
				// Split the content if necessary.
				if (($item->type === 'SMS' && $this->allow_split_sms) || ($item->type !== 'SMS' && $this->allow_split_lms))
				{
					if ($this->_getLengthInCharset($content, $spec[strtolower($item->type) . '_max_length_in_charset']) > $spec[strtolower($item->type) . '_max_length'])
					{
						$content_parts = $this->_splitString($content, $spec[strtolower($item->type) . '_max_length'], $spec[strtolower($item->type) . '_max_length_in_charset']);
					}
					else
					{
						$content_parts = array($content);
					}
				}
				else
				{
					$content_parts = array($content);
				}
				
				// Generate a message for each part of the content and attachments.
				$message_count = max(count($content_parts), count($attachments));
				$last_content = $item->type;
				for ($i = 1; $i <= $message_count; $i++)
				{
					// Get the message content.
					if ($content_part = array_shift($content_parts))
					{
						$item->content = $last_content = $content_part;
					}
					else
					{
						$item->content = $last_content ?: $item->type;
					}
					
					// Get the attachment.
					if ($attachment = array_shift($attachments))
					{
						$item->image = $attachment->local_filename;
					}
					else
					{
						unset($item->image);
					}
					
					// Clone the item to make a part.
					$cloneitem = clone $item;
					
					// Determine the best message type for this part.
					if ($cloneitem->type !== 'SMS' && (!isset($cloneitem->subject) || !$cloneitem->subject))
					{
						$cloneitem->type = $attachment ? 'MMS' : ($this->_getLengthInCharset($content_part, $spec['sms_max_length_in_charset']) > $spec['sms_max_length'] ? 'LMS' : 'SMS');
					}
					
					// Add the cloned part to the result array.
					$result[] = $cloneitem;
				}
			}
		}
		
		// Return the message parts.
		return $result;
	}
	
	/**
	 * Get the length of a string in another character set.
	 * 
	 * @param string $str String to measure
	 * @param string $charset Character set to measure length
	 * @return 
	 */
	protected function _getLengthInCharset($str, $charset)
	{
		$str = @iconv('UTF-8', $charset . '//IGNORE', $str);
		return strlen($str);
	}
	
	/**
	 * Split a string into several short chunks.
	 * 
	 * @param string $str String to split
	 * @param int $max_length Maximum length of a chunk
	 * @param string $charset Character set to measure length
	 * @return array
	 */
	protected function _splitString($str, $max_length, $charset)
	{
		$str = utf8_trim(utf8_normalize_spaces($str, true));
		$chars = preg_split('//u', $str, -1, \PREG_SPLIT_NO_EMPTY);
		$result = array();
		$current_entry = '';
		$current_length = 0;
		
		foreach ($chars as $char)
		{
			$char_length = strlen(@iconv('UTF-8', $charset . '//IGNORE', $char));
			if (($current_length + $char_length > $max_length) || ($current_length + $char_length > $max_length - 7 && ctype_space($char)))
			{
				$result[] = trim($current_entry);
				$current_entry = $char;
				$current_length = $char_length;
			}
			else
			{
				$current_entry .= $char;
				$current_length += $char_length;
			}
		}
		
		if ($current_entry !== '')
		{
			$result[] = trim($current_entry);
		}
		
		return $result;
	}
}
