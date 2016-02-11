<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Cache class for memcache
 *
 * @author NAVER (developer@xpressengine.com)
 */
class CacheMemcache extends CacheBase
{
	/**
	 * Instance of Memcache
	 */
	protected static $_instance;
	protected $_conn;
	protected $_status;
	protected $_useExtension;

	/**
	 * Get instance of CacheMemcache
	 *
	 * @param string $url url of memcache
	 * @return CacheMemcache instance of CacheMemcache
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
	 * @param string $url url of memcache
	 * @return void
	 */
	protected function __construct($url)
	{
		//$config['url'] = array('memcache://localhost:11211');
		$config['url'] = is_array($url) ? $url : array($url);
		if(class_exists('Memcached'))
		{
			$this->_conn = new Memcached;
			$this->_useExtension = 'Memcached';
		}
		elseif(class_exists('Memcache'))
		{
			$this->_conn = new Memcache;
			$this->_useExtension = 'Memcache';
		}
		else
		{
			return false;
		}

		foreach($config['url'] as $url)
		{
			$info = parse_url($url);
			$this->_conn->addServer($info['host'], $info['port']);
		}
	}

	/**
	 * Return whether support or not support cache
	 *
	 * @return bool Return true on support or false on not support
	 */
	public function isSupport()
	{
		if(isset($this->_status))
		{
			return $this->_status;
		}

		if($this->_useExtension === 'Memcached')
		{
			return $this->_status = $this->_conn->set('xe', 'xe', 1); 
		}
		elseif($this->_useExtension === 'Memcache')
		{
			return $this->_status = $this->_conn->set('xe', 'xe', MEMCACHE_COMPRESSED, 1); 
		}
		else
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
		return md5(_XE_PATH_ . $key);
	}

	/**
	 * Store data at the server
	 *
	 * CacheMemcache::put() stores an item $buff with $key on the memcached server.
	 * Parameter $valid_time is expiration time in seconds. If it's 0, the item never expires
	 * (but memcached server doesn't guarantee this item to be stored all the time, it could be delete from the cache to make place for other items).
	 *
	 * Remember that resource variables (i.e. file and connection descriptors) cannot be stored in the cache,
	 * because they can not be adequately represented in serialized state.
	 *
	 * @param string $key The key that will be associated with the item.
	 * @param mixed $buff The variable to store. Strings and integers are stored as is, other types are stored serialized.
	 * @param int $valid_time 	Expiration time of the item.
	 * 							You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
	 * 							If it's equal to zero, use the default valid time CacheMemcache::valid_time.
	 * @return bool Returns true on success or false on failure.
	 */
	public function put($key, $buff, $valid_time = 0)
	{
		if($valid_time == 0)
		{
			$valid_time = $this->valid_time;
		}

		if($this->_useExtension === 'Memcached')
		{
			return $this->_conn->set($this->getKey($key), array(time(), $buff), $valid_time);
		}
		else
		{
			return $this->_conn->set($this->getKey($key), array(time(), $buff), MEMCACHE_COMPRESSED, $valid_time);
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
		if(!$obj || !is_array($obj))
		{
			return false;
		}

		if($modified_time > 0 && $modified_time > $obj[0])
		{
			$this->delete($key);
			return false;
		}

		return true;
	}

	/**
	 * Retrieve item from the server
	 *
	 * CacheMemcache::get() returns previously stored data if an item with such $key exists on the server at this moment.
	 *
	 * @param string $key The key to fetch
	 * @param int $modified_time 	Unix time of data modified.
	 * 								If stored time is older then modified time, return false.
	 * @return false|mixed Return false on failure or older then modified time. Return the string associated with the $key on success.
	 */
	public function get($key, $modified_time = 0)
	{
		$obj = $this->_conn->get($this->getKey($key));
		if(!$obj || !is_array($obj))
		{
			return false;
		}

		if($modified_time > 0 && $modified_time > $obj[0])
		{
			$this->delete($key);
			return false;
		}

		return $obj[1];
	}

	/**
	 * Delete item from the server
	 *
	 * CacheMemcache::delete() deletes an item with tey $key.
	 *
	 * @param string $key The key associated with the item to delete.
	 * @return void
	 */
	public function delete($key)
	{
		return $this->_conn->delete($this->getKey($key));
	}

	/**
	 * Flush all existing items at the server
	 *
	 * CacheMemcache::truncate() immediately invalidates all existing items.
	 * CacheMemcache::truncate() doesn't actually free any resources, it only marks all the items as expired,
	 * so occupied memory will be overwitten by new items.
	 *
	 * @return bool Returns true on success or false on failure.
	 */
	public function truncate()
	{
		return $this->_conn->flush();
	}

}
/* End of file CacheMemcache.class.php */
/* Location: ./classes/cache/CacheMemcache.class.php */
