<?php

namespace Rhymix\Framework\Drivers\Cache;

/**
 * The Memcached cache driver.
 */
class Memcached implements \Rhymix\Framework\Drivers\CacheInterface
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
	 * The Memcached connection is stored here.
	 */
	protected $_conn = null;
	protected $_ext = null;
	
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct(array $config)
	{
		if (class_exists('\\Memcached', false))
		{
			$this->_conn = new \Memcached;
			$this->_ext = 'Memcached';
		}
		elseif (class_exists('\\Memcache', false))
		{
			$this->_conn = new \Memcache;
			$this->_ext = 'Memcache';
		}
		else
		{
			return;
		}
		
		foreach ($config as $url)
		{
			if (starts_with('/', $url))
			{
				$this->_conn->addServer($url, 0);
			}
			else
			{
				$info = parse_url($url);
				if (isset($info['host']) && isset($info['port']))
				{
					$this->_conn->addServer($info['host'], $info['port']);
				}
			}
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
		return class_exists('\\Memcached', false) || class_exists('\\Memcache', false);
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
		if (class_exists('\\Memcached', false))
		{
			$conn = new \Memcached;
			$ext = 'Memcached';
		}
		elseif (class_exists('\\Memcache', false))
		{
			$conn = new \Memcache;
			$ext = 'Memcache';
		}
		else
		{
			return false;
		}
		
		foreach ($config as $url)
		{
			if (starts_with('/', $url))
			{
				$conn->addServer($url, 0);
			}
			else
			{
				$info = parse_url($url);
				if (isset($info['host']) && isset($info['port']))
				{
					$conn->addServer($info['host'], $info['port']);
				}
			}
		}
		
		for ($i = 0; $i < 5; $i++)
		{
			$key = 'rhymix:test:' . md5($i);
			$status = ($ext === 'Memcached') ? $conn->set($key, $i, 2) : $conn->set($key, $i, 0, 2);
			if (!$status) return false;
		}
		return true;
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
		$value = $this->_conn->get($key);
		if ($value === false)
		{
			return null;
		}
		else
		{
			return $value;
		}
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
		if ($this->_ext === 'Memcached')
		{
			return $this->_conn->set($key, $value, $ttl);
		}
		else
		{
			return $this->_conn->set($key, $value, MEMCACHE_COMPRESSED, $ttl);
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
		return $this->_conn->delete($key);
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
		return $this->_conn->get($key) !== false;
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
		$result = $this->_conn->increment($key, $amount);
		if ($result === false)
		{
			$this->set($key, $amount);
			$result = $amount;
		}
		return $result;
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
		$result = $this->_conn->decrement($key, $amount);
		if ($result === false)
		{
			$this->set($key, 0 - $amount);
			$result = 0 - $amount;
		}
		return $result;
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
		return $this->_conn->flush();
	}
}
