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
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberInfo');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberSignUpForm');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberModifyInfo');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberLoginForm');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberLogout');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminList');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminConfig');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminInsert');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminDeleteForm');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminGroupList');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminJoinFormList');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminInfo');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminInsertJoinForm');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminDeniedIDList');
            $oModuleController->insertActionForward('member', 'model', 'getmemberMenu');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberLogin');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberLogout');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberInsert');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberInsertImageName');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberInsertImageMark');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberDeleteImageName');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberDeleteImageMark');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberAdminInsert');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberAdminDelete');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberAdminInsertConfig');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberAdminInsertGroup');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberAdminUpdateGroup');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberAdminInsertJoinForm');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberAdminUpdateJoinForm');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberAdminInsertDeniedID');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberAdminUpdateDeniedID');

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
            $output = $oMemberController->procMemberLogin($admin_info->user_id, $admin_info->password);
            if(!$output) return $output;

            // member 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/attach/image_name');
            FileHandler::makeDir('./files/attach/image_mark');

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
