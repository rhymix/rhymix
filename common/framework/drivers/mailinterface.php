<?php

namespace Rhymix\Framework\Drivers;

/**
 * The mail driver interface.
 */
interface MailInterface
{
	/**
	 * Create a new instance of the current mail driver, using the given settings.
	 * 
	 * @param array $config
	 * @return void
	 */
	public static function getInstance(array $config);
	
	/**
	 * Get the human-readable name of this mail driver.
	 * 
	 * @return string
	 */
	public static function getName();
	
	/**
	 * Get the list of configuration fields required by this mail driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig();
	
	/**
	 * Get the list of API types supported by this mail driver.
	 * 
	 * @return array
	 */
	public static function getAPITypes();
	
	/**
	 * Get the SPF hint.
	 * 
	 * @return string
	 */
	public static function getSPFHint();
	
	/**
	 * Get the DKIM hint.
	 * 
	 * @return string
	 */
	public static function getDKIMHint();
	
	/**
	 * Check if the current mail driver is supported on this server.
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
	 * @param object $message
	 * @return bool
	 */
	public function send(\Rhymix\Framework\Mail $message);
}
