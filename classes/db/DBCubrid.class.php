<?php
    /**
     * @class DBCubrid
     * @author zero (zero@nzeo.com)
     * @brief Cubrid DBMS를 이용하기 위한 class
     * @version 0.1
     *
     * cubrid 7.0 에서 테스트 하였음.
     * 기본 쿼리만 사용하였기에 특화된 튜닝이 필요
     **/

    class DBCubrid extends DB {

        /**
         * @brief Cubrid DB에 접속하기 위한 정보
         **/
        var $hostname = '127.0.0.1'; ///< hostname
        var $userid   = NULL; ///< user id
        var $password   = NULL; ///< password
        var $database = NULL; ///< database
        var $port = 33000; ///< db server port 
        var $prefix   = 'xe'; ///< 제로보드에서 사용할 테이블들의 prefix  (한 DB에서 여러개의 제로보드 설치 가능)

        /**
         * @brief cubrid에서 사용될 column type
         *
         * column_type은 schema/query xml에서 공통 선언된 type을 이용하기 때문에
         * 각 DBMS에 맞게 replace 해주어야 한다
         **/
        var $column_type = array(
            'bignumber' => 'integer',
            'number' => 'integer',
            'varchar' => 'character varying',
            'char' => 'character varying',
            'text' => 'character varying(1073741823)',
            'bigtext' => 'character varying(1073741823)',
            'date' => 'character varying(14)',
        );

        /**
         * @brief constructor
         **/
        function DBCubrid() {
            $this->_setDBInfo();
            $this->_connect();
        }

        /**
         * @brief 설치 가능 여부를 return
         **/
        function isSupported() {
            if(!function_exists('cubrid_connect')) return false;
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
            $this->port = $db_info->db_port;
            $this->prefix = $db_info->db_table_prefix;
            if(!substr($this->prefix,-1)!='_') $this->prefix .= '_';
        }

        /**
         * @brief DB 접속
         **/
        function _connect() {
            // db 정보가 없으면 무시
            if(!$this->hostname || !$this->userid || !$this->password || !$this->database || !$this->port) return;

            // 접속시도  
            $this->fd = @cubrid_connect($this->hostname, $this->port, $this->database, $this->userid, $this->password);

            // 접속체크
            if(!$this->fd) {
                $this->setError(-1, 'database connect fail');
                return $this->is_connected = false;
            }

            $this->is_connected = true;
        }

        /**
         * @brief DB접속 해제
         **/
        function close() {
            if(!$this->isConnected()) return;
            @cubrid_commit($this->fd);
            @cubrid_disconnect($this->fd);
        }

        /**
         * @brief 쿼리에서 입력되는 문자열 변수들의 quotation 조절
         **/
        function addQuotes($string) {
            if(!$this->fd) return $string;
            if(get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)) $string = str_replace("'","''",$string);
            return $string;
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
         * @brief : 쿼리문의 실행 및 결과의 fetch 처리
         *
         * query : query문 실행하고 result return\n
         * fetch : reutrn 된 값이 없으면 NULL\n
         *         rows이면 array object\n
         *         row이면 object\n
         *         return\n
         **/
        function _query($query) {
            if(!$query || !$this->isConnected()) return;

            // 쿼리 시작을 알림
            $this->actStart($query);

            // 쿼리 문 실행
            $result = @cubrid_execute($this->fd, $query);

            // 오류 체크
            if(cubrid_error_code()) $this->setError(cubrid_error_code(), cubrid_error_msg());

            // 쿼리 실행 종료를 알림
            $this->actFinish();

            // 결과 리턴
            return $result;
        }

        /**
         * @brief 결과를 fetch
         **/
        function _fetch($result) {
            if(!$this->isConnected() || $this->isError() || !$result) return;

            while($tmp = cubrid_fetch($result, CUBRID_OBJECT)) {
                $output[] = $tmp;
            }

            if($result) cubrid_close_request($result);

            if(count($output)==1) return $output[0];
            return $output;
        }

        /**
         * @brief 1씩 증가되는 sequence값을 return (cubrid의 auto_increment는 sequence테이블에서만 사용)
         **/
        function getNextSequence() {
            $query = sprintf("select %ssequence.nextval as seq from db_root", $this->prefix);
            $result = $this->_query($query);
            $output = $this->_fetch($result);
            return $output->seq;
        }

        /**
         * @brief 테이블 기생성 여부 return
         **/
        function isTableExists($target_name) {
            $query = sprintf("select * from db_attribute where class_name = '%s%s'", $this->prefix, $this->addQuotes($target_name));
            $result = $this->_query($query);

            if(cubrid_num_rows($result)>0) $output = true;
            else $output = false;

            if($result) cubrid_close_request($result);
            return $output;
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
         * @brief schema xml을 이용하여 create class query생성
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

            // 만약 테이블 이름이 sequence라면 serial 생성
            if($table_name == 'sequence') {

                $query = sprintf('create serial %s start with 1 increment by 1 minvalue 1 maxvalue 10000000000000000000000000000000000000 nocycle;', $this->prefix.$table_name);
                return $this->_query($query);
            }

            if($this->isTableExists($table_name)) return;

            $table_name = $this->prefix.$table_name;

            $query = sprintf('create class %s;', $table_name);
            $this->_query($query);

            $query = sprintf("call change_owner('%s','%s') on class db_root;", $table_name, $this->userid);
            $this->_query($query);

            if(!is_array($xml_obj->table->column)) $columns[] = $xml_obj->table->column;
            else $columns = $xml_obj->table->column;

            $query = sprintf("alter class %s add attribute ", $table_name);

            foreach($columns as $column) {
                $name = $column->attrs->name;
                $type = $column->attrs->type;
                $size = $column->attrs->size;
                $notnull = $column->attrs->notnull;
                $primary_key = $column->attrs->primary_key;
                $index = $column->attrs->index;
                $unique = $column->attrs->unique;
                $default = $column->attrs->default;

                switch($this->column_type[$type]) {
                    case 'integer' :
                            $size = null;
                        break;
                    case 'text' :
                            $size = null;
                        break;
                }

                if($default && !is_numeric($default)) $default = "'".$default."'";

                $column_schema[] = sprintf('"%s" %s%s %s %s',
                    $name,
                    $this->column_type[$type],
                    $size?'('.$size.')':'',
                    $default?"default ".$default:'',
                    $notnull?'not null':''
                );

                if($primary_key) $primary_list[] = $name;
                else if($unique) $unique_list[$unique][] = $name;
                else if($index) $index_list[$index][] = $name;
            }

            $query .= implode(',', $column_schema).';';
            $this->_query($query);

            if(count($primary_list)) {
                $query = sprintf("alter class %s add attribute constraint \"pkey_%s\" PRIMARY KEY(%s);", $table_name, $table_name, '"'.implode('","',$primary_list).'"');
                $this->_query($query);
            }

            if(count($unique_list)) {
                foreach($unique_list as $key => $val) {
                    $query = sprintf("create unique index %s_%s on %s (%s);", $table_name, $key, $table_name, '"'.implode('","',$val).'"');
                    $this->_query($query);
                }
            }

            if(count($index_list)) {
                foreach($index_list as $key => $val) {
                    $query = sprintf("create index %s_%s on %s (%s);", $table_name, $key, $table_name, '"'.implode('","',$val).'"');
                    $this->_query($query);
                }
            }
        }

        /**
         * @brief 조건문 작성하여 return
         **/
        function getCondition($output) {
            if(!$output->conditions) return;

            $condition = "";
            foreach($output->conditions as $key => $val) {
                $sub_condition = '';
                foreach($val['condition'] as $k =>$v) {
                    if(!$v['value']) continue;

                    $name = $v['column'];
                    $operation = $v['operation'];
                    $value = $v['value'];
                    $type = $this->getColumnType($output->column_type,$name);
                    $pipe = $v['pipe'];

                    $value = $this->getConditionValue($name, $value, $operation, $type);
                    if(!$value) $value = $v['value'];
                    if(strpos($name,'.')===false) $name = '"'.$name.'"';

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
                $table_list[] = '"'.$this->prefix.$key.'"';
            }

            // 컬럼 정리 
            foreach($output->columns as $key => $val) {
                $name = $val['name'];
                $value = $val['value'];
                if($this->getColumnType($output->column_type,$name)!='number') {
                    $value = "'".$this->addQuotes($value)."'";
                    if(!$value) $value = 'null';
                } else {
                    if(!$value) $value = 0;
                }

                if(strpos($name,'.')===false) $column_list[] = '"'.$name.'"';
                else $column_list[] = $name;
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
                $table_list[] = '"'.$this->prefix.$key.'"';
            }

            // 컬럼 정리 
            foreach($output->columns as $key => $val) {
                if(!isset($val['value'])) continue;

                $name = $val['name'];

                $value = $val['value'];
                if($this->getColumnType($output->column_type,$name)!='number') $value = "'".$this->addQuotes($value)."'";
                else $value = (int)$value;

                if(strpos($name,'.')===false) $name = '"'.$name.'"';
                $column_list[] = sprintf('%s = %s', $name, $value);
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
                $table_list[] = '"'.$this->prefix.$key.'"';
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
                $table_list[] = '"'.$this->prefix.$key.'" as '.$val;
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
                        if($alias) $column_list[] = sprintf('"%s" as "%s"', $name, $alias);
                        else $column_list[] = sprintf('"%s"',$name);
                    } else {
                        if(strpos($name,'.')!=false) {
                            list($prefix, $name) = explode('.',$name);
                            if($alias) $column_list[] = sprintf('%s."%s" as "%s"', $prefix, $name, $alias);
                            else $column_list[] = sprintf('%s."%s"',$prefix,$name);
                        } else {
                            if($alias) $column_list[] = sprintf('%s as "%s"', $name, $alias);
                            else $column_list[] = sprintf('%s',$name);
                        }
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
            $count_query = sprintf('select count(*) as "count" from %s %s', implode(',',$table_list), $condition);
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

            $query = sprintf('%s for orderby_num() between %d and %d', $query, $start_count, $list_count);

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
            while($tmp = cubrid_fetch($result, CUBRID_OBJECT)) {
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
    }
?>
