<?php
    /**
     * @class  wikiAdminView
     * @brief  wiki 모듈의 admin view class
     **/

    class wikiAdminView extends wiki {
        function init() {
            // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
            $module_srl = Context::get('module_srl');
            if(!$module_srl && $this->module_srl) {
                $module_srl = $this->module_srl;
                Context::set('module_srl', $module_srl);
            }

            // module model 객체 생성 
            $oModuleModel = &getModel('module');

            // module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
            if($module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    Context::set('module_srl','');
                    $this->act = 'list';
                } else {
                    ModuleModel::syncModuleToSite($module_info);
                    $this->module_info = $module_info;
                    Context::set('module_info',$module_info);
                }
            }

            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            // 템플릿 경로 지정 (board의 경우 tpl에 관리자용 템플릿 모아놓음)
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

        function dispWikiAdminContent() {
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 20;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = executeQueryArray('wiki.getWikiList', $args);
            ModuleModel::syncModuleToSite($output->data);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('wiki_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

        function dispWikiAdminInsertWiki() {
            if(!in_array($this->module_info->module, array('admin', 'wiki'))) {
                return $this->alertMessage('msg_invalid_request');
            }

            // 스킨 목록을 구해옴
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('wiki_insert');
        }

        function dispWikiAdminDeleteWiki() {
            if(!Context::get('module_srl')) return $this->dispWikiAdminContent();
            if(!in_array($this->module_info->module, array('admin', 'wiki'))) {
                return $this->alertMessage('msg_invalid_request');
            }
            $module_info = Context::get('module_info');

            $oDocumentModel = &getModel('document');
            $document_count = $oDocumentModel->getDocumentCount($module_info->module_srl);
            $module_info->document_count = $document_count;

            Context::set('module_info',$module_info);
            $this->setTemplateFile('wiki_delete');
        }

        function dispWikiAdminWikiAdditionSetup() {
            // content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
            $content = '';

            // 추가 설정을 위한 트리거 호출 
            // 게시판 모듈이지만 차후 다른 모듈에서의 사용도 고려하여 trigger 이름을 공용으로 사용할 수 있도록 하였음
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
            Context::set('setup_content', $content);

            // 템플릿 파일 지정
            $this->setTemplateFile('addition_setup');
        }

        function dispWikiAdminGrantInfo() {
            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
            Context::set('grant_content', $grant_content);

            $this->setTemplateFile('grant_list');
        }

        function dispWikiAdminSkinInfo() {
            // Call the common page for managing skin information
            $oModuleAdminModel = &getAdminModel('module');
            $skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
            Context::set('skin_content', $skin_content);

            $this->setTemplateFile('skin_info');
        }
    }
?>
