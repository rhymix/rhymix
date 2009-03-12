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
            // 위젯 스킨명이 있을 경우 위젯 스킨 상세 정보 함수로 출력
            if(Context::get('skin')) return $this->dispWidgetSkinInfo();

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
         * @brief 위젯 스킨의 상세 정보(skin.xml)를 팝업 출력
         **/
        function dispWidgetSkinInfo() {
            $widget = Context::get('selected_widget');
            $skin = Context::get('skin');

            $path = sprintf('./widgets/%s/', $widget);

            // 선택된 위젯 정보를 구함
            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($path, $skin);

            Context::set('skin_info',$skin_info);

            // 위젯을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('skin_info');
        }

        /**
         * @brief 위젯의 코드 생성기
         **/
        function dispWidgetGenerateCode() {
            // 선택된 위젯 정보를 구함
            $oWidgetModel = &getModel('widget');

            $widget_list = $oWidgetModel->getDownloadedWidgetList();
            $selected_widget = Context::get('selected_widget');
            if(!$selected_widget) $selected_widget = $widget_list[0]->widget;

            $widget_info = $oWidgetModel->getWidgetInfo($selected_widget);
            Context::set('widget_info', $widget_info);
            Context::set('widget_list', $widget_list);
            Context::set('selected_widget', $selected_widget);

            $oModuleModel = &getModel('module');

            // 모듈 카테고리 목록을 구함
            $module_categories = $oModuleModel->getModuleCategories();

            // mid 목록을 가져옴
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = $site_module_info->site_srl;
            $mid_list = $oModuleModel->getMidList($args);

            // 그룹 목록을 가져옴
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups($site_module_info->site_srl);
            Context::set('group_list', $group_list);

            // module_category와 module의 조합
            if($module_categories) {
                foreach($mid_list as $module_srl => $module) {
                    $module_categories[$module->module_category_srl]->list[$module_srl] = $module;
                }
            } else {
                $module_categories[0]->list = $mid_list;
            }

            Context::set('mid_list',$module_categories);

            // 메뉴 목록을 구함
            $output = executeQueryArray('menu.getMenus');
            Context::set('menu_list',$output->data);


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

            $oWidgetModel = &getModel('widget');
            $widget_list = $oWidgetModel->getDownloadedWidgetList();
            Context::set('widget_list',$widget_list);

            // 선택된 위젯이 없을경우 첫 위젯으로
            if(!Context::get('selected_widget')) Context::set('selected_widget',$widget_list[0]->widget);

            $this->dispWidgetGenerateCode();
            $this->setTemplateFile('widget_generate_code_in_page');
        }

        /**
         * @brief 페이지 관리에서 사용될 위젯 스타일 코드 생성 팝업
         **/
        function dispWidgetStyleGenerateCodeInPage() {

            // 위젯 스타일 목록
            $oWidgetModel = &getModel('widget');
            $widgetStyle_list = $oWidgetModel->getDownloadedWidgetStyleList();
            Context::set('widgetStyle_list',$widgetStyle_list);

            // 선택된 위젯 스타일 목록
            $widgetstyle = Context::get('widgetstyle');
            $widgetstyle_info = $oWidgetModel->getWidgetStyleInfo($widgetstyle);
            if($widgetstyle && $widgetstyle_info){
                Context::set('widgetstyle_info',$widgetstyle_info);
            }
            
            $this->dispWidgetGenerateCode();
            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('widget_style_generate_code_in_page');
        }
    }
?>
