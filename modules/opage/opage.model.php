<?php
    /**
     * @class  opageModel
     * @author NHN (developers@xpressengine.com)
     * @brief model class of the opage module
     **/

    class opageModel extends opage {

        /**
         * @brief Initialization
         **/
        function init() { }

        /**
         * @brief Return information on the external page
         * Use a separate model method because external page handles information and configurations of the defaul module either.
         **/
        function getOpage($module_srl) {
            $oModuleModel = &getModel('module');
			$columnList = array('module_srl');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
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
