<?php
    /**
     * @class DBSqlite2
     * @author zero (zero@nzeo.com)
     * @brief SQLite ver 2.x 를 이용하기 위한 class
     * @version 0.1
     *
     * sqlite handling class (sqlite ver 2.x)
     **/

    class DBSqlite2 extends DB {

        /**
         * DB를 이용하기 위한 정보
         **/
        var $database = NULL; ///< database
        var $prefix   = 'xe'; ///< 제로보드에서 사용할 테이블들의 prefix  (한 DB에서 여러개의 제로보드 설치 가능)

        /**
         * @brief sqlite 에서 사용될 column type
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
        function DBSqlite2() {
            $this->_setDBInfo();
            $this->_connect();
        }

        /**
         * @brief 설치 가능 여부를 return
         **/
        function isSupported() {
            if(!function_exists('sqlite_open')) return false;
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
            $this->fd = sqlite_open($this->database, 0666, &$error);
            if(!file_exists($this->database) || $error) {
                $this->setError(-1,$error);
                $this->is_connected = false;
                return;
            }

            // 접속체크
            $this->is_connected = true;
        }

        /**
         * @brief DB접속 해제
         **/
        function close() {
            if(!$this->isConnected()) return;
            sqlite_close($this->fd);
        }

        /**
         * @brief 트랜잭션 시작
         **/
        function begin() {
            if(!$this->is_connected || $this->transaction_started) return;
            if($this->_query("BEGIN;")) $this->transaction_started = true;
        }

        /**
         * @brief 롤백
         **/
        function rollback() {
            if(!$this->is_connected || !$this->transaction_started) return;
            $this->_query("ROLLBACK;");
            $this->transaction_started = false;
        }

        /**
         * @brief 커밋
         **/
        function commit() {
            if(!$this->is_connected || !$this->transaction_started) return;
            $this->_query("COMMIT;");
            $this->transaction_started = false;
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
         * @brief : 쿼리문의 실행 및 결과의 fetch 처리
         *
         * query : query문 실행하고 result return\n
         * fetch : reutrn 된 값이 없으면 NULL\n
         *         rows이면 array object\n
         *         row이면 object\n
         *         return\n
         **/
        function _query($query) {
            if(!$this->isConnected()) return;

            // 쿼리 시작을 알림
            $this->actStart($query);

            // 쿼리 문 실행
            $result = @sqlite_query($query, $this->fd);

            // 오류 체크
            if(sqlite_last_error($this->fd)) $this->setError(sqlite_last_error($this->fd), sqlite_error_string(sqlite_last_error($this->fd)));

            // 쿼리 실행 알림
            $this->actFinish();

            return $result;
        }

        /**
         * @brief 결과를 fetch
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
         * @brief 1씩 증가되는 sequence값을 return
         **/
        function getNextSequence() {
            $query = sprintf("insert into %ssequence (seq) values ('')", $this->prefix);
            $this->_query($query);
            return sqlite_last_insert_rowid($this->fd);
        }

        /**
         * @brief 테이블 기생성 여부 return
         **/
        function isTableExists($target_name) {
            $query = sprintf('pragma table_info(%s%s)', $this->prefix, $this->addQuotes($target_name));
            $result = $this->_query($query);
            if(sqlite_num_rows($result)==0) return false;
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
                        $default?"DEFAULT '".$default."'":'',
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
         * @brief 조건문 작성하여 return
         **/
        function getCondition($output) {
            if(!$output->conditions) return;

            foreach($output->conditions as $key => $val) {
                $sub_condition = '';
                foreach($val['condition'] as $k =>$v) {
                    if(!$v['value']) continue;

                    $name = $v['column'];
                    $operation = $v['operation'];
                    $value = $v['value'];
                    $type = $output->column_type[$name];
                    $pipe = $v['pipe'];

                    if(strpos($value,'.')===false) $value = $this->getConditionValue($name, $value, $operation, $type);
                    $str = $this->getConditionPart($name, $value, $operation);
                    if($sub_condition) $sub_condition .= ' '.$pipe.' ';
                    $sub_condition .=  $str;
                }
                if($sub_condition) {
                    if($condition && $val['pipe']) $condition .= ' '.$val['pipe'].' ';
                    $condition .= '('.$sub_condition.')';
                }
            }

            if($condition) $condition = ' where '.$condition;
            return $condition;
        }

        /**
         * @brief insertAct 처리
         **/
        function _executeInsertAct($output) {
            // 테이블 정리
            foreach($output->tables as $key => $val) {
                $table_list[] = $this->prefix.$key;
            }

            // 컬럼 정리 
            foreach($output->columns as $key => $val) {
                $name = $val['name'];
                $value = $val['value'];
                if($output->column_type[$name]!='number') {
                    $value = "'".$this->addQuotes($value)."'";
                    if(!$value) $value = 'null';
                } else {
                    if(!$value) $value = 0;
                }

                $column_list[] = $name;
                $value_list[] = $value;
            }

            $query = sprintf("insert into %s (%s) values (%s);", implode(',',$table_list), implode(',',$column_list), implode(',', $value_list));
            return $this->_query($query);
        }

        /**
         * @brief updateAct 처리
         **/
        function _executeUpdateAct($output) {
            // 테이블 정리
            foreach($output->tables as $key => $val) {
                $table_list[] = $this->prefix.$key;
            }

            // 컬럼 정리 
            foreach($output->columns as $key => $val) {
                if(!isset($val['value'])) continue;
                $name = $val['name'];
                $value = $val['value'];
                if($output->column_type[$name]!='number') $value = "'".$this->addQuotes($value)."'";
                else $value = (int)$value;

                $column_list[] = sprintf("%s = %s", $name, $value);
            }

            // 조건절 정리
            $condition = $this->getCondition($output);

            $query = sprintf("update %s set %s %s", implode(',',$table_list), implode(',',$column_list), $condition);

            return $this->_query($query);
        }

        /**
         * @brief deleteAct 처리
         **/
        function _executeDeleteAct($output) {
            // 테이블 정리
            foreach($output->tables as $key => $val) {
                $table_list[] = $this->prefix.$key;
            }

            // 조건절 정리
            $condition = $this->getCondition($output);

            $query = sprintf("delete from %s %s", implode(',',$table_list), $condition);

            return $this->_query($query);
        }

        /**
         * @brief selectAct 처리
         *
         * select의 경우 특정 페이지의 목록을 가져오는 것을 편하게 하기 위해\n
         * navigation이라는 method를 제공
         **/
        function _executeSelectAct($output) {
            // 테이블 정리
            $table_list = array();
            foreach($output->tables as $key => $val) {
                $table_list[] = $this->prefix.$key.' as '.$val;
            }

            if(!$output->columns) {
                $columns = '*';
            } else {
                $column_list = array();
                foreach($output->columns as $key => $val) {
                    $name = $val['name'];
                    $alias = $val['alias'];
                    if($name == '*') {
                        $column_list[] = '*';
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

            if($output->list_count) return $this->_getNavigationData($table_list, $columns, $condition, $output);

            $query = sprintf("select %s from %s %s", $columns, implode(',',$table_list), $condition);

            if($output->order) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
                }
                if(count($index_list)) $query .= ' order by '.implode(',',$index_list);
            }

            $result = $this->_query($query);
            if($this->isError()) return;
            $data = $this->_fetch($result);

            $buff = new Object();
            $buff->data = $data;
            return $buff;
        }

        /**
         * @brief query xml에 navigation 정보가 있을 경우 페이징 관련 작업을 처리한다
         *
         * 그닥 좋지는 않은 구조이지만 편리하다.. -_-;
         **/
        function _getNavigationData($table_list, $columns, $condition, $output) {
            require_once('./classes/page/PageHandler.class.php');

            // 전체 개수를 구함
            $count_query = sprintf("select count(*) as count from %s %s", implode(',',$table_list), $condition);
            $result = $this->_query($count_query);
            $count_output = $this->_fetch($result);
            $total_count = (int)$count_output->count;

            $list_count = $output->list_count['value'];
            if(!$list_count) $list_count = 20;
            $page_count = $output->page_count['value'];
            if(!$page_count) $page_count = 10;
            $page = $output->page->value;
            if(!$page) $page = 1;

            // 전체 페이지를 구함
            $total_page = (int)(($total_count-1)/$list_count) +1;

            // 페이지 변수를 체크
            if($page > $total_page) $page = $total_page;
            $start_count = ($page-1)*$list_count;

            $query = sprintf("select %s from %s %s", $columns, implode(',',$table_list), $condition);

            if($output->order) {
                foreach($output->order as $key => $val) {
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
                }
                if(count($index_list)) $query .= ' order by '.implode(',',$index_list);
            }

            $query = sprintf('%s limit %d, %d', $query, $start_count, $list_count);

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
?>
