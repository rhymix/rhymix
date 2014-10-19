<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Cache class for file
 *
 * Filedisk Cache Handler
 *
 * @author NAVER (developers@xpressengine.com)
 */
class CacheFile extends CacheBase
{
	/**
	 * Path that value to stored
	 * @var string
	 */
	var $cache_dir = 'files/cache/store/';

	/**
	 * Get instance of CacheFile
	 *
	 * @return CacheFile instance of CacheFile
	 */
	function getInstance()
	{
		if(!$GLOBALS['__CacheFile__'])
		{
			$GLOBALS['__CacheFile__'] = new CacheFile();
		}
		return $GLOBALS['__CacheFile__'];
	}

	/**
	 * Constructor
	 *
	 * @return void
	 */
	function CacheFile()
	{
		$this->cache_dir = _XE_PATH_ . $this->cache_dir;
		FileHandler::makeDir($this->cache_dir);
	}

	/**
	 * Get cache file name by key
	 *
	 * @param string $key The key that will be associated with the item.
	 * @return string Returns cache file path
	 */
	function getCacheFileName($key)
	{
		return $this->cache_dir . str_replace(':', DIRECTORY_SEPARATOR, $key) . '.php';
	}

	/**
	 * Return whether support or not support cache
	 *
	 * @return true
	 */
	function isSupport()
	{
		return true;
	}

	/**
	 * Cache a variable in the data store
	 *
	 * @param string $key Store the variable using this name.
	 * @param mixed $obj The variable to store
	 * @param int $valid_time Not used
	 * @return void
	 */
	function put($key, $obj, $valid_time = 0)
	{
		$cache_file = $this->getCacheFileName($key);
		$content = array();
		$content[] = '<?php';
		$content[] = 'if(!defined(\'__XE__\')) { exit(); }';
		$content[] = 'return \'' . addslashes(serialize($obj)) . '\';';
		FileHandler::writeFile($cache_file, implode(PHP_EOL, $content));
	}

	/**
	 * Return whether cache is valid or invalid
	 *
	 * @param string $key Cache key
	 * @param int $modified_time Not used
	 * @return bool Return true on valid or false on invalid.
	 */
	function isValid($key, $modified_time = 0)
	{
		$cache_file = $this->getCacheFileName($key);

		if(file_exists($cache_file))
		{
			if($modified_time > 0 && filemtime($cache_file) < $modified_timed)
			{
				FileHandler::removeFile($cache_file);
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Fetch a stored variable from the cache
	 *
	 * @param string $key The $key used to store the value.
	 * @param int $modified_time Not used
	 * @return false|mixed Return false on failure. Return the string associated with the $key on success.
	 */
	function get($key, $modified_time = 0)
	{
		if(!$cache_file = FileHandler::exists($this->getCacheFileName($key)))
		{
			return false;
		}

		if($modified_time > 0 && filemtime($cache_file) < $modified_timed)
		{
			FileHandler::removeFile($cache_file);
			return false;
		}

		$content = include($cache_file);

		return unserialize(stripslashes($content));
	}

	/**
	 * Delete variable from the cache(private)
	 *
	 * @param string $_key Used to store the value.
	 * @return void
	 */
	function _delete($_key)
	{
		$cache_file = $this->getCacheFileName($_key);
		FileHandler::removeFile($cache_file);
	}

	/**
	 * Delete variable from the cache
	 *
	 * @param string $key Used to store the value.
	 * @return void
	 */
	function delete($key)
	{
		$this->_delete($key);
	}

	/**
	 * Truncate all existing variables at the cache
	 *
	 * @return bool Returns true on success or false on failure.
	 */
	function truncate()
	{
		FileHandler::removeFilesInDir($this->cache_dir);
	}

}
/* End of file CacheFile.class.php */
/* Location: ./classes/cache/CacheFile.class.php */
