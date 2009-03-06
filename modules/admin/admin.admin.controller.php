<?php
    /**
     * @class  adminAdminController
     * @author zero (zero@nzeo.com)
     * @brief  admin 모듈의 admin controller class
     **/

    class adminAdminController extends admin {
        /**
         * @brief 초기화
         **/
        function init() {
            // 접속 사용자에 대한 체크
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();

            // 관리자가 아니면 금지
            if($logged_info->is_admin!='Y') return $this->stop("msg_is_not_administrator");
        }

        /**
         * @brief 모든 캐시 파일 재생성
         **/
        function procAdminRecompileCacheFile() {
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();

            // 개발 디버그 파일들 제거
            FileHandler::removeFile(_XE_PATH_.'files/_debug_message.php');
            FileHandler::removeFile(_XE_PATH_.'files/_debug_db_query.php');
            FileHandler::removeFile(_XE_PATH_.'files/_db_slow_query.php');

            // 각 모듈마다 돌면서 캐시 파일 제거
            foreach($module_list as $module) {
                $oModule = null;
                $oModule = &getClass($module->module);
                if(method_exists($oModule, 'recompileCache')) $oModule->recompileCache();
            }

            $this->setMessage('success_updated');
        }

        /**
         * @brief 관리자 로그아웃
         **/
        function procAdminLogout() {
            $oMemberController = &getController('member');
            $oMemberController->procMemberLogout();
        }
    }
?>
