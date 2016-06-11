<?php

namespace Rhymix\Framework\Drivers\Cache;

/**
 * The Redis cache driver.
 */
class Redis implements \Rhymix\Framework\Drivers\CacheInterface
{
	/**
	 * Set this flag to false to disable cache prefixes.
	 */
	public $prefix = true;
	
	/**
	 * The singleton instance is stored here.
	 */
	protected static $_instance = null;
	
	/**
	 * The Redis connection is stored here.
	 */
	protected $_conn = null;
	
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct(array $config)
	{
		try
		{
			$this->_conn = null;
			foreach ($config as $url)
			{
				$info = parse_url($url);
				if (isset($info['host']) && isset($info['port']))
				{
					$this->_conn = new \Redis;
					$this->_conn->connect($info['host'], $info['port'], 0.15);
					if(isset($info['user']) || isset($info['pass']))
					{
						$this->_conn->auth(isset($info['user']) ? $info['user'] : $info['pass']);
					}
					if(isset($info['path']) && $dbnum = intval(substr($info['path'], 1)))
					{
						$this->_conn->select($dbnum);
					}
					break;
				}
			}
		}
		catch (\RedisException $e)
		{
			$this->_conn = null;
		}
	}
	
	/**
	 * Create a new instance of the current cache driver, using the given settings.
	 * 
	 * @param array $config
	 * @return void
	 */
	public static function getInstance(array $config)
	{
		if (self::$_instance === null)
		{
			self::$_instance = new self($config);
		}
		return self::$_instance;
	}
	
	/**
	 * Check if the current cache driver is supported on this server.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public static function isSupported()
	{
		return class_exists('\\Redis', false);
	}
	
	/**
	 * Validate cache settings.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param mixed $config
	 * @return bool
	 */
	public static function validateSettings($config)
	{
		try
		{
			$conn = new \Redis;
			foreach ($config as $url)
			{
				$info = parse_url($url);
				if (isset($info['host']) && isset($info['port']))
				{
					$conn->connect($info['host'], $info['port'], 0.15);
					if(isset($info['user']) || isset($info['pass']))
					{
						$conn->auth(isset($info['user']) ? $info['user'] : $info['pass']);
					}
					if(isset($info['path']) && $dbnum = intval(substr($info['path'], 1)))
					{
						$conn->select($dbnum);
					}
					return true;
				}
			}
			return false;
		}
		catch (\RedisException $e)
		{
			return false;
		}
	}
	
	/**
	 * Get the value of a key.
	 * 
	 * This method returns null if the key was not found.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		try
		{
			$value = $this->_conn->get($key);
		}
		catch (\RedisException $e)
		{
			return null;
		}
		
		if ($value === false)
		{
			return null;
		}
		
		$value = unserialize($value);
		if ($value === false)
		{
			return null;
		}
		return $value;
	}
	
	/**
	 * Set the value to a key.
	 * 
	 * This method returns true on success and false on failure.
	 * $ttl is measured in seconds. If it is zero, the key should not expire.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @param bool $force
	 * @return bool
	 */
	public function set($key, $value, $ttl = 0, $force = false)
	{
		try
		{
			return $this->_conn->setex($key, $ttl, serialize($value)) ? true : false;
		}
		catch (\RedisException $e)
		{
			return false;
		}
	}
	
	/**
	 * Delete a key.
	 * 
	 * This method returns true on success and false on failure.
	 * If the key does not exist, it should return false.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function delete($key)
	{
		try
		{
			return $this->_conn->del($key) ? true : false;
		}
		catch (\RedisException $e)
		{
			return false;
		}
	}
	
	/**
	 * Check if a key exists.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		try
		{
			return $this->_conn->exists($key);
		}
		catch (\RedisException $e)
		{
			return false;
		}
	}
	
	/**
	 * Increase the value of a key by $amount.
	 * 
	 * If the key does not exist, this method assumes that the current value is zero.
	 * This method returns the new value.
	 * 
	 * @param string $key
	 * @param int $amount
	 * @return int
	 */
	public function incr($key, $amount)
	{
		try
		{
			return $this->_conn->incrBy($key, $amount);
		}
		catch (\RedisException $e)
		{
			return false;
		}
	}
	
	/**
	 * Decrease the value of a key by $amount.
	 * 
	 * If the key does not exist, this method assumes that the current value is zero.
	 * This method returns the new value.
	 * 
	 * @param string $key
	 * @param int $amount
	 * @return int
	 */
	public function decr($key, $amount)
	{
		try
		{
			return $this->_conn->decrBy($key, $amount);
		}
		catch (\RedisException $e)
		{
			return false;
		}
	}
	
	/**
	 * Clear all keys from the cache.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public function clear()
	{
		try
		{
			return $this->_conn->flushDB() ? true : false;
		}
		catch (\RedisException $e)
		{
			return false;
		}
	}
}
