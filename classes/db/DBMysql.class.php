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
            $result = @mysql_query($query, $this->fd);
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
        function _fetch($result) {
            if(!$this->isConnected() || $this->isError() || !$result) return;
            while($tmp = $this->db_fetch_object($result)) {
                $output[] = $tmp;
            }
            if(count($output)==1) return $output[0];
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
         * @brief Return conditional clause
         **/
        function getCondition($output) {
            if(!$output->conditions) return;
            $condition = $this->_getCondition($output->conditions,$output->column_type);
            if($condition) $condition = ' where '.$condition;
            return $condition;
        }

        function getLeftCondition($conditions,$column_type){
            return $this->_getCondition($conditions,$column_type);
        }


        function _getCondition($conditions,$column_type) {
            $condition = '';
            foreach($conditions as $val) {
                $sub_condition = '';
                foreach($val['condition'] as $v) {
                    if(!isset($v['value'])) continue;
                    if($v['value'] === '') continue;
                    if(!in_array(gettype($v['value']), array('string', 'integer', 'double', 'array'))) continue;

                    $name = $v['column'];
                    $operation = $v['operation'];
                    $value = $v['value'];
                    $type = $this->getColumnType($column_type,$name);
                    $pipe = $v['pipe'];
                    $value = $this->getConditionValue($name, $value, $operation, $type, $column_type);
                    if(!$value) $value = $v['value'];
                    $str = $this->getConditionPart($name, $value, $operation);
                    if($sub_condition) $sub_condition .= ' '.$pipe.' ';
                    $sub_condition .=  $str;
                }
                if($sub_condition) {
                    if($condition && $val['pipe']) $condition .= ' '.$val['pipe'].' ';
                    $condition .= '('.$sub_condition.')';
                }
            }
            return $condition;
        }

        /**
         * @brief Handle the insertAct
         **/
        function _executeInsertAct($output) {
            // List tables
            foreach($output->tables as $key => $val) {
                $table_list[] = '`'.$this->prefix.$val.'`';
            }
            // List columns
            foreach($output->columns as $key => $val) {
                $name = $val['name'];
                $value = $val['value'];

                if($output->column_type[$name]!='number') {

					if(!is_null($value)){
						$value = "'" . $this->addQuotes($value) ."'";
					}else{
						if($val['notnull']=='notnull') {
							$value = "''";
						} else {
							//$value = 'null';
							$value = "''";
						}
					}

                } elseif(!$value || is_numeric($value)) $value = (int)$value;

                $column_list[] = '`'.$name.'`';
                $value_list[] = $value;
            }

            $query = sprintf("insert into %s (%s) values (%s);", implode(',',$table_list), implode(',',$column_list), implode(',', $value_list));
            return $this->_query($query);
        }

        /**
         * @brief Handle updateAct
         **/
        function _executeUpdateAct($output) {
            // List tables
            foreach($output->tables as $key => $val) {
                $table_list[] = '`'.$this->prefix.$val.'` as '.$key;
            }
            // List columns
            foreach($output->columns as $key => $val) {
                if(!isset($val['value'])) continue;
                $name = $val['name'];
                $value = $val['value'];
                if(strpos($name,'.')!==false&&strpos($value,'.')!==false) $column_list[] = $name.' = '.$value;
                else {
                    if($output->column_type[$name]!='number') $value = "'".$this->addQuotes($value)."'";
                    elseif(!$value || is_numeric($value)) $value = (int)$value;

                    $column_list[] = sprintf("`%s` = %s", $name, $value);
                }
            }
            // List the conditional clause
            $condition = $this->getCondition($output);

            $query = sprintf("update %s set %s %s", implode(',',$table_list), implode(',',$column_list), $condition);

            return $this->_query($query);
        }

        /**
         * @brief Handle deleteAct
         **/
        function _executeDeleteAct($output) {
            // List tables
            foreach($output->tables as $key => $val) {
                $table_list[] = '`'.$this->prefix.$val.'`';
            }
            // List the conditional clause
            $condition = $this->getCondition($output);

            $query = sprintf("delete from %s %s", implode(',',$table_list), $condition);

            return $this->_query($query);
        }

        /**
         * @brief Handle selectAct
         *
         * In order to get a list of pages easily when selecting \n
         * it supports a method as navigation
         **/
        function _executeSelectAct($output) {
            // List tables
            $table_list = array();
            foreach($output->tables as $key => $val) {
                $table_list[] = '`'.$this->prefix.$val.'` as '.$key;
            }

            $left_join = array();
            // why???
            $left_tables= (array)$output->left_tables;

            foreach($left_tables as $key => $val) {
                $condition = $this->_getCondition($output->left_conditions[$key],$output->column_type);
                if($condition){
                    $left_join[] = $val . ' `'.$this->prefix.$output->_tables[$key].'` as '.$key  . ' on (' . $condition . ')';
                }
            }
			
            $click_count = array();
            if(!$output->columns){
				$output->columns = array(array('name'=>'*'));
			}

			$column_list = array();
			foreach($output->columns as $key => $val) 
			{
				$name = $val['name'];
				$alias = $val['alias'];
				if($val['click_count']) $click_count[] = $val['name'];

				if(substr($name,-1) == '*') 
				{
					$column_list[] = $name;
				} 
				else if(strpos($name,'.')===false && strpos($name,'(')===false) 
				{
					if($alias)
					{
						$col = sprintf('`%s` as `%s`', $name, $alias);
						$column_list[$alias] = $col;
					}
					else
					{
						$column_list[] = sprintf('`%s`',$name);
					}
				} 
				else 
				{
					if($alias)
					{
						$col = sprintf('%s as `%s`', $name, $alias);
						$column_list[$alias] = $col;
					}
					else
					{
						$column_list[] = sprintf('%s',$name);
					}
				}
			}

			$columns = implode(',',$column_list);
			$output->column_list = $column_list;
            $condition = $this->getCondition($output);

            if($output->list_count && $output->page) return $this->_getNavigationData($table_list, $columns, $left_join, $condition, $output);
            // Add a condition to use an index when sorting in order by list_order, update_order
            if($output->order) {
                $conditions = $this->getConditionList($output);
                if(!in_array('list_order', $conditions) && !in_array('update_order', $conditions)) {
                    foreach($output->order as $key => $val) {
                        $col = $val[0];
                        if(!in_array($col, array('list_order','update_order'))) continue;
                        if($condition) $condition .= sprintf(' and %s < 2100000000 ', $col);
                        else $condition = sprintf(' where %s < 2100000000 ', $col);
                    }
                }
            }


            if(count($output->groups))
			{
				$groupby_query = sprintf(' group by %s', implode(',',$output->groups));

				if(count($output->arg_columns))
				{
					foreach($output->groups as $group)
					{
						if($column_list[$group]) $output->arg_columns[] = $column_list[$group];
					}
				}
			}
	
            if($output->order) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
					if(count($output->arg_columns) && $column_list[$val[0]]) $output->arg_columns[] = $column_list[$val[0]];
                }
                if(count($index_list)) $orderby_query .= ' order by '.implode(',',$index_list);
            }

			if(count($output->arg_columns))
			{
				$columns = array();
				foreach($output->arg_columns as $col){
					if(strpos($col,'`')===false && strpos($col,' ')==false) $columns[] = '`'.$col.'`'; 
					else $columns[] = $col;
				}
				
				$columns = join(',',$columns);
			}

            $query = sprintf("select %s from %s %s %s %s", $columns, implode(',',$table_list),implode(' ',$left_join), $condition, $groupby_query.$orderby_query);

            // Apply when using list_count
            if($output->list_count['value']) $query = sprintf('%s limit %d', $query, $output->list_count['value']);

			$query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';

            $result = $this->_query($query);
            if($this->isError()) return;
            if(count($click_count) && count($output->conditions)){
                $_query = '';
                foreach($click_count as $k => $c) $_query .= sprintf(',%s=%s+1 ',$c,$c);
                $_query = sprintf('update %s set %s %s',implode(',',$table_list), substr($_query,1),  $condition);
                $this->_query($_query);
            }

            $data = $this->_fetch($result);

            $buff = new Object();
            $buff->data = $data;

            return $buff;
        }

        /**
         * @brief Paging is handled if navigation information exists in the query xml
         *
         * It is quite convenient although its structure is not good at all .. -_-;
         **/
        function _getNavigationData($table_list, $columns, $left_join, $condition, $output) {
            require_once(_XE_PATH_.'classes/page/PageHandler.class.php');

			$column_list = $output->column_list;

            // Get a total count
			$count_condition = count($output->groups) ? sprintf('%s group by %s', $condition, implode(', ', $output->groups)) : $condition;
			$count_query = sprintf("select count(*) as count from %s %s %s", implode(', ', $table_list), implode(' ', $left_join), $count_condition);
			if (count($output->groups)) $count_query = sprintf('select count(*) as count from (%s) xet', $count_query);

			$count_query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id . ' count(*)'):'';
			$result = $this->_query($count_query);
			$count_output = $this->_fetch($result);
			$total_count = (int)$count_output->count;

            $list_count = $output->list_count['value'];
            if(!$list_count) $list_count = 20;
            $page_count = $output->page_count['value'];
            if(!$page_count) $page_count = 10;
            $page = $output->page['value'];
            if(!$page) $page = 1;
            // Get a total page
            if($total_count) $total_page = (int)( ($total_count-1) / $list_count) + 1;
            else $total_page = 1;
            // Check Page variables
            if($page > $total_page) $page = $total_page;
            $start_count = ($page-1)*$list_count;
            // Add a condition to use an index when sorting in order by list_order, update_order
            if($output->order) {
                $conditions = $this->getConditionList($output);
                if(!in_array('list_order', $conditions) && !in_array('update_order', $conditions)) {
                    foreach($output->order as $key => $val) {
                        $col = $val[0];
                        if(!in_array($col, array('list_order','update_order'))) continue;
                        if($condition) $condition .= sprintf(' and %s < 2100000000 ', $col);
                        else $condition = sprintf(' where %s < 2100000000 ', $col);
                    }
                }
            }

            if(count($output->groups)){
				$groupby_query = sprintf(' group by %s', implode(',',$output->groups));

				if(count($output->arg_columns))
				{
					foreach($output->groups as $group)
					{
						if($column_list[$group]) $output->arg_columns[] = $column_list[$group];
					}
				}
			}

            if(count($output->order)) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
					if(count($output->arg_columns) && $column_list[$val[0]]) $output->arg_columns[] = $column_list[$val[0]];
                }
                if(count($index_list)) $orderby_query = ' order by '.implode(',',$index_list);
            }

			if(count($output->arg_columns))
			{
				$columns = array();
				foreach($output->arg_columns as $col){
					if(strpos($col,'`')===false && strpos($col,' ')==false) $columns[] = '`'.$col.'`'; 
					else $columns[] = $col;
				}
				
				$columns = join(',',$columns);
			}

            $query = sprintf("select %s from %s %s %s %s", $columns, implode(',',$table_list), implode(' ',$left_join), $condition, $groupby_query.$orderby_query);
            $query = sprintf('%s limit %d, %d', $query, $start_count, $list_count);
			$query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';

            $result = $this->_query($query);
            if($this->isError()) {
                $buff = new Object();
                $buff->total_count = 0;
                $buff->total_page = 0;
                $buff->page = 1;
                $buff->data = array();

                $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
                return $buff;
            }

            $virtual_no = $total_count - ($page-1)*$list_count;
			$data = array();
            while($tmp = $this->db_fetch_object($result)) {
                $data[$virtual_no--] = $tmp;
            }
            $buff = new Object();
            $buff->total_count = $total_count;
            $buff->total_page = $total_page;
            $buff->page = $page;
            $buff->data = $data;

            $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
            return $buff;
        }

		function db_insert_id()
		{
            return mysql_insert_id($this->fd);
		}

		function db_fetch_object(&$result)
		{
			return mysql_fetch_object($result);
		}
    }

return new DBMysql;
?>
