<?php
    /**
     * @class  pageAdminView
     * @author zero (zero@nzeo.com)
     * @brief  page 모듈의 admin view 클래스
     **/

    class pageAdminView extends page {

        var $module_srl = 0;
        var $list_count = 20;
        var $page_count = 10;

        /**
         * @brief 초기화
         **/
        function init() {
            // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
            $module_srl = Context::get('module_srl');

            // module model 객체 생성 
            $oModuleModel = &getModel('module');

            // 모듈 카테고리 목록을 구함
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            // 템플릿 경로 구함 (page의 경우 tpl에 관리자용 템플릿 모아놓음)
            $this->setTemplatePath($this->module_path.'tpl');

            // 권한 그룹의 목록을 가져온다
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            // module.xml에서 권한 관련 목록을 구해옴
            $grant_list = $this->xml_info->grant;
            Context::set('grant_list', $grant_list);
        }

        /**
         * @brief 페이지 관리 목록 보여줌
         **/
        function dispPageAdminContent() {
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = executeQuery('page.getPageList', $args);

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
            if(!$module_srl) return $this->dispPageAdminContent();

            // 레이아웃이 정해져 있다면 레이아웃 정보를 추가해줌(layout_title, layout)
            if($module_info->layout_srl) {
                $oLayoutModel = &getModel('layout');
                $layout_info = $oLayoutModel->getLayout($module_info->layout_srl);
                $module_info->layout = $layout_info->layout;
                $module_info->layout_title = $layout_info->layout_title;
            }

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);


            // 템플릿 파일 지정
            $this->setTemplateFile('page_info');
        }

        /**
         * @brief 페이지 추가 폼 출력
         **/
        function dispPageAdminInsert() {

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
            if(!$module_srl) $module_srl = getNextSequence();
            Context::set('module_srl',$module_srl);

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);


            // 템플릿 파일 지정
            $this->setTemplateFile('page_insert');
        }

        /**
         * @brief 페이지 내용 수정
         **/
        function dispPageAdminContentModify() {

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
            if(!$module_srl) $module_srl = getNextSequence();
            Context::set('module_srl',$module_srl);

            // 위젯 목록을 세팅
            $oWidgetModel = &getModel('widget');
            $widget_list = $oWidgetModel->getDownloadedWidgetList();
            Context::set('widget_list', $widget_list);

            // 에디터 모듈의 getEditor를 호출하여 세팅
            $oEditorModel = &getModel('editor');
            $option->primary_key_name = 'module_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = true;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = true;
            $option->height = 600;
            $editor = $oEditorModel->getEditor($module_srl, $option);
            Context::set('editor', $editor);

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('page_content_modify');
        }


        /**
         * @brief 페이지 삭제 화면 출력
         **/
        function dispPageAdminDelete() {
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
