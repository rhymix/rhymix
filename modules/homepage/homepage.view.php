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

        /**
         * @brief 초기화 
         **/
        function init() {
            $oModuleModel = &getModel('module');

            if($this->act != 'dispHomepageIndex' && strpos($this->act,'Homepage')!==false) {
                // 현재 접속 권한 체크하여 사이트 관리자가 아니면 접근 금지
                $logged_info = Context::get('logged_info');
                if(!Context::get('is_logged') || !$oModuleModel->isSiteAdmin($logged_info)) return $this->stop('msg_not_permitted');

                // site_module_info값으로 홈페이지의 정보를 구함
                $this->site_module_info = Context::get('site_module_info');
                $this->site_srl = $this->site_module_info->site_srl;
                if(!$this->site_srl) return $this->stop('msg_invalid_request');

                // 홈페이지 정보를 추출하여 세팅
                $oHomepageModel = &getModel('homepage');
                $this->homepage_info = $oHomepageModel->getHomepageInfo($this->site_srl);
                Context::set('homepage_info', $this->homepage_info);

                // 템플릿 디렉토리를 구함
                $template_path = sprintf("%stpl",$this->module_path);
                $this->setTemplatePath($template_path);

                // 모듈 번호가 있으면 해동 모듈의 정보를 구해와서 세팅
                $module_srl = Context::get('module_srl');
                if($module_srl) {
                    $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                    if(!$module_info || $module_info->site_srl != $this->site_srl) return new Object(-1,'msg_invalid_request');
                    $this->module_info = $module_info;
                    Context::set('module_info', $module_info);
                }
            }
        }

        /**
         * @brief 카페 메인 출력
         **/
        function dispHomepageIndex() {
            $oHomepageAdminModel = &getAdminModel('homepage');
            $oHomepageModel = &getModel('homepage');
            $oModuleModel = &getModel('module');

            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'xe_default';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }
            $this->setTemplatePath($template_path);

            // 카페 목록을 구함
            $page = Context::get('page');
            $output = $oHomepageAdminModel->getHomepageList($page);
            if($output->data && count($output->data)) {
                foreach($output->data as $key => $val) {
                    $banner_src = 'files/attach/cafe_banner/'.$val->site_srl.'.jpg';
                    if(file_exists(_XE_PATH_.$banner_src)) $output->data[$key]->cafe_banner = $banner_src.'?rnd='.filemtime(_XE_PATH_.$banner_src);

                    $url = getSiteUrl($val->domain,'');
                    if(substr($url,0,1)=='/') $url = substr(Context::getRequestUri(),0,-1).$url;
                    $output->data[$key]->url = $url;
                }
            }
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('homepage_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 카페 생성 권한 세팅
            if($oHomepageModel->isCreationGranted()) {
                Context::set('isEnableCreateCafe', true);
                Context::addJsFilter($this->module_path.'tpl/filter', 'cafe_creation.xml');
            }

            // 카페의 최신 글 추출
            $output = executeQueryArray('homepage.getNewestDocuments');
            Context::set('newest_documents', $output->data);
            
            // 카페의 최신 댓글 추출
            $output = executeQueryArray('homepage.getNewestComments');
            Context::set('newest_comments', $output->data);

            $logged_info = Context::get('logged_info');
            if($logged_info->member_srl) {
                $myargs->member_srl = $logged_info->member_srl;
                $output = executeQueryArray('homepage.getMyCafes', $myargs);
                Context::set('my_cafes', $output->data);
            }

            $homepage_info = $oModuleModel->getModuleConfig('homepage');
            if($homepage_info->use_rss == 'Y') Context::set('rss_url',getUrl('','mid',$this->module_info->mid,'act','rss'));

            $this->setTemplateFile('index');
        }

        /**
         * @brief 홈페이지 기본 관리
         **/
        function dispHomepageManage() {
            $oModuleModel = &getModel('module');
            $oMenuAdminModel = &getAdminModel('menu');
            $oLayoutModel = &getModel('layout');
            $oHomepageModel = &getModel('homepage');

            $homepage_config = $oHomepageModel->getConfig($this->site_srl);
            Context::set('homepage_config', $homepage_config);

            // 다운로드 되어 있는 레이아웃 목록을 구함
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            // 레이아웃 정보 가져옴
            $this->selected_layout = $oLayoutModel->getLayout($this->homepage_info->layout_srl);
            Context::set('selected_layout', $this->selected_layout);

            // 메뉴 목록을 가져옴
            $menu_list = $oMenuAdminModel->getMenus();
            Context::set('menu_list', $menu_list);

            if(!Context::get('act')) Context::set('act', 'dispHomepageManage');

            $args->site_srl = $this->site_srl;
            $mid_list = $oModuleModel->getMidList($args);
            Context::set('mid_list', $mid_list);

            $this->setTemplateFile('layout_setup');
        }

        /**
         * @brief 홈페이지 회원 그룹 관리
         **/
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
            $group_list = $oMemberModel->getGroups($this->site_srl);
            Context::set('group_list', $group_list);

            $this->setTemplateFile('group_list');
        }

        /**
         * @brief 홈페이지 모듈의 회원 관리
         **/
        function dispHomepageMemberManage() {
            // member model 객체 생성후 목록을 구해옴
            $oMemberAdminModel = &getAdminModel('member');
            $oMemberModel = &getModel('member');
            $output = $oMemberAdminModel->getSiteMemberList($this->site_srl,Context::get('page'));

            $members = array();
            if(count($output->data)) {
                foreach($output->data as $key=>$val) {
                    $members[] = $val->member_srl;
                }
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


        /**
         * @brief 홈페이지 상단 메뉴 관리
         **/
        function dispHomepageTopMenu() {
            $oMemberModel = &getModel('member');
            $oMenuModel = &getAdminModel('menu');
            $oModuleModel = &getModel('module');
            $oLayoutModel = &getModel('layout');
            $oHomepageModel = &getModel('homepage');

            // 홈페이지 정보
            $homepage_config = $oHomepageModel->getConfig($this->site_srl);
            if(count($homepage_config->allow_service)) {
                foreach($homepage_config->allow_service as $k => $v) {
                    if($v<1) continue;
                    $c = $oModuleModel->getModuleCount($this->site_srl, $k);
                    $homepage_config->allow_service[$k] -= $c;
                }
            }
            Context::set('homepage_config', $homepage_config);

            // 메뉴 정보 가져오기
            $menu_srl = $this->homepage_info->first_menu_srl;

            $menu_info = $oMenuModel->getMenu($menu_srl);
            Context::set('menu_info', $menu_info);

            $group_list = $oMemberModel->getGroups($this->site_srl);
            Context::set('group_list', $group_list);

            $selected_layout = $oLayoutModel->getLayout($this->homepage_info->layout_srl);

            $_menu_info = get_object_vars($selected_layout->menu);
            $menu = array_shift($_menu_info);
            Context::set('menu_max_depth', $menu->maxdepth);

            $this->setTemplateFile('menu_manage');
        }

        /**
         * @brief 홈페이지 모듈 목록
         **/
        function dispHomepageMidSetup() {
            // 현재 site_srl 에 등록된 것들을 가져오기 
            $args->site_srl = $this->site_srl;
            $oModuleModel = &getModel('module');
            $mid_list = $oModuleModel->getMidList($args);

            $installed_module_list = $oModuleModel->getModulesXmlInfo();
            foreach($installed_module_list as $key => $val) {
                if($val->category != 'service') continue;
                $service_modules[$val->module] = $val;
            }

            if(count($mid_list)) {
                foreach($mid_list as $key => $val) {
                    $mid_list[$key]->setup_index_act = $service_modules[$val->module]->setup_index_act;
                }
            }
            Context::set('mid_list', $mid_list);

            $this->setTemplateFile('mid_list');
        }

        /**
         * @brief 홈페이지 게시판 정보
         **/
        function dispHomepageBoardInfo() {
            // 스킨 목록을 구해옴
            $oModuleModel = &getModel('module');
            $skin_path = sprintf('./modules/%s', $this->module_info->module);
            $skin_list = $oModuleModel->getSkins($skin_path);
            Context::set('skin_list',$skin_list);

            $oBoardAdminView = &getAdminView('board');
            $oBoardAdminView->init();

            Context::set('module_info', $this->module_info);
            $this->setTemplateFile('board_insert');
        }

        /**
         * @brief 홈페이지 모듈의 게시판 분류
         **/
        function dispHomepageBoardCategoryInfo() {
            $oDocumentModel = &getModel('document');
            $catgegory_content = $oDocumentModel->getCategoryHTML($this->module_info->module_srl);
            Context::set('category_content', $catgegory_content);

            Context::set('module_info', $this->module_info);
            $this->setTemplateFile('category_list');
        }

        /**
         * @brief 홈페이지 게시판 추가 설정
         **/
        function dispHomepageBoardAddition() {
            $oModuleModel = &getModel('module');
            Context::set('module_info', $this->module_info);

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

        /**
         * @brief 홈페이지 게시판 권한 설정
         **/
        function dispHomepageBoardGrant() {
            $oModuleModel = &getModel('module');
            $xml_info = $oModuleModel->getModuleActionXml('board');

            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $xml_info->grant);
            Context::set('grant_content', $grant_content);

            $this->setTemplateFile('board_grant_list');
        }

        /**
         * @breif 홈페이지 게시판 스킨 설정
         **/
        function dispHomepageBoardSkin() {
            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
            Context::set('skin_content', $skin_content);

            $this->setTemplateFile('board_skin_info');
        }

        /**
         * @brief 홈페이지 모듈의 페이지 정보 
         **/
        function dispHomepagePageGrant() {
            Context::set('module_info', $this->module_info);

            $oModuleModel = &getModel('module');
            $xml_info = $oModuleModel->getModuleActionXml('page');

            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $xml_info->grant);
            Context::set('grant_content', $grant_content);

            $this->setTemplateFile('page_grant_list');
        }

        /**
         * @brief 홈페이지 모듈의 확장 변수 
         **/
        function dispHomepageBoardExtraVars() {
            $oDocumentAdminModel = &getModel('document');
            $extra_vars_content = $oDocumentAdminModel->getExtraVarsHTML($this->module_info->module_srl);
            Context::set('extra_vars_content', $extra_vars_content);

            $this->setTemplateFile('extra_vars');
        }

        /**
         * @brief 접속 통계
         **/
        function dispHomepageCounter() {
            // 정해진 일자가 없으면 오늘자로 설정
            $selected_date = Context::get('selected_date');
            if(!$selected_date) $selected_date = date("Ymd");
            Context::set('selected_date', $selected_date);

            // counter model 객체 생성
            $oCounterModel = &getModel('counter');

            // 전체 카운터 및 지정된 일자의 현황 가져오기
            $status = $oCounterModel->getStatus(array(0,$selected_date),$this->site_srl);
            Context::set('total_counter', $status[0]);
            Context::set('selected_day_counter', $status[$selected_date]);

            // 시간, 일, 월, 년도별로 데이터 가져오기
            $type = Context::get('type');
            if(!$type) {
                $type = 'day';
                Context::set('type',$type);
            }
            $detail_status = $oCounterModel->getHourlyStatus($type, $selected_date, $this->site_srl);
            Context::set('detail_status', $detail_status);
            
            // 표시
            $this->setTemplateFile('site_status');
        }

        /**
         * @brief 애드온/ 컴포넌트 설정
         **/
        function dispHomepageComponent() {
            // 애드온 목록을 가져옴
            $oAddonModel = &getAdminModel('addon');
            $addon_list = $oAddonModel->getAddonList($this->site_srl);
            Context::set('addon_list', $addon_list);

            // 에디터 컴포넌트 목록을 가져옴
            $oEditorModel = &getModel('editor');
            Context::set('component_list', $oEditorModel->getComponentList(false, $this->site_srl));

            // 표시
            $this->setTemplateFile('components');
        }

        /**
         * @brief rss
         **/
        function rss() {
            $oRss = &getView('rss');
            $oDocumentModel = &getModel('document');
            $oModuleModel = &getModel('module');

            $homepage_info = $oModuleModel->getModuleConfig('homepage');
            if($homepage_info->use_rss != 'Y') return new Object(-1,'msg_rss_is_disabled');

            $output = executeQueryArray('homepage.getRssList', $args);
            if($output->data) {
                foreach($output->data as $key => $val) {
                    unset($obj);
                    $obj = new DocumentItem(0);
                    $obj->setAttribute($val);
                    $document_list[] = $obj;
                }
            }

            $oRss->rss($document_list, $homepage_info->browser_title);
            $this->setTemplatePath($oRss->getTemplatePath());
            $this->setTemplateFile($oRss->getTemplateFile());
        }
    }
?>
