<?php
    /**
     * @class  adminAdminModel
     * @author zero (zero@nzeo.com)
     * @brief  admin 모듈의 admin model class
     **/

    class adminAdminModel extends admin {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief admin shortcut 에 등록된 목록을 return;
         **/
        function getShortCuts() {
            $output = executeQuery('admin.getShortCutList');
            if(!$output->toBool()) return $output;

            if(!is_array($output->data)) $list = array($output->data);
            else $list = $output->data;

            foreach($list as $val) {
                $shortcut_list[$val->module] = $val;
            }

            // 모듈 목록을 구해와서 숏컷에 해당하는 타이틀을 추출
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModulesXmlInfo();
            foreach($module_list as $key => $val) {
                $module_name = $val->module;
                if($shortcut_list[$module_name]) $shortcut_list[$module_name]->title = $val->title;
            }

            return $shortcut_list;
        }

    }
?>
