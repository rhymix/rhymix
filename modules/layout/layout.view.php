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
