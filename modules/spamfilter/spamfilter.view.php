<?php
    /**
     * @class  spamfilterView
     * @author zero (zero@nzeo.com)
     * @brief  spamfilter 모듈의 View class
     **/

    class spamfilterView extends spamfilter {

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로 지정 
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 관리자 모드에서 보여줄 화면
         **/
        function dispContent() {
            // 등록된 스패머의 목록을 가져옴
            $oSpamFilterModel = &getModel('spamfilter');
            $output = $oSpamFilterModel->getSpammerList();

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('document_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }


    }
?>
