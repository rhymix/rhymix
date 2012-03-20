<?php
    /**
     * @class DBSqlite3_pdo
     * @author NHN (developers@xpressengine.com)
     * @brief class to use SQLite3 with PDO
     * @version 0.1
     **/

    class DBSqlite3_pdo extends DB {

        /**
         * DB information
         **/
        var $database = NULL; ///< database
        var $prefix   = 'xe'; // /< prefix of a tablename (many XEs can be installed in a single DB)
		var $comment_syntax = '/* %s */';

        /**
         * Variables for using PDO
         **/
        var $handler      = NULL;
        var $stmt         = NULL;
        var $bind_idx     = 0;
        var $bind_vars    = array();

        /**
         * @brief column type used in sqlite3
         *
         * column_type should be replaced for each DBMS properly
         * because column_type uses a commonly defined type in schema/query xml files
         **/
        var $column_type = array(
            'bignumber' => 'INTEGER',
            'number'    => 'INTEGER',
            'varchar'   => 'VARCHAR',
            'char'      => 'CHAR',
            'text'      => 'TEXT',
            'bigtext'   => 'TEXT',
            'date'      => 'VARCHAR(14)',
            'float'     => 'REAL',
        );

        /**
         * @brief constructor
         **/
        function DBSqlite3_pdo() {
            $this->_setDBInfo();
            $this->_connect();
        }

		/**
		 * @brief create an instance of this class
		 */
		function create()
		{
			return new DBSqlite3_pdo;
		}

        /**
         * @brief Return if installable
         **/
        function isSupported() {
            return class_exists('PDO');
        }

        function isConnected() {
            return $this->is_connected;
        }

        /**
         * @brief DB settings and connect/close
         **/
        function _setDBInfo() {
            $db_info = Context::getDBInfo();
            $this->database = $db_info->master_db["db_database"];
            $this->prefix = $db_info->master_db["db_table_prefix"];
            //if(!substr($this->prefix,-1)!='_') $this->prefix .= '_';
        }

        /**
         * @brief DB Connection
         **/
        function _connect() {
            // override if db information not exists
            if(!$this->database) return;

            // Attempt to access the database file
            try {
                    // PDO is only supported with PHP5,
                    // so it is allowed to use try~catch statment in this class.
                    $this->handler = new PDO('sqlite:'.$this->database);
            } catch (PDOException $e) {
                    $this->setError(-1, 'Connection failed: '.$e->getMessage());
                    $this->is_connected = false;
                    return;
            }

            // Check connections
            $this->is_connected = true;
            $this->password = md5($this->password);
        }

        /**
         * @brief disconnect to DB
         **/
        function close() {
            if(!$this->is_connected) return;
            $this->commit();
        }

        /**
         * @brief Begin a transaction
         **/
        function begin() {
            if(!$this->is_connected || $this->transaction_started) return;
            if($this->handler->beginTransaction()) $this->transaction_started = true;
        }

        /**
         * @brief Rollback
         **/
        function rollback() {
            if(!$this->is_connected || !$this->transaction_started) return;
            $this->handler->rollBack();
            $this->transaction_started = false;
        }

        /**
         * @brief Commit
         **/
        function commit($force = false) {
            if(!$force && (!$this->is_connected || !$this->transaction_started)) return;
            try {
                $this->handler->commit();
            }
            catch(PDOException $e){
                // There was no transaction started, so just continue.
                error_log($e->getMessage());
            }
            $this->transaction_started = false;
        }

        /**
         * @brief Add or change quotes to the query string variables
         **/
        function addQuotes($string) {
            if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)) $string = str_replace("'","''",$string);
            return $string;
        }

        /**
         * @brief : Prepare a query statement
         **/
        function _prepare($query) {
            if(!$this->is_connected) return;

            // notify to start a query execution
            $this->actStart($query);

            $this->stmt = $this->handler->prepare($query);

            if($this->handler->errorCode() != '00000') {
                $this->setError($this->handler->errorCode(), print_r($this->handler->errorInfo(),true));
                $this->actFinish();
            }
            $this->bind_idx = 0;
            $this->bind_vars = array();
        }

        /**
         * @brief : Binding params in stmt
         **/
        function _bind($val) {
            if(!$this->is_connected || !$this->stmt) return;

            $this->bind_idx ++;
            $this->bind_vars[] = $val;
            $this->stmt->bindParam($this->bind_idx, $val);
        }

        /**
         * @brief : execute the prepared statement
         **/
        function _execute() {
            if(!$this->is_connected || !$this->stmt) return;

            $this->stmt->execute();

            if($this->stmt->errorCode() === '00000') {
                $output = null;
                while($tmp = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
                    unset($obj);
                    foreach($tmp as $key => $val) {
                        $pos = strpos($key, '.');
                        if($pos) $key = substr($key, $pos+1);
                        $obj->{$key} = str_replace("''","'",$val);
                    }
                    $output[] = $obj;
                }
            } else {
                $this->setError($this->stmt->errorCode(),print_r($this->stmt->errorInfo(),true));
            }

            $this->stmt = null;
            $this->actFinish();

            if(is_array($output) && count($output)==1) return $output[0];
            return $output;
        }

        /**
         * @brief Return the sequence value incremented by 1
         **/
        function getNextSequence() {
            $query = sprintf("insert into %ssequence (seq) values (NULL)", $this->prefix);
            $this->_prepare($query);
            $result = $this->_execute();
            $sequence = $this->handler->lastInsertId();
            if($sequence % 10000 == 0) {
              $query = sprintf("delete from  %ssequence where seq < %d", $this->prefix, $sequence);
              $this->_prepare($query);
              $result = $this->_execute();
            }

            return $sequence;
        }

        /**
         * @brief return if the table already exists
         **/
        function isTableExists($target_name) {
            $query = sprintf('pragma table_info(%s%s)', $this->prefix, $target_name);
            $this->_prepare($query);
            if(!$this->_execute()) return false;
            return true;
        }

        /**
         * @brief Add a column to a table
         **/
        function addColumn($table_name, $column_name, $type='number', $size='', $default = '', $notnull=false) {
            $type = $this->column_type[$type];
            if(strtoupper($type)=='INTEGER') $size = '';

            $query = sprintf("alter table %s%s add %s ", $this->prefix, $table_name, $column_name);
            if($size) $query .= sprintf(" %s(%s) ", $type, $size);
            else $query .= sprintf(" %s ", $type);
            if($default) $query .= sprintf(" default '%s' ", $default);
            if($notnull) $query .= " not null ";

            $this->_prepare($query);
            return $this->_execute();
        }

        /**
         * @brief Remove a column from a table
         **/
        function dropColumn($table_name, $column_name) {
            $query = sprintf("alter table %s%s drop column %s ", $this->prefix, $table_name, $column_name);
            $this->_query($query);
        }

        /**
         * @brief Return column information of a table
         **/
        function isColumnExists($table_name, $column_name) {
            $query = sprintf("pragma table_info(%s%s)", $this->prefix, $table_name);
            $this->_prepare($query);
            $output = $this->_execute();

            if($output) {
                $column_name = strtolower($column_name);
                foreach($output as $key => $val) {
                    $name = strtolower($val->name);
                    if($column_name == $name) return true;
                }
            }
            return false;
        }

        /**
         * @brief Add an index to a table
         * $target_columns = array(col1, col2)
         * $is_unique? unique : none
         **/
        function addIndex($table_name, $index_name, $target_columns, $is_unique = false) {
            if(!is_array($target_columns)) $target_columns = array($target_columns);

            $key_name = sprintf('%s%s_%s', $this->prefix, $table_name, $index_name);

            $query = sprintf('CREATE %s INDEX %s ON %s%s (%s)', $is_unique?'UNIQUE':'', $key_name, $this->prefix, $table_name, implode(',',$target_columns));
            $this->_prepare($query);
            $this->_execute();
        }

        /**
         * @brief Drop an index from a table
         **/
        function dropIndex($table_name, $index_name, $is_unique = false) {
            $key_name = sprintf('%s%s_%s', $this->prefix, $table_name, $index_name);
            $query = sprintf("DROP INDEX %s", $this->prefix, $table_name, $key_name);
            $this->_query($query);
        }

        /**
         * @brief Return index information of a table
         **/
        function isIndexExists($table_name, $index_name) {
            $key_name = sprintf('%s%s_%s', $this->prefix, $table_name, $index_name);

            $query = sprintf("pragma index_info(%s)", $key_name);
            $this->_prepare($query);
            $output = $this->_execute();
            if(!$output) return false;
            return true;
        }

        /**
         * @brief create a table from xml file
         **/
        function createTableByXml($xml_doc) {
            return $this->_createTable($xml_doc);
        }

        /**
         * @brief create a table from xml file
         **/
        function createTableByXmlFile($file_name) {
            if(!file_exists($file_name)) return;
            // read xml file
            $buff = FileHandler::readFile($file_name);
            return $this->_createTable($buff);
        }

        /**
         * @brief generate a query to create a table using the schema xml
         *
         * type : number, varchar, text, char, date, \n
         * opt : notnull, default, size\n
         * index : primary key, index, unique\n
         **/
        function _createTable($xml_doc) {
            // xml parsing
            $oXml = new XmlParser();
            $xml_obj = $oXml->parse($xml_doc);
            // Create a table schema
            $table_name = $xml_obj->table->attrs->name;
            if($this->isTableExists($table_name)) return;
            $table_name = $this->prefix.$table_name;

            if(!is_array($xml_obj->table->column)) $columns[] = $xml_obj->table->column;
            else $columns = $xml_obj->table->column;

            foreach($columns as $column) {
                $name = $column->attrs->name;
                $type = $column->attrs->type;
                if(strtoupper($this->column_type[$type])=='INTEGER') $size = '';
                else $size = $column->attrs->size;
                $notnull = $column->attrs->notnull;
                $primary_key = $column->attrs->primary_key;
                $index = $column->attrs->index;
                $unique = $column->attrs->unique;
                $default = $column->attrs->default;
                $auto_increment = $column->attrs->auto_increment;

                if($auto_increment) {
                    $column_schema[] = sprintf('%s %s PRIMARY KEY %s',
                        $name,
                        $this->column_type[$type],
                        $auto_increment?'AUTOINCREMENT':''
                    );
                } else {
                    $column_schema[] = sprintf('%s %s%s %s %s %s',
                        $name,
                        $this->column_type[$type],
                        $size?'('.$size.')':'',
                        $notnull?'NOT NULL':'',
                        $primary_key?'PRIMARY KEY':'',
                        isset($default)?"DEFAULT '".$default."'":''
                    );
                }

                if($unique) $unique_list[$unique][] = $name;
                else if($index) $index_list[$index][] = $name;
            }

            $schema = sprintf('CREATE TABLE %s (%s%s) ;', $table_name," ", implode($column_schema,", "));
            $this->_prepare($schema);
            $this->_execute();
            if($this->isError()) return;

            if(count($unique_list)) {
                foreach($unique_list as $key => $val) {
                    $query = sprintf('CREATE UNIQUE INDEX %s_%s ON %s (%s)', $this->addQuotes($table_name), $key, $this->addQuotes($table_name), implode(',',$val));
                    $this->_prepare($query);
                    $this->_execute();
                    if($this->isError()) $this->rollback();
                }
            }

            if(count($index_list)) {
                foreach($index_list as $key => $val) {
                    $query = sprintf('CREATE INDEX %s_%s ON %s (%s)', $this->addQuotes($table_name), $key, $this->addQuotes($table_name), implode(',',$val));
                    $this->_prepare($query);
                    $this->_execute();
                    if($this->isError()) $this->rollback();
                }
            }
        }

    function _getConnection($type = null){
        return null;
    }

    /**
     * @brief insertAct
     * */
    function _executeInsertAct($queryObject) {
        $query = $this->getInsertSql($queryObject);
        if (is_a($query, 'Object'))
            return;

        $this->_prepare($query);

        $val_count = count($val_list);
        for ($i = 0; $i < $val_count; $i++)
            $this->_bind($val_list[$i]);

        return $this->_execute();
    }

    /**
     * @brief updateAct
     * */
    function _executeUpdateAct($queryObject) {
        $query = $this->getUpdateSql($queryObject);
        if (is_a($query, 'Object'))
            return;

        $this->_prepare($query);
        return $this->_execute();
    }

    /**
     * @brief deleteAct
     * */
    function _executeDeleteAct($queryObject) {
        $query = $this->getDeleteSql($queryObject);
        if (is_a($query, 'Object'))
            return;

        $this->_prepare($query);
        return $this->_execute();
    }

    /**
     * @brief selectAct
     *
     * To fetch a list of the page conveniently when selecting, \n
     * navigation method supported
     * */
    function _executeSelectAct($queryObject) {
        $query = $this->getSelectSql($queryObject);
        if (is_a($query, 'Object'))
            return;

        $this->_prepare($query);
        $data = $this->_execute();
        // TODO isError is called twice
        if ($this->isError())
            return;

        if ($this->isError())
            return $this->queryError($queryObject);
        else
            return $this->queryPageLimit($queryObject, $data);
    }

    function queryError($queryObject) {
        if ($queryObject->getLimit() && $queryObject->getLimit()->isPageHandler()) {
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

    function queryPageLimit($queryObject, $data) {
        if ($queryObject->getLimit() && $queryObject->getLimit()->isPageHandler()) {
            // Total count
	    $temp_where = $queryObject->getWhereString(true, false);
            $count_query = sprintf('select count(*) as "count" %s %s', 'FROM ' . $queryObject->getFromString(), ($temp_where === '' ? '' : ' WHERE ' . $temp_where));
            if ($queryObject->getGroupByString() != '') {
                $count_query = sprintf('select count(*) as "count" from (%s) xet', $count_query);
            }

            $count_query .= ( __DEBUG_QUERY__ & 1 && $output->query_id) ? sprintf(' ' . $this->comment_syntax, $this->query_id) : '';
            $this->_prepare($count_query);
            $count_output = $this->_execute();
            $total_count = (int) $count_output->count;

            $list_count = $queryObject->getLimit()->list_count->getValue();
            if (!$list_count) $list_count = 20;
            $page_count = $queryObject->getLimit()->page_count->getValue();
            if (!$page_count) $page_count = 10;
            $page = $queryObject->getLimit()->page->getValue();
            if (!$page) $page = 1;
            // Total pages
            if ($total_count) {
                $total_page = (int) (($total_count - 1) / $list_count) + 1;
            } else
                $total_page = 1;

            // check the page variables
            if ($page > $total_page) {
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

            $this->_prepare($this->getSelectPageSql($queryObject, true, $start_count, $list_count));
            $this->stmt->execute();
            if ($this->stmt->errorCode() != '00000') {
                $this->setError($this->stmt->errorCode(), print_r($this->stmt->errorInfo(), true));
                $this->actFinish();
                return $buff;
            }

            $output = null;
            $virtual_no = $total_count - ($page - 1) * $list_count;
            //$data = $this->_fetch($result, $virtual_no);
            while ($tmp = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
                unset($obj);
                foreach ($tmp as $key => $val) {
                    $pos = strpos($key, '.');
                    if ($pos)
                        $key = substr($key, $pos + 1);
                    $obj->{$key} = $val;
                }
		$datatemp[$virtual_no--] = $obj;
            }

            $this->stmt = null;
            $this->actFinish();

            $buff = new Object ();
            $buff->total_count = $total_count;
            $buff->total_page = $total_page;
            $buff->page = $page;
            $buff->data = $datatemp;
            $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
        }else {
            //$data = $this->_fetch($result);
            $buff = new Object ();
            $buff->data = $data;
        }
        return $buff;
    }

    function getSelectPageSql($query, $with_values = true, $start_count = 0, $list_count = 0) {

        $select = $query->getSelectString($with_values);
        if ($select == '')
            return new Object(-1, "Invalid query");
        $select = 'SELECT ' . $select;

        $from = $query->getFromString($with_values);
        if ($from == '')
            return new Object(-1, "Invalid query");
        $from = ' FROM ' . $from;

        $where = $query->getWhereString($with_values);
        if ($where != '')
            $where = ' WHERE ' . $where;

        $groupBy = $query->getGroupByString();
        if ($groupBy != '')
            $groupBy = ' GROUP BY ' . $groupBy;

        $orderBy = $query->getOrderByString();
        if ($orderBy != '')
            $orderBy = ' ORDER BY ' . $orderBy;

        $limit = $query->getLimitString();
        if ($limit != '' && $query->getLimit()) {
            $limit = sprintf(' LIMIT %d, %d',$start_count, $list_count);
        }

        return $select . ' ' . $from . ' ' . $where . ' ' . $groupBy . ' ' . $orderBy . ' ' . $limit;
    }

    function getParser() {
        return new DBParser('"', '"', $this->prefix);
    }

    function getUpdateSql($query, $with_values = true, $with_priority = false){
                    $columnsList = $query->getUpdateString($with_values);
                    if($columnsList == '') return new Object(-1, "Invalid query");

                    $tableName = $query->getFirstTableName();
    		    if($tableName == '') return new Object(-1, "Invalid query");

                    $where = $query->getWhereString($with_values);
                    if($where != '') $where = ' WHERE ' . $where;

                    $priority = $with_priority?$query->getPriority():'';

                    return "UPDATE $priority $tableName SET $columnsList ".$where;
            }

   		function getDeleteSql($query, $with_values = true, $with_priority = false){
			$sql = 'DELETE ';

        		$tables = $query->getTables();
                        $from = $tables[0]->getName();
			$sql .= ' FROM '.$from;

			$where = $query->getWhereString($with_values);
			if($where != '') $sql .= ' WHERE ' . $where;

			return $sql;
		}
}

return new DBSqlite3_pdo;
?>
