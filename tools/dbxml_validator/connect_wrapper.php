<?php
/** @file

    Extends XE db classes to allow parsing methods to work in the absence of
    a real db connection for the db type.

    Included by XML Query/Schema Language validator
*/

class DBMysqlConnectWrapper extends DBMysql
{
    public $queries = '';

    public function __construct()
    {
	$this->db_type = 'mysql';
	$this->_setDBInfo();	// Context::get() should indicate a mysql db
    }

    public function create()
    {
	return new DBMysqlConnectWrapper();
    }

    public function actDBClassStart()
    {
    }

    public function actStart($query)
    {
    }

    public function actFinish()
    {
    }

    public function actDBClassFinish()
    {
    }

    public function isSupported()
    {
	// No need to actually check for 'mysql_connect' function
	return TRUE;
    }

    public function __connect($connection)
    {
	return TRUE;
    }

    public function _afterConnect($connection)
    {
    }

    public function _close($connection)
    {
    }

    public function close($type = 'master', $indx = NULL)
    {
    }

    public function _begin()
    {
	return TRUE;
    }

    public function _rollback()
    {
	return TRUE;
    }

    public function _commit()
    {
	return TRUE;
    }

    public function __query($query, $connection)
    {
	$this->queries .= "\n" . $query;

	return TRUE;
    }

    public function _fetch($result, $arrayIndexEndValue = NULL)
    {
	return new any_prop_obj_base();
    }

    public function isTableExists($target_name)
    {
	parent::isTableExists($target_name);

	return FALSE;
    }

    public function db_insert_id()
    {
	return NULL;
    }

    public function db_fetch_object(&$result)
    {
	return new any_prop_obj_base();
    }
}

class DBMysqliConnectWrapper extends DBMysqli
{
    public $queries = '';

    public function __construct()
    {
	$this->db_type = 'mysqli';
	$this->_setDBInfo();	// Context::get() should indicate a mysqli db
    }

    public function create()
    {
	return new DBMysqlConnectWrapper();
    }

    public function actDBClassStart()
    {
    }

    public function actStart($query)
    {
    }

    public function actFinish()
    {
    }

    public function actDBClassFinish()
    {
    }

    public function isSupported()
    {
	// No need to actually check for 'mysql_connect' function
	return TRUE;
    }

    public function isTableExists($target_name)
    {
	parent::isTableExists($target_name);

	return FALSE;
    }

    // use old mysql escape function, since the mysqli one uses
    // the connection resource (to get the current character set)
    public function addQuotes($string)
    {
	if (version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc())
	    $string = stripslashes(str_replace("\\","\\\\",$string));

	if (!is_numeric($string))
	    $string = @mysql_real_escape_string($string);

	return $string;
    }
    public function __connect($connection)
    {
	return TRUE;
    }

    public function _afterConnect($connection)
    {
    }

    public function _close($connection)
    {
    }

    public function close($type = 'master', $indx = NULL)
    {
    }

    public function _begin()
    {
	return TRUE;
    }

    public function _rollback()
    {
	return TRUE;
    }

    public function _commit()
    {
	return TRUE;
    }

    public function __query($query, $connection)
    {
	$this->queries .= "\n" . $query;

	return TRUE;
    }

    public function _fetch($result, $arrayIndexEndValue = NULL)
    {
	return new any_prop_obj_base();
    }

    public function db_insert_id()
    {
	return NULL;
    }

    public function db_fetch_object(&$result)
    {
	return new any_prop_obj_base();
    }
}

class DBCubridConnectWrapper extends DBCubrid
{
    public $queries = '';

    public function __construct()
    {
	$this->db_type = 'cubrid';
	$this->_setDBInfo();	// Context::get() should indicate a CUBRID db
    }

    public function create()
    {
	return new DBMysqlConnectWrapper();
    }

    public function actDBClassStart()
    {
    }

    public function actStart($query)
    {
    }

    public function _makeSequence()
    {
	return TRUE;
    }

    public function actFinish()
    {
    }

    public function actDBClassFinish()
    {
    }

    public function isSupported()
    {
	// No need to actually check for 'cubrid_connect' function
	return TRUE;
    }

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

    public function __connect($connection)
    {
	return TRUE;
    }

    public function _afterConnect($connection)
    {
    }

    public function _close($connection)
    {
    }

    public function close($type = 'master', $indx = NULL)
    {
    }

    public function _begin()
    {
	return TRUE;
    }

    public function _rollback()
    {
	return TRUE;
    }

    public function _commit()
    {
	return TRUE;
    }

    public function __query($query, $connection)
    {
	$this->queries .= "\n" . $query;

	return TRUE;
    }

    public function _fetch($result, $arrayIndexEndValue = NULL)
    {
	return new any_prop_obj_base();
    }

    public function db_insert_id()
    {
	return NULL;
    }

    public function &db_fetch_object()
    {
	return new any_prop_obj_base();
    }
}

class DBMssqlConnectWrapper extends DBMssql
{
    public $queries = '';

    public function __construct()
    {
	$this->db_type = 'mssql';
	$this->_setDBInfo();	// Context::get() should indicate a MS Sql db
    }

    public function create()
    {
	return new DBMssqlConnectWrapper();
    }

    public function actDBClassStart()
    {
    }

    public function actStart($query)
    {
    }

    public function actFinish()
    {
    }

    public function actDBClassFinish()
    {
    }

    public function isSupported()
    {
	// No need to actually check for 'mssql_connect' function
	return TRUE;
    }

    public function isTableExists($target_name)
    {
	parent::isTableExists($target_name);

	return FALSE;
    }

    public function __connect($connection)
    {
	return TRUE;
    }

    public function _afterConnect($connection)
    {
    }

    public function _close($connection)
    {
    }

    public function close($type = 'master', $indx = NULL)
    {
    }

    public function _begin()
    {
	return TRUE;
    }

    public function _rollback()
    {
	return TRUE;
    }

    public function _commit()
    {
	return TRUE;
    }

    public function __query($query, $connection)
    {
	if ($this->queries)
	    $this->queries .= ";\n";

	$this->queries .= $query;

	return TRUE;
    }

    public function _fetch($result, $arrayIndexEndValue = NULL)
    {
	return new any_prop_obj_base();
    }

    public function db_insert_id()
    {
	return NULL;
    }

    public function &db_fetch_object()
    {
	return new any_prop_obj_base();
    }
}

?>
