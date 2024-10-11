<?php

namespace Rhymix\Framework\Drivers\Queue;

use Rhymix\Framework\DB as RFDB;
use Rhymix\Framework\Drivers\QueueInterface;

/**
 * The DB queue driver.
 */
class DB implements QueueInterface
{
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
		return 'DB';
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
		$oDB = RFDB::getInstance();
		$stmt = $oDB->prepare('INSERT INTO task_queue (handler, args, options) VALUES (?, ?, ?)');
		$result = $stmt->execute([$handler, serialize($args), serialize($options)]);
		return $result ? $oDB->getInsertID() : 0;
	}

	/**
	 * Get the first task.
	 *
	 * @param int $blocking
	 * @return ?object
	 */
	public function getTask(int $blocking = 0): ?object
	{
		$oDB = RFDB::getInstance();
		$oDB->beginTransaction();
		$stmt = $oDB->query('SELECT * FROM task_queue ORDER BY id LIMIT 1 FOR UPDATE');
		$result = $stmt->fetchObject();
		$stmt->closeCursor();

		if ($result)
		{
			$stmt = $oDB->prepare('DELETE FROM task_queue WHERE id = ?');
			$stmt->execute([$result->id]);
			$oDB->commit();

			$result->args = unserialize($result->args);
			$result->options = unserialize($result->options);
			return $result;
		}
		else
		{
			$oDB->commit();
			return null;
		}
	}
}
