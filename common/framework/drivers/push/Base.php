<?php

namespace Rhymix\Framework\Drivers\Push;

use Rhymix\Framework\Drivers\PushInterface;

/**
 * The base class for other Push drivers.
 */
abstract class Base implements PushInterface
{
	/**
	 * The configuration is stored here.
	 */
	protected $_config = null;
	
	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array();
	protected static $_optional_config = array();
	
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct(array $config)
	{
		$this->_config = $config;
	}
	
	/**
	 * Create a new instance of the current Push driver, using the given settings.
	 * 
	 * @param array $config
	 * @return PushInterface
	 */
	public static function getInstance(array $config): PushInterface
	{
		return new static($config);
	}
	
	/**
	 * Get the human-readable name of this Push driver.
	 * 
	 * @return string
	 */
	public static function getName(): string
	{
		return class_basename(get_called_class());
	}
	
	/**
	 * Get the list of configuration fields required by this Push driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig(): array
	{
		return static::$_required_config;
	}
	
	/**
	 * Get the list of configuration fields optionally used by this Push driver.
	 * 
	 * @return array
	 */
	public static function getOptionalConfig(): array
	{
		return static::$_optional_config;
	}
	
	/**
	 * Check if the current Push driver is supported on this server.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public static function isSupported(): bool
	{
		return false;
	}
	
	/**
	 * Send a message.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param object $message
	 * @param array $tokens
	 * @return \stdClass
	 */
	public function send(\Rhymix\Framework\Push $message, array $tokens): \stdClass
	{
		return new \stdClass;
	}
}
