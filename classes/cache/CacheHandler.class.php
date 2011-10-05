<?php
    /**
     * @class CacheHandler
     * @author NHN (developer@xpressengine.com)
     * @brief Cache Handler
     * @version 0.1
	 *
     **/

    class CacheHandler extends Handler {

		var $handler = null;
                var $keyGroupVersions = null;

                function &getInstance($target='object') {
                    return new CacheHandler($target);
                }

		function CacheHandler($target, $info=null) {
			if(!$info) $info = Context::getDBInfo();
			if($info){
				if($target == 'object'){
					if($info->use_object_cache =='apc') $type = 'apc';
					else if(substr($info->use_object_cache,0,8)=='memcache'){
						$type = 'memcache';
						$url = $info->use_object_cache;
					}
				}else if($target == 'template'){
					if($info->use_template_cache =='apc') $type = 'apc';
					else if(substr($info->use_template_cache,0,8)=='memcache'){
						$type = 'memcache';
						$url = $info->use_template_cache;
					}
				}

				if($type){
					$class = 'Cache' . ucfirst($type);
					include_once sprintf('%sclasses/cache/%s.class.php', _XE_PATH_, $class);
					$this->handler = call_user_func(array($class,'getInstance'), $url);
                                        $this->keyGroupVersions = $this->handler->get('key_group_versions', 0);
                                        if(!$this->keyGroupVersions) {
                                            $this->keyGroupVersions = array();
                                            $this->handler->put('key_group_versions', $this->keyGroupVersions, 0);
                                        }
				}
			}
		}

		function isSupport(){
			if($this->handler && $this->handler->isSupport()) return true;
			return false;
		}

		function get($key, $modified_time = 0){
			if(!$this->handler) return false;
			return $this->handler->get($key, $modified_time);
		}

		function put($key, $obj, $valid_time = 0){
			if(!$this->handler) return false;
			return $this->handler->put($key, $obj, $valid_time);
		}

		function delete($key){
			if(!$this->handler) return false;
			return $this->handler->delete($key);
		}

		function isValid($key, $modified_time){
			if(!$this->handler) return false;
			return $this->handler->isValid($key, $modified_time);
		}

		function truncate(){
			if(!$this->handler) return false;
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
                 */
                function getGroupKey($keyGroupName, $key){
                    if(!$this->keyGroupVersions[$keyGroupName]){
                        $this->keyGroupVersions[$keyGroupName] = 1;
                        $this->handler->put('key_group_versions', $this->keyGroupVersions, 0);
                    }

                    return $this->keyGroupVersions[$keyGroupName] . ':' . $keyGroupName . ':' . $key;
                }

                function invalidateGroupKey($keyGroupName){
                    $this->keyGroupVersions[$keyGroupName]++;
                    $this->handler->put('key_group_versions', $this->keyGroupVersions, 0);
                }
    }

	class CacheBase{
		function get($key, $modified_time = 0){
			return false;
		}

		function put($key, $obj, $valid_time = 0){
			return false;
		}

		function isValid($key, $modified_time = 0){
			return false;
		}

		function isSupport(){
			return false;
		}

		function truncate(){
			return false;
		}
	}
?>
