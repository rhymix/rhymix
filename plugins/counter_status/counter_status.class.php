<?php
    /**
     * @class counter_status
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief counter 모듈의 데이터를 이용하여 counter 현황을 출력
     **/

    class counter_status extends PluginHandler {

        /**
         * @brief 플러그인의 실행 부분
         * ./plugins/플러그인/conf/info.xml에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 전체, 어제, 오늘 접속 현황을 가져옴
            $oCounterModel = &getModel('counter');

            $output = $oCounterModel->getStatus(array('00000000', date('Ymd', time()-60*60*24), date('Ymd')));
            foreach($output as $key => $val) {
                if(!$key) Context::set('total_counter', $val);
                elseif($key == date("Ymd")) Context::set('today_counter', $val);
                else Context::set('yesterday_counter', $val);
            }

            // 변수 설정
            Context::set('style', $args->style);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->plugin_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'counter_status';

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
