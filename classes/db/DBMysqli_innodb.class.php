<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

require_once('DBMysql.class.php');
require_once('DBMysqli.class.php');

/**
 * Class to use MySQLi innoDB DBMS as mysqli_*
 * mysql innodb handling class
 *
 * Does not use prepared statements, since mysql driver does not support them
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db
 * @version 0.1
 */
class DBMysqli_innodb extends DBMysqli
{
	/**
	 * DB transaction start
	 * this method is private
	 * @return boolean
	 */
	function _begin($transactionLevel = 0)
	{
		$connection = $this->_getConnection('master');

		if(!$transactionLevel)
		{
			if(function_exists('mysqli_begin_transaction'))
			{
				mysqli_begin_transaction($connection);
				$this->setQueryLog(array('query' => 'START TRANSACTION'));
			}
			else
			{
				$this->_query("START TRANSACTION" . $point, $connection);
			}
		}
		else
		{
			$this->_query("SAVEPOINT SP" . $transactionLevel, $connection);
		}
		return true;
	}

	/**
	 * DB transaction rollback
	 * this method is private
	 * @return boolean
	 */
	function _rollback($transactionLevel = 0)
	{
		$connection = $this->_getConnection('master');
		$point = $transactionLevel - 1;

		if($point)
		{
			$this->_query("ROLLBACK TO SP" . $point, $connection);
		}
		else
		{
			mysqli_rollback($connection);
			$this->setQueryLog(array('query' => 'ROLLBACK'));
		}
		return true;
	}

	/**
	 * DB transaction commit
	 * this method is private
	 * @return boolean
	 */
	function _commit()
	{
		$connection = $this->_getConnection('master');
		mysqli_commit($connection);
		$this->setQueryLog(array('query' => 'COMMIT'));
		return true;
	}
}

DBMysqli_innodb::$isSupported = function_exists('mysqli_connect');

/* End of file DBMysqli.class.php */
/* Location: ./classes/db/DBMysqli.class.php */
