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
        function dispLayoutAdminContent() {
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('index');
        }
 
        /**
         * @brief 레이아웃 등록 페이지 
         * 1차적으로 레이아웃만 선택한 후 DB 에 빈 값을 넣고 그 후 상세 값 설정하는 단계를 거침
         **/
        function dispLayoutAdminInsert() {
            // 레이아웃 목록을 세팅
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('insert_layout');
        }

        /**
         * @brief 레이아웃 세부 정보 입력
         **/
        function dispLayoutAdminMenu() {
            // 선택된 레이아웃의 정보르 구해서 세팅 
            $layout_srl = Context::get('layout_srl');

            // 레이아웃의 정보를 가져옴
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);

            // 등록된 레이아웃이 없으면 오류 표시
            if(!$layout_info) return $this->dispContent();

            Context::set('layout_info', $layout_info);

            $this->setTemplateFile('layout_info');
        }

        /**
         * @brief 레이아웃의 상세 정보(conf/info.xml)를 팝업 출력
         **/
        function dispLayoutAdminInfo() {
            // 선택된 레이아웃 정보를 구함 
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayoutInfo(Context::get('selected_layout'));
            Context::set('layout_info', $layout_info);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('layout_detail_info');
        }


        /**
         * @brief 레이아웃 목록을 보여줌
         **/
        function dispLayoutAdminDownloadedList() {
            // 레이아웃 목록을 세팅
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('downloaded_layout_list');
        }


    }
?>
