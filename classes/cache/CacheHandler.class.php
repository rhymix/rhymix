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
	 * instance of cache handler
	 * @var CacheBase
	 */
	var $handler = null;

	/**
	 * Version of key group
	 * @var int
	 */
	var $keyGroupVersions = null;

	/**
	 * Get a instance of CacheHandler(for singleton)
	 *
	 * @param string $target type of cache (object|template)
	 * @param object $info info. of DB
	 * @param boolean $always_use_file If set true, use a file cache always
	 * @return CacheHandler
	 */
	function &getInstance($target = 'object', $info = null, $always_use_file = false)
	{
		$cache_handler_key = $target . ($always_use_file ? '_file' : '');
		if(!$GLOBALS['__XE_CACHE_HANDLER__'][$cache_handler_key])
		{
			$GLOBALS['__XE_CACHE_HANDLER__'][$cache_handler_key] = new CacheHandler($target, $info, $always_use_file);
		}
		return $GLOBALS['__XE_CACHE_HANDLER__'][$cache_handler_key];
	}

	/**
	 * Constructor.
	 *
	 * Do not use this directly. You can use getInstance() instead.
	 *
	 * @see CacheHandler::getInstance
	 * @param string $target type of cache (object|template)
	 * @param object $info info. of DB
	 * @param boolean $always_use_file If set true, use a file cache always
	 * @return CacheHandler
	 */
	function CacheHandler($target, $info = null, $always_use_file = false)
	{
		if(!$info)
		{
			$info = Context::getDBInfo();
		}

		if($info)
		{
			if($target == 'object')
			{
				if($info->use_object_cache == 'apc')
				{
					$type = 'apc';
				}
				else if(substr($info->use_object_cache, 0, 8) == 'memcache')
				{
					$type = 'memcache';
					$url = $info->use_object_cache;
				}
				else if($info->use_object_cache == 'wincache')
				{
					$type = 'wincache';
				}
				else if($info->use_object_cache == 'file')
				{
					$type = 'file';
				}
				else if($always_use_file)
				{
					$type = 'file';
				}
			}
			else if($target == 'template')
			{
				if($info->use_template_cache == 'apc')
				{
					$type = 'apc';
				}
				else if(substr($info->use_template_cache, 0, 8) == 'memcache')
				{
					$type = 'memcache';
					$url = $info->use_template_cache;
				}
				else if($info->use_template_cache == 'wincache')
				{
					$type = 'wincache';
				}
			}

			if($type)
			{
				$class = 'Cache' . ucfirst($type);
				include_once sprintf('%sclasses/cache/%s.class.php', _XE_PATH_, $class);
				$this->handler = call_user_func(array($class, 'getInstance'), $url);
				$this->keyGroupVersions = $this->handler->get('key_group_versions', 0);
				if(!$this->keyGroupVersions)
				{
					$this->keyGroupVersions = array();
					$this->handler->put('key_group_versions', $this->keyGroupVersions, 0);
				}
			}
		}
	}

	/**
	 * Return whether support or not support cache
	 *
	 * @return boolean
	 */
	function isSupport()
	{
		if($this->handler && $this->handler->isSupport())
		{
			return true;
		}

		return false;
	}

	/**
	 * Get cache name by key
	 *
	 * @param string $key The key that will be associated with the item.
	 * @return string Returns cache name
	 */
	function getCacheKey($key)
	{
		$key = str_replace('/', ':', $key);

		return __XE_VERSION__ . ':' . $key;
	}

	/**
	 * Get cached data
	 *
	 * @param string $key Cache key
	 * @param int $modified_time 	Unix time of data modified.
	 * 								If stored time is older then modified time, return false.
	 * @return false|mixed Return false on failure or older then modified time. Return the string associated with the $key on success.
	 */
	function get($key, $modified_time = 0)
	{
		if(!$this->handler)
		{
			return false;
		}

		$key = $this->getCacheKey($key);

		return $this->handler->get($key, $modified_time);
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
	function put($key, $obj, $valid_time = 0)
	{
		if(!$this->handler && !$key)
		{
			return false;
		}

		$key = $this->getCacheKey($key);

		return $this->handler->put($key, $obj, $valid_time);
	}

	/**
	 * Delete Cache
	 *
	 * @param string $key Cache key
	 * @return void
	 */
	function delete($key)
	{
		if(!$this->handler)
		{
			return false;
		}

		$key = $this->getCacheKey($key);

		return $this->handler->delete($key);
	}

	/**
	 * Return whether cache is valid or invalid
	 *
	 * @param string $key Cache key
	 * @param int $modified_time 	Unix time of data modified.
	 * 								If stored time is older then modified time, the data is invalid.
	 * @return bool Return true on valid or false on invalid.
	 */
	function isValid($key, $modified_time)
	{
		if(!$this->handler)
		{
			return false;
		}

		$key = $this->getCacheKey($key);

		return $this->handler->isValid($key, $modified_time);
	}

	/**
	 * Truncate all cache
	 *
	 * @return bool|void Returns true on success or false on failure. If use CacheFile, returns void.
	 */
	function truncate()
	{
		if(!$this->handler)
		{
			return false;
		}

		return $this->handler->truncate();
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
	function getGroupKey($keyGroupName, $key)
	{
		if(!$this->keyGroupVersions[$keyGroupName])
		{
			$this->keyGroupVersions[$keyGroupName] = 1;
			$this->handler->put('key_group_versions', $this->keyGroupVersions, 0);
		}

		return 'cache_group_' . $this->keyGroupVersions[$keyGroupName] . ':' . $keyGroupName . ':' . $key;
	}

	/**
	 * Make invalid group key (like delete group key)
	 *
	 * @param string $keyGroupName Group name
	 * @return void
	 */
	function invalidateGroupKey($keyGroupName)
	{
		$this->keyGroupVersions[$keyGroupName]++;
		$this->handler->put('key_group_versions', $this->keyGroupVersions, 0);
	}

}

/**
 * Base class of Cache
 *
 * @author NAVER (developer@xpressengine.com)
 */
class CacheBase
{
	/**
	 * Default valid time
	 * @var int
	 */
	var $valid_time = 36000;

	/**
	 * Get cached data
	 *
	 * @param string $key Cache key
	 * @param int $modified_time 	Unix time of data modified.
	 * 								If stored time is older then modified time, return false.
	 * @return false|mixed Return false on failure or older then modified time. Return the string associated with the $key on success.
	 */
	function get($key, $modified_time = 0)
	{
		return false;
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
	function put($key, $obj, $valid_time = 0)
	{
		return false;
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
		return false;
	}

	/**
	 * Return whether support or not support cache
	 *
	 * @return boolean
	 */
	function isSupport()
	{
		return false;
	}

	/**
	 * Truncate all cache
	 *
	 * @return bool|void Returns true on success or false on failure. If use CacheFile, returns void.
	 */
	function truncate()
	{
		return false;
	}

}
/* End of file CacheHandler.class.php */
/* Location: ./classes/cache/CacheHandler.class.php */
