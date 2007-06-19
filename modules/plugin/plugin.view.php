<?php
    /**
     * @class  widgetView
     * @author zero (zero@nzeo.com)
     * @brief  widget 모듈의 View class
     **/

    class widgetView extends widget {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }
        
        /**
         * @brief 위젯의 상세 정보(conf/info.xml)를 팝업 출력
         **/
        function dispWidgetInfo() {
            // 선택된 위젯 정보를 구함
            $oWidgetModel = &getModel('widget');
            $widget_info = $oWidgetModel->getWidgetInfo(Context::get('selected_widget'));
            Context::set('widget_info', $widget_info);

            // 위젯을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('widget_detail_info');
        }

        /**
         * @brief 위젯의 코드 생성기
         **/
        function dispWidgetGenerateCode() {
            // 선택된 위젯 정보를 구함
            $oWidgetModel = &getModel('widget');
            $widget_info = $oWidgetModel->getWidgetInfo(Context::get('selected_widget'));
            Context::set('widget_info', $widget_info);

            // mid 목록을 가져옴
            $oModuleModel = &getModel('module');
            $mid_list = $oModuleModel->getMidList();
            Context::set('mid_list', $mid_list);

            // 스킨의 정보를 구함
            $skin_list = $oModuleModel->getSkins($widget_info->path);
            Context::set('skin_list', $skin_list);

            // 위젯을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('widget_generate_code');
        }

        /**
         * @brief 페이지 관리에서 사용될 코드 생성 팝업
         **/
        function dispWidgetGenerateCodeInPage() {
            $this->dispWidgetGenerateCode();
            $this->setTemplateFile('widget_generate_code_in_page');
        }

    }
?>
