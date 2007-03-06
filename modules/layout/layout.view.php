<?php
    /**
     * @class  layoutView
     * @author zero (zero@nzeo.com)
     * @brief  layout 모듈의 View class
     **/

    class layoutView extends layout {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 레이아웃 관리의 첫 페이지
         **/
        function dispContent() {
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('index');
        }
 
        /**
         * @brief 레이아웃 등록 페이지 step 1
         **/
        function dispInsertLayout() {
            // 레이아웃 목록을 세팅
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('insert_layout');
        }

        /**
         * @brief 레이아웃 등록 페이지 step 2
         **/
        function dispInsertLayout2() {
            // 선택된 레이아웃의 정볼르 구해서 세팅 
            $layout = Context::get('layout');
            $oLayoutModel = &getModel('layout');
            $info = $oLayoutModel->getLayoutInfoXml($layout);
            Context::set('info', $info);

            $this->setTemplateFile('insert_layout2');
        }

        /**
         * @brief 레이아웃 메뉴의 개별 정보 출력
         **/
        function dispLayoutMenuInfo() {
            // 팝업이기 때문에 팝업용 레이아웃을 지정
            $this->setLayoutPath('./common/tpl/');
            $this->setLayoutFile('popup_layout');

            // menu_srl에 해당하는 값을 가져옴

            // 템플릿 지정
            $this->setTemplateFile('layout_menu_info');
        }
 
        /**
         * @brief 레이아웃 목록을 보여줌
         **/
        function dispLayoutList() {
            // 레이아웃 목록을 세팅
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('layout_list');
        }


    }
?>
