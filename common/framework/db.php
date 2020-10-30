<?php

namespace Rhymix\Framework;

/**
 * The DB class.
 */
class DB
{
	/**
	 * Singleton instances.
	 */
	protected static $_instances = array();
	
	/**
	 * Connection handle.
	 */
	protected $_handle;
	
	/**
	 * Prefix and other connection settings.
	 */
	protected $_type = '';
	protected $_prefix = '';
	protected $_charset = 'utf8mb4';
	protected $_engine = 'innodb';
	
	/**
	 * Information about the last executed statement.
	 */
	protected $_last_stmt;
	
	/**
	 * Elapsed time.
	 */
	protected $_query_time = 0;
	protected $_total_time = 0;
	
	/**
	 * Current query ID and error information.
	 */
	protected $_query_id = '';
	protected $_errno = 0;
	protected $_errstr = '';
	
	/**
	 * Transaction level.
	 */
	protected $_transaction_level = 0;
	
	/**
	 * Properties for backward compatibility.
	 */
	public $db_type = 'mysql';
	public $db_version = '';
	public $use_prepared_statements = true;
	
	/**
	 * Get a singleton instance of the DB class.
	 * 
	 * @param string $type
	 * @return self
	 */
	public static function getInstance(string $type = 'master'): self
	{
		// If an instance already exists, return it.
		if (isset(self::$_instances[$type]))
		{
			return self::$_instances[$type];
		}
		
		// Create an instance with the appropriate configuration.
		if ($config = Config::get('db'))
		{
			$typeconfig = isset($config[$type]) ? $config[$type] : [];
			if (!count($typeconfig))
			{
				throw new Exceptions\DBError('DB type \'' . $type . '\' is not configured.');
			}
			
			return self::$_instances[$type] = new self($type, $typeconfig);
		}
		else
		{
			return new self($type, []);
		}
	}
   
	/**
	 * Constructor.
	 * 
	 * @param string $type
	 * @param array $config
	 */
	public function __construct(string $type, array $config)
	{
		// Save important config values to the instance.
		$this->_type = $type;
		$this->_prefix = $config['prefix'] ?: $this->_prefix;
		$this->_charset = $config['charset'] ?: $this->_charset;
		$this->_engine = $config['engine'] ?: $this->_engine;
		if (!count($config))
		{
			return;
		}
		
		// Connect to the DB.
		$dsn = 'mysql:host=' . $config['host'];
		$dsn .= (isset($config['port']) && $config['port'] != 3306) ? (';port=' . $config['port']) : '';
		$dsn .= ';dbname=' . $config['database'];
		$dsn .= ';charset=' . $this->_charset;
		$options = array(
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_EMULATE_PREPARES => false,
			\PDO::ATTR_STATEMENT_CLASS => array('\Rhymix\Framework\Helpers\DBStmtHelper'),
			\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
		);
		try
		{
			$this->_handle = new Helpers\DBHelper($dsn, $config['user'], $config['pass'], $options);
			$this->_handle->setType($type);
		}
		catch (\PDOException $e)
		{
			throw new Exceptions\DBError($e->getMessage(), 0, $e);
		}
		
		// Get the DB version.
		$this->db_version = $this->_handle->getAttribute(\PDO::ATTR_SERVER_VERSION);
	}
	
	/**
	 * Get the PDO handle for direct manipulation.
	 * 
	 * @return Helpers\DBHelper
	 */
	public function getHandle(): Helpers\DBHelper
	{
		return $this->_handle;
	}
	
	/**
	 * Create a prepared statement.
	 * 
	 * Table names in the FROM or JOIN clause of the statement are
	 * automatically prefixed with the configured prefix.
	 * 
	 * @param string $statement
	 * @param array $driver_options
	 * @return Helpers\DBStmtHelper
	 */
	public function prepare(string $statement, array $driver_options = [])
	{
		// Add table prefixes to the query string.
		$statement = $this->addPrefixes($statement);
		
		// Create and return a prepared statement.
		$this->_last_stmt = $this->_handle->prepare($statement, $driver_options);
		return $this->_last_stmt;
	}
	
	/**
	 * Execute a query string with or without parameters.
	 * 
	 * This method will automatically use prepared statements if there are
	 * any parameters. It is strongly recommended to pass any user-supplied
	 * values as separate parameters instead of embedding them directly
	 * in the query string, in order to prevent SQL injection attacks.
	 * 
	 * Table names in the FROM or JOIN clause of the statement are
	 * automatically prefixed with the configured prefix.
	 * 
	 * @param string $query_string
	 * @param mixed ...$args
	 * @return Helpers\DBStmtHelper
	 */
	public function query(string $query_string, ...$args)
	{
		// If query parameters are given as a single array, unpack it.
		if (count($args) === 1 && is_array($args[0]))
		{
			$args = $args[0];
		}
		
		// Add table prefixes to the query string.
		$query_string = $this->addPrefixes($query_string);
		
		// Execute either a prepared statement or a regular query depending on whether there are arguments.
		if (count($args))
		{
			$this->_last_stmt = $this->_handle->prepare($query_string);
			$this->_last_stmt->execute($args);
		}
		else
		{
			$this->_last_stmt = $this->_handle->query($query_string);
		}
		return $this->_last_stmt;
	}
	
	/**
	 * Execute an XML-defined query.
	 * 
	 * @param string $query_id
	 * @param array $args
	 * @param array $columns
	 * @param string $result_type
	 * @return \BaseObject
	 */
	public function executeQuery(string $query_id, $args = [], $column_list = [], $result_type = 'auto'): \BaseObject
	{
		// Validate the args.
		if (is_object($args))
		{
			$args = get_object_vars($args);
		}
		if (is_null($args))
		{
			$args = array();
		}
		if (!is_array($args))
		{
			return $this->setError(-1, 'Invalid query arguments.');
		}
		if (!$this->_handle)
		{
			return $this->setError(-1, 'DB is not configured.');
		}
		
		// Force the column list to a numerical array.
		$column_list = is_array($column_list) ? array_values($column_list) : array();
		
		// Start measuring elapsed time.
		$start_time = microtime(true);
		
		// Get the name of the XML file.
		$parts = explode('.', $query_id);
		if (count($parts) === 2)
		{
			array_unshift($parts, 'modules');
		}
		$filename = \RX_BASEDIR . $parts[0] . '/' . $parts[1] . '/queries/' . $parts[2] . '.xml';
		if (!Storage::exists($filename))
		{
			$output = $this->setError(-1, 'Query \'' . $query_id . '\' does not exist.');
			$output->page_navigation = new \PageHandler(0, 0, 0);
			$this->_total_time += (microtime(true) - $start_time);
			return $output;
		}
		
		// Parse and cache the XML file.
		$cache_key = sprintf('query:%s:%d', $filename, filemtime($filename));
		$query = Cache::get($cache_key);
		if (!$query)
		{
			$query = Parsers\DBQueryParser::loadXML($filename);
			if ($query)
			{
				Cache::set($cache_key, $query, 0, true);
			}
			else
			{
				$output = $this->setError(-1, 'Query \'' . $query_id . '\' cannot be parsed.');
				$output->page_navigation = new \PageHandler(0, 0, 0);
				$this->_total_time += (microtime(true) - $start_time);
				return $output;
			}
		}
		
		// Get the query string and parameters.
		try
		{
			$query_string = $query->getQueryString($this->_prefix, $args, $column_list);
			$query_params = $query->getQueryParams();
		}
		catch (Exceptions\QueryError $e)
		{
			$output = $this->setError(-1, $e->getMessage());
			$output->page_navigation = new \PageHandler(0, 0, 0);
			$this->_total_time += (microtime(true) - $start_time);
			return $output;
		}
		
		// If this query requires pagination, execute the COUNT(*) query first.
		$last_index = 0;
		if ($query->requiresPagination())
		{
			$this->_query_id = $query_id . ' (count)';
			$output = $this->_executeCountQuery($query_id, $query, $args, $last_index);
			if (!$output->toBool())
			{
				$output->page_navigation = new \PageHandler(0, 0, 0);
				$this->_query_id = '';
				$this->_total_time += (microtime(true) - $start_time);
				return $output;
			}
			
			// Do not execute the main query if the current page is out of bounds.
			if ($output->page > $output->total_page)
			{
				$output->add('_query', $query_string);
				$output->add('_elapsed_time', '0.00000');
				$output->page_navigation = new \PageHandler(0, 0, 0);
				$this->_query_id = '';
				$this->_total_time += (microtime(true) - $start_time);
				return $output;
			}
		}
		else
		{
			$output = new \BaseObject;
		}
		
		// Prepare and execute the main query.
		try
		{
			$this->_query_id = $query_id;
			if (count($query_params))
			{
				$this->_last_stmt = $this->_handle->prepare($query_string);
				$this->_last_stmt->execute($query_params);
			}
			else
			{
				$this->_last_stmt = $this->_handle->query($query_string);
			}
			
			if ($this->isError())
			{
				$output = $this->getError();
				$output->add('_query', $query_string);
				$output->add('_elapsed_time', '0.00000');
				$output->page_navigation = new \PageHandler(0, 0, 0);
				$this->_query_id = '';
				$this->_total_time += (microtime(true) - $start_time);
				return $output;
			}
			elseif ($query->type === 'SELECT')
			{
				$result = $this->_fetch($this->_last_stmt, $last_index, $result_type);
			}
			else
			{
				$result = null;
			}
		}
		catch (Exceptions\DBError $e)
		{
			$output = $this->setError(-1, $e->getMessage());
			$output->add('_query', $query_string);
			$output->add('_elapsed_time', '0.00000');
			$output->page_navigation = new \PageHandler(0, 0, 0);
			$this->_query_id = '';
			$this->_total_time += (microtime(true) - $start_time);
			return $output;
		}
		
		// Fill query information and result data in the output object.
		$this->_query_id = '';
		$this->_total_time += ($elapsed_time = microtime(true) - $start_time);
		$output->add('_query', $query_string);
		$output->add('_elapsed_time', sprintf('%0.5f', $elapsed_time));
		$output->data = $result;
		
		// Return the complete result.
		$this->clearError();
		return $output;
	}
	
	/**
	 * Execute a COUNT(*) query for pagination.
	 * 
	 * @param string $query_id
	 * @param Parsers\DBQuery\Query $query
	 * @param array $args
	 * @param int $last_index
	 * @return BaseObject
	 */
	protected function _executeCountQuery(string $query_id, Parsers\DBQuery\Query $query, array $args, int &$last_index): \BaseObject
	{
		// Get the COUNT(*) query string and parameters.
		try
		{
			$query_string = $query->getQueryString($this->_prefix, $args, [], true);
			$query_params = $query->getQueryParams();
		}
		catch (Exceptions\QueryError $e)
		{
			return $this->setError(-1, $e->getMessage());
		}
		
		// Prepare and execute the query.
		try
		{
			if (count($query_params))
			{
				$this->_last_stmt = $this->_handle->prepare($query_string);
				$this->_last_stmt->execute($query_params);
			}
			else
			{
				$this->_last_stmt = $this->_handle->query($query_string);
			}
			
			if ($this->isError())
			{
				return $this->getError();
			}
			else
			{
				$count = $this->_last_stmt->fetchColumn(0);
				$this->_last_stmt->closeCursor();
			}
		}
		catch (Exceptions\DBError $e)
		{
			$output = $this->setError(-1, $e->getMessage());
			return $output;
		}
		
		// Collect various counts used in the page calculation.
		list($is_expression, $list_count) = $query->navigation->list_count->getValue($args);
		list($is_expression, $page_count) = $query->navigation->page_count->getValue($args);
		list($is_expression, $page) = $query->navigation->page->getValue($args);
		$total_count = intval($count);
		$total_page = max(1, intval(ceil($total_count / $list_count)));
		$last_index = $total_count - (($page - 1) * $list_count);
		$page_handler = new \PageHandler($total_count, $total_page, $page, $page_count);
		
		// Compose the output object.
		$output = new \BaseObject;
		$output->total_count = $total_count;
		$output->total_page = $total_page;
		$output->page = $page;
		$output->data = null;
		$output->page_navigation = $page_handler;
		return $output;
	}
	
	/**
	 * Execute a literal query string.
	 * 
	 * This method should not be public, as it starts with an underscore.
	 * But since there are many legacy apps that rely on it, we will leave it public.
	 * 
	 * @param string $query_string
	 * @return Helpers\DBStmtHelper
	 */
	public function _query($query_string)
	{
		$this->_last_stmt = $this->_handle->query($query_string);
		return $this->_last_stmt;
	}
	
	/**
	 * Fetch results from a query.
	 * 
	 * This method should not be public, as it starts with an underscore.
	 * But since there are many legacy apps that rely on it, we will leave it public.
	 * 
	 * @param \PDOStatement $stmt
	 * @param int $last_index
	 * @param string $result_type
	 * @return mixed
	 */
	public function _fetch($stmt, $last_index = 0, $result_type = 'auto')
	{
		if (!($stmt instanceof \PDOStatement))
		{
			return null;
		}
		if ($result_type === 'raw')
		{
			return $stmt;
		}
		
		$result = array();
		$index = $last_index;
		$step = $last_index !== 0 ? -1 : 1;
		
		while ($row = $stmt->fetchObject())
		{
			$result[$index] = $row;
			$index += $step;
		}
		
		$stmt->closeCursor();
		
		if ($result_type === 'auto' && $last_index === 0 && count($result) === 1)
		{
			return $result[0];
		}
		else
		{
			return $result;
		}
	}
	
	/**
	 * Begin a transaction.
	 * 
	 * @return int
	 */
	public function begin(): int
	{
		if (!$this->_handle->inTransaction())
		{
			try
			{
				$this->_handle->beginTransaction();
				$this->clearError();
			}
			catch (\PDOException $e)
			{
				$this->setError(-1, $e->getMessage());
			}
			Debug::addQuery($this->getQueryLog('START TRANSACTION', 0));
		}
		$this->_transaction_level++;
		return $this->_transaction_level;
	}
	
	/**
	 * Roll back a transaction.
	 * 
	 * @return int
	 */
	public function rollback(): int
	{
		if ($this->_handle->inTransaction() && $this->_transaction_level === 1)
		{
			try
			{
				$this->_handle->rollBack();
				$this->clearError();
			}
			catch (\PDOException $e)
			{
				$this->setError(-1, $e->getMessage());
			}
			Debug::addQuery($this->getQueryLog('ROLLBACK', 0));
		}
		$this->_transaction_level--;
		return $this->_transaction_level;
	}
	
	/**
	 * Commit a transaction.
	 * 
	 * @return int
	 */
	public function commit(): int
	{
		if ($this->_handle->inTransaction() && $this->_transaction_level === 1)
		{
			try
			{
				$this->_handle->commit();
				$this->clearError();
			}
			catch (\PDOException $e)
			{
				$this->setError(-1, $e->getMessage());
			}
			Debug::addQuery($this->getQueryLog('COMMIT', 0));
		}
		$this->_transaction_level--;
		return $this->_transaction_level;
	}
	
	/**
	 * Get the number of rows affected by the last statement.
	 * 
	 * @return int
	 */
	public function getAffectedRows(): int
	{
		return $this->_last_stmt ? intval($this->_last_stmt->rowCount()) : 0;
	}
	
	/**
	 * Get the auto-incremented ID generated by the last statement.
	 * 
	 * @return int
	 */
	public function getInsertID(): int
	{
		return intval($this->_handle->lastInsertId());
	}
	
	/**
	 * Get the next global sequence value.
	 */
	public function getNextSequence()
	{
		$this->_handle->exec(sprintf('INSERT INTO `%s` (seq) VALUES (0)', $this->addQuotes($this->_prefix . 'sequence')));
		$sequence = $this->getInsertID();
		if ($this->isError())
		{
			throw new Exceptions\DBError($this->getError()->getMessage());
		}
		
		if($sequence % 10000 == 0)
		{
			$this->_handle->exec(sprintf('DELETE FROM `%s` WHERE seq < %d', $this->addQuotes($this->_prefix . 'sequence'), $sequence));
		}
		
		$this->clearError();
		return $sequence;
	}
	
	/**
	 * Check if a password is valid according to MySQL's old password hashing algorithm.
	 * 
	 * @param string $password
	 * @param string $saved_password
	 * @return bool
	 */
	public function isValidOldPassword(string $password, string $saved_password): bool
	{
		if ($saved_password && substr($saved_password, 0, 1) === '*')
		{
			return Password::checkPassword($password, $saved_password, 'mysql_new_password');
		}
		if ($saved_password && strlen($saved_password) === 16)
		{
			return Password::checkPassword($password, $saved_password, 'mysql_old_password');
		}
		
		return false;
	}
	
	/**
	 * Check if a table exists.
	 *
	 * @param string $table_name
	 * @return bool
	 */
	public function isTableExists(string $table_name): bool
	{
		$stmt = $this->_handle->query(sprintf("SHOW TABLES LIKE '%s'", $this->addQuotes($this->_prefix . $table_name)));
		$result = $this->_fetch($stmt);
		return $result ? true : false;
	}
	
	/**
	 * Create a table.
	 * 
	 * @param string $filename
	 * @param string $content
	 * @return BaseObject
	 */
	public function createTable(string $filename = '', string $content = ''): \BaseObject
	{
		// Get the table definition from DBTableParser.
		$table = Parsers\DBTableParser::loadXML($filename, $content);
		if (!$table)
		{
			return $this->setError(-1, 'Table creation failed.');
		}
		
		// Generate the CREATE TABLE query and execute it.
		$query_string = $table->getCreateQuery($this->_prefix, $this->_charset, $this->_engine);
		$result = $this->_handle->exec($query_string);
		return $result ? new \BaseObject : $this->getError();
	}
	
	/**
	 * Drop a table.
	 * 
	 * @param string $table_name
	 * @return BaseObject
	 */
	public function dropTable(string $table_name): \BaseObject
	{
		$stmt = $this->_handle->exec(sprintf("DROP TABLE `%s`", $this->addQuotes($this->_prefix . $table_name)));
		return $stmt ? new \BaseObject : $this->getError();
	}
	
	/**
	 * Check if a column exists.
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @return bool
	 */
	public function isColumnExists(string $table_name, string $column_name): bool
	{
		$stmt = $this->_handle->query(sprintf("SHOW FIELDS FROM `%s` WHERE Field = '%s'", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($column_name)));
		$result = $this->_fetch($stmt);
		return $result ? true : false;
	}
	
	/**
	 * Add a column.
	 * 
	 * @param string $table_name
	 * @param string $column_name
	 * @param string $type
	 * @param string $size
	 * @param mixed $default
	 * @param bool $notnull
	 * @param string $after_column
	 * @return BaseObject
	 */
	public function addColumn(string $table_name, string $column_name, string $type = 'number', $size = null, $default = null, $notnull = false, $after_column = null): \BaseObject
	{
		// Normalize the type and size.
		list($type, $xetype, $size) = Parsers\DBTableParser::getTypeAndSize($type, strval($size));
		
		// Compose the ADD COLUMN query.
		$query = sprintf("ALTER TABLE `%s` ADD COLUMN `%s` ", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($column_name));
		$query .= $size ? sprintf('%s(%s)', $type, $size) : $type;
		$query .= $notnull ? ' NOT NULL' : '';
		
		// Add the default value according to the type.
		if (isset($default))
		{
			if (contains('int', $type, false) && is_numeric($default))
			{
				$query .= sprintf(" DEFAULT %s", $default);
			}
			else
			{
				$query .= sprintf(" DEFAULT '%s'", $this->addQuotes($default));
			}
		}
		
		// Add position information.
		if ($after_column === 'FIRST')
		{
			$query .= ' FIRST';
		}
		elseif ($after_column)
		{
			$query .= sprintf(' AFTER `%s`', $this->addQuotes($after_column));
		}
		
		// Execute the query and return the result.
		$result = $this->_handle->exec($query);
		return $result ? new \BaseObject : $this->getError();
	}
	
	/**
	 * Modify a column.
	 * 
	 * @param string $table_name
	 * @param string $column_name
	 * @param string $type
	 * @param string $size
	 * @param mixed $default
	 * @param bool $notnull
	 * @param string $after_column
	 * @param string $new_name
	 * @return BaseObject
	 */
	public function modifyColumn(string $table_name, string $column_name, string $type = 'number', $size = null, $default = null, $notnull = false, $after_column = null, $new_name = null): \BaseObject
	{
		// Normalize the type and size.
		list($type, $xetype, $size) = Parsers\DBTableParser::getTypeAndSize($type, strval($size));
		
		// Compose the MODIFY COLUMN query.
		if ($new_name && $new_name !== $column_name)
		{
			$query = sprintf("ALTER TABLE `%s` CHANGE `%s` `%s` ", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($column_name), $this->addQuotes($new_name));
		}
		else
		{
			$query = sprintf("ALTER TABLE `%s` MODIFY `%s` ", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($column_name));
		}
		$query .= $size ? sprintf('%s(%s)', $type, $size) : $type;
		$query .= $notnull ? ' NOT NULL' : '';
		
		// Add the default value according to the type.
		if (isset($default))
		{
			if (contains('int', $type, false) && is_numeric($default))
			{
				$query .= sprintf(" DEFAULT %s", $default);
			}
			else
			{
				$query .= sprintf(" DEFAULT '%s'", $this->addQuotes($default));
			}
		}
		
		// Add position information.
		if ($after_column === 'FIRST')
		{
			$query .= ' FIRST';
		}
		elseif ($after_column)
		{
			$query .= sprintf(' AFTER `%s`', $this->addQuotes($after_column));
		}
		
		// Execute the query and return the result.
		$result = $this->_handle->exec($query);
		return $result ? new \BaseObject : $this->getError();
	}
	
	/**
	 * Drop a column.
	 * 
	 * @param string $table_name
	 * @param string $column_name
	 * @return BaseObject
	 */
	public function dropColumn(string $table_name, string $column_name): \BaseObject
	{
		$result = $this->_handle->exec(sprintf("ALTER TABLE `%s` DROP `%s`", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($column_name)));
		return $result ? new \BaseObject : $this->getError();
	}
	
	/**
	 * Get column information.
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @return object
	 */
	public function getColumnInfo(string $table_name, string $column_name)
	{
		// If column information is not found, return false.
		$stmt = $this->_handle->query(sprintf("SHOW FIELDS FROM `%s` WHERE Field = '%s'", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($column_name)));
		$column_info = $this->_fetch($stmt);
		if (!$column_info)
		{
			return false;
		}
		
		// Reorganize the type information.
		$dbtype = strtolower($column_info->{'Type'});
		if (preg_match('/^([a-z0-9_]+)\(([0-9,\s]+)\)$/i', $dbtype, $matches))
		{
			$dbtype = $matches[1];
			$size = $matches[2];
		}
		else
		{
			$size = '';
		}
		$xetype = Parsers\DBTableParser::getXEType($dbtype, $size ?: '');
		
		// Return the result as an object.
		return (object)array(
			'name' => $column_name,
			'dbtype' => $dbtype,
			'xetype' => $xetype,
			'size' => $size,
			'default_value' => $column_info->{'Default'},
			'notnull' => strncmp($column_info->{'Null'}, 'NO', 2) == 0 ? true : false,
		);
	}
	
	/**
	 * Check if an index exists.
	 * 
	 * @param string $table_name
	 * @param string $index_name
	 * @return boolean
	 */
	public function isIndexExists(string $table_name, string $index_name): bool
	{
		$stmt = $this->_handle->query(sprintf("SHOW INDEX FROM `%s` WHERE Key_name = '%s'", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($index_name)));
		$result = $this->_fetch($stmt);
		return $result ? true : false;
	}
	
	/**
	 * Add an index.
	 * 
	 * @param string $table_name
	 * @param string $index_name
	 * @param array $columns
	 * @param string $type
	 * @param string $options
	 * @return \BaseObject
	 */
	public function addIndex(string $table_name, string $index_name, $columns, $type = '', $options = ''): \BaseObject
	{
		if (!is_array($columns))
		{
			$columns = array($columns);
		}
		
		if ($type === true || $type === 1)
		{
			$type = 'UNIQUE';
		}
		
		$query = vsprintf("ALTER TABLE `%s` ADD %s `%s` (%s) %s", array(
			$this->addQuotes($this->_prefix . $table_name),
			ltrim($type . ' INDEX'),
			$this->addQuotes($index_name),
			implode(', ', array_map(function($column_name) {
				if (preg_match('/^([^()]+)\(([0-9]+)\)$/', $column_name, $matches))
				{
					return '`' . $this->addQuotes($matches[1]) . '`(' . $matches[2] . ')';
				}
				else
				{
					return '`' . $this->addQuotes($column_name) . '`';
				}
			}, $columns)),
			$options,
		));
		
		$result = $this->_handle->exec($query);
		return $result ? new \BaseObject : $this->getError();
	}
	
	/**
	 * Drop an index.
	 * 
	 * @param string $table_name
	 * @param string $index_name
	 * @return BaseObject
	 */
	public function dropIndex(string $table_name, string $index_name): \BaseObject
	{
		$result = $this->_handle->exec(sprintf("ALTER TABLE `%s` DROP INDEX `%s`", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($index_name)));
		return $result ? new \BaseObject : $this->getError();
	}
	
	/**
	 * Add table prefixes to a query string.
	 * 
	 * @param string $query_string
	 * @return string
	 */
	public function addPrefixes($query_string): string
	{
		if (!$this->_prefix)
		{
			return $query_string;
		}
		else
		{
			return preg_replace_callback('/((?:DELETE\s+)?FROM|JOIN|INTO|UPDATE)(?i)\s+((?:`?\w+\`?)(?:\s+AS\s+`?\w+`?)?(?:\s*,\s*(?:`?\w+\`?)(?:\s+AS\s+`?\w+`?)?)*)/', function($m) {
				$type = strtoupper($m[1]);
				$tables = array_map(function($str) use($type) {
					return preg_replace_callback('/`?(\w+)`?(?:\s+AS\s+`?(\w+)`?)?/i', function($m) use($type) {
						if ($type === 'FROM' || $type === 'JOIN')
						{
							return isset($m[2]) ? sprintf('`%s%s` AS `%s`', $this->_prefix, $m[1], $m[2]) : sprintf('`%s%s` AS `%s`', $this->_prefix, $m[1], $m[1]);
						}
						else
						{
							return sprintf('`%s%s`', $this->_prefix, $m[1]);	
						}
					}, trim($str));
				}, explode(',', $m[2]));
				return $m[1] . ' ' . implode(', ', $tables);
			}, $query_string);
		}
	}
	
	/**
	 * Escape a string according to current DB settings.
	 * 
	 * @param string $str
	 * @return string
	 */
	public function addQuotes($str): string
	{
		if (is_numeric($str))
		{
			return strval($str);
		}
		else
		{
			return preg_replace("/^'(.*)'$/s", '$1', $this->_handle->quote($str));
		}
	}
	
	/**
	 * Find out the best supported character set.
	 * 
	 * @return string
	 */
	public function getBestSupportedCharset(): string
	{
		$output = $this->_fetch($this->_handle->query("SHOW CHARACTER SET LIKE 'utf8%'"), 1);
		$utf8mb4_support = ($output && count(array_filter($output, function($row) {
			return $row->Charset === 'utf8mb4';
		})));
		return $utf8mb4_support ? 'utf8mb4' : 'utf8';
	}
	
	/**
	 * Check if the last statement produced an error.
	 * 
	 * @return bool
	 */
	public function isError(): bool
	{
		return $this->_errno !== 0 ? true : false;
	}
	
	/**
	 * Get the last error information.
	 * 
	 * @return \BaseObject
	 */
	public function getError(): \BaseObject
	{
		return new \BaseObject($this->_errno, $this->_errstr);
	}
	
	/**
	 * Set error information to instance properties.
	 * 
	 * @param int $errno
	 * @param string $errstr
	 * @return BaseObject
	 */
	public function setError(int $errno = 0, string $errstr = 'success'): \BaseObject
	{
		$this->_errno = $errno;
		$this->_errstr = $errstr;
		$output = new \BaseObject($errno, $errstr);
		return $output;
	}
	
	/**
	 * Clear error information.
	 * 
	 * @return void
	 */
	public function clearError()
	{
		$this->_errno = 0;
		$this->_errstr = 'success';
	}
	
	/**
	 * Generate a query log entry.
	 * 
	 * @param string $query
	 * @param float $elapsed_time
	 * @return array
	 */
	public function getQueryLog(string $query, float $elapsed_time): array
	{
		// Cache the debug status to improve performance.
		static $debug_enabled = null;
		static $debug_queries = null;
		if ($debug_enabled === null)
		{
			$debug_enabled = Config::get('debug.enabled');
		}
		if ($debug_queries === null)
		{
			$debug_queries = in_array('queries', Config::get('debug.display_content') ?: []);
		}
		
		// Compose the basic structure of the log entry.
		$result = array(
			'query' => $query,
			'query_id' => $this->_query_id,
			'connection' => $this->_type,
			'elapsed_time' => sprintf('%0.5f', $elapsed_time),
			'result' => 'success',
			'errno' => $this->_errno,
			'errstr' => $this->_errstr,
			'called_file' => null,
			'called_line' => null,
			'called_method' => null,
			'backtrace' => array(),
		);
		
		// Add debug information if enabled.
		if ($debug_enabled && ($this->_errno || $debug_queries))
		{
			$backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
			foreach ($backtrace as $no => $call)
			{
				if (!preg_match('#/common/framework/(?:db|helpers)\b#', $call['file']) && $call['file'] !== \RX_BASEDIR . 'common/legacy.php')
				{
					$result['called_file'] = $backtrace[$no]['file'];
					$result['called_line'] = $backtrace[$no]['line'];
					$no++;
					$result['called_method'] = $backtrace[$no]['class'] . $backtrace[$no]['type'] . $backtrace[$no]['function'];
					$result['backtrace'] = array_slice($backtrace, $no, 1);
					break;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Send an entry to the query log for debugging.
	 * 
	 * @param array $log
	 * @return void
	 */
	public function setQueryLog(array $log)
	{
		Debug::addQuery($log);
	}
	
	/**
	 * Add elapsed time.
	 * 
	 * @param float $elapsed_time
	 * @return void
	 */
	public function addElapsedTime(float $elapsed_time)
	{
		$this->_query_time += $elapsed_time;
	}
	
	/**
	 * Get total time spent during queries.
	 * 
	 * @return float
	 */
	public function getQueryElapsedTime(): float
	{
		return $this->_query_time;
	}
	
	/**
	 * Get total time spent in this class.
	 * 
	 * @return float
	 */
	public function getTotalElapsedTime(): float
	{
		return $this->_total_time;
	}
	
	/**
	 * ========================== DEPRECATED METHODS ==========================
	 * ==================== KEPT FOR COMPATIBILITY WITH XE ====================
	 */
	
	/**
	 * Old alias to getInstance().
	 * 
	 * @deprecated
	 * @return self
	 */
	public static function create(): self
	{
		return self::getInstance();
	}
	
	/**
	 * Old alias to $stmt->fetchObject().
	 * 
	 * @deprecated
	 * @param \PDOStatement $stmt
	 * @return object|false
	 */
	public function db_fetch_object(\PDOStatement $stmt)
	{
		return $stmt->fetchObject();
	}
	
	/**
	 * Old alias to $stmt->closeCursor().
	 * 
	 * @deprecated
	 * @param \PDOStatement $stmt
	 * @return bool
	 */
	public function db_free_result(\PDOStatement $stmt): bool
	{
		return $stmt->closeCursor();
	}
	
	/**
	 * Old alias to getInsertID().
	 * 
	 * @deprecated
	 * @return int
	 */
	public function db_insert_id(): int
	{
		return $this->getInsertID();
	}
	
	/**
	 * Get the list of supported database drivers.
	 * 
	 * @deprecated
	 * @return array
	 */
	public static function getSupportedList(): array
	{
		return array(
			(object)array(
				'db_type' => 'mysql',
				'enable' => extension_loaded('pdo_mysql'),
			),
		);
	}
	
	/**
	 * Get the list of enabled database drivers.
	 * 
	 * @deprecated
	 * @return array
	 */
	public static function getEnableList(): array
	{
		return array_filter(self::getSupportedList(), function($item) {
			return $item->enable;
		});
	}
   
	/**
	 * Get the list of disabled database drivers.
	 * 
	 * @deprecated
	 * @return array
	 */
	public static function getDisableList(): array
	{
		return array_filter(self::getSupportedList(), function($item) {
			return !$item->enable;
		});
	}
	
	/**
	 * Check if the current instance is supported.
	 * 
	 * @deprecated
	 * @return bool
	 */
	public function isSupported(): bool
	{
		return true;
	}
	
	/**
	 * Check if the current instance is connected.
	 * 
	 * @deprecated
	 * @return bool
	 */
	public function isConnected(): bool
	{
		return true;
	}
	
	/**
	 * Close the DB connection.
	 * 
	 * @deprecated
	 * @return bool
	 */
	public function close(): bool
	{
		return true;
	}
	
	/**
	 * Methods related to table creation.
	 * 
	 * @deprecated
	 * @return void
	 */
	public function createTableByXmlFile($filename)
	{
		$output = $this->createTable($filename);
		return $output->toBool();
	}
	public function createTableByXml($xml_doc)
	{
		$output = $this->createTable('', $xml_doc);
		return $output->toBool();
	}
	public function _createTable($xml_doc)
	{
		$output = $this->createTable('', $xml_doc);
		return $output->toBool();
	}
	
	/**
	 * Methods related to the click count cache feature.
	 * 
	 * @deprecated
	 * @return bool
	 */
	public function getCountCache(): bool
	{
		return false;
	}
	public function putCountCache(): bool
	{
		return false;
	}
	public function resetCountCache(): bool
	{
		return false;
	}
	
	/**
	 * Other deprecated methods.
	 */
	public function getParser(): bool
	{
		return false;
	}
	public function _getSlaveConnectionStringIndex(): int
	{
		return 0;
	}
	public function _getConnection(): \PDO
	{
		return $this->getHandle();
	}
	public function _dbInfoExists(): bool
	{
		return true;
	}
}
