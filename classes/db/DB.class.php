<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * - DB parent class
 * - usage of db in XE is via xml
 * - there are 2 types of xml - query xml, schema xml
 * - in case of query xml, DB::executeQuery() method compiles xml file into php code and then execute it
 * - query xml has unique query id, and will be created in module
 * - queryid = module_name.query_name
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db
 * @version 0.1
 */
class DB
{

	static $isSupported = FALSE;

	/**
	 * priority of DBMS
	 * @var array
	 */
	protected static $priority_dbms = array(
		'mysql' => 1,
	);

	/**
	 * master database connection string
	 * @var array
	 */
	protected $master_db = NULL;

	/**
	 * array of slave databases connection strings
	 * @var array
	 */
	protected $slave_db = NULL;
	protected $result = NULL;

	/**
	 * error code (0 means no error)
	 * @var int
	 */
	protected $errno = 0;

	/**
	 * error message
	 * @var string
	 */
	protected $errstr = '';

	/**
	 * query string of latest executed query
	 * @var string
	 */
	protected $query = '';
	protected $connection = '';

	/**
	 * elapsed time of latest executed query
	 * @var int
	 */
	protected $elapsed_time = 0;

	/**
	 * elapsed time of latest executed DB class
	 * @var int
	 */
	protected $elapsed_dbclass_time = 0;

	/**
	 * transaction flag
	 * @var boolean
	 */
	protected $transaction_started = FALSE;
	protected $is_connected = FALSE;

	/**
	 * returns enable list in supported dbms list
	 * will be written by classes/DB/DB***.class.php
	 * @var array
	 */
	protected static $supported_list = array();

	/**
	 * location of query cache
	 * @var string
	 */
	protected $cache_file = 'files/cache/queries/';

	/**
	 * stores database type, e.g. mysql
	 * @var string
	 */
	public $db_type;
	public $db_version = '';

	/**
	 * flag to decide if class prepared statements or not (when supported); can be changed from db.config.info
	 * @var string
	 */
	public $use_prepared_statements;

	/**
	 * leve of transaction
	 * @var unknown
	 */
	protected $transactionNestedLevel = 0;

	/**
	 * returns instance of certain db type
	 * @param string $db_type type of db
	 * @return DB return DB object instance
	 */
	public static function getInstance($db_type = NULL)
	{
		if(!$db_type)
		{
			$db_type = config('db.master.type');
		}
		if(!$db_type && Context::isInstalled())
		{
			Rhymix\Framework\Debug::displayError(lang('msg_db_not_setted'));
			exit;
		}
		if(!strncmp($db_type, 'mysql', 5))
		{
			$db_type = 'mysql';
		}

		if(!isset($GLOBALS['__DB__']))
		{
			$GLOBALS['__DB__'] = array();
		}
		if(!isset($GLOBALS['__DB__'][$db_type]))
		{
			$class_name = 'DB' . ucfirst($db_type);
			$class_file = RX_BASEDIR . "classes/db/$class_name.class.php";
			if(!file_exists($class_file))
			{
				Rhymix\Framework\Debug::displayError(sprintf('DB type "%s" is not supported.', $db_type));
				exit;
			}

			// get a singletone instance of the database driver class
			require_once($class_file);
			$GLOBALS['__DB__'][$db_type] = new $class_name;
			$GLOBALS['__DB__'][$db_type]->db_type = $db_type;
		}

		return $GLOBALS['__DB__'][$db_type];
	}

	/**
	 * returns instance of db
	 * @return DB return DB object instance
	 */
	public static function create()
	{
		return new static();
	}

	/**
	 * constructor
	 * @return void
	 */
	public function __construct()
	{
		$this->cache_file = _XE_PATH_ . $this->cache_file;
	}

	/**
	 * returns list of supported dbms list
	 * this list return by directory list
	 * check by instance can creatable
	 * @return array return supported DBMS list
	 */
	public static function getSupportedList()
	{
		return self::_getSupportedList();
	}

	/**
	 * returns enable list in supported dbms list
	 * this list return by child class
	 * @return array return enable DBMS list in supported dbms list
	 */
	public static function getEnableList()
	{
		if(!self::$supported_list)
		{
			$oDB = new DB();
			self::$supported_list = self::_getSupportedList();
		}

		$enableList = array();
		if(is_array(self::$supported_list))
		{
			foreach(self::$supported_list AS $key => $value)
			{
				if($value->enable)
				{
					$enableList[] = $value;
				}
			}
		}
		return $enableList;
	}

	/**
	 * returns list of disable in supported dbms list
	 * this list return by child class
	 * @return array return disable DBMS list in supported dbms list
	 */
	public static function getDisableList()
	{
		if(!self::$supported_list)
		{
			$oDB = new DB();
			self::$supported_list = self::_getSupportedList();
		}

		$disableList = array();
		if(is_array(self::$supported_list))
		{
			foreach(self::$supported_list AS $key => $value)
			{
				if(!$value->enable)
				{
					$disableList[] = $value;
				}
			}
		}
		return $disableList;
	}

	/**
	 * returns list of supported dbms list
	 * 
	 * @return array return supported DBMS list
	 */
	protected static function _getSupportedList()
	{
		if(self::$supported_list)
		{
			return self::$supported_list;
		}
		
		$get_supported_list = array();
		$db_classes_path = _XE_PATH_ . "classes/db/";
		$filter = "/^DB([^\.]+)\.class\.php/i";
		$supported_list = FileHandler::readDir($db_classes_path, $filter, TRUE);

		// after creating instance of class, check is supported
		foreach ($supported_list as $db_type)
		{
			if (strtolower($db_type) !== 'mysql')
			{
				continue;
			}
			$class_name = sprintf("DB%s%s", strtoupper(substr($db_type, 0, 1)), strtolower(substr($db_type, 1)));
			$class_file = sprintf(_XE_PATH_ . "classes/db/%s.class.php", $class_name);
			if (!file_exists($class_file))
			{
				continue;
			}
			
			require_once($class_file);
			$oDB = new $class_name();
			
			$obj = new stdClass;
			$obj->db_type = $db_type;
			$obj->enable = $oDB->isSupported() ? TRUE : FALSE;
			unset($oDB);
			
			$get_supported_list[] = $obj;
		}

		// sort
		usort($get_supported_list, function($a, $b) {
			$priority_a = isset(self::$priority_dbms[$a->db_type]) ? self::$priority_dbms[$a->db_type] : 0;
			$priority_b = isset(self::$priority_dbms[$b->db_type]) ? self::$priority_dbms[$b->db_type] : 0;
			return $a - $b;
		});

		return self::$supported_list = $get_supported_list;
	}

	/**
	 * Return dbms supportable status
	 * The value is set in the child class
	 * @return boolean true: is supported, false: is not supported
	 */
	public function isSupported()
	{
		return self::$isSupported;
	}

	/**
	 * Return connected status
	 * @param string $type master or slave
	 * @param int $indx key of server list
	 * @return boolean true: connected, false: not connected
	 */
	public function isConnected($type = 'master', $indx = 0)
	{
		if($type == 'master')
		{
			return $this->master_db["is_connected"] ? TRUE : FALSE;
		}
		else
		{
			return $this->slave_db[$indx]["is_connected"] ? TRUE : FALSE;
		}
	}

	/**
	 * start recording log
	 * @param string $query query string
	 * @return void
	 */
	public function actStart($query)
	{
		$this->setError(0, 'success');
		$this->query = $query;
		$this->act_start = microtime(true);
		$this->elapsed_time = 0;
	}

	/**
	 * finish recording log
	 * @return void
	 */
	public function actFinish()
	{
		if(!$this->query)
		{
			return;
		}
		$this->act_finish = microtime(true);
		$elapsed_time = $this->act_finish - $this->act_start;
		$this->elapsed_time = $elapsed_time;
		$GLOBALS['__db_elapsed_time__'] += $elapsed_time;

		$site_module_info = Context::get('site_module_info');
		$log = array();
		$log['query'] = $this->query;
		$log['elapsed_time'] = $elapsed_time;
		$log['connection'] = $this->connection;
		$log['query_id'] = $this->query_id;
		$log['module'] = $site_module_info->module;
		$log['act'] = Context::get('act');
		$log['time'] = date('Y-m-d H:i:s');
		$log['backtrace'] = array();

		if (config('debug.enabled') && ($this->isError() || in_array('queries', config('debug.display_content') ?: array())))
		{
			$bt = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
			foreach($bt as $no => $call)
			{
				if($call['function'] == 'executeQuery' || $call['function'] == 'executeQueryArray')
				{
					$call_no = $no;
					$call_no++;
					$log['called_file'] = $bt[$call_no]['file'];
					$log['called_line'] = $bt[$call_no]['line'];
					$call_no++;
					$log['called_method'] = $bt[$call_no]['class'].$bt[$call_no]['type'].$bt[$call_no]['function'];
					$log['backtrace'] = array_slice($bt, $call_no, 1);
					break;
				}
			}
		}
		else
		{
			$log['called_file'] = $log['called_line'] = $log['called_method'] = null;
			$log['backtrace'] = array();
		}

		// leave error log if an error occured
		if($this->isError())
		{
			$log['result'] = 'error';
			$log['errno'] = $this->errno;
			$log['errstr'] = $this->errstr;
		}
		else
		{
			$log['result'] = 'success';
			$log['errno'] = null;
			$log['errstr'] = null;
		}

		$this->setQueryLog($log);
	}

	/**
	 * set query debug log
	 * @param array $log values set query debug
	 * @return void
	*/
	public function setQueryLog($log)
	{
		Rhymix\Framework\Debug::addQuery($log);
	}

	/**
	 * set error
	 * @param int $errno error code
	 * @param string $errstr error message
	 * @return void
	 */
	public function setError($errno = 0, $errstr = 'success')
	{
		$this->errno = $errno;
		$this->errstr = $errstr;
	}

	/**
	 * Return error status
	 * @return boolean true: error, false: no error
	 */
	public function isError()
	{
		return ($this->errno !== 0);
	}

	/**
	 * Returns object of error info
	 * @return object object of error
	 */
	public function getError()
	{
		$this->errstr = Context::convertEncodingStr($this->errstr);
		return new BaseObject($this->errno, $this->errstr);
	}

	/**
	 * Execute Query that result of the query xml file
	 * This function finds xml file or cache file of $query_id, compiles it and then execute it
	 * @param string $query_id query id (module.queryname)
	 * @param array|object $args arguments for query
	 * @param array $arg_columns column list. if you want get specific colums from executed result, add column list to $arg_columns
	 * @return object result of query
	 */
	public function executeQuery($query_id, $args = NULL, $arg_columns = NULL, $type = NULL)
	{
		static $cache_file = array();

		if(!$query_id)
		{
			return new BaseObject(-1, 'msg_invalid_queryid');
		}
		if(!$this->db_type)
		{
			return new BaseObject(-1, 'msg_db_not_setted');
		}

		$this->actDBClassStart();

		$this->query_id = $query_id;

		if(!isset($cache_file[$query_id]) || !file_exists($cache_file[$query_id]))
		{
			$id_args = explode('.', $query_id);
			if(count($id_args) == 2)
			{
				$target = 'modules';
				$module = $id_args[0];
				$id = $id_args[1];
			}
			elseif(count($id_args) == 3)
			{
				$target = $id_args[0];
				$typeList = array('addons' => 1, 'widgets' => 1);
				if(!isset($typeList[$target]))
				{
					$this->actDBClassFinish();
					return new BaseObject(-1, 'msg_invalid_queryid');
				}
				$module = $id_args[1];
				$id = $id_args[2];
			}
			if(!$target || !$module || !$id)
			{
				$this->actDBClassFinish();
				return new BaseObject(-1, 'msg_invalid_queryid');
			}

			$xml_file = sprintf('%s%s/%s/queries/%s.xml', _XE_PATH_, $target, $module, $id);
			if(!file_exists($xml_file))
			{
				$this->actDBClassFinish();
				return new BaseObject(-1, 'msg_invalid_queryid');
			}

			// look for cache file
			$cache_file[$query_id] = $this->checkQueryCacheFile($query_id, $xml_file);
		}
		$result = $this->_executeQuery($cache_file[$query_id], $args, $query_id, $arg_columns, $type);

		$this->actDBClassFinish();
		// execute query
		return $result;
	}

	/**
	 * Look for query cache file
	 * @param string $query_id query id for finding
	 * @param string $xml_file original xml query file
	 * @return string cache file
	 */
	public function checkQueryCacheFile($query_id, $xml_file)
	{
		// first try finding cache file
		$cache_file = sprintf('%s%s%s.%s.%s.cache.php', _XE_PATH_, $this->cache_file, $query_id, __ZBXE_VERSION__, $this->db_type);

		$cache_time = -1;
		if(file_exists($cache_file))
		{
			$cache_time = filemtime($cache_file);
		}

		// if there is no cache file or is not new, find original xml query file and parse it
		if($cache_time < filemtime($xml_file) || $cache_time < filemtime(_XE_PATH_ . 'classes/db/DB.class.php') || $cache_time < filemtime(_XE_PATH_ . 'classes/xml/XmlQueryParser.class.php'))
		{
			$oParser = new XmlQueryParser();
			$oParser->parse($query_id, $xml_file, $cache_file);
		}

		return $cache_file;
	}

	/**
	 * Execute query and return the result
	 * @param string $cache_file cache file of query
	 * @param array|object $source_args arguments for query
	 * @param string $query_id query id
	 * @param array $arg_columns column list. if you want get specific colums from executed result, add column list to $arg_columns
	 * @return object result of query
	 */
	public function _executeQuery($cache_file, $source_args, $query_id, $arg_columns, $type)
	{
		global $lang;
		
		if(!in_array($type, array('master','slave'))) $type = 'slave';

		if(!file_exists($cache_file))
		{
			return new BaseObject(-1, 'msg_invalid_queryid');
		}

		if (is_object($source_args))
		{
			$args = clone $source_args;
		}
		elseif (is_array($source_args))
		{
			$args = (object)$source_args;
		}
		else
		{
			$args = null;
		}

		$output = include($cache_file);

		if($output instanceof BaseObject && !$output->toBool())
		{
			return $output;
		}
		if(!is_object($output) || !method_exists($output, 'getAction'))
		{
			return new BaseObject(-1, sprintf(lang('msg_failed_to_load_query'), $query_id));
		}

		// execute appropriate query
		switch($output->getAction())
		{
			case 'insert' :
			case 'insert-select' :
				$this->resetCountCache($output->tables);
				$output = $this->_executeInsertAct($output);
				break;
			case 'update' :
				$this->resetCountCache($output->tables);
				$output = $this->_executeUpdateAct($output);
				break;
			case 'delete' :
				$this->resetCountCache($output->tables);
				$output = $this->_executeDeleteAct($output);
				break;
			case 'select' :
				$arg_columns = is_array($arg_columns) ? $arg_columns : array();
				$output->setColumnList($arg_columns);
				$connection = $this->_getConnection($type);
				$output = $this->_executeSelectAct($output, $connection);
				break;
		}

		if($this->isError())
		{
			$output = $this->getError();
		}
		elseif(!($output instanceof BaseObject))
		{
			$output = new BaseObject();
		}
		$output->add('_query', $this->query);
		$output->add('_elapsed_time', sprintf("%0.5f", $this->elapsed_time));

		return $output;
	}

	/**
	 * Returns counter cache data
	 * @param array|string $tables tables to get data
	 * @param string $condition condition to get data
	 * @return int count of cache data
	 */
	public function getCountCache($tables, $condition)
	{
		return FALSE;
	}

	/**
	 * Save counter cache data
	 * @param array|string $tables tables to save data
	 * @param string $condition condition to save data
	 * @param int $count count of cache data to save
	 * @return void
	 */
	public function putCountCache($tables, $condition, $count = 0)
	{
		return FALSE;
	}

	/**
	 * Reset counter cache data
	 * @param array|string $tables tables to reset cache data
	 * @return boolean true: success, false: failed
	 */
	public function resetCountCache($tables)
	{
		return FALSE;
	}

	/**
	 * Drop tables
	 * @param string $table_name
	 * @return void
	 */
	public function dropTable($table_name)
	{
		if(!$table_name)
		{
			return;
		}
		$query = sprintf("drop table %s%s", $this->prefix, $table_name);
		$this->_query($query);
	}

	/**
	 * Return select query string
	 * @param object $query
	 * @param boolean $with_values
	 * @return string
	 */
	public function getSelectSql($query, $with_values = TRUE)
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

		$tableObjects = $query->getTables();
		$index_hint_list = '';
		/*
		foreach($tableObjects as $tableObject)
		{
			if(is_a($tableObject, 'CubridTableWithHint'))
			{
				$index_hint_list .= $tableObject->getIndexHintString() . ', ';
			}
		}
		$index_hint_list = substr($index_hint_list, 0, -2);
		if($index_hint_list != '')
		{
			$index_hint_list = 'USING INDEX ' . $index_hint_list;
		}
		*/

		$groupBy = $query->getGroupByString();
		if($groupBy != '')
		{
			$groupBy = ' GROUP BY ' . $groupBy;
		}

		$having = $query->getHavingString($with_values);
		if($having != '')
		{
			$having = ' HAVING ' . $having;
		}

		$orderBy = $query->getOrderByString();
		if($orderBy != '')
		{
			$orderBy = ' ORDER BY ' . $orderBy;
		}

		$limit = $query->getLimitString();
		if($limit != '')
		{
			$limit = ' LIMIT ' . $limit;
		}

		return "$select $from $where $index_hint_list $groupBy $having $orderBy $limit";
	}

	/**
	 * Given a SELECT statement that uses click count
	 * returns the corresponding update sql string
	 * for databases that don't have click count support built in
	 * (aka all besides CUBRID)
	 *
	 * Function does not check if click count columns exist!
	 * You must call $query->usesClickCount() before using this function
	 *
	 * @param $queryObject
	 */
	public function getClickCountQuery($queryObject)
	{
		$new_update_columns = array();
		$click_count_columns = $queryObject->getClickCountColumns();
		foreach($click_count_columns as $click_count_column)
		{
			$click_count_column_name = $click_count_column->column_name;

			$increase_by_1 = new Argument($click_count_column_name, null);
			$increase_by_1->setColumnOperation('+');
			$increase_by_1->ensureDefaultValue(1);

			$update_expression = new UpdateExpression($click_count_column_name, $increase_by_1);
			$new_update_columns[] = $update_expression;
		}
		$queryObject->columns = $new_update_columns;
		return $queryObject;
	}

	/**
	 * Return delete query string
	 * @param object $query
	 * @param boolean $with_values
	 * @param boolean $with_priority
	 * @return string
	 */
	public function getDeleteSql($query, $with_values = TRUE, $with_priority = FALSE)
	{
		$sql = 'DELETE ';

		$sql .= $with_priority ? $query->getPriority() : '';
		$tables = $query->getTables();

		$sql .= $tables[0]->getAlias();

		$from = $query->getFromString($with_values);
		if($from == '')
		{
			return new BaseObject(-1, "Invalid query");
		}
		$sql .= ' FROM ' . $from;

		$where = $query->getWhereString($with_values);
		if($where != '')
		{
			$sql .= ' WHERE ' . $where;
		}

		return $sql;
	}

	/**
	 * Return update query string
	 * @param object $query
	 * @param boolean $with_values
	 * @param boolean $with_priority
	 * @return string
	 */
	public function getUpdateSql($query, $with_values = TRUE, $with_priority = FALSE)
	{
		$columnsList = $query->getUpdateString($with_values);
		if($columnsList == '')
		{
			return new BaseObject(-1, "Invalid query");
		}

		$tables = $query->getFromString($with_values);
		if($tables == '')
		{
			return new BaseObject(-1, "Invalid query");
		}

		$where = $query->getWhereString($with_values);
		if($where != '')
		{
			$where = ' WHERE ' . $where;
		}

		$priority = $with_priority ? $query->getPriority() : '';

		return "UPDATE $priority $tables SET $columnsList " . $where;
	}

	/**
	 * Return insert query string
	 * @param object $query
	 * @param boolean $with_values
	 * @param boolean $with_priority
	 * @return string
	 */
	public function getInsertSql($query, $with_values = TRUE, $with_priority = FALSE)
	{
		$tableName = $query->getFirstTableName();
		$values = $query->getInsertString($with_values);
		$priority = $with_priority ? $query->getPriority() : '';

		return "INSERT $priority INTO $tableName \n $values";
	}

	/**
	 * Return index from slave server list
	 * @return int
	 */
	public function _getSlaveConnectionStringIndex()
	{
		$max = count($this->slave_db);
		$indx = rand(0, $max - 1);
		return $indx;
	}

	/**
	 * Return connection resource
	 * @param string $type use 'master' or 'slave'. default value is 'master'
	 * @param int $indx if indx value is NULL, return rand number in slave server list
	 * @return resource
	 */
	public function _getConnection($type = 'master', $indx = NULL)
	{
		if($type == 'master' || $this->transactionNestedLevel)
		{
			if(!$this->master_db['is_connected'])
			{
				$this->_connect($type);
			}
			$this->connection = 'master (' . $this->master_db['host'] . ')';
			return $this->master_db["resource"];
		}

		if($indx === NULL)
		{
			$indx = $this->_getSlaveConnectionStringIndex($type);
		}

		if($this->slave_db[$indx]['host'] == $this->master_db['host'] && $this->slave_db[$indx]['port'] == $this->master_db['port'])
		{
			if(!$this->master_db['is_connected'])
			{
				$this->_connect($type);
			}
			$this->connection = 'master (' . $this->master_db['host'] . ')';
			return $this->master_db["resource"];
		}
		
		if(!$this->slave_db[$indx]['is_connected'])
		{
			$this->_connect($type, $indx);
		}
		$this->connection = 'slave (' . $this->slave_db[$indx]['host'] . ')';
		return $this->slave_db[$indx]["resource"];
	}

	/**
	 * check db information exists
	 * @return boolean
	 */
	public function _dbInfoExists()
	{
		return ($this->master_db && count($this->slave_db));
	}

	/**
	 * DB disconnection
	 * 
	 * @param resource $connection
	 * @return void
	 */
	protected function _close($connection)
	{

	}

	/**
	 * DB disconnection
	 * @param string $type 'master' or 'slave'
	 * @param int $indx number in slave dbms server list
	 * @return void
	 */
	public function close($type = 'master', $indx = 0)
	{
		if(!$this->isConnected($type, $indx))
		{
			return;
		}

		if($type == 'master')
		{
			$connection = &$this->master_db;
		}
		else
		{
			$connection = &$this->slave_db[$indx];
		}

		$this->commit();
		$this->_close($connection["resource"]);

		$connection["is_connected"] = FALSE;
	}

	/**
	 * DB transaction start
	 * this method is protected
	 * @return boolean
	 */
	protected function _begin($transactionLevel = 0)
	{
		return TRUE;
	}

	/**
	 * DB transaction start
	 * @return void
	 */
	public function begin()
	{
		if(!$this->isConnected())
		{
			return;
		}

		if($this->_begin($this->transactionNestedLevel))
		{
			$this->transaction_started = TRUE;
			$this->transactionNestedLevel++;
		}
	}

	/**
	 * DB transaction rollback
	 * this method is protected
	 * @return boolean
	 */
	protected function _rollback($transactionLevel = 0)
	{
		return TRUE;
	}

	/**
	 * DB transaction rollback
	 * @return void
	 */
	public function rollback()
	{
		if(!$this->isConnected() || !$this->transaction_started)
		{
			return;
		}
		if($this->_rollback($this->transactionNestedLevel))
		{
			$this->transactionNestedLevel--;

			if(!$this->transactionNestedLevel)
			{
				$this->transaction_started = FALSE;
			}
		}
	}

	/**
	 * DB transaction commit
	 * this method is protected
	 * @return boolean
	 */
	protected function _commit()
	{
		return TRUE;
	}

	/**
	 * DB transaction commit
	 * @param boolean $force regardless transaction start status or connect status, forced to commit
	 * @return void
	 */
	public function commit($force = FALSE)
	{
		if(!$force && (!$this->isConnected() || !$this->transaction_started))
		{
			return;
		}
		if($this->transactionNestedLevel == 1 && $this->_commit())
		{
			$this->transaction_started = FALSE;
			$this->transactionNestedLevel = 0;
		}
		else
		{
			$this->transactionNestedLevel--;
		}
	}

	/**
	 * Execute the query
	 * this method is protected
	 * @param string $query
	 * @param resource $connection
	 * @return void
	 */
	protected function __query($query, $connection)
	{

	}

	/**
	 * Execute the query
	 * 
	 * @param string $query
	 * @param resource $connection
	 * @return resource
	 */
	public function _query($query, $connection = NULL)
	{
		if($connection == NULL)
		{
			$connection = $this->_getConnection('master');
		}
		// Notify to start a query execution
		$this->actStart($query);

		// Run the query statement
		$result = $this->__query($query, $connection);

		// Notify to complete a query execution
		$this->actFinish();
		// Return result
		return $result;
	}

	/**
	 * DB info settings
	 * this method is protected
	 * @return void
	 */
	protected function _setDBInfo()
	{
		$db_info = config('db');
		$this->master_db = $db_info['master'];
		$this->slave_db = $db_info ? array_values($db_info) : null;
		$this->prefix = $this->master_db['prefix'];
		$this->use_prepared_statements = config('use_prepared_statements');
	}

	/**
	 * DB Connect
	 * this method is protected
	 * @param array $connection
	 * @return void
	 */
	protected function __connect($connection)
	{

	}

	/**
	 * If have a task after connection, add a taks in this method
	 * this method is protected
	 * @param resource $connection
	 * @return void
	 */
	protected function _afterConnect($connection)
	{

	}

	/**
	 * DB Connect
	 * this method is protected
	 * @param string $type 'master' or 'slave'
	 * @param int $indx number in slave dbms server list
	 * @return void
	 */
	protected function _connect($type = 'master', $indx = 0)
	{
		if($this->isConnected($type, $indx))
		{
			return;
		}

		// Ignore if no DB information exists
		if(!$this->_dbInfoExists())
		{
			return;
		}

		if($type == 'master')
		{
			$connection = &$this->master_db;
		}
		else
		{
			$connection = &$this->slave_db[$indx];
		}
		
		$result = $this->__connect($connection);
		if($result === NULL || $result === FALSE)
		{
			$connection["is_connected"] = FALSE;
			return;
		}

		// Check connections
		$connection["resource"] = $result;
		$connection["is_connected"] = TRUE;

		// Save connection info for db logs
		$this->connection = $type . ' (' . $connection['host'] . ')';

		// regist $this->close callback
		register_shutdown_function(array($this, "close"));

		$this->_afterConnect($result);
	}

	/**
	 * Start recording DBClass log
	 * @return void
	 */
	public function actDBClassStart()
	{
		$this->setError(0, 'success');
		$this->act_dbclass_start = microtime(true);
		$this->elapsed_dbclass_time = 0;
	}

	/**
	 * Finish recording DBClass log
	 * @return void
	 */
	public function actDBClassFinish()
	{
		if(!$this->query)
		{
			return;
		}
		$this->act_dbclass_finish = microtime(true);
		$elapsed_dbclass_time = $this->act_dbclass_finish - $this->act_dbclass_start;
		$this->elapsed_dbclass_time = $elapsed_dbclass_time;
		$GLOBALS['__dbclass_elapsed_time__'] += $elapsed_dbclass_time;
	}

	/**
	 * Returns a database specific parser instance
	 * used for escaping expressions and table/column identifiers
	 *
	 * Requires an implementation of the DB class (won't work if database is not set)
	 * this method is singleton
	 *
	 * @param boolean $force force load DBParser instance
	 * @return DBParser
	 */
	public function getParser($force = FALSE)
	{
		static $dbParser = NULL;
		if(!$dbParser || $force)
		{
			$oDB = DB::getInstance();
			$dbParser = $oDB->getParser();
		}

		return $dbParser;
	}

	/**
	 * Get the number of rows affected by the last query
	 * @return int
	 */
	public function getAffectedRows()
	{
		return -1;
	}

	/**
	 * Get the ID generated in the last query
	 * @return int
	 */
	public function getInsertID()
	{
		return 0;
	}
}
/* End of file DB.class.php */
/* Location: ./classes/db/DB.class.php */
