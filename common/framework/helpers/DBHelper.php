<?php

namespace Rhymix\Framework\Helpers;

use Rhymix\Framework\DB;
use Rhymix\Framework\Debug;
use Rhymix\Framework\Exceptions\DBError;

/**
 * DB helper class.
 * 
 * We use instances of this class instead of raw PDO in order to provide
 * better logging and error handling while keeping backward compatibility.
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
			/**
			 * $stmt will be an instance of DBStmtHelper.
			 * This allows it to track the parent database's type
			 * and send query logs to the appropriate place.
			 */
			$stmt = $driver_options ? parent::prepare($statement, $driver_options) : parent::prepare($statement);
			$stmt->setFetchMode(\PDO::FETCH_OBJ);
			$stmt->setType($this->_type);
			$db_class->clearError();
		}
		catch (\PDOException $e)
		{
			/**
			 * We only measure the time when the prepared statement fails.
			 * If the statement is successfully prepared, time will be measured
			 * when the statement is executed in DBStmtHelper.
			 */
			$elapsed_time = microtime(true) - $start_time;
			$db_class->addElapsedTime($elapsed_time);
			$db_class->setError(-1, $e->getMessage());
			if (Debug::isEnabledForCurrentUser())
			{
				Debug::addQuery($db_class->getQueryLog($statement, $elapsed_time));
			}
			
			/**
			 * This is a new feature in Rhymix 2.0 so we don't have to mess
			 * with status objects. We just throw an exception. Catch it!
			 */
			throw new DBError($e->getMessage(), 0, $e);
		}
		
		return $stmt;
	}
	
	/**
	 * Execute a query.
	 * 
	 * This method accepts additional parameters, but they are not for creating
	 * prepared statements. They exist because PDO's own query() method accepts
	 * various kinds of additional parameters, and we don't want to touch them.
	 * 
	 * @param string $statement
	 * @return PDOStatement|DBStmtHelper
	 */
	public function query($statement, $fetch_mode = \PDO::FETCH_OBJ, ...$fetch_mode_args)
	{
		$start_time = microtime(true);
		$db_class = DB::getInstance($this->_type);
		$args = func_get_args();
		array_shift($args);
		
		try
		{
			/**
			 * $stmt will be an instance of DBStmtHelper.
			 * This allows it to track the parent database's type
			 * and send query logs to the appropriate place.
			 */
			$stmt = parent::query($statement, ...$args);
			$stmt->setFetchMode($fetch_mode);
			$stmt->setType($this->_type);
			$db_class->clearError();
		}
		catch (\PDOException $e)
		{
			$db_class->setError(-1, $e->getMessage());
		}
		finally
		{
			$elapsed_time = microtime(true) - $start_time;
			$db_class->addElapsedTime($elapsed_time);
			if (Debug::isEnabledForCurrentUser())
			{
				Debug::addQuery($db_class->getQueryLog($statement, $elapsed_time));
			}
		}
		
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
		finally
		{
			$elapsed_time = microtime(true) - $start_time;
			$db_class->addElapsedTime($elapsed_time);
			if (Debug::isEnabledForCurrentUser())
			{
				Debug::addQuery($db_class->getQueryLog($query, $elapsed_time));
			}
		}
		
		return $result;
	}
}
