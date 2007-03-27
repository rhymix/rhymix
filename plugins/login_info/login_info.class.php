<?php

    /**
     * @class login_info
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief 로그인 폼을 출력하는 플러그인
     *
     * $logged_info를 이용하며 이는 미리 설정되어 있음
     **/

    class login_info extends PluginHandler {

        /**
         * @brief 플러그인의 실행 부분
         * ./plugins/플러그인/conf/info.xml에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 return 해주어야 한다
         **/
        function proc($args) {
            // 변수 설정
            Context::set('style', $args->style);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->plugin_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'login_info';

            // 템플릿 컴파일
            $oTemplate = new TemplateHandler();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }


    }
?>
