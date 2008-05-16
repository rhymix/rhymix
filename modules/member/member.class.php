<?php
    /**
     * @class  member 
     * @author zero (zero@nzeo.com)
     * @brief  member module의 high class
     **/

    class member extends ModuleObject {

        /**
         * @brief constructor
         **/
        function member() {
            if(!Context::isInstalled()) return;

            $oModuleModel = &getModel('module');
            $member_config = $oModuleModel->getModuleConfig('member');

            // SSL 사용시 회원가입/정보/비밀번호등과 관련된 action에 대해 SSL 전송하도록 지정
            if($member_config->enable_ssl == 'Y') {
                Context::addSSLAction('dispMemberLoginForm');
                Context::addSSLAction('dispMemberModifyPassword');
                Context::addSSLAction('dispMemberSignUpForm');
                Context::addSSLAction('dispMemberModifyInfo');
                Context::addSSLAction('dispMemberOpenIDLogin');
                Context::addSSLAction('procMemberLogin');
                Context::addSSLAction('procMemberModifyPassword');
                Context::addSSLAction('procMemberInsert');
                Context::addSSLAction('procMemberModifyInfo');
                Context::addSSLAction('procMemberOpenIDLogin');
            }
        }

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
            $oModuleController->insertActionForward('member', 'view', 'dispMemberOpenIDLeave');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberLoginForm');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberLogout');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberOwnDocument');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberScrappedDocument');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberSavedDocument');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberFindAccount');

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

            $oModuleController->insertActionForward('member', 'controller', 'procMemberInsertProfileImage');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberInsertImageName');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberInsertImageMark');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberDeleteProfileImage');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberDeleteImageName');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberDeleteImageMark');

            $oModuleModel = &getModel('module');
            $args = $oModuleModel->getModuleConfig('member');

            // 기본 정보를 세팅
            $args->enable_join = 'Y';
            if(!$args->enable_openid) $args->enable_openid = 'N';
            if(!$args->enable_auth_mail) $args->enable_auth_mail = 'N';
            if(!$args->image_name) $args->image_name = 'Y';
            if(!$args->image_mark) $args->image_mark = 'Y';
            if(!$args->profile_image) $args->profile_image = 'Y';
            if(!$args->image_name_max_width) $args->image_name_max_width = '90';
            if(!$args->image_name_max_height) $args->image_name_max_height = '20';
            if(!$args->image_mark_max_width) $args->image_mark_max_width = '20';
            if(!$args->image_mark_max_height) $args->image_mark_max_height = '20';
            if(!$args->profile_image_max_width) $args->profile_image_max_width = '80';
            if(!$args->profile_image_max_height) $args->profile_image_max_height = '80';
            $oModuleController->insertModuleConfig('member',$args);

            // 멤버 컨트롤러 객체 생성
            $oMemberModel = &getModel('member');
            $oMemberController = &getController('member');
            $oMemberAdminController = &getAdminController('member');

            $groups = $oMemberModel->getGroups();
            if(!count($groups)) {
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
            }

            // 관리자 정보 세팅
            $admin_args->is_admin = 'Y';
            $output = executeQuery('member.getMemberList', $admin_args);
            if(!$output->data) {
                $admin_info = Context::gets('user_id','password','nick_name','user_name', 'email_address');
                if($admin_info->user_id) {
                    // 관리자 정보 입력
                    $oMemberAdminController->insertAdmin($admin_info);

                    // 로그인 처리시킴
                    $output = $oMemberController->doLogin($admin_info->user_id);
                }
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
            FileHandler::makeDir('./files/member_extra_info/image_name');
            FileHandler::makeDir('./files/member_extra_info/image_mark');
            FileHandler::makeDir('./files/member_extra_info/profile_image');
            FileHandler::makeDir('./files/member_extra_info/signature');
            FileHandler::makeDir('./files/member_extra_info/new_message_flags');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');

            // dispMemberOwnDocument act의 여부 체크 (2007. 7. 24 추가)
            $act = $oModuleModel->getActionForward('dispMemberOwnDocument');
            if(!$act) return true;

            // dispMemberScrappedDocument act의 여부 체크 (2007. 7. 25 추가)
            $act = $oModuleModel->getActionForward('dispMemberScrappedDocument');
            if(!$act) return true;

            // dispMemberOpenIDLeave act의 여부 체크 (2007. 9. 19 추가)
            $act = $oModuleModel->getActionForward('dispMemberOpenIDLeave');
            if(!$act) return true;

            // member 디렉토리 체크 (2007. 8. 11 추가)
            if(!is_dir("./files/member_extra_info")) return true;

            // dispMemberFindAccount act의 여부 체크 (2007. 10. 15)
            $act = $oModuleModel->getActionForward('dispMemberFindAccount');
            if(!$act) return true;

            // member 디렉토리 체크 (2007. 10. 22 추가)
            if(!is_dir("./files/member_extra_info/profile_image")) return true;

            // procMemberInsertProfileImage, procMemberDeleteProfileImage act의 여부 체크 (2007. 10. 22)
            $act = $oModuleModel->getActionForward('procMemberInsertProfileImage');
            if(!$act) return true;
            $act = $oModuleModel->getActionForward('procMemberDeleteProfileImage');
            if(!$act) return true;

            // dispMemberSavedDocument act의 여부 체크 (2007. 10. 29)
            $act = $oModuleModel->getActionForward('dispMemberSavedDocument');
            if(!$act) return true;

            // member_auth_mail 테이블에 is_register 필드 추가 (2008. 04. 22)
            $act = $oDB->isColumnExists("member_auth_mail", "is_register");
            if(!$act) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();
            $oModuleController = &getController('module');

            // act 추가
            $oModuleController->insertActionForward('member', 'view', 'dispMemberOwnDocument');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberScrappedDocument');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberSavedDocument');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberOpenIDLeave');
            $oModuleController->insertActionForward('member', 'view', 'dispMemberFindAccount');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberInsertProfileImage');
            $oModuleController->insertActionForward('member', 'controller', 'procMemberDeleteProfileImage');

            // member 디렉토리 체크
            FileHandler::makeDir('./files/member_extra_info/image_name');
            FileHandler::makeDir('./files/member_extra_info/image_mark');
            FileHandler::makeDir('./files/member_extra_info/signature');
            FileHandler::makeDir('./files/member_extra_info/new_message_flags');
            FileHandler::makeDir('./files/member_extra_info/profile_image');

            // DB 필드 추가
            if (!$oDB->isColumnExists("member_auth_mail", "is_register")) {
                $oDB->addColumn("member_auth_mail", "is_register", "char", 1, "N", true);
            }

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
