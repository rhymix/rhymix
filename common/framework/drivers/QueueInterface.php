<?php

namespace Rhymix\Framework\Drivers;

/**
 * The Queue driver interface.
 */
interface QueueInterface
{
	/**
	 * Create a new instance of the current Queue driver, using the given settings.
	 *
	 * @param array $config
	 * @return void
	 */
	public static function getInstance(array $config): self;

	/**
	 * Get the human-readable name of this Queue driver.
	 *
	 * @return string
	 */
	public static function getName(): string;

	/**
	 * Get the list of configuration fields required by this Queue driver.
	 *
	 * @return array
	 */
	public static function getRequiredConfig(): array;

	/**
	 * Get the list of configuration fields optionally used by this Queue driver.
	 *
	 * @return array
	 */
	public static function getOptionalConfig(): array;

	/**
	 * Check if this driver is supported on this server.
	 *
	 * @return bool
	 */
	public static function isSupported(): bool;

	/**
	 * Add a task.
	 *
	 * @param string $handler
	 * @param ?object $args
	 * @param ?object $options
	 * @return int
	 */
	public function addTask(string $handler, ?object $args = null, ?object $options = null): int;

	/**
	 * Get the first task.
	 *
	 * @param int $blocking
	 * @return ?object
	 */
	public function getTask(int $blocking = 0): ?object;
}