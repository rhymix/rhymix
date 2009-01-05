<?php
    /**
     * @class planet_document 
     * @author zero (zero@nzeo.com)
     * @brief 플래닛 글 목록 출력
     * @version 0.1
     **/

    class planet_document extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 제목
            $title = $args->title;

            // 출력된 목록 수
            $list_count = (int)$args->list_count;
            if(!$list_count) $list_count = 5;
            $args->list_count = $list_count;

            // 중복 허용/ 비허용 체크
            if($args->allow_repetition != 'Y') {
                $output = executeQueryArray('widgets.planet_document.getUniqueNewestDocuments', $args);
            } else {
                $output = executeQueryArray('widgets.planet_document.getNewestDocuments', $args);
            }

            // 플래닛 글 목록 구함
            $oPlanetModel = &getModel('planet');
            Context::set('planet', $planet = $oPlanetModel->getPlanet());

            foreach($output->data as $key => $val) {
                $document_srl = $val->document_srl;
                $oPlanet = null;
                $oPlanet = new PlanetItem();
                $oPlanet->setAttribute($val);
                $planet_list[] = $oPlanet;
            }
            Context::set('planet_list', $planet_list);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'list';

            if(!$args->thumbnail_width) $args->thumbnail_width = 50;
            if(!$args->thumbnail_height) $args->thumbnail_height = 50;
            $widget_info->thumbnail_width = $args->thumbnail_width;
            $widget_info->thumbnail_height = $args->thumbnail_height;
            Context::set('widget_info', $widget_info);

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            $output = $oTemplate->compile($tpl_path, $tpl_file);
            return $output;
        }
    }
?>
