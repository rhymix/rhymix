<?php
    /**
     * @class  pagemakerView
     * @author zero (zero@nzeo.com)
     * @brief  pagemaker 모듈의 view 클래스
     **/

    class pagemakerView extends pagemaker {

        var $module_srl = 0;
        var $list_count = 20;
        var $page_count = 10;

        /**
         * @brief 초기화
         **/
        function init() {
            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 페이지 관리 목록 보여줌
         **/
        function dispAdminContent() {
            // 등록된 page 모듈을 불러와 세팅
            $oDB = &DB::getInstance();
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = $oDB->executeQuery('pagemaker.getPageList', $args);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

        /**
         * @brief 페이지에 필요한 기본 설정들
         **/
        function dispAdminModuleConfig() {
            // 관리자  관련 정보 세팅
            $this->initAdmin();

            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('page');
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplateFile('page_config');
        }



    }
?>
