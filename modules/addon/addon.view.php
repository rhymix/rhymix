<?php
    /**
     * @class  addonView
     * @author zero (zero@nzeo.com)
     * @brief  addon 모듈의 View class
     **/

    class addonView extends addon {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 애드온 관리 메인 페이지
         **/
        function dispIndex() {
            $this->dispAddonList();
        }

        /**
         * @brief 애드온의 상세 정보(conf/info.xml)를 팝업 출력
         **/
        function dispAddonInfo() {
            // 모듈 목록을 구해서 
            $oAddonModel = &getModel('addon');
            $addon_info = $oAddonModel->getAddonInfoXml(Context::get('selected_addon'));
            Context::set('addon_info', $addon_info);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('addon_info');
        }

        /**
         * @brief 애드온 목록을 보여줌
         **/
        function dispAddonList() {
            // 애드온 목록을 세팅
            $oAddonModel = &getModel('addon');
            $addon_list = $oAddonModel->getAddonList();
            Context::set('addon_list', $addon_list);

            $this->setTemplateFile('addon_list');
        }


    }
?>
