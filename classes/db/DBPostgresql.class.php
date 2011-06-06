<?php
/**
 * @class DBPostgreSQL
 * @author ioseph (ioseph@postgresql.kr) updated by yoonjong.joh@gmail.com
 * @brief Class to use PostgreSQL DBMS
 * @version 0.2
 *
 * postgresql handling class
 * 2009.02.10 update and delete query for the table name at runtime, eliminating the alias to use. Not Supported
 * when running order by clause column alias to run a function to replace parts.
 * 2009.02.11 dropColumn() function added
 * 2009.02.13 addColumn() function changes
 **/

class DBPostgresql extends DB
{

    /**
     * @brief Connection information for PostgreSQL DB
     **/
    var $hostname = '127.0.0.1'; ///< hostname
    var $userid = null; ///< user id
    var $password = null; ///< password
    var $database = null; ///< database
    var $prefix = 'xe'; // / <prefix of a tablename (One or more XEs can be installed in a single DB)
	var $comment_syntax = '/* %s */';

    /**
     * @brief column type used in postgresql 
     *
     * Becasue a common column type in schema/query xml is used for colum_type,
     * it should be replaced properly for each DBMS
     **/
    var $column_type = array(
        'bignumber' => 'bigint', 
        'number' => 'integer',
        'varchar' => 'varchar', 
        'char' => 'char', 
        'text' => 'text', 
        'bigtext' => 'text',
        'date' => 'varchar(14)', 
        'float' => 'real',
    );

    /**
     * @brief constructor
     **/
    function DBPostgresql()
    {
        $this->_setDBInfo();
        $this->_connect();
    }
	
	/**
	 * @brief create an instance of this class
	 */
	function create()
	{
		return new DBPostgresql;
	}

    /**
     * @brief Return if it is installable
     **/
    function isSupported()
    {
        if (!function_exists('pg_connect'))
            return false;
        return true;
    }

    /**
     * @brief DB settings and connect/close
     **/
    function _setDBInfo()
    {
        $db_info = Context::getDBInfo();
        $this->hostname = $db_info->db_hostname;
        $this->port = $db_info->db_port;
        $this->userid = $db_info->db_userid;
        $this->password = $db_info->db_password;
        $this->database = $db_info->db_database;
        $this->prefix = $db_info->db_table_prefix;
        if (!substr($this->prefix, -1) != '_')
            $this->prefix .= '_';
    }

    /**
     * @brief DB Connection
     **/
    function _connect()
    {
        // the connection string for PG
        $conn_string = "";
        // Ignore if no DB information exists
        if (!$this->hostname || !$this->userid || !$this->database)
            return;
        // Create connection string
        $conn_string .= ($this->hostname) ? " host=$this->hostname" : "";
        $conn_string .= ($this->userid) ? " user=$this->userid" : "";
        $conn_string .= ($this->password) ? " password=$this->password" : "";
        $conn_string .= ($this->database) ? " dbname=$this->database" : "";
        $conn_string .= ($this->port) ? " port=$this->port" : "";
        // Attempt to connect
        $this->fd = @pg_connect($conn_string);
        if (!$this->fd || pg_connection_status($this->fd) != PGSQL_CONNECTION_OK) {
            $this->setError(-1, "CONNECTION FAILURE");
            return;
        }
        // Check connections
        $this->is_connected = true;
		$this->password = md5($this->password);
        // Set utf8
        //$this ->_query('set client_encoding to uhc');
    }

    /**
     * @brief DB disconnection
     **/
    function close()
    {
        if (!$this->isConnected())
            return;
        @pg_close($this->fd);
    }

    /**
     * @brief Add quotes on the string variables in a query
     **/
    function addQuotes($string)
    {
        if (version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc())
            $string = stripslashes(str_replace("\\", "\\\\", $string));
        if (!is_numeric($string))
            $string = @pg_escape_string($string);
        return $string;
    }

    /**
     * @brief Begin transaction
     **/
    function begin()
    {
        if (!$this->isConnected() || $this->transaction_started == false)
            return;
        if ($this->_query($this->fd, 'BEGIN'))
            $this->transaction_started = true;
    }

    /**
     * @brief Rollback
     **/
    function rollback()
    {
        if (!$this->isConnected() || $this->transaction_started == false)
            return;
        if ($this->_query($this->fd, 'ROLLBACK'))
            $this->transaction_started = false;
    }

    /**
     * @brief Commits
     **/
    function commit()
    {
        if (!$this->isConnected() || $this->transaction_started == false)
            return;
        if ($this->_query($this->fd, 'COMMIT'))
            $this->transaction_started = false;
    }

    /**
     * @brief : Run a query and fetch the result
     *
     * query: run a query and return the result \n
     * fetch: NULL if no value is returned \n
     *        array object if rows are returned \n
     *        object if a row is returned \n
     *         return\n
     **/
    function _query($query)
    {
        if (!$this->isConnected())
            return;

        /*
        $l_query_array = explode(" ", $query);
        if ($l_query_array[0] = "update")
        {
        if (strtolower($l_query_array[2]) == "as")
        {
        $l_query_array[2] = "";
        $l_query_array[3] = "";
        $query = implode(" ",$l_query_array);
        }
        }
        else if ($l_query_array[0] = "delete") 
        {
        if (strtolower($l_query_array[3]) == "as")
        {
        $l_query_array[3] = "";
        $l_query_array[4] = "";            
        $query = implode(" ",$l_query_array);
        }
        }
        */
        // Notify to start a query execution
        $this->actStart($query);
        $arr = array('Hello', 'World!', 'Beautiful', 'Day!');
        // Run the query statement
        $result = @pg_query($this->fd, $query);
        // Error Check
        if (!$result) {
            //              var_dump($l_query_array);
            //var_dump($query);
            //die("\nin query statement\n");
            //var_dump(debug_backtrace());
            $this->setError(1, pg_last_error($this->fd));
        }
        // Notify to complete a query execution
        $this->actFinish();
        // Return result
        return $result;
    }

    /**
     * @brief Fetch results
     **/
    // TODO This is duplicate code - maybe we can find away to abastract the driver
    function _fetch($result, $arrayIndexEndValue = NULL)
    {
        if (!$this->isConnected() || $this->isError() || !$result)
            return;
        while ($tmp = pg_fetch_object($result)) {
            if($arrayIndexEndValue) $output[$arrayIndexEndValue--] = $tmp;
            else $output[] = $tmp;        	
        }
        if(count($output)==1){
          	if(isset($arrayIndexEndValue)) return $output; 
            else return $output[0];
        }        
        return $output;
    }

    /**
     * @brief Return sequence value incremented by 1(in postgresql, auto_increment is used in the sequence table only)
     **/
    function getNextSequence()
    {
        $query = sprintf("select nextval('%ssequence') as seq", $this->prefix);
        $result = $this->_query($query);
        $tmp = $this->_fetch($result);
        return $tmp->seq;
    }

    /**
     * @brief Return if a table already exists
     **/
    function isTableExists($target_name)
    {
        if ($target_name == "sequence")
            return true;
        $query = sprintf("SELECT tablename FROM pg_tables WHERE tablename = '%s%s' AND schemaname = current_schema()",
            $this->prefix, $this->addQuotes($target_name));

        $result = $this->_query($query);
        $tmp = $this->_fetch($result);
        if (!$tmp)
            return false;
        return true;
    }

    /**
     * @brief Add a column to a table
     **/
    function addColumn($table_name, $column_name, $type = 'number', $size = '', $default =
        NULL, $notnull = false)
    {
        $type = $this->column_type[$type];
        if (strtoupper($type) == 'INTEGER' || strtoupper($type) == 'BIGINT')
            $size = '';

        $query = sprintf("alter table %s%s add %s ", $this->prefix, $table_name, $column_name);

        if ($size)
            $query .= sprintf(" %s(%s) ", $type, $size);
        else
            $query .= sprintf(" %s ", $type);

        $this->_query($query);

        if (isset($default)) {
            $query = sprintf("alter table %s%s alter %s  set default '%s' ", $this->prefix, $table_name, $column_name, $default);
            $this->_query($query);
        }
        if ($notnull) {
            $query = sprintf("update %s%s set %s  = %s ", $this->prefix, $table_name, $column_name, $default);
            $this->_query($query);              
            $query = sprintf("alter table %s%s alter %s  set not null ", $this->prefix, $table_name, $column_name);
            $this->_query($query);
        }
    }


    /**
     * @brief Return column information of a table
     **/
    function isColumnExists($table_name, $column_name)
    {
        $query = sprintf("SELECT attname FROM pg_attribute WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = '%s%s') AND attname = '%s'",
            $this->prefix, strtolower($table_name), strtolower($column_name));

        // $query = sprintf("select column_name from information_schema.columns where table_schema = current_schema() and table_name = '%s%s' and column_name = '%s'", $this->prefix, $this->addQuotes($table_name), strtolower($column_name));
        $result = $this->_query($query);
        if ($this->isError()) {
            return;
        }
        $output = $this->_fetch($result);
        if ($output) {
            return true;
        }
        return false;
    }

    /**
     * @brief Add an index to a table
     * $target_columns = array(col1, col2)
     * $is_unique? unique : none
     **/
    function addIndex($table_name, $index_name, $target_columns, $is_unique = false)
    {
        if (!is_array($target_columns))
            $target_columns = array($target_columns);

        if (strpos($table_name, $this->prefix) === false)
            $table_name = $this->prefix . $table_name;
        // Use a tablename before an index name to avoid defining the same index
        $index_name = $table_name . $index_name;

        $query = sprintf("create %s index %s on %s (%s);", $is_unique ? 'unique' : '', $index_name,
            $table_name, implode(',', $target_columns));
        $this->_query($query);
    }

    /**
     * @brief Delete a column from a table
     **/
    function dropColumn($table_name, $column_name)
    {
        $query = sprintf("alter table %s%s drop %s ", $this->prefix, $table_name, $column_name);
        $this->_query($query);
    }

    /**
     * @brief Drop an index from a table
     **/
    function dropIndex($table_name, $index_name, $is_unique = false)
    {
        if (strpos($table_name, $this->prefix) === false)
            $table_name = $this->prefix . $table_name;
        // Use a tablename before an index name to avoid defining the same index
        $index_name = $table_name . $index_name;

        $query = sprintf("drop index %s", $index_name);
        $this->_query($query);
    }


    /**
     * @brief Return index information of a table
     **/
    function isIndexExists($table_name, $index_name)
    {
        if (strpos($table_name, $this->prefix) === false)
            $table_name = $this->prefix . $table_name;
        // Use a tablename before an index name to avoid defining the same index
        $index_name = $table_name . $index_name;

        //$query = sprintf("show indexes from %s%s where key_name = '%s' ", $this->prefix, $table_name, $index_name);
        $query = sprintf("select indexname from pg_indexes where schemaname = current_schema() and tablename = '%s' and indexname = '%s'",
            $table_name, strtolower($index_name));
        $result = $this->_query($query);
        if ($this->isError())
            return;
        $output = $this->_fetch($result);

        if ($output) {
            return true;
        }
        //                var_dump($query);
        //                die(" no index");
        return false;
    }

    /**
     * @brief Create a table by using xml file
     **/
    function createTableByXml($xml_doc)
    {
        return $this->_createTable($xml_doc);
    }

    /**
     * @brief Create a table by using xml file
     **/
    function createTableByXmlFile($file_name)
    {
        if (!file_exists($file_name))
            return;
        // read xml file
        $buff = FileHandler::readFile($file_name);
        return $this->_createTable($buff);
    }

    /**
     * @brief generate a query statement to create a table by using schema xml
     *
     * type : number, varchar, text, char, date, \n
     * opt : notnull, default, size\n
     * index : primary key, index, unique\n
     **/
    function _createTable($xml_doc)
    {
        // xml parsing
        $oXml = new XmlParser();
        $xml_obj = $oXml->parse($xml_doc);
        // Create a table schema
        $table_name = $xml_obj->table->attrs->name;

        if ($table_name == 'sequence') {
            $query = sprintf('create sequence %s', $this->prefix . $table_name);
            return $this->_query($query);
        }

        if ($this->isTableExists($table_name))
            return;
        $table_name = $this->prefix . $table_name;

        if (!is_array($xml_obj->table->column))
            $columns[] = $xml_obj->table->column;
        else
            $columns = $xml_obj->table->column;

        foreach ($columns as $column) {
            $name = $column->attrs->name;
            $type = $column->attrs->type;
            $size = $column->attrs->size;
            $notnull = $column->attrs->notnull;
            $primary_key = $column->attrs->primary_key;
            $index = $column->attrs->index;
            $unique = $column->attrs->unique;
            $default = $column->attrs->default;
            $auto_increment = $column->attrs->auto_increment;

            if ($type == "bignumber" || $type == "number")
                $size = 0;

            $column_schema[] = sprintf('%s %s%s %s %s', $name, $this->column_type[$type], $size ?
                '(' . $size . ')' : '', isset($default) ? "default '" . $default . "'" : '', $notnull ?
                'not null' : '');

            if ($primary_key)
                $primary_list[] = $name;
            else
                if ($unique)
                    $unique_list[$unique][] = $name;
                else
                    if ($index)
                        $index_list[$index][] = $name;
        }

        if (count($primary_list)) {
            $column_schema[] = sprintf("primary key (%s)", implode($primary_list, ','));
        }

        if (count($unique_list)) {
            foreach ($unique_list as $key => $val) {
                $column_schema[] = sprintf("unique (%s)", implode($val, ','));
            }
        }


        $schema = sprintf('create table %s (%s%s);', $this->addQuotes($table_name), "\n",
            implode($column_schema, ",\n"));

        $output = $this->_query($schema);

        if (count($index_list)) {
            foreach ($index_list as $key => $val) {
                if (!$this->isIndexExists($table_name, $key))
                    $this->addIndex($table_name, $key, $val);
            }
        }

        if (!$output)
            return false;

    }

 
    /**
     * @brief Handle the insertAct
     **/
    function _executeInsertAct($queryObject)
    {
        $query = $this->getInsertSql($queryObject);
        if(is_a($query, 'Object')) return;
    
        return $this->_query($query);
    }

    /**
     * @brief Handle updateAct
     **/
    function _executeUpdateAct($queryObject)
    {
        $query = $this->getUpdateSql($queryObject);
        if(is_a($query, 'Object')) return;
        return $this->_query($query);
    }

    /**
     * @brief Handle deleteAct
     **/
    function _executeDeleteAct($queryObject)
    {
        $query = $this->getDeleteSql($queryObject);
			
      	if(is_a($query, 'Object')) return;
        return $this->_query($query);
    }

    /**
     * 
     * override
     * @param $queryObject
     */
    function getSelectSql($query){
		$select = $query->getSelectString();
		if($select == '') return new Object(-1, "Invalid query");
		$select = 'SELECT ' .$select;
		
		$from = $query->getFromString();
		if($from == '') return new Object(-1, "Invalid query");
		$from = ' FROM '.$from;
		
		$where = $query->getWhereString();
		if($where != '') $where = ' WHERE ' . $where;
						
		$groupBy = $query->getGroupByString();
		if($groupBy != '') $groupBy = ' GROUP BY ' . $groupBy;
		
		$orderBy = $query->getOrderByString();
		if($orderBy != '') $orderBy = ' ORDER BY ' . $orderBy;
		
	 	$limit = $query->getLimitString();
	 	if($limit != '') $limit = ' LIMIT ' . $query->getLimit()->getLimit() . ' OFFSET ' . $query->getLimit()->getOffset();

	 	return $select . ' ' . $from . ' ' . $where . ' ' . $groupBy . ' ' . $orderBy . ' ' . $limit;    	
    }
    
    /**
     * @brief Handle selectAct
     *
     * In order to get a list of pages easily when selecting \n
     * it supports a method as navigation
     **/
    function _executeSelectAct($queryObject)
    {
		$query = $this->getSelectSql($queryObject);
			
			if(is_a($query, 'Object')) return;
			
			$query .= (__DEBUG_QUERY__&1 && $queryObject->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';
           
            // TODO Add support for click count
            // TODO Add code for pagination           
            
			$result = $this->_query ($query);
			if ($this->isError ()) {
				if ($limit && $output->limit->isPageHandler()){
					$buff = new Object ();
					$buff->total_count = 0;
					$buff->total_page = 0;
					$buff->page = 1;
					$buff->data = array ();
					$buff->page_navigation = new PageHandler (/*$total_count*/0, /*$total_page*/1, /*$page*/1, /*$page_count*/10);//default page handler values
					return $buff;
				}else	
					return;
			}

		 	if ($queryObject->getLimit() && $queryObject->getLimit()->isPageHandler()) {
		 		// Total count
		 		$count_query = sprintf('select count(*) as "count" %s %s', 'FROM ' . $queryObject->getFromString(), ($queryObject->getWhereString() === '' ? '' : ' WHERE '. $queryObject->getWhereString()));
				if ($queryObject->getGroupByString() != '') {
					$count_query = sprintf('select count(*) as "count" from (%s) xet', $count_query);
				}

				$count_query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf (' '.$this->comment_syntax, $this->query_id):'';
				$result_count = $this->_query($count_query);
				$count_output = $this->_fetch($result_count);
				$total_count = (int)$count_output->count;
		 		
				// Total pages
				if ($total_count) {
					$total_page = (int) (($total_count - 1) / $queryObject->getLimit()->list_count) + 1;
				}	else	$total_page = 1;
		 		
				
		 		$virtual_no = $total_count - ($queryObject->getLimit()->page - 1) * $queryObject->getLimit()->list_count;
		 		$data = $this->_fetch($result, $virtual_no);

		 		$buff = new Object ();
				$buff->total_count = $total_count;
				$buff->total_page = $total_page;
				$buff->page = $queryObject->getLimit()->page;
				$buff->data = $data;
				$buff->page_navigation = new PageHandler($total_count, $total_page, $queryObject->getLimit()->page, $queryObject->getLimit()->page_count);				
			}else{
				$data = $this->_fetch($result);
				$buff = new Object ();
				$buff->data = $data;	
			}

			return $buff;        
    }
    
    function getParser(){
    	return new DBParser('"');
    }
}

return new DBPostgresql;
?>
