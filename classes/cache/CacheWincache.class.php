<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Cache class for Wincache
 *
 * Wincache Handler
 *
 * @author Arnia (support@xpressengine.org)
 */
class CacheWincache extends CacheBase
{
	public static $isSupport = false;

	/**
	 * Get instance of CacheWincache
	 *
	 * @param void $opt Not used
	 * @return CacheWincache instance of CacheWincache
	 */
	function getInstance($opt = null)
	{
		if(!$GLOBALS['__CacheWincache__'])
		{
			$GLOBALS['__CacheWincache__'] = new CacheWincache();
		}
		return $GLOBALS['__CacheWincache__'];
	}

	/**
	 * Constructor
	 *
	 * @return void
	 */
	function __construct()
	{
	}

	/**
	 * Return whether support or not support cache
	 *
	 * @return bool Return true on support or false on not support
	 */
	function isSupport()
	{
		return self::$isSupport;
	}

	/**
	 * Adds a variable in user cache and overwrites a variable if it already exists in the cache
	 *
	 * @param string $key 	Store the variable using this $key value.
	 * 						If a variable with same $key is already present the function will overwrite the previous value with the new one.
	 * @param mixed $buff	Value of a variable to store. $value supports all data types except resources, such as file handlers.
	 * @param int $valid_time	Time for the variable to live in the cache in seconds.
	 * 							After the value specified in ttl has passed the stored variable will be deleted from the cache.
	 * 							If no ttl is supplied, use the default valid time CacheWincache::valid_time.
	 * @return bool Returns true on success or false on failure.
	 */
	function put($key, $buff, $valid_time = 0)
	{
		if($valid_time == 0)
		{
			$valid_time = $this->valid_time;
		}
		return wincache_ucache_set(md5(_XE_PATH_ . $key), array($_SERVER['REQUEST_TIME'], $buff), $valid_time);
	}

	/**
	 * Return whether cache is valid or invalid
	 *
	 * @param string $key Cache key
	 * @param int $modified_time 	Unix time of data modified.
	 * 								If stored time is older then modified time, the data is invalid.
	 * @return bool Return true on valid or false on invalid.
	 */
	function isValid($key, $modified_time = 0)
	{
		$_key = md5(_XE_PATH_ . $key);
		$obj = wincache_ucache_get($_key, $success);
		if(!$success || !is_array($obj))
		{
			return false;
		}
		unset($obj[1]);

		if($modified_time > 0 && $modified_time > $obj[0])
		{
			$this->_delete($_key);
			return false;
		}

		return true;
	}

	/**
	 * Gets a variable stored in the user cache
	 *
	 * @param string $key The $key that was used to store the variable in the cache.
	 * @param int $modified_time 	Unix time of data modified.
	 * 								If stored time is older then modified time, return false.
	 * @return false|mixed Return false on failure or older then modified time. Return the string associated with the $key on success.
	 */
	function get($key, $modified_time = 0)
	{
		$_key = md5(_XE_PATH_ . $key);
		$obj = wincache_ucache_get($_key, $success);
		if(!$success || !is_array($obj))
		{
			return false;
		}

		if($modified_time > 0 && $modified_time > $obj[0])
		{
			$this->_delete($_key);
			return false;
		}

		return $obj[1];
	}

	/**
	 * Delete variable from the cache(private)
	 *
	 * @param string $_key Used to store the value.
	 * @return void
	 */
	function _delete($_key)
	{
		wincache_ucache_delete($_key);
	}

	/**
	 * Delete variable from the cache
	 *
	 * @param string $key Used to store the value.
	 * @return void
	 */
	function delete($key)
	{
		$_key = md5(_XE_PATH_ . $key);
		$this->_delete($_key);
	}

	/**
	 * Truncate all existing variables at the cache
	 *
	 * @return bool Returns true on success or false on failure.
	 */
	function truncate()
	{
		return wincache_ucache_clear();
	}
}

CacheWincache::$isSupport = function_exists('wincache_ucache_set');
/* End of file CacheWincache.class.php */
/* Location: ./classes/cache/CacheWincache.class.php */
