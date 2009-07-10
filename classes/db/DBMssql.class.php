<?php

    /**
     * @class DBMSSQL
     * @author zero (zero@nzeo.com)
     * @brief MSSQL 을 ADODB로 이
     * @version 0.1
     **/

    class DBMssql extends DB {

        /**
         * DB를 이용하기 위한 정보
         **/
		var $conn = NULL;
		var $rs = NULL;
        var $database = NULL; ///< database
        var $prefix   = 'xe'; ///< XE에서 사용할 테이블들의 prefix  (한 DB에서 여러개의 XE 설치 가능)
        
        /**
         * @brief mssql 에서 사용될 column type
         *
         * column_type은 schema/query xml에서 공통 선언된 type을 이용하기 때문에
         * 각 DBMS에 맞게 replace 해주어야 한다
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
         * @brief 설치 가능 여부를 return
         **/
        function isSupported() {
			return false;
            if(!class_exists('COM')) return false;
            return true;
        }

        /**
         * @brief DB정보 설정 및 connect/ close
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
         * @brief DB 접속
         **/
        function _connect() {
            // db 정보가 없으면 무시
            if(!$this->hostname || !$this->database) return;

			$this->conn = new COM("ADODB.Connection",NULL,CP_UTF8); 
			//$this->conn = new COM("ADODB.Connection"); 
			$this->conn->open( sprintf('Provider=sqloledb;Data Source=%s;Initial Catalog=%s;Network Library=dbmssocn;User ID=%s;Password=%s;', $this->hostname, $this->database, $this->userid, $this->password));
	
		    // 접속체크
            $this->is_connected = true;
        }

        /**
         * @brief DB접속 해제
         **/
        function close() {
            if(!$this->isConnected()) return;
            $this->commit();
    		$this->conn->close();
			$this->rs = $this->conn = null;
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
         * @brief 트랜잭션 시작
         **/
        function begin() {
			return;
            if(!$this->isConnected() || $this->transaction_started) return;
            $this->transaction_started = true;
            $this->_query("BEGIN TRANSACTION XE_Transaction");
        }

        /**
         * @brief 롤백
         **/
        function rollback() {
			return;		
            if(!$this->isConnected() || !$this->transaction_started) return;
            $this->transaction_started = false;
            $this->_query("ROLLBACK TRANSACTION XE_Transaction");
        }

        /**
         * @brief 커밋
         **/
        function commit($force = false) {
			return;		
            if(!$force && (!$this->isConnected() || !$this->transaction_started)) return;
            $this->transaction_started = false;	
            $this->_query("COMMIT TRANSACTION XE_Transaction");
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
			if(!$this->isConnected() || !$query) return;

			 	 
			$this->rs = new COM("ADODB.Recordset"); 
			$this->rs->CursorLocation=3; 

            // 쿼리 시작을 알림
            $this->actStart($query);

            // 쿼리 문 실행
			try {
				@$this->rs->open($query,$this->conn,0,1,1);
			} catch(Exception $e) {
				//$this->setError('MSSQL Error in '.$query);
			}
			

            // 쿼리 실행 종료를 알림
            $this->actFinish();
        }

        /**
         * @brief 결과를 fetch
         **/
        function _fetch() {
			if(!$this->isConnected() || $this->isError() || !$this->rs) return;
			
			$output = array();
			$k = (int)$this->rs->Fields->Count;
			for($i=0;!$this->rs->EOF;$this->rs->MoveNext(),$i++){ 
				unset($row);
				for($j=0;$j<$k;$j++){ 
					$row->{$this->rs[$j]->name} = $this->rs[$j]->value;
				} 
				$output[$i]=$row; 
			} 
			$this->rs->close();
			$this->rs = null;
					
			if(count($output)==1) return $output[0];
            return $output;
        }

        /**
         * @brief 1씩 증가되는 sequence값을 return (mssql의 auto_increment는 sequence테이블에서만 사용)
         **/
        function getNextSequence() {
            $query = sprintf("insert into %ssequence (seq) values (ident_incr('%ssequence'))", $this->prefix, $this->prefix);
			$this->_query($query);
            $query = sprintf("select ident_current('%ssequence')+1 as sequence", $this->prefix);
            $this->_query($query);
            $tmp = $this->_fetch();
            return $tmp->sequence;
        }

        /**
         * @brief 테이블 기생성 여부 return
         **/
        function isTableExists($target_name) {
            $query = sprintf("select name from sysobjects where name = '%s%s' and xtype='U'", $this->prefix, $this->addQuotes($target_name));
            $this->_query($query);			
            $tmp = $this->_fetch();
            if(!$tmp) return false;
            return true;
        }

        /**
         * @brief 특정 테이블에 특정 column 추가
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
         * @brief 특정 테이블에 특정 column 제거
         **/
        function dropColumn($table_name, $column_name) {
			if(!$this->isColumnExists($table_name, $column_name)) return;
            $query = sprintf("alter table %s%s drop %s ", $this->prefix, $table_name, $column_name);
            $this->_query($query);
        }

        /**
         * @brief 특정 테이블의 column의 정보를 return
         **/
        function isColumnExists($table_name, $column_name) {
            $query = sprintf("select syscolumns.name as name from syscolumns, sysobjects where sysobjects.name = '%s%s' and sysobjects.id = syscolumns.id and syscolumns.name = '%s'", $this->prefix, $table_name, $column_name);
            $this->_query($query);
            if($this->isError()) return;
            $tmp = $this->_fetch();
            if(!$tmp->name) return false;
            return true;
        }

        /**
         * @brief 특정 테이블에 특정 인덱스 추가
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
         * @brief 특정 테이블의 특정 인덱스 삭제
         **/
        function dropIndex($table_name, $index_name, $is_unique = false) {
			if(!$this->isIndexExists($table_name, $index_name)) return;
            $query = sprintf("drop index %s%s.%s", $this->prefix, $table_name, $index_name);
            $this->_query($query);
        }

        /**
         * @brief 특정 테이블의 index 정보를 return
         **/
        function isIndexExists($table_name, $index_name) {
            $query = sprintf("select sysindexes.name as name from sysindexes, sysobjects where sysobjects.name = '%s%s' and sysobjects.id = sysindexes.id and sysindexes.name = '%s'", $this->prefix, $table_name, $index_name);

            $this->_query($query);
            if($this->isError()) return;
            $tmp = $this->_fetch();

            if(!$tmp->name) return false;
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
                    $default?"default '".$default."'":'',
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
					if(preg_match('/^substr\(/i',$name)) $name = preg_replace('/^substr\(/i','substring(',$name);
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
                $table_list[] = '['.$this->prefix.$val.']';
            }

            // 컬럼 정리 
            foreach($output->columns as $key => $val) {
                $name = $val['name'];
                $value = $val['value'];
                if($output->column_type[$name]!='number') {
                    $value = "'".$this->addQuotes($value)."'";
                    if(!$value) $value = 'null';
                } elseif(!$value || is_numeric($value)) $value = (int)$value;

                $column_list[] = '['.$name.']';
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
                $table_list[] = '['.$this->prefix.$val.']';
            }

            // 컬럼 정리 
            foreach($output->columns as $key => $val) {
                if(!isset($val['value'])) continue;
                $name = $val['name'];
                $value = $val['value'];
                if(strpos($name,'.')!==false&&strpos($value,'.')!==false) $column_list[] = $name.' = '.$value;
                else {
                    if($output->column_type[$name]!='number') $value = "'".$this->addQuotes($value)."'";
                    elseif(!$value || is_numeric($value)) $value = (int)$value;

                    $column_list[] = sprintf("[%s] = %s",  $name, $value);
                }
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
                $table_list[] = '['.$this->prefix.$val.']';
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
                $table_list[] = '['.$this->prefix.$val.'] as '.$key;
            }

            $left_join = array();
            // why???
            $left_tables= (array)$output->left_tables;

            foreach($left_tables as $key => $val) {
                $condition = $this->_getCondition($output->left_conditions[$key],$output->column_type);
                if($condition){
                    $left_join[] = $val . ' ['.$this->prefix.$output->_tables[$key].'] as '.$key  . ' on (' . $condition . ')';
                }
            }

            if(!$output->columns) {
                $columns = '*';
            } else {
                $column_list = array();
                foreach($output->columns as $key => $val) {
                    $name = $val['name'];
					if(preg_match('/^substr\(/i',$name)) $name = preg_replace('/^substr\(/i','substring(',$name);
                    $alias = $val['alias'];
                    if(substr($name,-1) == '*') {
                        $column_list[] = $name;
                    } elseif(strpos($name,'.')===false && strpos($name,'(')===false) {
                        if($alias) $column_list[] = sprintf('[%s] as [%s]', $name, $alias);
                        else $column_list[] = sprintf('[%s]',$name);
                    } else {
                        if($alias) $column_list[] = sprintf('%s as [%s]', $name, $alias);
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

            $query = sprintf("%s from %s %s %s", $columns, implode(',',$table_list),implode(' ',$left_join), $condition);

            if(count($output->groups)) $query .= sprintf(' group by %s', implode(',',$output->groups));

            if($output->order && !preg_match('/count\(\*\)/i',$columns) ) {
                foreach($output->order as $key => $val) {
					if(preg_match('/^substr\(/i',$val[0])) $name = preg_replace('/^substr\(/i','substring(',$val[0]);
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
                }
                if(count($index_list)) $query .= ' order by '.implode(',',$index_list);
            }

            // list_count를 사용할 경우 적용
            if($output->list_count['value']) $query = sprintf('select top %d %s', $output->list_count['value'], $query);
			else $query = "select ".$query;
			
            $this->_query($query);
            if($this->isError()) return;
            $data = $this->_fetch();

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
            $count_condition = count($output->groups) ? sprintf('%s group by %s', $condition, implode(', ', $output->groups)) : $condition;
            $total_count = $this->getCountCache($output->tables, $count_condition);
            if($total_count === false) {
                $count_query = sprintf("select count(*) as count from %s %s %s", implode(', ', $table_list), implode(' ', $left_join), $count_condition);
                if (count($output->groups)) $count_query = sprintf('select count(*) as count from (%s) xet', $count_query);
                $this->_query($count_query);
                $count_output = $this->_fetch();
                $total_count = (int)$count_output->count;
                $this->putCountCache($output->tables, $count_condition, $total_count);
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
            $conditions = $this->getConditionList($output);
            if($output->order) {
                if(!in_array('list_order', $conditions) && !in_array('update_order', $conditions)) {
                    foreach($output->order as $key => $val) {
                        $col = $val[0];
                        if(!in_array($col, array('list_order','update_order'))) continue;
                        if($condition) $condition .= sprintf(' and %s < 2100000000 ', $col);
                        else $condition = sprintf(' %s < 2100000000 ', $col);
                    }
                }
            }
			

            // group by 절 추가
            if(count($output->groups)) $group .= sprintf('group by %s', implode(',',$output->groups));

            // order 절 추가
            $order_targets = array();
            if($output->order) {
                foreach($output->order as $key => $val) {
					if(preg_match('/^substr\(/i',$val[0])) $name = preg_replace('/^substr\(/i','substring(',$val[0]);
                    $order_targets[$val[0]] = $val[1];
                    $index_list[] = sprintf('%s %s', $val[0], $val[1]);
                }
                if(count($index_list)) $order .= 'order by '.implode(',',$index_list);
            }
            if(!count($order_targets)) {
                if(in_array('list_order',$conditions)) $order_targets['list_order'] = 'asc';
                else $order_targets['xe_seq'] = 'desc';
            }

            if($start_count<1) {
                $query = sprintf('select top %d %s from %s %s %s %s %s', $list_count, $columns, implode(',',$table_list), implode(' ',$left_join), $condition, $group, $order);

            } else {
                foreach($order_targets as $k => $v) {
					$first_columns[] = sprintf('%s(%s) as %s', $v=='asc'?'max':'min', $k, $k);
					$first_sub_columns[] = $k;
                }
				
				// 1차로 order 대상에 해당 하는 값을 가져옴
				$first_query = sprintf("select %s from (select top %d %s from %s %s %s %s %s) xet", implode(',',$first_columns),  $start_count, implode(',',$first_sub_columns), implode(',',$table_list), implode(' ',$left_join), $condition, $group, $order);
				$this->_query($first_query);
				$tmp = $this->_fetch();

				
				// 1차에서 나온 값을 이용 다시 쿼리 실행
				$sub_cond = array();
                foreach($order_targets as $k => $v) {
                    $sub_cond[] = sprintf("%s %s '%s'", $k, $v=='asc'?'>':'<', $tmp->{$k});
				}
				$sub_condition = ' and( '.implode(' and ',$sub_cond).' )';
				
				if($condition) $condition .= $sub_condition;
				else $condition  = ' where '.$sub_condition;
				$query = sprintf('select top %d %s from %s %s %s %s %s', $list_count, $columns, implode(',',$table_list), implode(' ',$left_join), $condition, $group, $order);
            }

            $this->_query($query);
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
			for($i=0;!$this->rs->EOF;$this->rs->MoveNext(),$i++){ 
				unset($row);
				for($j=0;$j<$this->rs->Fields->Count;$j++){ 
					$row->{$this->rs[$j]->name} = $this->rs[$j]->value;
				} 
				$data[$virtual_no--] = $row; 
			} 
			$this->rs = null;
			
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
