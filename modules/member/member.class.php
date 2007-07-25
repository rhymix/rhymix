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
            $oModuleController->insertActionForward('member', 'view', 'dispMemberModifyPassword');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberLeave');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberLoginForm');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberLogout');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberOwnDocument');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberScrappedDocument');

            $oModuleController->insertActionForward('member', 'view', 'dispMemberMessages');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberSendMessage');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberNewMessage');

            $oModuleController->insertActionForward('member', 'view', 'dispMemberFriend');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAddFriend');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAddFriendGroup');

            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminList');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminConfig');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminInsert');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminDeleteForm');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminGroupList');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminJoinFormList');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminInfo');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminInsertJoinForm');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberAdminDeniedIDList');

            $oModuleController->insertActionForward('member', 'controller', 'procMemberInsertImageName');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberInsertImageMark');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberDeleteImageName');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberDeleteImageMark');

            // 기본 정보를 세팅
            $args->enable_join = 'Y';
            $args->enable_openid = 'N';
            $args->image_name = 'Y';
            $args->image_mark = 'Y';
            $args->image_name_max_width = '90';
            $args->image_name_max_height = '20';
            $args->image_mark_max_width = '20';
            $args->image_mark_max_width = '20';
            $oModuleController->insertModuleConfig('member',$args);

            // 멤버 컨트롤러 객체 생성
            $oMemberController = &getController('member');
            $oMemberAdminController = &getAdminController('member');

            // 관리자, 정회원, 준회원 그룹을 입력
            $group_args->title = Context::getLang('admin_group');
            $group_args->is_default = 'N';
            $group_args->is_admin = 'Y';
            $output = $oMemberAdminController->insertGroup($group_args);

            unset($group_args);
            $group_args->title = Context::getLang('default_group_1');
            $group_args->is_default = 'Y';
            $group_args->is_admin = 'N';
            $output = $oMemberAdminController->insertGroup($group_args);

            unset($group_args);
            $group_args->title = Context::getLang('default_group_2');
            $group_args->is_default = 'N';
            $group_args->is_admin = 'N';
            $oMemberAdminController->insertGroup($group_args);

            // 관리자 정보 세팅
            $admin_info = Context::gets('user_id','password','nick_name','user_name', 'email_address');
            if($admin_info->user_id) {
                // 관리자 정보 입력
                $oMemberAdminController->insertAdmin($admin_info);

                // 로그인 처리시킴
                $output = $oMemberController->doLogin($admin_info->user_id);
            }

            // 금지 아이디 등록 (기본 + 모듈명)
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();
            foreach($module_list as $key => $val) {
                $oMemberAdminController->insertDeniedID($val->module,'');
            }
            $oMemberAdminController->insertDeniedID('www','');
            $oMemberAdminController->insertDeniedID('root','');
            $oMemberAdminController->insertDeniedID('administrator','');
            $oMemberAdminController->insertDeniedID('telnet','');
            $oMemberAdminController->insertDeniedID('ftp','');
            $oMemberAdminController->insertDeniedID('http','');

            // member 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/member_extra_info/attach/image_name');
            FileHandler::makeDir('./files/member_extra_info/attach/image_mark');
            FileHandler::makeDir('./files/member_extra_info/attach/signature');
            FileHandler::makeDir('./files/member_extra_info/new_message_flags');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // dispMemberOwnDocument act의 여부 체크 (2007. 7. 24 추가)
            $act = $oModuleModel->getActionForward('dispMemberOwnDocument');
            if(!$act) return true;

            // dispMemberScrappedDocument act의 여부 체크 (2007. 7. 25 추가)
            $act = $oModuleModel->getActionForward('dispMemberScrappedDocument');
            if(!$act) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberOwnDocument');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberScrappedDocument');

            return new Object(0, 'success_updated');
        }
    }
?>
