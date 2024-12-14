<?php

namespace Rhymix\Framework;

/**
 * The Queue class.
 */
class Queue
{
	/**
	 * Static properties.
	 */
	protected static $_drivers = [];

	/**
	 * Add a custom Queue driver.
	 *
	 * @param string $name
	 * @param object $driver
	 * @return void
	 */
	public static function addDriver(string $name, Drivers\QueueInterface $driver): void
	{
		self::$_drivers[$name] = $driver;
	}

	/**
	 * Get a Queue driver instance.
	 *
	 * @param string $name
	 * @return ?Drivers\QueueInterface
	 */
	public static function getDriver(string $name): ?Drivers\QueueInterface
	{
		if (isset(self::$_drivers[$name]))
		{
			return self::$_drivers[$name];
		}

		$driver_class = '\Rhymix\Framework\Drivers\Queue\\' . $name;
		if (class_exists($driver_class))
		{
			$driver_config = config('queue.' . $name) ?: [];
			return self::$_drivers[$name] = $driver_class::getInstance($driver_config);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get the DB driver instance, for managing scheduled tasks.
	 *
	 * @return Drivers\Queue\DB
	 */
	public static function getDbDriver(): Drivers\Queue\DB
	{
		return self::getDriver('db');
	}

	/**
	 * Get the list of supported Queue drivers.
	 *
	 * @return array
	 */
	public static function getSupportedDrivers(): array
	{
		$result = [];
		foreach (Storage::readDirectory(__DIR__ . '/drivers/queue', false) as $filename)
		{
			$driver_name = substr($filename, 0, -4);
			$class_name = '\Rhymix\Framework\Drivers\Queue\\' . $driver_name;
			if ($class_name::isSupported())
			{
				$result[$driver_name] = [
					'name' => $class_name::getName(),
					'required' => $class_name::getRequiredConfig(),
					'optional' => $class_name::getOptionalConfig(),
				];
			}
		}
		foreach (self::$_drivers as $driver_name => $driver)
		{
			if ($driver->isSupported())
			{
				$result[$driver_name] = [
					'name' => $driver->getName(),
					'required' => $driver->getRequiredConfig(),
					'optional' => $driver->getOptionalConfig(),
				];
			}
		}
		ksort($result);
		return $result;
	}

	/**
	 * Add a task to the queue.
	 *
	 * The queued task will be executed as soon as possible.
	 *
	 * The handler can be in one of the following formats:
	 *   - Global function, e.g. myHandler
	 *   - ClassName::staticMethodName
	 *   - ClassName::getInstance()->methodName
	 *   - new ClassName()->methodName
	 *
	 * Once identified and/or instantiated, the handler will be passed $args
	 * and $options, in that order. Each of them must be a single object.
	 *
	 * It is strongly recommended that you write a dedicated method to handle
	 * queued tasks, rather than reusing an existing method with a potentially
	 * incompatible structure. If you must to call an existing method,
	 * you should consider writing a wrapper.
	 *
	 * Any value returned by the handler will be discarded. If you throw an
	 * exception, it may be logged, but it will not cause a fatal error.
	 *
	 * @param string $handler
	 * @param ?object $args
	 * @param ?object $options
	 * @return int
	 */
	public static function addTask(string $handler, ?object $args = null, ?object $options = null): int
	{
		$driver_name = config('queue.driver');
		if (!$driver_name)
		{
			throw new Exceptions\FeatureDisabled('Queue not configured');
		}

		$driver = self::getDriver($driver_name);
		if (!$driver)
		{
			throw new Exceptions\FeatureDisabled('Queue not configured');
		}

		return $driver->addTask($handler, $args, $options);
	}

	/**
	 * Add a task to be executed at a specific time.
	 *
	 * The queued task will be executed once at the configured time.
	 * The rest is identical to addTask().
	 *
	 * @param int $time
	 * @param string $handler
	 * @param ?object $args
	 * @param ?object $options
	 * @return int
	 */
	public static function addTaskAt(int $time, string $handler, ?object $args = null, ?object $options = null): int
	{
		if (!config('queue.enabled'))
		{
			throw new Exceptions\FeatureDisabled('Queue not configured');
		}

		// This feature always uses the DB driver.
		$driver = self::getDbDriver();
		return $driver->addTaskAt($time, $handler, $args, $options);
	}

	/**
	 * Add a task to be executed at an interval.
	 *
	 * The queued task will be executed repeatedly at the scheduled interval.
	 * The synax for specifying the interval is the same as crontab.
	 * The rest is identical to addTask().
	 *
	 * @param string $interval
	 * @param string $handler
	 * @param ?object $args
	 * @param ?object $options
	 * @return int
	 */
	public static function addTaskAtInterval(string $interval, string $handler, ?object $args = null, ?object $options = null): int
	{
		if (!config('queue.enabled'))
		{
			throw new Exceptions\FeatureDisabled('Queue not configured');
		}

		// Validate the interval syntax.
		if (!self::checkIntervalSyntax($interval))
		{
			throw new Exceptions\InvalidRequest('Invalid interval syntax: ' . $interval);
		}

		// This feature always uses the DB driver.
		$driver = self::getDbDriver();
		return $driver->addTaskAtInterval($interval, $handler, $args, $options);
	}

	/**
	 * Get information about a scheduled task if it exists.
	 *
	 * @param int $task_srl
	 * @return ?object
	 */
	public static function getScheduledTask(int $task_srl): ?object
	{
		$driver = self::getDbDriver();
		return $driver->getScheduledTask($task_srl);
	}

	/**
	 * Cancel a scheduled task.
	 *
	 * @param int $task_srl
	 * @return bool
	 */
	public static function cancelScheduledTask(int $task_srl): bool
	{
		$driver = self::getDbDriver();
		return $driver->cancelScheduledTask($task_srl);
	}

	/**
	 * Check the process key.
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function checkKey(string $key): bool
	{
		return config('queue.key') === $key;
	}

	/**
	 * Check the interval syntax.
	 *
	 * This method returns true if the interval string is well-formed,
	 * and false otherwise. However, it does not check that all the numbers
	 * are in the correct range (e.g. 0-59 for minutes).
	 *
	 * @param string $interval
	 * @return bool
	 */
	public static function checkIntervalSyntax(string $interval): bool
	{
		$parts = preg_split('/\s+/', $interval);
		if (!$parts || count($parts) !== 5)
		{
			return false;
		}
		foreach ($parts as $part)
		{
			if (!preg_match('!^(?:\\*(?:/\d+)?|\d+(?:-\d+)?(?:,\d+(?:-\d+)?)*)$!', $part))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Parse an interval string check it against a timestamp.
	 *
	 * This method returns true if the interval covers the given timestamp,
	 * and false otherwise.
	 *
	 * @param string $interval
	 * @param ?int $time
	 * @return bool
	 */
	public static function parseInterval(string $interval, ?int $time): bool
	{
		$parts = preg_split('/\s+/', $interval);
		if (!$parts || count($parts) !== 5)
		{
			return false;
		}

		$current_time = explode(' ', date('i G j n w', $time ?? time()));
		foreach ($parts as $i => $part)
		{
			$subparts = explode(',', $part);
			foreach ($subparts as $subpart)
			{
				if ($subpart === '*' || ltrim($subpart, '0') === ltrim($current_time[$i], '0'))
				{
					continue 2;
				}
				if ($subpart === '7' && $i === 4 && intval($current_time[$i], 10) === 0)
				{
					continue 2;
				}
				if (preg_match('!^\\*/(\d+)?$!', $subpart, $matches) && ($div = intval($matches[1], 10)) && (intval($current_time[$i], 10) % $div === 0))
				{
					continue 2;
				}
				if (preg_match('!^(\d+)-(\d+)$!', $subpart, $matches) && intval($current_time[$i], 10) >= intval($matches[1], 10) && intval($current_time[$i], 10) <= intval($matches[2], 10))
				{
					continue 2;
				}
			}
			return false;
		}
		return true;
	}

	/**
	 * Process queued and scheduled tasks.
	 *
	 * This will usually be called by a separate script, run every minute
	 * through an external scheduler such as crontab or systemd.
	 *
	 * If you are on a shared hosting service, you may also call a URL
	 * using a "web cron" service provider.
	 *
	 * @param int $index
	 * @param int $count
	 * @param int $timeout
	 * @return void
	 */
	public static function process(int $index, int $count, int $timeout): void
	{
		// This part will run in a loop until timeout.
		$process_start_time = microtime(true);

		// Get default driver instance.
		$driver_name = config('queue.driver');
		$driver = self::getDriver($driver_name);
		if (!$driver_name || !$driver)
		{
			throw new Exceptions\FeatureDisabled('Queue not configured');
		}

		// Process scheduled tasks.
		if ($index === 0)
		{
			$db_driver = self::getDbDriver();
			$tasks = $db_driver->getScheduledTasks('once');
			foreach ($tasks as $task)
			{
				self::_executeTask($task);
			}
		}
		if ($index === 1 || $count < 2)
		{
			$db_driver = self::getDbDriver();
			$tasks = $db_driver->getScheduledTasks('interval');
			foreach ($tasks as $task)
			{
				$db_driver->updateLastRunTimestamp($task);
				self::_executeTask($task);
			}
		}

		// Process queued tasks.
		while (true)
		{
			// Get a task from the driver, with a 1 second delay at maximum.
			$loop_start_time = microtime(true);
			$task = $driver->getNextTask(1);
			if ($task)
			{
				self::_executeTask($task);
			}

			// If the timeout is imminent, break the loop.
			$process_elapsed_time = microtime(true) - $process_start_time;
			if ($process_elapsed_time > $timeout - 2)
			{
				break;
			}

			// If there was no task, wait 1 second to make sure that the loop isn't too tight.
			$loop_elapsed_time = microtime(true) - $loop_start_time;
			if (!$task && $loop_elapsed_time < 1)
			{
				usleep(intval((1 - $loop_elapsed_time) * 1000000));
			}
		}
	}

	/**
	 * Execute a task.
	 *
	 * @param object $task
	 * @return void
	 */
	protected static function _executeTask(object $task): void
	{
		// Find the handler for the task.
		$task->handler = trim($task->handler, '\\()');
		$handler = null;
		try
		{
			if (preg_match('/^(?:\\\\)?([\\\\\\w]+)::(\\w+)$/', $task->handler, $matches))
			{
				$class_name = '\\' . $matches[1];
				$method_name = $matches[2];
				if (class_exists($class_name) && method_exists($class_name, $method_name))
				{
					$handler = [$class_name, $method_name];
				}
				else
				{
					error_log('RxQueue: task handler not found: ' . $task->handler);
				}
			}
			elseif (preg_match('/^(?:\\\\)?([\\\\\\w]+)::(\\w+)(?:\(\))?->(\\w+)$/', $task->handler, $matches))
			{
				$class_name = '\\' . $matches[1];
				$initializer_name = $matches[2];
				$method_name = $matches[3];
				if (class_exists($class_name) && method_exists($class_name, $initializer_name))
				{
					$obj = $class_name::$initializer_name();
					if (method_exists($obj, $method_name))
					{
						$handler = [$obj, $method_name];
					}
					else
					{
						error_log('RxQueue: task handler not found: ' . $task->handler);
					}
				}
				else
				{
					error_log('RxQueue: task handler not found: ' . $task->handler);
				}
			}
			elseif (preg_match('/^new (?:\\\\)?([\\\\\\w]+)(?:\(\))?->(\\w+)$/', $task->handler, $matches))
			{
				$class_name = '\\' . $matches[1];
				$method_name = $matches[2];
				if (class_exists($class_name) && method_exists($class_name, $method_name))
				{
					$obj = new $class_name();
					$handler = [$obj, $method_name];
				}
				else
				{
					error_log('RxQueue: task handler not found: ' . $task->handler);
				}
			}
			else
			{
				if (function_exists('\\' . $task->handler))
				{
					$handler = '\\' . $task->handler;
				}
				else
				{
					error_log('RxQueue: task handler not found: ' . $task->handler);
				}
			}
		}
		catch (\Throwable $th)
		{
			error_log(vsprintf('RxQueue: task handler %s could not be accessed: %s in %s:%d', [
				$task->handler,
				get_class($th),
				$th->getFile(),
				$th->getLine(),
			]));
		}

		// Call the handler.
		try
		{
			if ($handler)
			{
				call_user_func($handler, $task->args, $task->options);
			}
		}
		catch (\Throwable $th)
		{
			error_log(vsprintf('RxQueue: task handler %s threw %s in %s:%d', [
				$task->handler,
				get_class($th),
				$th->getFile(),
				$th->getLine(),
			]));
		}
	}
}
