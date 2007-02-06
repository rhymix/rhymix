<?php
  /**
   * @file   : modules/message/message.module.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 기본 모듈중의 하나인 message module
   *           Module class에서 상속을 받아서 사용
   *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
   *           미리 기록을 하여야 함
   **/

  class message extends Module {

    /**
     * 모듈의 정보
     **/
    var $cur_version = "20070130_0.01";

    /**
     * 기본 action 지정
     * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
     **/
    var $default_act = 'dispSystemMessage';

    /**
     * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
     * css/js파일의 load라든지 lang파일 load등을 미리 선언
     *
     * Init() => 공통 
     * dispInit() => disp시에
     * procInit() => proc시에
     *
     * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
     * (ex: $this->module_path = "./modules/install/";
     **/

    // 초기화
    function init() {/*{{{*/
    }/*}}}*/

    // disp 초기화
    function dispInit() {/*{{{*/
      Context::addCssFile($this->template_path.'css/message.css');
    }/*}}}*/
    
    // proc 초기화
    function procInit() {/*{{{*/
    }/*}}}*/

    /**
     * 여기서부터는 action의 구현
     * request parameter의 경우 각 method의 첫번째 인자로 넘어온다
     *
     * dispXXXX : 출력을 위한 method, output에 tpl file이 지정되어야 한다
     * procXXXX : 처리를 위한 method, output에는 message, message가 지정되어야 한다
     **/
    // function void dispSystemMessage()/*{{{*/
    // 메세지 출력 Context의 erro/ message를 출력
    function dispSystemMessage() {
      $this->setTemplateFile('system_message');
    }/*}}}*/

    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/
  }
?>
