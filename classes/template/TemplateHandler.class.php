<?php
  /**
   * @file   : classes/template/TemplateHandler.class.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 템플릿 컴파일
   **/

  class TemplateHandler {

    var $compiled_path = './files/template_compiled/';

    var $tpl_path = '';
    var $tpl_file = '';

    // public string compile($tpl_path, $tpl_filename)/*{{{*/
    function compile($tpl_path, $tpl_filename) {
      $this->tpl_path = $tpl_path;

      // 디버그를 위한 컴파일 시작 시간 저장
      if(__DEBUG__) $start = getMicroTime();

      // 변수 체크
      if(substr($tpl_path,-1)!='/') $tpl_path .= '/';
      if(substr($tpl_filename,-5)!='.html') $tpl_filename .= '.html';

      // tpl_file 변수 생성
      $tpl_file = $tpl_path.$tpl_filename;

      // tpl_file이 비어 있거나 해당 파일이 없으면 return
      if(!$tpl_file || !file_exists($tpl_file)) return;

      $this->tpl_file = $tpl_file;

      // compiled된(or 될) 파일이름을 구함
      $compiled_tpl_file = $this->_getCompiledFileName($tpl_file);

      // 일단 컴파일
      $buff = $this->_compile($tpl_file, $compiled_tpl_file);

      if(__DEBUG__) {
        $template_elapsed = getMicroTime() - $start;
        $GLOBALS['__template_elapsed__'] += $template_elapsed;
      }

      // Context와 compiled_tpl_file로 컨텐츠 생성
      $output = $this->_fetch($compiled_tpl_file, $buff);

      // 컴파일된 파일을 실행
      return $output; 
    }/*}}}*/

    // private void _compile($tpl_file, $compiled_tpl_file)/*{{{*/
    // tpl_file이 컴파일이 되어 있는 것이 있는지 체크
    function _compile($tpl_file, $compiled_tpl_file) {
      if(!file_exists($compiled_tpl_file)) return $this->_compileTplFile($tpl_file, $compiled_tpl_file);

      $source_ftime = filectime($tpl_file);
      $target_ftime = filectime($compiled_tpl_file);
      if($source_ftime>$target_ftime) return $this->_compileTplFile($tpl_file, $compiled_tpl_file);
    }/*}}}*/

    // private void _compileTplFile($tpl_file, $compiled_tpl_file)/*{{{*/
    // tpl_file을 compile
    function _compileTplFile($tpl_file, $compiled_tpl_file) {

      // tpl 파일을 읽음
      $buff = FileHandler::readFile($tpl_file);
      if(!$buff) return;

      // 변수를 변경
      $buff = preg_replace_callback('/\{[^@^ ]([^\}]+)\}/i', array($this, '_compileVarToContext'), $buff);

      // 이미지 태그 img의 src의 값이 ./ 로 시작하면 {$tpl_path}로 변경
      $buff = preg_replace_callback('!src=[\'"]{1}(.*?)[\'"]{1}!is', array($this, '_compileImgPath'), $buff);

      // 함수를 변경
      $buff = preg_replace_callback('/\{\@([^\}]+)\}/i', array($this, '_compileVarToFunc'), $buff);

      // <!--@, --> 의 변경
      $buff = preg_replace_callback('!<\!--@(.*?)-->!is', array($this, '_compileFuncToCode'), $buff);

      // include 변경 <!--#include($path, $filename)-->
      $buff = preg_replace_callback('!<\!--#include\(([^\)]*?)\)-->!is', array($this, '_compileIncludeToCode'), $buff);

      // import xml filter/ css/ js <!--%filename-->
      $buff = preg_replace_callback('!<\!--%import\(\"([^\"]*?)\"\)-->!is', array($this, '_compileImportCode'), $buff);

      // 파일에 쓰기 전에 직접 호출되는 것을 방지
      $buff = sprintf('%s%s%s','<?php if(!__ZB5__) exit();?>',"\n",$buff);

      // 컴파일된 코드를 파일에 저장
      FileHandler::writeFile($compiled_tpl_file, $buff);

      return $buff;
    }/*}}}*/

    // private string _compileVarToContext($matches)/*{{{*/
    // {$와 } 안의 $... 변수를 Context::get(...) 으로 변경
    function _compileVarToContext($matches) {
      $str = trim(substr($matches[0],1,strlen($matches[0])-2));
      return '<?php print('.preg_replace('/\$([a-zA-Z0-9\_\-\>]+)/i','$__Context->\\1', $str).');?>';
    }/*}}}*/

    // private string _compileImgPath($matches)/*{{{*/
    // {$와 } 안의 $... 변수를 Context::get(...) 으로 변경
    function _compileImgPath($matches) {
      $str1 = $matches[0];
      $str2 = $matches[1];
      $path = $str2;
      if(!eregi("^\.\/(images|img)",$path)) return $str1;
      
      $path = '<?=$this->tpl_path?>'.substr($path,2);
      return str_replace($str2, $path, $str1);
    }/*}}}*/

    // private string _compileVarToFunc($matches)/*{{{*/
    // {@와 } 안의 @... 함수를 print func(..)로 변경
    function _compileVarToFunc($matches) {
      return '<?php print('.preg_replace('/\$([a-zA-Z0-9\_\-\>]+)/i','$__Context->\\1', trim($matches[1])).');?>';
    }/*}}}*/

    // private string _compileFuncToCode($matches)/*{{{*/
    // <!--@, --> 사이의 구문을 php코드로 변경
    function _compileFuncToCode($matches) {
      $code = trim($matches[1]);
      if(!$code) return;
      switch(strtolower($code)) {
        case 'else' :
            $output = '}else{';
          break;
        case 'end' :
        case 'endif' :
        case 'endfor' :
        case 'endforeach' :
            $output = '}';
          break;
        default :
            if(substr($code,0,4)=='else') {
              $code = '}'.$code;
            } elseif(substr($code,0,7)=='foreach') {
              $tmp_str = substr($code,8);
              $tmp_arr = explode(' ', $tmp_str);
              $var_name = $tmp_arr[0];
              if(substr($var_name,0,1)=='$') $prefix = sprintf('if(is_array($__Context->%s)) ', substr($var_name,1));
              else $prefix = sprintf('if(is_array(%s)) ', $var_name);
            } 
            $output = preg_replace('/\$([a-zA-Z0-9\_\-]+)/i','$__Context->\\1', $code).'{';
          break;
      }

      return sprintf('<?php %s %s ?>', $prefix, $output);
    }/*}}}*/

    // private string _compileIncludeToCode($matches)/*{{{*/
    // <!--#include $path-->를 변환
    function _compileIncludeToCode($matches) {
      // include하려는 대상문자열에 변수가 있으면 변수 처리
      $arg = str_replace(array('"','\''), '', $matches[1]);
      if(!$arg) return;

      $tmp_arr = explode("/", $arg);
      for($i=0;$i<count($tmp_arr);$i++) {
        $item1 = trim($tmp_arr[$i]);
        if($item1=='.'||eregi("\.html$",$item1)) continue;

        $tmp2_arr = explode(".",$item1);
        for($j=0;$j<count($tmp2_arr);$j++) {
          $item = trim($tmp2_arr[$j]);
          if(substr($item,0,1)=='$') $item = Context::get(substr($item,1));
          $tmp2_arr[$j] = $item;
        }
        $tmp_arr[$i] = implode(".",$tmp2_arr);
      }
      $arg = implode("/",$tmp_arr);
      if(substr($arg,0,2)=='./') $arg = substr($arg,2);

      // 1단계로 해당 tpl 내의 파일을 체크
      $filename = sprintf("%s/%s", dirname($this->tpl_file), $arg);

      // 2단계로 root로부터 경로를 체크
      if(!file_exists($filename)) $filename = './'.$arg;
      if(!file_exists($filename)) return;

      // path, filename으로 분리
      $tmp_arr = explode('/', $filename);
      $filename = array_pop($tmp_arr);
      $path = implode('/', $tmp_arr).'/';

      // include 시도
      $output = sprintf(
          '<?php%s'.
          '$oTemplate = new TemplateHandler();%s'.
          'print $oTemplate->compile(\'%s\',\'%s\');%s'.
          '?>%s',
          "\n",
          "\n",
          $path,
          $filename,
          "\n",
          "\n"
      );
      return $output;
    }/*}}}*/

    // private string _compileImportCode($matches)/*{{{*/
    // <!--%filename-->의 확장자를 봐서 js filter/ css/ js 파일을 include하도록 수정
    function _compileImportCode($matches) {
      // 현재 tpl 파일의 위치를 구해서 $base_path에 저장하여 적용하려는 xml file을 찾음
      $base_path = dirname($this->tpl_file).'/';
      $given_file = trim($matches[1]);
      if(!$given_file) return;
      $filename = sprintf("%s%s",$base_path, $given_file);

      // path와 파일이름을 구함
      $tmp_arr = explode("/",$filename);
      $filename = array_pop($tmp_arr);

      $base_path = implode("/",$tmp_arr)."/";

      // 확장자를 구함
      $tmp_arr = explode(".",$filename);
      $ext = strtolower(array_pop($tmp_arr));

      // 확장자에 따라서 파일 import를 별도로
      switch($ext) {
        // xml js filter
        case 'xml' :
            // XmlJSFilter 클래스의 객체 생성후 js파일을 만들고 Context::addJsFile처리
            $output = sprintf(
              '<?php%s'.
              'require_once("./classes/xml/XmlJsFilter.class.php");%s'.
              '$oXmlFilter = new XmlJSFilter("%s","%s");%s'.
              '$oXmlFilter->compile();%s'.
              '?>%s',
              "\n",
              "\n",
              $base_path,
              $filename,
              "\n",
              "\n",
              "\n"
            );
          break;
        // css file
        case 'css' :
            $output = sprintf('<?php Context::addCSSFile("%s%s"); ?>', $base_path, $filename);
          break;
        // js file
        case 'js' :
            $output = sprintf('<?php Context::addJsFile("%s%s"); ?>', $base_path, $filename);
          break;
      }

      return $output;
    }/*}}}*/

    // private string _getComliedFileName($tpl_file) /*{{{*/
    // $tpl_file로 compiled_tpl_file이름을 return
    function _getCompiledFileName($tpl_file) {
      return sprintf('%s%s.compiled.php',$this->compiled_path, md5($tpl_file));
    }/*}}}*/

    // private string _fetch($compiled_tpl_file)/*{{{*/
    // ob_* 함수를 이용하여 fetch...
    function _fetch($compiled_tpl_file, $buff = NULL) {
      $__Context = &$GLOBALS['__Context__'];

      if($_SESSION['is_logged']) $__Context->logged_info = $_SESSION['logged_info'];

      // ob_start를 시킨후 컴파일된 tpl파일을 include하고 결과를 return
      ob_start();
      // tpl파일을 compile하지 못할 경우 $buff로 넘어온 값을 eval시킴 (미설치시에나..)
      if($buff) {
        $eval_str = "?>".$buff;
        eval($eval_str);
      } else {
        @include $compiled_tpl_file;
      }
      $output = ob_get_contents();
      ob_end_clean();

      return $output;
    }/*}}}*/
  
  }
?>
