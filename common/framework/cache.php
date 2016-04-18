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
	
	/**
	 * The default TTL.
	 */
	protected static $_ttl = 86400;
	
	/**
	 * The automatically generated cache prefix.
	 */
	protected static $_prefix = null;
	
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
			$class_name = '\\Rhymix\\Framework\\Drivers\\Cache\\' . $config['type'];
			if (isset($config['ttl']))
			{
				self::$_ttl = intval($config['ttl']);
			}
			$config = isset($config['servers']) ? $config['servers'] : array();
		}
		elseif (preg_match('/^(apc|dummy|file|memcache|redis|sqlite|wincache|xcache)/', strval(array_first($config)), $matches))
		{
			$class_name = '\\Rhymix\\Framework\\Drivers\\Cache\\' . $matches[1] . ($matches[1] === 'memcache' ? 'd' : '');
		}
		else
		{
			$class_name = null;
		}
		
		if (class_exists($class_name) && $class_name::isSupported())
		{
			self::$_driver = new $class_name($config);
		}
		else
		{
			self::$_driver = new Drivers\Cache\File(array());
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
	 * Get the currently enabled cache driver, or a named driver with the given settings.
	 * 
	 * @param string $name (optional)
	 * @param array $config (optional)
	 * @return object|null
	 */
	public static function getCacheDriver($name = null, array $config = [])
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
				return new $class_name($config);
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
	 * return object|null
	 */
	public static function getCachePrefix()
	{
		return self::$_prefix;
	}
	
	/**
	 * Get the value of a key.
	 * 
	 * This method returns null if the key was not found.
	 * 
	 * @param string $key
	 * @param string $group_name (optional)
	 * @return mixed
	 */
	public static function get($key, $group_name = null)
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->get(self::getRealKey($key, $group_name));
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
	 * @param int $ttl (optional)
	 * @param string $group_name (optional)
	 * @return bool
	 */
	public static function set($key, $value, $ttl = null, $group_name = null)
	{
		if (self::$_driver !== null)
		{
			if ($ttl >= (3600 * 24 * 30))
			{
				$ttl = min(3600 * 24 * 30, max(0, $ttl - time()));
			}
			if ($ttl === null)
			{
				$ttl = self::$_ttl;
			}
			return self::$_driver->set(self::getRealKey($key, $group_name), $value, intval($ttl)) ? true : false;
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
	 * @param string $group_name (optional)
	 * @return bool
	 */
	public static function delete($key, $group_name = null)
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->delete(self::getRealKey($key, $group_name)) ? true : false;
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
	 * @param string $group_name (optional)
	 * @return bool
	 */
	public static function exists($key, $group_name = null)
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->exists(self::getRealKey($key, $group_name)) ? true : false;
		}
		else
		{
			return false;
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
	public static function clearGroup($group_name)
	{
		if (self::$_driver !== null)
		{
			return self::$_driver->incr(self::$_prefix . $group_name . '#version', 1) ? true : false;
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
	public static function clearAll()
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
	public static function getGroupVersion($group_name)
	{
		if (self::$_driver !== null)
		{
			return intval(self::$_driver->get(self::$_prefix . $group_name . '#version'));
		}
		else
		{
			return 0;
		}
	}
	
	/**
	 * Get the actual key used by Rhymix.
	 * 
	 * @param string $key
	 * @param string $group_name (optional)
	 * @param bool $add_prefix (optional)
	 * @return string
	 */
	public static function getRealKey($key, $group_name = null, $add_prefix = true)
	{
		if ($group_name)
		{
			$key = $group_name . '#' . self::getGroupVersion($group_name) . ':' . $key;
		}
		
		return ($add_prefix ? self::$_prefix : '') . $key;
	}
}
