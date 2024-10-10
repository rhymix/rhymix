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
	protected $caller = '';
	protected $from = 0;
	protected $to = [];
	protected $topics = [];
	protected $subject = '';
	protected $content = '';
	protected $image = '';
	protected $metadata = [];
	protected $data = [];
	protected $errors = [];
	protected $success_tokens = [];
	protected $deleted_tokens = [];
	protected $updated_tokens = [];
	protected $sent = 0;

	/**
	 * Static properties.
	 */
	protected static $_drivers = [];

	/**
	 * Add a custom Push driver.
	 *
	 * @param string $name
	 * @param object $driver
	 * @return void
	 */
	public static function addDriver(string $name, Drivers\PushInterface $driver): void
	{
		self::$_drivers[$name] = $driver;
	}

	/**
	 * Get the default driver.
	 *
	 * @param string $name
	 * @return ?object
	 */
	public static function getDriver(string $name): ?object
	{
		if (isset(self::$_drivers[$name]))
		{
			return self::$_drivers[$name];
		}

		$driver_class = '\Rhymix\Framework\Drivers\Push\\' . $name;
		if (class_exists($driver_class))
		{
			$driver_config = config('push.' . $name) ?: [];
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
		$result = [];
		foreach (Storage::readDirectory(__DIR__ . '/drivers/push', false) as $filename)
		{
			$driver_name = substr($filename, 0, -4);
			$class_name = '\Rhymix\Framework\Drivers\Push\\' . $driver_name;
			if ($class_name::isSupported())
			{
				$result[$driver_name] = [
					'name' => $class_name::getName(),
					'required' => $class_name::getRequiredConfig(),
					'optional' => $class_name::getOptionalConfig(),
				];
			}
		}
		foreach (self::$_drivers as $driver_name => $driver)
		{
			if ($driver->isSupported())
			{
				$result[$driver_name] = [
					'name' => $driver->getName(),
					'required' => $driver->getRequiredConfig(),
					'optional' => $driver->getOptionalConfig(),
				];
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
	 * Get the list of recipients.
	 *
	 * @return array
	 */
	public function getRecipients(): array
	{
		return $this->to;
	}

	/**
	 * Add a topic.
	 *
	 * @param string $topic
	 * @return bool
	 */
	public function addTopic(string $topic): bool
	{
		$this->topics[] = $topic;
		return true;
	}

	/**
	 * Get the list of topics.
	 *
	 * @return array
	 */
	public function getTopics(): array
	{
		return $this->topics;
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
		$this->content = strtr($this->content, ["\r\n" => "\n"]);
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
	 * Set the image.
	 *
	 * @param string $url
	 * @return bool
	 */
	public function setImage(string $url): bool
	{
		$url = preg_replace('!^\./!', URL::getCurrentDomainURL(\RX_BASEURL), $url);
		$this->image = $url;
		return true;
	}

	/**
	 * Get the image.
	 *
	 * @return string
	 */
	public function getImage(): string
	{
		return $this->image;
	}

	/**
	 * Set an click-action to associate with this push notification.
	 *
	 * @param string $click_action
	 * @return bool
	 */
	public function setClickAction(string $click_action): bool
	{
		$this->metadata['click_action'] = utf8_trim(utf8_clean($click_action));
		return true;
	}

	/**
	 * Get the click-action associated with this push notification.
	 *
	 * @return string
	 */
	public function getClickAction(): string
	{
		return $this->metadata['click_action'];
	}

	/**
	 * Set a sound to associate with this push notification.
	 *
	 * @param string $sound
	 * @return bool
	 */
	public function setSound(string $sound): bool
	{
		$this->metadata['sound'] = utf8_trim(utf8_clean($sound));
		return true;
	}

	/**
	 * Set a badge to associate with this push notification.
	 *
	 * @param string $badge
	 * @return bool
	 */
	public function setBadge(string $badge): bool
	{
		$this->metadata['badge'] = utf8_trim(utf8_clean($badge));
		return true;
	}

	/**
	 * Set an icon to associate with this push notification.
	 *
	 * @param string $icon
	 * @return bool
	 */
	public function setIcon(string $icon): bool
	{
		$this->metadata['icon'] = utf8_trim(utf8_clean($icon));
		return true;
	}

	/**
	 * Set a tag to associate with this push notification.
	 *
	 * @param string $tag
	 * @return bool
	 */
	public function setTag(string $tag): bool
	{
		$this->metadata['tag'] = utf8_trim(utf8_clean($tag));
		return true;
	}

	/**
	 * Set a color to associate with this push notification.
	 *
	 * @param string $color
	 * @return bool
	 */
	public function setColor(string $color): bool
	{
		$this->metadata['color'] = utf8_trim(utf8_clean($color));
		return true;
	}

	/**
	 * Set an android-channel-id to associate with this push notification.
	 *
	 * @param string $android_channel_id
	 * @return bool
	 */
	public function setAndroidChannelId(string $android_channel_id): bool
	{
		$this->metadata['channel_id'] = utf8_trim(utf8_clean($android_channel_id));
		return true;
	}

	/**
	 * Get notification array
	 *
	 * @return array
	 */
	public function getMetadata(): array
	{
		return array_filter($this->metadata, function($val) {
			return $val !== '';
		});
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
		// If queue is enabled, send asynchronously.
		if (config('queue.enabled') && !defined('RXQUEUE_CRON'))
		{
			Queue::addTask(self::class . '::' . 'sendAsync', $this);
			return true;
		}

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

			// FCM HTTP v1 or Legacy API
			if(count($tokens->fcm) || count($this->topics))
			{
				$fcm_driver_name = array_key_exists('fcmv1', config('push.types') ?: []) ? 'fcmv1' : 'fcm';
				$fcm_driver = $this->getDriver($fcm_driver_name);
				$output = $fcm_driver->send($this, $tokens->fcm);
				$this->sent += count($output->success);
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
				$this->sent += count($output->success);
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

		return $this->sent > 0 ? true : false;
	}

	/**
	 * Send asynchronously (for Queue integration).
	 *
	 * @param self $sms
	 * @return void
	 */
	public static function sendAsync(self $push): void
	{
		$push->send();
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
		$driver_types = config('push.types') ?: [];
		if(isset($driver_types['fcm']) || isset($driver_types['fcmv1']))
		{
			$args->device_token_type[] = 'fcm';
		}
		if(isset($driver_types['apns']))
		{
			$args->device_token_type[] = 'apns';
		}
		if(!count($args->device_token_type) || !count($args->member_srl))
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
	 * @return bool
	 */
	protected function _deleteInvalidTokens(array $invalid_tokens): bool
	{
		if(!count($invalid_tokens))
		{
			return true;
		}
		$args = new \stdClass;
		$args->device_token = $invalid_tokens;
		$output = executeQueryArray('member.deleteMemberDevice', $args);
		return $output->toBool();
	}

	/**
	 * Update the device toekn
	 *
	 * @param array
	 * @return bool
	 */
	protected function _updateDeviceTokens(array $update_tokens): bool
	{
		$args = new \stdClass;
		$result = true;
		foreach($update_tokens as $key => $value)
		{
			$args->old_token = $key;
			$args->new_token = $value;
			$output = executeQueryArray('member.updateMemberDevice', $args);
			if (!$output->toBool())
			{
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Check if the message was sent.
	 *
	 * @return bool
	 */
	public function isSent(): bool
	{
		return $this->sent > 0 ? true : false;
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
	public function addError(string $message): void
	{
		$this->errors[] = $message;
	}
}
