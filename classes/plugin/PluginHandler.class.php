<?php
    /**
    * @class PluginHandler
    * @author zero (zero@nzeo.com)
    * @brief addon을 호출하여 실행
    **/

    class PluginHandler {

        var $plugin_path = '';

        /**
         * @brief 플러그인을 찾아서 실행하고 결과를 출력
         * <div plugin='플러그인'...></div> 태그 사용 templateHandler에서 PluginHandler::execute()를 실행하는 코드로 대체하게 된다
         **/
        function execute($plugin, $args) {
            // $plugin의 객체를 받음 
            $oPlugin = PluginHandler::getObject($plugin);
            if(!$oPlugin) return;

            // 플러그인 실행
            return $oPlugin->proc($args);
        }

        /**
         * @brief 플러그인 객체를 return
         **/
        function getObject($plugin) {
            // 일단 플러그인의 위치를 찾음
            $oPluginModel = &getModel('plugin');
            $path = $oPluginModel->getPluginPath($plugin);

            // 플러그인 클래스 파일을 찾고 없으면 에러 출력 (html output)
            $class_file = sprintf('%s%s.class.php', $path, $plugin);
            if(!file_exists($class_file)) return sprintf(Context::getLang('msg_plugin_is_not_exists'), $plugin);

            // 플러그인 클래스를 include
            require_once($class_file);
            
            // 객체 생성
            $eval_str = sprintf('$oPlugin = new %s();', $plugin);
            @eval($eval_str);
            if(!is_object($oPlugin)) return sprintf(Context::getLang('msg_plugin_object_is_null'), $plugin);
            if(!method_exists($oPlugin, 'proc')) return sprintf(Context::getLang('msg_plugin_proc_is_null'), $plugin);

            $oPlugin->plugin_path = $path;

            return $oPlugin;
        }

    }

?>
