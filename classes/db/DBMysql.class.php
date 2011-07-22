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
        var $hostname = '127.0.0.1'; ///< hostname
        var $userid   = NULL; ///< user id
        var $password   = NULL; ///< password
        var $database = NULL; ///< database
        var $prefix   = 'xe'; // / <prefix of a tablename (One or more XEs can be installed in a single DB)
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
         * @brief DB settings and connect/close
         **/
        function _setDBInfo() {
            $db_info = Context::getDBInfo();
            $this->hostname = $db_info->db_hostname;
            $this->port = $db_info->db_port;
            $this->userid   = $db_info->db_userid;
            $this->password   = $db_info->db_password;
            $this->database = $db_info->db_database;
            $this->prefix = $db_info->db_table_prefix;
            if(!substr($this->prefix,-1)!='_') $this->prefix .= '_';
        }

        /**
         * @brief DB Connection
         **/
        function _connect() {
            // Ignore if no DB information exists
            if(!$this->hostname || !$this->userid || !$this->password || !$this->database) return;

            if(strpos($this->hostname, ':')===false && $this->port) $this->hostname .= ':'.$this->port;
            // Attempt to connect
            $this->fd = @mysql_connect($this->hostname, $this->userid, $this->password);
            if(mysql_error()) {
                $this->setError(mysql_errno(), mysql_error());
                return;
            }
            // Error appears if the version is lower than 4.1
            if(mysql_get_server_info($this->fd)<"4.1") {
                $this->setError(-1, "XE cannot be installed under the version of mysql 4.1. Current mysql version is ".mysql_get_server_info());
                return;
            }
            // select db
            @mysql_select_db($this->database, $this->fd);
            if(mysql_error()) {
                $this->setError(mysql_errno(), mysql_error());
                return;
            }
            // Check connections
            $this->is_connected = true;
			$this->password = md5($this->password);
            // Set utf8 if a database is MySQL
            $this->_query("set names 'utf8'");
        }

        /**
         * @brief DB disconnection
         **/
        function close() {
            if(!$this->isConnected()) return;
            @mysql_close($this->fd);
        }

        /**
         * @brief Add quotes on the string variables in a query
         **/
        function addQuotes($string) {
            if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)) $string = @mysql_escape_string($string);
            return $string;
        }

        /**
         * @brief Begin transaction
         **/
        function begin() {
        }

        /**
         * @brief Rollback
         **/
        function rollback() {
        }

        /**
         * @brief Commits
         **/
        function commit() {
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
        function _query($query) {
            if(!$this->isConnected()) return;
            // Notify to start a query execution
            $this->actStart($query);
            // Run the query statement
            $result = mysql_query($query, $this->fd);
            // Error Check
            if(mysql_error($this->fd)) $this->setError(mysql_errno($this->fd), mysql_error($this->fd));
            // Notify to complete a query execution
            $this->actFinish();
            // Return result
            return $result;
        }

        /**
         * @brief Fetch results
         **/
        function _fetch($result, $arrayIndexEndValue = NULL) {
            if(!$this->isConnected() || $this->isError() || !$result) return;
            while($tmp = $this->db_fetch_object($result)) {
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

            $query = $this->getInsertSql($queryObject);
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

            $query = $this->getUpdateSql($queryObject);
            if(is_a($query, 'Object')) return;
            return $this->_query($query);
        }

        /**
         * @brief Handle deleteAct
         **/
        function _executeDeleteAct($queryObject) {
        	$query = $this->getDeleteSql($queryObject);
			
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
        function _executeSelectAct($queryObject) {
			$query = $this->getSelectSql($queryObject);
			
			if(is_a($query, 'Object')) return;
			
			$query .= (__DEBUG_QUERY__&1 && $queryObject->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';
           
            // TODO Add support for click count
            // TODO Add code for pagination           
            
			$result = $this->_query ($query);
			if ($this->isError ()) return $this->queryError($queryObject);
			else return $this->queryPageLimit($queryObject, $result);            
        }

		function db_insert_id()
		{
            return mysql_insert_id($this->fd);
		}

		function db_fetch_object(&$result)
		{
			return mysql_fetch_object($result);
		}
		
    	function getParser(){
			return new DBParser('`');
		}
		
		function queryError($queryObject){
			if ($queryObject->getLimit() && $queryObject->getLimit()->isPageHandler()){
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
		
		function queryPageLimit($queryObject, $result){
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
				$buff->page = $queryObject->getLimit()->page->getValue();
				$buff->data = $data;
				$buff->page_navigation = new PageHandler($total_count, $total_page, $queryObject->getLimit()->page->getValue(), $queryObject->getLimit()->page_count);				
			}else{
				$data = $this->_fetch($result);
				$buff = new Object ();
				$buff->data = $data;	
			}
			return $buff;
		}
    }

return new DBMysql;
?>
