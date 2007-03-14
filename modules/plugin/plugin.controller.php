<?php
    /**
     * @class  pluginController
     * @author zero (zero@nzeo.com)
     * @brief  plugin 모듈의 Controller class
     **/

    class pluginController extends plugin {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 플러그인의 생성된 코드를 return
         **/
        function procGenerateCode() {
            // 변수 정리
            //$vars = Context::getRequestVars();
            unset($vars->module);
            unset($vars->act);
            unset($vars->selected_plugin);
            if($vars) foreach($vars as $key=>$val) $vars->{$key} = str_replace(array('"','\''),array('\"','\\\''),$val);

            // 코드 출력
            $this->add('plugin_code', 'hahaha');
        }

    }
?>
