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
    }
?>
