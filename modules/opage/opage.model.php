<?php
    /**
     * @class  opageModel
     * @author zero (zero@nzeo.com)
     * @brief  opage 모듈의 model 클래스
     **/

    class opageModel extends opage {

        /**
         * @brief 초기화
         **/
        function init() { }

        /**
         * @brief 특정 외부 페이지의 정보를 return
         * 외부 페이지의 경우 기본 모듈의 정보와 설정정보를 함께 다루기 때문에 별도의 model method를 이용하게 한다
         **/
        function getOpage($module_srl) {
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if($module_info->module_srl != $module_srl) return;

            $extra_vars = unserialize($module_info->extra_vars);
            if($extra_vars) {
                foreach($extra_vars as $key => $val) $module_info->{$key} = $val;
                unset($module_info->extra_vars);
            }
            return $module_info;
        }

    }
?>
