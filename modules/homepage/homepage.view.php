<?php
    /**
     * @class  homepageView
     * @author zero (zero@nzeo.com)
     * @brief  homepage 모듈의 view class
     **/

    class homepageView extends homepage {

        var $site_module_info = null;
        var $site_srl = 0;
        var $homepage_info = null;

        function init() {
            $oModuleModel = &getModel('module');
            if(!$oModuleModel->isSiteAdmin()) return $this->stop('msg_not_permitted');

            // site_module_info값으로 홈페이지의 정보를 구함
            $this->site_module_info = Context::get('site_module_info');
            $this->site_srl = $this->site_module_info->site_srl;
            if(!$this->site_srl) exit();

            // 홈페이지 정보를 추출하여 세팅
            $oHomepageModel = &getModel('homepage');
            $this->homepage_info = $oHomepageModel->getHomepageInfo($this->site_srl);
            Context::set('homepage_info', $this->homepage_info);

            // 기본 스킨 디렉토리를 구함
            $template_path = sprintf("%sskins/xe_official",$this->module_path);
            $this->setTemplatePath($template_path);

            // 홈페이지 관리 화면은 별도의 레이아웃으로 설정하여 운영
            $this->setLayoutPath($template_path);
            $this->setLayoutFile('layout');

            // 레이아웃 정보 가져옴
            $oLayoutModel = &getModel('layout');
            $this->selected_layout = $oLayoutModel->getLayout($this->homepage_info->layout_srl);
            Context::set('selected_layout', $this->selected_layout);
        }

        function dispHomepageManage() {
            // 다운로드 되어 있는 레이아웃 목록을 구함
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            // 메뉴 목록을 가져옴
            $oMenuAdminModel = &getAdminModel('menu');
            $menu_list = $oMenuAdminModel->getMenus();
            Context::set('menu_list', $menu_list);

            if(!Context::get('act')) Context::set('act', 'dispHomepageManage');

            $this->setTemplateFile('index');
        }

        function dispHomepageMemberGroupManage() {
            // 멤버모델 객체 생성
            $oMemberModel = &getModel('member');

            // group_srl이 있으면 미리 체크하여 selected_group 세팅
            $group_srl = Context::get('group_srl');
            if($group_srl) {
                $selected_group = $oMemberModel->getGroup($group_srl);
                Context::set('selected_group',$selected_group);
            }

            // group 목록 가져오기
            $this->group_list = $oMemberModel->getGroups($this->site_srl);
            Context::set('group_list', $this->group_list);

            $this->setTemplateFile('group_list');
        }

        function dispHomepageTopMenu() {
            // 메뉴 정보 가져오기
            $menu_srl = $this->homepage_info->first_menu_srl;
            $oMenuModel = &getAdminModel('menu');
            $menu_info = $oMenuModel->getMenu($menu_srl);
            Context::set('menu_info', $menu_info);

            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            $_menu_info = get_object_vars($this->selected_layout->menu);
            $menu = array_shift($_menu_info);
            Context::set('menu_max_depth', $menu->maxdepth);

            $this->setTemplateFile('menu_manage');
        }

        function dispHomepageBottomMenu() {
            // 메뉴 정보 가져오기
            $menu_srl = $this->homepage_info->second_menu_srl;
            $oMenuModel = &getAdminModel('menu');
            $menu_info = $oMenuModel->getMenu($menu_srl);
            Context::set('menu_info', $menu_info);

            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            $_menu_info = get_object_vars($this->selected_layout->menu);
            $menu = array_pop($_menu_info);
            Context::set('menu_max_depth', $menu->maxdepth);

            $this->setTemplateFile('menu_manage');
        }

        function dispHomepageMidSetup() {
            // 현재 site_srl 에 등록된 것들을 가져오기 
            $args->site_srl = $this->site_srl;
            $oModuleModel = &getModel('module');
            $mid_list = $oModuleModel->getMidList($args);
            Context::set('mid_list', $mid_list);

            $this->setTemplateFile('mid_list');
        }

        function dispHomepageBoardInfo() {
            $module_srl = Context::get('module_srl');
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$module_info || $module_info->site_srl != $this->site_srl) return new Object(-1,'msg_invalid_request');
            Context::set('module_info', $module_info);

            // 스킨 목록을 구해옴
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            // 템플릿 파일 지정
            if($module_info->module == 'board') {
                $oBoardAdminView = &getAdminView('board');
                $oBoardAdminView->init();
                $this->setTemplateFile('board_insert');
            } else {
                $oMemberModel = &getModel('member');
                $group_list = $oMemberModel->getGroups($this->site_srl);
                Context::set('group_list', $group_list);

                $xml_info = $oModuleModel->getModuleActionXml('page');
                $grant_list = $xml_info->grant;
                Context::set('grant_list', $grant_list);

                $oPage = &getClass('page');
                $this->setTemplateFile('page_insert');
            }
        }

        function dispHomepageBoardAddition() {
            $module_srl = Context::get('module_srl');
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$module_info || $module_info->site_srl != $this->site_srl) return new Object(-1,'msg_invalid_request');
            Context::set('module_info', $module_info);
            // content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
            $content = '';

            // 추가 설정을 위한 트리거 호출 
            // 게시판 모듈이지만 차후 다른 모듈에서의 사용도 고려하여 trigger 이름을 공용으로 사용할 수 있도록 하였음
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
            Context::set('setup_content', $content);

            // 템플릿 파일 지정
            $this->setTemplateFile('board_addition_setup');
        }

        function dispHomepageBoardGrant() {
            $module_srl = Context::get('module_srl');
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$module_info || $module_info->site_srl != $this->site_srl) return new Object(-1,'msg_invalid_request');
            Context::set('module_info', $module_info);

            $xml_info = $oModuleModel->getModuleActionXml('board');
            $grant_list = $xml_info->grant;
            Context::set('grant_list', $grant_list);

            // 권한 그룹의 목록을 가져온다
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups($this->site_srl);
            Context::set('group_list', $group_list);

            $this->setTemplateFile('board_grant_list');
        }

        function dispHomepageBoardSkin() {
            $module_srl = Context::get('module_srl');
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$module_info || $module_info->site_srl != $this->site_srl) return new Object(-1,'msg_invalid_request');
            Context::set('module_info', $module_info);
            $skin = $module_info->skin;

            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo('./modules/board/', $skin);
            if(!$skin_info) {
                $skin = 'xe_board';
                $skin_info = $oModuleModel->loadSkinInfo('./modules/board/', $skin);
            }

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
            $this->setTemplateFile('board_skin_info');
        }

        function dispHomepageMemberManage() {
            // member model 객체 생성후 목록을 구해옴
            $oMemberAdminModel = &getAdminModel('member');
            $oMemberModel = &getModel('member');
            $output = $oMemberAdminModel->getSiteMemberList($this->site_srl);

            $members = array();
            foreach($output->data as $key=>$val) {
                $members[] = $val->member_srl;
            }
            $members_groups = $oMemberModel->getMembersGroups($members, $this->site_srl);
            Context::set('members_groups',$members_groups);

            $group_list = $oMemberModel->getGroups($this->site_srl);
            Context::set('group_list', $group_list);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('member_list');
        }
    }

?>
