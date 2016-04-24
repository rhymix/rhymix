<?php

namespace Rhymix\Framework\Drivers\Cache;

/**
 * The dummy cache driver.
 */
class Dummy extends File implements \Rhymix\Framework\Drivers\CacheInterface
{
	/**
	 * Set this flag to false to disable cache prefixes.
	 */
	public $prefix = false;
	
	/**
	 * The singleton instance is stored here.
	 */
	protected static $_instance = null;
	
	/**
	 * Dummy data is stored here.
	 */
	public $data = array();
	
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
		$value = parent::get($key);
		if ($value !== null)
		{
			return $value;
		}
		elseif (isset($this->data[$key]))
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
	 * @param bool $force
	 * @return bool
	 */
	public function set($key, $value, $ttl = 0, $force = false)
	{
		if ($force)
		{
			return parent::set($key, $value, $ttl, $force);
		}
		else
		{
			$this->data[$key] = array($ttl ? (time() + $ttl) : 0, $value);
			return true;
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
		if (parent::delete($key))
		{
			return true;
		}
		elseif (isset($this->data[$key]))
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
		return parent::exists($key) || isset($this->data[$key]);
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
		parent::clear();
		$this->data = array();
		return true;
	}
}
