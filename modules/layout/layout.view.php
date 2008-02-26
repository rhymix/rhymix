<?php
    /**
     * @class  layoutView
     * @author zero (zero@nzeo.com)
     * @brief  layout 모듈의 admin view class
     **/

    class layoutView extends layout {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 레이아웃의 상세 정보(conf/info.xml)를 팝업 출력
         **/
        function dispLayoutInfo() {
            // 선택된 레이아웃 정보를 구함 
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayoutInfo(Context::get('selected_layout'));
            if(!$layout_info) exit();
            Context::set('layout_info', $layout_info);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('layout_detail_info');
        }
    }
?>
