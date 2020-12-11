<?php

namespace Rhymix\Framework;

/**
 * The Push class.
 */
class Push
{
	/**
	 * Instance properties.
	 */
	protected $from = 0;
	protected $to = array();
	protected $subject = '';
	protected $content = '';
	protected $click_action = '';
	protected $data = [];
	protected $errors = array();
	protected $success_tokens = array();
	protected $deleted_tokens = array();
	protected $updated_tokens = array();
	protected $sent = false;

	/**
	 * Static properties.
	 */
	protected static $_drivers = array();
	
	/**
	 * Add a custom Push driver.
	 * 
	 * @param string $name
	 * @param object $driver
	 * @return void
	 */
	public static function addDriver(string $name, Drivers\PushInterface $driver)
	{
		self::$_drivers[$name] = $driver;
	}
	
	/**
	 * Get the default driver.
	 * 
	 * @param string $name
	 * @return object|null
	 */
	public static function getDriver(string $name)
	{
		if (isset(self::$_drivers[$name]))
		{
			return self::$_drivers[$name];
		}
		
		$driver_class = '\Rhymix\Framework\Drivers\Push\\' . $name;
		if (class_exists($driver_class))
		{
			$driver_config = config('push.' . $name) ?: array();
			return self::$_drivers[$name] = $driver_class::getInstance($driver_config);
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Get the list of supported Push drivers.
	 * 
	 * @return array
	 */
	public static function getSupportedDrivers(): array
	{
		$result = array();
		foreach (Storage::readDirectory(__DIR__ . '/drivers/push', false) as $filename)
		{
			$driver_name = substr($filename, 0, -4);
			$class_name = '\Rhymix\Framework\Drivers\Push\\' . $driver_name;
			if ($class_name::isSupported())
			{
				$result[$driver_name] = array(
					'name' => $class_name::getName(),
					'required' => $class_name::getRequiredConfig(),
					'optional' => $class_name::getOptionalConfig(),
				);
			}
		}
		foreach (self::$_drivers as $driver_name => $driver)
		{
			if ($driver->isSupported())
			{
				$result[$driver_name] = array(
					'name' => $driver->getName(),
					'required' => $driver->getRequiredConfig(),
					'optional' => $driver->getOptionalConfig(),
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
		
	}
	
	/**
	 * Set the sender's member_srl.
	 *
	 * @param int $member_srl
	 * @return bool
	 */
	public function setFrom(int $member_srl): bool
	{
		$this->from = $member_srl;
		return true;
	}
	
	/**
	 * Get the sender's phone number.
	 *
	 * @return int|null
	 */
	public function getFrom(): int
	{
		return intval($this->from);
	}
	
	/**
	 * Add a recipient.
	 *
	 * @param int $member_srl
	 * @return bool
	 */
	public function addTo(int $member_srl): bool
	{
		$this->to[] = $member_srl;
		return true;
	}
	
	/**
	 * Get the list of recipients without country codes.
	 *
	 * @return array
	 */
	public function getRecipients(): array
	{
		return $this->to;
	}
	
	/**
	 * Set the subject.
	 *
	 * @param string $subject
	 * @return bool
	 */
	public function setSubject(string $subject): bool
	{
		$this->subject = utf8_trim(utf8_clean($subject));
		return true;
	}
	
	/**
	 * Get the subject.
	 *
	 * @return string
	 */
	public function getSubject(): string
	{
		return $this->subject;
	}
	
	/**
	 * Set the content.
	 *
	 * @param string $content
	 * @return bool
	 */
	public function setContent(string $content): bool
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
	public function getContent(): string
	{
		return $this->content;
	}
	
	/**
	 * Set an click-action to associate with this push notification.
	 *
	 * @param string $click_action
	 * @return bool
	 */
	public function setClickAction(string $click_action): bool
	{
		$this->click_action = utf8_trim(utf8_clean($click_action));
		return true;
	}
	
	/**
	 * Get the click-action associated with this push notification.
	 * 
	 * @return string
	 */
	public function getClickAction(): string
	{
		return $this->click_action;
	}

	/**
	 * Set a data to associate with this push notification.
	 *
	 * @param array $data
	 * @return bool
	 */
	public function setData(array $data): bool
	{
		$this->data = $data;
		return true;
	}
	
	/**
	 * Get the data associated with this push notification.
	 * 
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}
	
	/**
	 * Set a URL to associate with this push notification.
	 *
	 * @param string $url
	 * @return bool
	 */
	public function setURL(string $url): bool
	{
		$this->data['url'] = $url;
		return true;
	}
	
	/**
	 * Get the URL associated with this push notification.
	 * 
	 * @return string
	 */
	public function getURL(): string
	{
		return $this->data['url'];
	}
	
	/**
	 * Send the message.
	 * 
	 * @return bool
	 */
	public function send(): bool
	{
		// Get caller information.
		$backtrace = debug_backtrace(0);
		if(count($backtrace) && isset($backtrace[0]['file']))
		{
			$this->caller = $backtrace[0]['file'] . ($backtrace[0]['line'] ? (' line ' . $backtrace[0]['line']) : '');
		}
		
		$output = \ModuleHandler::triggerCall('push.send', 'before', $this);
		if(!$output->toBool())
		{
			$this->errors[] = $output->getMessage();
			return false;
		}
		
		try
		{
			$tokens = $this->_getDeviceTokens();
			$output = null;
			
			// Android FCM
			if(count($tokens->fcm))
			{
				$fcm_driver = $this->getDriver('fcm');
				$output = $fcm_driver->send($this, $tokens->fcm);
				$this->sent = count($output->success) ? true : false;
				$this->success_tokens = $output ? $output->success : [];
				$this->deleted_tokens = $output ? $output->invalid : [];
				$this->updated_tokens = $output ? $output->needUpdate : [];
				$this->_deleteInvalidTokens($output->invalid);
				$this->_updateDeviceTokens($output->needUpdate);
			}

			// iOS APNs
			if(count($tokens->apns))
			{
				$apns_driver =$this->getDriver('apns');
				$output = $apns_driver->send($this, $tokens->apns);
				$this->sent = count($output->success) ? true : false;
				$this->success_tokens += $output ? $output->success : [];
				$this->deleted_tokens += $output ? $output->invalid : [];
				$this->updated_tokens += $output ? $output->needUpdate : [];
				$this->_deleteInvalidTokens($output->invalid);
				$this->_updateDeviceTokens($output->needUpdate);
			}
			
		}
		catch(\Exception $e)
		{
			$this->errors[] = class_basename($e) . ': ' . $e->getMessage();
			$this->sent = false;
		}
		
		$output = \ModuleHandler::triggerCall('push.send', 'after', $this);
		if(!$output->toBool())
		{
			$this->errors[] = $output->getMessage();
		}
		
		return $this->sent;
	}

	/**
	 * Get the device token
	 * 
	 * @return \stdClass
	 * 
	 */
	protected function _getDeviceTokens(): \stdClass
	{
		$result = new \stdClass;
		$result->fcm = [];
		$result->apns = [];

		$args = new \stdClass;
		$args->member_srl = $this->getRecipients();
		$args->device_token_type = [];
		$driver_types = config('push.types') ?: array();
		if(isset($driver_types['fcm']))
		{
			$args->device_token_type[] = 'fcm';
		}
		if(isset($driver_types['apns']))
		{
			$args->device_token_type[] = 'apns';
		}
		if(!count($args->device_token_type))
		{
			return $result;
		}

		$output = executeQueryArray('member.getMemberDeviceTokensByMemberSrl', $args);
		if(!$output->toBool() || !$output->data)
		{
			return $result;
		}

		foreach($output->data as $row)
		{
			$result->{$row->device_token_type}[] = $row->device_token;
		}

		return $result;

	}

	/**
	 * Delete the device toekn
	 * 
	 * @param array
	 * @return void
	 */
	protected function _deleteInvalidTokens(array $invalid_tokens)
	{
		if(!count($invalid_tokens))
		{
			return;
		}
		$args = new \stdClass;
		$args->device_token = $invalid_tokens;
		executeQueryArray('member.deleteMemberDevice', $args);
	}

	/**
	 * Update the device toekn
	 * 
	 * @param array
	 * @return void
	 */
	protected function _updateDeviceTokens(array $update_tokens)
	{
		$args = new \stdClass;
		foreach($update_tokens as $key => $value)
		{
			$args->old_token = $key;
			$args->new_token = $value;
			executeQueryArray('member.updateMemberDevice', $args);
		}
	}
	
	/**
	 * Check if the message was sent.
	 * 
	 * @return bool
	 */
	public function isSent(): bool
	{
		return $this->sent;
	}
	
	/**
	 * Get caller information.
	 * 
	 * @return string
	 */
	public function getCaller(): string
	{
		return $this->caller;
	}
	
	/**
	 * Get errors.
	 * 
	 * @return array
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}
	
	/**
	 * Get success tokens.
	 * 
	 * @return array
	 */
	public function getSuccessTokens(): array
	{
		return $this->success_tokens;
	}
	
	/**
	 * Get deleted tokens.
	 * 
	 * @return array
	 */
	public function getDeletedTokens(): array
	{
		return $this->deleted_tokens;
	}
	
	/**
	 * Get updated tokens.
	 * 
	 * @return array
	 */
	public function getUpdatedTokens(): array
	{
		return $this->updated_tokens;
	}
	
	/**
	 * Add an error message.
	 * 
	 * @param string $message
	 * @return void
	 */
	public function addError(string $message)
	{
		$this->errors[] = $message;
	}
}
