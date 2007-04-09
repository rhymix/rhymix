<?php
    /**
     * @class  pollView
     * @author zero (zero@nzeo.com)
     * @brief  poll모듈의 View class
     **/

    class pollView extends poll {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 페이지
         **/
        function dispPollAdminList() {
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 50; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->sort_index = 'list_order'; ///< 소팅 값

            // 목록 구함
            $oPollModel = &getModel('poll');
            $output = $oPollModel->getPollList($args);

            // 템플릿 변수 설정
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('poll_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            Context::set('module_list', $module_list);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('poll_list');
        }

    }
?>
