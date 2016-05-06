<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The base class for other mail drivers.
 */
abstract class Base implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * The configuration is stored here.
	 */
	protected $_config = null;
	
	/**
	 * The mailer instance is stored here.
	 */
	protected $_mailer = null;
	
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
	 * @return void
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
	 * Check if the current mail driver is supported on this server.
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
	 * @param object $message
	 * @return bool
	 */
	public function send(\Rhymix\Framework\Mail $message)
	{
		return false;
	}
}
