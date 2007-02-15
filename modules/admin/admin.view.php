<?php
    /**
     * @class  adminView
     * @author zero (zero@nzeo.com)
     * @brief  admin 모듈의 view class
     **/

    class adminView extends Module {

        /**
         * @brief 초기화
         **/
        function init() {
            // 관리자 모듈 목록을 세팅
            $oModuleModel = getModel('module');
            $module_list = $oModuleModel->getAdminModuleList();
            Context::set('module_list', $module_list);

            // 접속 사용자에 대한 체크
            $oMemberModel = getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();

            // 로그인 하지 않았다면 로그인 폼 출력
            if(!$oMemberModel->isLogged()) return $this->act = 'dispLogin';

            // 로그인되었는데 관리자(member->is_admin!=1)가 아니면 오류 표시
            if($logged_info->is_admin != 'Y') {
            Context::set('msg_code', 'msg_is_not_administrator');
            return $this->act = 'dispError';
            }

            // 관리자용 레이아웃으로 변경
            $this->setLayoutPath($this->getLayoutPath());
            $this->setLayoutTpl($this->getLayoutTpl());

            return true;
        }

        // proc 초기화
        function procInit() {
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
        }

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
        function dispAdminIndex() {
        $this->setTemplateFile('index');
        }

        function dispLogin() {
        if(Context::get('is_logged')) return $this->dispAdminIndex();
        $this->setTemplateFile('login_form');
        }

        function dispLogout() {
        if(!Context::get('is_logged')) return $this->dispAdminIndex();
        $this->setTemplateFile('logout');
        }

        function dispError() {
        Context::set('error_msg', Context::getLang( Context::get('msg_code') ) );
        $this->setTemplateFile('error');
        }

        // 실행부분
        function procLogin() {
        // 아이디, 비밀번호를 받음
        $user_id = Context::get('user_id');
        $password = Context::get('password');
        // member모듈 객체 생성
        $oMember = getModule('member');
        return $oMember->doLogin($user_id, $password);
        }

        function procLogout() {
        // member모듈 객체 생성
        $oMember = getModule('member');
        return $oMember->doLogout();
        }

        /**
         * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
         **/

        function getLayoutPath() {
        return $this->template_path;
        }

        function getLayoutTpl() {
        return "layout.html";
        }
    }
?>
