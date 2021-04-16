<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * CacheHandler
 *
 * @author NAVER (developer@xpressengine.com)
 */
class CacheHandler extends Handler
{
	/**
	 * Force file cache.
	 */
	protected $_always_use_file = false;
	
	/**
	 * Get a instance of CacheHandler(for singleton)
	 *
	 * @param string $target type of cache (object|template)
	 * @param object $info info. of DB
	 * @param boolean $always_use_file If set true, use a file cache always
	 * @return CacheHandler
	 */
	public static function getInstance($target = null, $info = null, $always_use_file = false)
	{
		return new self($target, $info, $always_use_file);
	}

	/**
	 * Constructor.
	 *
	 * Do not use this directly. You can use getInstance() instead.
	 *
	 * @see CacheHandler::getInstance
	 * @param string $target type of cache (object)
	 * @param object $info info. of DB
	 * @param boolean $always_use_file If set true, use a file cache always
	 * @return CacheHandler
	 */
	protected function __construct($target = null, $info = null, $always_use_file = false)
	{
		$this->_always_use_file = $always_use_file;
	}

	/**
	 * Return whether support or not support cache
	 *
	 * @return boolean
	 */
	public function isSupport()
	{
		return $this->_always_use_file || (Rhymix\Framework\Cache::getDriverName() !== 'dummy');
	}

	/**
	 * Get cache name by key
	 *
	 * @param string $key The key that will be associated with the item.
	 * @return string Returns cache name
	 */
	public function getCacheKey($key)
	{
		return $key;
	}

	/**
	 * Get cached data
	 *
	 * @param string $key Cache key
	 * @param int $modified_time 	Unix time of data modified.
	 * 								If stored time is older then modified time, return false.
	 * @return false|mixed Return false on failure or older then modified time. Return the string associated with the $key on success.
	 */
	public function get($key, $modified_time = 0)
	{
		$value = Rhymix\Framework\Cache::get($key);
		return $value === null ? false : $value;
	}

	/**
	 * Put data into cache
	 *
	 * @param string $key Cache key
	 * @param mixed $obj	Value of a variable to store. $value supports all data types except resources, such as file handlers.
	 * @param int $valid_time	Time for the variable to live in the cache in seconds.
	 * 							After the value specified in ttl has passed the stored variable will be deleted from the cache.
	 * 							If no ttl is supplied, use the default valid time.
	 * @return bool|void Returns true on success or false on failure. If use CacheFile, returns void.
	 */
	public function put($key, $obj, $valid_time = 0)
	{
		return Rhymix\Framework\Cache::set($key, $obj, $valid_time, $this->_always_use_file);
	}

	/**
	 * Delete Cache
	 *
	 * @param string $key Cache key
	 * @return void
	 */
	public function delete($key)
	{
		return Rhymix\Framework\Cache::delete($key);
	}

	/**
	 * Return whether cache is valid or invalid
	 *
	 * @param string $key Cache key
	 * @param int $modified_time 	Unix time of data modified.
	 * 								If stored time is older then modified time, the data is invalid.
	 * @return bool Return true on valid or false on invalid.
	 */
	public function isValid($key, $modified_time = 0)
	{
		return Rhymix\Framework\Cache::exists($key);
	}

	/**
	 * Truncate all cache
	 *
	 * @return bool|void Returns true on success or false on failure. If use CacheFile, returns void.
	 */
	public function truncate()
	{
		return Rhymix\Framework\Cache::clearAll();
	}

	/**
	 * Function used for generating keys for similar objects.
	 *
	 * Ex: 1:document:123
	 *     1:document:777
	 *
	 * This allows easily removing all object of type "document"
	 * from cache by simply invalidating the group key.
	 *
	 * The new key will be 2:document:123, thus forcing the document
	 * to be reloaded from the database.
	 *
	 * @param string $keyGroupName Group name
	 * @param string $key Cache key
	 * @return string
	 */
	public function getGroupKey($keyGroupName, $key)
	{
		return $keyGroupName . ':' . $key;
	}

	/**
	 * Make invalid group key (like delete group key)
	 *
	 * @param string $keyGroupName Group name
	 * @return bool
	 */
	public function invalidateGroupKey($keyGroupName)
	{
		return Rhymix\Framework\Cache::clearGroup($keyGroupName);
	}
}

/* End of file CacheHandler.class.php */
/* Location: ./classes/cache/CacheHandler.class.php */
