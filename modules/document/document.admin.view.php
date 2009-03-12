<?php
    /**
     * @class  documentAdminView
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 admin view 클래스
     **/

    class documentAdminView extends document {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 목록 출력 (관리자용)
         **/
        function dispDocumentAdminList() {
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 30; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->search_target = Context::get('search_target'); ///< 검색 대상 (title, contents...)
            $args->search_keyword = Context::get('search_keyword'); ///< 검색어

            $args->sort_index = 'list_order'; ///< 소팅 값

            $args->module_srl = Context::get('module_srl');

            // 목록 구함, document->getDocumentList 에서 걍 알아서 다 해버리는 구조이다... (아.. 이거 나쁜 버릇인데.. ㅡ.ㅜ 어쩔수 없다)
            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDocumentList($args);

            // 템플릿에 쓰기 위해서 document_model::getDocumentList() 의 return object에 있는 값들을 세팅
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('document_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿에서 사용할 검색옵션 세팅
            $count_search_option = count($this->search_option);
            for($i=0;$i<$count_search_option;$i++) {
                $search_option[$this->search_option[$i]] = Context::getLang($this->search_option[$i]);
            }
            Context::set('search_option', $search_option);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('document_list');
        }

        /**
         * @brief 문서 모듈 설정 
         **/
        function dispDocumentAdminConfig() {
            $oDocumentModel = &getModel('document');
            $config = $oDocumentModel->getDocumentConfig();
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('document_config');
        }

        /**
         * @brief 관리자 페이지의 신고 목록 보기
         **/
        function dispDocumentAdminDeclared() {
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 30; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->sort_index = 'document_declared.declared_count'; ///< 소팅 값
            $args->order_type = 'desc'; ///< 소팅 정렬 값

            // 목록을 구함
            $declared_output = executeQuery('document.getDeclaredList', $args);

            if($declared_output->data && count($declared_output->data)) {
                $document_list = array();

                $oDocumentModel = &getModel('document');
                foreach($declared_output->data as $key => $document) {
                    $document_list[$key] = new documentItem();
                    $document_list[$key]->setAttribute($document);
                }
                $declared_output->data = $document_list;
            }
        
            // 템플릿에 쓰기 위해서 document_model::getDocumentList() 의 return object에 있는 값들을 세팅
            Context::set('total_count', $declared_output->total_count);
            Context::set('total_page', $declared_output->total_page);
            Context::set('page', $declared_output->page);
            Context::set('document_list', $declared_output->data);
            Context::set('page_navigation', $declared_output->page_navigation);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('declared_list');
        }

        function dispDocumentAdminAlias() {
            $args->document_srl = Context::get('document_srl');
            if(!$args->document_srl) return $this->dispDocumentAdminList();

            $oModel = &getModel('document');
            $oDocument = $oModel->getDocument($args->document_srl);
            if(!$oDocument->isExists()) return $this->dispDocumentAdminList();
            Context::set('oDocument', $oDocument);

            $output = executeQueryArray('document.getAliases', $args);
            if(!$output->data)
            {
                $aliases = array();
            }
            else
            {
                $aliases = $output->data; 
            }


            Context::set('aliases', $aliases);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('document_alias');
        }
    }
?>
