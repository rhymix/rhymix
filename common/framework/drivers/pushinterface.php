<?php

namespace Rhymix\Framework\Drivers;

/**
 * The Push driver interface.
 */
interface PushInterface
{
	/**
	 * Create a new instance of the current Push driver, using the given settings.
	 * 
	 * @param array $config
	 * @return void
	 */
	public static function getInstance(array $config): object;
	
	/**
	 * Get the human-readable name of this Push driver.
	 * 
	 * @return string
	 */
	public static function getName(): string;
	
	/**
	 * Get the list of configuration fields required by this Push driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig(): array;
	
	/**
	 * Get the list of configuration fields optionally used by this Push driver.
	 * 
	 * @return array
	 */
	public static function getOptionalConfig(): array;
	
	/**
	 * Check if the current SMS driver is supported on this server.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public static function isSupported(): bool;
	
	/**
	 * Send a message.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param object $message
	 * @param array $tokens
	 * @return \stdClass
	 */
	public function send(\Rhymix\Framework\Push $message, array $tokens): \stdClass;
}
