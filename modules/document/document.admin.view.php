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
            $args->list_count = 50; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->search_target = Context::get('search_target'); ///< 검색 대상 (title, contents...)
            $args->search_keyword = Context::get('search_keyword'); ///< 검색어

            $args->sort_index = 'list_order'; ///< 소팅 값

            $args->module_srl = Context::get('module_srl');

            // mid목록을 구함
            $oModuleModel = &getModel('module');
            $mid_list = $oModuleModel->getMidList();
            Context::set('mid_list', $mid_list);

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
         * @brief 관리자가 선택한 문서에 대한 관리
         **/
        function dispDocumentAdminManageDocument() {
            // 선택한 목록을 세션에서 가져옴
            $flag_list = $_SESSION['document_management'];
            if(count($flag_list)) {
                foreach($flag_list as $key => $val) {
                    if(!is_bool($val)) continue;
                    $document_srl_list[] = $key;
                }

            }

            if(count($document_srl_list)) {
                $oDocumentModel = &getModel('document');
                $document_list = $oDocumentModel->getDocuments($document_srl_list, $this->grant->is_admin);
                Context::set('document_list', $document_list);
            }

            $oModuleModel = &getModel('module');

            // 모듈 카테고리 목록을 구함
            $module_categories = $oModuleModel->getModuleCategories();

            // 모듈의 목록을 가져옴
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = $site_module_info->site_srl;
            $module_list = $oModuleModel->getMidList($args);

            // 사이트 운영자가 아닌 경우
            if(!$oModuleModel->isSiteAdmin()) {
                $logged_info = Context::get('logged_info');
                $user_id = $logged_info->user_id;
                $group_list = $logged_info->group_list;

                if($logged_info->is_admin != 'Y') {
                    foreach($module_list as $key => $val) {
                        $info = $oModuleModel->arrangeModuleInfo($val);

                        // 직접 최고 관리자로 지정이 안되어 있으면 그룹을 체크
                        if(!in_array($user_id, $info->admin_id)) {

                            $is_granted = false;
                            $manager_group = $info->grants['manager'];
                            if(count($group_list) && count($manager_group)) {
                                foreach($group_list as $group_srl => $group_info) {
                                    if(in_array($group_srl, $manager_group)) {
                                        $is_granted = true;
                                        break;
                                    }
                                }
                            }
                            if(!$is_granted) unset($module_list[$key]);
                        }
                    }
                }
            }

            // 게시판만 뽑자
            foreach($module_list as $module_srl => $module) {
                if($module->module != 'board') unset($module_list[$module_srl]);
            }

            // module_category와 module의 조합
            if($module_categories) {
                foreach($module_list as $module_srl => $module) {
                    $module_categories[$module->module_category_srl]->list[$module_srl] = $module; 
                }
            } else {
                $module_categories[0]->list = $module_list;
            }


            // 모듈 카테고리 목록과 모듈 목록의 조합
            if(count($module_list)>1) Context::set('module_list', $module_categories);

            // 팝업 레이아웃 선택
            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('popup_layout');

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('checked_list');
        }

        /**
         * @brief 관리자 페이지의 신고 목록 보기
         **/
        function dispDocumentAdminDeclared() {
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 50; ///< 한페이지에 보여줄 글 수
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
    }
?>
