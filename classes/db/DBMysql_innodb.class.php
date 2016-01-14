<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

require_once('DBMysql.class.php');

/**
 * Class to use MySQL innoDB DBMS
 * mysql innodb handling class
 *
 * Does not use prepared statements, since mysql driver does not support them
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db
 * @version 0.1
 */
class DBMysql_innodb extends DBMysql
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
			$this->_query("START TRANSACTION", $connection);
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
			$this->_query("ROLLBACK", $connection);
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
		$this->_query("COMMIT", $connection);
		return true;
	}
}

DBMysql_innodb::$isSupported = function_exists('mysql_connect');

/* End of file DBMysql_innodb.class.php */
/* Location: ./classes/db/DBMysql_innodb.class.php */
