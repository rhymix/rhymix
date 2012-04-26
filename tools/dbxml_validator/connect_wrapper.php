<?php
/** @file
	vi: ts=4

	Extends XE db classes to allow parsing methods to work in the absence of
	a real db connection for the db type.

	Included by XML Query/Schema Language validator
*/

/**
  @brief
  @developer
  */
class DBMysqlConnectWrapper extends DBMysql
{
	public $queries = '';

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function __construct()
	{
		$this->db_type = 'mysql';
		$this->_setDBInfo();	// Context::get() should indicate a mysql db
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function create()
	{
		return new DBMysqlConnectWrapper();
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actDBClassStart()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $query
		*/
	public function actStart($query)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actFinish()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actDBClassFinish()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function isSupported()
	{
		// No need to actually check for 'mysql_connect' function
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function __connect($connection)
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function _afterConnect($connection)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function _close($connection)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $type
		@param $indx
		*/
	public function close($type = 'master', $indx = NULL)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _begin()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _rollback()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _commit()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $query
		@param $connection
		*/
	public function __query($query, $connection)
	{
		$this->queries .= "\n" . $query;

		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $result
		@param $arrayIndexEndValue
		*/
	public function _fetch($result, $arrayIndexEndValue = NULL)
	{
		return new Any_prop_obj_base();
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $target_name
		*/
	public function isTableExists($target_name)
	{
		parent::isTableExists($target_name);

		return FALSE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function db_insert_id()
	{
		return NULL;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $result
		*/
	public function db_fetch_object(&$result)
	{
		return new Any_prop_obj_base();
	}
}

/**
  @brief
  @developer
  */
class DBMysqliConnectWrapper extends DBMysqli
{
	public $queries = '';

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function __construct()
	{
		$this->db_type = 'mysqli';
		$this->_setDBInfo();	// Context::get() should indicate a mysqli db
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function create()
	{
		return new DBMysqlConnectWrapper();
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actDBClassStart()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $query
		*/
	public function actStart($query)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actFinish()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actDBClassFinish()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function isSupported()
	{
		// No need to actually check for 'mysql_connect' function
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $target_name
		*/
	public function isTableExists($target_name)
	{
		parent::isTableExists($target_name);

		return FALSE;
	}

	// use old mysql escape function, since the mysqli one uses
	// the connection resource (to get the current character set)
	/**
		@brief
		@developer
		@return
		@access
		@param $string
		*/
	public function addQuotes($string)
	{
		if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc())
		{
			$string = stripslashes(str_replace("\\", "\\\\", $string));
		}

		if(!is_numeric($string))
		{
			$string = @mysql_real_escape_string($string);
		}

		return $string;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function __connect($connection)
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function _afterConnect($connection)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function _close($connection)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $type
		@param $indx
		*/
	public function close($type = 'master', $indx = NULL)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _begin()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _rollback()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _commit()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $query
		@param $connection
		*/
	public function __query($query, $connection)
	{
		$this->queries .= "\n" . $query;

		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $result
		@param $arrayIndexEndValue
		*/
	public function _fetch($result, $arrayIndexEndValue = NULL)
	{
		return new Any_prop_obj_base();
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function db_insert_id()
	{
		return NULL;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $result
		*/
	public function db_fetch_object(&$result)
	{
		return new Any_prop_obj_base();
	}
}

/**
  @brief
  @developer
  */
class DBCubridConnectWrapper extends DBCubrid
{
	public $queries = '';

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function __construct()
	{
		$this->db_type = 'cubrid';
		$this->_setDBInfo();	// Context::get() should indicate a CUBRID db
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function create()
	{
		return new DBMysqlConnectWrapper();
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actDBClassStart()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $query
		*/
	public function actStart($query)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _makeSequence()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actFinish()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actDBClassFinish()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function isSupported()
	{
		// No need to actually check for 'cubrid_connect' function
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $target_name
		*/
	public function isTableExists($target_name)
	{
		try
		{
			parent::isTableExists($target_name);
		}
		catch (Exception $ex)
		{
		}

		return FALSE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function __connect($connection)
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function _afterConnect($connection)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function _close($connection)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $type
		@param $indx
		*/
	public function close($type = 'master', $indx = NULL)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _begin()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _rollback()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _commit()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $query
		@param $connection
		*/
	public function __query($query, $connection)
	{
		$this->queries .= "\n" . $query;

		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $result
		@param $arrayIndexEndValue
		*/
	public function _fetch($result, $arrayIndexEndValue = NULL)
	{
		return new Any_prop_obj_base();
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function db_insert_id()
	{
		return NULL;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function &db_fetch_object()
	{
		return new Any_prop_obj_base();
	}
}

/**
  @brief
  @developer
  */
class DBMssqlConnectWrapper extends DBMssql
{
	public $queries = '';

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function __construct()
	{
		$this->db_type = 'mssql';
		$this->_setDBInfo();	// Context::get() should indicate a MS Sql db
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function create()
	{
		return new DBMssqlConnectWrapper();
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actDBClassStart()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $query
		*/
	public function actStart($query)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actFinish()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function actDBClassFinish()
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function isSupported()
	{
		// No need to actually check for 'mssql_connect' function
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $target_name
		*/
	public function isTableExists($target_name)
	{
		parent::isTableExists($target_name);

		return FALSE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function __connect($connection)
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function _afterConnect($connection)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $connection
		*/
	public function _close($connection)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $type
		@param $indx
		*/
	public function close($type = 'master', $indx = NULL)
	{
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _begin()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _rollback()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function _commit()
	{
		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $query
		@param $connection
		*/
	public function __query($query, $connection)
	{
		if($this->queries)
		{
			$this->queries .= ";\n";
		}

		$this->queries .= $query;

		return TRUE;
	}

	/**
		@brief
		@developer
		@return
		@access
		@param $result
		@param $arrayIndexEndValue
		*/
	public function _fetch($result, $arrayIndexEndValue = NULL)
	{
		return new Any_prop_obj_base();
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function db_insert_id()
	{
		return NULL;
	}

	/**
		@brief
		@developer
		@return
		@access
		*/
	public function &db_fetch_object()
	{
		return new Any_prop_obj_base();
	}
}

/* End of file connect_wrapper.php */
/* Location: tools/dbxml_validator/connect_wrapper.php */
