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

            // module_srl 값이 없다면 그냥 index 페이지를 보여줌
            if(!Context::get('module_srl')) return $this->dispIssuetrackerAdminContent();

            // 레이아웃이 정해져 있다면 레이아웃 정보를 추가해줌(layout_title, layout)
            if($this->module_info->layout_srl) {
                $oLayoutModel = &getModel('layout');
                $layout_info = $oLayoutModel->getLayout($this->module_info->layout_srl);
                $this->module_info->layout = $layout_info->layout;
                $this->module_info->layout_title = $layout_info->layout_title;
            }

            // 정해진 스킨이 있으면 해당 스킨의 정보를 구함
            if($this->module_info->skin) {
                $oModuleModel = &getModel('module');
                $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $this->module_info->skin);
                $this->module_info->skin_title = $skin_info->title;
            }

            // 템플릿 파일 지정
            $this->setTemplateFile('project_info');
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
            // module_srl을 구함
            $module_srl = Context::get('module_srl');

            // module.xml에서 권한 관련 목록을 구해옴
            $grant_list = $this->xml_info->grant;
            Context::set('grant_list', $grant_list);

            // 권한 그룹의 목록을 가져온다
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            $this->setTemplateFile('grant_list');
        }

        /**
         * @brief 스킨 정보 보여줌
         **/
        function dispIssuetrackerAdminSkinInfo() {

            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $module_info = Context::get('module_info');
            $skin = $module_info->skin;

            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

            // skin_info에 extra_vars 값을 지정
            if(count($skin_info->extra_vars)) {
                foreach($skin_info->extra_vars as $key => $val) {
                    $group = $val->group;
                    $name = $val->name;
                    $type = $val->type;
                    $value = $module_info->{$name};
                    if($type=="checkbox"&&!$value) $value = array();
                    $skin_info->extra_vars[$key]->value= $value;
                }
            }

            Context::set('skin_info', $skin_info);
            $this->setTemplateFile('skin_info');
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

    }
?>
