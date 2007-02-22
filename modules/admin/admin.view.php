<?php
    /**
     * @class  adminView
     * @author zero (zero@nzeo.com)
     * @brief  admin 모듈의 view class
     **/

    class adminView extends admin {

        /**
         * @brief 초기화
         **/
        function init() {
            // admin class의 init
            parent::init();

            // 접속 사용자에 대한 체크
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();

            // 로그인 하지 않았다면 로그인 폼 출력
            if(!$oMemberModel->isLogged()) return $this->act = 'dispLogin';

            // 로그인되었는데 관리자(member->is_admin!=1)가 아니면 오류 표시
            if($logged_info->is_admin != 'Y') return $this->stop('msg_is_not_administrator');
        }

        /**
         * @brief 관리자 메인 페이지 출력
         **/
        function dispIndex() {
            $this->setTemplateFile('index');
        }

        /**
         * @brief 모듈의 목록을 보여줌
         **/
        function dispModuleList() {
            // 관리자 모듈 목록을 세팅
            $oAdminModel = &getModel('admin');
            $module_list = $oAdminModel->getModuleList();
            Context::set('module_list', $module_list);

            $this->setTemplateFile('module_list');
        }

        /**
         * @brief 관리자 로그인 페이지 출력
         **/
        function dispLogin() {
            if(Context::get('is_logged')) return $this->dispIndex();
            $this->setTemplateFile('login_form');
        }

        /**
         * @brief 관리자 로그아웃 페이지 출력
         **/
        function dispLogout() {
            if(!Context::get('is_logged')) return $this->dispIndex();
            $this->setTemplateFile('logout');
        }
    }
?>
