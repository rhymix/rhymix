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

            // 기존에 mid_list, mid를 쓸 때의 코드를 위하여 하위 호환 유지 코드
            if($args->mid_list) {
                $tmp_mid = explode(",",$args->mid_list);
                $mid = $tmp_mid[0];
            } elseif($args->mid) {
                $mid = $args->mid;
            }
            if($mid) {
                $module_srl = $oModuleModel->getModuleSrlByMid($mid);
            }

            if($args->srl) $module_srl = $args->srl;

            if(is_array($module_srl)) $module_srl = $module_srl[0];

            // DocumentModel::getMonthlyArchivedList()를 이용하기 위한 변수 정리
            $obj->module_srl = $module_srl;

            // 선택된 모듈이 없으면 실행 취소
            if(!$obj->module_srl) return;

            // 모듈의 정보를 구함
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);

            // 대상 모듈의 카테고리 파일을 불러옴
            $oDocumentModel = &getModel('document');
            $category_list = $oDocumentModel->getCategoryList($obj->module_srl);

            // 모듈의 정보를 구함
            $widget_info->module_info = $module_info;
            $widget_info->mid = $module_info->mid;
            $widget_info->document_category = $document_category;
            $widget_info->category_list = $category_list;

            // 전체 개수를 구함
            $total_count = $oDocumentModel->getDocumentCount($obj->module_srl);
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
