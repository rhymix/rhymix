<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Class to use MySQL DBMS
 * mysql handling class
 *
 * Does not use prepared statements, since mysql driver does not support them
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db
 * @version 0.1
 */
class DBMysql extends DB
{

	/**
	 * prefix of a tablename (One or more XEs can be installed in a single DB)
	 * @var string
	 */
	var $prefix = 'xe_'; // / <
	var $comment_syntax = '/* %s */';

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
	 * Constructor
	 * @return void
	 */
	function DBMysql()
	{
		$this->_setDBInfo();
		$this->_connect();
	}

	/**
	 * Create an instance of this class
	 * @return DBMysql return DBMysql object instance
	 */
	function create()
	{
		return new DBMysql;
	}

	/**
	 * DB Connect
	 * this method is private
	 * @param array $connection connection's value is db_hostname, db_port, db_database, db_userid, db_password
	 * @return resource
	 */
	function __connect($connection)
	{
		// Ignore if no DB information exists
		if(strpos($connection["db_hostname"], ':') === false && $connection["db_port"])
		{
			$connection["db_hostname"] .= ':' . $connection["db_port"];
		}

		// Attempt to connect
		$result = @mysql_connect($connection["db_hostname"], $connection["db_userid"], $connection["db_password"]);
		if(!$result)
		{
			exit('XE cannot connect to DB.');
		}

		if(mysql_error())
		{
			$this->setError(mysql_errno(), mysql_error());
			return;
		}
		// Error appears if the version is lower than 4.1
		if(version_compare(mysql_get_server_info($result), '4.1', '<'))
		{
			$this->setError(-1, 'XE cannot be installed under the version of mysql 4.1. Current mysql version is ' . mysql_get_server_info());
			return;
		}
		// select db
		@mysql_select_db($connection["db_database"], $result);
		if(mysql_error())
		{
			$this->setError(mysql_errno(), mysql_error());
			return;
		}

		return $result;
	}

	/**
	 * If have a task after connection, add a taks in this method
	 * this method is private
	 * @param resource $connection
	 * @return void
	 */
	function _afterConnect($connection)
	{
		// Set utf8 if a database is MySQL
		$this->_query("set names 'utf8'", $connection);
	}

	/**
	 * DB disconnection
	 * this method is private
	 * @param resource $connection
	 * @return void
	 */
	function _close($connection)
	{
		@mysql_close($connection);
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
			$string = @mysql_real_escape_string($string);
		}
		return $string;
	}

	/**
	 * DB transaction start
	 * this method is private
	 * @return boolean
	 */
	function _begin()
	{
		return true;
	}

	/**
	 * DB transaction rollback
	 * this method is private
	 * @return boolean
	 */
	function _rollback()
	{
		return true;
	}

	/**
	 * DB transaction commit
	 * this method is private
	 * @return boolean
	 */
	function _commit()
	{
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
		if(!$connection)
		{
			exit('XE cannot handle DB connection.');
		}
		// Run the query statement
		$result = mysql_query($query, $connection);
		// Error Check
		if(mysql_error($connection))
		{
			$this->setError(mysql_errno($connection), mysql_error($connection));
		}
		// Return result
		return $result;
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
		$this->db_free_result($result);
		return $output;
	}

	/**
	 * Return the sequence value incremented by 1
	 * Auto_increment column only used in the sequence table
	 * @return int
	 */
	function getNextSequence()
	{
		$query = sprintf("insert into `%ssequence` (seq) values ('0')", $this->prefix);
		$this->_query($query);
		$sequence = $this->db_insert_id();
		if($sequence % 10000 == 0)
		{
			$query = sprintf("delete from  `%ssequence` where seq < %d", $this->prefix, $sequence);
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
		$query = sprintf("select password('%s') as password, old_password('%s') as old_password", $this->addQuotes($password), $this->addQuotes($password));
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
		$query = sprintf("show tables like '%s%s'", $this->prefix, $this->addQuotes($target_name));
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
	function addColumn($table_name, $column_name, $type = 'number', $size = '', $default = null, $notnull = false)
	{
		$type = $this->column_type[$type];
		if(strtoupper($type) == 'INTEGER')
		{
			$size = '';
		}

		$query = sprintf("alter table `%s%s` add `%s` ", $this->prefix, $table_name, $column_name);
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
			$query .= sprintf(" default '%s' ", $default);
		}
		if($notnull)
		{
			$query .= " not null ";
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
		$query = sprintf("alter table `%s%s` drop `%s` ", $this->prefix, $table_name, $column_name);
		$this->_query($query);
	}

	/**
	 * Check column exist status of the table
	 * @param string $table_name table name
	 * @param string $column_name column name
	 * @return boolean
	 */
	function isColumnExists($table_name, $column_name)
	{
		$query = sprintf("show fields from `%s%s`", $this->prefix, $table_name);
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

		$query = sprintf("alter table `%s%s` add %s index `%s` (%s);", $this->prefix, $table_name, $is_unique ? 'unique' : '', $index_name, implode(',', $target_columns));
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
		$query = sprintf("alter table `%s%s` drop index `%s`", $this->prefix, $table_name, $index_name);
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
		//$query = sprintf("show indexes from %s%s where key_name = '%s' ", $this->prefix, $table_name, $index_name);
		$query = sprintf("show indexes from `%s%s`", $this->prefix, $table_name);
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
		// xml parsing
		$oXml = new XmlParser();
		$xml_obj = $oXml->parse($xml_doc);
		// Create a table schema
		$table_name = $xml_obj->table->attrs->name;
		if($this->isTableExists($table_name))
		{
			return;
		}
		$table_name = $this->prefix . $table_name;

		if(!is_array($xml_obj->table->column))
		{
			$columns[] = $xml_obj->table->column;
		}
		else
		{
			$columns = $xml_obj->table->column;
		}

		$primary_list = array();
		$unique_list = array();
		$index_list = array();

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

			$column_schema[] = sprintf('`%s` %s%s %s %s %s', $name, $this->column_type[$type], $size ? '(' . $size . ')' : '', isset($default) ? "default '" . $default . "'" : '', $notnull ? 'not null' : '', $auto_increment ? 'auto_increment' : '');

			if($primary_key)
			{
				$primary_list[] = $name;
			}
			else if($unique)
			{
				$unique_list[$unique][] = $name;
			}
			else if($index)
			{
				$index_list[$index][] = $name;
			}
		}

		if(count($primary_list))
		{
			$column_schema[] = sprintf("primary key (%s)", '`' . implode($primary_list, '`,`') . '`');
		}

		if(count($unique_list))
		{
			foreach($unique_list as $key => $val)
			{
				$column_schema[] = sprintf("unique %s (%s)", $key, '`' . implode($val, '`,`') . '`');
			}
		}

		if(count($index_list))
		{
			foreach($index_list as $key => $val)
			{
				$column_schema[] = sprintf("index %s (%s)", $key, '`' . implode($val, '`,`') . '`');
			}
		}

		$schema = sprintf('create table `%s` (%s%s) %s;', $this->addQuotes($table_name), "\n", implode($column_schema, ",\n"), "ENGINE = MYISAM  CHARACTER SET utf8 COLLATE utf8_general_ci");

		$output = $this->_query($schema);
		if(!$output)
			return false;
	}

	/**
	 * Handles insertAct
	 * @param Object $queryObject
	 * @param boolean $with_values
	 * @return resource
	 */
	function _executeInsertAct($queryObject, $with_values = true)
	{
		$query = $this->getInsertSql($queryObject, $with_values, true);
		$query .= (__DEBUG_QUERY__ & 1 && $this->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';
		if(is_a($query, 'Object'))
		{
			return;
		}
		return $this->_query($query);
	}

	/**
	 * Handles updateAct
	 * @param Object $queryObject
	 * @param boolean $with_values
	 * @return resource
	 */
	function _executeUpdateAct($queryObject, $with_values = true)
	{
		$query = $this->getUpdateSql($queryObject, $with_values, true);
		if(is_a($query, 'Object'))
		{
			if(!$query->toBool()) return $query;
			else return;
		}

		$query .= (__DEBUG_QUERY__ & 1 && $this->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';


		return $this->_query($query);
	}

	/**
	 * Handles deleteAct
	 * @param Object $queryObject
	 * @param boolean $with_values
	 * @return resource
	 */
	function _executeDeleteAct($queryObject, $with_values = true)
	{
		$query = $this->getDeleteSql($queryObject, $with_values, true);
		$query .= (__DEBUG_QUERY__ & 1 && $this->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';
		if(is_a($query, 'Object'))
		{
			return;
		}
		return $this->_query($query);
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
	function _executeSelectAct($queryObject, $connection = null, $with_values = true)
	{
		$limit = $queryObject->getLimit();
		$result = NULL;
		if($limit && $limit->isPageHandler())
		{
			return $this->queryPageLimit($queryObject, $result, $connection, $with_values);
		}
		else
		{
			$query = $this->getSelectSql($queryObject, $with_values);
			if(is_a($query, 'Object'))
			{
				return;
			}
			$query .= (__DEBUG_QUERY__ & 1 && $queryObject->queryID) ? sprintf(' ' . $this->comment_syntax, $queryObject->queryID) : '';

			$result = $this->_query($query, $connection);
			if($this->isError())
			{
				return $this->queryError($queryObject);
			}

			$data = $this->_fetch($result);
			$buff = new Object ();
			$buff->data = $data;

			if($queryObject->usesClickCount())
			{
				$update_query = $this->getClickCountQuery($queryObject);
				$this->_executeUpdateAct($update_query, $with_values);
			}

			return $buff;
		}
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
		return mysql_insert_id($connection);
	}

	/**
	 * Fetch a result row as an object
	 * @param resource $result
	 * @return object
	 */
	function db_fetch_object(&$result)
	{
		return mysql_fetch_object($result);
	}

	/**
	 * Free result memory
	 * @param resource $result
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	function db_free_result(&$result)
	{
		return mysql_free_result($result);
	}

	/**
	 * Return the DBParser
	 * @param boolean $force
	 * @return DBParser
	 */
	function &getParser($force = FALSE)
	{
		$dbParser = new DBParser('`', '`', $this->prefix);
		return $dbParser;
	}

	/**
	 * If have a error, return error object
	 * @param Object $queryObject
	 * @return Object
	 */
	function queryError($queryObject)
	{
		$limit = $queryObject->getLimit();
		if($limit && $limit->isPageHandler())
		{
			$buff = new Object ();
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
	 * @param Object $queryObject
	 * @param resource $result
	 * @param resource $connection
	 * @param boolean $with_values
	 * @return Object Object with page info containing
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

		$count_query .= (__DEBUG_QUERY__ & 1 && $queryObject->queryID) ? sprintf(' ' . $this->comment_syntax, $queryObject->queryID) : '';
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
			$buff = new Object ();
			$buff->total_count = $total_count;
			$buff->total_page = $total_page;
			$buff->page = $page;
			$buff->data = array();
			$buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
			return $buff;
		}
		$start_count = ($page - 1) * $list_count;

		$query = $this->getSelectPageSql($queryObject, $with_values, $start_count, $list_count);

		$query .= (__DEBUG_QUERY__ & 1 && $queryObject->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';
		$result = $this->_query($query, $connection);
		if($this->isError())
		{
			return $this->queryError($queryObject);
		}

		$virtual_no = $total_count - ($page - 1) * $list_count;
		$data = $this->_fetch($result, $virtual_no);

		$buff = new Object ();
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
			return new Object(-1, "Invalid query");
		}
		$select = 'SELECT ' . $select;

		$from = $query->getFromString($with_values);
		if($from == '')
		{
			return new Object(-1, "Invalid query");
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

}

DBMysql::$isSupported = function_exists('mysql_connect');

/* End of file DBMysql.class.php */
/* Location: ./classes/db/DBMysql.class.php */
