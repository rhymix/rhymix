<?php
    /**
     * @class CacheApc
     * @author NHN (developer@xpressengine.com)
     * @brief APC Handler
     * @version 0.1
	 *
     **/


	class CacheApc extends CacheBase {
		var $valid_time = 36000;

		function getInstance($opt=null){
            if(!$GLOBALS['__CacheApc__']) {
                $GLOBALS['__CacheApc__'] = new CacheApc();
            }
            return $GLOBALS['__CacheApc__'];
		}

		function CacheApc(){
		}

		function isSupport(){
			return function_exists('apc_add');
		}

		function put($key, $buff, $valid_time = 0){
			if($valid_time == 0) $valid_time = $this->valid_time;
			return apc_store(md5(_XE_PATH_.$key), array(time(), $buff), $valid_time);
		}

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

		function _delete($_key) {
			$this->put($_key,null,1);
		}

		function delete($key) {
			$this->_delete(md5(_XE_PATH_.$key));
		}

		function truncate() {
			return apc_clear_cache('user');
		}
	}
?>
