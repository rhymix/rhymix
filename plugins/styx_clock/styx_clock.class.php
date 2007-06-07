<?php
    /**
     * @class styx_clock
     * @author styx (styx@bystyx.com)
     * @brief 플래시 시계 출력
     * @version 0.1
     **/

    class styx_clock extends PluginHandler {

        /**
         * @brief 플러그인의 실행 부분
         *
         * ./plugins/플러그인/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->plugin_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'clock';

            $theme = $args->theme;
            if($theme != "white") $theme = "black";

            $day = $args->day;
            if($day != "false") $day = "true";

            $width = $args->width;
            if(!$width) $width = 200;
            $plugin_info->width = $width;

            $plugin_info->src = sprintf("%s/clock.swf?theme=%s&day=%s", $tpl_path, $theme, $day);

            Context::set('plugin_info', $plugin_info);

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
