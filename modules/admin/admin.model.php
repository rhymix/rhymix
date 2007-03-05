<?php
    /**
     * @class  adminModel
     * @author zero (zero@nzeo.com)
     * @brief  admin 모듈의 model class
     **/

    class adminModel extends admin {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 모듈의 종류와 정보를 구함
         **/
        function getModuleList() {
            //  module model 객체 생성
            $oModuleModel = &getModel('module');

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 다운받은 모듈과 설치된 모듈의 목록을 구함
            $downloaded_list = FileHandler::readDir('./files/modules');
            $installed_list = FileHandler::readDir('./modules');
            $searched_list = array_merge($downloaded_list, $installed_list);
            $searched_count = count($searched_list);
            if(!$searched_count) return;

            for($i=0;$i<$searched_count;$i++) {
                // 모듈의 이름
                $module_name = $searched_list[$i];

                // 모듈의 경로 (files/modules가 우선)
                $path = ModuleHandler::getModulePath($module_name);

                // schemas내의 테이블 생성 xml파일수를 구함
                $tmp_files = FileHandler::readDir($path."schemas");
                $table_count = count($tmp_files);

                // 테이블이 설치되어 있는지 체크
                $created_table_count = 0;
                for($j=0;$j<count($tmp_files);$j++) {
                    list($table_name) = explode(".",$tmp_files[$j]);
                    if($oDB->isTableExists($table_name)) $created_table_count ++;
                }

                // 해당 모듈의 정보를 구함
                $info = $oModuleModel->getModuleInfoXml($module_name);
                unset($obj);

                $info->module = $module_name;
                $info->created_table_count = $created_table_count;
                $info->table_count = $table_count;
                $info->path = $path;
                $info->admin_index_act = $info->admin_index_act;

                $list[] = $info;
            }
            return $list;
        }

        /**
         * @brief 애드온의 종류와 정보를 구함
         **/
        function getAddonList() {
            //  addon model 객체 생성
            $oAddonModel = &getModel('addon');

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 다운받은 애드온과 설치된 애드온의 목록을 구함
            $downloaded_list = FileHandler::readDir('./files/addons');
            $installed_list = FileHandler::readDir('./addons');
            $searched_list = array_merge($downloaded_list, $installed_list);
            $searched_count = count($searched_list);
            if(!$searched_count) return;

            for($i=0;$i<$searched_count;$i++) {
                // 애드온의 이름
                $addon_name = $searched_list[$i];

                // 애드온의 경로 (files/addons가 우선)
                $path = AddonHandler::getAddonPath($addon_name);

                // schemas내의 테이블 생성 xml파일수를 구함
                $tmp_files = FileHandler::readDir($path."schemas");
                $table_count = count($tmp_files);

                // 테이블이 설치되어 있는지 체크
                $created_table_count = 0;
                for($j=0;$j<count($tmp_files);$j++) {
                    list($table_name) = explode(".",$tmp_files[$j]);
                    if($oDB->isTableExists($table_name)) $created_table_count ++;
                }

                // 해당 애드온의 정보를 구함
                $info = $oAddonModel->getAddonInfoXml($addon_name);
                unset($obj);

                $info->addon = $addon_name;
                $info->created_table_count = $created_table_count;
                $info->table_count = $table_count;
                $info->path = $path;
                $info->admin_index_act = $info->admin_index_act;

                $list[] = $info;
            }
            return $list;
        }
    }
?>
