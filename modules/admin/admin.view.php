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
            // 관리자 모듈 목록을 세팅
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getAdminModuleList();
            Context::set('module_list', $module_list);

            // 접속 사용자에 대한 체크
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();

            // 로그인 하지 않았다면 로그인 폼 출력
            if(!$oMemberModel->isLogged()) return $this->act = 'dispLogin';

            // 로그인되었는데 관리자(member->is_admin!=1)가 아니면 오류 표시
            if($logged_info->is_admin != 'Y') {
                Context::set('msg_code', 'msg_is_not_administrator');
                return $this->act = 'dispError';
            }

            // 관리자용 레이아웃으로 변경
            //$this->setLayoutPath($this->getLayoutPath());
            //$this->setLayoutTpl($this->getLayoutTpl());

            // 로그인/로그아웃 act의 경우는 패스~
            if(in_array($this->act, array('procLogin', 'procLogout'))) return true;

            // 접속 사용자에 대한 체크
            $logged_info = $oMemberModel->getLoggedInfo();

            // 로그인되었는데 관리자(member->is_admin!=1)가 아니면 오류 표시
            if($logged_info->is_admin != 'Y') {
                $this->setError(-1);
                $this->setMessage('msg_is_not_administrator');
                return false;
            }

            return true;
        }

        /**
         * @brief 관리자 메인 페이지 출력
         **/
        function dispAdminIndex() {
            $this->setTemplateFile('index');
        }

        /**
         * @brief 관리자 로그인 페이지 출력
         **/
        function dispLogin() {
            if(Context::get('is_logged')) return $this->dispAdminIndex();
            $this->setTemplateFile('login_form');
        }

        /**
         * @brief 관리자 로그아웃 페이지 출력
         **/
        function dispLogout() {
            if(!Context::get('is_logged')) return $this->dispAdminIndex();
            $this->setTemplateFile('logout');
        }

        /**
         * @brief 에러 출력
         **/
        function dispError() {
            Context::set('error_msg', Context::getLang( Context::get('msg_code') ) );
            $this->setTemplateFile('error');
        }

    }
?>
