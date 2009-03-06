<?php
    /**
     * @class DBSqlite3_pdo
     * @author zero (zero@nzeo.com)
     * @brief SQLite3를 PDO로 이용하여 class
     * @version 0.1
     **/

    class DBSqlite3_pdo extends DB {

        /**
         * DB를 이용하기 위한 정보
         **/
        var $database = NULL; ///< database
        var $prefix   = 'xe'; ///< XE에서 사용할 테이블들의 prefix  (한 DB에서 여러개의 XE 설치 가능)

        /**
         * PDO 사용시 필요한 변수들
         **/
        var $handler = NULL;
        var $stmt = NULL;
        var $bind_idx = 0;
        var $bind_vars = array();

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
         * @brief DB접속 해제
         **/
        function close() {
            if(!$this->isConnected()) return;
            $this->commit();
        }

        /**
         * @brief 트랜잭션 시작
         **/
        function begin() {
            if(!$this->isConnected() || $this->transaction_started) return;
            if($this->handler->beginTransaction()) $this->transaction_started = true;
        }

        /**
         * @brief 롤백
         **/
        function rollback() {
            if(!$this->isConnected() || !$this->transaction_started) return;
            $this->handler->rollBack();
            $this->transaction_started = false;
        }

        /**
         * @brief 커밋
         **/
        function commit($force = false) {
            if(!$force && (!$this->isConnected() || !$this->transaction_started)) return;
            $this->handler->commit();
            $this->transaction_started = false;
        }

        /**
         * @brief 쿼리에서 입력되는 문자열 변수들의 quotation 조절
         **/
        function addQuotes($string) {
            if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)) $string = str_replace("'","''",$string);
            return $string;
        }

        /**
         * @brief : 쿼리문의 prepare
         **/
        function _prepare($query) {
            if(!$this->isConnected()) return;

            // 쿼리 시작을 알림
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
         * @brief 1씩 증가되는 sequence값을 return
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
         * @brief 테이블 기생성 여부 return
         **/
        function isTableExists($target_name) {
            $query = sprintf('pragma table_info(%s%s)', $this->prefix, $target_name);
            $this->_prepare($query);
            if(!$this->_execute()) return false;
            return true;
        }

        /**
         * @brief 특정 테이블에 특정 column 추가
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
         * @brief 특정 테이블에 특정 column 제거
         **/
        function dropColumn($table_name, $column_name) {
            $query = sprintf("alter table %s%s drop column %s ", $this->prefix, $table_name, $column_name);
            $this->_query($query);
        }

        /**
         * @brief 특정 테이블의 column의 정보를 return
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
         * @brief 특정 테이블에 특정 인덱스 추가
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
         * @brief 특정 테이블의 특정 인덱스 삭제
         **/
        function dropIndex($table_name, $index_name, $is_unique = false) {
            $key_name = sprintf('%s%s_%s', $this->prefix, $table_name, $index_name);
            $query = sprintf("DROP INDEX %s", $this->prefix, $table_name, $key_name);
            $this->_query($query);
        }

        /**
         * @brief 특정 테이블의 index 정보를 return
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
         * @brief 조건문 작성하여 return
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
                    if(!in_array(gettype($v['value']), array('string', 'integer'))) continue;

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
         * @brief insertAct 처리
         **/
        function _executeInsertAct($output) {
            // 테이블 정리
            foreach($output->tables as $key => $val) {
                $table_list[] = $this->prefix.$val;
            }

            // 컬럼 정리 
            foreach($output->columns as $key => $val) {
                $name = $val['name'];
                $value = $val['value'];

                $key_list[] = $name;

                if($output->column_type[$name]!='number') $val_list[] = $this->addQuotes($value);
                else {
                    if(!$value || is_numeric($value)) $value = (int)$value;
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
         * @brief updateAct 처리
         **/
        function _executeUpdateAct($output) {
            $table_count = count(array_values($output->tables));

            // 대상 테이블이 1개일 경우
            if($table_count == 1) {
                // 테이블 정리
                list($target_table) = array_values($output->tables);
                $target_table = $this->prefix.$target_table;

                // 컬럼 정리 
                foreach($output->columns as $key => $val) {
                    if(!isset($val['value'])) continue;
                    $name = $val['name'];
                    $value = $val['value'];
                    if(strpos($name,'.')!==false&&strpos($value,'.')!==false) $column_list[] = $name.' = '.$value;
                    else {
                        if($output->column_type[$name]!='number') $value = "'".$this->addQuotes($value)."'";
                        elseif(!$value || is_numeric($value)) $value = (int)$value;

                        $column_list[] = sprintf("%s = %s", $name, $value);
                    }
                }

                // 조건절 정리
                $condition = $this->getCondition($output);

                $query = sprintf("update %s set %s %s", $target_table, implode(',',$column_list), $condition);

            // 대상 테이블이 2개일 경우 (sqlite에서 update 테이블을 1개 이상 지정 못해서 이렇게 꽁수로... 다른 방법이 있으려나..)
            } elseif($table_count == 2) {
                // 테이블 정리
                foreach($output->tables as $key => $val) {
                    $table_list[$val] = $this->prefix.$key;
                }
                list($source_table, $target_table) = array_values($table_list);

                // 조건절 정리
                $condition = $this->getCondition($output);
                foreach($table_list as $key => $val) {
                    $condition = eregi_replace($key.'\\.', $val.'.', $condition);
                }

                // 컬럼 정리 
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
         * @brief deleteAct 처리
         **/
        function _executeDeleteAct($output) {
            // 테이블 정리
            foreach($output->tables as $key => $val) {
                $table_list[] = $this->prefix.$val;
            }

            // 조건절 정리
            $condition = $this->getCondition($output);

            $query = sprintf("delete from %s %s", implode(',',$table_list), $condition);

            $this->_prepare($query);
            return $this->_execute();
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



            if(!$output->columns) {
                $columns = '*';
            } else {
                $column_list = array();
                foreach($output->columns as $key => $val) {
                    $name = $val['name'];
                    $alias = $val['alias'];
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

            if($output->list_count && $output->page) return $this->_getNavigationData($table_list, $columns, $left_join, $condition, $output);

            // list_order, update_order 로 정렬시에 인덱스 사용을 위해 condition에 쿼리 추가
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

            // list_count를 사용할 경우 적용
            if($output->list_count['value']) $query = sprintf('%s limit %d', $query, $output->list_count['value']);

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
        function _getNavigationData($table_list, $columns, $left_join, $condition, $output) {
            require_once(_XE_PATH_.'classes/page/PageHandler.class.php');

            // 전체 개수를 구함
            $count_query = sprintf("select count(*) as count from %s %s %s", implode(',',$table_list),implode(' ',$left_join), $condition);
            $total_count = $this->getCountCache($output->tables, $condition);
            if($total_count === false) {
                $this->_prepare($count_query);
                $count_output = $this->_execute();
                $total_count = (int)$count_output->count;
                $this->putCountCache($output->tables, $condition, $total_count);
            }

            $list_count = $output->list_count['value'];
            if(!$list_count) $list_count = 20;
            $page_count = $output->page_count['value'];
            if(!$page_count) $page_count = 10;
            $page = $output->page['value'];
            if(!$page) $page = 1;

            // 전체 페이지를 구함
            if($total_count) $total_page = (int)( ($total_count-1) / $list_count) + 1;
            else $total_page = 1;

            // 페이지 변수를 체크
            if($page > $total_page) $page = $total_page;
            $start_count = ($page-1)*$list_count;

            // list_order, update_order 로 정렬시에 인덱스 사용을 위해 condition에 쿼리 추가
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

            // return 결과물 생성
            $buff = new Object();
            $buff->total_count = 0;
            $buff->total_page = 0;
            $buff->page = 1;
            $buff->data = array();
            $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);

            // 쿼리 실행
            $query = sprintf('%s limit %d, %d', $query, $start_count, $list_count);
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
?>
