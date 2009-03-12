<?php
    /**
     * @class  ldap
     * @author zero (zero@nzeo.com)
     * @brief  ldap 모듈의 high class
     **/

    class ldap extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');

            // 로그인 연동 트리거
            $oModuleController->insertTrigger('member.doLogin', 'ldap', 'controller', 'triggerLdapLogin', 'before');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            if(!$oModuleModel->getTrigger('member.doLogin', 'ldap', 'controller', 'triggerLdapLogin', 'before')) return true;
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            if(!$oModuleModel->getTrigger('member.doLogin', 'ldap', 'controller', 'triggerLdapLogin', 'before')) 
                $oModuleController->insertTrigger('member.doLogin', 'ldap', 'controller', 'triggerLdapLogin', 'before');

            return new Object(0,'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
