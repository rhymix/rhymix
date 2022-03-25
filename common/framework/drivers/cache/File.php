<?php

namespace Rhymix\Framework\Drivers\Cache;

use Rhymix\Framework\Storage;

/**
 * The file cache driver.
 */
class File implements \Rhymix\Framework\Drivers\CacheInterface
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
	 * The cache directory.
	 */
	protected $_dir;
	
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct(array $config)
	{
		$this->_dir = \RX_BASEDIR . 'files/cache/store';
		if (!Storage::isDirectory($this->_dir))
		{
			Storage::createDirectory($this->_dir);
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
		if (static::$_instance === null)
		{
			static::$_instance = new static($config);
		}
		return static::$_instance;
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
		$filename = $this->_getFilename($key);
		$data = Storage::readPHPData($filename);
		
		if ($data === false)
		{
			return null;
		}
		elseif (!is_array($data) || count($data) < 2 || ($data[0] > 0 && $data[0] < time()))
		{
			Storage::delete($filename);
			return null;
		}
		else
		{
			return $data[1];
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
		return Storage::writePHPData($this->_getFilename($key), array($ttl ? (time() + $ttl) : 0, $value), $key);
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
		return Storage::delete($this->_getFilename($key));
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
		return $this->get($key) !== null;
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
		$value = intval($this->get($key));
		$success = $this->set($key, $value + $amount, 0, true);
		return $success ? ($value + $amount) : false;
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
		return Storage::deleteDirectory($this->_dir) ? true : false;
	}
	
	/**
	 * Get the filename to store a key.
	 * 
	 * @param string $key
	 * @return string
	 */
	protected function _getFilename($key)
	{
		$hash = sha1($key);
		return $this->_dir . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash . '.php';
	}
}
