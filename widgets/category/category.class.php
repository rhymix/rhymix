<?php
    /**
     * @class category
     * @author zero (zero@nzeo.com)
     * @brief 분류 출력기
     * @version 0.1
     **/

    class category extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 위젯 자체적으로 설정한 변수들을 체크
            $pos = strpos($args->mid_list, ',');
            if($pos === false) $mid = $args->mid_list;
            else $mid = substr($args->mid_list, 0, $pos);
            if(!$mid) return;

            // 대상 mid의 module_srl 을 구함
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByMid($mid);

            $module_srl = $module_info->module_srl;

            // 대상 모듈의 카테고리 파일을 불러옴
            $oDocumentModel = &getModel('document');
            $category_list = $oDocumentModel->getCategoryList($module_srl);

            // 모듈의 정보를 구함
            $widget_info->module_info = $module_info;

            $widget_info->mid = $mid;
            $widget_info->document_category = $document_category;
            $widget_info->module_info = $module_info;
            $widget_info->category_list = $category_list;

            // 전체 개수를 구함
            $total_count = $oDocumentModel->getDocumentCount($module_srl);
            $widget_info->total_document_count = $total_count;

            Context::set('widget_info', $widget_info);

            // 템플릿 컴파일
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            $tpl_file = 'category';

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
