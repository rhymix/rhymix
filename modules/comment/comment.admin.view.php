<?php
    /**
     * @class  commentAdminView
     * @author zero (zero@nzeo.com)
     * @brief  comment 모듈의 admin view 클래스
     **/

    class commentAdminView extends comment {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 목록 출력 (관리자용)
         **/
        function dispCommentAdminList() {
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 30; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->sort_index = 'list_order'; ///< 소팅 값

            $args->module_srl = Context::get('module_srl');

            // 목록 구함, comment->getCommentList 에서 걍 알아서 다 해버리는 구조이다... (아.. 이거 나쁜 버릇인데.. ㅡ.ㅜ 어쩔수 없다)
            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getTotalCommentList($args);

            // 템플릿에 쓰기 위해서 comment_model::getTotalCommentList() 의 return object에 있는 값들을 세팅
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('comment_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('comment_list');
        }

        /**
         * @brief 관리자 페이지의 신고 목록 보기
         **/
        function dispCommentAdminDeclared() {
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 30; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->sort_index = 'comment_declared.declared_count'; ///< 소팅 값
            $args->order_type = 'desc'; ///< 소팅 정렬 값

            // 목록을 구함
            $declared_output = executeQuery('comment.getDeclaredList', $args);

            if($declared_output->data && count($declared_output->data)) {
                $comment_list = array();

                $oCommentModel = &getModel('comment');
                foreach($declared_output->data as $key => $comment) {
                    $comment_list[$key] = new commentItem();
                    $comment_list[$key]->setAttribute($comment);
                }
                $declared_output->data = $comment_list;
            }
        
            // 템플릿에 쓰기 위해서 comment_model::getCommentList() 의 return object에 있는 값들을 세팅
            Context::set('total_count', $declared_output->total_count);
            Context::set('total_page', $declared_output->total_page);
            Context::set('page', $declared_output->page);
            Context::set('comment_list', $declared_output->data);
            Context::set('page_navigation', $declared_output->page_navigation);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('declared_list');
        }
    }
?>
