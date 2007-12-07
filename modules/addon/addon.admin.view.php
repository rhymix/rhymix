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
            // 애드온 목록을 세팅
            $oAddonModel = &getAdminModel('addon');
            $addon_list = $oAddonModel->getAddonList();
            Context::set('addon_list', $addon_list);

            // 템플릿 패스 및 파일을 지정
            $this->setTemplateFile('addon_list');
        }

        /**
         * @biref 애드온 세부 설정 팝업 출력
         **/
        function dispAddonAdminSetup() {
            // 요청된 애드온을 구함
            $selected_addon = Context::get('selected_addon');

            // 요청된 애드온의 정보를 구함
            $oAddonModel = &getAdminModel('addon');
            $addon_info = $oAddonModel->getAddonInfoXml($selected_addon);
            Context::set('addon_info', $addon_info);

            // mid 목록을 가져옴
            $oModuleModel = &getModel('module');
            $mid_list = $oModuleModel->getMidList();
            Context::set('mid_list', $mid_list);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 패스 및 파일을 지정
            $this->setTemplateFile('setup_addon');
        }

        /**
         * @brief 애드온의 상세 정보(conf/info.xml)를 팝업 출력
         **/
        function dispAddonAdminInfo() {
            // 요청된 애드온을 구함
            $selected_addon = Context::get('selected_addon');

            // 요청된 애드온의 정보를 구함
            $oAddonModel = &getAdminModel('addon');
            $addon_info = $oAddonModel->getAddonInfoXml($selected_addon);
            Context::set('addon_info', $addon_info);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 패스 및 파일을 지정
            $this->setTemplateFile('addon_info');
        }

    }
?>
