<?php
    /**
     * @class  trackbackAdminView
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 admin view class
     **/

    class trackbackAdminView extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 목록 출력 (관리자용)
         **/
        function dispTrackbackAdminList() {
            // 설정 구함
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('trackback');
            Context::set('config',$config);

            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 30; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->sort_index = 'list_order'; ///< 소팅 값
            $args->module_srl = Context::get('module_srl');

            // 목록 구함
            $oTrackbackAdminModel = &getAdminModel('trackback');
            $output = $oTrackbackAdminModel->getTotalTrackbackList($args);

            // 템플릿에 쓰기 위해서 변수 설정
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('trackback_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('trackback_list');
        }

    }
?>
