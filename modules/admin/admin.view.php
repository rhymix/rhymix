<?php
  /**
   * @file   : modules/admin/admin.module.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 기본 모듈중의 하나인 admin module
   *           Module class에서 상속을 받아서 사용
   *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
   *           미리 기록을 하여야 함
   **/

  class admin extends Module {

    /** 
     * 모듈의 정보
     **/
    var $cur_version = "20070130_0.01";

    /**
     * 기본 action 지정
     * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
     **/
    var $default_act = '';

    // 모듈에서 사용할 변수들
    var $skin = "default";

    /**
     * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
     * css/js파일의 load라든지 lang파일 load등을 미리 선언
     *
     * Init() => 공통 
     * dispInit() => disp시에
     * procInit() => proc시에
     *
     * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
     * (ex: $this->module_path = "./modules/admin/";
     **/

    // 초기화
    function init() {/*{{{*/
      // admin 모듈의 언어 로드
      Context::loadLang($this->module_path.'lang');

      // 관리자 모듈 목록을 세팅
      $module_list = module_manager::getAdminModuleList();
      Context::set('module_list', $module_list);
    }/*}}}*/

    // disp 초기화
    function dispInit() {/*{{{*/
      // 접속 사용자에 대한 체크
      $oMember = getModule('member');
      $logged_info = $oMember->getLoggedInfo();

      // 로그인 하지 않았다면 로그인 폼 출력
      if(!$oMember->isLogged()) return $this->act = 'dispLogin';

      // 로그인되었는데 관리자(member->is_admin!=1)가 아니면 오류 표시
      if($logged_info->is_admin != 'Y') {
        Context::set('msg_code', 'msg_is_not_administrator');
        return $this->act = 'dispError';
      }

      // 관리자용 레이아웃으로 변경
      $this->setLayoutPath($this->getLayoutPath());
      $this->setLayoutTpl($this->getLayoutTpl());

      return true;
    }/*}}}*/
    
    // proc 초기화
    function procInit() {/*{{{*/
      // 로그인/로그아웃 act의 경우는 패스~
      if(in_array($this->act, array('procLogin', 'procLogout'))) return true;

      // 접속 사용자에 대한 체크
      $oMember = getModule('member');
      $logged_info = $oMember->getLoggedInfo();

      // 로그인되었는데 관리자(member->is_admin!=1)가 아니면 오류 표시
      if($logged_info->is_admin != 'Y') {
        $this->setError(-1);
        $this->setMessage('msg_is_not_administrator');
        return false;
      }

      return true;
    }/*}}}*/

    /**
     * 여기서부터는 action의 구현
     * request parameter의 경우 각 method의 첫번째 인자로 넘어온다
     *
     * dispXXXX : 출력을 위한 method, output에 tpl file이 지정되어야 한다
     * procXXXX : 처리를 위한 method, output에는 error, message가 지정되어야 한다
     *
     * 변수의 사용은 Context::get('이름')으로 얻어오면 된다
     **/

    // 출력부분
    function dispAdminIndex() {/*{{{*/
      $this->setTemplateFile('index');
    }/*}}}*/

    function dispLogin() {/*{{{*/
      if(Context::get('is_logged')) return $this->dispAdminIndex();
      $this->setTemplateFile('login_form');
    }/*}}}*/

    function dispLogout() {/*{{{*/
      if(!Context::get('is_logged')) return $this->dispAdminIndex();
      $this->setTemplateFile('logout');
    }/*}}}*/

    function dispError() {/*{{{*/
      Context::set('error_msg', Context::getLang( Context::get('msg_code') ) );
      $this->setTemplateFile('error');
    }/*}}}*/

    // 실행부분
    function procLogin() {/*{{{*/
      // 아이디, 비밀번호를 받음
      $user_id = Context::get('user_id');
      $password = Context::get('password');
      // member모듈 객체 생성
      $oMember = getModule('member');
      return $oMember->doLogin($user_id, $password);
    }/*}}}*/

    function procLogout() {/*{{{*/
      // member모듈 객체 생성
      $oMember = getModule('member');
      return $oMember->doLogout();
    }/*}}}*/

    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/

    function getLayoutPath() {/*{{{*/
      return $this->template_path;
    }/*}}}*/

    function getLayoutTpl() {/*{{{*/
      return "layout.html";
    }/*}}}*/
  }
?>
