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
            if(!$this->grant->is_admin) return;

            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl');

            // 접속 사용자에 대한 체크
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();

            // 관리자용 레이아웃으로 변경
            $this->setLayoutPath($this->getTemplatePath());
            $this->setLayoutFile('layout.html');

            // shortcut 가져오기
            $oAdminModel = &getModel('admin');
            $shortcut_list = $oAdminModel->getShortCuts();
            Context::set('shortcut_list', $shortcut_list);

            // 현재 실행중인 모듈을 구해 놓음
            $running_module = strtolower(preg_replace('/([a-z]+)([A-Z]+)([a-z]+)(.*)/', '\\2\\3', $this->act));
            Context::set('running_module', $running_module);
        }

        /**
         * @brief 관리자 메인 페이지 출력
         **/
        function dispAdminIndex() {
            $this->setTemplateFile('index');
        }

        /**
         * @brief 관리자 메뉴 숏컷 출력
         **/
        function dispAdminShortCut() {
            $this->setTemplateFile('shortcut_list');
        }
    }
?>
