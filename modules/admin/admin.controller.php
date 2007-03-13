<?php
    /**
     * @class  adminController
     * @author zero (zero@nzeo.com)
     * @brief  admin 모듈의 controller class
     **/

    class adminController extends admin {
        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief admin 모듈내에서 다른 모듈을 실행하는 부분
         **/
        function procOtherModule($module, $act) {
            $oModuleHandler = new ModuleHandler($module, $act);
            $oModule = &$oModuleHandler->procModule();
            return $oModule;
        }

        /**
         * @brief 로그인 시킴
         **/
        function procLogin() {
            // 아이디, 비밀번호를 받음
            $user_id = Context::get('user_id');
            $password = Context::get('password');

            // member controller 객체 생성
            $oMemberController = &getController('member');
            return $oMemberController->procLogin($user_id, $password);
        }

        /**
         * @brief 로그아웃 시킴
         **/
        function procLogout() {
            // member controller 객체 생성
            $oMemberController = &getController('member');
            $output = $oMemberController->procLogout();
            if(!$output->toBool()) return $output;

            $this->setRedirectUrl('./?module=admin');
        }

        /**
         * @brief 숏컷 추가
         **/
        function procInsertShortCut() {
            $module = Context::get('selected_module');
            $output = $this->insertShortCut($module);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
        }

        /**
         * @brief 숏컷을 추가하는 method
         **/
        function insertShortCut($module) {
            // 선택된 모듈의 정보중에서 admin_index act를 구함
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoXml($module);

            $args->module = $module;
            $args->title = $module_info->title;
            $args->default_act = $module_info->admin_index_act;
            if(!$args->default_act) return new Object(-1, 'msg_default_act_is_null');

            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('admin.insertShortCut', $args);
            return $output;
        }


        /**
         * @brief 숏컷의 내용 수정
         **/
        function procDeleteShortCut() {

            $oDB = &DB::getInstance();

            $args->module = Context::get('selected_module');

            // 삭제 불가능 바로가기의 처리
            if(in_array($args->module, array('module','addon','plugin','layout'))) return new Object(-1, 'msg_manage_module_cannot_delete');

            $output = $oDB->executeQuery('admin.deleteShortCut', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

    }
?>
