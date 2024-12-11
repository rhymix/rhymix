<?php

namespace Rhymix\Framework\Drivers\Queue;

use Rhymix\Framework\Drivers\QueueInterface;

/**
 * The Dummy queue driver.
 */
class Dummy implements QueueInterface
{
	/**
	 * Dummy queue for testing.
	 */
	protected $_dummy_queue;

	/**
	 * Create a new instance of the current Queue driver, using the given settings.
	 *
	 * @param array $config
	 * @return QueueInterface
	 */
	public static function getInstance(array $config): QueueInterface
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
	 * Validate driver configuration.
	 *
	 * @param mixed $config
	 * @return bool
	 */
	public static function validateConfig($config): bool
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
		$this->_dummy_queue = (object)[
			'handler' => $handler,
			'args' => $args,
			'options' => $options,
		];
		return 0;
	}

	/**
	 * Get the next task from the queue.
	 *
	 * @param int $blocking
	 * @return ?object
	 */
	public function getNextTask(int $blocking = 0): ?object
	{
		$result = $this->_dummy_queue;
		$this->_dummy_queue = null;
		return $result;
	}
}
