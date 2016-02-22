<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Cache class for Redis
 *
 * @author NAVER (developer@xpressengine.com)
 */
class CacheRedis extends CacheBase
{
	/**
	 * Instance of Memcache
	 */
	protected static $_instance;
	protected $_conn;
	protected $_status;

	/**
	 * Get instance of CacheRedis
	 *
	 * @param string $url url of Redis
	 * @return CacheRedis instance of CacheRedis
	 */
	public static function getInstance($url, $force_new_instance = false)
	{
		if(!self::$_instance || $force_new_instance)
		{
			self::$_instance = new self($url);
		}
		return self::$_instance;
	}

	/**
	 * Construct
	 *
	 * Do not use this directly. You can use getInstance() instead.
	 * @param string $url url of Redis
	 * @return void
	 */
	protected function __construct($url)
	{
		//$url = 'redis://localhost:6379/1';
		$url = is_array($url) ? reset($url) : $url;

		if(!class_exists('Redis'))
		{
			$this->_status = false;
		}

		try
		{
			$this->_conn = new Redis;
			$info = parse_url($url);
			$this->_conn->connect($info['host'], $info['port'], 0.15);
			if(isset($info['user']) || isset($info['pass']))
			{
				$this->_conn->auth(isset($info['user']) ? $info['user'] : $info['pass']);
			}
			if(isset($info['path']) && $dbnum = intval(substr($info['path'], 1)))
			{
				$this->_conn->select($dbnum);
			}
		}
		catch(RedisException $e)
		{
			$this->_status = false;
		}
	}

	/**
	 * Return whether support or not support cache
	 *
	 * @return bool Return true on support or false on not support
	 */
	public function isSupport()
	{
		if($this->_status !== null)
		{
			return $this->_status;
		}

		try
		{
			return $this->_conn->setex('xe', 1, 'xe');
		}
		catch(RedisException $e)
		{
			return $this->_status = false;
		}
	}

	/**
	 * Get unique key of given key by path of XE
	 *
	 * @param string $key Cache key
	 * @return string Return unique key
	 */
	protected function getKey($key)
	{
		static $prefix = null;
		if($prefix === null)
		{
			$prefix = substr(sha1(_XE_PATH_), 0, 12) . ':';
		}
		return $prefix . $key;
	}

	/**
	 * Store data at the server
	 *
	 * CacheRedis::put() stores an item $buff with $key on the Redis server.
	 * Parameter $valid_time is expiration time in seconds. If it's 0, the item never expires
	 * (but Redis server doesn't guarantee this item to be stored all the time, it could be delete from the cache to make place for other items).
	 *
	 * Remember that resource variables (i.e. file and connection descriptors) cannot be stored in the cache,
	 * because they can not be adequately represented in serialized state.
	 *
	 * @param string $key The key that will be associated with the item.
	 * @param mixed $buff The variable to store. Strings and integers are stored as is, other types are stored serialized.
	 * @param int $valid_time 	Expiration time of the item.
	 * 							You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
	 * 							If it's equal to zero, use the default valid time CacheRedis::valid_time.
	 * @return bool Returns true on success or false on failure.
	 */
	public function put($key, $buff, $valid_time = 0)
	{
		if($valid_time > 60 * 60 * 24 * 30)
		{
			$valid_time = $valid_time - time();
		}
		if($valid_time <= 0)
		{
			$valid_time = $this->valid_time;
		}

		try
		{
			return $this->_conn->setex($this->getKey($key), $valid_time, serialize(array($_SERVER['REQUEST_TIME'], $buff)));
		}
		catch(RedisException $e)
		{
			return $this->_status = false;
		}
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
		$obj = $this->_conn->get($this->getKey($key));
		$obj = $obj ? unserialize($obj) : false;
		if(!$obj || !is_array($obj))
		{
			return false;
		}

		if($modified_time > 0 && $modified_time > $obj[0])
		{
			$this->_conn->del($this->getKey($key));
			return false;
		}

		return true;
	}

	/**
	 * Retrieve item from the server
	 *
	 * CacheRedis::get() returns previously stored data if an item with such $key exists on the server at this moment.
	 *
	 * @param string $key The key to fetch
	 * @param int $modified_time 	Unix time of data modified.
	 * 								If stored time is older then modified time, return false.
	 * @return false|mixed Return false on failure or older then modified time. Return the string associated with the $key on success.
	 */
	public function get($key, $modified_time = 0)
	{
		$obj = $this->_conn->get($this->getKey($key));
		$obj = $obj ? unserialize($obj) : false;
		if(!$obj || !is_array($obj))
		{
			return false;
		}

		if($modified_time > 0 && $modified_time > $obj[0])
		{
			$this->_conn->del($this->getKey($key));
			return false;
		}

		return $obj[1];
	}

	/**
	 * Delete item from the server
	 *
	 * CacheRedis::delete() deletes an item with tey $key.
	 *
	 * @param string $key The key associated with the item to delete.
	 * @return void
	 */
	public function delete($key)
	{
		try
		{
			return $this->_conn->del($this->getKey($key));
		}
		catch(RedisException $e)
		{
			return $this->_status = false;
		}
	}

	/**
	 * Flush all existing items at the server
	 *
	 * CacheRedis::truncate() immediately invalidates all existing items.
	 * If using multiple databases, items in other databases are not affected.
	 *
	 * @return bool Returns true on success or false on failure.
	 */
	public function truncate()
	{
		try
		{
			return $this->_conn->flushDB();
		}
		catch(RedisException $e)
		{
			return $this->_status = false;
		}
	}

}
/* End of file CacheRedis.class.php */
/* Location: ./classes/cache/CacheRedis.class.php */
