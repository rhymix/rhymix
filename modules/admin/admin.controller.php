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
    }
?>
