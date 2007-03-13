<?php
    /**
     * @class  member 
     * @author zero (zero@nzeo.com)
     * @brief  member module의 high class
     **/

    class member extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // 멤버 컨트롤러 객체 생성
            $oMemberController = &getController('member');

            // 그룹을 입력
            $group_args->title = Context::getLang('default_group_1');
            $group_args->is_default = 'Y';
            $output = $oMemberController->insertGroup($group_args);

            $group_args->title = Context::getLang('default_group_2');
            $group_args->is_default = 'N';
            $oMemberController->insertGroup($group_args);

            // 관리자 정보 세팅
            $admin_info = Context::gets('user_id','password','nick_name','user_name', 'email_address');

            // 관리자 정보 입력
            $oMemberController->insertAdmin($admin_info);

            // 금지 아이디 등록 (기본 + 모듈명)
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();
            foreach($module_list as $key => $val) {
                $oMemberController->insertDeniedID($val->module,'');
            }
            $oMemberController->insertDeniedID('www','');
            $oMemberController->insertDeniedID('root','');
            $oMemberController->insertDeniedID('administrator','');
            $oMemberController->insertDeniedID('telnet','');
            $oMemberController->insertDeniedID('ftp','');
            $oMemberController->insertDeniedID('http','');

            // 로그인 처리시킴
            $output = $oMemberController->procLogin($admin_info->user_id, $admin_info->password);
            if(!$output) return $output;

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function moduleIsInstalled() {
            return new Object();
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }
    }
?>
