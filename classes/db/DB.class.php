<?php
    /**
    * @class DB
    * @author zero (zero@nzeo.com)
    * @brief  DB*의 상위 클래스
    * @version 0.1
    *
    * 제로보드5의 DB 사용은 xml을 이용하여 이루어짐을 원칙으로 한다.\n
    * xml의 종류에는 query xml, schema xml이 있다.\n
    * query xml의 경우 DB::executeQuery() method를 이용하여 xml파일을\n
    * php code로 compile한 후에 실행이 된다.\n
    * query xml은 고유한 query id를 가지며 생성은 module에서 이루어진다.\n
    * \n
    * queryid = 모듈.쿼리명\n
    * \n
    * 으로 된다.\n
    **/

    class DB {

        var $fd = NULL; ///< connector resource or file description

        var $result = NULL; ///< result

        var $errno = 0; ///< 에러 발생시 에러 코드 (0이면 에러가 없다고 정의)
        var $errstr = ''; ///< 에러 발생시 에러 메세지
        var $query = ''; ///< 가장 최근에 수행된 query string

        var $is_connected = false; ///< DB에 접속이 되었는지에 대한 flag

        var $supported_list = array(); ///< 지원하는 DB의 종류, classes/DB/DB***.class.php 를 이용하여 동적으로 작성됨

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
            if(!count($this->supported_list)) {
                $db_classes_path = "./classes/db/";
                $filter = "/^DB([^\.]+)\.class\.php/i";
                $this->supported_list = FileHandler::readDir($db_classes_path, $filter, true);
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
         * @brief 에러발생시 에러 메세지를 남기고 debug 모드일때는 GLOBALS 변수에 에러 로깅
         **/
        function setError($errno, $errstr) {
            $this->errno = $errno;
            $this->errstr = $errstr;

            if(__DEBUG__ && $this->errno!=0) debugPrint(sprintf("Query Fail\t#%05d : %s - %s\n\t\t%s", $GLOBALS['__dbcnt'], $errno, $errstr, $this->query));
        }

        /**
         * @brief 접속되었는지 return
         **/
        function isConnected() {
            return $this->is_connected;
        }

        /**
         * @brief 오류가 발생하였는지 return
         **/
        function isError() {
            return $error===0?true:false;
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
            if(!$module||!$id) return new Object(-1, 'msg_invalid_queryid');

            $xml_file = sprintf('./modules/%s/queries/%s.xml', $module, $id);
            if(!file_exists($xml_file)) {
                $xml_file = sprintf('./files/modules/%s/queries/%s.xml', $module, $id);
                if(!file_exists($xml_file)) return new Object(-1, 'msg_invalid_queryid');
            }

            // 일단 cache 파일을 찾아본다
            $cache_file = sprintf('./files/queries/%s.cache.php', $query_id);

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

            if(__DEBUG__) $query_start = getMicroTime();

            if($source_args) $args = clone($source_args);
            $output = include($cache_file);

            if( (is_a($output, 'Object')||is_subclass_of($output,'Object'))&&!$output->toBool()) return $output;

            // action값에 따라서 쿼리 생성으로 돌입
            switch($action) {
                case 'insert' :
                        $output = $this->_executeInsertAct($tables, $column, $pass_quotes);
                    break;
                case 'update' :
                        $output = $this->_executeUpdateAct($tables, $column, $condition, $pass_quotes);
                    break;
                case 'delete' :
                        $output = $this->_executeDeleteAct($tables, $condition, $pass_quotes);
                    break;
                case 'select' :
                        $output = $this->_executeSelectAct($tables, $column, $invert_columns, $condition, $navigation, $group_script, $pass_quotes);
                    break;
            }

            if(__DEBUG__) {
                $query_end = getMicroTime();
                $elapsed_time = $query_end - $query_start;
                $GLOBALS['__db_elapsed_time__'] += $elapsed_time;
                $GLOBALS['__db_queries__'] .= sprintf("\t%02d. %s (%0.4f sec)\n\t    %s\n", ++$GLOBALS['__dbcnt'], $query_id, $elapsed_time, $this->query);
            }

            if($this->errno!=0) return  new Object($this->errno, $this->errstr);
            if(is_a($output, 'Object') || is_subclass_of($output, 'Object')) return $output;
            return new Object();
        }

        /**
         * @brief $val을 $filter_type으로 검사
         **/
        function _checkFilter($key, $val, $filter_type, $minlength, $maxlength) {
            global $lang;

            $length = strlen($val);
            if($minlength && $length < $minlength) return new Object(-1, sprintf($lang->filter->outofrange, $lang->{$key}?$lang->{$key}:$key));
            if($maxlength && $length > $maxlength) return new Object(-1, sprintf($lang->filter->outofrange, $lang->{$key}?$lang->{$key}:$key));

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
         * @brief 조건문들 정리
         **/
        function _combineCondition($cond_group, $group_pipe) {
            if(!is_array($cond_group)) return;
            $cond_query = '';

            foreach($cond_group as $group_idx => $group) {
                if(!is_array($group)) continue;

                $buff = '';
                foreach($group as $key => $val) {
                    $pipe = key($val);
                    $cond = array_pop($val);
                    if($buff) $buff .= $pipe.' '.$cond;
                    else $buff = $cond;
                }

                $g_pipe = $group_pipe[$group_idx];
                if(!$g_pipe) $g_pipe = 'and';
                if($cond_query) $cond_query .= sprintf(' %s ( %s )', $g_pipe, $buff);
                else $cond_query = '('.$buff.')';
            }
            return $cond_query;
        }

    }
?>
