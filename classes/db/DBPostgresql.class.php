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
     * @brief DB Connection
     **/
    function __connect($connection)
    {
        // the connection string for PG
        $conn_string = "";
        // Create connection string
        $conn_string .= ($connection["db_hostname"]) ? ' host='.$connection["db_hostname"] : "";
        $conn_string .= ($connection["db_userid"]) ? " user=" . $connection["db_userid"] : "";
        $conn_string .= ($connection["db_password"]) ? " password=" . $connection["db_password"] : "";
        $conn_string .= ($connection["db_database"]) ? " dbname=" . $connection["db_database"] : "";
        $conn_string .= ($connection["db_port"]) ? " port=" . $connection["db_port"] : "";

        // Attempt to connect
        $result = @pg_connect($conn_string);
        if (!$result || pg_connection_status($result) != PGSQL_CONNECTION_OK) {
            $this->setError(-1, "CONNECTION FAILURE");
            return;
        }
        return $result;
    }

    /**
     * @brief DB disconnection
     **/
    function _close($connection)
    {
        @pg_close($connection);
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
    function _begin()
    {
        $connection = $this->_getConnection('master');
        if (!$this->_query('BEGIN')) return false;
        return true;
    }

    /**
     * @brief Rollback
     **/
    function _rollback()
    {
        if (!$this->_query('ROLLBACK')) return false;
        return true;
    }

    /**
     * @brief Commits
     **/
    function _commit()
    {
        if (!$this->_query('COMMIT')) return false;
        return true;
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
    function __query($query, $connection)
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
        // $arr = array('Hello', 'World!', 'Beautiful', 'Day!');
        // Run the query statement
        $result = @pg_query($connection, $query);
        // Error Check
        if (!$result) {
            //              var_dump($l_query_array);
            //var_dump($query);
            //die("\nin query statement\n");
            //var_dump(debug_backtrace());
            $this->setError(1, pg_last_error($connection));
        }
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
                $limitObject = $query->getLimit();
	 	if($limit != '') $limit = ' LIMIT ' . $limitObject->getLimit() . ' OFFSET ' . $limitObject->getOffset();

	 	return $select . ' ' . $from . ' ' . $where . ' ' . $groupBy . ' ' . $orderBy . ' ' . $limit;
    }

    /**
     * @brief Handle selectAct
     *
     * In order to get a list of pages easily when selecting \n
     * it supports a method as navigation
     **/
    function _executeSelectAct($queryObject, $connection)
    {
		$query = $this->getSelectSql($queryObject);

			if(is_a($query, 'Object')) return;

			$query .= (__DEBUG_QUERY__&1 && $queryObject->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';

            // TODO Add support for click count
            // TODO Add code for pagination

			$result = $this->_query ($query, $connection);
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

                        $limit = $queryObject->getLimit();
		 	if ($limit && $limit->isPageHandler()) {
		 		// Total count
				$temp_where = $queryObject->getWhereString(true, false);
		 		$count_query = sprintf('select count(*) as "count" %s %s', 'FROM ' . $queryObject->getFromString(), ($temp_where === '' ? '' : ' WHERE '. $temp_where));
				if ($queryObject->getGroupByString() != '') {
					$count_query = sprintf('select count(*) as "count" from (%s) xet', $count_query);
				}

				$count_query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf (' '.$this->comment_syntax, $this->query_id):'';
				$result_count = $this->_query($count_query, $connection);
				$count_output = $this->_fetch($result_count);
				$total_count = (int)$count_output->count;

				// Total pages
				if ($total_count) {
					$total_page = (int) (($total_count - 1) / $limit->list_count) + 1;
				}	else	$total_page = 1;


		 		$virtual_no = $total_count - ($limit->page - 1) * $limit->list_count;
		 		$data = $this->_fetch($result, $virtual_no);

		 		$buff = new Object ();
				$buff->total_count = $total_count;
				$buff->total_page = $total_page;
				$buff->page = $limit->page->getValue();
				$buff->data = $data;
				$buff->page_navigation = new PageHandler($total_count, $total_page, $limit->page->getValue(), $limit->page_count);
			}else{
				$data = $this->_fetch($result);
				$buff = new Object ();
				$buff->data = $data;
			}

			return $buff;
    }

    function getParser(){
    	return new DBParser('"', '"', $this->prefix);
    }
}

return new DBPostgresql;
?>
