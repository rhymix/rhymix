<?php

namespace Rhymix\Framework;

/**
 * The cache class.
 */
class Cache
{
	/**
	 * The currently enabled cache driver.
	 */
	protected static $_driver = null;
	protected static $_driver_name = null;
	
	/**
	 * The cache prefix.
	 */
	protected static $_prefix = null;
	
	/**
	 * The default TTL.
	 */
	protected static $_ttl = 86400;
	
	/**
	 * Cache group versions.
	 */
	protected static $_group_versions = array();
	
	/**
	 * Initialize the cache system.
	 * 
	 * @param array $config
	 * @return void
	 */
	public static function init($config)
	{
		if (!is_array($config))
		{
			$config = array($config);
		}
		
		if (isset($config['type']))
		{
			$driver_name = $config['type'];
			$class_name = '\\Rhymix\\Framework\\Drivers\\Cache\\' . $config['type'];
			if (isset($config['ttl']))
			{
				self::$_ttl = intval($config['ttl']);
			}
			$config = isset($config['servers']) ? $config['servers'] : array();
		}
		elseif (preg_match('/^(apc|dummy|file|memcache|redis|sqlite|wincache|xcache)/', strval(array_first($config)), $matches))
		{
			$driver_name = $matches[1] . ($matches[1] === 'memcache' ? 'd' : '');
			$class_name = '\\Rhymix\\Framework\\Drivers\\Cache\\' . $driver_name;
		}
		else
		{
			$driver_name = null;
			$class_name = null;
		}
		
		if ($class_name && class_exists($class_name) && $class_name::isSupported())
		{
			self::$_driver = $class_name::getInstance($config);
			self::$_driver_name = strtolower($driver_name);
		}
		else
		{
			self::$_driver = Drivers\Cache\Dummy::getInstance(array());
			self::$_driver_name = 'dummy';
		}
		
		if (self::$_driver->prefix)
		{
			self::$_prefix = substr(sha1(\RX_BASEDIR), 0, 10) . ':' . \RX_VERSION . ':';
		}
		else
		{
			self::$_prefix = \RX_VERSION . ':';
		}
		
		return self::$_driver;
	}
	
	/**
	 * Get the list of supported cache drivers.
	 * 
	 * @return array
	 */
	public static function getSupportedDrivers()
	{
		$result = array();
		foreach (Storage::readDirectory(__DIR__ . '/drivers/cache', false) as $filename)
		{
			$driver_name = substr($filename, 0, -4);
			$class_name = '\Rhymix\Framework\Drivers\Cache\\' . $driver_name;
			if ($class_name::isSupported())
			{
				$result[] = $driver_name;
			}
		}
		return $result;
	}
	
	/**
	 * Get the name of the currently enabled cache driver.
	 * 
	 * @return string|null
	 */
	public static function getDriverName()
	{
		return self::$_driver_name;
	}
	
	/**
	 * Get the currently enabled cache driver, or a named driver with the given settings.
	 * 
	 * @param string $name (optional)
	 * @param array $config (optional)
	 * @return object|null
	 */
	public static function getDriverInstance($name = null, array $config = [])
	{
		if ($name === null)
		{
			return self::$_driver;
		}
		else
		{
			$class_name = '\\Rhymix\\Framework\\Drivers\\Cache\\' . $name;
			if (class_exists($class_name) && $class_name::isSupported() && $class_name::validateSettings($config))
			{
				return $class_name::getInstance($config);
			}
			else
			{
				return null;
			}
		}
	}
	
	/**
	 * Get the automatically generated cache prefix for this installation of Rhymix.
	 * 
	 * @return object|null
	 */
	public static function getPrefix()
	{
		return self::$_prefix;
	}
	
	/**
	 * Get the default TTL.
	 * 
	 * @return int
	 */
	public static function getDefaultTTL()
	{
		return self::$_ttl;
	}
	
	/**
	 * Set the default TTL.
	 * 
	 * @param int $ttl
	 * @return void
	 */
	public static function setDefaultTTL($ttl)
	{
		self::$_ttl = $ttl;
	}
	
	/**
	 * Get the value of a key.
	 * 
	 * This method returns null if the key was not found.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key)
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->get(self::getRealKey($key));
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
	 * $ttl is measured in seconds. If it is not given, the default TTL is used.
	 * $force is used to cache essential data when using the default driver.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl (optional)
	 * @param bool $force (optional)
	 * @return bool
	 */
	public static function set($key, $value, $ttl = 0, $force = false)
	{
		if (self::$_driver !== null)
		{
			$ttl = intval($ttl);
			if ($ttl >= (3600 * 24 * 30))
			{
				$ttl = min(3600 * 24 * 30, max(0, $ttl - time()));
			}
			if ($ttl === 0)
			{
				$ttl = self::$_ttl;
			}
			return self::$_driver->set(self::getRealKey($key), $value, $ttl, $force) ? true : false;
		}
		else
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
	public static function delete(string $key): bool
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->delete(self::getRealKey($key)) ? true : false;
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
	public static function exists(string $key): bool
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->exists(self::getRealKey($key)) ? true : false;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Increase the value of a key by $amount.
	 * 
	 * If the key does not exist, this method assumes that the current value is zero.
	 * This method returns the new value, or -1 on failure.
	 * 
	 * @param string $key
	 * @param int $amount (optional)
	 * @return int
	 */
	public static function incr(string $key, int $amount = 1): int
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->incr(self::getRealKey($key), $amount);
		}
		else
		{
			return -1;
		}
	}
	
	/**
	 * Decrease the value of a key by $amount.
	 * 
	 * If the key does not exist, this method assumes that the current value is zero.
	 * This method returns the new value, or -1 on failure.
	 * 
	 * @param string $key
	 * @param int $amount (optional)
	 * @return int
	 */
	public static function decr(string $key, int $amount = 1): int
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->decr(self::getRealKey($key), $amount);
		}
		else
		{
			return -1;
		}
	}
	
	/**
	 * Clear a group of keys from the cache.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param string $group_name
	 * @return bool
	 */
	public static function clearGroup(string $group_name): bool
	{
		if (self::$_driver !== null)
		{
			$success = self::$_driver->incr(self::$_prefix . $group_name . '#version', 1) ? true : false;
			unset(self::$_group_versions[$group_name]);
			return $success;
		}
		else
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
	public static function clearAll(): bool
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->clear() ? true : false;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Get the group version.
	 * 
	 * @param string $group_name
	 * @return int
	 */
	public static function getGroupVersion(string $group_name): int
	{
		if (isset(self::$_group_versions[$group_name]))
		{
			return self::$_group_versions[$group_name];
		}
		else
		{
			if (self::$_driver !== null)
			{
				return self::$_group_versions[$group_name] = intval(self::$_driver->get(self::$_prefix . $group_name . '#version'));
			}
			else
			{
				return self::$_group_versions[$group_name] = 0;
			}
		}
	}
	
	/**
	 * Get the actual key used by Rhymix.
	 * 
	 * @param string $key
	 * @return string
	 */
	public static function getRealKey(string $key): string
	{
		if (preg_match('/^([^:]+):(.+)$/i', $key, $matches))
		{
			$key = $matches[1] . '#' . self::getGroupVersion($matches[1]) . ':' . $matches[2];
		}
		
		return self::$_prefix . $key;
	}
}
