<?php
    /**
     * @file   config/func.inc.php
     * @author zero (zero@nzeo.com)
     * @desc   편의 목적으로 만든 함수라이브러리 파일
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
     * @brief ModuleHandler::getModuleObject($module_name, $act_type)을 쓰기 쉽게 함수로 선언
     * @param module_name 모듈이름
     * @param act_type disp, proc, lib(기본), admin
     * @return module instance
     **/
    function getModule($module_name, $act_type = 'view') {
        return ModuleHandler::getModuleInstance($module_name, $act_type);
    }


    /**
     * @brief Context::getUrl($args_list)를 쓰기 쉽게 함수로 선언
     * @param args_list 제한없는 args
     * @return string
     *
     * getUrl()은 현재 요청된 RequestURI에 주어진 인자의 값으로 변형하여 url을 리턴한다\n
     * 인자는 (key, value)... 의 형식으로 주어져야 한다.\n
     * ex) getUrl('key1','val1', 'key2', '') : key1, key2를 val1과 '' 로 변형\n
     * 아무런 인자가 없으면 argument를 제외한 url을 리턴
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
    function debugPrint($buff, $display_line = true) {
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
        if(count(get_object_vars($target_obj))<1) return;
        if(count(get_object_vars($del_obj))<1) clone($target_obj);

        if(is_object($target_var)) $var = clone($target_var);

        foreach($del_obj as $key => $val) {
            unset($var->{$var_name});
        }

        return $var;
    }
?>
