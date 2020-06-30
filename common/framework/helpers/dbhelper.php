<?php

namespace Rhymix\Framework\Helpers;

use Rhymix\Framework\DB;
use Rhymix\Framework\Debug;
use Rhymix\Framework\Exceptions\DBError;

/**
 * DB helper class.
 */
class DBHelper extends \PDO
{
	/**
	 * Store the database type (e.g. master) here.
	 */
	protected $_type = 'master';
	
	/**
	 * Set the database type.
	 */
	public function setType(string $type)
	{
		$this->_type = $type;
	}
	
	/**
	 * Create a prepared statement.
	 * 
	 * @param string $statement
	 * @param array $driver_options
	 * @return PDOStatement|DBStmtHelper
	 */
	public function prepare($statement, $driver_options = null)
	{
		$start_time = microtime(true);
		$db_class = DB::getInstance($this->_type);
		
		try
		{
			if ($driver_options)
			{
				$stmt = parent::prepare($statement, $driver_options);
			}
			else
			{
				$stmt = parent::prepare($statement);
			}
			$stmt->setFetchMode(\PDO::FETCH_OBJ);
			$stmt->setType($this->_type);
		}
		catch (\PDOException $e)
		{
			$elapsed_time = microtime(true) - $start_time;
			$db_class->addElapsedTime($elapsed_time);
			$db_class->setError(-1, $e->getMessage());
			Debug::addQuery($db_class->getQueryLog($statement, $elapsed_time));
			throw new DBError($e->getMessage(), 0, $e);
		}
		
		return $stmt;
	}
	
	/**
	 * Execute a query.
	 * 
	 * @param string $statement
	 * @return PDOStatement|DBStmtHelper
	 */
	public function query($statement)
	{
		$start_time = microtime(true);
		$db_class = DB::getInstance($this->_type);
		$args = func_get_args();
		array_shift($args);
		
		try
		{
			$stmt = parent::query($statement, ...$args);
			$stmt->setFetchMode(\PDO::FETCH_OBJ);
			$stmt->setType($this->_type);
			$db_class->clearError();
		}
		catch (\PDOException $e)
		{
			$db_class->setError(-1, $e->getMessage());
		}
		
		$elapsed_time = microtime(true) - $start_time;
		$db_class->addElapsedTime($elapsed_time);
		Debug::addQuery($db_class->getQueryLog($statement, $elapsed_time));
		
		return $stmt;
	}
	
	/**
	 * Execute a query and return the number of affected rows.
	 * 
	 * @param string $statement
	 * @return bool
	 */
	public function exec($query)
	{
		$start_time = microtime(true);
		$db_class = DB::getInstance($this->_type);
		
		try
		{
			$result = parent::exec($query);
			$db_class->clearError();
		}
		catch (\PDOException $e)
		{
			$db_class->setError(-1, $e->getMessage());
		}
		
		$elapsed_time = microtime(true) - $start_time;
		$db_class->addElapsedTime($elapsed_time);
		Debug::addQuery($db_class->getQueryLog($query, $elapsed_time));
		
		return $result;
	}
}
