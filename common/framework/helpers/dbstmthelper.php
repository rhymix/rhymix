<?php

namespace Rhymix\Framework\Helpers;

use Rhymix\Framework\DB;
use Rhymix\Framework\Debug;
use Rhymix\Framework\Exceptions\DBError;

/**
 * DB Statement helper class.
 */
class DBStmtHelper extends \PDOStatement
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
	 * Execute a prepared statement.
	 * 
	 * @param array $input_parameters
	 * @return bool
	 */
	public function execute($input_parameters = null): bool
	{
		$start_time = microtime(true);
		$db_class = DB::getInstance($this->_type);
		
		try
		{
			$result = parent::execute($input_parameters);
			$db_class->clearError();
			
			$elapsed_time = microtime(true) - $start_time;
			$db_class->addElapsedTime($elapsed_time);
			Debug::addQuery($db_class->getQueryLog($this->queryString, $elapsed_time));
		}
		catch (\PDOException $e)
		{
			$db_class->setError(-1, $e->getMessage());
			
			$elapsed_time = microtime(true) - $start_time;
			$db_class->addElapsedTime($elapsed_time);
			Debug::addQuery($db_class->getQueryLog($this->queryString, $elapsed_time));
			throw new DBError($e->getMessage(), 0, $e);
		}
		
		return $result;
	}
}
