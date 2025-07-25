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
	protected $_version = '';
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
	protected $_debug_queries = false;
	protected $_debug_comment = false;
	protected $_debug_full_stack = false;

	/**
	 * Transaction level.
	 */
	protected $_transaction_level = 0;

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

		// Cache the debug comment setting.
		$this->_debug_queries = in_array('queries', Config::get('debug.display_content') ?: []);
		$this->_debug_comment = !!config('debug.query_comment');
		$this->_debug_full_stack = !!Config::get('debug.query_full_stack');

		// Connect to the DB.
		$this->connect($config);
	}

	/**
	 * Connect to the database.
	 *
	 * @param array $config
	 * @return void
	 */
	public function connect(array $config): void
	{
		// Assemble the DSN and default options.
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

		// Preload the statement helper class.
		class_exists('\Rhymix\Framework\Helpers\DBStmtHelper');

		try
		{
			$this->_handle = new Helpers\DBHelper($dsn, $config['user'], $config['pass'], $options);
			$this->_handle->setType($this->_type);
		}
		catch (\PDOException $e)
		{
			throw new Exceptions\DBError($e->getMessage());
		}
	}

	/**
	 * Disconnect from the database.
	 *
	 * @return void
	 */
	public function disconnect(): void
	{
		$this->_handle = null;
		unset(self::$_instances[$this->_type]);
	}

	/**
	 * Get the PDO handle for direct manipulation.
	 *
	 * @return ?Helpers\DBHelper
	 */
	public function getHandle(): ?Helpers\DBHelper
	{
		return $this->_handle;
	}

	/**
	 * Create a prepared statement.
	 *
	 * Table names in the FROM or JOIN clause of the statement are
	 * automatically prefixed with the configured prefix.
	 *
	 * Note that this method will throw an exception (DBError) on error,
	 * instead of returning false or null as legacy functions do.
	 *
	 * @param string $statement
	 * @param array $driver_options
	 * @return Helpers\DBStmtHelper
	 */
	public function prepare(string $statement, array $driver_options = []): Helpers\DBStmtHelper
	{
		// Add table prefixes to the query string.
		$statement = $this->addPrefixes($statement);

		// Add the debug comment.
		if ($this->_debug_comment)
		{
			$statement .= "\n" . sprintf('/* prepare() %s */', \RX_CLIENT_IP);
		}

		// Create and return a prepared statement.
		$this->_last_stmt = null;
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
	 * @return ?Helpers\DBStmtHelper
	 */
	public function query(string $query_string, ...$args): ?Helpers\DBStmtHelper
	{
		// If query parameters are given as a single array, unpack it.
		if (count($args) === 1 && is_array($args[0]))
		{
			$args = $args[0];
		}

		// Add table prefixes to the query string.
		$query_string = $this->addPrefixes($query_string);

		// Add the debug comment.
		if ($this->_debug_comment)
		{
			$query_string .= "\n" . sprintf('/* query() %s */', \RX_CLIENT_IP);
		}

		// Execute either a prepared statement or a regular query depending on whether there are arguments.
		$this->_last_stmt = null;
		if (count($args))
		{
			$this->_last_stmt = $this->_handle->prepare($query_string);
			$this->_last_stmt->execute($args);
		}
		else
		{
			$this->_last_stmt = $this->_handle->query($query_string);
		}
		return $this->_last_stmt ?: null;
	}

	/**
	 * Execute an XML-defined query.
	 *
	 * @param string $query_id
	 * @param array|object $args
	 * @param array $columns
	 * @param string $result_type
	 * @param string $result_class
	 * @return Helpers\DBResultHelper
	 */
	public function executeQuery(string $query_id, $args = [], array $column_list = [], string $result_type = 'auto', string $result_class = ''): Helpers\DBResultHelper
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
		$column_list = array_values($column_list);

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
				$output->data = null;
				$this->_query_id = '';
				$this->_total_time += (microtime(true) - $start_time);
				return $output;
			}

			// Do not execute the main query if the current page is out of bounds.
			if ($output->page > $output->total_page)
			{
				$output->add('_query', $query_string);
				$output->add('_elapsed_time', '0.00000');
				$output->data = ($result_type === 'array') ? [] : null;
				$this->_query_id = '';
				$this->_total_time += (microtime(true) - $start_time);
				return $output;
			}
		}
		else
		{
			$output = new Helpers\DBResultHelper;
		}

		// Prepare and execute the main query.
		try
		{
			if ($this->_debug_comment)
			{
				$query_string .= "\n" . sprintf('/* %s %s */', $query_id, \RX_CLIENT_IP);
			}

			$this->_query_id = $query_id;
			$this->_last_stmt = null;
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
				$output->data = null;
				$this->_query_id = '';
				$this->_total_time += (microtime(true) - $start_time);
				return $output;
			}
			elseif ($query->type === 'SELECT')
			{
				$result = $this->fetch($this->_last_stmt, $last_index, $result_type, $result_class);
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
			$output->data = null;
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
	 * @return Helpers\DBResultHelper
	 */
	protected function _executeCountQuery(string $query_id, Parsers\DBQuery\Query $query, array $args, int &$last_index): Helpers\DBResultHelper
	{
		// Get the COUNT(*) query string and parameters.
		try
		{
			$query_string = $query->getQueryString($this->_prefix, $args, [], 1);
			$query_params = $query->getQueryParams();
		}
		catch (Exceptions\QueryError $e)
		{
			return $this->setError(-1, $e->getMessage());
		}

		// Prepare and execute the query.
		try
		{
			if ($this->_debug_comment)
			{
				$query_string .= "\n" . sprintf('/* %s %s */', $query_id, \RX_CLIENT_IP);
			}

			$this->_last_stmt = null;
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
				$output->add('_count', $query_string);
				return $output;
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
			$output->add('_count', $query_string);
			return $output;
		}
		catch (\PDOException $e)
		{
			$output = $this->setError(-1, $e->getMessage());
			$output->add('_count', $query_string);
			return $output;
		}

		// Collect various counts used in the page calculation.
		$list_count = $query->navigation->list_count->getValue($args)[0];
		$page_count = $query->navigation->page_count->getValue($args)[0];
		$page = $query->navigation->page->getValue($args)[0];
		$total_count = intval($count);
		$total_page = max(1, intval(ceil($total_count / $list_count)));
		$last_index = $total_count - (($page - 1) * $list_count);
		$page_handler = new \PageHandler($total_count, $total_page, $page, $page_count ?: 10);

		// Compose the output object.
		$output = new Helpers\DBResultHelper;
		$output->add('_count', $query_string);
		$output->total_count = $total_count;
		$output->total_page = $total_page;
		$output->page = $page;
		$output->data = null;
		$output->page_navigation = $page_handler;
		return $output;
	}

	/**
	 * Fetch results from a query.
	 *
	 * @param \PDOStatement $stmt
	 * @param int $last_index
	 * @param string $result_type
	 * @param string $result_class
	 * @return array|object|null|\PDOStatement
	 */
	public function fetch($stmt, int $last_index = 0, string $result_type = 'auto', string $result_class = '')
	{
		if (!($stmt instanceof \PDOStatement))
		{
			return null;
		}
		if ($result_type === 'raw')
		{
			return $stmt;
		}

		try
		{
			$result = array();
			$index = $last_index;
			$step = $last_index !== 0 ? -1 : 1;
			$count = 0;
			$result_class = ($result_class && $result_class !== 'master') ? $result_class : 'stdClass';
			if (!class_exists($result_class))
			{
				throw new Exceptions\DBError('Class not found: ' . $result_class);
			}
			while ($row = $stmt->fetchObject($result_class))
			{
				$result[$index] = $row;
				$index += $step;
				$count++;
				if ($count === 10000 && $this->_query_id !== '')
				{
					trigger_error('XML query ' . $this->_query_id . ' returned 10000 rows or more', E_USER_WARNING);
				}
			}

			$stmt->closeCursor();
		}
		catch (\PDOException $e)
		{
			throw new Exceptions\DBError($e->getMessage());
		}

		if ($result_type === 'auto' && $last_index === 0 && count($result) <= 1)
		{
			return isset($result[0]) ? $result[0] : null;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Alias to begin().
	 *
	 * @return int
	 */
	public function beginTransaction(): int
	{
		return $this->begin();
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

			if (Debug::isEnabledForCurrentUser())
			{
				Debug::addQuery($this->getQueryLog('START TRANSACTION', 0));
			}
		}
		else
		{
			$this->_handle->exec(sprintf('SAVEPOINT `%s%s%d`', $this->_prefix, 'savepoint', $this->_transaction_level));
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

			if (Debug::isEnabledForCurrentUser())
			{
				Debug::addQuery($this->getQueryLog('ROLLBACK', 0));
			}
		}
		else
		{
			$this->_handle->exec(sprintf('ROLLBACK TO SAVEPOINT `%s%s%d`', $this->_prefix, 'savepoint', $this->_transaction_level - 1));
		}

		if ($this->_transaction_level > 0)
		{
			$this->_transaction_level--;
		}
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

			if (Debug::isEnabledForCurrentUser())
			{
				Debug::addQuery($this->getQueryLog('COMMIT', 0));
			}
		}
		else
		{
			if (Debug::isEnabledForCurrentUser())
			{
				Debug::addQuery($this->getQueryLog('NESTED COMMIT IGNORED BY RHYMIX', 0));
			}
		}

		if ($this->_transaction_level > 0)
		{
			$this->_transaction_level--;
		}
		return $this->_transaction_level;
	}

	/**
	 * Get the current transaction level.
	 *
	 * @return int
	 */
	public function getTransactionLevel(): int
	{
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
	 *
	 * @return int
	 */
	public function getNextSequence(): int
	{
		$this->_handle->exec(sprintf('INSERT INTO `%s` (seq) VALUES (NULL)', $this->addQuotes($this->_prefix . 'sequence')));
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
		return (int)$sequence;
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
		$result = $this->fetch($stmt);
		return $result ? true : false;
	}

	/**
	 * Create a table.
	 *
	 * @param string $filename
	 * @param string $content
	 * @return Helpers\DBResultHelper
	 */
	public function createTable(string $filename = '', string $content = ''): Helpers\DBResultHelper
	{
		// Get the table definition from DBTableParser.
		$table = Parsers\DBTableParser::loadXML($filename, $content);
		if (!$table)
		{
			return $this->setError(-1, 'Failed to load table schema file');
		}
		if ($table->deleted)
		{
			return new Helpers\DBResultHelper(-1, 'Table is marked as deleted');
		}

		// Generate the CREATE TABLE query and execute it.
		$query_string = $table->getCreateQuery($this->_prefix, $this->_charset, $this->_engine);
		$result = $this->_handle->exec($query_string);
		return $result ? new Helpers\DBResultHelper : $this->getError();
	}

	/**
	 * Drop a table.
	 *
	 * @param string $table_name
	 * @return Helpers\DBResultHelper
	 */
	public function dropTable(string $table_name): Helpers\DBResultHelper
	{
		$stmt = $this->_handle->exec(sprintf("DROP TABLE `%s`", $this->addQuotes($this->_prefix . $table_name)));
		return $stmt ? new Helpers\DBResultHelper : $this->getError();
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
		$result = $this->fetch($stmt);
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
	 * @return Helpers\DBResultHelper
	 */
	public function addColumn(string $table_name, string $column_name, string $type = 'number', $size = null, $default = null, $notnull = false, $after_column = null): Helpers\DBResultHelper
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
		return $result ? new Helpers\DBResultHelper : $this->getError();
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
	 * @param string $new_charset
	 * @return Helpers\DBResultHelper
	 */
	public function modifyColumn(string $table_name, string $column_name, string $type = 'number', $size = null, $default = null, $notnull = false, $after_column = null, $new_name = null, $new_charset = null): Helpers\DBResultHelper
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

		// Add the character set information.
		if (isset($new_charset))
		{
			$new_collation = preg_match('/^utf8/i', $new_charset) ? ($new_charset . '_unicode_ci') : ($new_charset . '_general_ci');
			$query .= ' CHARACTER SET ' . $new_charset . ' COLLATE ' . $new_collation;
		}

		// Add the NOT NULL constraint.
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
		return $result ? new Helpers\DBResultHelper : $this->getError();
	}

	/**
	 * Drop a column.
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @return Helpers\DBResultHelper
	 */
	public function dropColumn(string $table_name, string $column_name): Helpers\DBResultHelper
	{
		$result = $this->_handle->exec(sprintf("ALTER TABLE `%s` DROP `%s`", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($column_name)));
		return $result ? new Helpers\DBResultHelper : $this->getError();
	}

	/**
	 * Get column information.
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @return ?object
	 */
	public function getColumnInfo(string $table_name, string $column_name): ?object
	{
		// If column information is not found, return null.
		$stmt = $this->_handle->query(sprintf("SHOW FIELDS FROM `%s` WHERE Field = '%s'", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($column_name)));
		$column_info = $this->fetch($stmt);
		if (!$column_info)
		{
			return null;
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
		$result = $this->fetch($stmt);
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
	 * @return Helpers\DBResultHelper
	 */
	public function addIndex(string $table_name, string $index_name, $columns, $type = '', $options = ''): Helpers\DBResultHelper
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
		return $result ? new Helpers\DBResultHelper : $this->getError();
	}

	/**
	 * Drop an index.
	 *
	 * @param string $table_name
	 * @param string $index_name
	 * @return Helpers\DBResultHelper
	 */
	public function dropIndex(string $table_name, string $index_name): Helpers\DBResultHelper
	{
		$result = $this->_handle->exec(sprintf("ALTER TABLE `%s` DROP INDEX `%s`", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($index_name)));
		return $result ? new Helpers\DBResultHelper : $this->getError();
	}

	/**
	 * Get index information.
	 *
	 * @param string $table_name
	 * @param string $index_name
	 * @return ?object
	 */
	public function getIndexInfo(string $table_name, string $index_name): ?object
	{
		// If the index is not found, return null.
		$stmt = $this->_handle->query(sprintf("SHOW INDEX FROM `%s` WHERE Key_name = '%s'", $this->addQuotes($this->_prefix . $table_name), $this->addQuotes($index_name)));
		$index_info = $this->fetch($stmt, 0, 'array');
		if (!$index_info)
		{
			return null;
		}

		// Get the list of columns included in the index.
		$is_unique = false;
		$columns = [];
		foreach ($index_info as $column)
		{
			if (!$column->Non_unique)
			{
				$is_unique = true;
			}
			$columns[] = (object)[
				'name' => $column->Column_name,
				'size' => $column->Sub_part ? intval($column->Sub_part) : null,
				'cardinality' => $column->Cardinality ? intval($column->Cardinality) : null,
			];
		}

		// Return the result as an object.
		return (object)array(
			'name' => $column->Key_name,
			'table' => $column->Table,
			'is_unique' => $is_unique,
			'columns' => $columns,
		);
	}

	/**
	 * Add table prefixes to a query string.
	 *
	 * @param string $query_string
	 * @return string
	 */
	public function addPrefixes(string $query_string): string
	{
		// Return early if no prefix is set.
		if (!$this->_prefix)
		{
			return $query_string;
		}

		// Generate a list of common table expressions (CTEs) to exclude from prefixing.
		if (preg_match_all('/\bWITH(?:\s+RECURSIVE)?\s+`?(\w+)`?\s+AS\b/', $query_string, $matches))
		{
			$exceptions = $matches[1];
		}
		else
		{
			$exceptions = [];
		}

		// Add prefixes to all other table names in the query string.
		return preg_replace_callback('/\b((?:DELETE\s+)?FROM|JOIN|INTO|(?<!KEY\s)UPDATE)(?i)\s+((?:`?\w+`?)(?:\s+AS\s+`?\w+`?)?(?:\s*,\s*(?:`?\w+\`?)(?:\s+AS\s+`?\w+`?)?)*)/', function($m) use($exceptions) {
			$type = strtoupper($m[1]);
			$tables = array_map(function($str) use($type, $exceptions) {
				return preg_replace_callback('/`?(\w+)`?(?:\s+AS\s+`?(\w+)`?)?/i', function($m) use($type, $exceptions) {
					if (count($exceptions) && in_array($m[1], $exceptions))
					{
						return isset($m[2]) ? sprintf('`%s` AS `%s`',  $m[1], $m[2]) : sprintf('`%s`', $m[1]);
					}
					elseif ($type === 'FROM' || $type === 'JOIN')
					{
						return isset($m[2]) ? sprintf('`%s%s` AS `%s`', $this->_prefix, $m[1], $m[2]) : sprintf('`%s%s` AS `%s`', $this->_prefix, $m[1], $m[1]);
					}
					else
					{
						return isset($m[2]) ? sprintf('`%s%s` AS `%s`', $this->_prefix, $m[1], $m[2]) : sprintf('`%s%s`', $this->_prefix, $m[1]);
					}
				}, trim($str));
			}, explode(',', $m[2]));
			return $m[1] . ' ' . implode(', ', $tables);
		}, $query_string);
	}

	/**
	 * Escape a string according to current DB settings.
	 *
	 * @param int|float|string $str
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
			return preg_replace("/^'(.*)'$/s", '$1', $this->_handle->quote(strval($str)));
		}
	}

	/**
	 * Find out the best supported character set.
	 *
	 * @return string
	 */
	public function getBestSupportedCharset(): string
	{
		$output = $this->fetch($this->_handle->query("SHOW CHARACTER SET LIKE 'utf8%'"), 1);
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
	 * @return Helpers\DBResultHelper
	 */
	public function getError(): Helpers\DBResultHelper
	{
		return new Helpers\DBResultHelper($this->_errno, $this->_errstr);
	}

	/**
	 * Set error information to instance properties.
	 *
	 * @param int $errno
	 * @param string $errstr
	 * @return Helpers\DBResultHelper
	 */
	public function setError(int $errno = 0, string $errstr = 'success'): Helpers\DBResultHelper
	{
		$this->_errno = $errno;
		$this->_errstr = $errstr;
		$output = new Helpers\DBResultHelper($errno, $errstr);
		return $output;
	}

	/**
	 * Clear error information.
	 *
	 * @return void
	 */
	public function clearError(): void
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
		// Compose the basic structure of the log entry.
		$result = array(
			'query' => preg_replace('!\n/\* .+ \*/$!s', '', $query),
			'query_id' => $this->_query_id,
			'connection' => $this->_type,
			'elapsed_time' => sprintf('%0.5f', $elapsed_time),
			'result' => $this->_errno ? 'error' : 'success',
			'errno' => $this->_errno,
			'errstr' => $this->_errstr,
			'called_file' => null,
			'called_line' => null,
			'called_method' => null,
			'backtrace' => array(),
		);

		// Add debug information if enabled.
		if ($this->_errno || $this->_debug_queries)
		{
			$backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
			foreach ($backtrace as $no => $call)
			{
				if (!preg_match('#/common/framework/(?:DB|helpers)\b#', $call['file']) && $call['file'] !== \RX_BASEDIR . 'common/legacy.php')
				{
					$result['called_file'] = $backtrace[$no]['file'];
					$result['called_line'] = $backtrace[$no]['line'];
					$no++;
					if (isset($backtrace[$no]))
					{
						$result['called_method'] = ($backtrace[$no]['class'] ?? '') . ($backtrace[$no]['type'] ?? '') . ($backtrace[$no]['function'] ?? '');
						$result['backtrace'] = $this->_debug_full_stack ? array_slice($backtrace, $no - 1) : [];
					}
					else
					{
						$result['called_method'] = '';
						$result['backtrace'] = [];
					}
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
	public function setQueryLog(array $log): void
	{
		Debug::addQuery($log);
	}

	/**
	 * Add elapsed time.
	 *
	 * @param float $elapsed_time
	 * @return void
	 */
	public function addElapsedTime(float $elapsed_time): void
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
	 * Enable or disable debug comments.
	 *
	 * @param bool $enabled
	 * @return void
	 */
	public function setDebugComment(bool $enabled): void
	{
		$this->_debug_comment = $enabled;
	}

	/**
	 * Magic method to support some read-only properties for backward compatibility.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key)
	{
		switch ($key)
		{
			case 'db_type': return $this->_handle->getAttribute(\PDO::ATTR_DRIVER_NAME);
			case 'db_version': return $this->_handle->getAttribute(\PDO::ATTR_SERVER_VERSION);
			case 'prefix': return $this->_prefix;
			case 'use_prepared_statements': return true;
			default: return null;
		}
	}

	/**
	 * ========================== DEPRECATED METHODS ==========================
	 * ==================== KEPT FOR COMPATIBILITY WITH XE ====================
	 */

	/**
	 * Execute a literal query string.
	 *
	 * Use query() instead, or call methods directly on the handle.
	 *
	 * @deprecated
	 * @param string $query_string
	 * @return ?Helpers\DBStmtHelper
	 */
	public function _query($query_string): ?Helpers\DBStmtHelper
	{
		trigger_error('Custom query using unsafe method', \E_USER_WARNING);
		if ($this->_debug_comment)
		{
			$query_string .= "\n" . sprintf('/* _query() %s */', \RX_CLIENT_IP);
		}

		$this->_last_stmt = null;
		$this->_last_stmt = $this->_handle->query(strval($query_string));
		return $this->_last_stmt ?: null;
	}

	/**
	 * Fetch results from a query.
	 *
	 * Use query() and fetch() instead.
	 *
	 * @deprecated
	 * @param \PDOStatement $stmt
	 * @param int $last_index
	 * @return mixed
	 */
	public function _fetch($stmt, int $last_index = 0)
	{
		trigger_error('Custom query using unsafe method', \E_USER_WARNING);
		return $this->fetch($stmt, $last_index);
	}

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
		return $this->_handle ? true : false;
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
	 * @return bool
	 */
	public function createTableByXmlFile($filename): bool
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
	public function _getConnection(): ?Helpers\DBHelper
	{
		return $this->getHandle();
	}
	public function _dbInfoExists(): bool
	{
		return true;
	}
}
