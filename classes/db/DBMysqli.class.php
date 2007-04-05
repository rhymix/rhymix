<?php
    /**
     * @class DBMysqli
     * @author zero (zero@nzeo.com)
     * @brief MySQLi DBMS를 이용하기 위한 class
     * @version 0.1
     * @todo mysqli 미구현 (mysql과 같은 처리..)
     *
     * mysqli의 prepare, bind param등을 사용하려고 만들었으나....
     * 문제는 bind_param 시에 mixed var를 eval이 아닌 방법으로 구현할 방법을 찾지 못했음.
     **/

    class DBMysqli extends DB {

        var $handler = null;

        var $hostname = '127.0.0.1'; ///< hostname
        var $userid   = NULL; ///< user id
        var $password   = NULL; ///< password
        var $database = NULL; ///< database
        var $prefix   = 'zb'; ///< 제로보드에서 사용할 테이블들의 prefix  (한 DB에서 여러개의 제로보드 설치 가능)

        /**
         * @brief mysql에서 사용될 column type
         *
         * column_type은 schema/query xml에서 공통 선언된 type을 이용하기 때문에
         * 각 DBMS에 맞게 replace 해주어야 한다
         **/
        var $column_type = array(
            'bignumber' => 'bigint',
            'number' => 'bigint',
            'varchar' => 'varchar',
            'char' => 'char',
            'text' => 'text',
            'bigtext' => 'longtext',
            'date' => 'varchar(14)',
        );

        /**
         * @brief constructor
         **/
        function DBMysqli() {
            $this->_setDBInfo();
            $this->_connect();
        }

        /**
         * @brief 설치 가능 여부를 return
         **/
        function isSupported() {
            if(!function_exists('mysqli_connect') || mysqli_get_client_info() < "4.1.00") return false;
            return true;
        }

        /**
         * @brief DB정보 설정 및 connect/ close
         **/
        function _setDBInfo() {
            $db_info = Context::getDBInfo();
            $this->hostname = $db_info->db_hostname;
            $this->userid   = $db_info->db_userid;
            $this->password   = $db_info->db_password;
            $this->database = $db_info->db_database;
            $this->prefix = $db_info->db_table_prefix;
            if(!substr($this->prefix,-1)!='_') $this->prefix .= '_';
        }

        /**
         * @brief DB 접속
         **/
        function _connect() {
            // db 정보가 없으면 무시
            if(!$this->hostname || !$this->userid || !$this->password || !$this->database) return;

            // 접속시도  
            $this->handler = new mysqli($this->hostname, $this->userid, $this->password, $this->database);

            // 접속체크
            if(mysqli_connect_error()) $this->is_connected = false;
            else $this->is_connected = true;

            // mysql의 경우 utf8임을 지정
            $this->handler->query("SET NAMES 'utf8'");
        }

        /**
         * @brief DB접속 해제
         **/
        function close() {
            if(!$this->isConnected()) return;
            $this->handler->close();
        }

        /**
         * @brief 쿼리에서 입력되는 문자열 변수들의 quotation 조절
         **/
        function addQuotes($string) {
            if(get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)) $string = $this->handler->real_escape_string($string);
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
            $this->query = $query;

            $this->setError(0,'success');

            $result = $this->handler->query($query);

            if($this->handler->error) {
                $this->setError($this->handler->errno, $this->handler->error);
                return;
            }

            return $result;
        }

        /**
         * @brief 트랜잭션 시작
         **/
        function begin() {
        }

        /**
         * @brief 롤백
         **/
        function rollback() {
        }

        /**
         * @brief 커밋
         **/
        function commit() {
        }

        /**
         * @brief 결과를 fetch
         **/
        function _fetch($result) {
            if($this->isError() || !$result) return;

            while($tmp = $result->fetch_object()) {
                $output[] = $tmp;
            }
            if(count($output)==1) return $output[0];
            return $output;
        }

        /**
         * @brief 1씩 증가되는 sequence값을 return (mysql의 auto_increment는 sequence테이블에서만 사용)
         **/
        function getNextSequence() {
            $query = sprintf("insert into `%ssequence` (seq) values ('')", $this->prefix);
            $this->_query($query);
            return $this->handler->insert_id;
        }

        /**
         * @brief 테이블 기생성 여부 return
         **/
        function isTableExists($target_name) {
            $query = sprintf("show tables like '%s%s'", $this->prefix, $this->addQuotes($target_name));
            $result = $this->_query($query);
            $tmp = $this->_fetch($result);
            if(!$tmp) return false;
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
                $default?"default '".$default."'":'',
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
         * @brief 테이블 삭제
         **/
        function dropTable($target_name) {
            $query = sprintf('drop table `%s%s`;', $this->prefix, $this->addQuotes($target_name));
            $this->_query($query);
        }

        /**
         * @brief 테이블의 이름 변경
         **/
        function renameTable($source_name, $targe_name) {
            $query = sprintf("alter table `%s%s` rename `%s%s`;", $this->prefix, $this->addQuotes($source_name), $this->prefix, $this->addQuotes($targe_name));
            $this->_query($query);
        }

        /**
         * @brief 테이블을 비움
         **/
        function truncateTable($target_name) {
            $query = sprintf("truncate table `%s%s`;", $this->prefix, $this->addQuotes($target_name));
            $this->_query($query);
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
                if(in_array($key, $pass_quotes)) $val_list[] = $this->addQuotes($val);
                else $val_list[] = '\''.$this->addQuotes($val).'\'';
            }

            $query = sprintf("insert into `%s%s` (%s) values (%s);", $this->prefix, $table, '`'.implode('`,`',$key_list).'`', implode(',', $val_list));
            return $this->_query($query);
        }

        /**
         * @brief updateAct 처리
         **/
        function _executeUpdateAct($tables, $column, $args, $condition, $pass_quotes) {
            $table = array_pop($tables);

            foreach($column as $key => $val) {
                // args에 아예 해당 key가 없으면 패스
                if(!isset($args->{$key})) continue;
                if(in_array($key, $pass_quotes)) $update_list[] = sprintf('`%s` = %s', $key, $this->addQuotes($val));
                else $update_list[] = sprintf('`%s` = \'%s\'', $key, $this->addQuotes($val));
            }
            if(!count($update_list)) return;
            $update_query = implode(',',$update_list);

            if($condition) $condition = ' where '.$condition;

            $query = sprintf("update `%s%s` set %s %s;", $this->prefix, $table, $update_query, $condition);

            return $this->_query($query);
        }

        /**
         * @brief deleteAct 처리
         **/
        function _executeDeleteAct($tables, $condition, $pass_quotes) {
            $table = array_pop($tables);

            if($condition) $condition = ' where '.$condition;

            $query = sprintf("delete from `%s%s` %s;", $this->prefix, $table, $condition);
            return $this->_query($query);
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

            if($condition) $condition = ' where '.$condition;

            if($navigation->list_count) return $this->_getNavigationData($table, $columns, $condition, $navigation);

            $query = sprintf("select %s from %s %s", $columns, $table, $condition);

            $query .= ' '.$group_script;

            if($navigation->index) {
                foreach($navigation->index as $index_obj) {
                    $index_list[] = sprintf('%s %s', $index_obj[0], $index_obj[1]);
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
        function _getNavigationData($table, $columns, $condition, $navigation) {
            require_once('./classes/page/PageHandler.class.php');

            // 전체 개수를 구함
            $count_query = sprintf("select count(*) as count from %s %s", $table, $condition);
            $result = $this->_query($count_query);
            $count_output = $this->_fetch($result);
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
            $query = sprintf('select %s from %s %s order by %s limit %d, %d', $columns, $table, $condition, $index, $start_count, $navigation->list_count);
            $result = $this->_query($query);
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
            while($tmp = $result->fetch_object()) {
                $data[$virtual_no--] = $tmp;
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
