<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

require_once('DBMysql.class.php');

/**
 * Class to use MySQLi DBMS as mysqli_*
 * mysql handling class
 *
 * Does not use prepared statements, since mysql driver does not support them
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db
 * @version 0.1
 */
class DBMysqli extends DBMysql
{

	/**
	 * Constructor
	 * @return void
	 */
	function DBMysqli()
	{
		$this->_setDBInfo();
		$this->_connect();
	}

	/**
	 * Create an instance of this class
	 * @return DBMysqli return DBMysqli object instance
	 */
	function create()
	{
		return new DBMysqli;
	}

	/**
	 * DB Connect
	 * this method is private
	 * @param array $connection connection's value is db_hostname, db_port, db_database, db_userid, db_password
	 * @return resource
	 */
	function __connect($connection)
	{
		// Attempt to connect
		if($connection["db_port"])
		{
			$result = @mysqli_connect($connection["db_hostname"]
							, $connection["db_userid"]
							, $connection["db_password"]
							, $connection["db_database"]
							, $connection["db_port"]);
		}
		else
		{
			$result = @mysqli_connect($connection["db_hostname"]
							, $connection["db_userid"]
							, $connection["db_password"]
							, $connection["db_database"]);
		}
		$error = mysqli_connect_errno();
		if($error)
		{
			$this->setError($error, mysqli_connect_error());
			return;
		}
		mysqli_set_charset($result, 'utf8');
		return $result;
	}

	/**
	 * DB disconnection
	 * this method is private
	 * @param resource $connection
	 * @return void
	 */
	function _close($connection)
	{
		mysqli_close($connection);
	}

	/**
	 * Handles quatation of the string variables from the query
	 * @param string $string
	 * @return string
	 */
	function addQuotes($string)
	{
		if(version_compare(PHP_VERSION, "5.4.0", "<") && get_magic_quotes_gpc())
		{
			$string = stripslashes(str_replace("\\", "\\\\", $string));
		}
		if(!is_numeric($string))
		{
			$connection = $this->_getConnection('master');
			$string = mysqli_escape_string($connection, $string);
		}
		return $string;
	}

	/**
	 * Execute the query
	 * this method is private
	 * @param string $query
	 * @param resource $connection
	 * @return resource
	 */
	function __query($query, $connection)
	{
		if($this->use_prepared_statements == 'Y')
		{
			// 1. Prepare query
			$stmt = mysqli_prepare($connection, $query);
			if($stmt)
			{
				$types = '';
				$params = array();
				$this->_prepareQueryParameters($types, $params);

				if(!empty($params))
				{
					$args[0] = $stmt;
					$args[1] = $types;

					$i = 2;
					foreach($params as $key => $param)
					{
						$copy[$key] = $param;
						$args[$i++] = &$copy[$key];
					}

					// 2. Bind parameters
					$status = call_user_func_array('mysqli_stmt_bind_param', $args);
					if(!$status)
					{
						$this->setError(-1, "Invalid arguments: $query" . mysqli_error($connection) . PHP_EOL . print_r($args, true));
					}
				}

				// 3. Execute query
				$status = mysqli_stmt_execute($stmt);

				if(!$status)
				{
					$this->setError(-1, "Prepared statement failed: $query" . mysqli_error($connection) . PHP_EOL . print_r($args, true));
				}

				// Return stmt for other processing - like retrieving resultset (_fetch)
				return $stmt;
				// mysqli_stmt_close($stmt);
			}
		}
		// Run the query statement
		$result = mysqli_query($connection, $query);
		// Error Check
		$error = mysqli_error($connection);
		if($error)
		{
			$this->setError(mysqli_errno($connection), $error);
		}
		// Return result
		return $result;
	}

	/**
	 * Before execute query, prepare statement
	 * this method is private
	 * @param string $types
	 * @param array $params
	 * @return void
	 */
	function _prepareQueryParameters(&$types, &$params)
	{
		$types = '';
		$params = array();
		if(!$this->param)
		{
			return;
		}

		foreach($this->param as $k => $o)
		{
			$value = $o->getUnescapedValue();
			$type = $o->getType();

			// Skip column names -> this should be concatenated to query string
			if($o->isColumnName())
			{
				continue;
			}

			switch($type)
			{
				case 'number' :
					$type = 'i';
					break;
				case 'varchar' :
					$type = 's';
					break;
				default:
					$type = 's';
			}

			if(is_array($value))
			{
				foreach($value as $v)
				{
					$params[] = $v;
					$types .= $type;
				}
			}
			else
			{
				$params[] = $value;
				$types .= $type;
			}
		}
	}

	/**
	 * Fetch the result
	 * @param resource $result
	 * @param int|NULL $arrayIndexEndValue
	 * @return array
	 */
	function _fetch($result, $arrayIndexEndValue = NULL)
	{
		if($this->use_prepared_statements != 'Y')
		{
			return parent::_fetch($result, $arrayIndexEndValue);
		}
		$output = array();
		if(!$this->isConnected() || $this->isError() || !$result)
		{
			return $output;
		}

		// Prepared stements: bind result variable and fetch data
		$stmt = $result;
		$meta = mysqli_stmt_result_metadata($stmt);
		$fields = mysqli_fetch_fields($meta);

		/**
		 * Mysqli has a bug that causes LONGTEXT columns not to get loaded
		 * Unless store_result is called before
		 * MYSQLI_TYPE for longtext is 252
		 */
		$longtext_exists = false;
		foreach($fields as $field)
		{
			if(isset($resultArray[$field->name])) // When joined tables are used and the same column name appears twice, we should add it separately, otherwise bind_result fails
			{
				$field->name = 'repeat_' . $field->name;
			}

			// Array passed needs to contain references, not values
			$row[$field->name] = "";
			$resultArray[$field->name] = &$row[$field->name];

			if($field->type == 252)
			{
				$longtext_exists = true;
			}
		}
		$resultArray = array_merge(array($stmt), $resultArray);

		if($longtext_exists)
		{
			mysqli_stmt_store_result($stmt);
		}

		call_user_func_array('mysqli_stmt_bind_result', $resultArray);

		$rows = array();
		while(mysqli_stmt_fetch($stmt))
		{
			$resultObject = new stdClass();

			foreach($resultArray as $key => $value)
			{
				if($key === 0)
				{
					continue; // Skip stmt object
				}
				if(strpos($key, 'repeat_'))
				{
					$key = substr($key, 6);
				}
				$resultObject->$key = $value;
			}

			$rows[] = $resultObject;
		}

		mysqli_stmt_close($stmt);

		if($arrayIndexEndValue)
		{
			foreach($rows as $row)
			{
				$output[$arrayIndexEndValue--] = $row;
			}
		}
		else
		{
			$output = $rows;
		}

		if(count($output) == 1)
		{
			if(isset($arrayIndexEndValue))
			{
				return $output;
			}
			else
			{
				return $output[0];
			}
		}

		return $output;
	}

	/**
	 * Handles insertAct
	 * @param Object $queryObject
	 * @param boolean $with_values
	 * @return resource
	 */
	function _executeInsertAct($queryObject, $with_values = false)
	{
		if($this->use_prepared_statements != 'Y')
		{
			return parent::_executeInsertAct($queryObject);
		}
		$this->param = $queryObject->getArguments();
		$result = parent::_executeInsertAct($queryObject, $with_values);
		unset($this->param);
		return $result;
	}

	/**
	 * Handles updateAct
	 * @param Object $queryObject
	 * @param boolean $with_values
	 * @return resource
	 */
	function _executeUpdateAct($queryObject, $with_values = false)
	{
		if($this->use_prepared_statements != 'Y')
		{
			return parent::_executeUpdateAct($queryObject);
		}
		$this->param = $queryObject->getArguments();
		$result = parent::_executeUpdateAct($queryObject, $with_values);
		unset($this->param);
		return $result;
	}

	/**
	 * Handles deleteAct
	 * @param Object $queryObject
	 * @param boolean $with_values
	 * @return resource
	 */
	function _executeDeleteAct($queryObject, $with_values = false)
	{
		if($this->use_prepared_statements != 'Y')
		{
			return parent::_executeDeleteAct($queryObject);
		}
		$this->param = $queryObject->getArguments();
		$result = parent::_executeDeleteAct($queryObject, $with_values);
		unset($this->param);
		return $result;
	}

	/**
	 * Handle selectAct
	 * In order to get a list of pages easily when selecting \n
	 * it supports a method as navigation
	 * @param Object $queryObject
	 * @param resource $connection
	 * @param boolean $with_values
	 * @return Object
	 */
	function _executeSelectAct($queryObject, $connection = null, $with_values = false)
	{
		if($this->use_prepared_statements != 'Y')
		{
			return parent::_executeSelectAct($queryObject, $connection);
		}
		$this->param = $queryObject->getArguments();
		$result = parent::_executeSelectAct($queryObject, $connection, $with_values);
		unset($this->param);
		return $result;
	}

	/**
	 * Get the ID generated in the last query
	 * Return next sequence from sequence table
	 * This method use only mysql
	 * @return int
	 */
	function db_insert_id()
	{
		$connection = $this->_getConnection('master');
		return mysqli_insert_id($connection);
	}

	/**
	 * Fetch a result row as an object
	 * @param resource $result
	 * @return object
	 */
	function db_fetch_object(&$result)
	{
		return mysqli_fetch_object($result);
	}

	/**
	 * Free result memory
	 * @param resource $result
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	function db_free_result(&$result)
	{
		return mysqli_free_result($result);
	}

}

DBMysqli::$isSupported = function_exists('mysqli_connect');

/* End of file DBMysqli.class.php */
/* Location: ./classes/db/DBMysqli.class.php */
