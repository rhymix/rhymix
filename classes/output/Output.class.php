<?php
  /**
   * @file   : classes/output/Output.class.php
   * @author : zero <zero@nzeo.com>
   * @desc   : result 객체
   *           request method에 따라서 error+message+variables[]를 xml문서로
   *           또는 html 출력을
   *           또는 사용법에 따라 error로 결과를 리턴한다
   *           error != 0 이면 에러가 발생하였다는 것으로 정의
   **/

  class Output {

    // template path 지정
    var $template_path = NULL;
    var $template_file = NULL;

    // 기본 에러와 메세지
    var $error = 0;
    var $message = 'success';

    // 추가 변수
    var $variables = array();

    // public void Output($error = 0, $message = 'success')/*{{{*/
    // error 코드를 지정
    function Output($error = 0, $message = 'success') {
      $this->error = $error;
      $this->message = $message;
    }/*}}}*/

    // public void setError($error)/*{{{*/
    // error 코드를 지정
    function setError($error = 0) {
      $this->error = $error;
    }/*}}}*/

    // public string getError()/*{{{*/
    function getError() {
      return $this->error;
    }/*}}}*/

    // public void setMessage($message)/*{{{*/
    // 메세지 지정
    function setMessage($message = 'success') {
      if(Context::getLang($message)) $message = Context::getLang($message);
      $this->message = $message;
      return true;
    }/*}}}*/

    // public string getMessage()/*{{{*/
    // 메세지 지정
    function getMessage() {
      return $this->message;
    }/*}}}*/

    // public void add($key, $val)/*{{{*/
    // xml문서를 작성시 필요한 key, val 추가
    function add($key, $val) {
      $this->variables[$key] = $val;
    }/*}}}*/

    // public string get($key)/*{{{*/
    // 추가된 변수의 key에 해당하는 값을 return
    function get($key) {
      return $this->variables[$key];
    }/*}}}*/

    // public array getVariables()/*{{{*/
    // 설정된 variables를 return
    function getVariables() {
      return $this->variables;
    }/*}}}*/

    // public boolean toBool()/*{{{*/
    // error값이 0이 아니면 오류
    function toBool() {
      return $this->error==0?true:false;
    }/*}}}*/

    // public boolean toBoolean()/*{{{*/
    // error값이 0이 아니면 오류
    function toBoolean() {
      return $this->toBool();
    }/*}}}*/

    // public void setTemplatePath($path)/*{{{*/
    // 현재 모듈의 tpl 파일을 저장
    function setTemplatePath($path) {
      if(!substr($path,-1)!='/') $path .= '/';
      $this->template_path = $path;
    }/*}}}*/

    // public string getTemplatePath()/*{{{*/
    // 설정된 template path를 return
    function getTemplatePath() {
      return $this->template_path;
    }/*}}}*/

    // public void setTemplateFile($filename)/*{{{*/
    function setTemplateFile($filename) {
      $this->template_file = $filename;
    }/*}}}*/

    // public string getTemplateFile()/*{{{*/
    function getTemplateFile() {
      return $this->template_file;
    }/*}}}*/
  }
?>
