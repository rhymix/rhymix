<?php
    /**
     * @class  documentView
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 view 클래스
     **/

    class documentView extends document {

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
            $args->list_count = 50; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->search_target = Context::get('search_target'); ///< 검색 대상 (title, contents...)
            $args->search_keyword = Context::get('search_keyword'); ///< 검색어

            $args->sort_index = 'list_order'; ///< 소팅 값

            // 목록 구함, document->getDocumentList 에서 걍 알아서 다 해버리는 구조이다... (아.. 이거 나쁜 버릇인데.. ㅡ.ㅜ 어쩔수 없다)
            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDocumentList($args);

            // 목록의 loop를 돌면서 mid를 구하기 위한 module_srl값을 구함
            $document_count = count($output->data);
            $module_srl_list = array();
            if($document_count) {
                foreach($output->data as $key => $val) {
                    $module_srl = $val->module_srl;
                    if(!in_array($module_srl, $module_srl_list)) $module_srl_list[] = $module_srl;
                }
                if(count($module_srl_list)) {
                    $args->module_srls = implode(',',$module_srl_list);
                    $mid_output = executeQuery('module.getModuleInfoByModuleSrl', $args);
                    if($mid_output->data && !is_array($mid_output->data)) $mid_output->data = array($mid_output->data);
                    for($i=0;$i<count($mid_output->data);$i++) {
                        $mid_info = $mid_output->data[$i];
                        $module_list[$mid_info->module_srl] = $mid_info;
                    }
                }
            }

            // 템플릿에 쓰기 위해서 document_model::getDocumentList() 의 return object에 있는 값들을 세팅
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('document_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            Context::set('module_list', $module_list);

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

    }
?>
