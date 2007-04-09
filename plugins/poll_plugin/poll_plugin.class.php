<?php
    /**
     * @class 
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief 설문조사를 출력
     **/

    class poll_plugin extends PluginHandler {

        /**
         * @brief 플러그인의 실행 부분
         * ./plugins/플러그인/conf/info.xml에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
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
