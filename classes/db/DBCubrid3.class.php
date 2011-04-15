<?php
    /**
     * @class DBCubrid3
     * @author NHN (developers@xpressengine.com)
     * @brief Used for connecting to Cubrid DBMS
     * @version 0.1
     *
     * Modified to work with CUBRID2008 R3.1
     **/

    class DBCubrid3 extends DB
    {

        /**
         * @brief CUBRID DB connection information
         **/
        var $hostname = '127.0.0.1'; ///< hostname
        var $userid = NULL; ///< user id
        var $password = NULL; ///< password
        var $database = NULL; ///< database
        var $port = 33000; ///< db server port
        var $prefix = 'xe'; // / <prefix of XE tables(One more XE can be installed on a single DB)
        var $cutlen = 12000; // /< max size of constant in CUBRID(if string is larger than this, '...'+'...' should be used)
        var $comment_syntax = '/* %s */';
		var $magic_quotes_enabled = false;
		
        /**
         * @brief column type used in CUBRID
         *
         * column_type should be replaced for each DBMS's type
         * becasue it uses commonly defined type in the schema/query xml
         **/
        var $column_type = array(
            'bignumber' => 'numeric(20)',
            'number' => 'integer',
            'varchar' => 'character varying',
            'char' => 'character',
            'tinytext' => 'character varying(256)',
            'text' => 'character varying(1073741823)',
            'bigtext' => 'character varying(1073741823)',
            'date' => 'character varying(14)',
            'float' => 'float',
        );

        /**
         * @brief constructor
         **/
        function DBCubrid3()
        {
            $this->_setDBInfo();
            $this->_connect();
        }
		
		/**
		 * @brief create an instance of this class
		 */
		function create()
		{
			return new DBCubrid3;
		}

        /**
         * @brief Return if installable
         **/
        function isSupported()
        {
            if (!function_exists('cubrid_connect')) return false;
            return true;
        }

        /**
         * @brief DB settings and connect/close
         **/
        function _setDBInfo()
        {
            $db_info = Context::getDBInfo();
            $this->hostname = $db_info->db_hostname;
            $this->userid   = $db_info->db_userid;
            $this->password   = $db_info->db_password;
            $this->database = $db_info->db_database;
            $this->port = $db_info->db_port;
            $this->prefix = $db_info->db_table_prefix;

            if (!substr($this->prefix, -1) != '_') $this->prefix .= '_';
            $this->magic_quotes_enabled = (version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc());
        }

        /**
         * @brief DB Connection
         **/
        function _connect()
        {
            // ignore if db information not exists
	    if (!$this->hostname || !$this->userid || !$this->password || !$this->database || !$this->port) return;

            // attempts to connect
            $this->fd = @cubrid_connect ($this->hostname, $this->port, $this->database, $this->userid, $this->password);

            // check connections
            if (!$this->fd) {
                $this->setError (-1, 'database connect fail');
                return $this->is_connected = false;
            }

            $this->is_connected = true;
            $this->password = md5 ($this->password);
        }

        /**
         * @brief DB disconnect
         **/
        function close()
        {
            if (!$this->isConnected ()) return;

            @cubrid_commit ($this->fd);
            @cubrid_disconnect ($this->fd);
            $this->transaction_started = false;
        }

        /**
         * @brief handles quatation of the string variables from the query
         **/
        function addQuotes($string)
        {
            if (!$this->fd) return $string;

            if ($this->magic_quotes_enabled) {
                $string = stripslashes (str_replace ("\\","\\\\", $string));
            }

            if (!is_numeric($string)) {
                $string = str_replace("'","''",$string);
            }

            return $string;
        }

        /**
         * @brief Begin transaction
         **/
        function begin()
        {
            if (!$this->isConnected () || $this->transaction_started) return;
            $this->transaction_started = true;
        }

        /**
         * @brief Rollback
         **/
        function rollback()
        {
            if (!$this->isConnected () || !$this->transaction_started) return;
            @cubrid_rollback ($this->fd);
            $this->transaction_started = false;
        }

        /**
         * @brief Commit
         **/
        function commit()
        {
            if (!$force && (!$this->isConnected () ||
              !$this->transaction_started)) return;

            @cubrid_commit($this->fd);
            $this->transaction_started = false;
        }

        /**
         * @brief : executing the query and fetching the result
         *
         * query: run a query and return the result\n
         * fetch: NULL if no value returned \n
         *        array object if rows returned \n
         *        object if a row returned \n
         *        return\n
         **/
        function _query($query)
        {
            if (!$query || !$this->isConnected ()) return;

            // Notify to start a query execution 
            $this->actStart ($query);

            // Execute the query
            $result = @cubrid_execute ($this->fd, $query);
            // error check
            if (cubrid_error_code ()) {
                $code = cubrid_error_code ();
                $msg = cubrid_error_msg ();

                $this->setError ($code, $msg);
            }

            // Notify to complete a query execution
            $this->actFinish ();

            // Return the result
            return $result;
        }

        /**
         * @brief Fetch the result
         **/
        function _fetch($result)
        {
            if (!$this->isConnected() || $this->isError() || !$result) return;

            $col_types = cubrid_column_types ($result);
            $col_names = cubrid_column_names ($result);
            $max = count ($col_types);

            for ($count = 0; $count < $max; $count++) {
                if (preg_match ("/^char/", $col_types[$count]) > 0) {
                    $char_type_fields[] = $col_names[$count];
                }
            }

            while ($tmp = cubrid_fetch ($result, CUBRID_OBJECT)) {
                if (is_array ($char_type_fields)) {
                    foreach ($char_type_fields as $val) {
                        $tmp->{$val} = rtrim ($tmp->{$val});
                    }
                }

                $output[] = $tmp;
            }

            unset ($char_type_fields);

            if ($result) cubrid_close_request($result);

            if (count ($output) == 1) return $output[0];
            return $output;
        }

        /**
         * @brief return the sequence value incremented by 1(auto_increment column only used in the CUBRID sequence table)
         **/
        function getNextSequence()
        {
            $this->_makeSequence();

            $query = sprintf ("select \"%ssequence\".\"nextval\" as \"seq\" from db_root", $this->prefix);
            $result = $this->_query($query);
            $output = $this->_fetch($result);

            return $output->seq;
        }

        /**
         * @brief return if the table already exists
         **/
        function _makeSequence()
        {
            if($_GLOBALS['XE_EXISTS_SEQUENCE']) return;

            // check cubrid serial
            $query = sprintf('select count(*) as "count" from "db_serial" where name=\'%ssequence\'', $this->prefix);
            $result = $this->_query($query);
            $output = $this->_fetch($result);

            // if do not create serial
            if ($output->count == 0) {
                $query = sprintf('select max("a"."srl") as "srl" from '.
                                 '( select max("document_srl") as "srl" from '.
                                 '"%sdocuments" UNION '.
                                 'select max("comment_srl") as "srl" from '.
                                 '"%scomments" UNION '.
                                 'select max("member_srl") as "srl" from '.
                                 '"%smember"'.
                                  ') as "a"', $this->prefix, $this->prefix, $this->prefix);

                $result = $this->_query($query);
                $output = $this->_fetch($result);
                $srl = $output->srl;
                if ($srl < 1) {
                    $start = 1;
                }
                else {
                    $start = $srl + 1000000;
                }

                // create sequence
                $query = sprintf('create serial "%ssequence" start with %s increment by 1 minvalue 1 maxvalue 10000000000000000000000000000000000000 nocycle;', $this->prefix, $start);
                $result = $this->_query($query);
                if ($result) cubrid_close_request($result);
            }

            $_GLOBALS['XE_EXISTS_SEQUENCE'] = true;
        }


        /**
         * brief return a table if exists
         **/
        function isTableExists ($target_name)
        {
            if($target_name == 'sequence') {
                $query = sprintf ("select \"name\" from \"db_serial\" where \"name\" = '%s%s'", $this->prefix, $target_name);
            }
            else {
                $query = sprintf ("select \"class_name\" from \"db_class\" where \"class_name\" = '%s%s'", $this->prefix, $target_name);
            }

            $result = $this->_query ($query);
            if (cubrid_num_rows($result) > 0) {
                $output = true;
            }
            else {
                $output = false;
            }

            if ($result) cubrid_close_request ($result);

            return $output;
        }

        /**
         * @brief add a column to the table
         **/
        function addColumn($table_name, $column_name, $type = 'number', $size = '', $default = '', $notnull = false)
        {
            $type = strtoupper($this->column_type[$type]);
            if ($type == 'INTEGER') $size = '';

            $query = sprintf ("alter class \"%s%s\" add \"%s\" ", $this->prefix, $table_name, $column_name);

            if ($type == 'CHAR' || $type == 'VARCHAR') {
                if ($size) $size = $size * 3;
            }

            if ($size) {
                $query .= sprintf ("%s(%s) ", $type, $size);
            }
            else {
                $query .= sprintf ("%s ", $type);
            }

            if ($default) {
                if ($type == 'INTEGER' || $type == 'BIGINT' || $type=='INT') {
                    $query .= sprintf ("default %d ", $default);
                }
                else {
                    $query .= sprintf ("default '%s' ", $default);
                }
            }

            if ($notnull) $query .= "not null ";

            $result = $this->_query($query);
            if ($result) cubrid_close_request($result);
        }

        /**
         * @brief drop a column from the table
         **/
        function dropColumn ($table_name, $column_name)
        {
            $query = sprintf ("alter class \"%s%s\" drop \"%s\" ", $this->prefix, $table_name, $column_name);

            $result = $this->_query($query);
            if($result) cubrid_close_request($result);
        }

        /**
         * @brief return column information of the table
         **/
        function isColumnExists ($table_name, $column_name)
        {
            $query = sprintf ("select \"attr_name\" from \"db_attribute\" where ".  "\"attr_name\" ='%s' and \"class_name\" = '%s%s'", $column_name, $this->prefix, $table_name);
            $result = $this->_query ($query);

            if (cubrid_num_rows ($result) > 0) $output = true;
            else $output = false;

            if ($result) cubrid_close_request ($result);

            return $output;
        }

        /**
         * @brief add an index to the table
         * $target_columns = array(col1, col2)
         * $is_unique? unique : none
         **/
        function addIndex ($table_name, $index_name, $target_columns, $is_unique = false)
        {
            if (!is_array ($target_columns)) {
                $target_columns = array ($target_columns);
            }

            $query = sprintf ("create %s index \"%s\" on \"%s%s\" (%s);", $is_unique?'unique':'', $this->prefix .$index_name, $this->prefix, $table_name, '"'.implode('","',$target_columns).'"');

            $result = $this->_query($query);
            if($result) cubrid_close_request($result);
        }

        /**
         * @brief drop an index from the table
         **/
        function dropIndex ($table_name, $index_name, $is_unique = false)
        {
            $query = sprintf ("drop %s index \"%s\" on \"%s%s\"", $is_unique?'unique':'', $this->prefix .$index_name, $this->prefix, $table_name);

            $result = $this->_query($query);
            if($result) cubrid_close_request($result);
        }

        /**
         * @brief return index information of the table
         **/
        function isIndexExists ($table_name, $index_name)
        {
            $query = sprintf ("select \"index_name\" from \"db_index\" where ".  "\"class_name\" = '%s%s' and \"index_name\" = '%s' ", $this->prefix, $table_name, $this->prefix .$index_name);
            $result = $this->_query ($query);

            if ($this->isError ()) return false;

            $output = $this->_fetch ($result);

            if (!$output) return false;
            return true;
        }

        /**
         * @brief creates a table by using xml file
         **/
        function createTableByXml ($xml_doc)
        {
            return $this->_createTable ($xml_doc);
        }

        /**
         * @brief creates a table by using xml file
         **/
        function createTableByXmlFile ($file_name)
        {
            if (!file_exists ($file_name)) return;
            // read xml file
            $buff = FileHandler::readFile ($file_name);

            return $this->_createTable ($buff);
        }

        /**
         * @brief create table by using the schema xml
         *
         * type : number, varchar, tinytext, text, bigtext, char, date, \n
         * opt : notnull, default, size\n
         * index : primary key, index, unique\n
         **/
        function _createTable ($xml_doc)
        {
            // xml parsing
            $oXml = new XmlParser();
            $xml_obj = $oXml->parse($xml_doc);
            // Create a table schema
            $table_name = $xml_obj->table->attrs->name;

			// if the table already exists exit function
            if ($this->isTableExists($table_name)) return;

            // If the table name is sequence, it creates a serial
            if ($table_name == 'sequence') {
                $query = sprintf ('create serial "%s" start with 1 increment by 1'.
                                  ' minvalue 1 '.
                                  'maxvalue 10000000000000000000000000000000000000'.  ' nocycle;', $this->prefix.$table_name);

                $result = $this->_query($query);
                if($result) cubrid_close_request($result);
                return;
            }


            $table_name = $this->prefix.$table_name;

            $query = sprintf ('create class "%s";', $table_name);
            $result = $this->_query($query);
            if($result) cubrid_close_request($result);            

            if (!is_array ($xml_obj->table->column)) {
                $columns[] = $xml_obj->table->column;
            }
            else {
                $columns = $xml_obj->table->column;
            }

            $query = sprintf ("alter class \"%s\" add attribute ", $table_name);

            foreach ($columns as $column) {
                $name = $column->attrs->name;
                $type = $column->attrs->type;
                $size = $column->attrs->size;
                $notnull = $column->attrs->notnull;
                $primary_key = $column->attrs->primary_key;
                $index = $column->attrs->index;
                $unique = $column->attrs->unique;
                $default = $column->attrs->default;

                switch ($this->column_type[$type]) {
                    case 'integer' :
                        $size = null;
                        break;
                    case 'text' :
                        $size = null;
                        break;
                }

                if (isset ($default) && ($type == 'varchar' || $type == 'char' ||
                  $type == 'text' || $type == 'tinytext' || $type == 'bigtext')) {
                    $default = sprintf ("'%s'", $default);
                }

                if ($type == 'varchar' || $type == 'char') {
                    if($size) $size = $size * 3;
                }


                $column_schema[] = sprintf ('"%s" %s%s %s %s',
                                    $name,
                                    $this->column_type[$type],
                                    $size?'('.$size.')':'',
                                    isset($default)?"default ".$default:'',
                                    $notnull?'not null':'');

                if ($primary_key) {
                    $primary_list[] = $name;
                }
                else if ($unique) {
                    $unique_list[$unique][] = $name;
                }
                else if ($index) {
                    $index_list[$index][] = $name;
                }
            }

            $query .= implode (',', $column_schema).';';
            $result = $this->_query($query);
            if($result) cubrid_close_request($result);

            if (count ($primary_list)) {
                $query = sprintf ("alter class \"%s\" add attribute constraint ".  "\"pkey_%s\" PRIMARY KEY(%s);", $table_name, $table_name, '"'.implode('","',$primary_list).'"');
                $result = $this->_query($query);
                if($result) cubrid_close_request($result);
            }

            if (count ($unique_list)) {
                foreach ($unique_list as $key => $val) {
                    $query = sprintf ("create unique index \"%s\" on \"%s\" ".  "(%s);", $this->prefix .$key, $table_name, '"'.implode('","', $val).'"');
                	$result = $this->_query($query);
                	if($result) cubrid_close_request($result);
                }
            }

            if (count ($index_list)) {
                foreach ($index_list as $key => $val) {
                    $query = sprintf ("create index \"%s\" on \"%s\" (%s);", $this->prefix .$key, $table_name, '"'.implode('","',$val).'"');
                	$result = $this->_query($query);
                	if($result) cubrid_close_request($result);
                }
            }
        }

        /**
         * @brief return the condition
         **/
        function getCondition ($output)
        {
            if (!$output->conditions) return;
            $condition = $this->_getCondition ($output->conditions, $output->column_type, $output);
            if ($condition) $condition = ' where '.$condition;

            return $condition;
        }

        function _getCondition ($conditions, $column_type, &$output)
        {
            $condition = '';

            foreach ($conditions as $val) {
                $sub_condition = '';

                foreach ($val['condition'] as $v) {
                	$value = $v['value'];
                    if (!isset ($value)) continue;
                    if ($value === '') continue;
                    if(!is_string($value) && !is_integer($value) && !is_float($value) && !is_array($value)) continue;
                	
                    $name = $v['column'];
                    $operation = $v['operation'];
                    $type = $this->getColumnType ($column_type, $name);
                    $pipe = $v['pipe'];
                    $value = $this->getConditionValue ($name, $value, $operation, $type, $column_type);

                    if (!$value) {
                        $value = $v['value'];
                        if (strpos ($value, '(')) {
                            $valuetmp = $value;
                        }
                        elseif (strpos ($value, ".") === false) {
                            $valuetmp = $value;
                        }
                        else {
                            $valuetmp = '"'.str_replace('.', '"."', $value).'"';
                        }
                    }
                    else {
                        $tmp = explode('.',$value);

                        if (count($tmp)==2) {
                            $table = $tmp[0];
                            $column = $tmp[1];

                            if ($column_type[$column] && (in_array ($table, $output->tables) ||
                              array_key_exists($table, $output->tables))) {
                                $valuetmp = sprintf('"%s"."%s"', $table, $column);
                            }
                            else {
                                $valuetmp = $value;
                            }
                        }
                        else {
                            $valuetmp = $value;
                        }
                    }

                    if (strpos ($name, '(') > 0) {
                        $nametmp = $name;
                    }
                    elseif (strpos ($name, ".") === false) {
                        $nametmp = '"'.$name.'"';
                    }
                    else {
                        $nametmp = '"'.str_replace('.', '"."', $name).'"';
                    }
                    $str = $this->getConditionPart ($nametmp, $valuetmp, $operation);
                    if ($sub_condition) $sub_condition .= ' '.$pipe.' ';
                    $sub_condition .= $str;
                }

                if ($sub_condition) {
                    if ($condition && $val['pipe']) {
                        $condition .= ' '.$val['pipe'].' ';
                    }
                    $condition .= '('.$sub_condition.')';
                }
            }

            return $condition;
        }

        /**
         * @brief handles insertAct
         **/
        function _executeInsertAct ($output)
        {
            // tables
            foreach ($output->tables as $val) {
                $table_list[] = '"'.$this->prefix.$val.'"';
            }

            // columns
            foreach ($output->columns as $key => $val) {
                $name = $val['name'];
                $value = $val['value'];
                //if ($this->getColumnType ($output->column_type, $name) != 'number')
                if ($output->column_type[$name] != 'number') {
                    if (!is_null($value)) {
                        $value = "'" . $this->addQuotes($value) ."'";
                    }
                    else {
                        if ($val['notnull']=='notnull') {
                            $value = "''";
                        }
                        else {
                            //$value = 'null';
                            $value = "''";
                        }
                    }
                }
                elseif (!$value || is_numeric ($value)) {
                    $value = (int) $value;
                }

                $column_list[] = '"'.$name.'"';
                $value_list[] = $value;
            }

            $query = sprintf ("insert into %s (%s) values (%s);", implode(',', $table_list), implode(',', $column_list), implode(',', $value_list));

            $query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf (' '.$this->comment_syntax, $this->query_id):'';
            $result = $this->_query ($query);
            if ($result && !$this->transaction_started) {
                @cubrid_commit ($this->fd);
            }

            return $result;
        }

        /**
         * @brief handles updateAct
         **/
        function _executeUpdateAct ($output)
        {
            // tables
            foreach ($output->tables as $key => $val) {
                $table_list[] = '"'.$this->prefix.$val.'" as "'.$key.'"';
            }

            $check_click_count = true;

            // columns
            foreach ($output->columns as $key => $val) {
                if (!isset ($val['value'])) continue;
                $name = $val['name'];
                $value = $val['value'];

                if (substr ($value, -2) != '+1' || $output->column_type[$name] != 'number') {
                    $check_click_count = false;
                }

                for ($i = 0; $i < $key; $i++) {
                    // not allows to define the same property repeatedly in a single query in CUBRID
                    if ($output->columns[$i]['name'] == $name) break;
                }
                if ($i < $key) continue; // ignore the rest of properties if duplicated property found

                if (strpos ($name, '.') !== false && strpos ($value, '.') !== false) {
                    $column_list[] = $name.' = '.$value;
                }
                else {
                    if ($output->column_type[$name] != 'number') {
                        $check_column = false;
                        $value = "'".$this->addQuotes ($value)."'";
                    }
                    elseif (!$value || is_numeric ($value)) {
                        $value = (int) $value;
                    }

                    $column_list[] = sprintf ("\"%s\" = %s", $name, $value);
                }
            }

            // conditional clause
            $condition = $this->getCondition ($output);

            $check_click_count_condition = false;
            if ($check_click_count) {
                foreach ($output->conditions as $val) {
                    if ($val['pipe'] == 'or') {
                        $check_click_count_condition = false;
                        break;
                    }

                    foreach ($val['condition'] as $v) {
                        if ($v['operation'] == 'equal') {
                            $check_click_count_condition = true;
                        }
                        else {
                            if ($v['operation'] == 'in' && !strpos ($v['value'], ',')) {
                                $check_click_count_condition = true;
                            }
                            else {
                                $check_click_count_condition = false;
                            }
                        }

                        if ($v['pipe'] == 'or') {
                            $check_click_count_condition = false;
                            break;
                        }
                    }
                }
            }

            if ($check_click_count&& $check_click_count_condition && count ($output->tables) == 1 && count ($output->conditions) > 0 && count ($output->groups) == 0 && count ($output->order) == 0) {
                foreach ($output->columns as $v) {
                    $incr_columns[] = 'incr("'.$v['name'].'")';
                }

                $query = sprintf ('select %s from %s %s', join (',', $incr_columns), implode(',', $table_list), $condition);
            }
            else {
                $query = sprintf ("update %s set %s %s", implode (',', $table_list), implode (',', $column_list), $condition);
            }

            $result = $this->_query ($query);
            if ($result && !$this->transaction_started) @cubrid_commit ($this->fd);

            return $result;
        }

        /**
         * @brief handles deleteAct
         **/
        function _executeDeleteAct ($output)
        {
            // tables
            foreach ($output->tables as $val) {
                $table_list[] = '"'.$this->prefix.$val.'"';
            }

            // Conditional clauses
            $condition = $this->getCondition ($output);

            $query = sprintf ("delete from %s %s", implode (',',$table_list), $condition);
            $result = $this->_query ($query);
            if ($result && !$this->transaction_started) @cubrid_commit ($this->fd);

            return $result;
        }

        /**
         * @brief Handle selectAct
         *
         * to get a specific page list easily in select statement,\n
         * a method, navigation, is used 
         **/
        function _executeSelectAct ($output)
        {
            // tables
            $table_list = array ();
            foreach ($output->tables as $key => $val) {
                $table_list[] = '"'.$this->prefix.$val.'" as "'.$key.'"';
            }
            $left_join = array ();
            // why???
            $left_tables = (array) $output->left_tables;

            foreach ($left_tables as $key => $val) {
                $condition = $this->_getCondition ($output->left_conditions[$key], $output->column_type, $output);
                if ($condition) {
                    $left_join[] = $val.' "'.$this->prefix.$output->_tables[$key].  '" "'.$key.'" on ('.$condition.')';
                }
            }

            $click_count = array();
            if(!$output->columns){
                $output->columns = array(array('name'=>'*'));
            }

            $column_list = array ();
            foreach ($output->columns as $key => $val) {
                $name = $val['name'];

                $click_count = '%s';
                if ($val['click_count'] && count ($output->conditions) > 0) {
                    $click_count = 'incr(%s)';
                }

                $alias = $val['alias'] ? sprintf ('"%s"', $val['alias']) : null;
                $_alias = $val['alias'];

                if ($name == '*') {
                    $column_list[] = $name;
                }
                elseif (strpos ($name, '.') === false && strpos ($name, '(') === false) {
                    $name = sprintf ($click_count,$name);
                    if ($alias) {
                        $column_list[$alias] = sprintf('"%s" as %s', $name, $alias);
                    }
                    else {
                        $column_list[] = sprintf ('"%s"', $name);
                    }
                }
                else {
                    if (strpos ($name, '.') != false) {
                        list ($prefix, $name) = explode('.', $name);
                        if (($now_matchs = preg_match_all ("/\(/", $prefix, $xtmp)) > 0) {
                            if ($now_matchs == 1) {
                                $tmpval = explode ("(", $prefix);
                                $tmpval[1] = sprintf ('"%s"', $tmpval[1]);
                                $prefix = implode ("(", $tmpval);
                                $tmpval = explode (")", $name);
                                $tmpval[0] = sprintf ('"%s"', $tmpval[0]);
                                $name = implode (")", $tmpval);
                            }
                        }
                        else {
                            $prefix = sprintf ('"%s"', $prefix);
                            $name = ($name == '*') ? $name : sprintf('"%s"',$name);
                        }
                        $xtmp = null;
                        $now_matchs = null;
                        if($alias) $column_list[$_alias] = sprintf ($click_count, sprintf ('%s.%s', $prefix, $name)) .  ($alias ? sprintf (' as %s',$alias) : '');
                        else $column_list[] = sprintf ($click_count, sprintf ('%s.%s', $prefix, $name));
                    }
                    elseif (($now_matchs = preg_match_all ("/\(/", $name, $xtmp)) > 0) {
                        if ($now_matchs == 1 && preg_match ("/[a-zA-Z0-9]*\(\*\)/", $name) < 1) {
                            $open_pos = strpos ($name, "(");
                            $close_pos = strpos ($name, ")");

                            if (preg_match ("/,/", $name)) {
                                $tmp_func_name = sprintf ('%s', substr ($name, 0, $open_pos));
                                $tmp_params = sprintf ('%s', substr ($name, $open_pos + 1, $close_pos - $open_pos - 1));
                                $tmpval = null;
                                $tmpval = explode (',', $tmp_params);

                                foreach ($tmpval as $tmp_param) {
                                    $tmp_param_list[] = (!is_numeric ($tmp_param)) ? sprintf ('"%s"', $tmp_param) : $tmp_param;
                                }

                                $tmpval = implode (',', $tmp_param_list);
                                $name = sprintf ('%s(%s)', $tmp_func_name, $tmpval);
                            }
                            else {
                                $name = sprintf ('%s("%s")', substr ($name, 0, $open_pos), substr ($name, $open_pos + 1, $close_pos - $open_pos - 1));
                            }
                        }

                        if($alias) $column_list[$_alias] = sprintf ($click_count, $name).  ($alias ? sprintf (' as %s', $alias) : '');
                        else $column_list[] = sprintf ($click_count, $name);
                    }
                    else {
                        if($alias) $column_list[$_alias] = sprintf($click_count, $name).  ($alias ? sprintf(' as %s',$alias) : '');
                        else $column_list[] = sprintf($click_count, $name);
                    }
                }
                $columns = implode (',', $column_list);
            }

            $condition = $this->getCondition ($output);

            $output->column_list = $column_list;
            if ($output->list_count && $output->page) {
                return ($this->_getNavigationData($table_list, $columns, $left_join, $condition, $output));
            }
            
            $condition = $this->limitResultIfOrderByIsUsed($output->order, $condition);

            // group by
            $groupby_query = $this->getGroupByClause($output->groups);
            
            // order by
			$orderby_query = $this->getOrderByClause($output->order);
					
            // limit
            $limit_query = $this->getLimitClause(0, $output->list_count['value']);

            if(count($output->arg_columns))
            {
                $columns = array();
                foreach($output->arg_columns as $col){
                    if(strpos($col,'"')===false && strpos($col,' ')===false) $columns[] = '"'.$col.'"';
                    else $columns[] = $col;
                }

                $columns = join(',',$columns);
            }

            $query = sprintf ("select %s from %s %s %s %s", $columns, implode (',',$table_list), implode (' ',$left_join), $condition, $groupby_query.$orderby_query.$limit_query);
            $query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf (' '.$this->comment_syntax, $this->query_id):'';
            $result = $this->_query ($query);
            if ($this->isError ()) return;
            $data = $this->_fetch ($result);

            $buff = new Object ();
            $buff->data = $data;

            return $buff;
        }

        /**
         * @brief Retrieve text for limit clause
         *
         * Example: SELECT * FROM xe_modules LIMIT 20
         *  
         **/        
        function getLimitClause($offset, $row_count){
        	if(!$row_count) return '';
        	if($offset === 0)
        		return sprintf(' limit %d', $row_count);
        	return	sprintf(' limit %d, %d', $offset, $row_count);
        }

        /**
         * @brief Retrieve text for order by clause
         *
         * Example: SELECT * FROM xe_modules ORDER BY list_order, regdate
         *  
         **/              
        function getOrderByClause($order_list){
        	if(!$order_list) return '';
        	
            foreach ($order_list as $val) {
            	// Parse column names
            	if (strpos ($val[0], '.')) {
                	$tmpval = explode ('.', $val[0]);
                    $tmpval[0] = sprintf ('"%s"', $tmpval[0]);
                    $tmpval[1] = sprintf ('"%s"', $tmpval[1]);
                    $val[0] = implode ('.', $tmpval);
                }
                elseif (strpos ($val[0], '(')) $val[0] = $val[0];
                elseif ($val[0] == 'count') $val[0] = 'count(*)';
                else $val[0] = sprintf ('"%s"', $val[0]);
                
                // Save name
                $index_list[] = sprintf('%s %s', $val[0], $val[1]);

                // 1. This if never gets executed: column names are alias in $column_list but with real name in order clause
                // 2. There is no need for the columns in the order by clause to also show up in the select statement
                /*
                	if(count($output->arg_columns) && $column_list[$val[0]]) 
                	$output->arg_columns[] = $column_list[$val[0]];
                */
            }

            if (count($index_list)) {
                return ' order by '.implode(',', $index_list);
            }        	
            
            return '';
        }

        /**
         * @brief Retrieve text for group by clause
         *
         * Example: SELECT substr(regdate, 1, 8), count(*) FROM xe_modules GROUP BY substr(regdate, 1, 8)
         *  
         **/            
        function getGroupByClause($group_list){
        	if(!$group_list) return '';
        	if(!count($group_list)) return '';        	
          
            foreach ($group_list as $key => $value) {
	            // If value is qualified table name
	            if (strpos ($value, '.')) {
	            	$tmp = explode ('.', $value);
	                $tmp[0] = sprintf ('"%s"', $tmp[0]);
	                $tmp[1] = sprintf ('"%s"', $tmp[1]);
	                $value = implode ('.', $tmp);
	                }
	            // If value is an expression
	            elseif (strpos ($value, '(')) {
	            	$value = $value;
	            }
	            else {
	            	$value = sprintf ('"%s"', $value);
	            }
	            // Update 
	            $group_list[$key] = $value;
	
				/*
				 * The same as with order by - columns in "group by" do not need to be in the select clause
	            if(count($output->arg_columns))
	            {
	            	if($column_list[$value]) $output->arg_columns[] = $column_list[$value];
	            }
	            */
            }
            return sprintf ('group by %s', implode(',', $group_list));
        }

        /**
         * @brief Adds a where clause that retrieves only a subset of the table data if order by is used
         * @remarks Only works with tables that have columns named "list_order" or "update_order" 
         * 
         * EXAMPLE: SELECT * FROM xe_documents WHERE module_srl = 10 AND list_order < 2100000000 ORDER BY list_order
         *  
         **/          
        function limitResultIfOrderByIsUsed($order_list, $condition){
        	if(!$order_list) return $condition;
        	
            foreach ($order_list as $key => $val) {
            	$col = $val[0];
                if(!in_array($col, array('list_order','update_order'))) continue;
            
                if($condition) $condition .= sprintf(' and %s < 2100000000 ', $col);
                else $condition = sprintf(' where %s < 2100000000 ', $col);
            }
            
            return $condition;
        }        
        
        /**
         * @brief displays the current stack trace. Fetch the result
         **/
        function backtrace ()
        {
            $output = "<div style='text-align: left;'>\n";
            $output .= "<b>Backtrace:</b><br />\n";
            $backtrace = debug_backtrace ();

            foreach ($backtrace as $bt) {
                $args = '';
                foreach ($bt['args'] as $a) {
                    if (!empty ($args)) {
                        $args .= ', ';
                    }
                    switch (gettype ($a)) {
                    case 'integer':
                    case 'double':
                        $args .= $a;
                        break;
                    case 'string':
                        $a = htmlspecialchars (substr ($a, 0, 64)).
                            ((strlen ($a) > 64) ? '...' : '');
                        $args .= "\"$a\"";
                        break;
                    case 'array':
                        $args .= 'Array ('. count ($a).')';
                        break;
                    case 'object':
                        $args .= 'Object ('.get_class ($a).')';
                        break;
                    case 'resource':
                        $args .= 'Resource ('.strstr ($a, '#').')';
                        break;
                    case 'boolean':
                        $args .= $a ? 'True' : 'False';
                        break;
                    case 'NULL':
                        $args .= 'Null';
                        break;
                    default:
                        $args .= 'Unknown';
                    }
                }
                $output .= "<br />\n";
                $output .= "<b>file:</b> ".$bt['line']." - ".  $bt['file']."<br />\n";
                $output .= "<b>call:</b> ".$bt['class'].  $bt['type'].$bt['function'].$args."<br />\n";
            }
            $output .= "</div>\n";
            return $output;
        }

        /**
         * @brief paginates when navigation info exists in the query xml
         *
         * it is convenient although its structure is not good .. -_-;
         **/
        function _getNavigationData ($table_list, $columns, $left_join, $condition, $output) {
            require_once (_XE_PATH_.'classes/page/PageHandler.class.php');

            $column_list = $output->column_list;

            $count_condition = count($output->groups) ? sprintf('%s group by %s', $condition, implode(', ', $output->groups)) : $condition;
            $count_query = sprintf('select count(*) as "count" from %s %s %s', implode(', ', $table_list), implode(' ', $left_join), $count_condition);
            if (count($output->groups)) {
                $count_query = sprintf('select count(*) as "count" from (%s) xet', $count_query);
            }

            $count_query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf (' '.$this->comment_syntax, $this->query_id):'';
            $result = $this->_query($count_query);
            $count_output = $this->_fetch($result);
            $total_count = (int)$count_output->count;

            $list_count = $output->list_count['value'];
            if (!$list_count) $list_count = 20;
            $page_count = $output->page_count['value'];
            if (!$page_count) $page_count = 10;
            $page = $output->page['value'];
            if (!$page) $page = 1;

            // total pages
            if ($total_count) {
                $total_page = (int) (($total_count - 1) / $list_count) + 1;
            }
            else {
                $total_page = 1;
            }

            // check the page variables
            if ($page > $total_page) $page = $total_page;
            $start_count = ($page - 1) * $list_count;

			$condition = $this->limitResultIfOrderByIsUsed($output->order, $condition);            
            
            // group by
            $groupby_query = $this->getGroupByClause($output->groups);

            // order by
			$orderby_query = $this->getOrderByClause($output->order);
					
            // limit
            $limit_query = $this->getLimitClause($start_count, $list_count);                        

            if(count($output->arg_columns))
            {
                $columns = array();
                foreach($output->arg_columns as $col){
                    if(strpos($col,'"')===false) $columns[] = '"'.$col.'"';
                    else $columns[] = $col;
                }

                $columns = join(',',$columns);
            }

            $query = sprintf ("select %s from %s %s %s %s", $columns, implode (',',$table_list), implode (' ',$left_join), $condition, $groupby_query.$orderby_query.$limit_query);
            $query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf (' '.$this->comment_syntax, $this->query_id):'';
            $result = $this->_query ($query);

            if ($this->isError ()) {
                $buff = new Object ();
                $buff->total_count = 0;
                $buff->total_page = 0;
                $buff->page = 1;
                $buff->data = array ();

                $buff->page_navigation = new PageHandler ($total_count, $total_page, $page, $page_count);

                return $buff;
            }

            $virtual_no = $total_count - ($page - 1) * $list_count;
            while ($tmp = cubrid_fetch ($result, CUBRID_OBJECT)) {
                if ($tmp) {
                    foreach ($tmp as $k => $v) {
                        $tmp->{$k} = rtrim($v);
                    }
                }
                $data[$virtual_no--] = $tmp;
            }

            $buff = new Object ();
            $buff->total_count = $total_count;
            $buff->total_page = $total_page;
            $buff->page = $page;
            $buff->data = $data;

            $buff->page_navigation = new PageHandler ($total_count, $total_page, $page, $page_count);

            return $buff;
        }
    }

return new DBCubrid3;
?>
