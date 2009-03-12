<?php
    /**
     * @class  addonAdminView
     * @author zero (zero@nzeo.com)
     * @brief  addon 모듈의 admin view class
     **/

    class addonAdminView extends addon {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 애드온 관리 메인 페이지 (목록 보여줌)
         **/
        function dispAddonAdminIndex() {
            $site_module_info = Context::get('site_module_info');

            // 애드온 목록을 세팅
            $oAddonModel = &getAdminModel('addon');
            $addon_list = $oAddonModel->getAddonList($site_module_info->site_srl);
            Context::set('addon_list', $addon_list);

            // 템플릿 패스 및 파일을 지정
            $this->setTemplateFile('addon_list');
        }

        /**
         * @biref 애드온 세부 설정 팝업 출력
         **/
        function dispAddonAdminSetup() {
            $site_module_info = Context::get('site_module_info');

            // 요청된 애드온을 구함
            $selected_addon = Context::get('selected_addon');

            // 요청된 애드온의 정보를 구함
            $oAddonModel = &getAdminModel('addon');
            $addon_info = $oAddonModel->getAddonInfoXml($selected_addon, $site_module_info->site_srl);
            Context::set('addon_info', $addon_info);

            // mid 목록을 가져옴
            $oModuleModel = &getModel('module');

            if($site_module_info->site_srl) $args->site_srl = $site_module_info->site_srl;
            $mid_list = $oModuleModel->getMidList($args);

            // module_category와 module의 조합
            if(!$site_module_info->site_srl) {
                // 모듈 카테고리 목록을 구함
                $module_categories = $oModuleModel->getModuleCategories();

                foreach($mid_list as $module_srl => $module) {
                    $module_categories[$module->module_category_srl]->list[$module_srl] = $module; 
                }
            } else {
                $module_categories[0]->list = $mid_list;
            }

            Context::set('mid_list',$module_categories);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 패스 및 파일을 지정
            $this->setTemplateFile('setup_addon');
        }

        /**
         * @brief 애드온의 상세 정보(conf/info.xml)를 팝업 출력
         **/
        function dispAddonAdminInfo() {
            $site_module_info = Context::get('site_module_info');

            // 요청된 애드온을 구함
            $selected_addon = Context::get('selected_addon');

            // 요청된 애드온의 정보를 구함
            $oAddonModel = &getAdminModel('addon');
            $addon_info = $oAddonModel->getAddonInfoXml($selected_addon, $site_module_info->site_srl);
            Context::set('addon_info', $addon_info);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 패스 및 파일을 지정
            $this->setTemplateFile('addon_info');
        }

    }
?>
