<?php

namespace Rhymix\Framework\Drivers\Cache;

use Rhymix\Framework\Storage;

/**
 * The SQLite cache driver.
 */
class SQLite implements \Rhymix\Framework\Drivers\CacheInterface
{
	/**
	 * Set this flag to false to disable cache prefixes.
	 */
	public $prefix = false;
	
	/**
	 * The singleton instance is stored here.
	 */
	protected static $_instance = null;
	
	/**
	 * The database handle and prepared statements are stored here.
	 */
	protected $_dbh = null;
	protected $_ps = array();
	
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct()
	{
		$dir = \RX_BASEDIR . 'files/cache/store';
		if (!Storage::isDirectory($dir))
		{
			Storage::createDirectory($dir);
		}
		
		$key = substr(hash_hmac('sha256', $dir, config('crypto.authentication_key')), 0, 32);
		$filename = "$dir/$key.db";
		if (Storage::exists($filename))
		{
			$this->_connect($filename);
		}
		else
		{
			$this->_connect($filename);
			for ($i = 0; $i < 32; $i++)
			{
				$this->_dbh->exec('CREATE TABLE cache_' . $i . ' (k TEXT PRIMARY KEY, v TEXT, exp INT)');
			}
		}
	}
	
	/**
	 * Connect to an SQLite3 database.
	 * 
	 * @param string $filename
	 * @return void
	 */
	protected function _connect($filename)
	{
		$this->_dbh = new \SQLite3($filename);
		$this->_dbh->busyTimeout(250);
		$this->_dbh->exec('PRAGMA journal_mode = MEMORY');
		$this->_dbh->exec('PRAGMA synchronous = OFF');
	}
	
	/**
	 * Create a new instance of the current cache driver, using the given settings.
	 * 
	 * @param array $config
	 * @return void
	 */
	public static function getInstance(array $config)
	{
		if (self::$_instance === null)
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * Check if the current cache driver is supported on this server.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public static function isSupported()
	{
		return class_exists('\\SQLite3', false) && config('crypto.authentication_key') !== null && stripos(\PHP_SAPI, 'win') === false;
	}
	
	/**
	 * Validate cache settings.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param mixed $config
	 * @return bool
	 */
	public static function validateSettings($config)
	{
		return true;
	}
	
	/**
	 * Get the value of a key.
	 * 
	 * This method returns null if the key was not found.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		$table = 'cache_' . (crc32($key) % 32);
		$stmt = $this->_dbh->prepare('SELECT v, exp FROM ' . $table . ' WHERE k = :key');
		if (!$stmt)
		{
			return null;
		}
		
		$stmt->bindValue(':key', $key, \SQLITE3_TEXT);
		$result = $stmt->execute();
		if (!$result)
		{
			return null;
		}
		
		$row = $result->fetchArray(\SQLITE3_NUM);
		if ($row)
		{
			if ($row[1] == 0 || $row[1] >= time())
			{
				return unserialize($row[0]);
			}
			else
			{
				$this->delete($key);
				return null;
			}
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Set the value to a key.
	 * 
	 * This method returns true on success and false on failure.
	 * $ttl is measured in seconds. If it is zero, the key should not expire.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @param bool $force
	 * @return bool
	 */
	public function set($key, $value, $ttl = 0, $force = false)
	{
		$table = 'cache_' . (crc32($key) % 32);
		$stmt = $this->_dbh->prepare('INSERT OR REPLACE INTO ' . $table . ' (k, v, exp) VALUES (:key, :val, :exp)');
		if (!$stmt)
		{
			return false;
		}
		
		$stmt->bindValue(':key', $key, \SQLITE3_TEXT);
		$stmt->bindValue(':val', serialize($value), \SQLITE3_TEXT);
		$stmt->bindValue(':exp', $ttl ? (time() + $ttl) : 0, \SQLITE3_INTEGER);
		return $stmt->execute() ? true : false;
	}
	
	/**
	 * Delete a key.
	 * 
	 * This method returns true on success and false on failure.
	 * If the key does not exist, it should return false.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function delete($key)
	{
		$table = 'cache_' . (crc32($key) % 32);
		$stmt = $this->_dbh->prepare('DELETE FROM ' . $table . ' WHERE k = :key');
		if (!$stmt)
		{
			return false;
		}
		
		$stmt->bindValue(':key', $key, \SQLITE3_TEXT);
		return $stmt->execute() ? true : false;
	}
	
	/**
	 * Check if a key exists.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		$table = 'cache_' . (crc32($key) % 32);
		$stmt = $this->_dbh->prepare('SELECT 1 FROM ' . $table . ' WHERE k = :key AND (exp = 0 OR exp >= :exp)');
		if (!$stmt)
		{
			return false;
		}
		
		$stmt->bindValue(':key', $key, \SQLITE3_TEXT);
		$stmt->bindValue(':exp', time(), \SQLITE3_INTEGER);
		$result = $stmt->execute();
		if (!$result)
		{
			return false;
		}
		
		$row = $result->fetchArray(\SQLITE3_NUM);
		if ($row)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Increase the value of a key by $amount.
	 * 
	 * If the key does not exist, this method assumes that the current value is zero.
	 * This method returns the new value.
	 * 
	 * @param string $key
	 * @param int $amount
	 * @return int
	 */
	public function incr($key, $amount)
	{
		$this->_dbh->exec('BEGIN');
		$current_value = $this->get($key);
		$new_value = intval($current_value) + $amount;
		if ($this->set($key, $new_value))
		{
			$this->_dbh->exec('COMMIT');
			return $new_value;
		}
		else
		{
			$this->_dbh->exec('ROLLBACK');
			return false;
		}
	}
	
	/**
	 * Decrease the value of a key by $amount.
	 * 
	 * If the key does not exist, this method assumes that the current value is zero.
	 * This method returns the new value.
	 * 
	 * @param string $key
	 * @param int $amount
	 * @return int
	 */
	public function decr($key, $amount)
	{
		return $this->incr($key, 0 - $amount);
	}
	
	/**
	 * Clear all keys from the cache.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public function clear()
	{
		for ($i = 0; $i < 32; $i++)
		{
			$this->_dbh->exec('DROP TABLE cache_' . $i);
		}
		
		for ($i = 0; $i < 32; $i++)
		{
			$this->_dbh->exec('CREATE TABLE cache_' . $i . ' (k TEXT PRIMARY KEY, v TEXT, exp INT)');
		}
		
		return true;
	}
}
