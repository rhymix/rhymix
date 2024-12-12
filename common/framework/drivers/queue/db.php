<?php

namespace Rhymix\Framework\Drivers\Queue;

use Rhymix\Framework\DB as RFDB;
use Rhymix\Framework\Drivers\QueueInterface;
use Rhymix\Framework\Queue;

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
		$stmt = $oDB->prepare('INSERT INTO task_queue (handler, args, options, regdate) VALUES (?, ?, ?, ?)');
		$result = $stmt->execute([$handler, serialize($args), serialize($options), date('Y-m-d H:i:s')]);
		return $result ? $oDB->getInsertID() : 0;
	}

	/**
	 * Add a task to be executed at a specific time.
	 *
	 * @param int $time
	 * @param string $handler
	 * @param ?object $args
	 * @param ?object $options
	 * @return int
	 */
	public function addTaskAt(int $time, string $handler, ?object $args = null, ?object $options = null): int
	{
		$oDB = RFDB::getInstance();
		$task_srl = getNextSequence();
		$stmt = $oDB->prepare(trim(<<<END
			INSERT INTO task_schedule
			(task_srl, task_type, first_run, handler, args, options, regdate)
			VALUES (?, ?, ?, ?, ?, ?, ?)
		END));
		$result = $stmt->execute([
			$task_srl,
			'once',
			date('Y-m-d H:i:s', $time),
			$handler,
			serialize($args),
			serialize($options),
			date('Y-m-d H:i:s'),
		]);
		return $result ? $task_srl : 0;
	}

	/**
	 * Add a task to be executed at an interval.
	 *
	 * @param string $interval
	 * @param string $handler
	 * @param ?object $args
	 * @param ?object $options
	 * @return int
	 */
	public function addTaskAtInterval(string $interval, string $handler, ?object $args = null, ?object $options = null): int
	{
		$oDB = RFDB::getInstance();
		$task_srl = getNextSequence();
		$stmt = $oDB->prepare(trim(<<<END
			INSERT INTO task_schedule
			(task_srl, task_type, run_interval, handler, args, options, regdate)
			VALUES (?, ?, ?, ?, ?, ?, ?)
		END));
		$result = $stmt->execute([
			$task_srl,
			'interval',
			$interval,
			$handler,
			serialize($args),
			serialize($options),
			date('Y-m-d H:i:s'),
		]);
		return $result ? $task_srl : 0;
	}

	/**
	 * Get the next task from the queue.
	 *
	 * @param int $blocking
	 * @return ?object
	 */
	public function getNextTask(int $blocking = 0): ?object
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

	/**
	 * Get a scheduled task by its task_srl.
	 *
	 * @param int $task_srl
	 * @return ?object
	 */
	public function getScheduledTask(int $task_srl): ?object
	{
		$oDB = RFDB::getInstance();
		$stmt = $oDB->query('SELECT * FROM task_schedule WHERE task_srl = ?', [$task_srl]);
		$task = $stmt->fetchObject();
		$stmt->closeCursor();

		if ($task)
		{
			$task->args = unserialize($task->args);
			$task->options = unserialize($task->options);
			return $task;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get scheduled tasks.
	 *
	 * @param string $type
	 * @return array
	 */
	public function getScheduledTasks(string $type): array
	{
		$oDB = RFDB::getInstance();
		$tasks = [];
		$task_srls = [];

		// Get tasks to be executed once at the current time.
		if ($type === 'once')
		{
			$oDB->beginTransaction();
			$timestamp = date('Y-m-d H:i:s');
			$stmt = $oDB->query("SELECT * FROM task_schedule WHERE task_type = 'once' AND first_run <= ? ORDER BY first_run FOR UPDATE", [$timestamp]);
			while ($task = $stmt->fetchObject())
			{
				$task->args = unserialize($task->args);
				$task->options = unserialize($task->options);
				$tasks[] = $task;
				$task_srls[] = $task->task_srl;
			}
			if (count($task_srls))
			{
				$stmt = $oDB->prepare('DELETE FROM task_schedule WHERE task_srl IN (' . implode(', ', array_fill(0, count($task_srls), '?')) . ')');
				$stmt->execute($task_srls);
			}
			$oDB->commit();
		}

		// Get tasks to be executed at an interval.
		if ($type === 'interval')
		{
			$stmt = $oDB->query("SELECT task_srl, run_interval FROM task_schedule WHERE task_type = 'interval' ORDER BY task_srl");
			while ($task = $stmt->fetchObject())
			{
				if (Queue::parseInterval($task->run_interval, time()))
				{
					$task_srls[] = $task->task_srl;
				}
			}
			if (count($task_srls))
			{
				$stmt = $oDB->prepare('SELECT * FROM task_schedule WHERE task_srl IN (' . implode(', ', array_fill(0, count($task_srls), '?')) . ')');
				$stmt->execute($task_srls);
				while ($task = $stmt->fetchObject())
				{
					$task->args = unserialize($task->args);
					$task->options = unserialize($task->options);
					$tasks[] = $task;
				}
			}
		}

		return $tasks;
	}

	/**
	 * Update the last executed timestamp of a scheduled task.
	 *
	 * @param object $task
	 * @return void
	 */
	public function updateLastRunTimestamp(object $task): void
	{
		$oDB = RFDB::getInstance();
		if ($task->first_run)
		{
			$stmt = $oDB->prepare('UPDATE task_schedule SET last_run = ?, run_count = run_count + 1 WHERE task_srl = ?');
			$stmt->execute([date('Y-m-d H:i:s'), $task->task_srl]);
		}
		else
		{
			$stmt = $oDB->prepare('UPDATE task_schedule SET first_run = ?, last_run = ?, run_count = run_count + 1 WHERE task_srl = ?');
			$stmt->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $task->task_srl]);
		}
	}

	/**
	 * Cancel a scheduled task.
	 *
	 * @param int $task_srl
	 * @return bool
	 */
	public function cancelScheduledTask(int $task_srl): bool
	{
		$oDB = RFDB::getInstance();
		$stmt = $oDB->query('DELETE FROM task_schedule WHERE task_srl = ?', [$task_srl]);
		return ($stmt && $stmt->rowCount()) ? true : false;
	}
}
