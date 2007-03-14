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
            $vars = Context::getRequestVars();
            $plugin = $vars->selected_plugin;

            unset($vars->module);
            unset($vars->act);
            unset($vars->selected_plugin);

            $attribute = array();
            if($vars) {
                foreach($vars as $key=>$val) {
                    debugPrint($val);
                    debugPrint(strpos('|@|',  $val));
                    if(strpos('|@|', $val)>0) $val = str_replace('|@|',',',$val);
                    $attribute[] = sprintf('%s="%s"', $key, str_replace('"','\"',$val));
                }
            }

            $plugin_code = sprintf('<div plugin="%s" %s></div>', $plugin, implode(' ',$attribute));

            // 코드 출력
            $this->add('plugin_code', $plugin_code);
        }

    }
?>
