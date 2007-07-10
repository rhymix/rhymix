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
         * @brief time zone변경
         **/
        function procInstallAdminSaveTimeZone() {
            $time_zone = Context::get('time_zone');

            $db_info = Context::getDBInfo();
            $db_info->time_zone = $time_zone;
            Context::setDBInfo($db_info);

            $oInstallController = &getController('install');
            $oInstallController->makeConfigFile();

            $this->setMessage('success_updated');
        }
    }
?>
