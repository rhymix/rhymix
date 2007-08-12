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
         * @brief 숏컷 추가
         **/
        function procAdminInsertShortCut() {
            $module = Context::get('selected_module');

            $output = $this->insertShortCut($module);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
        }

        /**
         * @brief 숏컷의 삭제
         **/
        function procAdminDeleteShortCut() {
            $args->module = Context::get('selected_module');

            // 삭제 불가능 바로가기의 처리
            if(in_array($args->module, array('module','addon','widget','layout'))) return new Object(-1, 'msg_manage_module_cannot_delete');

            $output = executeQuery('admin.deleteShortCut', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 관리자 페이지의 단축 메뉴 추가
         **/
        function insertShortCut($module) {
            // 선택된 모듈의 정보중에서 admin_index act를 구함
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoXml($module);

            $args->module = $module;
            $args->title = $module_info->title;
            $args->default_act = $module_info->admin_index_act;
            if(!$args->default_act) return new Object(-1, 'msg_default_act_is_null');

            $output = executeQuery('admin.insertShortCut', $args);
            return $output;
        }
    }
?>
