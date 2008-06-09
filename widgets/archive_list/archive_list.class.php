<?php
    /**
     * @class archive_list
     * @author zero (zero@nzeo.com)
     * @brief 보관현황 목록 출력
     * @version 0.1
     **/

    class archive_list extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            if($args->mid_list) {
                $tmp_mid = explode(",",$args->mid_list);
                $mid = $tmp_mid[0];
            } else {
                $mid = $args->mid;
            }

            // DocumentModel::getMonthlyArchivedList()를 이용하기 위한 변수 정리
            $obj->mid = $mid;

            // document 모듈의 model 객체를 받아서 getMonthlyArchivedList() method를 실행
            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getMonthlyArchivedList($obj);

            // 템플릿 파일에서 사용할 변수들을 세팅
            $widget_info->module_name = $mid;
            $widget_info->mid = $mid;
            
            $widget_info->title = $args->title;
            $widget_info->archive_list = $output->data;

            Context::set('widget_info', $widget_info);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'archive_list';

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
