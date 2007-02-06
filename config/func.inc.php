<?php
  /**
   * @file   : config/func.inc.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 편의 목적으로 만든 함수라이브러리 파일
   **/

  // php5에 대비하여 clone 정의/*{{{*/
  if (version_compare(phpversion(), '5.0') < 0) {
    eval('
    function clone($object) {
      return $object;
    }
    ');
  }/*}}}*/

  // function object getModule($module_name, $is_admin=false)/*{{{*/
  // module_manager::getModuleObject($module_name)을 쓰기 쉽게 함수로 선언
  function getModule($module_name, $is_admin=false) {
    if($is_admin) return module_manager::getAdminModuleObject($module_name);
    return module_manager::getModuleObject($module_name);
  }/*}}}*/

  // function string getUrl($args_list)/*{{{*/
  // Context::getUrl($args_list)를 쓰기 쉽게 함수로 선언
  function getUrl() {
    $num_args = func_num_args();
    $args_list = func_get_args();

    if(!$num_args) return Context::getRequestUri();

    return Context::getUrl($num_args, $args_list);
  }/*}}}*/

  // function string cut_str($string, $cut_size, $tail = '...')/*{{{*/
  // microtime
  function cut_str($string, $cut_size, $tail='...') {
    if(!$string || !$cut_size) return $string;
    $unicode_str = iconv("UTF-8","UCS-2",$string);
    if(strlen($unicode_str) < $cut_size*2) return $string;

    $output = substr($unicode_str, 0, $cut_size*2);
    return iconv("UCS-2","UTF-8",$output_str).$tail;
  }/*}}}*/

  // function string zdate($str, $format = "Y-m-d H:i:s")/*{{{*/
  // 시간 출력
  function zdate($str, $format = "Y-m-d H:i:s") {
    return date($format, mktime(substr($str,8,2), substr($str,10,2), substr($str,12,2), substr($str,4,2), substr($str,6,2), substr($str,0,4)));
  }/*}}}*/

  // function void debugPrint($buff)/*{{{*/
  // 간단한 console debugging용 함수
  function debugPrint($buff, $display_line = true) {
    $debug_file = "./files/_debug_message.php";
    $buff = sprintf("%s\n",print_r($buff,true));

    if($display_line) $buff = "\n====================================\n".$buff."------------------------------------\n";

    if(@!$fp = fopen($debug_file,"a")) return;
    fwrite($fp, $buff);
    fclose($fp);
  }/*}}}*/

  // function float getMicroTime()/*{{{*/
  // microtime
  function getMicroTime() {
    list($time1, $time2) = explode(' ', microtime());
    return (float)$time1+(float)$time2;
  }/*}}}*/

  // function string delObjectVars($target_obj, $del_obj)/*{{{*/
  // 첫번째 인자로 오는 object var에서 2번째 object의 var들을 빼낸다
  function delObjectVars($target_obj, $del_obj) {
    if(count(get_object_vars($target_obj))<1) return;
    if(count(get_object_vars($del_obj))<1) clone($target_obj);

    if(is_object($target_var)) $var = clone($target_var);
    foreach($del_obj as $key => $val) {
      unset($var->{$var_name});
    }
    return $var;
  }/*}}}*/
?>
