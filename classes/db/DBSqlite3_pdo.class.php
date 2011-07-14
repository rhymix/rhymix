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
            'varchar'   => 'VARHAR',
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
            $this->handler->commit();
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

        /**
         * @brief Return conditional clause(where)
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
         * @brief insertAct
         **/
        function _executeInsertAct($output) {
            // list tables
            foreach($output->tables as $key => $val) {
                $table_list[] = $this->prefix.$val;
            }
            // list columns
            foreach($output->columns as $key => $val) {
                $name = $val['name'];
                $value = $val['value'];

                $key_list[] = $name;

                if($output->column_type[$name]!='number') $val_list[] = $this->addQuotes($value);
                else {
					$this->_filterNumber($value);
                    $val_list[] = $value;
                }

                $prepare_list[] = '?';
            }

            $query = sprintf("INSERT INTO %s (%s) VALUES (%s);", implode(',',$table_list), implode(',',$key_list), implode(',',$prepare_list));

            $this->_prepare($query);

            $val_count = count($val_list);
            for($i=0;$i<$val_count;$i++) $this->_bind($val_list[$i]);

            return $this->_execute();
        }

        /**
         * @brief updateAct
         **/
        function _executeUpdateAct($output) {
            $table_count = count(array_values($output->tables));
            // If a target table is one
            if($table_count == 1) {
                // list tables
                list($target_table) = array_values($output->tables);
                $target_table = $this->prefix.$target_table;
                // list columns
                foreach($output->columns as $key => $val) {
                    if(!isset($val['value'])) continue;
                    $name = $val['name'];
                    $value = $val['value'];
                    if(strpos($name,'.')!==false&&strpos($value,'.')!==false) $column_list[] = $name.' = '.$value;
                    else {
                        if($output->column_type[$name]!='number') $value = "'".$this->addQuotes($value)."'";
						else $this->_filterNumber($value);

                        $column_list[] = sprintf("%s = %s", $name, $value);
                    }
                }
                // List where cluase
                $condition = $this->getCondition($output);

                $query = sprintf("update %s set %s %s", $target_table, implode(',',$column_list), $condition);
            // If tables to update are morea than two (In sqlite, it is possible to update a single table only. Let me know if you know a better way)
            } elseif($table_count == 2) {
                // List tables
                foreach($output->tables as $key => $val) {
                    $table_list[$val] = $this->prefix.$key;
                }
                list($source_table, $target_table) = array_values($table_list);
                // List where cluase
                $condition = $this->getCondition($output);
                foreach($table_list as $key => $val) {
					$condition = preg_replace('/'.$key.'\\./i', $val.'.', $condition);
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

            $this->_prepare($query);
            return $this->_execute();
        }

        /**
         * @brief deleteAct
         **/
        function _executeDeleteAct($output) {
            // List tables
            foreach($output->tables as $key => $val) {
                $table_list[] = $this->prefix.$val;
            }
            // List the conditional clause
            $condition = $this->getCondition($output);

            $query = sprintf("delete from %s %s", implode(',',$table_list), $condition);

            $this->_prepare($query);
            return $this->_execute();
        }

        /**
         * @brief selectAct
         *
         * To fetch a list of the page conveniently when selecting, \n
         * navigation method supported
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
                    $left_join[] = $val . ' '.$this->prefix.$output->_tables[$key].' as '.$key  . ' on (' . $condition . ')';
                }
            }


            $click_count = array();
            if(!$output->columns){
				$output->columns = array(array('name'=>'*'));
			}

			$column_list = array();
			foreach($output->columns as $key => $val) {
				$name = $val['name'];
				$alias = $val['alias'];
				if($val['click_count']) $click_count[] = $val['name'];

				if(substr($name,-1) == '*') {
					$column_list[] = $name;
				} elseif(strpos($name,'.')===false && strpos($name,'(')===false) {
					if($alias) $column_list[$alias] = sprintf('%s as %s', $name, $alias);
					else $column_list[] = sprintf('%s',$name);
				} else {
					if($alias) $column_list[$alias] = sprintf('%s as %s', $name, $alias);
					else $column_list[] = sprintf('%s',$name);
				}
			}
			$columns = implode(',',$column_list);

            $condition = $this->getCondition($output);

			$output->column_list = $column_list;
            if($output->list_count && $output->page) return $this->_getNavigationData($table_list, $columns, $left_join, $condition, $output);
            // add the condition to the query to use an index for ordering by list_order, update_order
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

            if($output->order) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
					if(count($output->arg_columns) && $column_list[$val[0]]) $output->arg_columns[] = $column_list[$val[0]];
                }
                if(count($index_list)) $orderby_query = ' order by '.implode(',',$index_list);
            }

			if(count($output->arg_columns))
			{
				$columns = join(',',$output->arg_columns);
			}

            $query = sprintf("select %s from %s %s %s %s", $columns, implode(',',$table_list),implode(' ',$left_join), $condition, $groupby_query.$orderby_query);
            // apply when using list_count
            if($output->list_count['value']) $query = sprintf('%s limit %d', $query, $output->list_count['value']);

			$query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';
            $this->_prepare($query);
            $data = $this->_execute();
            if($this->isError()) return;

            if(count($click_count)>0 && count($output->conditions)>0){
                $_query = '';
                foreach($click_count as $k => $c) $_query .= sprintf(',%s=%s+1 ',$c,$c);
                $_query = sprintf('update %s set %s %s',implode(',',$table_list), substr($_query,1),  $condition);
                $this->_query($_query);
            }

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
			$this->_prepare($count_query);
			$count_output = $this->_execute();
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

            if($output->order) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
					if(count($output->arg_columns) && $column_list[$val[0]]) $output->arg_columns[] = $column_list[$val[0]];
                }
                if(count($index_list)) $orderby_query = ' order by '.implode(',',$index_list);
            }

			if(count($output->arg_columns))
			{
				$columns = join(',',$output->arg_columns);
			}

            // Return the result
            $buff = new Object();
            $buff->total_count = 0;
            $buff->total_page = 0;
            $buff->page = 1;
            $buff->data = array();
            $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);

            // Query Execution
            $query = sprintf("select %s from %s %s %s %s", $columns, implode(',',$table_list),implode(' ',$left_join), $condition, $groupby_query.$orderby_query);
            $query = sprintf('%s limit %d, %d', $query, $start_count, $list_count);
			$query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';

            $this->_prepare($query);

            if($this->isError()) {
                $this->setError($this->handler->errorCode(), print_r($this->handler->errorInfo(),true));
                $this->actFinish();
                return $buff;
            }

            $this->stmt->execute();

            if($this->stmt->errorCode() != '00000') {
                $this->setError($this->stmt->errorCode(), print_r($this->stmt->errorInfo(),true));
                $this->actFinish();
                return $buff;
            }

            $output = null;
            $virtual_no = $total_count - ($page-1)*$list_count;
            while($tmp = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
                unset($obj);
                foreach($tmp as $key => $val) {
                    $pos = strpos($key, '.');
                    if($pos) $key = substr($key, $pos+1);
                    $obj->{$key} = $val;
                }
                $data[$virtual_no--] = $obj;
            }

            $this->stmt = null;
            $this->actFinish();

            $buff = new Object();
            $buff->total_count = $total_count;
            $buff->total_page = $total_page;
            $buff->page = $page;
            $buff->data = $data;

            $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
            return $buff;
        }
    }

return new DBSqlite3_pdo;
?>
