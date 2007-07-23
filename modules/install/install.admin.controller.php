<?php
    /**
     * @class  installAdminController
     * @author zero (zero@nzeo.com)
     * @brief  install module의 admin controller class
     **/

    class installAdminController extends install {


        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 모듈 설치
         **/
        function procInstallAdminInstall() {
            $module_name = Context::get('module_name');
            if(!$module_name) return new object(-1, 'invalid_request');

            $oInstallController = &getController('install');
            $oInstallController->installModule($module_name, './modules/'.$module_name);

            $this->setMessage('success_installed');
        }

        /**
         * @brief 모듈 업데이트
         **/
        function procInstallAdminUpdate() {
            $module_name = Context::get('module_name');
            if(!$module_name) return new object(-1, 'invalid_request');

            $oModule = &getModule($module_name, 'class');
            if($oModule) $output = $oModule->moduleUpdate();
            else $output = new Object(-1, 'invalid_request');

            return $output;
        }

        /**
         * @brief time zone변경
         **/
        function procInstallAdminSaveTimeZone() {
            $use_rewrite = Context::get('use_rewrite');
            if($use_rewrite!='Y') $use_rewrite = 'N';
            $time_zone = Context::get('time_zone');

            $db_info = Context::getDBInfo();
            $db_info->time_zone = $time_zone;
            $db_info->use_rewrite = $use_rewrite;
            Context::setDBInfo($db_info);

            $oInstallController = &getController('install');
            $oInstallController->makeConfigFile();

            $this->setMessage('success_updated');
        }
    }
?>
