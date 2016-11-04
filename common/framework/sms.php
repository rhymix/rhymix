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
	public $caller = '';
	protected $from = '';
	protected $to = array();
	protected $subject = '';
	protected $content = '';
	protected $attachments = array();
	protected $force_sms = false;
	protected $allow_split_sms = true;
	protected $allow_split_lms = true;
	public $errors = array();
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
					'api_types' => $class_name::getAPITypes(),
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
					'api_types' => $driver->getAPITypes(),
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
		$this->from = trim(config('sms.default_from'));
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
	 * @return string
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
		
		try
		{
			if ($this->driver)
			{
				$this->sent = $this->driver->send($this) ? true : false;
			}
			else
			{
				$this->errors[] = 'No SMS driver selected';
				$this->sent = false;
			}
		}
		catch(\Exception $e)
		{
			$this->errors[] = $e->getMessage();
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
	 * Check if a message is no longer than the given length.
	 * 
	 * This is useful when checking whether a message can fit into a single SMS.
	 * 
	 * @param string $message
	 * @param int $maxlength
	 * @param string $measure_in_charset (optional)
	 * @return 
	 */
	public function checkLength($message, $maxlength, $measure_in_charset = 'CP949')
	{
		$message = @iconv('UTF-8', $measure_in_charset . '//IGNORE', $message);
		return strlen($message) <= $maxlength;
	}
	
	/**
	 * Split a message into several short messages.
	 * 
	 * This is useful when sending a long message as a series of SMS.
	 * 
	 * @param string $message
	 * @param int $maxlength
	 * @param string $measure_in_charset (optional)
	 * @return array
	 */
	public function splitMessage($message, $maxlength, $measure_in_charset = 'CP949')
	{
		$message = utf8_trim(utf8_normalize_spaces($message));
		$chars = preg_split('//u', $message, -1, PREG_SPLIT_NO_EMPTY);
		$result = array();
		$current_entry = '';
		$current_length = 0;
		
		foreach ($chars as $char)
		{
			$char_length = strlen(@iconv('UTF-8', $measure_in_charset . '//IGNORE', $char));
			if ($current_length + $char_length > $maxlength)
			{
				$result[] = $current_entry;
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
			$result[] = $current_entry;
		}
		
		return $result;
	}
}
