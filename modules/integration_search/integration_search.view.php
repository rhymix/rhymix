<?php
    /**
     * @class  integration_searchView
     * @author zero (zero@nzeo.com)
     * @brief  integration_search module의 view class
     *
     * 통합검색 출력
     *
     **/

    class integration_searchView extends integration_search {

        var $target_mid = array();
        var $skin = 'default';

        /**
         * @brief 초기화
         **/
        function init() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('integration_search');

            $this->target_mid = $config->target_mid;
            if(!$this->target_mid) $this->target_mid = array();

            $this->skin = $config->skin;
            $this->module_info = unserialize($config->skin_vars);
            Context::set('module_info', $this->module_info);

            $this->setTemplatePath($this->module_path."/skins/".$this->skin."/");
        }

        /**
         * @brief 통합 검색 출력
         **/
        function IS() {
            // 검색이 가능한 목록을 구하기 위해 전체 목록을 구해옴
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getMidList($args);

            // 대상 모듈을 정리함
            $module_srl_list = array();
            foreach($module_list as $mid => $val) {
                $mid_list[$val->module_srl] = $val;
                if(in_array($mid, $this->target_mid)) {
                    $module_srl_list[] = $val->module_srl;
                }
            }

            // 검색대상 변수 설정
            $search_target = Context::get('search_target');
            if(!in_array($search_target, array('title','content','title_content','comment','tag'))) $search_target = 'title';

            // 검색어 변수 설정
            $is_keyword = Context::get('is_keyword');

            // 페이지 변수 설정
            $page = (int)Context::get('page');
            if(!$page) $page = 1;


            $list_count = (int)Context::get('list_count');
            if(!$list_count|| $list_count>20) $list_count = 20;

            // 출력할 컨텐츠 추출을 위한 인자 정리
            $args->module_srl = implode(',',$module_srl_list);
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = $search_target;
            $args->search_keyword = Context::get('is_keyword'); 
            $args->sort_index = 'list_order'; 
            $args->order_type = 'asc';

            // 검색글이 있을 경우 검색 시도
            if($args->search_keyword) {

                // 대상 문서들을 가져옴
                $oDocumentModel = &getModel('document');
                $output = $oDocumentModel->getDocumentList($args);
                Context::set('total_count', $output->total_count);
                Context::set('total_page', $output->total_page);
                Context::set('page', $output->page);
                Context::set('document_list', $output->data);
                Context::set('page_navigation', $output->page_navigation);
            }

            // 텍스트 생성
			if(Context::getLangType()=='en')
				$result_text = sprintf(Context::getLang("is_result_text"), $output->total_count, $is_keyword);
			else
				$result_text = sprintf(Context::getLang("is_result_text"), $is_keyword, $output->total_count);
            Context::set('result_text', $result_text);
            Context::set('mid_list', $mid_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }
    }
?>
