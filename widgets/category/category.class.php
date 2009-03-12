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
            $oModuleModel = &getModel('module');

            // 대상 모듈 (mid_list는 기존 위젯의 호환을 위해서 처리하는 루틴을 유지. module_srl로 위젯에서 변경)
            if($args->mid_list) {
                $tmp_mid = explode(",",$args->mid_list);
                $args->mid = $tmp_mid[0];
            } 

            if($args->mid) $args->srl = $oModuleModel->getModuleSrlByMid($args->mid);

            $obj->module_srl = $args->srl;

            // 선택된 모듈이 없으면 실행 취소
            if(!$obj->module_srl) return Context::getLang('msg_not_founded');

            // 모듈의 정보를 구함
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);

            // 대상 모듈의 카테고리 파일을 불러옴
            $oDocumentModel = &getModel('document');
            $category_list = $oDocumentModel->getCategoryList($obj->module_srl);

            // 전체 개수를 구함
            $widget_info->total_document_count = $oDocumentModel->getDocumentCount($obj->module_srl);

            $widget_info->module_info = $module_info;
            $widget_info->mid = $module_info->mid;
            $widget_info->document_category = $document_category;
            $widget_info->category_list = $category_list;

            if($module_info->site_srl) {
                $site_module_info = Context::get('site_module_info');
                if($site_module_info->site_srl == $module_info->site_srl) $widget_info->domain = $site_module_info->domain;
                else {
                    $site_info = $oModuleModel->getSiteInfo($module_info->site_srl);
                    $widget_info->domain = $site_info->domain;
                }
            } else $widget_info->domain = Context::getDefaultUrl();

            Context::set('widget_info', $widget_info);

            // 템플릿 컴파일
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            $tpl_file = 'category';

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
