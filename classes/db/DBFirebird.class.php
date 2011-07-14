<?php
    /**
     * @class DBFirebird
     * @author Kim Hyun Sik (dev.hyuns @ gmail.com)
     * @brief class to use Firebird DBMS
     * @version 0.3
     *
     * firebird handling class
     **/

    class DBFireBird extends DB {

        /**
         * @brief connection to Firebird DB
         **/
        var $hostname = '127.0.0.1'; ///< hostname
        var $userid   = NULL; ///< user id
        var $password   = NULL; ///< password
        var $database = NULL; ///< database
        var $prefix   = 'xe'; // / <prefix of XE tables(One more XE can be installed on a single DB)
        var $idx_no = 0; // counter for creating an index
		var $comment_syntax = '/* %s */';

        /**
         * @brief column type used in firebird
         *
         * column_type should be replaced for each DBMS's type
         * becasue it uses commonly defined type in the schema/query xml
         **/
        var $column_type = array(
            'bignumber' => 'BIGINT',
            'number' => 'INTEGER',
            'varchar' => 'VARCHAR',
            'char' => 'CHAR',
            'text' => 'BLOB SUB_TYPE TEXT SEGMENT SIZE 32',
            'bigtext' => 'BLOB SUB_TYPE TEXT SEGMENT SIZE 32',
            'date' => 'VARCHAR(14)',
            'float' => 'FLOAT',
        );

        /**
         * @brief constructor
         **/
        function DBFireBird() {
            $this->_setDBInfo();
            $this->_connect();
        }
		
		/**
		 * @brief create an instance of this class
		 */
		function create()
		{
			return new DBFireBird;
		}

        /**
         * @brief Return if installable
         **/
        function isSupported() {
            if(!function_exists('ibase_connect')) return false;
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
            // ignore if db information not exists
            if(!$this->hostname || !$this->port || !$this->userid || !$this->password || !$this->database) return;

            //if(strpos($this->hostname, ':')===false && $this->port) $this->hostname .= ':'.$this->port;
            // attempts to connect
            $host = $this->hostname."/".$this->port.":".$this->database;

            $this->fd = @ibase_connect($host, $this->userid, $this->password);
            if(ibase_errmsg()) {
                $this->setError(ibase_errcode(), ibase_errmsg());
                return $this->is_connected = false;
            }
            // Error when Firebird version is lower than 2.0
            if (($service = ibase_service_attach($this->hostname, $this->userid, $this->password)) != FALSE) {
                // get server version and implementation strings
                $server_info  = ibase_server_info($service, IBASE_SVC_SERVER_VERSION);
                ibase_service_detach($service);
            }
            else {
                $this->setError(ibase_errcode(), ibase_errmsg());
                @ibase_close($this->fd);
                return $this->is_connected = false;
            }

            $pos = strpos($server_info, "Firebird");
            if($pos !== false) {
                $ver = substr($server_info, $pos+strlen("Firebird"));
                $ver = trim($ver);
            }

            if($ver < "2.0") {
                $this->setError(-1, "XE cannot be installed under the version of firebird 2.0. Current firebird version is ".$ver);
                @ibase_close($this->fd);
                return $this->is_connected = false;
            }
            // Check connections
            $this->is_connected = true;
			$this->password = md5($this->password);
        }

        /**
         * @brief DB disconnect
         **/
        function close() {
            if(!$this->isConnected()) return;
            @ibase_commit($this->fd);
            @ibase_close($this->fd);
            $this->transaction_started = false;
        }

        /**
         * @brief handles quatation of the string variables from the query
         **/
        function addQuotes($string) {
//            if(get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
//            if(!is_numeric($string)) $string = str_replace("'","''", $string);
            return $string;
        }

        /**
        * @brief put double quotes for tabls, column names in the query statement
        **/
        function addDoubleQuotes($string) {
            if($string == "*") return $string;

            if(strpos($string, "'")!==false) {
                $string = str_replace("'", "\"", $string);
            }
            else if(strpos($string, "\"")!==false) {
            }
            else {
                $string = "\"".$string."\"";
            }

            return $string;
        }

        /**
         * @brief put double quotes for tabls, column names in the query statement
         **/
        function autoQuotes($string){
            $string = strtolower($string);
            // for substr function
            if(strpos($string, "substr(") !== false) {
                $tokken = strtok($string, "(,)");
                $tokken = strtok("(,)");
                while($tokken) {
                    $tokkens[] = $tokken;
                    $tokken = strtok("(,)");
                }

                if(count($tokkens) !== 3) return $string;
                return sprintf("substring(%s from %s for %s)", $this->addDoubleQuotes($tokkens[0]), $tokkens[1], $tokkens[2]);
            }

            // as
            $as = false;
            if(($no1 = strpos($string," as ")) !== false) {
                $as = substr($string, $no1, strlen($string)-$no1);
                $string = substr($string, 0, $no1);

                $as = str_replace(" as ", "", $as);
                $as = trim($as);
                $as = $this->addDoubleQuotes($as);
            }
            // for functions
            $tmpFunc1 = null;
            $tmpFunc2 = null;
            if(($no1 = strpos($string,'('))!==false && ($no2 = strpos($string, ')'))!==false) {
                $tmpFunc1 = substr($string, 0, $no1+1);
                $tmpFunc2 = substr($string, $no2, strlen($string)-$no2+1);
                $string = trim(substr($string, $no1+1, $no2-$no1-1));
            }
            // for (table.column) structure
            preg_match("/((?i)[a-z0-9_-]+)[.]((?i)[a-z0-9_\-\*]+)/", $string, $matches);

            if($matches) {
                $string = $this->addDoubleQuotes($matches[1]).".".$this->addDoubleQuotes($matches[2]);
            }
            else {
                $string = $this->addDoubleQuotes($string);
            }

            if($tmpFunc1 != null) $string = $tmpFunc1.$string;
            if($tmpFunc2 != null) $string = $string.$tmpFunc2;

            if($as !== false) $string = $string." as ".$as;
            return $string;
        }

        function autoValueQuotes($string, $tables){
            $tok = strtok($string, ",");
            while($tok !== false) {
                $values[] = $tok;
                $tok = strtok(",");
            }

            foreach($values as $val1) {
                // for (table.column) structure
                preg_match("/((?i)[a-z0-9_-]+)[.]((?i)[a-z0-9_\-\*]+)/", $val1, $matches);
                if($matches) {
                    $isTable = false;

                    foreach($tables as $key2 => $val2) {
                        if($key2 == $matches[1]) $isTable = true;
                        if($val2 == $matches[1]) $isTable = true;
                    }

                    if($isTable) {
                        $return[] = $this->addDoubleQuotes($matches[1]).".".$this->addDoubleQuotes($matches[2]);
                    }
                    else {
                        $return[] = $val1;
                    }
                }
                else if(!is_numeric($val1)) {
                    if(strpos($val1, "'") !== 0)
                        $return[] = "'".$val1."'";
                    else
                        $return[] = $val1;
                }
                else {
                    $return[] = $val1;
                }
            }

            return implode(",", $return);
        }

        /**
         * @brief Begin transaction
         **/
        function begin() {
            if(!$this->isConnected() || $this->transaction_started) return;
            $this->transaction_started = true;
        }

        /**
         * @brief Rollback
         **/
        function rollback() {
            if(!$this->isConnected() || !$this->transaction_started) return;
            @ibase_rollback($this->fd);
            $this->transaction_started = false;
        }

        /**
         * @brief Commits
         **/
        function commit() {
            if(!$force && (!$this->isConnected() || !$this->transaction_started)) return;
            @ibase_commit($this->fd);
            $this->transaction_started = false;
        }

        /**
         * @brief : Run a query and fetch the result
         *
         * query: run a query and return the result\n
         * fetch: NULL if no value returned \n
         *        array object if rows returned \n
         *        object if a row returned \n
         *        return\n
         **/
        function _query($query, $params=null) {
            if(!$this->isConnected()) return;

            if(count($params) == 0) {
                // Notify to start a query execution
                $this->actStart($query);
                // Execute the query statement
                 $result = ibase_query($this->fd, $query);
            }
            else {
                // Notify to start a query execution
                $log = $query."\n\t\t\t";
                $log .= implode(",", $params);
                $this->actStart($log);
                // Execute the query(for blob type)
                $query = ibase_prepare($this->fd, $query);
                $fnarr = array_merge(array($query), $params);
                $result = call_user_func_array("ibase_execute", $fnarr);
            }
            // Error Check
            if(ibase_errmsg()) $this->setError(ibase_errcode(), ibase_errmsg());
            // Notify to complete a query execution
            $this->actFinish();
            // Return the result
            return $result;
        }

        /**
         * @brief Fetch the result
         **/
        function _fetch($result, $output = null) {
            if(!$this->isConnected() || $this->isError() || !$result) return;

            while($tmp = ibase_fetch_object($result)) {
                foreach($tmp as $key => $val) {
                    $type = $output->column_type[$key];
                    // type value is null when $key is an alias. so get a type by finding actual coloumn name
                    if($type == null && $output->columns && count($output->columns)) {
                        foreach($output->columns as $cols) {
                            if($cols['alias'] == $key) {
                                // checks if the format is table.column or a regular expression
                                preg_match("/\w+[.](\w+)/", $cols['name'], $matches);
                                if($matches) {
                                    $type = $output->column_type[$matches[1]];
                                }
                                else {
                                    $type = $output->column_type[$cols['name']];
                                }
                            }
                        }
                    }

                    if(($type == "text" || $type == "bigtext") && $tmp->{$key}) {
                        $blob_data = ibase_blob_info($tmp->{$key});
                        $blob_hndl = ibase_blob_open($tmp->{$key});
                        $tmp->{$key} = ibase_blob_get($blob_hndl, $blob_data[0]);
                        ibase_blob_close($blob_hndl);
                    }
                    else if($type == "char") {
                        $tmp->{$key} = trim($tmp->{$key});	// remove blanks generated when DB character set is UTF8
                    }
                }

                $return[] = $tmp;
            }

            if(count($return)==1) return $return[0];
            return $return;
        }

        /**
         * @brief return sequence value incremented by 1(increase the value of the generator in firebird)
         **/
        function getNextSequence() {
            $gen = "GEN_".$this->prefix."sequence_ID";
            $sequence = ibase_gen_id($gen, 1);
            return $sequence;
        }

        /**
         * @brief returns if the table already exists
         **/
        function isTableExists($target_name) {
            $query = sprintf("select rdb\$relation_name from rdb\$relations where rdb\$system_flag=0 and rdb\$relation_name = '%s%s';", $this->prefix, $target_name);
            $result = $this->_query($query);
            $tmp = $this->_fetch($result);
            if(!$tmp) {
                if(!$this->transaction_started) @ibase_rollback($this->fd);
                return false;
            }
            if(!$this->transaction_started) @ibase_commit($this->fd);
            return true;
        }

        /**
         * @brief add a column to the table
         **/
        function addColumn($table_name, $column_name, $type='number', $size='', $default = '', $notnull=false) {
            $type = $this->column_type[$type];
            if(strtoupper($type)=='INTEGER') $size = null;
            else if(strtoupper($type)=='BIGINT') $size = null;
            else if(strtoupper($type)=='BLOB SUB_TYPE TEXT SEGMENT SIZE 32') $size = null;
            else if(strtoupper($type)=='VARCHAR' && !$size) $size = 256;

            $query = sprintf("ALTER TABLE \"%s%s\" ADD \"%s\" ", $this->prefix, $table_name, $column_name);
            if($size) $query .= sprintf(" %s(%s) ", $type, $size);
            else $query .= sprintf(" %s ", $type);
            if(!is_null($default)) $query .= sprintf(" DEFAULT '%s' ", $default);
            if($notnull) $query .= " NOT NULL ";

            $this->_query($query);
            if(!$this->transaction_started) @ibase_commit($this->fd);
        }

        /**
         * @brief drop a column from the table
         **/
        function dropColumn($table_name, $column_name) {
            $query = sprintf("alter table %s%s drop %s ", $this->prefix, $table_name, $column_name);
            $this->_query($query);
            if(!$this->transaction_started) @ibase_commit($this->fd);
        }


        /**
         * @brief return column information of the table
         **/
        function isColumnExists($table_name, $column_name) {
            $query = sprintf("SELECT RDB\$FIELD_NAME as \"FIELD\" FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = '%s%s'", $this->prefix, $table_name);
            $result = $this->_query($query);
            if($this->isError()) {
                if(!$this->transaction_started) @ibase_rollback($this->fd);
                return false;
            }

            $output = $this->_fetch($result);
            if(!$this->transaction_started) @ibase_commit($this->fd);

            if($output) {
                $column_name = strtolower($column_name);
                foreach($output as $key => $val) {
                    $name = trim(strtolower($val->FIELD));
                    if($column_name == $name) return true;
                }
            }
            return false;
        }

        /**
         * @brief add an index to the table
         * $target_columns = array(col1, col2)
         * $is_unique? unique : none
         **/
        function addIndex($table_name, $index_name, $target_columns, $is_unique = false) {
            // index name size should be limited to 31 byte. no index name assigned
            // if index name omitted, Firebird automatically assign its name like "RDB $10"
            // deletes indexes when deleting the table
            if(!is_array($target_columns)) $target_columns = array($target_columns);

            $query = sprintf('CREATE %s INDEX "" ON "%s%s" ("%s");', $is_unique?'UNIQUE':'', $this->prefix, $table_name, implode('", "',$target_columns));
            $this->_query($query);

            if(!$this->transaction_started) @ibase_commit($this->fd);
        }

        /**
         * @brief drop an index from the table
         **/
        function dropIndex($table_name, $index_name, $is_unique = false) {
            $query = sprintf('DROP INDEX "%s" ON "%s%s"', $index_name, $this->prefix, $table_name);
            $this->_query($query);

            if(!$this->transaction_started) @ibase_commit($this->fd);
        }


        /**
         * @brief return index information of the table
         **/
        function isIndexExists($table_name, $index_name) {
            $query = "SELECT\n";
            $query .= "   RDB\$INDICES.rdb\$index_name AS Key_name\n";
            $query .= "FROM\n";
            $query .= "   RDB\$INDICES, rdb\$index_segments\n";
            $query .= "WHERE\n";
            $query .= "   RDB\$INDICES.rdb\$index_name =  rdb\$index_segments.rdb\$index_name AND\n";
            $query .= "   RDB\$INDICES.rdb\$relation_name = '";
            $query .= $this->prefix;
            $query .= $table_name;
            $query .= "' AND\n";
            $query .= "   RDB\$INDICES.rdb\$index_name = '";
            $query .= $index_name;
            $query .= "'";

            $result = $this->_query($query);
            if($this->isError()) return;
            $output = $this->_fetch($result);

            if(!$output) {
                if(!$this->transaction_started) @ibase_rollback($this->fd);
                return false;
            }

            if(!$this->transaction_started) @ibase_commit($this->fd);

            if(!is_array($output)) $output = array($output);
            for($i=0;$i<count($output);$i++) {
                if(trim($output[$i]->KEY_NAME) == $index_name) return true;
            }

            return false;
        }

        /**
         * @brief creates a table by using xml file
         **/
        function createTableByXml($xml_doc) {
            return $this->_createTable($xml_doc);
        }

        /**
         * @brief creates a table by using xml file
         **/
        function createTableByXmlFile($file_name) {
            if(!file_exists($file_name)) return;
            // read xml file
            $buff = FileHandler::readFile($file_name);
            return $this->_createTable($buff);
        }

        /**
         * @brief create table by using the schema xml
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

                if($this->column_type[$type]=='INTEGER') $size = null;
                else if($this->column_type[$type]=='BIGINT') $size = null;
                else if($this->column_type[$type]=='BLOB SUB_TYPE TEXT SEGMENT SIZE 32') $size = null;
                else if($this->column_type[$type]=='VARCHAR' && !$size) $size = 256;

                $column_schema[] = sprintf('"%s" %s%s %s %s',
                    $name,
                    $this->column_type[$type],
                    $size?'('.$size.')':'',
                    is_null($default)?"":"DEFAULT '".$default."'",
                    $notnull?'NOT NULL':'');

                if($auto_increment) $auto_increment_list[] = $name;

                if($primary_key) $primary_list[] = $name;
                else if($unique) $unique_list[$unique][] = $name;
                else if($index) $index_list[$index][] = $name;
            }

            if(count($primary_list)) {
                $column_schema[] = sprintf("PRIMARY KEY(\"%s\")%s", implode("\",\"", $primary_list), "\n");
            }

            if(count($unique_list)) {
                foreach($unique_list as $key => $val) {
                    $column_schema[] = sprintf("UNIQUE(\"%s\")%s", implode("\",\"", $val), "\n");
                }
            }

            $schema = sprintf("CREATE TABLE \"%s\" (%s%s); \n", $table_name, "\n", implode($column_schema, ",\n"));

            $output = $this->_query($schema);
            if(!$this->transaction_started) @ibase_commit($this->fd);
            if(!$output) return false;

            if(count($index_list)) {
                foreach($index_list as $key => $val) {
                    // index name size should be limited to 31 byte. no index name assigned
                    // if index name omitted, Firebird automatically assign its name like "RDB $10"
                    // deletes indexes when deleting the table
                    $schema = sprintf("CREATE INDEX \"\" ON \"%s\" (\"%s\");",
                            $table_name, implode($val, "\",\""));
                    $output = $this->_query($schema);
                    if(!$this->transaction_started) @ibase_commit($this->fd);
                    if(!$output) return false;
                }
            }

            foreach($auto_increment_list as $increment) {
                $schema = sprintf('CREATE GENERATOR GEN_%s_ID;', $table_name);
                $output = $this->_query($schema);
                if(!$this->transaction_started) @ibase_commit($this->fd);
                if(!$output) return false;
                // auto_increment in Firebird creates a generator which activates a trigger when insert occurs
                // the generator increases the value of the generator and then insert to the table
                // The trigger below acts like auto_increment however I commented the below because the trigger cannot be defined by a query statement
                // php api has a function to increase a generator, so 
                // no need to use auto increment in XE
                /*
                $schema = 'SET TERM ^ ; ';
                $schema .= sprintf('CREATE TRIGGER "%s_BI" FOR "%s" ', $table_name, $table_name);
                $schema .= 'ACTIVE BEFORE INSERT POSITION 0 ';
                $schema .= sprintf('AS BEGIN IF (NEW."%s" IS NULL) THEN ', $increment);
                $schema .= sprintf('NEW."%s" = GEN_ID("GEN_%s_ID",1);', $increment, $table_name);
                $schema .= 'END^ SET TERM ; ^';

                $output = $this->_query($schema);
                if(!$output) return false;
                */
            }
        }

        /**
         * @brief Return conditional clause
         **/
        function getCondition($output) {
            if(!$output->conditions) return;
            $condition = $this->_getCondition($output->conditions,$output->column_type,$output->_tables);
            if($condition) $condition = ' where '.$condition;
            return $condition;
        }

        function getLeftCondition($conditions,$column_type,$tables){
            return $this->_getCondition($conditions,$column_type,$tables);
        }


        function _getCondition($conditions,$column_type,$tables) {
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

                    $value = $this->getConditionValue('"'.$name.'"', $value, $operation, $type, $column_type);
                    if(!$value) $value = $v['value'];

                    $name = $this->autoQuotes($name);
                    $value = $this->autoValueQuotes($value, $tables);

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
            // tables
            foreach($output->tables as $key => $val) {
                $table_list[] = '"'.$this->prefix.$val.'"';
            }
            // Columns
            foreach($output->columns as $key => $val) {
                $name = $val['name'];
                $value = $val['value'];

                $value = str_replace("'", "`", $value);

                if($output->column_type[$name]=="text" || $output->column_type[$name]=="bigtext"){
                    if(!isset($val['value'])) continue;
                    $blh = ibase_blob_create($this->fd);
                    ibase_blob_add($blh, $value);
                    $value = ibase_blob_close($blh);
                }
                else if($output->column_type[$name]!='number') {
//                    if(!$value) $value = 'null';
                }
                else $this->_filterNumber($value);

                $column_list[] = '"'.$name.'"';
                $value_list[] = $value;
                $questions[] = "?";
            }

            $query = sprintf("insert into %s (%s) values (%s);", implode(',',$table_list), implode(',',$column_list), implode(',', $questions));

            $result = $this->_query($query, $value_list);
            if(!$this->transaction_started) @ibase_commit($this->fd);
            return $result;
        }

        /**
         * @brief handles updateAct
         **/
        function _executeUpdateAct($output) {
            // Tables
            foreach($output->tables as $key => $val) {
                $table_list[] = '"'.$this->prefix.$val.'"';
            }
            // Columns
            foreach($output->columns as $key => $val) {
                if(!isset($val['value'])) continue;
                $name = $val['name'];
                $value = $val['value'];

                $value = str_replace("'", "`", $value);

                if(strpos($name,'.')!==false&&strpos($value,'.')!==false) $column_list[] = $name.' = '.$value;
                else {
                    if($output->column_type[$name]=="text" || $output->column_type[$name]=="bigtext"){
                        $blh = ibase_blob_create($this->fd);
                        ibase_blob_add($blh, $value);
                        $value = ibase_blob_close($blh);
                    }
                    else if($output->column_type[$name]=='number' ||
                            $output->column_type[$name]=='bignumber' ||
                            $output->column_type[$name]=='float') {
                        // put double-quotes on column name if an expression is entered
                        preg_match("/(?i)[a-z][a-z0-9_]+/", $value, $matches);

                        foreach($matches as $key => $val) {
                            $value = str_replace($val, "\"".$val."\"", $value);
                        }

                        if($matches != null) {
                            $column_list[] = sprintf("\"%s\" = %s", $name, $value);
                            continue;
                        }
                    }

                    $values[] = $value;
                    $column_list[] = sprintf('"%s" = ?', $name);
                }
            }
            // conditional clause 
            $condition = $this->getCondition($output);

            $query = sprintf("update %s set %s %s;", implode(',',$table_list), implode(',',$column_list), $condition);
            $result = $this->_query($query, $values);
            if(!$this->transaction_started) @ibase_commit($this->fd);
            return $result;
        }

        /**
         * @brief handles deleteAct
         **/
        function _executeDeleteAct($output) {
            // Tables
            foreach($output->tables as $key => $val) {
                $table_list[] = '"'.$this->prefix.$val.'"';
            }
            // List the conditional clause
            $condition = $this->getCondition($output);

            $query = sprintf("delete from %s %s;", implode(',',$table_list), $condition);

            $result = $this->_query($query);
            if(!$this->transaction_started) @ibase_commit($this->fd);
            return $result;
        }

        /**
         * @brief Handle selectAct
         *
         * In order to get a list of pages easily when selecting \n
         * it supports a method as navigation
         **/
        function _executeSelectAct($output) {
            // Tables
            $table_list = array();
            foreach($output->tables as $key => $val) {
                $table_list[] = sprintf("\"%s%s\" as \"%s\"", $this->prefix, $val, $key);
            }

            $left_join = array();
            // why???
            $left_tables= (array)$output->left_tables;

            foreach($left_tables as $key => $val) {
                $condition = $this->getLeftCondition($output->left_conditions[$key],$output->column_type,$output->_tables);
                if($condition){
                    $left_join[] = $val . ' "'.$this->prefix.$output->_tables[$key].'" as "'.$key.'" on (' . $condition . ')';
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

				if($alias == "")
					$column_list[] = $this->autoQuotes($name);
				else
					$column_list[$alias] = sprintf("%s as \"%s\"", $this->autoQuotes($name), $alias);
			}
			$columns = implode(',',$column_list);

            $condition = $this->getCondition($output);

			$output->column_list = $column_list;
            if($output->list_count && $output->page) return $this->_getNavigationData($table_list, $columns, $left_join, $condition, $output);
            // query added in the condition to use an index when ordering by list_order, update_order 
            if($output->order) {
                $conditions = $this->getConditionList($output);
                if(!in_array('list_order', $conditions) && !in_array('update_order', $conditions)) {
                    foreach($output->order as $key => $val) {
                        $col = $val[0];
                        if(!in_array($col, array('list_order','update_order'))) continue;
                        if($condition) $condition .= sprintf(' and "%s" < 2100000000 ', $col);
                        else $condition = sprintf(' where "%s" < 2100000000 ', $col);
                    }
                }
            }
            // apply when using list_count
            if($output->list_count['value']) $limit = sprintf('FIRST %d', $output->list_count['value']);
            else $limit = '';


            if($output->groups) {
                foreach($output->groups as $key => $val) {
                    $group_list[] = $this->autoQuotes($val);
					if($column_list[$val]) $output->arg_columns[] = $column_list[$val];
                }
                if(count($group_list)) $groupby_query = sprintf(" group by %s", implode(",",$group_list));
            }

            if($output->order) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf("%s %s", $this->autoQuotes($val[0]), $val[1]);
					if(count($output->arg_columns) && $column_list[$val[0]]) $output->arg_columns[] = $column_list[$val[0]];
                }
                if(count($index_list)) $orderby_query = sprintf(" order by %s", implode(",",$index_list));
            }

			if(count($output->arg_columns))
			{
				$columns = array();
				foreach($output->arg_columns as $col){
					if(strpos($col,'"')===false && strpos($col,' ')==false) $columns[] = '"'.$col.'"'; 
					else $columns[] = $col;
				}
				
				$columns = join(',',$columns);
			}

            $query = sprintf("select %s from %s %s %s %s", $columns, implode(',',$table_list),implode(' ',$left_join), $condition, $groupby_query.$orderby_query);
            $query .= ";";
			$query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';
            $result = $this->_query($query);
            if($this->isError()) {
                if(!$this->transaction_started) @ibase_rollback($this->fd);
                return;
            }

            $data = $this->_fetch($result, $output);
            if(!$this->transaction_started) @ibase_commit($this->fd);

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
         * @brief paginates when navigation info exists in the query xml
         *
         * it is convenient although its structure is not good .. -_-;
         **/
        function _getNavigationData($table_list, $columns, $left_join, $condition, $output) {
            require_once(_XE_PATH_.'classes/page/PageHandler.class.php');

			$column_list = $output->column_list;

            $query_groupby = '';
            if ($output->groups) {
                foreach ($output->groups as $key => $val){
                    $group_list[] = $this->autoQuotes($val);
					if($column_list[$val]) $output->arg_columns[] = $column_list[$val];
				}
                if (count($group_list)) $query_groupby = sprintf(" GROUP BY %s", implode(", ", $group_list));
            }

            /*
            // modified to get the number of rows by SELECT query with group by clause
            // activate the commented codes when you confirm it works correctly
            //
            $count_condition = strlen($query_groupby) ? sprintf('%s group by %s', $condition, $query_groupby) : $condition;
            $total_count = $this->getCountCache($output->tables, $count_condition);
            if($total_count === false) {
                $count_query = sprintf('select count(*) as "count" from %s %s %s', implode(', ', $table_list), implode(' ', $left_join), $count_condition);
                if (count($output->groups))
                    $count_query = sprintf('select count(*) as "count" from (%s) xet', $count_query);
                $result = $this->_query($count_query);
                $count_output = $this->_fetch($result);
                $total_count = (int)$count_output->count;
                $this->putCountCache($output->tables, $count_condition, $total_count);
            }
            */
            // total number of rows
            $count_query = sprintf("select count(*) as \"count\" from %s %s %s", implode(',',$table_list),implode(' ',$left_join), $condition);
			$count_query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id . ' count(*)'):'';
			$result = $this->_query($count_query);
			$count_output = $this->_fetch($result);
			if(!$this->transaction_started) @ibase_commit($this->fd);

			$total_count = (int)$count_output->count;

            $list_count = $output->list_count['value'];
            if(!$list_count) $list_count = 20;
            $page_count = $output->page_count['value'];
            if(!$page_count) $page_count = 10;
            $page = $output->page['value'];
            if(!$page) $page = 1;
            // total pages
            if($total_count) $total_page = (int)( ($total_count-1) / $list_count) + 1;
            else $total_page = 1;
            // check the page variables
            if($page > $total_page) $page = $total_page;
            $start_count = ($page-1)*$list_count;
            // query added in the condition to use an index when ordering by list_order, update_order 
            if($output->order) {
                $conditions = $this->getConditionList($output);
                if(!in_array('list_order', $conditions) && !in_array('update_order', $conditions)) {
                    foreach($output->order as $key => $val) {
                        $col = $val[0];
                        if(!in_array($col, array('list_order','update_order'))) continue;
                        if($condition) $condition .= sprintf(' and "%s" < 2100000000 ', $col);
                        else $condition = sprintf(' where "%s" < 2100000000 ', $col);
                    }
                }
            }

            $limit = sprintf('FIRST %d SKIP %d ', $list_count, $start_count);


            if($output->order) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf("%s %s", $this->autoQuotes($val[0]), $val[1]);
					if(count($output->arg_columns) && $column_list[$val[0]]) $output->arg_columns[] = $column_list[$val[0]];
                }
                if(count($index_list)) $orderby_query = sprintf(" ORDER BY %s", implode(",",$index_list));
            }

			if(count($output->arg_columns))
			{
				$columns = array();
				foreach($output->arg_columns as $col){
					if(strpos($col,'"')===false && strpos($col,' ')==false) $columns[] = '"'.$col.'"'; 
					else $columns[] = $col;
				}
				
				$columns = join(',',$columns);
			}

            $query = sprintf('SELECT %s %s FROM %s %s %s, %s', $limit, $columns, implode(',',$table_list), implode(' ',$left_join), $condition, $groupby_query.$orderby_query);
            $query .= ";";
			$query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';
            $result = $this->_query($query);
            if($this->isError()) {
                if(!$this->transaction_started) @ibase_rollback($this->fd);

                $buff = new Object();
                $buff->total_count = 0;
                $buff->total_page = 0;
                $buff->page = 1;
                $buff->data = array();

                $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
                return $buff;
            }

            $virtual_no = $total_count - ($page-1)*$list_count;
            while($tmp = ibase_fetch_object($result)) {
                foreach($tmp as $key => $val){
                    $type = $output->column_type[$key];
                    // $key value is an alias when type value is null. get type by finding the actual column name
                    if($type == null && $output->columns && count($output->columns)) {
                        foreach($output->columns as $cols) {
                            if($cols['alias'] == $key) {
                                // checks if the format is table.column or a regular expression
                                preg_match("/\w+[.](\w+)/", $cols['name'], $matches);
                                if($matches) {
                                    $type = $output->column_type[$matches[1]];
                                }
                                else {
                                    $type = $output->column_type[$cols['name']];
                                }
                            }
                        }
                    }

                    if(($type == "text" || $type == "bigtext") && $tmp->{$key}) {
                        $blob_data = ibase_blob_info($tmp->{$key});
                        $blob_hndl = ibase_blob_open($tmp->{$key});
                        $tmp->{$key} = ibase_blob_get($blob_hndl, $blob_data[0]);
                        ibase_blob_close($blob_hndl);
                    }
                }

                $data[$virtual_no--] = $tmp;
            }

            if(!$this->transaction_started) @ibase_commit($this->fd);

            $buff = new Object();
            $buff->total_count = $total_count;
            $buff->total_page = $total_page;
            $buff->page = $page;
            $buff->data = $data;

            $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
            return $buff;
        }
    }

return new DBFireBird;
?>
