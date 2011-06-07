<?php

    /**
     * @class DBMSSQL
     * @author NHN (developers@xpressengine.com)
     * @brief Modified to use MSSQL driver by sol (sol@ngleader.com)
     * @version 0.1
     **/

    class DBMssql extends DB {

        /**
         * information to connect to DB
         **/
		var $conn		= NULL;
        var $database	= NULL; ///< database
        var $prefix		= 'xe'; // / <prefix of XE tables(One more XE can be installed on a single DB)
		var $param		= array();
		var $comment_syntax = '/* %s */';
        
        /**
         * @brief column type used in mssql
         *
         * column_type should be replaced for each DBMS's type
         * becasue it uses commonly defined type in the schema/query xml
         **/
        var $column_type = array(
            'bignumber' => 'bigint',
            'number' => 'int',
            'varchar' => 'varchar',
            'char' => 'char',
            'text' => 'text',
            'bigtext' => 'text',
            'date' => 'varchar(14)',
            'float' => 'float',
        );

        /**
         * @brief constructor
         **/
        function DBMssql() {
            $this->_setDBInfo();
            $this->_connect();
        }
		
		/**
		 * @brief create an instance of this class
		 */
		function create()
		{
			return new DBMssql;
		}

        /**
         * @brief Return if installable
         **/
        function isSupported() {
            if (!extension_loaded("sqlsrv")) return false;
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
            if(!$this->hostname || !$this->database) return;

			//sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
			//sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
			//sqlsrv_configure( 'LogSubsystems', SQLSRV_LOG_SYSTEM_ALL );

			$this->conn = sqlsrv_connect( $this->hostname, 
											array( 'Database' => $this->database,'UID'=>$this->userid,'PWD'=>$this->password ));

											
			// Check connections
		    if($this->conn){
				$this->is_connected = true;
				$this->password = md5($this->password);
			}else{
				$this->is_connected = false;
			}
        }

        /**
         * @brief DB disconnect
         **/
        function close() {
            if($this->is_connected == false) return;
			
            $this->commit();
			sqlsrv_close($this->conn);
			$this->conn = null;
        }

        /**
         * @brief handles quatation of the string variables from the query
         **/
        // TODO See what to do about this
        function addQuotes($string) {
            if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            //if(!is_numeric($string)) $string = str_replace("'","''",$string);
			
            return $string;
        }

        /**
         * @brief Begin transaction
         **/
        function begin() {
            if($this->is_connected == false || $this->transaction_started) return;
			if(sqlsrv_begin_transaction( $this->conn ) === false) return;
			
            $this->transaction_started = true;
        }

        /**
         * @brief Rollback
         **/
        function rollback() {
            if($this->is_connected == false || !$this->transaction_started) return;
            
			$this->transaction_started = false;
            sqlsrv_rollback( $this->conn );
        }

        /**
         * @brief Commit
         **/
        function commit($force = false) {
            if(!$force && ($this->is_connected == false || !$this->transaction_started)) return;
			
            $this->transaction_started = false;	
            sqlsrv_commit( $this->conn );
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
        function _query($query) {
			if($this->is_connected == false || !$query) return;

			$_param = array();
			
			if(count($this->param)){
				foreach($this->param as $k => $o){
					if($o['type'] == 'number'){
						$_param[] = &$o['value'];
					}else{
						$_param[] = array(&$o['value'], SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'));
					}
				}	
			}
			
            // Notify to start a query execution
            $this->actStart($query);
			
            // Run the query statement
			$result = false;
			if(count($_param)){
				$result = @sqlsrv_query($this->conn, $query, $_param);
			}else{
				$result = @sqlsrv_query($this->conn, $query);
			}
// Error Check
			
			if(!$result) $this->setError(print_r(sqlsrv_errors(),true));
						
            // Notify to complete a query execution
            $this->actFinish();
			$this->param = array();

			return $result;
        }

        /**
         * @brief Fetch results
         **/
        function _fetch($result) {
			if(!$this->isConnected() || $this->isError() || !$result) return;
			
			$c = sqlsrv_num_fields($result);
			$m = null;
			$output = array();
			
			while(sqlsrv_fetch($result)){
				if(!$m) $m = sqlsrv_field_metadata($result);
				unset($row);
				for($i=0;$i<$c;$i++){
					$row->{$m[$i]['Name']} = sqlsrv_get_field( $result, $i, SQLSRV_PHPTYPE_STRING( 'utf-8' )); 
				}
				$output[] = $row;
			}

            if(count($output)==1) return $output[0];
            return $output;

        }

        /**
         * @brief Return sequence value incremented by 1(auto_increment is usd in the sequence table only)
         **/
        function getNextSequence() {
            $query = sprintf("insert into %ssequence (seq) values (ident_incr('%ssequence'))", $this->prefix, $this->prefix);
			$this->_query($query);
			
            $query = sprintf("select ident_current('%ssequence')+1 as sequence", $this->prefix);
            $result = $this->_query($query);
            $tmp = $this->_fetch($result);

			
            return $tmp->sequence;
        }

        /**
         * @brief Return if a table already exists
         **/
        function isTableExists($target_name) {
            $query = sprintf("select name from sysobjects where name = '%s%s' and xtype='U'", $this->prefix, $this->addQuotes($target_name));
            $result = $this->_query($query);			
            $tmp = $this->_fetch($result);
			
            if(!$tmp) return false;
            return true;
        }

        /**
         * @brief Add a column to a table
         **/
        function addColumn($table_name, $column_name, $type='number', $size='', $default = '', $notnull=false) {
			if($this->isColumnExists($table_name, $column_name)) return;
            $type = $this->column_type[$type];
            if(strtoupper($type)=='INTEGER') $size = '';

            $query = sprintf("alter table %s%s add %s ", $this->prefix, $table_name, $column_name);
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
			if(!$this->isColumnExists($table_name, $column_name)) return;
            $query = sprintf("alter table %s%s drop %s ", $this->prefix, $table_name, $column_name);
            $this->_query($query);
        }

        /**
         * @brief Return column information of a table
         **/
        function isColumnExists($table_name, $column_name) {
            $query = sprintf("select syscolumns.name as name from syscolumns, sysobjects where sysobjects.name = '%s%s' and sysobjects.id = syscolumns.id and syscolumns.name = '%s'", $this->prefix, $table_name, $column_name);
            $result = $this->_query($query);
            if($this->isError()) return;
            $tmp = $this->_fetch($result);
            if(!$tmp->name) return false;
            return true;
        }

        /**
         * @brief Add an index to a table
         * $target_columns = array(col1, col2)
         * $is_unique? unique : none
         **/
        function addIndex($table_name, $index_name, $target_columns, $is_unique = false) {
			if($this->isIndexExists($table_name, $index_name)) return;
            if(!is_array($target_columns)) $target_columns = array($target_columns);

            $query = sprintf("create %s index %s on %s%s (%s)", $is_unique?'unique':'', $index_name, $this->prefix, $table_name, implode(',',$target_columns));
            $this->_query($query);
        }

        /**
         * @brief Drop an index from a table
         **/
        function dropIndex($table_name, $index_name, $is_unique = false) {
			if(!$this->isIndexExists($table_name, $index_name)) return;
            $query = sprintf("drop index %s%s.%s", $this->prefix, $table_name, $index_name);
            $this->_query($query);
        }

        /**
         * @brief Return index information of a table
         **/
        function isIndexExists($table_name, $index_name) {
            $query = sprintf("select sysindexes.name as name from sysindexes, sysobjects where sysobjects.name = '%s%s' and sysobjects.id = sysindexes.id and sysindexes.name = '%s'", $this->prefix, $table_name, $index_name);

            $result = $this->_query($query);
            if($this->isError()) return;
            $tmp = $this->_fetch($result);

            if(!$tmp->name) return false;
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

            if($table_name == 'sequence') {
                $table_name = $this->prefix.$table_name;
                $query = sprintf('create table %s ( sequence int identity(1,1), seq int )', $table_name);
                return $this->_query($query);
            } else {
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

                    $column_schema[] = sprintf('[%s] %s%s %s %s %s %s',
                    $name,
                    $this->column_type[$type],
                    !in_array($type,array('number','text'))&&$size?'('.$size.')':'',
                    $primary_key?'primary key':'',
                    isset($default)?"default '".$default."'":'',
                    $notnull?'not null':'null',
                    $auto_increment?'identity(1,1)':''
                    );

                    if($unique) $unique_list[$unique][] = $name;
                    else if($index) $index_list[$index][] = $name;
                }
				
                $schema = sprintf('create table [%s] (xe_seq int identity(1,1),%s%s)', $this->addQuotes($table_name), "\n", implode($column_schema,",\n"));
                $output = $this->_query($schema);
                if(!$output) return false;
				
                if(count($unique_list)) {
                    foreach($unique_list as $key => $val) {
                        $query = sprintf("create unique index %s on %s (%s);", $key, $table_name, '['.implode('],[',$val).']');
                        $this->_query($query);
                    }
                }

                if(count($index_list)) {
                    foreach($index_list as $key => $val) {
                        $query = sprintf("create index %s on %s (%s);", $key, $table_name, '['.implode('],[',$val).']');
                        $this->_query($query);
                    }
                }
				return true;
            }
        }

  
        /**
         * @brief Handle the insertAct
         **/
        // TODO Lookup _filterNumber against sql injection - see if it is still needed and how to integrate
        function _executeInsertAct($queryObject) {
 			$query = '';
            return $this->_query($query);
        }

        /**
         * @brief Handle updateAct
         **/
        function _executeUpdateAct($queryObject) {
    		$query = '';
            return $this->_query($query);
        }

        /**
         * @brief Handle deleteAct
         **/
        function _executeDeleteAct($queryObject) {
			$query = '';
            return $this->_query($query);
        }

        /**
         * @brief Handle selectAct
         *
         * In order to get a list of pages easily when selecting \n
         * it supports a method as navigation
         **/
        function _executeSelectAct($queryObject) {
			$query .= (__DEBUG_QUERY__&1 && $output->query_id)?sprintf(' '.$this->comment_syntax,$this->query_id):'';
            $result = $this->_query($query);
            if($this->isError()) return;
            $data = $this->_fetch($result);

            $buff = new Object();
            $buff->data = $data;
            return $buff;
        }

  
    }

return new DBMssql;
?>
