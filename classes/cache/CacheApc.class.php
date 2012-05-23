<?php
/**
 * Cache class for APC
 *
 * @author NHN (developer@xpressengine.com)
 **/
class CacheApc extends CacheBase {
	/**
	 * Default valid time
	 * @var int
	 */
	var $valid_time = 36000;

	/**
	 * Get instance of CacheApc
	 *
	 * @param void $opt Not used
	 * @return CacheApc instance of CacheApc
	 */
	function getInstance($opt=null){
		if(!$GLOBALS['__CacheApc__']) {
			$GLOBALS['__CacheApc__'] = new CacheApc();
		}
		return $GLOBALS['__CacheApc__'];
	}

	/**
	 * Constructor
	 *
	 * @return void
	 */
	function CacheApc(){
	}

	/**
	 * Return whether support or not support cache
	 *
	 * @return bool Return true on support or false on not support
	 */
	function isSupport(){
		return function_exists('apc_add');
	}

	/**
	 * Cache a variable in the data store
	 *
	 * @param string $key Store the variable using this name. $key are cache-unique, so storing a second value with the same $key will overwrite the original value.
	 * @param mixed $buff The variable to store
	 * @param int $valid_time 	Time To Live; store $buff in the cache for ttl seconds.
	 *							After the ttl has passed., the stored variable will be expunged from the cache (on the next request).
	 *							If no ttl is supplied, use the default valid time CacheApc::valid_time.
	 * @return bool Returns true on success or false on failure.
	 */
	function put($key, $buff, $valid_time = 0){
		if($valid_time == 0) $valid_time = $this->valid_time;
		return apc_store(md5(_XE_PATH_.$key), array(time(), $buff), $valid_time);
	}

	/**
	 * Return whether cache is valid or invalid
	 *
	 * @param string $key Cache key
	 * @param int $modified_time 	Unix time of data modified.
	 *								If stored time is older then modified time, the data is invalid.
	 * @return bool Return true on valid or false on invalid.
	 */
	function isValid($key, $modified_time = 0) {
		$_key = md5(_XE_PATH_.$key);
		$obj = apc_fetch($_key, $success);
		if(!$success || !is_array($obj)) return false;
		unset($obj[1]);

		if($modified_time > 0 && $modified_time > $obj[0]) {
			$this->_delete($_key);
			return false;
		}
		
		return true;
	}

	/**
	 * Fetch a stored variable from the cache
	 *
	 * @param string $key The $key used to store the value.
	 * @param int $modified_time 	Unix time of data modified.
	 *								If stored time is older then modified time, return false.
	 * @return false|mixed Return false on failure or older then modified time. Return the string associated with the $key on success.
	 */
	function get($key, $modified_time = 0) {
		$_key = md5(_XE_PATH_.$key);
		$obj = apc_fetch($_key, $success);
		if(!$success || !is_array($obj)) return false;

		if($modified_time > 0 && $modified_time > $obj[0]) {
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
	function _delete($_key) {
		$this->put($_key,null,1);
	}

	/**
	 * Delete variable from the cache
	 *
	 * @param string $key Used to store the value.
	 * @return void
	 */
	function delete($key) {
		$_key = md5(_XE_PATH_.$key);
		$this->_delete($_key);
	}

	/**
	 * Truncate all existing variables at the cache
	 *
	 * @return bool Returns true on success or false on failure.
	 */
	function truncate() {
		return apc_clear_cache('user');
	}
}

/* End of file CacheApc.class.php */
/* Location: ./classes/cache/CacheApc.class.php */
