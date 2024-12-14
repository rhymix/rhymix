<?php

namespace Rhymix\Framework\Drivers\Queue;

use Rhymix\Framework\Drivers\QueueInterface;

/**
 * The Redis queue driver.
 */
class Redis implements QueueInterface
{
	/**
	 * The Redis connection is stored here.
	 */
	protected $_conn;
	protected $_key;

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
		return 'Redis';
	}

	/**
	 * Get the list of configuration fields required by this Queue driver.
	 *
	 * @return array
	 */
	public static function getRequiredConfig(): array
	{
		return ['host', 'port'];
	}

	/**
	 * Get the list of configuration fields optionally used by this Queue driver.
	 *
	 * @return array
	 */
	public static function getOptionalConfig(): array
	{
		return ['dbnum', 'user', 'pass'];
	}

	/**
	 * Check if this driver is supported on this server.
	 *
	 * @return bool
	 */
	public static function isSupported(): bool
	{
		return class_exists('\\Redis');
	}

	/**
	 * Validate driver configuration.
	 *
	 * @param mixed $config
	 * @return bool
	 */
	public static function validateConfig($config): bool
	{
		try
		{
			$test = new \Redis;
			$test->connect($config['host'], $config['port'] ?? 6379);
			if (isset($config['user']) || isset($config['pass']))
			{
				$auth = [];
				if (isset($config['user']) && $config['user']) $auth[] = $config['user'];
				if (isset($config['pass']) && $config['pass']) $auth[] = $config['pass'];
				$test->auth(count($auth) > 1 ? $auth : $auth[0]);
			}
			if (isset($config['dbnum']))
			{
				$test->select(intval($config['dbnum']));
			}
			return true;
		}
		catch (\Throwable $th)
		{
			return false;
		}
	}

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		try
		{
			$this->_conn = new \Redis;
			$this->_conn->connect($config['host'], $config['port'] ?? 6379);
			if (isset($config['user']) || isset($config['pass']))
			{
				$auth = [];
				if (isset($config['user']) && $config['user']) $auth[] = $config['user'];
				if (isset($config['pass']) && $config['pass']) $auth[] = $config['pass'];
				$this->_conn->auth(count($auth) > 1 ? $auth : $auth[0]);
			}
			if (isset($config['dbnum']))
			{
				$this->_conn->select(intval($config['dbnum']));
			}
			$this->_key = 'rxQueue_' . substr(hash_hmac('sha1', 'rxQueue_', config('crypto.authentication_key')), 0, 24);
		}
		catch (\RedisException $e)
		{
			$this->_conn = null;
		}
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
		$value = serialize((object)[
			'handler' => $handler,
			'args' => $args,
			'options' => $options,
		]);

		if ($this->_conn)
		{
			$result = $this->_conn->rPush($this->_key, $value);
			return intval($result);
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Get the next task from the queue.
	 *
	 * @param int $blocking
	 * @return ?object
	 */
	public function getNextTask(int $blocking = 0): ?object
	{
		if ($this->_conn)
		{
			if ($blocking > 0)
			{
				$result = $this->_conn->blpop($this->_key, $blocking);
				if (is_array($result) && isset($result[1]))
				{
					return unserialize($result[1]);
				}
				else
				{
					return null;
				}
			}
			else
			{
				$result = $this->_conn->lpop($this->_key);
				if ($result)
				{
					return unserialize($result);
				}
				else
				{
					return null;
				}
			}
		}
		else
		{
			return null;
		}
	}
}
