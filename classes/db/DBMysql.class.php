<?php
    /**
     * @class DBMysql
     * @author NHN (developers@xpressengine.com)
     * @brief Class to use MySQL DBMS
     * @version 0.1
     *
     * mysql handling class
     **/

    class DBMysql extends DB {

        /**
         * @brief Connection information for Mysql DB
         **/
        var $prefix   = 'xe_'; // / <prefix of a tablename (One or more XEs can be installed in a single DB)
        var $comment_syntax = '/* %s */';

        /**
         * @brief Column type used in MySQL
         *
         * Becasue a common column type in schema/query xml is used for colum_type,
         * it should be replaced properly for each DBMS
         **/
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
         * @brief constructor
         **/
        function DBMysql() {
			$this->_setDBInfo();
            $this->_connect();
        }

		function create() {
			return new DBMysql;
		}

        /**
         * @brief Return if it is installable
         **/
        function isSupported() {
            if(!function_exists('mysql_connect')) return false;
            return true;
        }

        /**
         * @brief DB Connection
         **/
        function __connect($connection) {
            // Ignore if no DB information exists
           if (strpos($connection["db_hostname"], ':') === false && $connection["db_port"])
                $connection["db_hostname"] .= ':' . $connection["db_port"];

            // Attempt to connect
            $result = @mysql_connect($connection["db_hostname"], $connection["db_userid"], $connection["db_password"]);

            if(mysql_error()) {
                $this->setError(mysql_errno(), mysql_error());
                return;
            }
            // Error appears if the version is lower than 4.1
            if(mysql_get_server_info($result)<"4.1") {
                $this->setError(-1, "XE cannot be installed under the version of mysql 4.1. Current mysql version is ".mysql_get_server_info());
                return;
            }
            // select db
            @mysql_select_db($connection["db_database"], $result);
            if(mysql_error()) {
                $this->setError(mysql_errno(), mysql_error());
                return;
            }

            return $result;
        }

        function _afterConnect($connection){
            // Set utf8 if a database is MySQL
            $this->_query("set names 'utf8'", $connection);
        }

        /**
         * @brief DB disconnection
         **/
        function _close($connection) {
            @mysql_close($connection);
        }

        /**
         * @brief Add quotes on the string variables in a query
         **/
        function addQuotes($string) {
            if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)) $string = @mysql_real_escape_string($string);
            return $string;
        }

        /**
         * @brief Begin transaction
         **/
        function _begin() {
            return true;
        }

        /**
         * @brief Rollback
         **/
        function _rollback() {
            return true;
        }

        /**
         * @brief Commits
         **/
        function _commit() {
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
        function __query($query, $connection) {
            // Run the query statement
            $result = mysql_query($query, $connection);
            // Error Check
            if(mysql_error($connection)) $this->setError(mysql_errno($connection), mysql_error($connection));
            // Return result
            return $result;
        }

        /**
         * @brief Fetch results
         **/
        function _fetch($result, $arrayIndexEndValue = NULL) {
			$output = array();
            if(!$this->isConnected() || $this->isError() || !$result) return $output;
            while($tmp = $this->db_fetch_object($result)) {
            	if($arrayIndexEndValue) $output[$arrayIndexEndValue--] = $tmp;
                else $output[] = $tmp;
            }
            if(count($output)==1){
            	if(isset($arrayIndexEndValue)) return $output;
            	else return $output[0];
            }
            $this->db_free_result($result);
            return $output;
        }

        /**
         * @brief Return sequence value incremented by 1(auto_increment is used in sequence table only in MySQL)
         **/
        function getNextSequence() {
            $query = sprintf("insert into `%ssequence` (seq) values ('0')", $this->prefix);
            $this->_query($query);
            $sequence = $this->db_insert_id();
            if($sequence % 10000 == 0) {
              $query = sprintf("delete from  `%ssequence` where seq < %d", $this->prefix, $sequence);
              $this->_query($query);
            }

            return $sequence;
        }

        /**
         * @brief Function to obtain mysql old password(mysql only)
         **/
        function isValidOldPassword($password, $saved_password) {
            $query = sprintf("select password('%s') as password, old_password('%s') as old_password", $this->addQuotes($password), $this->addQuotes($password));
            $result = $this->_query($query);
            $tmp = $this->_fetch($result);
            if($tmp->password == $saved_password || $tmp->old_password == $saved_password) return true;
            return false;
        }

        /**
         * @brief Return if a table already exists
         **/
        function isTableExists($target_name) {
            $query = sprintf("show tables like '%s%s'", $this->prefix, $this->addQuotes($target_name));
            $result = $this->_query($query);
            $tmp = $this->_fetch($result);
            if(!$tmp) return false;
            return true;
        }

        /**
         * @brief Add a column to a table
         **/
        function addColumn($table_name, $column_name, $type='number', $size='', $default = '', $notnull=false) {
            $type = $this->column_type[$type];
            if(strtoupper($type)=='INTEGER') $size = '';

            $query = sprintf("alter table `%s%s` add `%s` ", $this->prefix, $table_name, $column_name);
            if($size) $query .= sprintf(" %s(%s) ", $type, $size);
            else $query .= sprintf(" %s ", $type);
            if($default) $query .= sprintf(" default '%s' ", $default);
            if($notnull) $query .= " not null ";

            $this->_query($query);
        }

        /**
         * @brief Delete a column from a table
         **/
        function dropColumn($table_name, $column_name) {
            $query = sprintf("alter table `%s%s` drop `%s` ", $this->prefix, $table_name, $column_name);
            $this->_query($query);
        }

        /**
         * @brief Return column information of a table
         **/
        function isColumnExists($table_name, $column_name) {
            $query = sprintf("show fields from `%s%s`", $this->prefix, $table_name);
            $result = $this->_query($query);
            if($this->isError()) return;
            $output = $this->_fetch($result);
            if($output) {
                $column_name = strtolower($column_name);
                foreach($output as $key => $val) {
                    $name = strtolower($val->Field);
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

            $query = sprintf("alter table `%s%s` add %s index `%s` (%s);", $this->prefix, $table_name, $is_unique?'unique':'', $index_name, implode(',',$target_columns));
            $this->_query($query);
        }

        /**
         * @brief Drop an index from a table
         **/
        function dropIndex($table_name, $index_name, $is_unique = false) {
            $query = sprintf("alter table `%s%s` drop index `%s`", $this->prefix, $table_name, $index_name);
            $this->_query($query);
        }


        /**
         * @brief Return index information of a table
         **/
        function isIndexExists($table_name, $index_name) {
            //$query = sprintf("show indexes from %s%s where key_name = '%s' ", $this->prefix, $table_name, $index_name);
            $query = sprintf("show indexes from `%s%s`", $this->prefix, $table_name);
            $result = $this->_query($query);
            if($this->isError()) return;
            $output = $this->_fetch($result);
            if(!$output) return;
            if(!is_array($output)) $output = array($output);

            for($i=0;$i<count($output);$i++) {
                if($output[$i]->Key_name == $index_name) return true;
            }
            return false;
        }

        /**
         * @brief Create a table by using xml file
         **/
        function createTableByXml($xml_doc) {
            return $this->_createTable($xml_doc);
        }

        /**
         * @brief Create a table by using xml file
         **/
        function createTableByXmlFile($file_name) {
            if(!file_exists($file_name)) return;
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
                $size = $column->attrs->size;
                $notnull = $column->attrs->notnull;
                $primary_key = $column->attrs->primary_key;
                $index = $column->attrs->index;
                $unique = $column->attrs->unique;
                $default = $column->attrs->default;
                $auto_increment = $column->attrs->auto_increment;

                $column_schema[] = sprintf('`%s` %s%s %s %s %s',
                    $name,
                    $this->column_type[$type],
                    $size?'('.$size.')':'',
                    isset($default)?"default '".$default."'":'',
                    $notnull?'not null':'',
                    $auto_increment?'auto_increment':''
                );

                if($primary_key) $primary_list[] = $name;
                else if($unique) $unique_list[$unique][] = $name;
                else if($index) $index_list[$index][] = $name;
            }

            if(count($primary_list)) {
                $column_schema[] = sprintf("primary key (%s)", '`'.implode($primary_list,'`,`').'`');
            }

            if(count($unique_list)) {
                foreach($unique_list as $key => $val) {
                    $column_schema[] = sprintf("unique %s (%s)", $key, '`'.implode($val,'`,`').'`');
                }
            }

            if(count($index_list)) {
                foreach($index_list as $key => $val) {
                    $column_schema[] = sprintf("index %s (%s)", $key, '`'.implode($val,'`,`').'`');
                }
            }

            $schema = sprintf('create table `%s` (%s%s) %s;', $this->addQuotes($table_name), "\n", implode($column_schema,",\n"), "ENGINE = MYISAM  CHARACTER SET utf8 COLLATE utf8_general_ci");

            $output = $this->_query($schema);
            if(!$output) return false;
        }

        /**
         * @brief Handle the insertAct
         **/
        function _executeInsertAct($queryObject) {
            // TODO See what priority does
			//priority setting
			//$priority = '';
			//if($output->priority) $priority = $output->priority['type'].'_priority';

            $query = $this->getInsertSql($queryObject, true, true);
            if(is_a($query, 'Object')) return;
            return $this->_query($query);
        }

        /**
         * @brief Handle updateAct
         **/
        function _executeUpdateAct($queryObject) {
            // TODO See what proiority does
			//priority setting
			//$priority = '';
			//if($output->priority) $priority = $output->priority['type'].'_priority';

            $query = $this->getUpdateSql($queryObject, true, true);
            if(is_a($query, 'Object')) return;
            return $this->_query($query);
        }

        /**
         * @brief Handle deleteAct
         **/
        function _executeDeleteAct($queryObject) {
        	$query = $this->getDeleteSql($queryObject, true, true);

        	if(is_a($query, 'Object')) return;

        	//priority setting
			// TODO Check what priority does
			//$priority = '';
			//if($output->priority) $priority = $output->priority['type'].'_priority';
            return $this->_query($query);
        }

        /**
         * @brief Handle selectAct
         *
         * In order to get a list of pages easily when selecting \n
         * it supports a method as navigation
         **/
        function _executeSelectAct($queryObject, $connection = null) {
            $limit = $queryObject->getLimit();
            if ($limit && $limit->isPageHandler())
                    return $this->queryPageLimit($queryObject, $result, $connection);
            else {
                $query = $this->getSelectSql($queryObject);
		if(is_a($query, 'Object')) return;
                    $query .= (__DEBUG_QUERY__&1 && $queryObject->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';

		$result = $this->_query ($query, $connection);
		if ($this->isError ()) return $this->queryError($queryObject);

                $data = $this->_fetch($result);
                $buff = new Object ();
                $buff->data = $data;
                return $buff;
            }
        }

        function db_insert_id()
        {
            $connection = $this->_getConnection('master');
            return mysql_insert_id($connection);
        }

        function db_fetch_object(&$result)
        {
            return mysql_fetch_object($result);
        }
		
		function db_free_result(&$result){
			return mysql_free_result($result);		
		}

        function getParser(){
            return new DBParser('`', '`', $this->prefix);
        }

        function queryError($queryObject){
            $limit = $queryObject->getLimit();
            if ($limit && $limit->isPageHandler()){
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

        function queryPageLimit($queryObject, $result, $connection){
            $limit = $queryObject->getLimit();
            // Total count
            $temp_where = $queryObject->getWhereString(true, false);
            $count_query = sprintf('select count(*) as "count" %s %s', 'FROM ' . $queryObject->getFromString(), ($temp_where === '' ? '' : ' WHERE '. $temp_where));
			
			// Check for distinct query and if found update count query structure
            $temp_select = $queryObject->getSelectString();
			if(strpos(strtolower($temp_select), "distinct") !== false) {
					$count_query = sprintf('select %s %s %s', 'FROM ' . $queryObject->getFromString(), $temp_select, ($temp_where === '' ? '' : ' WHERE '. $temp_where));
					$uses_distinct = true;
			}
			
			// If query uses grouping or distinct, count from original select
			if ($queryObject->getGroupByString() != '' || $uses_distinct) {
                    $count_query = sprintf('select count(*) as "count" from (%s) xet', $count_query);
            }

            $count_query .= (__DEBUG_QUERY__&1 && $queryObject->query_id)?sprintf (' '.$this->comment_syntax, $this->query_id):'';
            $result_count = $this->_query($count_query, $connection);
            $count_output = $this->_fetch($result_count);
            $total_count = (int)$count_output->count;

            $list_count = $limit->list_count->getValue();
            if (!$list_count) $list_count = 20;
            $page_count = $limit->page_count->getValue();
            if (!$page_count) $page_count = 10;
            $page = $limit->page->getValue();
            if (!$page) $page = 1;

            // total pages
            if ($total_count)
                    $total_page = (int) (($total_count - 1) / $list_count) + 1;
            else
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

            $query = $this->getSelectPageSql($queryObject, true, $start_count, $list_count);

            $query .= (__DEBUG_QUERY__&1 && $queryObject->query_id)?sprintf (' '.$this->comment_syntax, $this->query_id):'';
            $result = $this->_query ($query, $connection);
            if ($this->isError ())
                return $this->queryError($queryObject);

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

        function getSelectPageSql($query, $with_values = true, $start_count = 0, $list_count = 0) {
            $select = $query->getSelectString($with_values);
            if($select == '') return new Object(-1, "Invalid query");
            $select = 'SELECT ' .$select;

            $from = $query->getFromString($with_values);
            if($from == '') return new Object(-1, "Invalid query");
            $from = ' FROM '.$from;

            $where = $query->getWhereString($with_values);
            if($where != '') $where = ' WHERE ' . $where;

            $groupBy = $query->getGroupByString();
            if($groupBy != '') $groupBy = ' GROUP BY ' . $groupBy;

            $orderBy = $query->getOrderByString();
            if($orderBy != '') $orderBy = ' ORDER BY ' . $orderBy;

            $limit = $query->getLimitString();
            if ($limit != '') $limit = sprintf (' LIMIT %d, %d', $start_count, $list_count);

            return $select . ' ' . $from . ' ' . $where . ' ' . $groupBy . ' ' . $orderBy . ' ' . $limit;
        }
    }

return new DBMysql;
?>
