<?php
    /**
     * @class DBSqlite2
     * @author NHN (developers@xpressengine.com)
     * @brief Class for using SQLite ver 2.x
     * @version 0.1
     *
     * sqlite handling class (sqlite ver 2.x)
     **/

    class DBSqlite2 extends DB {

        /**
         * DB information
         **/
        var $database = NULL; ///< database
        var $prefix   = 'xe'; // / <prefix of a tablename (One or more XEs can be installed in a single DB)
		var $comment_syntax = '/* %s */';

        /**
         * @brief sqlite column type used in
         *
         * Becasue a common column type in schema/query xml is used for colum_type,
         * it should be replaced properly for each DBMS
         **/
        var $column_type = array(
            'bignumber' => 'INTEGER',
            'number' => 'INTEGER',
            'varchar' => 'VARCHAR',
            'char' => 'CHAR',
            'text' => 'TEXT',
            'bigtext' => 'TEXT',
            'date' => 'VARCHAR(14)',
            'float' => 'FLOAT',
        );

        /**
         * @brief constructor
         **/
        function DBSqlite2() {
            $this->_setDBInfo();
            $this->_connect();
        }
		
		/**
		 * @brief create an instance of this class
		 */
		function create()
		{
			return new DBSqlite2;
		}

        /**
         * @brief Return if it is installable
         **/
        function isSupported() {
            if(!function_exists('sqlite_open')) return false;
            return true;
        }

        /**
         * @brief DB settings and connect/close
         **/
        function _setDBInfo() {
            $db_info = Context::getDBInfo();
            $this->database = $db_info->db_database;
            $this->prefix = $db_info->db_table_prefix;
            if(!substr($this->prefix,-1)!='_') $this->prefix .= '_';
        }

        /**
         * @brief DB Connection
         **/
        function _connect() {
            // Ignore if no DB information exists
            if(!$this->database) return;
            // Attempt to access the database file
            $this->fd = sqlite_open($this->database, 0666, $error);
            if(!file_exists($this->database) || $error) {
                $this->setError(-1,$error);
                $this->is_connected = false;
                return;
            }
            // Check connections
            $this->is_connected = true;
			$this->password = md5($this->password);
        }

        /**
         * @brief DB disconnection
         **/
        function close() {
            if(!$this->isConnected()) return;
            sqlite_close($this->fd);
        }

        /**
         * @brief Begin transaction
         **/
        function begin() {
            if(!$this->is_connected || $this->transaction_started) return;
            if($this->_query("BEGIN;")) $this->transaction_started = true;
        }

        /**
         * @brief Rollback
         **/
        function rollback() {
            if(!$this->is_connected || !$this->transaction_started) return;
            $this->_query("ROLLBACK;");
            $this->transaction_started = false;
        }

        /**
         * @brief Commits
         **/
        function commit($force = false) {
            if(!$force && (!$this->isConnected() || !$this->transaction_started)) return;
            if(!$this->is_connected || !$this->transaction_started) return;
            $this->_query("COMMIT;");
            $this->transaction_started = false;
        }

        /**
         * @brief Add quotes on the string variables in a query
         **/
        function addQuotes($string) {
            if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)) $string = str_replace("'","''", $string);
            return $string;
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
            $result = @sqlite_query($query, $this->fd);
            // Error Check
            if(sqlite_last_error($this->fd)) $this->setError(sqlite_last_error($this->fd), sqlite_error_string(sqlite_last_error($this->fd)));
            // Notify to complete a query execution
            $this->actFinish();

            return $result;
        }

        /**
         * @brief Fetch results
         **/
        function _fetch($result) {
            if($this->isError() || !$result) return;

            while($tmp = sqlite_fetch_array($result, SQLITE_ASSOC)) {
                unset($obj);
                foreach($tmp as $key => $val) {
                    $pos = strpos($key, '.');
                    if($pos) $key = substr($key, $pos+1);
                    $obj->{$key} = $val;
                }
                $output[] = $obj;
            }

            if(count($output)==1) return $output[0];
            return $output;
        }

        /**
         * @brief Return the sequence value is incremented by 1
         **/
        function getNextSequence() {
            $query = sprintf("insert into %ssequence (seq) values ('')", $this->prefix);
            $this->_query($query);
            $sequence = sqlite_last_insert_rowid($this->fd);
            if($sequence % 10000 == 0) {
              $query = sprintf("delete from  %ssequence where seq < %d", $this->prefix, $sequence);
              $this->_query($query);
            }

            return $sequence;
        }

        /**
         * @brief Return if a table already exists
         **/
        function isTableExists($target_name) {
            $query = sprintf('pragma table_info(%s%s)', $this->prefix, $this->addQuotes($target_name));
            $result = $this->_query($query);
            if(sqlite_num_rows($result)==0) return false;
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

            return $this->_query($query);
        }

        /**
         * @brief Delete a column from a table
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
            $result = $this->_query($query);
            $output = $this->_fetch($result);
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
            $query = sprintf("pragma table_info(%s%s)", $this->prefix, $table_name);

            $query = sprintf('CREATE %s INDEX %s ON %s%s (%s)', $is_unique?'UNIQUE':'', $key_name, $this->prefix, $table_name, implode(',',$target_columns));
            return $this->_query($query);
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
            $result = $this->_query($query);
            $output = $this->_fetch($result);
            if(!$output) return false;
            return true;
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
                if(strtoupper($this->column_type[$type])=='INTEGER') $size = '';
                else $size = $column->attrs->size;
                $notnull = $column->attrs->notnull;
                $primary_key = $column->attrs->primary_key;
                $index = $column->attrs->index;
                $unique = $column->attrs->unique;
                $default = $column->attrs->default;
                $auto_increment = $column->attrs->auto_increment;

                if($auto_increment) {
                    $column_schema[] = sprintf('%s %s %s',
                        $name,
                        $this->column_type[$type],
                        $auto_increment?'AUTOINCREMENT':''
                    );
                } else {
                    $column_schema[] = sprintf('%s %s%s %s %s %s %s',
                        $name,
                        $this->column_type[$type],
                        $size?'('.$size.')':'',
                        $notnull?'NOT NULL':'',
                        $primary_key?'PRIMARY KEY':'',
                        isset($default)?"DEFAULT '".$default."'":'',
                        $auto_increment?'AUTOINCREMENT':''
                    );
                }

                if($unique) $unique_list[$unique][] = $name;
                else if($index) $index_list[$index][] = $name;
            }

            $schema = sprintf('CREATE TABLE %s (%s%s) ;', $this->addQuotes($table_name)," ", implode($column_schema,", "));
            $this->_query($schema);

            if(count($unique_list)) {
                foreach($unique_list as $key => $val) {
                    $query = sprintf('CREATE UNIQUE INDEX %s_%s ON %s (%s)', $this->addQuotes($table_name), $key, $this->addQuotes($table_name), implode(',',$val));
                    $this->_query($query);
                }
            }

            if(count($index_list)) {
                foreach($index_list as $key => $val) {
                    $query = sprintf('CREATE INDEX %s_%s ON %s (%s)', $this->addQuotes($table_name), $key, $this->addQuotes($table_name), implode(',',$val));
                    $this->_query($query);
                }
            }
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
                    if(!in_array(gettype($v['value']), array('string', 'integer', 'double'))) continue;

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
                $table_list[] = $this->prefix.$val;
            }
            // List columns
            foreach($output->columns as $key => $val) {
                $name = $val['name'];
                $value = $val['value'];
                if($output->column_type[$name]!='number') {
                    $value = "'".$this->addQuotes($value)."'";
                    if(!$value) $value = 'null';
                }
				// sql injection 문제로 xml 선언이 number인 경우이면서 넘어온 값이 숫자형이 아니면 숫자형으로 강제 형변환
				// elseif(!$value || is_numeric($value)) $value = (int)$value;
                else $this->_filterNumber(&$value);

                $column_list[] = $name;
                $value_list[] = $value;
            }

            $query = sprintf("insert into %s (%s) values (%s);", implode(',',$table_list), implode(',',$column_list), implode(',', $value_list));
            return $this->_query($query);
        }

        /**
         * @brief Handle updateAct
         **/
        function _executeUpdateAct($output) {
            $table_count = count(array_values($output->tables));
            // If one day the destination table
            if($table_count == 1) {
                // List tables
                list($target_table) = array_values($output->tables);
                $target_table = $this->prefix.$target_table;
                // List columns
                foreach($output->columns as $key => $val) {
                    if(!isset($val['value'])) continue;
                    $name = $val['name'];
                    $value = $val['value'];
                    if(strpos($name,'.')!==false&&strpos($value,'.')!==false) $column_list[] = $name.' = '.$value;
                    else {
                        if($output->column_type[$name]!='number') $value = "'".$this->addQuotes($value)."'";
						// sql injection 문제로 xml 선언이 number인 경우이면서 넘어온 값이 숫자형이 아니면 숫자형으로 강제 형변환
						else $this->_filterNumber(&$value);

                        $column_list[] = sprintf("%s = %s", $name, $value);
                    }
                }
                // List the conditional clause
                $condition = $this->getCondition($output);

                $query = sprintf("update %s set %s %s", $target_table, implode(',',$column_list), $condition);
            // trick to handle if targt table to update is more than one (sqlite doesn't support update to multi-tables)
            } elseif($table_count == 2) {
                // List tables
                foreach($output->tables as $key => $val) {
                    $table_list[$val] = $this->prefix.$key;
                }
                list($source_table, $target_table) = array_values($table_list);
                // List the conditional clause
                $condition = $this->getCondition($output);
                foreach($table_list as $key => $val) {
                    $condition = eregi_replace($key.'\\.', $val.'.', $condition);
                }
                // List columns
                foreach($output->columns as $key => $val) {
                    if(!isset($val['value'])) continue;
                    $name = $val['name'];
                    $value = $val['value'];
                    list($s_prefix, $s_column) = explode('.',$name);
                    list($t_prefix, $t_column) = explode('.',$value);

                    $s_table = $table_list[$s_prefix];
                    $t_table = $table_list[$t_prefix];
                    $column_list[] = sprintf(' %s = (select %s from %s %s) ', $s_column, $t_column, $t_table, $condition);
                }

                $query = sprintf('update %s set %s where exists(select * from %s %s)', $source_table, implode(',', $column_list), $target_table, $condition);
            } else {
                return;
            }

            return $this->_query($query);
        }

        /**
         * @brief Handle deleteAct
         **/
        function _executeDeleteAct($output) {
            // List tables
            foreach($output->tables as $key => $val) {
                $table_list[] = $this->prefix.$val;
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
                $table_list[] = $this->prefix.$val.' as '.$key;
            }

            $left_join = array();
            // why???
            $left_tables= (array)$output->left_tables;

            foreach($left_tables as $key => $val) {
                $condition = $this->_getCondition($output->left_conditions[$key],$output->column_type);
                if($condition){
                    $left_join[] = $val . ' '.$this->prefix.$output->_tables[$key].' as '.$key  . ' on ' . $condition . '';
                }
            }

            if(!$output->columns) {
                $columns = '*';
            } else {
                $column_list = array();
                foreach($output->columns as $key => $val) {
                    $name = $val['name'];
                    $alias = $val['alias'];
                    if($val['click_count']) $click_count[] = $val['name'];

                    if(substr($name,-1) == '*') {
                        $column_list[] = $name;
                    } elseif(strpos($name,'.')===false && strpos($name,'(')===false) {
                        if($alias) $column_list[] = sprintf('%s as %s', $name, $alias);
                        else $column_list[] = sprintf('%s',$name);
                    } else {
                        if($alias) $column_list[] = sprintf('%s as %s', $name, $alias);
                        else $column_list[] = sprintf('%s',$name);
                    }
                }
                $columns = implode(',',$column_list);
            }

            $condition = $this->getCondition($output);

			$output->column_list = $column_list;
            if($output->list_count && $output->page) return $this->_getNavigationData($table_list, $columns,  $left_join, $condition, $output);
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

            $query = sprintf("select %s from %s %s %s", $columns, implode(',',$table_list),implode(' ',$left_join), $condition);

            if(count($output->groups)) $query .= sprintf(' group by %s', implode(',',$output->groups));

            if($output->order) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
                }
                if(count($index_list)) $query .= ' order by '.implode(',',$index_list);
            }
            // Apply when using list_count
            if($output->list_count['value']) $query = sprintf('%s limit %d', $query, $output->list_count['value']);

			$query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';
            $result = $this->_query($query);
            if($this->isError()) return;

            if(count($click_count)>0 && count($output->conditions)>0){
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
            /*
            // Modified to find total number of SELECT queries having group by clause
            // If it works correctly, uncomment the following codes
            //
            $count_condition = count($output->groups) ? sprintf('%s group by %s', $condition, implode(', ', $output->groups)) : $condition;
            $total_count = $this->getCountCache($output->tables, $count_condition);
            if($total_count === false) {
                $count_query = sprintf("select count(*) as count from %s %s %s", implode(', ', $table_list), implode(' ', $left_join), $count_condition);
                if (count($output->groups))
                    $count_query = sprintf('select count(*) as count from (%s) xet', $count_query);
                $result = $this->_query($count_query);
                $count_output = $this->_fetch($result);
                $total_count = (int)$count_output->count;
                $this->putCountCache($output->tables, $count_condition, $total_count);
            }
            */
            // Get a total count
            $count_query = sprintf("select count(*) as count from %s %s %s", implode(',',$table_list),implode(' ',$left_join), $condition);
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

            $query = sprintf("select %s from %s %s %s", $columns, implode(',',$table_list), implode(' ',$left_join), $condition);


            if(count($output->groups)) $query .= sprintf(' group by %s', implode(',',$output->groups));

            if($output->order) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
                }
                if(count($index_list)) $query .= ' order by '.implode(',',$index_list);
            }

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

            if($result) {
                $virtual_no = $total_count - ($page-1)*$list_count;
                while($tmp = sqlite_fetch_array($result, SQLITE_ASSOC)) {
                    unset($obj);
                    foreach($tmp as $key => $val) {
                        $pos = strpos($key, '.');
                        if($pos) $key = substr($key, $pos+1);
                        $obj->{$key} = $val;
                    }
                    $data[$virtual_no--] = $obj;
                }
            }

            $buff = new Object();
            $buff->total_count = $total_count;
            $buff->total_page = $total_page;
            $buff->page = $page;
            $buff->data = $data;

            $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
            return $buff;
        }
    }

return new DBSqlite2;
?>
