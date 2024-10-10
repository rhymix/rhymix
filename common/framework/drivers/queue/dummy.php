<?php

namespace Rhymix\Framework\Drivers\Queue;

use Rhymix\Framework\Drivers\QueueInterface;

/**
 * The Dummy queue driver.
 */
class Dummy implements QueueInterface
{
	/**
	 * Create a new instance of the current Queue driver, using the given settings.
	 *
	 * @param array $config
	 * @return void
	 */
	public static function getInstance(array $config): self
	{
		return new self($config);
	}

	/**
	 * Get the human-readable name of this Queue driver.
	 *
	 * @return string
	 */
	public static function getName(): string
	{
		return 'Dummy';
	}

	/**
	 * Get the list of configuration fields required by this Queue driver.
	 *
	 * @return array
	 */
	public static function getRequiredConfig(): array
	{
		return [];
	}

	/**
	 * Get the list of configuration fields optionally used by this Queue driver.
	 *
	 * @return array
	 */
	public static function getOptionalConfig(): array
	{
		return [];
	}

	/**
	 * Check if this driver is supported on this server.
	 *
	 * @return bool
	 */
	public static function isSupported(): bool
	{
		return true;
	}

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{

	}

	/**
	 * Add a task.
	 *
	 * @param string $handler
	 * @param ?object $args
	 * @param ?object $options
	 * @return int
	 */
	public function addTask(string $handler, ?object $args = null, ?object $options = null): int
	{
		return 0;
	}

	/**
	 * Get the first task.
	 *
	 * @param int $blocking
	 * @return ?object
	 */
	public function getTask(int $blocking = 0): ?object
	{
		return null;
	}
}
