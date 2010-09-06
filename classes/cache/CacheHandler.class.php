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
