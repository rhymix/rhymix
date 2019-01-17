<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Merged class for MySQL and MySQLi, with or without InnoDB
 */
class DBMySQL extends DB
{
	/**
	 * prefix of a tablename (One or more Rhymix can be installed in a single DB)
	 * @var string
	 */
	var $prefix = 'rx_';
	var $comment_syntax = '/* %s */';
	var $charset = 'utf8mb4';

	/**
	 * Column type used in MySQL
	 *
	 * Becasue a common column type in schema/query xml is used for colum_type,
	 * it should be replaced properly for each DBMS
	 * @var array
	 */
	var $column_type = array(
		'bignumber' => 'bigint',
		'number' => 'bigint',
		'varchar' => 'varchar',
		'char' => 'char',
		'text' => 'text',
		'bigtext' => 'longtext',
		'date' => 'varchar(14)',
		'float' => 'float',
	);

	/**
	 * Last statement executed
	 */
	var $last_stmt;
	
	/**
	 * Query parameters for prepared statement
	 */
	var $params = array();

	/**
	 * Constructor
	 * @return void
	 */
	function __construct()
	{
		$this->_setDBInfo();
		$this->_connect();
	}

	/**
	 * DB Connect
	 * this method is private
	 * @param array $connection connection's value is db_hostname, db_port, db_database, db_userid, db_password
	 * @return mysqli
	 */
	protected function __connect($connection)
	{
		// Attempt to connect
		if($connection['port'])
		{
			$mysqli = new mysqli($connection['host'], $connection['user'], $connection['pass'], $connection['database'], $connection['port']);
		}
		else
		{
			$mysqli = new mysqli($connection['host'], $connection['user'], $connection['pass'], $connection['database']);
		}
		
		// Check connection error
		if($mysqli->connect_errno)
		{
			Rhymix\Framework\Debug::displayError(sprintf('DB ERROR %d : %s', $mysqli->connect_errno, $mysqli->connect_error));
			exit;
		}
		
		// Check DB version
		$this->db_version = $mysqli->server_info;
		if (version_compare($this->db_version, '5.0.7', '<'))
		{
			Rhymix\Framework\Debug::displayError('Rhymix requires MySQL 5.0.7 or later. Current MySQL version is ' . $this->db_version);
			exit;
		}
		
		// Set DB charset
		$this->charset = isset($connection['charset']) ? $connection['charset'] : 'utf8';
		$mysqli->set_charset($this->charset);
		
		return $mysqli;
	}

	/**
	 * DB disconnection
	 * this method is private
	 * @param mysqli $connection
	 * @return void
	 */
	protected function _close($connection)
	{
		if ($connection instanceof mysqli)
		{
			$connection->close();
		}
	}

	/**
	 * Handles quatation of the string variables from the query
	 * @param string $string
	 * @return string
	 */
	function addQuotes($string)
	{
		if(!is_numeric($string))
		{
			$connection = $this->_getConnection('master');
			$string = $connection->real_escape_string($string);
		}
		return $string;
	}

	/**
	 * DB transaction start
	 * this method is private
	 * @return boolean
	 */
	protected function _begin($transactionLevel = 0)
	{
		$connection = $this->_getConnection('master');

		if(!$transactionLevel)
		{
			$connection->begin_transaction();
			$this->setQueryLog(array('query' => 'START TRANSACTION'));
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
	protected function _rollback($transactionLevel = 0)
	{
		$connection = $this->_getConnection('master');
		$point = $transactionLevel - 1;

		if($point)
		{
			$this->_query("ROLLBACK TO SP" . $point, $connection);
		}
		else
		{
			$connection->rollback();
			$this->setQueryLog(array('query' => 'ROLLBACK'));
		}
		return true;
	}

	/**
	 * DB transaction commit
	 * this method is private
	 * @return boolean
	 */
	protected function _commit()
	{
		$connection = $this->_getConnection('master');
		$connection->commit();
		$this->setQueryLog(array('query' => 'COMMIT'));
		return true;
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
		if (!($connection instanceof mysqli) || $connection->connection_errno)
		{
			$this->setError(-1, 'Unable to connect to DB.');
			return false;
		}
		
		if($this->use_prepared_statements == 'Y')
		{
			// 1. Prepare query
			$stmt = $connection->prepare($query);
			if(!$stmt)
			{
				$this->setError($connection->errno, $connection->error);
				return $this->last_stmt = $result;
			}
			
			// 2. Bind parameters
			if ($this->params)
			{
				$types = '';
				$params = array();
				foreach($this->params as $k => $o)
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
				
				// 2. Bind parameters
				$args = array();
				$args[0] = $stmt;
				$args[1] = $types;
				$i = 2;
				foreach($params as $key => $param)
				{
					$copy[$key] = $param;
					$args[$i++] = &$copy[$key];
				}

				$status = call_user_func_array('mysqli_stmt_bind_param', $args);
				if(!$status)
				{
					$this->setError(-1, "Invalid arguments: " . $connection->error);
					return $this->last_stmt = $stmt;
				}
			}

			// 3. Execute query
			$status = $stmt->execute();
			if(!$status)
			{
				$this->setError(-1, "Prepared statement failed: " . $connection->error);
				return $this->last_stmt = $stmt;
			}

			// Return stmt for other processing
			return $this->last_stmt = $stmt;
		}
		else
		{
			$result = $connection->query($query);
			if($connection->errno)
			{
				$this->setError($connection->errno, $connection->error);
			}
			
			return $this->last_stmt = $result;
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
		$output = array();
		if(!$this->isConnected() || $this->isError() || !$result)
		{
			return $output;
		}

		// No prepared statements
		if($this->use_prepared_statements != 'Y')
		{
			while($tmp = $this->db_fetch_object($result))
			{
				if($arrayIndexEndValue)
				{
					$output[$arrayIndexEndValue--] = $tmp;
				}
				else
				{
					$output[] = $tmp;
				}
			}
			$result->free_result();
		}
		
		// Prepared stements: bind result variable and fetch data
		else
		{
			$stmt = $result;
			$fields = $stmt->result_metadata()->fetch_fields();
			$row = array();
			$resultArray = array();

			/**
			 * Mysqli has a bug that causes LONGTEXT columns not to get loaded
			 * Unless store_result is called before
			 * MYSQLI_TYPE for longtext is 252
			 */
			$longtext_exists = false;
			foreach($fields as $field)
			{
				// When joined tables are used and the same column name appears twice, we should add it separately, otherwise bind_result fails
				if(isset($resultArray[$field->name]))
				{
					$field->name = 'repeat_' . $field->name;
				}

				// Array passed needs to contain references, not values
				$row[$field->name] = '';
				$resultArray[$field->name] = &$row[$field->name];

				if($field->type == 252)
				{
					$longtext_exists = true;
				}
			}
			$resultArray = array_merge(array($stmt), $resultArray);

			if($longtext_exists)
			{
				$stmt->store_result();
			}

			call_user_func_array('mysqli_stmt_bind_result', $resultArray);
			array_shift($resultArray);

			while($stmt->fetch())
			{
				$resultObject = new stdClass;
				foreach($resultArray as $key => $value)
				{
					if(strpos($key, 'repeat_'))
					{
						$key = substr($key, 6);
					}
					$resultObject->$key = $value;
				}
				
				if($arrayIndexEndValue)
				{
					$output[$arrayIndexEndValue--] = $resultObject;
				}
				else
				{
					$output[] = $resultObject;
				}
			}

			$stmt->free_result();
			$stmt->close();
		}
		
		// Return object if there is only 1 result.
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
		else
		{
			return $output;
		}
	}

	/**
	 * Return the sequence value incremented by 1
	 * Auto_increment column only used in the sequence table
	 * @return int
	 */
	function getNextSequence()
	{
		$query = sprintf("INSERT INTO `%ssequence` (seq) VALUES ('0')", $this->prefix);
		$this->_query($query);
		$sequence = $this->getInsertID();
		if($sequence % 10000 == 0)
		{
			$query = sprintf("DELETE FROM `%ssequence` WHERE seq < %d", $this->prefix, $sequence);
			$this->_query($query);
		}

		return $sequence;
	}

	/**
	 * Function to obtain mysql old password(mysql only)
	 * @param string $password input password
	 * @param string $saved_password saved password in DBMS
	 * @return boolean
	 */
	function isValidOldPassword($password, $saved_password)
	{
		$query = sprintf("SELECT PASSWORD('%s') AS password, OLD_PASSWORD('%s') AS old_password", $this->addQuotes($password), $this->addQuotes($password));
		$result = $this->_query($query);
		$tmp = $this->_fetch($result);
		if($tmp->password === $saved_password || $tmp->old_password === $saved_password)
		{
			return true;
		}
		return false;
	}

	/**
	 * Check a table exists status
	 * @param string $target_name
	 * @return boolean
	 */
	function isTableExists($target_name)
	{
		$query = sprintf("SHOW TABLES LIKE '%s%s'", $this->prefix, $this->addQuotes($target_name));
		$result = $this->_query($query);
		$tmp = $this->_fetch($result);
		if(!$tmp)
		{
			return false;
		}
		return true;
	}

	/**
	 * Add a column to the table
	 * @param string $table_name table name
	 * @param string $column_name column name
	 * @param string $type column type, default value is 'number'
	 * @param int $size column size
	 * @param string|int $default default value
	 * @param boolean $notnull not null status, default value is false
	 * @return void
	 */
	function addColumn($table_name, $column_name, $type = 'number', $size = '', $default = null, $notnull = false, $after = null)
	{
		$type = strtolower($type);
		$type = isset($this->column_type[$type]) ? $this->column_type[$type] : $type;
		if(in_array($type, ['integer', 'int', 'bigint', 'smallint']))
		{
			$size = '';
		}

		$query = sprintf("ALTER TABLE `%s%s` ADD `%s` ", $this->prefix, $table_name, $column_name);
		if($size)
		{
			$query .= sprintf(" %s(%s) ", $type, $size);
		}
		else
		{
			$query .= sprintf(" %s ", $type);
		}
		if(isset($default))
		{
			$query .= sprintf(" DEFAULT '%s' ", $default);
		}
		if($notnull)
		{
			$query .= " NOT NULL ";
		}
		if($after_column)
		{
			$query .= sprintf(" AFTER `%s` ", $after_column);
		}

		return $this->_query($query);
	}

	/**
	 * Drop a column from the table
	 * @param string $table_name table name
	 * @param string $column_name column name
	 * @return void
	 */
	function dropColumn($table_name, $column_name)
	{
		$query = sprintf("ALTER TABLE `%s%s` DROP `%s` ", $this->prefix, $table_name, $column_name);
		$this->_query($query);
	}

	/**
	 * Modify a column
	 * @param string $table_name table name
	 * @param string $column_name column name
	 * @param string $type column type, default value is 'number'
	 * @param int $size column size
	 * @param string|int $default default value
	 * @param boolean $notnull not null status, default value is false
	 * @return bool
	 */
	function modifyColumn($table_name, $column_name, $type = 'number', $size = '', $default = '', $notnull = false)
	{
		$type = strtolower($type);
		$type = isset($this->column_type[$type]) ? $this->column_type[$type] : $type;
		if(in_array($type, ['integer', 'int', 'bigint', 'smallint']))
		{
			$size = '';
		}

		$query = sprintf("ALTER TABLE `%s%s` MODIFY `%s` ", $this->prefix, $table_name, $column_name);
		if($size)
		{
			$query .= sprintf(" %s(%s) ", $type, $size);
		}
		else
		{
			$query .= sprintf(" %s ", $type);
		}
		if($default)
		{
			$query .= sprintf(" DEFAULT '%s' ", $default);
		}
		if($notnull)
		{
			$query .= " NOT NULL ";
		}
		
		return $this->_query($query) ? true : false;
	}

	/**
	 * Check column exist status of the table
	 * @param string $table_name table name
	 * @param string $column_name column name
	 * @return boolean
	 */
	function isColumnExists($table_name, $column_name)
	{
		$query = sprintf("SHOW FIELDS FROM `%s%s`", $this->prefix, $table_name);
		$result = $this->_query($query);
		if($this->isError())
		{
			return;
		}
		$output = $this->_fetch($result);
		if($output)
		{
			$column_name = strtolower($column_name);
			foreach($output as $key => $val)
			{
				$name = strtolower($val->Field);
				if($column_name == $name)
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get information about a column
	 * @param string $table_name table name
	 * @param string $column_name column name
	 * @return BaseObject
	 */
	function getColumnInfo($table_name, $column_name)
	{
		$query = sprintf("SHOW FIELDS FROM `%s%s` WHERE `Field` = '%s'", $this->prefix, $table_name, $column_name);
		$result = $this->_query($query);
		if($this->isError())
		{
			return;
		}
		$output = $this->_fetch($result);
		if($output)
		{
			$dbtype = $output->{'Type'};
			if($xetype = array_search($dbtype, $this->column_type))
			{
				$size = null;
			}
			elseif(strpos($dbtype, '(') !== false)
			{
				list($dbtype, $size) = explode('(', $dbtype, 2);
				$size = intval(rtrim($size, ')'));
				if($xetype = array_search($dbtype, $this->column_type))
				{
					// no-op
				}
				else
				{
					$xetype = $dbtype;
				}
			}
			else
			{
				$xetype = $dbtype;
				$size = null;
			}
			return (object)array(
				'name' => $output->{'Field'},
				'dbtype' => $dbtype,
				'xetype' => $xetype,
				'size' => $size,
				'default_value' => $output->{'Default'},
				'notnull' => strncmp($output->{'Null'}, 'NO', 2) == 0 ? true : false,
			);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Add an index to the table
	 * $target_columns = array(col1, col2)
	 * $is_unique? unique : none
	 * @param string $table_name table name
	 * @param string $index_name index name
	 * @param string|array $target_columns target column or columns
	 * @param boolean $is_unique
	 * @return void
	 */
	function addIndex($table_name, $index_name, $target_columns, $is_unique = false)
	{
		if(!is_array($target_columns))
		{
			$target_columns = array($target_columns);
		}

		$query = sprintf("ALTER TABLE `%s%s` ADD %s INDEX `%s` (%s);", $this->prefix, $table_name, $is_unique ? 'UNIQUE' : '', $index_name, implode(',', $target_columns));
		$this->_query($query);
	}

	/**
	 * Drop an index from the table
	 * @param string $table_name table name
	 * @param string $index_name index name
	 * @param boolean $is_unique
	 * @return void
	 */
	function dropIndex($table_name, $index_name, $is_unique = false)
	{
		$query = sprintf("ALTER TABLE `%s%s` DROP INDEX `%s`", $this->prefix, $table_name, $index_name);
		$this->_query($query);
	}

	/**
	 * Check index status of the table
	 * @param string $table_name table name
	 * @param string $index_name index name
	 * @return boolean
	 */
	function isIndexExists($table_name, $index_name)
	{
		$query = sprintf("SHOW INDEXES FROM `%s%s`", $this->prefix, $table_name);
		$result = $this->_query($query);
		if($this->isError())
		{
			return;
		}
		$output = $this->_fetch($result);
		if(!$output)
		{
			return;
		}
		if(!is_array($output))
		{
			$output = array($output);
		}

		for($i = 0; $i < count($output); $i++)
		{
			if($output[$i]->Key_name == $index_name)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Creates a table by using xml contents
	 * @param string $xml_doc xml schema contents
	 * @return void|object
	 */
	function createTableByXml($xml_doc)
	{
		return $this->_createTable($xml_doc);
	}

	/**
	 * Creates a table by using xml file path
	 * @param string $file_name xml schema file path
	 * @return void|object
	 */
	function createTableByXmlFile($file_name)
	{
		if(!file_exists($file_name))
		{
			return;
		}
		// read xml file
		$buff = FileHandler::readFile($file_name);
		return $this->_createTable($buff);
	}

	/**
	 * Create table by using the schema xml
	 *
	 * type : number, varchar, tinytext, text, bigtext, char, date, \n
	 * opt : notnull, default, size\n
	 * index : primary key, index, unique\n
	 * @param string $xml_doc xml schema contents
	 * @return void|object
	 */
	function _createTable($xml_doc)
	{
		// Parse XML
		$oXml = new XmlParser();
		$xml_obj = $oXml->parse($xml_doc);
		
		// Get table name and column list
		$table_name = $xml_obj->table->attrs->name;
		if($this->isTableExists($table_name))
		{
			return;
		}
		if(!is_array($xml_obj->table->column))
		{
			$columns[] = $xml_obj->table->column;
		}
		else
		{
			$columns = $xml_obj->table->column;
		}
		
		// Initialize the list of columns and indexes
		$column_schema = array();
		$primary_list = array();
		$unique_list = array();
		$index_list = array();
		
		// Process columns
		foreach($columns as $column)
		{
			$name = $column->attrs->name;
			$type = $column->attrs->type;
			$size = $column->attrs->size;
			$notnull = $column->attrs->notnull;
			$primary_key = $column->attrs->primary_key;
			$index = $column->attrs->index;
			$unique = $column->attrs->unique;
			$default = $column->attrs->default;
			$auto_increment = $column->attrs->auto_increment;
			$column_charset = '';
			$index_size_limit = '';
			
			// MySQL only supports 767 bytes for indexed columns.
			// This is 191 characters in utf8mb4 and 255 characters in utf8.
			if($column->attrs->utf8mb4 === 'false' && stripos($type, 'char') !== false)
			{
				$column_charset = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci';
			}
			elseif(($primary_key || $unique || $index) && stripos($type, 'char') !== false)
			{
				if($size > 255 || ($size > 191 && $this->charset === 'utf8mb4'))
				{
					if($primary_key || $unique)
					{
						$size = ($this->charset === 'utf8mb4') ? 191 : 255;
					}
					else
					{
						$index_size_limit = '(' . (($this->charset === 'utf8mb4') ? 191 : 255) . ')';
					}
				}
			}
			
			// Normalize data type
			$type = strtolower($type);
			$type = isset($this->column_type[$type]) ? $this->column_type[$type] : $type;
			if(in_array($type, ['integer', 'int', 'bigint', 'smallint']))
			{
				$size = '';
			}
			
			$column_schema[$name] = sprintf('`%s` %s%s %s %s %s %s',
				$name,
				$type,
				$size ? "($size)" : '',
				$column_charset,
				isset($default) ? "DEFAULT '$default'" : '',
				$notnull ? 'NOT NULL' : '',
				$auto_increment ? 'AUTO_INCREMENT' : ''
			);
			
			if($primary_key)
			{
				$primary_list[] = "`$name`";
			}
			else if($unique)
			{
				$unique_list[$unique][] = "`$name`" . $index_size_limit;
			}
			else if($index)
			{
				$index_list[$index][] = "`$name`" . $index_size_limit;
			}
		}
		
		// Process indexes
		if(count($primary_list))
		{
			$column_schema[] = sprintf("PRIMARY KEY (%s)", implode($primary_list, ', '));
		}
		if(count($unique_list))
		{
			foreach($unique_list as $key => $val)
			{
				$column_schema[] = sprintf("UNIQUE %s (%s)", $key, implode($val, ', '));
			}
		}
		if(count($index_list))
		{
			foreach($index_list as $key => $val)
			{
				$column_schema[] = sprintf("INDEX %s (%s)", $key, implode($val, ', '));
			}
		}
		
		// Generate table schema
		$engine = config('db.master.engine') === 'innodb' ? 'InnoDB' : 'MyISAM';
		$charset = $this->charset ?: 'utf8';
		$collation = $charset . '_unicode_ci';
		$schema = sprintf("CREATE TABLE `%s` (%s) %s",
			$this->addQuotes($this->prefix . $table_name),
			"\n" . implode($column_schema, ",\n") . "\n",
			"ENGINE = $engine CHARACTER SET $charset COLLATE $collation"
		);
		
		// Execute the complete query
		$output = $this->_query($schema);
		if($output)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Drop table
	 *
	 * @param string $name
	 * @return bool
	 */
	function dropTable($table_name)
	{
		// Generate the drop query
		$query = sprintf('DROP TABLE `%s`', $this->addQuotes($this->prefix . $table_name));
		
		// Execute the drop query
		$output = $this->_query($query);
		if($output)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Handles insertAct
	 * @param BaseObject $queryObject
	 * @param boolean $with_values
	 * @return mixed
	 */
	function _executeInsertAct($queryObject, $with_values = true)
	{
		if ($this->use_prepared_statements == 'Y')
		{
			$this->params = $queryObject->getArguments();
			$with_values = false;
		}
		
		$query = $this->getInsertSql($queryObject, $with_values, true);
		if ($query instanceof BaseObject)
		{
			$this->params = array();
			return $query;
		}
		else
		{
			$output = $this->_query($query);
			$this->params = array();
			return $output;
		}
	}

	/**
	 * Handles updateAct
	 * @param BaseObject $queryObject
	 * @param boolean $with_values
	 * @return mixed
	 */
	function _executeUpdateAct($queryObject, $with_values = true)
	{
		if ($this->use_prepared_statements == 'Y')
		{
			$this->params = $queryObject->getArguments();
			$with_values = false;
		}
		
		$query = $this->getUpdateSql($queryObject, $with_values, true);
		if ($query instanceof BaseObject)
		{
			$this->params = array();
			return $query;
		}
		else
		{
			$output = $this->_query($query);
			$this->params = array();
			return $output;
		}
	}

	/**
	 * Handles deleteAct
	 * @param BaseObject $queryObject
	 * @param boolean $with_values
	 * @return mixed
	 */
	function _executeDeleteAct($queryObject, $with_values = true)
	{
		if ($this->use_prepared_statements == 'Y')
		{
			$this->params = $queryObject->getArguments();
			$with_values = false;
		}
		
		$query = $this->getDeleteSql($queryObject, $with_values, true);
		if ($query instanceof BaseObject)
		{
			$this->params = array();
			return $query;
		}
		else
		{
			$output = $this->_query($query);
			$this->params = array();
			return $output;
		}
	}

	/**
	 * Handle selectAct
	 * In order to get a list of pages easily when selecting \n
	 * it supports a method as navigation
	 * @param BaseObject $queryObject
	 * @param resource $connection
	 * @param boolean $with_values
	 * @return BaseObject
	 */
	function _executeSelectAct($queryObject, $connection = null, $with_values = true)
	{
		if ($this->use_prepared_statements == 'Y')
		{
			$this->params = $queryObject->getArguments();
			$with_values = false;
		}
		
		$result = null;
		$limit = $queryObject->getLimit();
		if($limit && $limit->isPageHandler())
		{
			$output = $this->queryPageLimit($queryObject, $result, $connection, $with_values);
			$this->params = array();
			return $output;
		}
		else
		{
			$query = $this->getSelectSql($queryObject, $with_values);
			if($query instanceof BaseObject)
			{
				$this->params = array();
				return $query;
			}

			$result = $this->_query($query, $connection);
			if($this->isError())
			{
				$this->params = array();
				return $this->queryError($queryObject);
			}

			$data = $this->_fetch($result);
			$buff = new BaseObject;
			$buff->data = $data;

			if($queryObject->usesClickCount())
			{
				$update_query = $this->getClickCountQuery($queryObject);
				$this->_executeUpdateAct($update_query, $with_values);
			}

			$this->params = array();
			return $buff;
		}
	}

	/**
	 * Get the number of rows affected by the last query
	 * @return int
	 */
	function getAffectedRows()
	{
		$stmt = $this->last_stmt;
		return $stmt ? $stmt->affected_rows : -1;
	}

	/**
	 * Get the ID generated in the last query
	 * @return int
	 */
	function getInsertID()
	{
		$connection = $this->_getConnection('master');
		return $connection->insert_id;
	}

	/**
	 * @deprecated
	 * @return int
	 */
	function db_insert_id()
	{
		return $this->getInsertID();
	}

	/**
	 * Fetch a result row as an object
	 * @param resource $result
	 * @return BaseObject
	 */
	function db_fetch_object($result)
	{
		return $result->fetch_object();
	}

	/**
	 * Free result memory
	 * @param resource $result
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	function db_free_result($result)
	{
		return $result->free_result();
	}

	/**
	 * Return the DBParser
	 * @param boolean $force
	 * @return DBParser
	 */
	function getParser($force = FALSE)
	{
		$dbParser = new DBParser('`', '`', $this->prefix);
		return $dbParser;
	}

	/**
	 * If have a error, return error object
	 * @param BaseObject $queryObject
	 * @return BaseObject
	 */
	function queryError($queryObject)
	{
		$limit = method_exists($queryObject, 'getLimit') ? $queryObject->getLimit() : false;
		if($limit && $limit->isPageHandler())
		{
			$buff = new BaseObject;
			$buff->total_count = 0;
			$buff->total_page = 0;
			$buff->page = 1;
			$buff->data = array();
			$buff->page_navigation = new PageHandler(/* $total_count */0, /* $total_page */1, /* $page */1, /* $page_count */10); //default page handler values
			return $buff;
		}
		else
		{
			return;
		}
	}

	/**
	 * If select query execute, return page info
	 * @param BaseObject $queryObject
	 * @param resource $result
	 * @param resource $connection
	 * @param boolean $with_values
	 * @return BaseObject Object with page info containing
	 */
	function queryPageLimit($queryObject, $result, $connection, $with_values = true)
	{
		$limit = $queryObject->getLimit();
		// Total count
		$temp_where = $queryObject->getWhereString($with_values, false);
		$count_query = sprintf('select count(*) as "count" %s %s', 'FROM ' . $queryObject->getFromString($with_values), ($temp_where === '' ? '' : ' WHERE ' . $temp_where));

		// Check for distinct query and if found update count query structure
		$temp_select = $queryObject->getSelectString($with_values);
		$uses_distinct = stripos($temp_select, "distinct") !== false;
		$uses_groupby = $queryObject->getGroupByString() != '';
		if($uses_distinct || $uses_groupby)
		{
			$count_query = sprintf('select %s %s %s %s'
					, $temp_select == '*' ? '1' : $temp_select
					, 'FROM ' . $queryObject->getFromString($with_values)
					, ($temp_where === '' ? '' : ' WHERE ' . $temp_where)
					, ($uses_groupby ? ' GROUP BY ' . $queryObject->getGroupByString() : '')
			);

			// If query uses grouping or distinct, count from original select
			$count_query = sprintf('select count(*) as "count" from (%s) xet', $count_query);
		}

		$result_count = $this->_query($count_query, $connection);
		$count_output = $this->_fetch($result_count);
		$total_count = (int) (isset($count_output->count) ? $count_output->count : NULL);

		$list_count = $limit->list_count->getValue();
		if(!$list_count)
		{
			$list_count = 20;
		}
		$page_count = $limit->page_count->getValue();
		if(!$page_count)
		{
			$page_count = 10;
		}
		$page = $limit->page->getValue();
		if(!$page || $page < 1)
		{
			$page = 1;
		}

		// total pages
		if($total_count)
		{
			$total_page = (int) (($total_count - 1) / $list_count) + 1;
		}
		else
		{
			$total_page = 1;
		}

		// check the page variables
		if($page > $total_page)
		{
			// If requested page is bigger than total number of pages, return empty list
			$buff = new BaseObject;
			$buff->total_count = $total_count;
			$buff->total_page = $total_page;
			$buff->page = $page;
			$buff->data = array();
			$buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
			return $buff;
		}
		$start_count = ($page - 1) * $list_count;

		$query = $this->getSelectPageSql($queryObject, $with_values, $start_count, $list_count);

		$result = $this->_query($query, $connection);
		if($this->isError())
		{
			return $this->queryError($queryObject);
		}

		$virtual_no = $total_count - ($page - 1) * $list_count;
		$data = $this->_fetch($result, $virtual_no);

		$buff = new BaseObject;
		$buff->total_count = $total_count;
		$buff->total_page = $total_page;
		$buff->page = $page;
		$buff->data = $data;
		$buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
		return $buff;
	}

	/**
	 * If select query execute, return paging sql
	 * @param object $query
	 * @param boolean $with_values
	 * @param int $start_count
	 * @param int $list_count
	 * @return string select paging sql
	 */
	function getSelectPageSql($query, $with_values = true, $start_count = 0, $list_count = 0)
	{
		$select = $query->getSelectString($with_values);
		if($select == '')
		{
			return new BaseObject(-1, "Invalid query");
		}
		$select = 'SELECT ' . $select;

		$from = $query->getFromString($with_values);
		if($from == '')
		{
			return new BaseObject(-1, "Invalid query");
		}
		$from = ' FROM ' . $from;

		$where = $query->getWhereString($with_values);
		if($where != '')
		{
			$where = ' WHERE ' . $where;
		}

		$groupBy = $query->getGroupByString();
		if($groupBy != '')
		{
			$groupBy = ' GROUP BY ' . $groupBy;
		}

		$orderBy = $query->getOrderByString();
		if($orderBy != '')
		{
			$orderBy = ' ORDER BY ' . $orderBy;
		}

		$limit = $query->getLimitString();
		if($limit != '')
		{
			$limit = sprintf(' LIMIT %d, %d', $start_count, $list_count);
		}

		return $select . ' ' . $from . ' ' . $where . ' ' . $groupBy . ' ' . $orderBy . ' ' . $limit;
	}

	/**
	 * Find out the best supported character set
	 * 
	 * @return string
	 */
	function getBestSupportedCharset()
	{
		if($output = $this->_fetch($this->_query("SHOW CHARACTER SET LIKE 'utf8%'")))
		{
			$mb4_support = false;
			foreach($output as $row)
			{
				if($row->Charset === 'utf8mb4')
				{
					$mb4_support = true;
				}
			}
			return $mb4_support ? 'utf8mb4' : 'utf8';
		}
		else
		{
			return 'utf8';
		}
	}
}

DBMysql::$isSupported = class_exists('mysqli');
