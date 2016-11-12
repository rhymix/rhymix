<?php

namespace Rhymix\Framework\Drivers;

/**
 * The SMS driver interface.
 */
interface SMSInterface
{
	/**
	 * Create a new instance of the current SMS driver, using the given settings.
	 * 
	 * @param array $config
	 * @return void
	 */
	public static function getInstance(array $config);
	
	/**
	 * Get the human-readable name of this SMS driver.
	 * 
	 * @return string
	 */
	public static function getName();
	
	/**
	 * Get the list of configuration fields required by this SMS driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig();
	
	/**
	 * Get the list of configuration fields optionally used by this SMS driver.
	 * 
	 * @return array
	 */
	public static function getOptionalConfig();
	
	/**
	 * Get the list of API types supported by this SMS driver.
	 * 
	 * @return array
	 */
	public static function getAPITypes();
	
	/**
	 * Get the spec for this SMS driver.
	 * 
	 * @return array
	 */
	public static function getAPISpec();
	
	/**
	 * Check if the current SMS driver is supported on this server.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public static function isSupported();
	
	/**
	 * Send a message.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param array $messages
	 * @param object $original
	 * @return bool
	 */
	public function send(array $messages, \Rhymix\Framework\SMS $original);
}
