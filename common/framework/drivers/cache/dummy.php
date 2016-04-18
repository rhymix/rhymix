<?php

namespace Rhymix\Framework\Drivers\Cache;

/**
 * The dummy cache driver.
 */
class Dummy implements \Rhymix\Framework\Drivers\CacheInterface
{
	/**
	 * Set this flag to false to disable cache prefixes.
	 */
	public $prefix = true;
	
	/**
	 * Dummy data is stored here.
	 */
	public $data = array();
	
	/**
	 * Create a new instance of the current cache driver, using the given settings.
	 * 
	 * @param array $config
	 * @return void
	 */
	public function __construct(array $config)
	{
		
	}
	
	/**
	 * Check if the current cache driver is supported on this server.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public function isSupported()
	{
		return true;
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
		if (isset($this->data[$key]))
		{
			if ($this->data[$key][0] > 0 && $this->data[$key][0] < time())
			{
				unset($this->data[$key]);
				return null;
			}
			return $this->data[$key][1];
		}
		else
		{
			return null;
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
	 * @return bool
	 */
	public function set($key, $value, $ttl)
	{
		$this->data[$key] = array($ttl ? (time() + $ttl) : 0, $value);
		return true;
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
		if (isset($this->data[$key]))
		{
			unset($this->data[$key]);
			return true;
		}
		else
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
		return isset($this->data[$key]);
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
		if (isset($this->data[$key]))
		{
			$this->data[$key][1] += $amount;
			return $this->data[$key][1];
		}
		else
		{
			$this->set($key, $amount, 0);
			return $amount;
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
		return $this->incr($key, 0 - $amount);
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
		$this->data = array();
	}
}
