<?php
    /**
     * @class DBSqlite3_pdo
     * @author zero (zero@nzeo.com)
     * @brief SQLite3를 PDO로 이용하여 class
     * @version 0.1
     **/

    class DBSqlite3_pdo extends DB {

        var $handler = NULL;
        var $stmt = NULL;
        var $bind_idx = 0;
        var $bind_vars = array();

        var $debugDetail = true;

        var $database = NULL; ///< database
        var $prefix   = 'zb'; ///< 제로보드에서 사용할 테이블들의 prefix  (한 DB에서 여러개의 제로보드 설치 가능)

        /**
         * @brief sqlite3 에서 사용될 column type
         *
         * column_type은 schema/query xml에서 공통 선언된 type을 이용하기 때문에
         * 각 DBMS에 맞게 replace 해주어야 한다
         **/
        var $column_type = array(
            'bignumber' => 'INTEGER',
            'number' => 'INTEGER',
            'varchar' => 'VARHAR',
            'char' => 'CHAR',
            'text' => 'TEXT',
            'bigtext' => 'TEXT',
            'date' => 'VARCHAR(14)',
        );

        /**
         * @brief constructor
         **/
        function DBSqlite3_pdo() {
            $this->_setDBInfo();
            $this->_connect();
        }

        /**
         * @brief 설치 가능 여부를 return
         **/
        function isSupported() {
            if(!class_exists('PDO')) return false;
            return true;
        }

        /**
         * @brief DB정보 설정 및 connect/ close
         **/
        function _setDBInfo() {
            $db_info = Context::getDBInfo();
            $this->database = $db_info->db_database;
            $this->prefix = $db_info->db_table_prefix;
            if(!substr($this->prefix,-1)!='_') $this->prefix .= '_';
        }

        /**
         * @brief DB 접속
         **/
        function _connect() {
            // db 정보가 없으면 무시
            if(!$this->database) return;

            // 데이터 베이스 파일 접속 시도
            $this->handler = new PDO('sqlite:'.$this->database);

            if(!file_exists($this->database) || $error) {
                $this->setError(-1,'permission denied to access database');
                //$this->setError(-1,$error);
                $this->is_connected = false;
                return;
            }

            // 접속체크
            $this->is_connected = true;
        }

        /**
         * @brief 트랜잭션 시작
         **/
        function begin() {
            if(!$this->is_connected || $this->transaction_started) return;
            if($this->handler->beginTransaction()) $this->transaction_started = true;
        }

        /**
         * @brief 롤백
         **/
        function rollback() {
            if(!$this->is_connected || $this->transaction_started) return;
            $this->handler->rollBack();
            $this->transaction_started = false;
        }

        /**
         * @brief 커밋
         **/
        function commit() {
            if(!$this->is_connected || $this->transaction_started) return;
            $this->handler->commit();
            $this->transaction_started = false;
        }


        /**
         * @brief DB접속 해제
         **/
        function close() {
            if(!$this->isConnected()) return;
        }

        /**
         * @brief 쿼리에서 입력되는 문자열 변수들의 quotation 조절
         **/
        function addQuotes($string) {
            if(get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)) $string = str_replace("'","''", $string);
            return $string;
        }

        /**
         * @brief : 쿼리문의 prepare
         **/
        function _prepare($query) {
            if(!$this->isConnected()) return;

            $this->query = $query;
            $this->setError(0,'success');

            $this->stmt = $this->handler->prepare($query);
            $this->bind_idx = 0;
            $this->bind_vars = array();
        }

        /**
         * @brief : stmt에 binding params
         **/
        function _bind($val) {
            if(!$this->isConnected() || !$this->stmt) return;

            $this->bind_idx ++;
            $this->bind_vars[] = $val;
            $this->stmt->bindParam($this->bind_idx, $val);
        }

        /**
         * @brief : prepare된 쿼리의 execute
         **/
        function _execute() {
            if(!$this->isConnected() || !$this->stmt) return;

            $this->stmt->execute();

            if($this->debugDetail && $this->stmt->errorCode()!='00000') debugPrint($this->query."\n".$this->stmt->errorCode()." : ".print_r($this->stmt->errorInfo(),true)."\n".print_r($this->bind_vars,true));

            $this->bind_idx = 0;
            $this->bind_vars = 0;

            if($this->stmt->errorCode()!='00000') {
                $this->setError($this->stmt->errorCode(),print_r($this->stmt->errorInfo(),true));
                $this->stmt = null;
                return false;
            }

            $output = null;
            while($tmp = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
                unset($obj);
                foreach($tmp as $key => $val) {
                    $pos = strpos($key, '.');
                    if($pos) $key = substr($key, $pos+1);
                    $obj->{$key} = $val;
                }
                $output[] = $obj;
            }

            $this->stmt = null;

            if(is_array($output) && count($output)==1) return $output[0];
            return $output;
        }

        /**
         * @brief 1씩 증가되는 sequence값을 return
         **/
        function getNextSequence() {
            $query = sprintf("insert into %ssequence (seq) values (NULL)", $this->prefix);
            $this->_prepare($query);
            $result = $this->_execute();
            return $this->handler->lastInsertId();
        }

        /**
         * @brief 테이블 기생성 여부 return
         **/
        function isTableExists($target_name) {
            $query = sprintf('pragma table_info(%s%s)', $this->prefix, $target_name);
            $this->_prepare($query);
            if(!$this->_execute()) return false;
            return true;
        }

        /**
         * @brief xml 을 받아서 테이블을 생성
         **/
        function createTableByXml($xml_doc) {
            return $this->_createTable($xml_doc);
        }

        /**
         * @brief xml 을 받아서 테이블을 생성
         **/
        function createTableByXmlFile($file_name) {
            if(!file_exists($file_name)) return;
            // xml 파일을 읽음
            $buff = FileHandler::readFile($file_name);
            return $this->_createTable($buff);
        }

        /**
         * @brief schema xml을 이용하여 create table query생성
         *
         * type : number, varchar, text, char, date, \n
         * opt : notnull, default, size\n
         * index : primary key, index, unique\n
         **/
        function _createTable($xml_doc) {
            // xml parsing
            $oXml = new XmlParser();
            $xml_obj = $oXml->parse($xml_doc);

            // 테이블 생성 schema 작성
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
                        $default?"DEFAULT '".$default."'":''
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
                    $query = sprintf('CREATE UNIQUE INDEX IF NOT EXISTS %s (%s)', $key, implode(',',$val));
                    $this->_prepare($query);
                    $this->_execute();
                    if($this->isError()) $this->rollback();
                }
            }

            if(count($unique_list)) {
                foreach($unique_list as $key => $val) {
                    $query = sprintf('CREATE INDEX IF NOT EXISTS %s (%s)', $key, implode(',',$val));
                    $this->_prepare($query);
                    $this->_execute();
                    if($this->isError()) $this->rollback();
                }
            }
        }

        /**
         * @brief 테이블 삭제
         **/
        function dropTable($target_name) {
            $query = sprintf('DROP TABLE %s%s;', $this->prefix, $this->addQuotes($target_name));
            $this->_prepare($query);
            $this->_execute();
        }

        /**
         * @brief 테이블의 이름 변경
         **/
        function renameTable($source_name, $targe_name) {
            $query = sprintf("ALTER TABLE %s%s RENAME TO %s%s;", $this->prefix, $this->addQuotes($source_name), $this->prefix, $this->addQuotes($targe_name));
            $this->_prepare($query);
            $this->_execute();
        }

        /**
         * @brief 테이블을 비움
         **/
        function truncateTable($target_name) {
            $query = sprintf("VACUUM %s%s;", $this->prefix, $this->addQuotes($target_name));
            $this->_prepare($query);
            $this->_execute();
        }

        /**
         * @brief 테이블 데이터 Dump
         *
         * @todo 아직 미구현
         **/
        function dumpTable($target_name) {
        }

        /**
         * @brief insertAct 처리
         **/
        function _executeInsertAct($tables, $column, $pass_quotes) {
            $table = array_pop($tables);

            foreach($column as $key => $val) {
                $key_list[] = $key;
                $val_list[] = $this->addQuotes($val);
                $prepare_list[] = '?';
            }

            $query = sprintf("INSERT INTO %s%s (%s) VALUES (%s);", $this->prefix, $table, implode(',',$key_list), implode(',',$prepare_list));

            $this->_prepare($query);

            $val_count = count($val_list);
            for($i=0;$i<$val_count;$i++) $this->_bind($val_list[$i]);

            $this->_execute();
            return $this->isError();
        }

        /**
         * @brief updateAct 처리
         **/
        function _executeUpdateAct($tables, $column, $args, $condition, $pass_quotes) {
            $table = array_pop($tables);

            foreach($column as $key => $val) {
                // args에 아예 해당 key가 없으면 패스
                if(!isset($args->{$key})) continue;

                if(in_array($key, $pass_quotes)) $update_list[] = sprintf('%s = ?', $key, $this->addQuotes($val));
                $val_list[] = $this->addQuotes($val);
            }
            if(!count($update_list)) return;

            $update_query = implode(',',$update_list);
            if($condition) $condition = ' WHERE '.$condition;
            $query = sprintf("UPDATE %s%s SET %s %s;", $this->prefix, $table, $update_query, $condition);

            $this->_prepare($query);

            $val_count = count($val_list);
            for($i=0;$i<$val_count;$i++) $this->_bind($val_list[$i]);

            $this->_execute();
            return $this->isError();
        }

        /**
         * @brief deleteAct 처리
         **/
        function _executeDeleteAct($tables, $condition, $pass_quotes) {
            $table = array_pop($tables);

            if($condition) $condition = ' WHERE '.$condition;

            $query = sprintf("DELETE FROM %s%s %s;", $this->prefix, $table, $condition);
            $this->_prepare($query);
            $this->_execute();

            return $this->isError();
        }

        /**
         * @brief selectAct 처리
         *
         * select의 경우 특정 페이지의 목록을 가져오는 것을 편하게 하기 위해\n
         * navigation이라는 method를 제공
         **/
        function _executeSelectAct($tables, $column, $invert_columns, $condition, $navigation, $group_script, $pass_quotes) {
            if(!count($tables)) $table = $this->prefix.array_pop($tables);
            else { 
                foreach($tables as $key => $val) $table_list[] = sprintf('%s%s as %s', $this->prefix, $key, $val);
            }
            $table = implode(',',$table_list);

            if(!$column) $columns = '*';
            else {
                foreach($invert_columns as $key => $val) {
                    $column_list[] = sprintf('%s as %s',$val, $key);
                }
                $columns = implode(',', $column_list);
            }

            if($condition) $condition = ' WHERE '.$condition;

            if($navigation->list_count) return $this->_getNavigationData($table, $columns, $condition, $navigation);

            $query = sprintf("SELECT %s FROM %s %s", $columns, $table, $condition);

            $query .= ' '.$group_script;

            if($navigation->index) {
                foreach($navigation->index as $index_obj) {
                    $index_list[] = sprintf('%s %s', $index_obj[0], $index_obj[1]);
                }
                if(count($index_list)) $query .= ' ORDER BY '.implode(',',$index_list);
            }

            $this->_prepare($query);
            $data = $this->_execute();
            if($this->isError()) return;

            $buff = new Object();
            $buff->data = $data;
            return $buff;
        }

        /**
         * @brief query xml에 navigation 정보가 있을 경우 페이징 관련 작업을 처리한다
         *
         * 그닥 좋지는 않은 구조이지만 편리하다.. -_-;
         **/
        function _getNavigationData($table, $columns, $condition, $navigation) {
            require_once('./classes/page/PageHandler.class.php');

            // 전체 개수를 구함
            $count_query = sprintf("select count(*) as count from %s %s", $table, $condition);
            $this->_prepare($count_query);
            $count_output = $this->_execute();
            $total_count = (int)$count_output->count;

            // 전체 페이지를 구함
            $total_page = (int)(($total_count-1)/$navigation->list_count) +1;

            // 페이지 변수를 체크
            if($navigation->page > $total_page) $page = $navigation->page;
            else $page = $navigation->page;
            $start_count = ($page-1)*$navigation->list_count;

            foreach($navigation->index as $index_obj) {
                $index_list[] = sprintf('%s %s', $index_obj[0], $index_obj[1]);
            }

            $index = implode(',',$index_list);
            $query = sprintf('SELECT %s FROM %s %s ORDER BY %s LIMIT %d, %d', $columns, $table, $condition, $index, $start_count, $navigation->list_count);
            $this->_prepare($query);
            $tmp_data = $this->_execute();
            if($this->isError()) {
                $buff = new Object();
                $buff->total_count = 0;
                $buff->total_page = 0;
                $buff->page = 1;
                $buff->data = array();
                $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $navigation->page_count);
                return $buff;
            }

            $virtual_no = $total_count - ($page-1)*$navigation->list_count;
            if($tmp_data) {
                if(!is_array($tmp_data)) $tmp_data = array($tmp_data);
                foreach($tmp_data as $tmp) {
                    $data[$virtual_no--] = $tmp;
                }
            } else {
                $data = null;
            }

            $buff = new Object();
            $buff->total_count = $total_count;
            $buff->total_page = $total_page;
            $buff->page = $page;
            $buff->data = $data;

            $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $navigation->page_count);
            return $buff;
        }
    }
?>
