<?php
/**
 * @class CacheFile
 * @author Arnia Software (xe_dev@arnia.ro)
 * @brief Filedisk Cache Handler
 * @version 0.1
 **/

class CacheFile extends CacheBase {
	var $valid_time = 36000;
	var $cache_dir = 'files/cache/store/';
	
	function getInstance(){
		if(!$GLOBALS['__CacheFile__']) {
			$GLOBALS['__CacheFile__'] = new CacheFile();
		}
		return $GLOBALS['__CacheFile__'];
	}

	function CacheFile(){
		$this->cache_dir = _XE_PATH_ . $this->cache_dir;
		if(!is_dir($this->cache_dir)) FileHandler::makeDir($this->cache_dir);
	}

	function getCacheFileName($key){
		return $this->cache_dir . str_replace(':', '_', $key);
	}
	
	function isSupport(){
		return true;
	}

	function put($key, $obj, $valid_time = 0){
		$cache_file = $this->getCacheFileName($key);		
		$text = serialize($obj);
		FileHandler::writeFile($cache_file, $text);
	}

	function isValid($key, $modified_time = 0) {
		$cache_file = $this->getCacheFileName($key);
		if(file_exists($cache_file)) return true;
		
		return false;
	}

	function get($key, $modified_time = 0) {
		$cache_file = $this->getCacheFileName($key);
		$content = FileHandler::readFile($cache_file);
		if(!$content) return false;
		
		return unserialize($content);
	}

	function _delete($_key) {
		$cache_file = $this->getCacheFileName($_key);
		FileHandler::removeFile($cache_file);
	}

	function delete($key) {
		$this->_delete($key);
	}

	function truncate() {
		FileHandler::removeFilesInDir($this->cache_dir);
	}
}

/* End of file CacheFile.class.php */
/* Location: ./classes/cache/CacheFile.class.php */
