<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * - DB child class
 * - Cubrid DBMS to use the class
 * - Works with CUBRID up to 8.4.1
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db
 * @version 0.1
 */
class DBCubrid extends DB
{

	/**
	 * prefix of XE tables(One more XE can be installed on a single DB)
	 * @var string
	 */
	var $prefix = 'xe_';

	/**
	 * max size of constant in CUBRID(if string is larger than this, '...'+'...' should be used)
	 * @var int
	 */
	var $cutlen = 12000;
	var $comment_syntax = '/* %s */';

	/**
	 * column type used in CUBRID
	 *
	 * column_type should be replaced for each DBMS's type
	 * becasue it uses commonly defined type in the schema/query xml
	 * @var array
	 */
	var $column_type = array(
		'bignumber' => 'numeric(20)',
		'number' => 'integer',
		'varchar' => 'character varying',
		'char' => 'character',
		'tinytext' => 'character varying(256)',
		'text' => 'character varying(1073741823)',
		'bigtext' => 'character varying(1073741823)',
		'date' => 'character varying(14)',
		'float' => 'float',
	);

	/**
	 * constructor
	 * @return void
	 */
	function DBCubrid()
	{
		$this->_setDBInfo();
		$this->_connect();
	}

	/**
	 * Create an instance of this class
	 * @return DBCubrid return DBCubrid object instance
	 */
	function create()
	{
		return new DBCubrid;
	}

	/**
	 * DB Connect
	 * this method is private
	 * @param array $connection connection's value is db_hostname, db_port, db_database, db_userid, db_password
	 * @return resource
	 */
	function __connect($connection)
	{
		// attempts to connect
		$result = @cubrid_connect($connection["db_hostname"], $connection["db_port"], $connection["db_database"], $connection["db_userid"], $connection["db_password"]);

		// check connections
		if(!$result)
		{
			$this->setError(-1, 'database connect fail');
			return;
		}

		if(!defined('__CUBRID_VERSION__'))
		{
			$cubrid_version = cubrid_get_server_info($result);
			$cubrid_version_elem = explode('.', $cubrid_version);
			$cubrid_version = $cubrid_version_elem[0] . '.' . $cubrid_version_elem[1] . '.' . $cubrid_version_elem[2];
			define('__CUBRID_VERSION__', $cubrid_version);
		}

		if(__CUBRID_VERSION__ >= '8.4.0')
			cubrid_set_autocommit($result, CUBRID_AUTOCOMMIT_TRUE);

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
		@cubrid_commit($connection);
		@cubrid_disconnect($connection);
		$this->transaction_started = FALSE;
	}

	/**
	 * Handles quatation of the string variables from the query
	 * @param string $string
	 * @return string
	 */
	function addQuotes($string)
	{
		if(version_compare(PHP_VERSION, "5.4.0", "<") &&
				get_magic_quotes_gpc())
		{
			$string = stripslashes(str_replace("\\", "\\\\", $string));
		}

		if(!is_numeric($string))
		{
			/*
			  if ($this->isConnected()) {
			  $string = cubrid_real_escape_string($string);
			  }
			  else {
			  $string = str_replace("'","\'",$string);
			  }
			 */

			$string = str_replace("'", "''", $string);
		}

		return $string;
	}

	/**
	 * DB transaction start
	 * this method is private
	 * @return boolean
	 */
	function _begin($transactionLevel)
	{
		if(__CUBRID_VERSION__ >= '8.4.0')
		{
			$connection = $this->_getConnection('master');

			if(!$transactionLevel)
			{
				cubrid_set_autocommit($connection, CUBRID_AUTOCOMMIT_FALSE);
			}
			else
			{
				$this->_query("SAVEPOINT SP" . $transactionLevel, $connection);
			}
		}
		return TRUE;
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
			$this->_query("ROLLBACK TO SP" . $point, $connection);
		}
		else
		{
			@cubrid_rollback($connection);
		}

		return TRUE;
	}

	/**
	 * DB transaction commit
	 * this method is private
	 * @return boolean
	 */
	function _commit()
	{
		$connection = $this->_getConnection('master');
		@cubrid_commit($connection);
		return TRUE;
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
			$req = @cubrid_prepare($connection, $query);
			if(!$req)
			{
				$this->_setError();
				return false;
			}

			$position = 0;
			if($this->param)
			{
				foreach($this->param as $param)
				{
					$value = $param->getUnescapedValue();
					$type = $param->getType();

					if($param->isColumnName())
					{
						continue;
					}

					switch($type)
					{
						case 'number' :
							$bind_type = 'numeric';
							break;
						case 'varchar' :
							$bind_type = 'string';
							break;
						default:
							$bind_type = 'string';
					}

					if(is_array($value))
					{
						foreach($value as $v)
						{
							$bound = @cubrid_bind($req, ++$position, $v, $bind_type);
							if(!$bound)
							{
								$this->_setError();
								return false;
							}
						}
					}
					else
					{
						$bound = @cubrid_bind($req, ++$position, $value, $bind_type);
						if(!$bound)
						{
							$this->_setError();
							return false;
						}
					}
				}
			}

			$result = @cubrid_execute($req);
			if(!$result)
			{
				$this->_setError();
				return false;
			}
			return $req;
		}
		// Execute the query
		$result = @cubrid_execute($connection, $query);
		// error check
		if(!$result)
		{
			$this->_setError();
			return false;
		}
		// Return the result
		return $result;
	}

	/**
	 * Retrieve CUBRID error and set to object
	 *
	 * @author Corina Udrescu (dev@xpressengine.org)
	 */
	function _setError()
	{
		$code = cubrid_error_code();
		$msg = cubrid_error_msg();

		$this->setError($code, $msg);
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
			return array();
		}

		if($this->use_prepared_statements == 'Y')
		{

		}

		// TODO Improve this piece of code
		// This code trims values from char type columns
		$col_types = cubrid_column_types($result);
		$col_names = cubrid_column_names($result);
		$max = count($col_types);

		for($count = 0; $count < $max; $count++)
		{
			if(preg_match("/^char/", $col_types[$count]) > 0)
			{
				$char_type_fields[] = $col_names[$count];
			}
		}

		while($tmp = cubrid_fetch($result, CUBRID_OBJECT))
		{
			if(is_array($char_type_fields))
			{
				foreach($char_type_fields as $val)
				{
					$tmp->{$val} = rtrim($tmp->{$val});
				}
			}

			if($arrayIndexEndValue)
			{
				$output[$arrayIndexEndValue--] = $tmp;
			}
			else
			{
				$output[] = $tmp;
			}
		}

		unset($char_type_fields);

		if($result)
		{
			cubrid_close_request($result);
		}

		if(count($output) == 1)
		{
			// If call is made for pagination, always return array
			if(isset($arrayIndexEndValue))
			{
				return $output;
			}
			// Else return object instead of array
			else
			{
				return $output[0];
			}
		}
		return $output;
	}

	/**
	 * Return the sequence value incremented by 1
	 * Auto_increment column only used in the CUBRID sequence table
	 * @return int
	 */
	function getNextSequence()
	{
		$this->_makeSequence();

		$query = sprintf("select \"%ssequence\".\"nextval\" as \"seq\" from db_root", $this->prefix);
		$result = $this->_query($query);
		$output = $this->_fetch($result);

		return $output->seq;
	}

	/**
	 * if the table already exists, set the status to GLOBALS
	 * @return void
	 */
	function _makeSequence()
	{
		if($_GLOBALS['XE_EXISTS_SEQUENCE'])
			return;

		// check cubrid serial
		$query = sprintf('select count(*) as "count" from "db_serial" where name=\'%ssequence\'', $this->prefix);
		$result = $this->_query($query);
		$output = $this->_fetch($result);

		// if do not create serial
		if($output->count == 0)
		{
			$query = sprintf('select max("a"."srl") as "srl" from ' .
					'( select max("document_srl") as "srl" from ' .
					'"%sdocuments" UNION ' .
					'select max("comment_srl") as "srl" from ' .
					'"%scomments" UNION ' .
					'select max("member_srl") as "srl" from ' .
					'"%smember"' .
					') as "a"', $this->prefix, $this->prefix, $this->prefix);

			$result = $this->_query($query);
			$output = $this->_fetch($result);
			$srl = $output->srl;
			if($srl < 1)
			{
				$start = 1;
			}
			else
			{
				$start = $srl + 1000000;
			}

			// create sequence
			$query = sprintf('create serial "%ssequence" start with %s increment by 1 minvalue 1 maxvalue 10000000000000000000000000000000000000 nocycle;', $this->prefix, $start);
			$this->_query($query);
		}

		$_GLOBALS['XE_EXISTS_SEQUENCE'] = TRUE;
	}

	/**
	 * Check a table exists status
	 * @param string $target_name
	 * @return boolean
	 */
	function isTableExists($target_name)
	{
		if($target_name == 'sequence')
		{
			$query = sprintf("select \"name\" from \"db_serial\" where \"name\" = '%s%s'", $this->prefix, $target_name);
		}
		else
		{
			$query = sprintf("select \"class_name\" from \"db_class\" where \"class_name\" = '%s%s'", $this->prefix, $target_name);
		}

		$result = $this->_query($query);
		if(cubrid_num_rows($result) > 0)
		{
			$output = TRUE;
		}
		else
		{
			$output = FALSE;
		}

		if($result)
		{
			cubrid_close_request($result);
		}

		return $output;
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
	function addColumn($table_name, $column_name, $type = 'number', $size = '', $default = null, $notnull = FALSE)
	{
		$type = strtoupper($this->column_type[$type]);
		if($type == 'INTEGER')
		{
			$size = '';
		}

		$query = sprintf("alter class \"%s%s\" add \"%s\" ", $this->prefix, $table_name, $column_name);

		if($type == 'char' || $type == 'varchar')
		{
			if($size)
			{
				$size = $size * 3;
			}
		}

		if($size)
		{
			$query .= sprintf("%s(%s) ", $type, $size);
		}
		else
		{
			$query .= sprintf("%s ", $type);
		}

		if(isset($default))
		{
			if($type == 'INTEGER' || $type == 'BIGINT' || $type == 'INT')
			{
				$query .= sprintf("default %d ", $default);
			}
			else
			{
				$query .= sprintf("default '%s' ", $default);
			}
		}

		if($notnull)
		{
			$query .= "not null ";
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
		$query = sprintf("alter class \"%s%s\" drop \"%s\" ", $this->prefix, $table_name, $column_name);

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
		$query = sprintf("select \"attr_name\" from \"db_attribute\" where " . "\"attr_name\" ='%s' and \"class_name\" = '%s%s'", $column_name, $this->prefix, $table_name);
		$result = $this->_query($query);

		if(cubrid_num_rows($result) > 0)
		{
			$output = TRUE;
		}
		else
		{
			$output = FALSE;
		}

		if($result)
		{
			cubrid_close_request($result);
		}

		return $output;
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
	function addIndex($table_name, $index_name, $target_columns, $is_unique = FALSE)
	{
		if(!is_array($target_columns))
		{
			$target_columns = array($target_columns);
		}

		$query = sprintf("create %s index \"%s\" on \"%s%s\" (%s);", $is_unique ? 'unique' : '', $index_name, $this->prefix, $table_name, '"' . implode('","', $target_columns) . '"');

		$this->_query($query);
	}

	/**
	 * Drop an index from the table
	 * @param string $table_name table name
	 * @param string $index_name index name
	 * @param boolean $is_unique
	 * @return void
	 */
	function dropIndex($table_name, $index_name, $is_unique = FALSE)
	{
		$query = sprintf("drop %s index \"%s\" on \"%s%s\"", $is_unique ? 'unique' : '', $index_name, $this->prefix, $table_name);

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
		$query = sprintf("select \"index_name\" from \"db_index\" where " . "\"class_name\" = '%s%s' and (\"index_name\" = '%s' or \"index_name\" = '%s') ", $this->prefix, $table_name, $this->prefix . $index_name, $index_name);
		$result = $this->_query($query);

		if($this->isError())
		{
			return FALSE;
		}

		$output = $this->_fetch($result);

		if(!$output)
		{
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Delete duplicated index of the table
	 * @return boolean
	 */
	function deleteDuplicateIndexes()
	{
		$query = sprintf("
				select \"class_name\"
				, case
				when substr(\"index_name\", 0, %d) = '%s'
				then substr(\"index_name\", %d)
				else \"index_name\" end as unprefixed_index_name
				, \"is_unique\"
				from \"db_index\"
				where \"class_name\" like %s
				group by \"class_name\"
				, case
				when substr(\"index_name\", 0, %d) = '%s'
				then substr(\"index_name\", %d)
				else \"index_name\"
				end
				having count(*) > 1
				", strlen($this->prefix)
				, $this->prefix
				, strlen($this->prefix) + 1
				, "'" . $this->prefix . '%' . "'"
				, strlen($this->prefix)
				, $this->prefix
				, strlen($this->prefix) + 1
		);
		$result = $this->_query($query);

		if($this->isError())
		{
			return FALSE;
		}

		$output = $this->_fetch($result);
		if(!$output)
		{
			return FALSE;
		}

		if(!is_array($output))
		{
			$indexes_to_be_deleted = array($output);
		}
		else
		{
			$indexes_to_be_deleted = $output;
		}

		foreach($indexes_to_be_deleted as $index)
		{
			$this->dropIndex(substr($index->class_name, strlen($this->prefix))
					, $this->prefix . $index->unprefixed_index_name
					, $index->is_unique == 'YES' ? TRUE : FALSE);
		}

		return TRUE;
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

		// if the table already exists exit function
		if($this->isTableExists($table_name))
		{
			return;
		}

		// If the table name is sequence, it creates a serial
		if($table_name == 'sequence')
		{
			$query = sprintf('create serial "%s" start with 1 increment by 1' .
					' minvalue 1 ' .
					'maxvalue 10000000000000000000000000000000000000' . ' nocycle;', $this->prefix . $table_name);

			return $this->_query($query);
		}


		$table_name = $this->prefix . $table_name;

		$query = sprintf('create class "%s";', $table_name);
		$this->_query($query);

		if(!is_array($xml_obj->table->column))
		{
			$columns[] = $xml_obj->table->column;
		}
		else
		{
			$columns = $xml_obj->table->column;
		}

		$query = sprintf("alter class \"%s\" add attribute ", $table_name);

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

			switch($this->column_type[$type])
			{
				case 'integer' :
					$size = NULL;
					break;
				case 'text' :
					$size = NULL;
					break;
			}

			if(isset($default) && ($type == 'varchar' || $type == 'char' ||
					$type == 'text' || $type == 'tinytext' || $type == 'bigtext'))
			{
				$default = sprintf("'%s'", $default);
			}

			if($type == 'varchar' || $type == 'char')
			{
				if($size)
					$size = $size * 3;
			}


			$column_schema[] = sprintf('"%s" %s%s %s %s', $name, $this->column_type[$type], $size ? '(' . $size . ')' : '', isset($default) ? "default " . $default : '', $notnull ? 'not null' : '');

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

		$query .= implode(',', $column_schema) . ';';
		$this->_query($query);

		if(count($primary_list))
		{
			$query = sprintf("alter class \"%s\" add attribute constraint " . "\"pkey_%s\" PRIMARY KEY(%s);", $table_name, $table_name, '"' . implode('","', $primary_list) . '"');
			$this->_query($query);
		}

		if(count($unique_list))
		{
			foreach($unique_list as $key => $val)
			{
				$query = sprintf("create unique index \"%s\" on \"%s\" " . "(%s);", $key, $table_name, '"' . implode('","', $val) . '"');
				$this->_query($query);
			}
		}

		if(count($index_list))
		{
			foreach($index_list as $key => $val)
			{
				$query = sprintf("create index \"%s\" on \"%s\" (%s);", $key, $table_name, '"' . implode('","', $val) . '"');
				$this->_query($query);
			}
		}
	}

	/**
	 * Handles insertAct
	 * @param Object $queryObject
	 * @param boolean $with_values
	 * @return resource
	 */
	function _executeInsertAct($queryObject, $with_values = TRUE)
	{
		if($this->use_prepared_statements == 'Y')
		{
			$this->param = $queryObject->getArguments();
			$with_values = FALSE;
		}
		$query = $this->getInsertSql($queryObject, $with_values);
		if(is_a($query, 'Object'))
		{
			unset($this->param);
			return;
		}

		$query .= (__DEBUG_QUERY__ & 1 && $this->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';

		$result = $this->_query($query);
		if($result && !$this->transaction_started)
		{
			$this->_commit();
		}
		unset($this->param);
		return $result;
	}

	/**
	 * Handles updateAct
	 * @param Object $queryObject
	 * @param boolean $with_values
	 * @return resource
	 */
	function _executeUpdateAct($queryObject, $with_values = TRUE)
	{
		if($this->use_prepared_statements == 'Y')
		{
			$this->param = $queryObject->getArguments();
			$with_values = FALSE;
		}
		$query = $this->getUpdateSql($queryObject, $with_values);
		if(is_a($query, 'Object'))
		{
			unset($this->param);
			return;
		}

		$query .= (__DEBUG_QUERY__ & 1 && $this->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';

		$result = $this->_query($query);

		if($result && !$this->transaction_started)
		{
			$this->_commit();
		}
		unset($this->param);
		return $result;
	}

	/**
	 * Handles deleteAct
	 * @param Object $queryObject
	 * @param boolean $with_values
	 * @return resource
	 */
	function _executeDeleteAct($queryObject, $with_values = TRUE)
	{
		if($this->use_prepared_statements == 'Y')
		{
			$this->param = $queryObject->getArguments();
			$with_values = FALSE;
		}
		$query = $this->getDeleteSql($queryObject, $with_values);
		if(is_a($query, 'Object'))
		{
			unset($this->param);
			return;
		}

		$query .= (__DEBUG_QUERY__ & 1 && $this->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';

		$result = $this->_query($query);

		if($result && !$this->transaction_started)
		{
			$this->_commit();
		}

		unset($this->param);
		return $result;
	}

	/**
	 * Handle selectAct
	 * To get a specific page list easily in select statement,
	 * a method, navigation, is used
	 * @param Object $queryObject
	 * @param resource $connection
	 * @param boolean $with_values
	 * @return Object
	 */
	function _executeSelectAct($queryObject, $connection = NULL, $with_values = TRUE)
	{
		if($this->use_prepared_statements == 'Y')
		{
			$this->param = $queryObject->getArguments();
			$with_values = FALSE;
		}
		$limit = $queryObject->getLimit();
		if($limit && $limit->isPageHandler())
		{
			return $this->queryPageLimit($queryObject, $connection, $with_values);
		}
		else
		{
			$query = $this->getSelectSql($queryObject, $with_values);
			if(is_a($query, 'Object'))
			{
				unset($this->param);
				return;
			}

			$query .= (__DEBUG_QUERY__ & 1 && $this->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';
			$result = $this->_query($query, $connection);

			if($this->isError())
			{
				unset($this->param);
				return $this->queryError($queryObject);
			}

			$data = $this->_fetch($result);
			$buff = new Object ();
			$buff->data = $data;

			unset($this->param);
			return $buff;
		}
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
		}else
			return;
	}

	/**
	 * If select query execute, return page info
	 * @param Object $queryObject
	 * @param resource $connection
	 * @param boolean $with_values
	 * @return Object Object with page info containing
	 */
	function queryPageLimit($queryObject, $connection, $with_values)
	{
		$limit = $queryObject->getLimit();
		// Total count
		$temp_where = $queryObject->getWhereString($with_values, FALSE);
		$count_query = sprintf('select count(*) as "count" %s %s', 'FROM ' . $queryObject->getFromString($with_values), ($temp_where === '' ? '' : ' WHERE ' . $temp_where));

		// Check for distinct query and if found update count query structure
		$temp_select = $queryObject->getSelectString($with_values);
		$uses_distinct = stripos($temp_select, "distinct") !== FALSE;
		$uses_groupby = $queryObject->getGroupByString() != '';
		if($uses_distinct || $uses_groupby)
		{
			$count_query = sprintf('select %s %s %s %s'
					, $temp_select
					, 'FROM ' . $queryObject->getFromString($with_values)
					, ($temp_where === '' ? '' : ' WHERE ' . $temp_where)
					, ($uses_groupby ? ' GROUP BY ' . $queryObject->getGroupByString() : '')
			);

			// If query uses grouping or distinct, count from original select
			$count_query = sprintf('select count(*) as "count" from (%s) xet', $count_query);
		}

		$count_query .= (__DEBUG_QUERY__ & 1 && $queryObject->queryID) ? sprintf(' ' . $this->comment_syntax, $queryObject->queryID) : '';
		$result = $this->_query($count_query, $connection);
		$count_output = $this->_fetch($result);
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
			unset($this->param);
			return $buff;
		}
		$start_count = ($page - 1) * $list_count;

		$query = $this->getSelectPageSql($queryObject, $with_values, $start_count, $list_count);
		$query .= (__DEBUG_QUERY__ & 1 && $queryObject->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';
		$result = $this->_query($query, $connection);
		if($this->isError())
		{
			unset($this->param);
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
		unset($this->param);
		return $buff;
	}

	/**
	 * Return the DBParser
	 * @param boolean $force
	 * @return DBParser
	 */
	function getParser($force = FALSE)
	{
		return new DBParser('"', '"', $this->prefix);
	}

	/**
	 * If select query execute, return paging sql
	 * @param object $query
	 * @param boolean $with_values
	 * @param int $start_count
	 * @param int $list_count
	 * @return string select paging sql
	 */
	function getSelectPageSql($query, $with_values = TRUE, $start_count = 0, $list_count = 0)
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

DBCubrid::$isSupported = function_exists('cubrid_connect');

/* End of file DBCubrid.class.php */
/* Location: ./classes/db/DBCubrid.class.php */
