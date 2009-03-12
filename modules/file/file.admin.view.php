<?php
    /**
     * @class  fileAdminView
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 admin view 클래스
     **/

    class fileAdminView extends file {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 목록 출력 (관리자용)
         **/
        function dispFileAdminList() {
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 30; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->sort_index = 'file_srl'; ///< 소팅 값
            $args->isvalid = Context::get('isvalid');
            $args->module_srl = Context::get('module_srl');

            // 목록 구함
            $oFileModel = &getAdminModel('file');
            $output = $oFileModel->getFileList($args);

            // 목록의 loop를 돌면서 document를 구하기
            if($file_count) {
                $document_srl_list = array();

                foreach($output->data as $val) {
                    if(!in_array($val->upload_target_srl, $document_srl_list)) $document_srl_list[] = $val->upload_target_srl;
                }

                // comment의 첨부파일이면 document_srl을 추가로 구함
                if($document_srl_list) {
                    $oCommentModel = &getModel('comment');
                    $comment_output = $oCommentModel->getComments($document_srl_list);

                    if($comment_output) {
                        foreach($comment_output as $val) {
                            $comment_list[$val->comment_srl] = $val->document_srl;
                        }

                        $file_list_keys = array_keys($output->data);
                        for($i=0; $i < $file_count; $i++) {
                            $output->data[$file_list_keys[$i]]->target_document_srl = $comment_list[$output->data[$file_list_keys[$i]]->upload_target_srl];
                        }

                        foreach($comment_output as $val) {
                            if(!in_array($val->document_srl, $document_srl_list)) $document_srl_list[] = $val->document_srl;
                        }
                    }

                    $args->document_srls = implode(',', $document_srl_list);
                    $document_output = executeQueryArray('document.getDocuments', $args);

                    if($document_output->data) {
                        foreach($document_output->data as $document) {
                            $document_list[$document->document_srl] = $document;
                        }
                    }
                }

            }

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('file_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            Context::set('document_list', $document_list);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('file_list');
        }

        /**
         * @brief 첨부파일 정보 설정 (관리자용)
         **/
        function dispFileAdminConfig() {
            $oFileModel = &getModel('file');
            $config = $oFileModel->getFileConfig();
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('file_config');
        }

    }
?>
