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
            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl');

            // 접속 사용자에 대한 체크
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();

            // 로그인 하지 않았다면 로그인 폼 출력
            if(!$oMemberModel->isLogged()) return $this->act = 'dispLogin';

            // 로그인되었는데 관리자(member->is_admin!=1)가 아니면 오류 표시
            if($logged_info->is_admin != 'Y') return $this->stop('msg_is_not_administrator');

            // 관리자용 레이아웃으로 변경
            $this->setLayoutPath($this->getTemplatePath());
            $this->setLayoutFile('layout.html');

            // admin class의 init
            parent::init();
        }

        /**
         * @brief 관리자 메인 페이지 출력
         **/
        function dispIndex() {
            // mo(module), act 변수값이 넘어오면 해당 모듈을 실행
            $mo = Context::get('mo');
            $act = Context::get('act');

            if($mo && $mo != 'admin' && $act) {
                $oAdminController = &getController('admin');
                $oModule = &$oAdminController->procOtherModule($mo, $act);
            }

            // 만약 oModule이 없으면 관리자 초기 페이지 출력
            if(!$oModule || !is_object($oModule)) {
                $this->setTemplateFile('index');

            // oModule이 정상이라면 
            } else {
                // 모듈의 타이틀 값을 구해옴
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoXml($mo);
                Context::set('selected_module_info', $module_info);

                // 해당 모듈의 template path, file을 가로챔
                $this->setTemplatePath($oModule->getTemplatePath());
                $this->setTemplateFile($oModule->getTemplateFile());
            }
        }

        /**
         * @brief 모듈의 목록을 보여줌
         **/
        function dispModuleList() {
            // 모듈 목록을 세팅
            $oAdminModel = &getModel('admin');
            $module_list = $oAdminModel->getModuleList();
            Context::set('module_list', $module_list);

            $this->setTemplateFile('module_list');
        }

        /**
         * @brief애드온의 목록을 보여줌
         **/
        function dispAddonList() {
            $oAddonView = &getView('addon');
            $oAddonView->dispAddonList();

            $this->setTemplatePath($oAddonView->getTemplatePath());
            $this->setTemplateFile($oAddonView->getTemplateFile());
        }

        /**
         * @brief 레이아웃의 목록을 보여줌
         **/
        function dispLayoutList() {
            $oLayoutView = &getView('layout');
            $oLayoutView->dispDownloadedLayoutList();

            $this->setTemplatePath($oLayoutView->getTemplatePath());
            $this->setTemplateFile($oLayoutView->getTemplateFile());
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
