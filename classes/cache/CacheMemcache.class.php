<?php
/**
 * @class CacheMemcache
 * @author NHN (developer@xpressengine.com)
 * @brief Memcache Handler
 * @version 0.1
 **/

class CacheMemcache extends CacheBase {
	var $valid_time = 36000;
	var $Memcache;

	function getInstance($url){
		if(!$GLOBALS['__CacheMemcache__']) {
			$GLOBALS['__CacheMemcache__'] = new CacheMemcache($url);
		}
		return $GLOBALS['__CacheMemcache__'];
	}

	function CacheMemcache($url){
		//$config['url'] = array('memcache://localhost:11211');
		$config['url'] = is_array($url)?$url:array($url);
		$this->Memcache = new Memcache;

		foreach($config['url'] as $url) {
			$info = parse_url($url);
			$this->Memcache->addServer($info['host'], $info['port']);
		}
	}

	function isSupport(){
		if($GLOBALS['XE_MEMCACHE_SUPPORT']) return true;
		if($this->Memcache->set('xe', 'xe', MEMCACHE_COMPRESSED, 1)) {
			$GLOBALS['XE_MEMCACHE_SUPPORT'] = true;
		} else {
			$GLOBALS['XE_MEMCACHE_SUPPORT'] = false;
		}
		return $GLOBALS['XE_MEMCACHE_SUPPORT'];
	}

	function getKey($key){
		return md5(_XE_PATH_.$key);
	}

	function put($key, $buff, $valid_time = 0){
		if($valid_time == 0) $valid_time = $this->valid_time;

		return $this->Memcache->set($this->getKey($key), array(time(), $buff), MEMCACHE_COMPRESSED, $valid_time);
	}

	function isValid($key, $modified_time = 0) {
		$_key = $this->getKey($key);

		$obj = $this->Memcache->get($_key);
		if(!$obj || !is_array($obj)) return false;
		unset($obj[1]);

		if($modified_time > 0 && $modified_time > $obj[0]) {
			$this->_delete($_key);
			return false;
		}

		return true;
	}

	function get($key, $modified_time = 0) {
		$_key = $this->getKey($key);
		$obj = $this->Memcache->get($_key);
		if(!$obj || !is_array($obj)) return false;

		if($modified_time > 0 && $modified_time > $obj[0]) {
			$this->_delete($_key);
			return false;
		}

		unset($obj[0]);

		return $obj[1];
	}

	function delete($key) {
		$_key = $this->getKey($key);
		$this->_delete($_key);
	}

	function _delete($_key) {
		$this->Memcache->delete($_key);
	}

	function truncate() {
		return $this->Memcache->flush();
	}
}

/* End of file CacheMemcache.class.php */
/* Location: ./classes/cache/CacheMemcache.class.php */
