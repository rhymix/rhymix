<?php
    /**
     * @class  issuetrackerAdminView
     * @author zero (zero@nzeo.com)
     * @brief  issuetracker 모듈의 admin view class
     **/

    class issuetrackerAdminView extends issuetracker {

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
                    $this->module_info = $module_info;
                    Context::set('module_info',$module_info);
                }
            }

            // 모듈 카테고리 목록을 구함
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

        /**
         * @brief 프로젝트 관리 목록 보여줌
         **/
        function dispIssuetrackerAdminContent() {
            // 등록된 board 모듈을 불러와 세팅
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = executeQuery('issuetracker.getProjectList', $args);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('project_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

        function dispIssuetrackerAdminInsertProject() {
            // 스킨 목록 구해옴
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('project_insert');
        }

        function dispIssuetrackerAdminModifyMilestone() {
            if(!Context::get('milestone_srl')) return $this->dispIssuetrackerAdminContent();

            $milestone_srl = Context::get('milestone_srl');
            $oModel = &getModel('issuetracker');
            $output = $oModel->getMilestone($milestone_srl);

            $milestone = $output->data;
            Context::set('milestone', $milestone);
            $this->setTemplateFile('modify_milestone');
        }

        function dispIssuetrackerAdminModifyPriority() {
            if(!Context::get('priority_srl')) return $this->dispIssuetrackerAdminContent();

            $priority_srl = Context::get('priority_srl');
            $oModel = &getModel('issuetracker');
            $output = $oModel->getPriority($priority_srl);

            $priority = $output->data;
            Context::set('priority', $priority);
            $this->setTemplateFile('modify_priority');
        }

        function dispIssuetrackerAdminModifyType() {
            if(!Context::get('type_srl')) return $this->dispIssuetrackerAdminContent();

            $type_srl = Context::get('type_srl');
            $oModel = &getModel('issuetracker');
            $output = $oModel->getType($type_srl);

            $type = $output->data;
            Context::set('type', $type);
            $this->setTemplateFile('modify_type');
        }

        function dispIssuetrackerAdminModifyComponent() {
            if(!Context::get('component_srl')) return $this->dispIssuetrackerAdminContent();

            $component_srl = Context::get('component_srl');
            $oModel = &getModel('issuetracker');
            $output = $oModel->getComponent($component_srl);

            $component = $output->data;
            Context::set('component', $component);
            $this->setTemplateFile('modify_component');
        }

        function dispIssuetrackerAdminModifyPackage() {
            $package_srl = Context::get('package_srl');
            if($package_srl) {
                $oModel = &getModel('issuetracker');
                $package = $oModel->getPackage($package_srl);
                Context::set('package', $package);
            }
            $this->setTemplateFile('modify_package');
        }

        function dispIssuetrackerAdminModifyRelease() {
            $release_srl = Context::get('release_srl');
            if($release_srl) {
                $oModel = &getModel('issuetracker');
                $release = $oModel->getRelease($release_srl);
                Context::set('release', $release);
            }
            $this->setTemplateFile('modify_release');
        }

        function dispIssuetrackerAdminAttachRelease() {
            if(!Context::get('release_srl')) return $this->dispIssuetrackerAdminContent();

            $release_srl = Context::get('release_srl');
            $oModel = &getModel('issuetracker');
            $release = $oModel->getRelease($release_srl);
            Context::set('release', $release);
            $this->setTemplateFile('attach_release');
        }

        function dispIssuetrackerAdminProjectSetting() {

            if(!Context::get('module_srl')) return $this->dispIssuetrackerAdminContent();

            $module_srl = Context::get('module_srl');

            // priority
            $oIssuetrackerModel = &getModel('issuetracker');
            $priority_list = $oIssuetrackerModel->getList($module_srl, "Priorities");
            Context::set('priority_list', $priority_list);

            // component
            $component_list = $oIssuetrackerModel->getList($module_srl, "Components");
            Context::set('component_list', $component_list);

            // milestone
            $milestone_list = $oIssuetrackerModel->getList($module_srl, "Milestones");
            Context::set('milestone_list', $milestone_list);
            
            // display option
            $oModuleModel = &getModel('module');
            $module_config = $oModuleModel->getModulePartConfig('issuetracker',$this->module_srl);
            if($module_config) $this->default_enable = $module_config->display_option;

            // 템플릿에서 사용할 노출옵션 세팅
            foreach($this->display_option as $opt) {
                $obj = null;
                $obj->title = Context::getLang($opt);
                $checked = Context::get('d_'.$opt);
                if($opt == 'title' || $checked==1 || (Context::get('d')!=1&&in_array($opt,$this->default_enable))) $obj->checked = true;
                $display_option[$opt] = $obj;
            }
            
            Context::set('display_option', $display_option);

            // type
            $type_list = $oIssuetrackerModel->getList($module_srl, "Types");
            Context::set('type_list', $type_list);
            $this->setTemplateFile('project_setting');
        }

        function dispIssuetrackerAdminReleaseSetting() {

            if(!Context::get('module_srl')) return $this->dispIssuetrackerAdminContent();

            $module_srl = Context::get('module_srl');
            $package_srl = Context::get('package_srl');

            $oIssuetrackerModel = &getModel('issuetracker');
            $package_list = $oIssuetrackerModel->getPackageList($module_srl);

            if($package_srl) {
                $release_list = $oIssuetrackerModel->getReleaseList($package_srl);
                if($release_list) $package_list[$package_srl]->releases = $release_list;
            }

            Context::set('package_list', $package_list);

            $this->setTemplateFile('release_setting');
        }

        function dispIssuetrackerAdminProjectInfo() {
            $this->dispIssuetrackerAdminInsertProject();
        }

        function dispIssuetrackerAdminAdditionSetup() {
            $content = '';

            // 추가 설정을 위한 트리거 호출 
            // 이슈트래커 모듈이지만 차후 다른 모듈에서의 사용도 고려하여 trigger 이름을 공용으로 사용할 수 있도록 하였음
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
            Context::set('setup_content', $content);

            // 템플릿 파일 지정
            $this->setTemplateFile('addition_setup');
        }

        /**
         * @brief 권한 목록 출력
         **/
        function dispIssuetrackerAdminGrantInfo() {
            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
            Context::set('grant_content', $grant_content);

            $this->setTemplateFile('grant_list');
        }

        /**
         * @brief 이슈트래커 삭제 화면 출력
         **/
        function dispIssuetrackerAdminDeleteIssuetracker() {

            if(!Context::get('module_srl')) return $this->dispIssuetrackerAdminContent();

            $module_info = Context::get('module_info');

            $oDocumentModel = &getModel('document');
            $document_count = $oDocumentModel->getDocumentCount($module_info->module_srl);
            $module_info->document_count = $document_count;

            Context::set('module_info',$module_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('issuetracker_delete');
        }

        function dispIssuetrackerAdminManageDocument() {
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

            $module_srl = $this->module_info->module_srl;
            Context::set('module_srl', $module_srl);

            $oIssuetrackerModel = &getModel('issuetracker');
            $project = null;
            $project->priorities = $oIssuetrackerModel->getList($module_srl, "Priorities");
            $project->components = $oIssuetrackerModel->getList($module_srl, "Components");
            $project->milestones = $oIssuetrackerModel->getList($module_srl, "Milestones");
            $project->types = $oIssuetrackerModel->getList($module_srl, "Types");
            Context::set('project', $project);

            // 팝업 레이아웃 선택
            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('popup_layout');

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('checked_list');
        }

        /**
         * @brief 확장 변수 설정
         **/
        function dispIssuetrackerAdminExtraVars() {
            $oDocumentModel = &getModel('document');
            $extra_vars_content = $oDocumentModel->getExtraVarsHTML($this->module_info->module_srl);
            Context::set('extra_vars_content', $extra_vars_content);

            $this->setTemplateFile('extra_vars');
        }

        /**
         * @brief 스킨 정보 보여줌
         **/
        function dispIssuetrackerAdminSkinInfo() {
            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
            Context::set('skin_content', $skin_content);

            $this->setTemplateFile('skin_info');
        }

    }
?>
