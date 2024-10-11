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
	 * Get the default driver.
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
	 * Add a task.
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
	 * Get the first task to execute immediately.
	 *
	 * If no tasks are pending, this method will return null.
	 * Detailed scheduling of tasks will be handled by each driver.
	 *
	 * @param int $blocking
	 * @return ?object
	 */
	public static function getTask(int $blocking = 0): ?object
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

		return $driver->getTask($blocking);
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
	 * Process the queue.
	 *
	 * This will usually be called by a separate script, run every minute
	 * through an external scheduler such as crontab or systemd.
	 *
	 * If you are on a shared hosting service, you may also call a URL
	 * using a "web cron" service provider.
	 *
	 * @param int $timeout
	 * @return void
	 */
	public static function process(int $timeout): void
	{
		// This part will run in a loop until timeout.
		$process_start_time = microtime(true);
		while (true)
		{
			// Get a task from the driver.
			$loop_start_time = microtime(true);
			$task = self::getTask(1);

			// Wait 1 second and loop back.
			if ($task)
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
}
