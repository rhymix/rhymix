<?php
    /**
     * @file   config/func.inc.php
     * @author zero (zero@nzeo.com)
     * @brief  편의 목적으로 만든 함수라이브러리 파일
    **/

    /**
     * @brief php5에 대비하여 clone 정의
     **/
    if (version_compare(phpversion(), '5.0') < 0) {
        eval('
            function clone($object) {
            return $object;
            }
        ');
    }

    /**
     * @brief ModuleHandler::getModuleObject($module_name, $type)을 쓰기 쉽게 함수로 선언
     * @param module_name 모듈이름
     * @param type disp, proc, controller, class
     * @return module instance
     **/
    function &getModule($module_name, $type = 'view') {
        return ModuleHandler::getModuleInstance($module_name, $type);
    }

    /**
     * @brief module의 controller 객체 생성용
     * @param module_name 모듈이름
     * @return module controller instance
     **/
    function &getController($module_name) {
        return getModule($module_name, 'controller'); 
    }

    /**
     * @brief module의 view 객체 생성용
     * @param module_name 모듈이름
     * @return module view instance
     **/
    function &getView($module_name) {
        return getModule($module_name, 'view'); 
    }

    /**
     * @brief module의 model 객체 생성용
     * @param module_name 모듈이름
     * @return module model instance
     **/
    function &getModel($module_name) {
        return getModule($module_name, 'model'); 
    }

    /**
     * @brief module의 상위 class 객체 생성용
     * @param module_name 모듈이름
     * @return module class instance
     **/
    function &getClass($module_name) {
        return getModule($module_name, 'class'); 
    }

    /**
     * @brief DB::executeQuery() 의 alias
     * @param query_id 쿼리 ID ( 모듈명.쿼리XML파일 )
     * @param args object 변수로 선언된 인자값
     * @return 처리결과
     **/
    function executeQuery($query_id, $args = null) {
        $oDB = &DB::getInstance();
        return $oDB->executeQuery($query_id, $args);
    }

    /**
     * @brief DB::getNextSequence() 의 alias
     * @return big int
     **/
    function getNextSequence() {
        $oDB = &DB::getInstance();
        return $oDB->getNextSequence();
    }

    /**
     * @brief Context::getUrl($args_list)를 쓰기 쉽게 함수로 선언
     * @return string
     *
     * getUrl()은 현재 요청된 RequestURI에 주어진 인자의 값으로 변형하여 url을 리턴한다\n
     * 1. 인자는 (key, value)... 의 형식으로 주어져야 한다.\n
     *    ex) getUrl('key1','val1', 'key2', '') : key1, key2를 val1과 '' 로 변형\n
     * 2. 아무런 인자가 없으면 argument를 제외한 url을 리턴
     * 3. 첫 인자값이 '' 이면 RequestUri에다가 추가된 args_list로 url을 만듬
     **/
    function getUrl() {
        $num_args = func_num_args();
        $args_list = func_get_args();

        if(!$num_args) return Context::getRequestUri();

        return Context::getUrl($num_args, $args_list);
    }

    /**
     * @brief 주어진 문자를 주어진 크기로 자르고 잘라졌을 경우 주어진 꼬리를 담
     * @param string 자를 원 문자열
     * @param cut_size 주어진 원 문자열을 자를 크기
     * @param tail 잘라졌을 경우 문자열의 제일 뒤에 붙을 꼬리
     * @return string
     *
     * 손쉽고 확실한 변환을 위해 2byte unicode로 변형한후 처리를 한다
     **/
    function cut_str($string, $cut_size, $tail='...') {
        if(!$string || !$cut_size) return $string;
        $unicode_str = iconv("UTF-8","UCS-2",$string);
        if(strlen($unicode_str) < $cut_size*2) return $string;

        $output = substr($unicode_str, 0, $cut_size*2);
        return iconv("UCS-2","UTF-8",$output_str).$tail;
    }

    /**
     * @brief YYYYMMDDHHIISS 형식의 시간값을 원하는 시간 포맷으로 변형
     * @param str YYYYMMDDHHIISS 형식의 시간값
     * @param format php date()함수의 시간 포맷
     * @return string
     **/
    function zdate($str, $format = "Y-m-d H:i:s") {
        if(!$str) return;
        return date($format, mktime(substr($str,8,2), substr($str,10,2), substr($str,12,2), substr($str,4,2), substr($str,6,2), substr($str,0,4)));
    }

    /**
     * @brief 간단한 console debugging 함수
     * @param buff 출력하고자 하는 object
     * @param display_line 구분자를 출력할 것인지에 대한 플래그 (기본:true)
     * @return none
     *
     * ./files/_debug_message.php 파일에 $buff 내용을 출력한다.
     * tail -f ./files/_debug_message.php 하여 계속 살펴 볼 수 있다
     **/
    function debugPrint($buff = null, $display_line = true) {
        if($buff == null) $buff = $GLOBALS['HTTP_RAW_POST_DATA'];
        $debug_file = "./files/_debug_message.php";
        $buff = sprintf("%s\n",print_r($buff,true));

        if($display_line) $buff = "\n====================================\n".$buff."------------------------------------\n";

        if(@!$fp = fopen($debug_file,"a")) return;
        fwrite($fp, $buff);
        fclose($fp);
    }

    /**
     * @brief microtime() return
     * @return float
     **/
    function getMicroTime() {
        list($time1, $time2) = explode(' ', microtime());
        return (float)$time1+(float)$time2;
    }

    /** 
     * @brief 첫번째 인자로 오는 object var에서 2번째 object의 var들을 제거
     * @param target_obj 원 object
     * @param del_obj 원 object의 vars에서 del_obj의 vars를 제거한다
     * @return object
     **/
    function delObjectVars($target_obj, $del_obj) {
        if(!is_object($target_obj)) return;
        if(!is_object($del_obj)) return;

        $target_vars = get_object_vars($target_obj);
        $del_vars = get_object_vars($del_obj);

        $target = array_keys($target_vars);
        $del = array_keys($del_vars);
        if(!count($target)||!count($del)) return $target_obj;

        $return_obj = NULL;

        $target_count = count($target);
        for($i=0;$i<$target_count;$i++) {
            $target_key = $target[$i];
            if(!in_array($target_key, $del)) $return_obj->{$target_key} = $target_obj->{$target_key};
        }

        return $return_obj;
    }

    /** 
     * @brief php5 이상에서 error_handing을 debugPrint로 변경
     * @param errno 
     * @param errstr
     * @return file
     * @return line
     **/
    function handleError($errno, $errstr, $file, $line) {
        if(!__DEBUG__) return;
        $errors = array(E_USER_ERROR, E_ERROR, E_PARSE);
        if(!in_array($errno,$errors)) return;

        $output  = sprintf("Fatal error : %s - %d", $file, $line);
        $output .= sprintf("%d - %s", $errno, $errstr);

        debugPrint($output);
    }

    /**
     * @brief 주어진 숫자를 주어진 크기로 recursive하게 잘라줌
     * @param no 주어진 숫자
     * @param size 잘라낼 크기
     *
     * ex) 12, 3 => 012/
     * ex) 1234, 3 => 123/004/
     **/
    function getNumberingPath($no, $size=3) {
        $mod = pow(10,$size);
        $output = sprintf('%0'.$size.'d/', $no%$mod);
        if($no >= $mod) $output .= getNumberingPath((int)$no/$mod, $size);
        return $output;
    }

    /**
     * @brief 한글이 들어간 url의 decode
     **/
    function url_decode($str) {
        return preg_replace('/%u([[:alnum:]]{4})/', '&#x\\1;',$str);
    }
?>
