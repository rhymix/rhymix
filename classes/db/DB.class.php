<?php
    /**
     * @class DB
     * @author NHN (developers@xpressengine.com)
     * @brief  base class of db* classes
     * @version 0.1
     *
     * usage of db in XE is via xml
     * there are 2 types of xml - query xml, schema xml
     * in case of query xml, DB::executeQuery() method compiles xml file into php code and then execute it
     * query xml has unique query id, and will be created in module
     *
     * queryid = module_name.query_name
     **/

    if(!defined('__XE_LOADED_DB_CLASS__')){
        define('__XE_LOADED_DB_CLASS__', 1);

        require(_XE_PATH_.'classes/xml/xmlquery/DBParser.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/QueryParser.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/argument/Argument.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/argument/SortArgument.class.php');
        require(_XE_PATH_.'classes/xml/xmlquery/argument/ConditionArgument.class.php');

        require(_XE_PATH_.'classes/db/queryparts/expression/Expression.class.php');
        require(_XE_PATH_.'classes/db/queryparts/expression/SelectExpression.class.php');
        require(_XE_PATH_.'classes/db/queryparts/expression/InsertExpression.class.php');
        require(_XE_PATH_.'classes/db/queryparts/expression/UpdateExpression.class.php');
        require(_XE_PATH_.'classes/db/queryparts/expression/UpdateExpressionWithoutArgument.class.php');
        require(_XE_PATH_.'classes/db/queryparts/table/Table.class.php');
        require(_XE_PATH_.'classes/db/queryparts/table/JoinTable.class.php');
        require(_XE_PATH_.'classes/db/queryparts/table/CubridTableWithHint.class.php');
        require(_XE_PATH_.'classes/db/queryparts/table/MysqlTableWithHint.class.php');
        require(_XE_PATH_.'classes/db/queryparts/table/MssqlTableWithHint.class.php');
        require(_XE_PATH_.'classes/db/queryparts/table/IndexHint.class.php');
        require(_XE_PATH_.'classes/db/queryparts/condition/ConditionGroup.class.php');
        require(_XE_PATH_.'classes/db/queryparts/condition/Condition.class.php');
        require(_XE_PATH_.'classes/db/queryparts/condition/ConditionWithArgument.class.php');
        require(_XE_PATH_.'classes/db/queryparts/condition/ConditionWithoutArgument.class.php');
        require(_XE_PATH_.'classes/db/queryparts/condition/ConditionSubquery.class.php');
        require(_XE_PATH_.'classes/db/queryparts/expression/StarExpression.class.php');
        require(_XE_PATH_.'classes/db/queryparts/order/OrderByColumn.class.php');
        require(_XE_PATH_.'classes/db/queryparts/limit/Limit.class.php');
        require(_XE_PATH_.'classes/db/queryparts/Query.class.php');
        require(_XE_PATH_.'classes/db/queryparts/Subquery.class.php');
    }

    class DB {

        var $count_cache_path = 'files/cache/db';

        var $cond_operation = array( ///< operations for condition
            'equal' => '=',
            'more' => '>=',
            'excess' => '>',
            'less' => '<=',
            'below' => '<',
            'notequal' => '<>',
            'notnull' => 'is not null',
            'null' => 'is null',
        );

        var $master_db = NULL; // master database connection string
        var $slave_db = NULL; // array of slave databases connection strings

        var $result = NULL; ///< result

        var $errno = 0; ///< error code (0 means no error)
        var $errstr = ''; ///< error message
        var $query = ''; ///< query string of latest executed query
        var $connection = '';
        var $elapsed_time = 0; ///< elapsed time of latest executed query
        var $elapsed_dbclass_time = 0; ///< elapsed time of latest executed query

        var $transaction_started = false; ///< transaction flag

        var $is_connected = false; ///< is db connected

        var $supported_list = array(); ///< list of supported db, (will be written by classes/DB/DB***.class.php)

        var $cache_file = 'files/cache/queries/'; ///< location of query cache

		var $db_type; ///< stores database type: 'mysql','cubrid','mssql' etc. or 'db' when database is not yet set

		var $use_prepared_statements; ///< flag to decide if class prepared statements or not (when supported); can be changed from db.config.info
		
        /**
         * @brief returns instance of certain db type
         * @param[in] $db_type type of db
         * @return instance
         **/
        function &getInstance($db_type = NULL) {
            if(!$db_type) $db_type = Context::getDBType();
            if(!$db_type && Context::isInstalled()) return new Object(-1, 'msg_db_not_setted');

			if(!isset($GLOBALS['__DB__'])) $GLOBALS['__DB__'] = array();
            if(!isset($GLOBALS['__DB__'][$db_type])) {
                $class_name = 'DB'.ucfirst($db_type);
                $class_file = _XE_PATH_."classes/db/$class_name.class.php";
                if(!file_exists($class_file)) return new Object(-1, 'msg_db_not_setted');

				// get a singletone instance of the database driver class
				require_once($class_file);
                $GLOBALS['__DB__'][$db_type] = call_user_func(array($class_name, 'create'));
				$GLOBALS['__DB__'][$db_type]->db_type = $db_type;
            }

            return $GLOBALS['__DB__'][$db_type];
        }

		function create() {
			return new DB;
		}

        /**
         * @brief constructor
         * @return none
         **/
        function DB() {
            $this->count_cache_path = _XE_PATH_.$this->count_cache_path;
            $this->cache_file = _XE_PATH_.$this->cache_file;
        }

        /**
         * @brief returns list of supported db
         * @return list of supported db
         **/
        function getSupportedList() {
            $oDB = new DB();
            return $oDB->_getSupportedList();
        }

        /**
         * @brief returns list of enable in supported db
         * @return list of enable in supported db
         **/
        function getEnableList()
        {
                if(!$this->supported_list)
                {
                        $oDB = new DB();
                        $this->supported_list = $oDB->_getSupportedList();
                }

                $enableList = array();
                if(is_array($this->supported_list))
                {
                        foreach($this->supported_list AS $key=>$value)
                                if($value->enable) array_push($enableList, $value);
                }
                return $enableList;
        }

        /**
         * @brief returns list of disable in supported db
         * @return list of disable in supported db
         **/
        function getDisableList()
        {
                if(!$this->supported_list)
                {
                        $oDB = new DB();
                        $this->supported_list = $oDB->_getSupportedList();
                }

                $disableList = array();
                if(is_array($this->supported_list))
                {
                        foreach($this->supported_list AS $key=>$value)
                                if(!$value->enable) array_push($disableList, $value);
                }
                return $disableList;
        }

        /**
         * @brief returns list of supported db
         * @return list of supported db
         **/
        function _getSupportedList() {
			static $get_supported_list = '';
			if(is_array($get_supported_list)) {
				$this->supported_list = $get_supported_list;
				return $this->supported_list;
			}
			$get_supported_list = array();
            $db_classes_path = _XE_PATH_."classes/db/";
            $filter = "/^DB([^\.]+)\.class\.php/i";
            $supported_list = FileHandler::readDir($db_classes_path, $filter, true);
            sort($supported_list);

            // after creating instance of class, check is supported
            for($i = 0; $i < count($supported_list); $i++) {
                $db_type = $supported_list[$i];

                if(version_compare(phpversion(), '5.0') < 0 && preg_match('/pdo/i',$db_type)) continue;

                $class_name = sprintf("DB%s%s", strtoupper(substr($db_type,0,1)), strtolower(substr($db_type,1)));
                $class_file = sprintf(_XE_PATH_."classes/db/%s.class.php", $class_name);
                if(!file_exists($class_file)) continue;

                unset($oDB);
                require_once($class_file);
				$tmp_fn = create_function('', "return new {$class_name}();");
				$oDB    = $tmp_fn();

                if(!$oDB) continue;

                $obj = null;
                $obj->db_type = $db_type;
                $obj->enable = $oDB->isSupported() ? true : false;

                $get_supported_list[] = $obj;
            }
			$this->supported_list = $get_supported_list;
            return $this->supported_list;
        }

        /**
         * @brief check if the db_type is supported
         * @param[in] $db_type type of db to check
         * @return true: is supported, false: is not supported
         **/
        function isSupported() {
			return FALSE;
        }

        /**
         * @brief check if is connected
         * @return true: connected, false: not connected
         **/
        function isConnected($type = 'master', $indx = 0) {
            if($type == 'master') return $this->master_db["is_connected"] ? true : false;
            else return $this->slave_db[$indx]["is_connected"] ? true : false;
        }

        /**
         * @brief start recording log
         * @return none
         **/
        function actStart($query) {
            $this->setError(0, 'success');
            $this->query = $query;
            $this->act_start = getMicroTime();
            $this->elapsed_time = 0;
        }

        /**
         * @brief finish recording log
         * @return none
         **/
        function actFinish() {
            if(!$this->query) return;
            $this->act_finish = getMicroTime();
            $elapsed_time = $this->act_finish - $this->act_start;
            $this->elapsed_time = $elapsed_time;
            $GLOBALS['__db_elapsed_time__'] += $elapsed_time;

            $log['query'] = $this->query;
            $log['elapsed_time'] = $elapsed_time;
            $log['connection'] = $this->connection;

            // leave error log if an error occured (if __DEBUG_DB_OUTPUT__ is defined)
            if($this->isError()) {
                $site_module_info = Context::get('site_module_info');
                $log['module'] = $site_module_info->module;
                $log['act'] = Context::get('act');
                $log['query_id'] = $this->query_id;
                $log['time'] = date('Y-m-d H:i:s');
                $log['result'] = 'Failed';
                $log['errno'] = $this->errno;
                $log['errstr'] = $this->errstr;

                if(__DEBUG_DB_OUTPUT__ == 1)  {
                    $debug_file = _XE_PATH_."files/_debug_db_query.php";
                    $buff = array();
                    if(!file_exists($debug_file)) $buff[] = '<?php exit(); ?>';
                    $buff[] = print_r($log, true);

                    if(@!$fp = fopen($debug_file, "a")) return;
                    fwrite($fp, implode("\n", $buff)."\n\n");
                    fclose($fp);
                }
            } else {
                $log['result'] = 'Success';
            }
            $GLOBALS['__db_queries__'][] = $log;

            // if __LOG_SLOW_QUERY__ if defined, check elapsed time and leave query log
            if(__LOG_SLOW_QUERY__ > 0 && $elapsed_time > __LOG_SLOW_QUERY__) {
                $buff = '';
                $log_file = _XE_PATH_.'files/_db_slow_query.php';
                if(!file_exists($log_file)) {
                    $buff = '<?php exit();?>'."\n";
                }

                $buff .= sprintf("%s\t%s\n\t%0.6f sec\tquery_id:%s\n\n", date("Y-m-d H:i"), $this->query, $elapsed_time, $this->query_id);

                if($fp = fopen($log_file, 'a')) {
                    fwrite($fp, $buff);
                    fclose($fp);
                }
            }
        }

        /**
         * @brief set error
         * @param[in] $errno error code
         * @param[in] $errstr error message
         * @return none
         **/
        function setError($errno = 0, $errstr = 'success') {
            $this->errno = $errno;
            $this->errstr = $errstr;
        }

        /**
         * @brief check if an error occured
         * @return true: error, false: no error
         **/
        function isError() {
            return $this->errno === 0 ? false : true;
        }

        /**
         * @brief returns object of error info
         * @return object of error
         **/
        function getError() {
            $this->errstr = Context::convertEncodingStr($this->errstr);
            return new Object($this->errno, $this->errstr);
        }

        /**
         * @brief Run the result of the query xml file
         * @param[in] $query_id query id (module.queryname
         * @param[in] $args arguments for query
         * @return result of query
         * @remarks this function finds xml file or cache file of $query_id, compiles it and then execute it
         **/
        function executeQuery($query_id, $args = NULL, $arg_columns = NULL) {
			static $cache_file = array();
            if(!$query_id) return new Object(-1, 'msg_invalid_queryid');
			if(!$this->db_type) return;

			$this->actDBClassStart();

			$this->query_id = $query_id;

			if(!isset($cache_file[$query_id])) {
				$id_args = explode('.', $query_id);
				if(count($id_args) == 2) {
					$target = 'modules';
					$module = $id_args[0];
					$id = $id_args[1];
				} elseif(count($id_args) == 3) {
					$target = $id_args[0];
					if(!in_array($target, array('addons','widgets'))){
						$this->actDBClassFinish();
						return;
					}
					$module = $id_args[1];
					$id = $id_args[2];
				}
				if(!$target || !$module || !$id){
					$this->actDBClassFinish();
					return new Object(-1, 'msg_invalid_queryid');
				}

				$xml_file = sprintf('%s%s/%s/queries/%s.xml', _XE_PATH_, $target, $module, $id);
				if(!file_exists($xml_file)){
					$this->actDBClassFinish();
					return new Object(-1, 'msg_invalid_queryid');
				}

				// look for cache file
				$cache_file[$query_id] = $this->checkQueryCacheFile($query_id, $xml_file);
			}
			$result = $this->_executeQuery($cache_file[$query_id], $args, $query_id, $arg_columns);

			$this->actDBClassFinish();
			// execute query
			return $result;
        }


        /**
         * @brief look for cache file
         * @param[in] $query_id query id for finding
         * @param[in] $xml_file original xml query file
         * @return cache file
         **/
        function checkQueryCacheFile($query_id,$xml_file){
            // first try finding cache file
            $cache_file = sprintf('%s%s%s.%s.%s.cache.php', _XE_PATH_, $this->cache_file, $query_id, __ZBXE_VERSION__, $this->db_type);

            if(file_exists($cache_file)) $cache_time = filemtime($cache_file);
            else $cache_time = -1;

            // if there is no cache file or is not new, find original xml query file and parse it
            if($cache_time < filemtime($xml_file) || $cache_time < filemtime(_XE_PATH_.'classes/db/DB.class.php') || $cache_time < filemtime(_XE_PATH_.'classes/xml/XmlQueryParser.150.class.php')) {
                require_once(_XE_PATH_.'classes/xml/XmlQueryParser.150.class.php');
                $oParser = new XmlQueryParser();
                $oParser->parse($query_id, $xml_file, $cache_file);
            }

            return $cache_file;
        }


        /**
         * @brief execute query and return the result
         * @param[in] $cache_file cache file of query
         * @param[in] $source_args arguments for query
         * @param[in] $query_id query id
         * @return result of query
         **/
        function _executeQuery($cache_file, $source_args, $query_id, $arg_columns) {
            global $lang;

            if(!file_exists($cache_file)) return new Object(-1, 'msg_invalid_queryid');

            if($source_args) $args = @clone($source_args);

            $output = include($cache_file);

            if( (is_a($output, 'Object') || is_subclass_of($output, 'Object')) && !$output->toBool()) return $output;

            // execute appropriate query
            switch($output->getAction()) {
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
                        $arg_columns = is_array($arg_columns)?$arg_columns:array();
                        $output->setColumnList($arg_columns);
                        $connection = $this->_getConnection('slave');
                        $output = $this->_executeSelectAct($output, $connection);
                    break;
            }

            if($this->isError()) $output = $this->getError();
            else if(!is_a($output, 'Object') && !is_subclass_of($output, 'Object')) $output = new Object();
            $output->add('_query', $this->query);
            $output->add('_elapsed_time', sprintf("%0.5f", $this->elapsed_time));

            return $output;
        }


        /**
         * @brief returns counter cache data
         * @param[in] $tables tables to get data
         * @param[in] $condition condition to get data
         * @return count of cache data
         **/
        function getCountCache($tables, $condition) {
            return false;
            if(!$tables) return false;
            if(!is_dir($this->count_cache_path)) return FileHandler::makeDir($this->count_cache_path);

            $condition = md5($condition);

            if(!is_array($tables)) $tables_str = $tables;
            else $tables_str = implode('.',$tables);

            $cache_path = sprintf('%s/%s%s', $this->count_cache_path, $this->prefix, $tables_str);
            if(!is_dir($cache_path)) FileHandler::makeDir($cache_path);

            $cache_filename = sprintf('%s/%s.%s', $cache_path, $tables_str, $condition);
            if(!file_exists($cache_filename)) return false;

            $cache_mtime = filemtime($cache_filename);

            if(!is_array($tables)) $tables = array($tables);
            foreach($tables as $alias => $table) {
                $table_filename = sprintf('%s/cache.%s%s', $this->count_cache_path, $this->prefix, $table) ;
                if(!file_exists($table_filename) || filemtime($table_filename) > $cache_mtime) return false;
            }

            $count = (int)FileHandler::readFile($cache_filename);
            return $count;
        }

        /**
         * @brief save counter cache data
         * @param[in] $tables tables to save data
         * @param[in] $condition condition to save data
         * @param[in] $count count of cache data to save
         * @return none
         **/
        function putCountCache($tables, $condition, $count = 0) {
            return false;
            if(!$tables) return false;
            if(!is_dir($this->count_cache_path)) return FileHandler::makeDir($this->count_cache_path);

            $condition = md5($condition);

            if(!is_array($tables)) $tables_str = $tables;
            else $tables_str = implode('.',$tables);

            $cache_path = sprintf('%s/%s%s', $this->count_cache_path, $this->prefix, $tables_str);
            if(!is_dir($cache_path)) FileHandler::makeDir($cache_path);

            $cache_filename = sprintf('%s/%s.%s', $cache_path, $tables_str, $condition);

            FileHandler::writeFile($cache_filename, $count);
        }

        /**
         * @brief reset counter cache data
         * @param[in] $tables tables to reset cache data
         * @return true: success, false: failed
         **/
        function resetCountCache($tables) {
            return false;
            if(!$tables) return false;
            if(!is_dir($this->count_cache_path)) return FileHandler::makeDir($this->count_cache_path);

            if(!is_array($tables)) $tables = array($tables);
            foreach($tables as $alias => $table) {
                $filename = sprintf('%s/cache.%s%s', $this->count_cache_path, $this->prefix, $table);
                FileHandler::removeFile($filename);
                FileHandler::writeFile($filename, '');
            }

            return true;
        }

        /**
         * @brief returns supported database list
         * @return list of supported database
         **/
        function getSupportedDatabase(){
            $result = array();

            if(function_exists('mysql_connect')) $result[] = 'MySQL';
            if(function_exists('cubrid_connect')) $result[] = 'Cubrid';
            if(function_exists('ibase_connect')) $result[] = 'FireBird';
            if(function_exists('pg_connect')) $result[] = 'Postgre';
            if(function_exists('sqlite_open')) $result[] = 'sqlite2';
            if(function_exists('mssql_connect')) $result[] = 'MSSQL';
            if(function_exists('PDO')) $result[] = 'sqlite3(PDO)';

            return $result;
        }

        function dropTable($table_name){
            if(!$table_name) return;
            $query = sprintf("drop table %s%s", $this->prefix, $table_name);
            $this->_query($query);
        }

    	function getSelectSql($query, $with_values = true){
			$select = $query->getSelectString($with_values);
			if($select == '') return new Object(-1, "Invalid query");
			$select = 'SELECT ' .$select;

			$from = $query->getFromString($with_values);
			if($from == '') return new Object(-1, "Invalid query");
			$from = ' FROM '.$from;

			$where = $query->getWhereString($with_values);
			if($where != '') $where = ' WHERE ' . $where;

                        $tableObjects = $query->getTables();
                        $index_hint_list = '';
                        foreach($tableObjects as $tableObject){
                            if(is_a($tableObject, 'CubridTableWithHint'))
                                    $index_hint_list .= $tableObject->getIndexHintString() . ', ';
                        }
                        $index_hint_list = substr($index_hint_list, 0, -2);
                        if($index_hint_list != '')
                            $index_hint_list = 'USING INDEX ' . $index_hint_list;

			$groupBy = $query->getGroupByString();
			if($groupBy != '') $groupBy = ' GROUP BY ' . $groupBy;

			$orderBy = $query->getOrderByString();
			if($orderBy != '') $orderBy = ' ORDER BY ' . $orderBy;

		 	$limit = $query->getLimitString();
		 	if($limit != '') $limit = ' LIMIT ' . $limit;

		 	return $select . ' ' . $from . ' ' . $where . ' ' . $index_hint_list . ' ' . $groupBy . ' ' . $orderBy . ' ' . $limit;
		}

   		function getDeleteSql($query, $with_values = true, $with_priority = false){
			$sql = 'DELETE ';

			$sql .= $with_priority?$query->getPriority():'';
			$tables = $query->getTables();

			$sql .= $tables[0]->getAlias();

			$from = $query->getFromString($with_values);
			if($from == '') return new Object(-1, "Invalid query");
			$sql .= ' FROM '.$from;

			$where = $query->getWhereString($with_values);
			if($where != '') $sql .= ' WHERE ' . $where;

			return $sql;
		}

    	function getUpdateSql($query, $with_values = true, $with_priority = false){
			$columnsList = $query->getUpdateString($with_values);
			if($columnsList == '') return new Object(-1, "Invalid query");

                        $tables = $query->getFromString($with_values);
                        if($tables == '') return new Object(-1, "Invalid query");

			$where = $query->getWhereString($with_values);
			if($where != '') $where = ' WHERE ' . $where;

			$priority = $with_priority?$query->getPriority():'';

			return "UPDATE $priority $tables SET $columnsList ".$where;
		}

    	function getInsertSql($query, $with_values = true, $with_priority = false){
			$tableName = $query->getFirstTableName();
			$values = $query->getInsertString($with_values);
			$priority = $with_priority?$query->getPriority():'';

			return "INSERT $priority INTO $tableName \n $values";
		}

        function _getSlaveConnectionStringIndex() {
            $max = count($this->slave_db);
            $indx = rand(0, $max - 1);
            return $indx;
        }

        function _getConnection($type = 'master', $indx = NULL){
            if($type == 'master'){
                if(!$this->master_db['is_connected'])
                        $this->_connect($type);
                $this->connection = 'Master ' . $this->master_db['db_hostname'];
                return $this->master_db["resource"];
            }

            if($indx === NULL)
                $indx = $this->_getSlaveConnectionStringIndex($type);

            if(!$this->slave_db[$indx]['is_connected'])
                    $this->_connect($type, $indx);

            $this->connection = 'Slave ' . $this->slave_db[$indx]['db_hostname'];
            return $this->slave_db[$indx]["resource"];
        }

        function _dbInfoExists() {
            if (!$this->master_db)
                return false;
            if (count($this->slave_db) === 0)
                return false;
            return true;
        }

        function _close($connection){

        }

        /**
         * @brief DB disconnection
         * */
        function close($type = 'master', $indx = 0) {
            if (!$this->isConnected($type, $indx))
                return;

            if ($type == 'master')
                $connection = &$this->master_db;
            else
                $connection = &$this->slave_db[$indx];

            $this->_close($connection["resource"]);

            $connection["is_connected"] = false;
        }

        function _begin(){
            return true;
        }
        /**
         * @brief Begin transaction
         * */
        function begin() {
            if (!$this->isConnected() || $this->transaction_started)
                return;

            if($this->_begin())
                 $this->transaction_started = true;
        }

        function _rollback(){
            return true;
        }

        /**
         * @brief Rollback
         * */
        function rollback() {
            if (!$this->isConnected() || !$this->transaction_started)
                return;
            if($this->_rollback())
                $this->transaction_started = false;
        }

        function _commit(){
            return true;
        }
        /**
         * @brief Commits
         * */
        function commit($force = false) {
            if (!$force && (!$this->isConnected() || !$this->transaction_started))
                return;
            if($this->_commit())
                $this->transaction_started = false;
        }

        function __query($query, $connection){

        }
        /**
         * @brief : Run a query and fetch the result
         *
         * query: run a query and return the result \n
         * fetch: NULL if no value is returned \n
         *        array object if rows are returned \n
         *        object if a row is returned \n
         *         return\n
         * */
        function _query($query, $connection = null) {
            if($connection == null)
                $connection = $this->_getConnection('master');
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
         * @brief DB settings and connect/close
         * */
        function _setDBInfo(){
            $db_info = Context::getDBInfo();
            $this->master_db = $db_info->master_db;
            if($db_info->master_db["db_hostname"] == $db_info->slave_db[0]["db_hostname"]
                    && $db_info->master_db["db_port"] == $db_info->slave_db[0]["db_port"]
                    && $db_info->master_db["db_userid"] == $db_info->slave_db[0]["db_userid"]
                    && $db_info->master_db["db_password"] == $db_info->slave_db[0]["db_password"]
                    && $db_info->master_db["db_database"] == $db_info->slave_db[0]["db_database"]
                    )
                    $this->slave_db[0] = &$this->master_db;
            else
                    $this->slave_db = $db_info->slave_db;
            $this->prefix = $db_info->master_db["db_table_prefix"];
			$this->use_prepared_statements = $db_info->use_prepared_statements;
        }

        function __connect($connection){

        }

        function _afterConnect($connection){

        }
        /**
         * @brief DB Connection
         * */
        function _connect($type = 'master', $indx = 0) {
            if ($this->isConnected($type, $indx))
                return;

            // Ignore if no DB information exists
            if (!$this->_dbInfoExists())
                return;

            if ($type == 'master')
                $connection = &$this->master_db;
            else
                $connection = &$this->slave_db[$indx];

            $result = $this->__connect($connection);
            if($result === NULL || $result === false) {
                $connection["is_connected"] = false;
                return;
            }

            // Check connections
            $connection["resource"] = $result;
            $connection["is_connected"] = true;

            // Save connection info for db logs
            $this->connection = ucfirst($type) . ' ' . $connection["db_hostname"];

            $this->_afterConnect($result);
        }
	/**
         * @brief start recording DBClass log
         * @return none
         **/
        function actDBClassStart() {
            $this->setError(0, 'success');
            $this->act_dbclass_start = getMicroTime();
            $this->elapsed_dbclass_time = 0;
        }

        /**
         * @brief finish recording DBClass log
         * @return none
         **/
        function actDBClassFinish() {
            if(!$this->query) return;
            $this->act_dbclass_finish = getMicroTime();
            $elapsed_dbclass_time = $this->act_dbclass_finish - $this->act_dbclass_start;
            $this->elapsed_dbclass_time = $elapsed_dbclass_time;
            $GLOBALS['__dbclass_elapsed_time__'] += $elapsed_dbclass_time;
        }

        /**
         * Returns a database specific parser class
         * used for escaping expressions and table/column identifiers
         *
         * Requires an implementation of the DB class (won't work if database is not set)
         *
         * @remarks singleton
         */
       function &getParser($force = false){
            static $dbParser = null;
            if(!$dbParser || $force) {
                $oDB = &DB::getInstance();
                $dbParser = $oDB->getParser();
            }

            return $dbParser;
        }

    }
?>