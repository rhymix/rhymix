<?php
    /**
     * @class  pageView
     * @author zero (zero@nzeo.com)
     * @brief  page 모듈의 view 클래스
     **/

    class pageView extends page {

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
         * @brief 관리자 기능 호출시에 관련 정보들 세팅해줌
         **/
        function initAdmin() {
            // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
            $module_srl = Context::get('module_srl');

            // module model 객체 생성 
            $oModuleModel = &getModel('module');

            // 모듈 카테고리 목록을 구함
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            // 템플릿 경로 구함 (page의 경우 tpl.admin에 관리자용 템플릿 모아놓음)
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 일반 요청시 출력
         **/
        function dispPageIndex() {
            // 템플릿에서 사용할 변수를 Context::set()
            if($this->module_srl) Context::set('module_srl',$this->module_srl);

            Context::set('module_info', $this->module_info);
            Context::set('page_content', $this->module_info->content);

            $this->setTemplatePath($this->module_path.'tpl.admin');
            $this->setTemplateFile('content');
        }

        /**
         * @brief 페이지 관리 목록 보여줌
         **/
        function dispPageAdminContent() {
            // 모듈 관련 정보 세팅
            $this->initAdmin();

            // 등록된 page 모듈을 불러와 세팅
            $oDB = &DB::getInstance();
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = $oDB->executeQuery('page.getPageList', $args);

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
        function dispPageAdminModuleConfig() {
            // 관리자  관련 정보 세팅
            $this->initAdmin();

            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('page');
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplateFile('page_config');
        }

        /**
         * @brief 선택된 페이지의 정보 출력
         **/
        function dispPageAdminInfo() {
            // 관리자  관련 정보 세팅
            $this->initAdmin();

            // GET parameter에서 module_srl을 가져옴
            $module_srl = Context::get('module_srl');

            // module model 객체 생성 
            if($module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if($module_info->module_srl == $module_srl) Context::set('module_info',$module_info);
                else {
                    unset($module_info);
                    unset($module_srl);
                }
            }

            // module_srl 값이 없다면 그냥 index 페이지를 보여줌
            if(!$module_srl) return $this->dispAdminContent();

            // 레이아웃이 정해져 있다면 레이아웃 정보를 추가해줌(layout_title, layout)
            if($module_info->layout_srl) {
                $oLayoutModel = &getModel('layout');
                $layout_info = $oLayoutModel->getLayout($module_info->layout_srl);
                $module_info->layout = $layout_info->layout;
                $module_info->layout_title = $layout_info->layout_title;
            }

            // 템플릿 파일 지정
            $this->setTemplateFile('page_info');
        }

        /**
         * @brief 페이지 추가 폼 출력
         **/
        function dispPageAdminInsert() {
            // 관리자  관련 정보 세팅
            $this->initAdmin();

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);

            // GET parameter에서 module_srl을 가져옴
            $module_srl = Context::get('module_srl');

            // module_srl이 있으면 해당 모듈의 정보를 구해서 세팅
            if($module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if($module_info->module_srl == $module_srl) Context::set('module_info',$module_info);
                else {
                    unset($module_info);
                    unset($module_srl);
                }
            }

            // module_srl이 없으면 sequence값으로 미리 구해 놓음
            if(!$module_srl) {
                $oDB = &DB::getInstance();
                $module_srl = $oDB->getNextSequence();
            }
            Context::set('module_srl',$module_srl);

            // 플러그인 목록을 세팅
            $oPluginModel = &getModel('plugin');
            $plugin_list = $oPluginModel->getDownloadedPluginList();
            Context::set('plugin_list', $plugin_list);

            // 에디터 모듈의 getEditor를 호출하여 세팅
            $oEditorModel = &getModel('editor');
            $editor = $oEditorModel->getEditor($module_srl, true);
            Context::set('editor', $editor);

            // 템플릿 파일 지정
            $this->setTemplateFile('page_insert');
        }

        /**
         * @brief 페이지 삭제 화면 출력
         **/
        function dispPageAdminDelete() {
            // 관리자  관련 정보 세팅
            $this->initAdmin();

            $module_srl = Context::get('module_srl');
            if(!$module_srl) return $this->dispContent();

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            Context::set('module_info',$module_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('page_delete');
        }

    }
?>
