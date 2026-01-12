<?php

namespace Rhymix\Framework\Drivers\SMS;

use Rhymix\Framework\Drivers\SMSInterface;
use Rhymix\Framework\SMS;

/**
 * The base class for other SMS drivers.
 */
abstract class Base implements SMSInterface
{
	/**
	 * The configuration is stored here.
	 */
	protected $_config = null;

	/**
	 * The driver specification is stored here.
	 */
	protected static $_spec = array();

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
	 * Create a new instance of the current SMS driver, using the given settings.
	 *
	 * @param array $config
	 * @return SMSInterface
	 */
	public static function getInstance(array $config): SMSInterface
	{
		return new static($config);
	}

	/**
	 * Get the human-readable name of this SMS driver.
	 *
	 * @return string
	 */
	public static function getName()
	{
		return class_basename(get_called_class());
	}

	/**
	 * Get the list of configuration fields required by this SMS driver.
	 *
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return static::$_required_config;
	}

	/**
	 * Get the list of configuration fields optionally used by this SMS driver.
	 *
	 * @return array
	 */
	public static function getOptionalConfig()
	{
		return static::$_optional_config;
	}

	/**
	 * Get the list of API types supported by this SMS driver.
	 *
	 * @return array
	 */
	public static function getAPITypes()
	{
		return array();
	}

	/**
	 * Get the spec for this SMS driver.
	 *
	 * @return array
	 */
	public static function getAPISpec()
	{
		return static::$_spec;
	}

	/**
	 * Check if the current SMS driver is supported on this server.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @return bool
	 */
	public static function isSupported()
	{
		return false;
	}

	/**
	 * Send a message.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @param array $messages
	 * @param SMS $original
	 * @return bool
	 */
	public function send(array $messages, SMS $original)
	{
		return false;
	}
}
