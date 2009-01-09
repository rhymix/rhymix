<?php
    /**
     * @class  widgetAdminModel
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief  widget 모듈의 AdminModel class
     **/

    class widgetAdminModel extends widget {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 위젯의 경로를 구함
         **/
        function getWidgetAdminModuleList() {
            $args->module_srls = Context::get('module_srls');
            $output = executeQueryArray('module.getModulesInfo', $args);
            if(!$output->toBool() || !$output->data) return new Object();

            foreach($output->data as $key => $val) {
                $list[$val->module_srl] = array('module_srl'=>$val->module_srl,'mid'=>$val->mid,'browser_title'=>$val->browser_title);
            }
            $modules = explode(',',$args->module_srls);
            for($i=0;$i<count($modules);$i++) {
                $module_list[$modules[$i]] = $list[$modules[$i]];
            }

            $this->add('id', Context::get('id'));
            $this->add('module_list', $module_list);
        }
    }
?>
