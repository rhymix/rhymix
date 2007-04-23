<?php
    /**
     * @class DB
     * @author zero (zero@nzeo.com)
     * @brief  DB*의 상위 클래스
     * @version 0.1
     *
     * 제로보드의 DB 사용은 xml을 이용하여 이루어짐을 원칙으로 한다.
     * xml의 종류에는 query xml, schema xml이 있다.
     * query xml의 경우 DB::executeQuery() method를 이용하여 xml파일을 php code로 compile한 후에 실행이 된다.
     * query xml은 고유한 query id를 가지며 생성은 module에서 이루어진다.
     *
     * queryid = 모듈.쿼리명
     **/

    class DB {

        var $cond_operation = array( ///< 조건문에서 조건을 등호로 표시하는 변수
            'equal' => '=',
            'more' => '>=',
            'excess' => '>',
            'less' => '<=',
            'below' => '<',
            'notequal' => '!=',
            'notnull' => 'is not null',
            'null' => 'is null',
        );

        var $fd = NULL; ///< connector resource or file description

        var $result = NULL; ///< result

        var $errno = 0; ///< 에러 발생시 에러 코드 (0이면 에러가 없다고 정의)
        var $errstr = ''; ///< 에러 발생시 에러 메세지
        var $query = ''; ///< 가장 최근에 수행된 query string

        var $transaction_started = false; ///< 트랙잭션 처리 flag

        var $is_connected = false; ///< DB에 접속이 되었는지에 대한 flag

        var $supported_list = array(); ///< 지원하는 DB의 종류, classes/DB/DB***.class.php 를 이용하여 동적으로 작성됨

        var $cache_file = './files/cache/queries/'; ///< query cache파일의 위치

        /**
         * @brief DB를 상속받는 특정 db type의 instance를 생성 후 return
         **/
        function &getInstance($db_type = NULL) {
            if(!$db_type) $db_type = Context::getDBType();
            if(!$db_type) return new Object(-1, 'msg_db_not_setted');

            if(!$GLOBALS['__DB__']) {
                $class_name = sprintf("DB%s%s", strtoupper(substr($db_type,0,1)), strtolower(substr($db_type,1)));
                $class_file = sprintf("./classes/db/%s.class.php", $class_name);
                if(!file_exists($class_file)) new Object(-1, 'msg_db_not_setted');

                require_once($class_file);
                $eval_str = sprintf('$GLOBALS[\'__DB__\'] = new %s();', $class_name);
                eval($eval_str);
            }

            return $GLOBALS['__DB__'];
        }

        /**
         * @brief 지원 가능한 DB 목록을 return
         **/
        function getSupportedList() {
            $oDB = new DB();
            return $oDB->_getSupportedList();
        }

        /**
         * @brief 지원 가능한 DB 목록을 return
         **/
        function _getSupportedList() {
            $db_classes_path = "./classes/db/";
            $filter = "/^DB([^\.]+)\.class\.php/i";
            $supported_list = FileHandler::readDir($db_classes_path, $filter, true);
            sort($supported_list);

            // 구해진 클래스의 객체 생성후 isSupported method를 통해 지원 여부를 판단
            for($i=0;$i<count($supported_list);$i++) {
                $db_type = $supported_list[$i];
                $class_name = sprintf("DB%s%s", strtoupper(substr($db_type,0,1)), strtolower(substr($db_type,1)));
                $class_file = sprintf("./classes/db/%s.class.php", $class_name);
                if(!file_exists($class_file)) continue;

                unset($oDB);
                require_once($class_file);
                $eval_str = sprintf('$oDB = new %s();', $class_name);
                eval($eval_str);

                if(!$oDB || !$oDB->isSupported()) continue;

                $this->supported_list[] = $db_type;
            }

            return $this->supported_list;
        }

        /**
         * @brief 지원하는 DB인지에 대한 check
         **/
        function isSupported($db_type) {
            $supported_list = DB::getSupportedList();
            return in_array($db_type, $supported_list);
        }

        /**
         * @brief 접속되었는지 return
         **/
        function isConnected() {
            return $this->is_connected?true:false;
        }

        /**
         * @brief 로그 남김 
         **/
        function actStart($query) {
            if(__DEBUG__ < 2) return;
            $this->setError(0,'success');
            $this->query = $query;
            $this->act_start = getMicroTime();
        }

        function actFinish() {
            if(__DEBUG__ < 2 || !$this->query) return;
            $this->act_finish = getMicroTime();
            $elapsed_time = $this->act_finish - $this->act_start;
            $GLOBALS['__db_elapsed_time__'] += $elapsed_time;

            $str = sprintf("\t%02d. %s (%0.6f sec)\n", ++$GLOBALS['__dbcnt'], $this->query, $elapsed_time);

            if($this->isError()) {
                $str .= sprintf("\t    Query Failed : %d\n\t\t\t   %s\n", $this->errno, $this->errstr); 
            } else {
                $str .= "\t    Query Success\n";
            }
            $GLOBALS['__db_queries__'] .= $str;
            $this->query = null;
        }

        /**
         * @brief 에러발생시 에러 메세지를 남기고 debug 모드일때는 GLOBALS 변수에 에러 로깅
         **/
        function setError($errno = 0, $errstr = 'success') {
            $this->errno = $errno;
            $this->errstr = $errstr;
        }

        /**
         * @brief 오류가 발생하였는지 return
         **/
        function isError() {
            return $this->errno===0?false:true;
        }

        /**
         * @brief 에러결과를 Object 객체로 return
         **/
        function getError() {
            return new Object($this->errno, $this->errstr);
        }

        /**
         * @brief query xml 파일을 실행하여 결과를 return
         *
         * query_id = module.queryname 
         * query_id에 해당하는 xml문(or 캐싱파일)을 찾아서 컴파일 후 실행
         **/
        function executeQuery($query_id, $args = NULL) {
            if(!$query_id) return new Object(-1, 'msg_invalid_queryid');

            list($module, $id) = explode('.',$query_id);
            if(!$module || !$id) return new Object(-1, 'msg_invalid_queryid');

            $xml_file = sprintf('./modules/%s/queries/%s.xml', $module, $id);
            if(!file_exists($xml_file)) return new Object(-1, 'msg_invalid_queryid');

            // 일단 cache 파일을 찾아본다
            $cache_file = sprintf('%s%s.cache.php', $this->cache_file, $query_id);

            // 없으면 원본 쿼리 xml파일을 찾아서 파싱을 한다
            if(!file_exists($cache_file)||filectime($cache_file)<filectime($xml_file)) {
                require_once('./classes/xml/XmlQueryParser.class.php');   
                $oParser = new XmlQueryParser();
                $oParser->parse($query_id, $xml_file, $cache_file);
            }

            // 쿼리를 실행한다
            return $this->_executeQuery($cache_file, $args, $query_id);
        }

        /**
         * @brief 쿼리문을 실행하고 결과를 return한다
         **/
        function _executeQuery($cache_file, $source_args, $query_id) {
            global $lang;

            if(!file_exists($cache_file)) return new Object(-1, 'msg_invalid_queryid');

            if($source_args) $args = clone($source_args);

            $output = @include($cache_file);

            if( (is_a($output, 'Object')||is_subclass_of($output,'Object'))&&!$output->toBool()) return $output;

            // action값에 따라서 쿼리 생성으로 돌입
            switch($output->action) {
                case 'insert' :
                        $output = $this->_executeInsertAct($output);
                    break;
                case 'update' :
                        $output = $this->_executeUpdateAct($output);
                    break;
                case 'delete' :
                        $output = $this->_executeDeleteAct($output);
                    break;
                case 'select' :
                        $output = $this->_executeSelectAct($output);
                    break;
            }

            if($this->errno !=0 ) return new Object($this->errno, $this->errstr);
            if(is_a($output, 'Object') || is_subclass_of($output, 'Object')) return $output;
            return new Object();
        }

        /**
         * @brief $val을 $filter_type으로 검사
         * XmlQueryParser에서 사용하도록 함
         **/
        function checkFilter($key, $val, $filter_type) {
            global $lang;

            switch($filter_type) {
                case 'email' :
                case 'email_adderss' :
                        if(!eregi('^[_0-9a-z-]+(\.[_0-9a-z-]+)*@[0-9a-z-]+(\.[0-9a-z-]+)*$', $val)) return new Object(-1, sprintf($lang->filter->invalid_email, $lang->{$key}?$lang->{$key}:$key));
                    break;
                case 'homepage' :
                        if(!eregi('^(http|https)+(:\/\/)+[0-9a-z_-]+\.[^ ]+$', $val)) return new Object(-1, sprintf($lang->filter->invalid_homepage, $lang->{$key}?$lang->{$key}:$key));
                    break;
                case 'userid' :
                case 'user_id' :
                        if(!eregi('^[a-zA-Z]+([_0-9a-zA-Z]+)*$', $val)) return new Object(-1, sprintf($lang->filter->invalid_userid, $lang->{$key}?$lang->{$key}:$key));
                    break;
                case 'number' :
                        if(!eregi('^[0-9]+$', $val)) return new Object(-1, sprintf($lang->filter->invalid_number, $lang->{$key}?$lang->{$key}:$key));
                    break;
                case 'alpha' :
                        if(!eregi('^[a-z]+$', $val)) return new Object(-1, sprintf($lang->filter->invalid_alpha, $lang->{$key}?$lang->{$key}:$key));
                    break;
                case 'alpha_number' :
                        if(!eregi('^[0-9a-z]+$', $val)) return new Object(-1, sprintf($lang->filter->invalid_alpha_number, $lang->{$key}?$lang->{$key}:$key));
                    break;
            }

            return new Object();
        }

        /**
         * @brief 컬럼의 타입을 구해옴
         * 컬럼의 경우 a.b 와 같이 되어 있는 경우가 있어서 별도 함수가 필요
         **/
        function getColumnType($column_type_list, $name) {
            if(strpos($name,'.')===false) return $column_type_list[$name];
            list($prefix, $name) = explode('.',$name);
            return $column_type_list[$name];
        }

        /**
         * @brief 이름, 값, operation, type으로 값을 변경
         * like, like_prefix의 경우 value자체가 변경됨
         * type == number가 아니면 addQuotes()를 하고 ' ' 로 묶음
         **/
        function getConditionValue($name, $value, $operation, $type) {
            if($type == 'number') {
                if(strpos($value,',')===false && strpos($value,'(')===false) return (int)$value;
                return $value;
            }

            $value = preg_replace('/(^\'|\'$){1}/','',$value);

            switch($operation) {
                case 'like_tail' : 
                        $value = $value.'%';
                    break;
                case 'like_prefix' : 
                        $value = '%'.$value;
                    break;
                case 'like' : 
                        $value = '%'.$value.'%';
                    break;
                case 'in' :
                    return "'".$value."'";
                    break;
            }

            return "'".$this->addQuotes($value)."'";
        }

        /**
         * @brief 이름, 값, operation으로 조건절 작성
         * 조건절을 완성하기 위해 세부 조건절 마다 정리를 해서 return
         **/
        function getConditionPart($name, $value, $operation) {
            switch($operation) {
                case 'equal' :
                        if(!$value) return;
                        return $name.' = '.$value;
                    break;
                case 'more' :
                        if(!$value) return;
                        return $name.' >= '.$value;
                    break;
                case 'excess' :
                        if(!$value) return;
                        return $name.' > '.$value;
                    break;
                case 'less' :
                        if(!$value) return;
                        return $name.' <= '.$value;
                    break;
                case 'below' : 
                        if(!$value) return;
                        return $name.' < '.$value;
                    break;
                case 'like_tail' : 
                case 'like_prefix' : 
                case 'like' : 
                        if(!$value) return;
                        return $name.' like '.$value;
                    break;
                case 'in' : 
                        if(!$value) return;
                        return $name.' in ('.$value.')';
                    break;
                case 'notequal' : 
                        if(!$value) return;
                        return $name.' != '.$value;
                    break;
                case 'notnull' : 
                        return $name.' is not null';
                    break;
                case 'null' : 
                        return $name.' is null';
                    break;
            }
        }
    }
?>
