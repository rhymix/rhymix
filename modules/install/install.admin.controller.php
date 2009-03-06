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

            $use_optimizer = Context::get('use_optimizer');
            if($use_optimizer!='Y') $use_optimizer = 'N';

            $time_zone = Context::get('time_zone');
            
            $qmail_compatibility = Context::get('qmail_compatibility');
            if($qmail_compatibility!='Y') $qmail_compatibility = 'N';

            $use_ssl = Context::get('use_ssl');
            if(!$use_ssl) $use_ssl = 'none';

            $http_port = Context::get('http_port');
            $https_port = Context::get('https_port');

            $db_info = Context::getDBInfo();
            $db_info->default_url = Context::get('default_url');
            if($db_info->default_url && !preg_match('/^(http|https):\/\//i', $db_info->default_url)) $db_info->default_url = 'http://'.$db_info->default_url;
            $db_info->time_zone = $time_zone;
            $db_info->qmail_compatibility = $qmail_compatibility;
            $db_info->use_rewrite = $use_rewrite;
            $db_info->use_optimizer = $use_optimizer;
            $db_info->lang_type = Context::get('change_lang_type');
            $db_info->use_ssl = $use_ssl;
            if($http_port) $db_info->http_port = (int) $http_port;
            else if($db_info->http_port) unset($db_info->http_port);

            if($https_port) $db_info->https_port = (int) $https_port;
            else if($db_info->https_port) unset($db_info->https_port);

            Context::setDBInfo($db_info);

            $oInstallController = &getController('install');
            $oInstallController->makeConfigFile();

            $this->setMessage('success_updated');
        }

        /**
         * @brief 지원 언어 선택
         **/
        function procInstallAdminSaveLangSelected() {
            $selected_lang = trim(Context::get('selected_lang'));
            if(!$selected_lang) return new Object(-1,'msg_invalid_request');
            $langs = explode('|@|', $selected_lang);

            $lang_supported = Context::loadLangSupported();
            $buff = null;
            for($i=0;$i<count($langs);$i++) {
                $buff .= sprintf("%s,%s\n", $langs[$i], $lang_supported[$langs[$i]]);

            }
            FileHandler::writeFile(_XE_PATH_.'files/cache/lang_selected.info', trim($buff));

            $this->setMessage('success_updated');
        }

        /**
         * @brief FTP 정보 등록
         **/
        function procInstallAdminSaveFTPInfo() {
            $ftp_info = Context::gets('ftp_user','ftp_password','ftp_port');
            $ftp_info->ftp_port = (int)$ftp_info->ftp_port;
            if(!$ftp_info->ftp_port) $ftp_info->ftp_port = 21;
            $buff = '<?php if(!defined("__ZBXE__")) exit();'."\n";
            foreach($ftp_info as $key => $val) {
                $buff .= sprintf("\$ftp_info->%s = '%s';\n", $key, str_replace("'","\\'",$val));
            }
            $buff .= "?>";
            $config_file = Context::getFTPConfigFile();
            FileHandler::WriteFile($config_file, $buff);
            $this->setMessage('success_updated');
        }
    }
?>
