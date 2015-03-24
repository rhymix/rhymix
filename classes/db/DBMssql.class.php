<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * - DBMSSQL
 * - Modified to use MSSQL driver by sol (sol@ngleader.com)
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db
 * @version 0.1
 */
class DBMssql extends DB
{

	/**
	 * prefix of XE tables(One more XE can be installed on a single DB)
	 * @var string
	 */
	var $prefix = 'xe';
	var $param = array();
	var $comment_syntax = '/* %s */';

	/**
	 * column type used in mssql
	 *
	 * column_type should be replaced for each DBMS's type
	 * becasue it uses commonly defined type in the schema/query xml
	 * @var array
	 */
	var $column_type = array(
		'bignumber' => 'bigint',
		'number' => 'int',
		'varchar' => 'nvarchar',
		'char' => 'nchar',
		'text' => 'ntext',
		'bigtext' => 'ntext',
		'date' => 'nvarchar(14)',
		'float' => 'float',
	);

	/**
	 * Constructor
	 * @return void
	 */
	function DBMssql()
	{
		$this->_setDBInfo();
		$this->_connect();
	}

	/**
	 * Create an instance of this class
	 * @return DBMssql return DBMssql object instance
	 */
	function create()
	{
		return new DBMssql;
	}

	/**
	 * DB Connect
	 * this method is private
	 * @param array $connection connection's value is db_hostname, db_database, db_userid, db_password
	 * @return resource
	 */
	function __connect($connection)
	{
		//sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
		//sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
		//sqlsrv_configure( 'LogSubsystems', SQLSRV_LOG_SYSTEM_ALL );
		$result = @sqlsrv_connect($connection["db_hostname"], array('Database' => $connection["db_database"], 'UID' => $connection["db_userid"], 'PWD' => $connection["db_password"]));

		if(!$result)
		{
			$errors = print_r(sqlsrv_errors(), true);
			$this->setError(-1, 'database connect fail' . PHP_EOL . $errors);
			return;
		}
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
		$this->commit();
		sqlsrv_close($connection);
	}

	/**
	 * Handles quatation of the string variables from the query
	 * @todo See what to do about this
	 * @param string $string
	 * @return string
	 */
	function addQuotes($string)
	{
		if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc())
		{
			$string = stripslashes(str_replace("\\", "\\\\", $string));
		}
		//if(!is_numeric($string)) $string = str_replace("'","''",$string);

		return $string;
	}

	/**
	 * DB transaction start
	 * this method is private
	 * @return boolean
	 */
	function _begin($transactionLevel)
	{
		$connection = $this->_getConnection('master');

		if(!$transactionLevel)
		{
			if(sqlsrv_begin_transaction($connection) === false)
			{
				return;
			}
		}
		else
		{
			$this->_query("SAVE TRANS SP" . $transactionLevel, $connection);
		}
		return true;
	}

	/**
	 * DB transaction rollback
	 * this method is private
	 * @return boolean
	 */
	function _rollback($transactionLevel)
	{
		$connection = $this->_getConnection('master');

		$point = $transactionLevel - 1;

		if($point)
		{
			$this->_query("ROLLBACK TRANS SP" . $point, $connection);
		}
		else
		{
			sqlsrv_rollback($connection);
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
		sqlsrv_commit($connection);
		return true;
	}

	/**
	 * Execute the query
	 * this method is private
	 * @param string $query
	 * @param resource $connection
	 * @return resource|boolean Returns a statement resource on success and FALSE if an error occurred.
	 */
	function __query($query, $connection)
	{
		$_param = array();

		if(count($this->param))
		{
			foreach($this->param as $k => $o)
			{
				if($o->isColumnName())
				{
					continue;
				}
				if($o->getType() == 'number')
				{
					$value = $o->getUnescapedValue();
					if(is_array($value))
					{
						$_param = array_merge($_param, $value);
					}
					else
					{
						$_param[] = $o->getUnescapedValue();
					}
				}
				else
				{
					$value = $o->getUnescapedValue();
					if(is_array($value))
					{
						foreach($value as $v)
						{
							$_param[] = array($v, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'));
						}
					}
					else
					{
						$_param[] = array($value, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'));
					}
				}
			}
		}

		// Run the query statement
		$result = false;
		if(count($_param))
		{
			$args = $this->_getParametersByReference($_param);
			$stmt = sqlsrv_prepare($connection, $query, $args);
		}
		else
		{
			$stmt = sqlsrv_prepare($connection, $query);
		}

		if(!$stmt)
		{
			$result = false;
		}
		else
		{
			$result = sqlsrv_execute($stmt);
		}

		// Error Check
		if(!$result)
		{
			$this->setError(print_r(sqlsrv_errors(), true));
		}

		$this->param = array();

		return $stmt;
	}

	/**
	 * Parameters to sqlsrv_prepare need to be references, and not literals
	 * Parameters are sent as an array, where each parameter can be:
	 * 	- a PHP variable (by reference)
	 * 	- a PHP array (containng param value, type and direction) -> also needs to be sent by reference
	 * @param array $_param
	 * @return array
	 */
	function _getParametersByReference($_param)
	{
		$copy = array();
		$args = array();
		$i = 0;
		foreach($_param as $key => $value)
		{
			if(is_array($value))
			{
				$value_copy = $value;
				$value_arg = array();
				$value_arg[] = &$value_copy[0];
				$value_arg[] = $value_copy[1];
				$value_arg[] = $value_copy[2];
			}
			else
			{
				$value_arg = $value;
			}
			$copy[$key] = $value_arg;
			$args[$i++] = &$copy[$key];
		}
		return $args;
	}

	/**
	 * Fetch results
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

		$c = sqlsrv_num_fields($result);
		$m = null;

		while(sqlsrv_fetch($result))
		{
			if(!$m)
			{
				$m = sqlsrv_field_metadata($result);
			}
			unset($row);
			for($i = 0; $i < $c; $i++)
			{
				$row->{$m[$i]['Name']} = sqlsrv_get_field($result, $i, SQLSRV_PHPTYPE_STRING('utf-8'));
			}
			if($arrayIndexEndValue)
			{
				$output[$arrayIndexEndValue--] = $row;
			}
			else
			{
				$output[] = $row;
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
		return $output;
	}

	/**
	 * Return the sequence value incremented by 1
	 * Auto_increment column only used in the sequence table
	 * @return int
	 */
	function getNextSequence()
	{
		$query = sprintf("insert into %ssequence (seq) values (ident_incr('%ssequence'))", $this->prefix, $this->prefix);
		$this->_query($query);

		$query = sprintf("select ident_current('%ssequence')+1 as sequence", $this->prefix);
		$result = $this->_query($query);
		$tmp = $this->_fetch($result);


		return $tmp->sequence;
	}

	/**
	 * Check a table exists status
	 * @param string $target_name
	 * @return boolean
	 */
	function isTableExists($target_name)
	{
		$query = sprintf("select name from sysobjects where name = '%s%s' and xtype='U'", $this->prefix, $this->addQuotes($target_name));
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
		if($this->isColumnExists($table_name, $column_name))
		{
			return;
		}
		$type = $this->column_type[$type];
		if(strtoupper($type) == 'INTEGER')
		{
			$size = '';
		}

		$query = sprintf("alter table %s%s add %s ", $this->prefix, $table_name, $column_name);
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
		if(!$this->isColumnExists($table_name, $column_name))
		{
			return;
		}
		$query = sprintf("alter table %s%s drop %s ", $this->prefix, $table_name, $column_name);
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
		$query = sprintf("select syscolumns.name as name from syscolumns, sysobjects where sysobjects.name = '%s%s' and sysobjects.id = syscolumns.id and syscolumns.name = '%s'", $this->prefix, $table_name, $column_name);
		$result = $this->_query($query);
		if($this->isError())
		{
			return;
		}
		$tmp = $this->_fetch($result);
		if(!$tmp->name)
		{
			return false;
		}
		return true;
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
		if($this->isIndexExists($table_name, $index_name))
		{
			return;
		}
		if(!is_array($target_columns))
		{
			$target_columns = array($target_columns);
		}

		$query = sprintf("create %s index %s on %s%s (%s)", $is_unique ? 'unique' : '', $index_name, $this->prefix, $table_name, implode(',', $target_columns));
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
		if(!$this->isIndexExists($table_name, $index_name))
		{
			return;
		}
		$query = sprintf("drop index %s%s.%s", $this->prefix, $table_name, $index_name);
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
		$query = sprintf("select sysindexes.name as name from sysindexes, sysobjects where sysobjects.name = '%s%s' and sysobjects.id = sysindexes.id and sysindexes.name = '%s'", $this->prefix, $table_name, $index_name);

		$result = $this->_query($query);
		if($this->isError())
		{
			return;
		}
		$tmp = $this->_fetch($result);

		if(!$tmp->name)
		{
			return false;
		}
		return true;
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

		if($table_name == 'sequence')
		{
			$table_name = $this->prefix . $table_name;
			$query = sprintf('create table %s ( sequence int identity(1,1), seq int )', $table_name);
			return $this->_query($query);
		}
		else
		{
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

			$typeList = array('number' => 1, 'text' => 1);
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

				$column_schema[] = sprintf('[%s] %s%s %s %s %s', $name, $this->column_type[$type], !isset($typeList[$type]) && $size ? '(' . $size . ')' : '', isset($default) ? "default '" . $default . "'" : '', $notnull ? 'not null' : 'null', $auto_increment ? 'identity(1,1)' : '');

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
				$column_schema[] = sprintf("primary key (%s)", '"' . implode($primary_list, '","') . '"');
			}

			$schema = sprintf('create table [%s] (%s%s)', $this->addQuotes($table_name), "\n", implode($column_schema, ",\n"));
			$output = $this->_query($schema);
			if(!$output)
			{
				return false;
			}

			if(count($unique_list))
			{
				foreach($unique_list as $key => $val)
				{
					$query = sprintf("create unique index %s on %s (%s);", $key, $table_name, '[' . implode('],[', $val) . ']');
					$this->_query($query);
				}
			}

			if(count($index_list))
			{
				foreach($index_list as $key => $val)
				{
					$query = sprintf("create index %s on %s (%s);", $key, $table_name, '[' . implode('],[', $val) . ']');
					$this->_query($query);
				}
			}
			return true;
		}
	}

	/**
	 * Handles insertAct
	 * @todo Lookup _filterNumber against sql injection - see if it is still needed and how to integrate
	 * @param Object $queryObject
	 * @return resource
	 */
	function _executeInsertAct($queryObject)
	{
		$query = $this->getInsertSql($queryObject, false);
		$this->param = $queryObject->getArguments();
		return $this->_query($query);
	}

	/**
	 * Handles updateAct
	 * @param Object $queryObject
	 * @return resource
	 */
	function _executeUpdateAct($queryObject)
	{
		$query = $this->getUpdateSql($queryObject, false);
		$this->param = $queryObject->getArguments();
		return $this->_query($query);
	}

	/**
	 * Return update query string
	 * @param object $query
	 * @param boolean $with_values
	 * @param boolean $with_priority
	 * @return string
	 */
	function getUpdateSql($query, $with_values = true, $with_priority = false)
	{
		$columnsList = $query->getUpdateString($with_values);
		if($columnsList == '')
		{
			return new Object(-1, "Invalid query");
		}

		$from = $query->getFromString($with_values);
		if($from == '')
		{
			return new Object(-1, "Invalid query");
		}

		$tables = $query->getTables();
		$alias_list = '';
		foreach($tables as $table)
		{
			$alias_list .= $table->getAlias();
		}
		implode(',', explode(' ', $alias_list));

		$where = $query->getWhereString($with_values);
		if($where != '')
		{
			$where = ' WHERE ' . $where;
		}

		$priority = $with_priority ? $query->getPriority() : '';

		return "UPDATE $priority $alias_list SET $columnsList FROM " . $from . $where;
	}

	/**
	 * Handles deleteAct
	 * @param Object $queryObject
	 * @return resource
	 */
	function _executeDeleteAct($queryObject)
	{
		$query = $this->getDeleteSql($queryObject, false);
		$this->param = $queryObject->getArguments();
		return $this->_query($query);
	}

	/**
	 * Return select query string
	 * @param object $query
	 * @param boolean $with_values
	 * @return string
	 */
	function getSelectSql($query, $with_values = TRUE, $connection=NULL)
	{
		$with_values = false;

		//$limitOffset = $query->getLimit()->getOffset();
		//if($limitOffset)
		// TODO Implement Limit with offset with subquery
		$limit = '';
		$limitCount = '';
		$limitQueryPart = $query->getLimit();
		if($limitQueryPart)
		{
			$limitCount = $limitQueryPart->getLimit();
		}
		if($limitCount != '')
		{
			$limit = 'SELECT TOP ' . $limitCount;
		}

		$select = $query->getSelectString($with_values);
		if($select == '')
		{
			return new Object(-1, "Invalid query");
		}
		if($limit != '')
		{
			$select = $limit . ' ' . $select;
		}
		else
		{
			$select = 'SELECT ' . $select;
		}

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

		if($limitCount != '' && $query->limit->start > 0)
		{
			$order = $query->getOrder();
			$first_columns = array();
			foreach($order as $val)
			{
				$tmpColumnName = $val->getPureColumnName();
				$first_columns[] = sprintf('%s(%s) as %s', $val->getPureSortOrder()=='asc'?'max':'min', $tmpColumnName, $tmpColumnName);
				$first_sub_columns[] = $tmpColumnName;
			}

			$first_query = sprintf("select %s from (select top %d %s %s %s %s %s) xet", implode(',',$first_columns),  $query->limit->start, implode(',',$first_sub_columns), $from, $where, $groupBy, $orderBy);
			$this->param = $query->getArguments();
			$result = $this->__query($first_query, $connection);
			$tmp = $this->_fetch($result);

			$sub_cond = array();
			foreach($order as $k => $v)
			{
				//for example... use Document
				if(get_class($v->sort_order) == 'SortArgument')
				{
					$sort_order = $v->sort_order->value;
				}
				//for example... use comment, file
				else
				{
					$sort_order = $v->sort_order;
				}

				$sub_cond[] = sprintf("%s %s '%s'", $v->getPureColumnName(), $sort_order=='asc'?'>':'<', $tmp->{$v->getPureColumnName()});
			}

			if(!$where)
			{
				$sub_condition = ' WHERE ( '.implode(' and ',$sub_cond).' )';
			}
			else
			{
				$sub_condition = ' and ( '.implode(' and ',$sub_cond).' )';
			}
		}
		return $select . ' ' . $from . ' ' . $where .$sub_condition. ' ' . $groupBy . ' ' . $orderBy;
	}

	/**
	 * Handle selectAct
	 * In order to get a list of pages easily when selecting \n
	 * it supports a method as navigation
	 * @param Object $queryObject
	 * @param resource $connection
	 * @return Object
	 */
	function _executeSelectAct($queryObject, $connection = null)
	{
		$query = $this->getSelectSql($queryObject, true, $connection);

		if(strpos($query, "substr"))
		{
			$query = str_replace("substr", "substring", $query);
		}

		// TODO Decide if we continue to pass parameters like this
		$this->param = $queryObject->getArguments();

		$query .= (__DEBUG_QUERY__ & 1 && $output->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';
		$result = $this->_query($query, $connection);

		if($this->isError())
		{
			return $this->queryError($queryObject);
		}
		else
		{
			return $this->queryPageLimit($queryObject, $result, $connection);
		}
	}

	/**
	 * Return the DBParser
	 * @param boolean $force
	 * @return DBParser
	 */
	function getParser($force = FALSE)
	{
		return new DBParser("[", "]", $this->prefix);
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
	 * @return Object Object with page info containing
	 */
	function queryPageLimit($queryObject, $result, $connection)
	{
		$limit = $queryObject->getLimit();
		if($limit && $limit->isPageHandler())
		{
			// Total count
			$temp_where = $queryObject->getWhereString(true, false);
			$count_query = sprintf('select count(*) as "count" %s %s', 'FROM ' . $queryObject->getFromString(), ($temp_where === '' ? '' : ' WHERE ' . $temp_where));

			// Check for distinct query and if found update count query structure
			$temp_select = $queryObject->getSelectString(true);
			$uses_distinct = stripos($temp_select, "distinct") !== false;
			$uses_groupby = $queryObject->getGroupByString() != '';
			if($uses_distinct || $uses_groupby)
			{
				$count_query = sprintf('select %s %s %s %s'
						, $temp_select
						, 'FROM ' . $queryObject->getFromString(true)
						, ($temp_where === '' ? '' : ' WHERE ' . $temp_where)
						, ($uses_groupby ? ' GROUP BY ' . $queryObject->getGroupByString() : '')
				);

				// If query uses grouping or distinct, count from original select
				$count_query = sprintf('select count(*) as "count" from (%s) xet', $count_query);
			}

			$count_query .= (__DEBUG_QUERY__ & 1 && $queryObject->queryID) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';
			$this->param = $queryObject->getArguments();
			$result_count = $this->_query($count_query, $connection);
			$count_output = $this->_fetch($result_count);
			$total_count = (int) $count_output->count;

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
			// Total pages
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

				if($queryObject->usesClickCount())
				{
					$update_query = $this->getClickCountQuery($queryObject);
					$this->_executeUpdateAct($update_query);
				}
			}

			$start_count = ($page - 1) * $list_count;
			$this->param = $queryObject->getArguments();
			$virtual_no = $total_count - $start_count;
			$data = $this->_fetch($result, $virtual_no);

			$buff = new Object ();
			$buff->total_count = $total_count;
			$buff->total_page = $total_page;
			$buff->page = $page;
			$buff->data = $data;
			$buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
		}
		else
		{
			$data = $this->_fetch($result);
			$buff = new Object ();
			$buff->data = $data;
		}
		return $buff;
	}

}

DBMssql::$isSupported = extension_loaded("sqlsrv");

/* End of file DBMssql.class.php */
/* Location: ./classes/db/DBMssql.class.php */
