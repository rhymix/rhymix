<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The base class for other SMS drivers.
 */
abstract class Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	/**
	 * The configuration is stored here.
	 */
	protected $_config = null;
	
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct(array $config)
	{
		$this->_config = $config;
	}
	
	/**
	 * Create a new instance of the current mail driver, using the given settings.
	 * 
	 * @param array $config
	 * @return object
	 */
	public static function getInstance(array $config)
	{
		return new static($config);
	}
	
	/**
	 * Get the human-readable name of this mail driver.
	 * 
	 * @return string
	 */
	public static function getName()
	{
		return class_basename(get_called_class());
	}
	
	/**
	 * Get the list of configuration fields required by this mail driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array();
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
	 * Check if the current mail driver is supported on this server.
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
	 * @param object $message
	 * @return bool
	 */
	public function send(\Rhymix\Framework\SMS $message)
	{
		return false;
	}
}
