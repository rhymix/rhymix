<?php
    /**
     * @class  homepageAdminController
     * @author zero (zero@nzeo.com)
     * @brief  homepage 모듈의 admin controller class
     **/

    class homepageAdminController extends homepage {

        function init() {
        }

        /**
         * @brief 카페 설정
         **/
        function procHomepageAdminInsertConfig() {
            global $lang;

            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');
            $oHomepageModel = &getModel('homepage');

            $vars = Context::getRequestVars();

            $args->default_layout = $vars->default_layout;
            $args->enable_change_layout = $vars->enable_change_layout;
            $args->use_rss = $vars->use_rss;
            foreach($vars as $key => $val) {
                if(strpos($key,'allow_service_')===false) continue;
                $args->allow_service[substr($key, strlen('allow_service_'))] = $val;
            }
            if($vars->site_srl) {
                unset($vars->creation_group);
                unset($vars->cafe_main_mid);
                unset($vars->skin);
                $oModuleController->insertModulePartConfig('homepage', $vars->site_srl, $args);
            }else {
                $args->access_type = $vars->access_type;
                $args->default_domain = $vars->default_domain;
                if(strpos($args->default_domain,':')===false) $args->default_domain = 'http://'.$args->default_domain;
                if(substr($args->default_domain,-1)!='/') $args->default_domain .= '/';
                if($args->access_type != 'vid' && !$args->default_domain) return new Object(-1,sprintf($lang->filter->isnull, $lang->domain));

                $args->cafe_main_mid = $vars->cafe_main_mid;
                $args->browser_title = $vars->browser_title;
                if(!$args->browser_title) $args->browser_title = 'cafeXE';
                if(!$args->cafe_main_mid) return new Object(-1,sprintf($lang->filter->isnull,$lang->cafe_main_mid));
                $args->skin = $vars->skin;
                if(!$args->skin) $args->skin = 'xe_default';

                $homepage_config = $oHomepageModel->getConfig(0);
                $mid = $homepage_config->cafe_main_mid;
                $module_info = $oModuleModel->getModuleInfoByMid($mid, 0);
                if(!$module_info->module_srl) {
                    $module_args->site_srl = 0;
                    $module_args->mid = $args->cafe_main_mid;
                    $module_args->skin = $args->skin;
                    $module_args->browser_title = $args->browser_title;
                    $module_args->module = 'homepage';
                    $module_args->layout_srl = $vars->layout_srl;
                    $output = $oModuleController->insertModule($module_args);
                    if(!$output->toBool()) return $output;
                } else {
                    $module_args->module = 'homepage';
                    $module_args->mid = $args->cafe_main_mid;
                    $module_args->skin = $args->skin;
                    $module_args->site_srl = 0;
                    $module_args->browser_title = $args->browser_title;
                    $module_args->module_srl = $module_info->module_srl;
                    $module_args->layout_srl = $vars->layout_srl;
                    $output = $oModuleController->updateModule($module_args);
                    if(!$output->toBool()) return $output;
                }

                $module_info = $oModuleModel->getModuleInfoByMid($mid, 0);
                $args->module_srl = $module_info->module_srl;
                $args->creation_group = implode(',',explode('|@|',$vars->creation_group));
                $args->layout_srl = $vars->layout_srl;
                $oModuleController->insertModuleConfig('homepage', $args);
            }
        }

        /**
         * @brief 접속 방법중 domain 이나 site id나 모두 sites 테이블의 domain 컬럼에 저장이 됨
         * site id보다 domain이 우선 순위를 가짐
         **/
        function procHomepageAdminInsertHomepage() {
            $title = Context::get('title');

            $domain = preg_replace('/^(http|https):\/\//i','', trim(Context::get('domain')));
            $vid = trim(Context::get('site_id'));

            if($domain && $vid) unset($vid);
            if(!$domain && $vid) $domain = $vid;

            if(!$title) return new Object(-1, 'msg_invalid_request');
            if(!$domain) return new Object(-1, 'msg_invalid_request');

            $output = $this->insertHomepage($title, $domain);
            return $output;
        }

        function insertHomepage($title, $domain) {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            $info->title = $title;
            $info->domain = $domain;

            // 언어 코드 추출
            $files = FileHandler::readDir('./modules/homepage/lang');
            foreach($files as $filename) {
                $lang_code = str_replace('.lang.php', '', $filename);
                $lang = null;
                @include('./modules/homepage/lang/'.$filename);
                if(count($lang->default_menus)) {
                    foreach($lang->default_menus as $key => $val) {
                        $defined_lang[$lang_code]->{$key} = $val;
                    }
                }
            }
            $lang = null;

            // virtual site 생성하고 site_srl을 보관
            $output = $oModuleController->insertSite($domain, 0);
            if(!$output->toBool()) return $output;
            $info->site_srl = $output->get('site_srl');

            // 언어 코드 등록 (홈, 공지사항, 등업신청, 자유게시판, 전체 글 보기, 한줄이야기, 카페앨범, 메뉴등)
            foreach($defined_lang as $lang_code => $v) {
                foreach($v as $key => $val) {
                    unset($lang_args);
                    $lang_args->site_srl = $info->site_srl;
                    $lang_args->name = $key;
                    $lang_args->lang_code = $lang_code;
                    $lang_args->value = $val;
                    executeQuery('module.insertLang', $lang_args);
                }
            }
            $oModuleAdminController = &getAdminController('module');
            $oModuleAdminController->makeCacheDefinedLangCode($info->site_srl);

            $oHomepageModel = &getModel('homepage');
            $homepage_config = $oHomepageModel->getConfig(0);
            if(!$homepage_config->default_layout) $homepage_config->default_layout = 'cafeXE';

            // 레이아웃 생성
            $info->layout_srl = $this->makeLayout($info->site_srl, $title,$homepage_config->default_layout);

            // 기본 게시판+페이지 생성
            $info->module->home_srl = $this->makePage($info->site_srl, 'home', '$user_lang->home', $info->layout_srl, $this->getHomeContent());
            $info->module->notice_srl = $this->makeBoard($info->site_srl, 'notice', '$user_lang->notice', $info->layout_srl);
            $info->module->notice_srl = $this->makeBoard($info->site_srl, 'levelup', '$user_lang->levelup', $info->layout_srl);
            $info->module->freeboard_srl = $this->makeBoard($info->site_srl, 'freeboard', '$user_lang->freeboard', $info->layout_srl);

            // 메뉴 생성
            $info->menu_srl = $this->makeMenu($info->site_srl, $title, 'Main Menu');

            // menu 설정
            $this->insertMenuItem($info->menu_srl, 0, 'home', '$user_lang->home');
            $this->insertMenuItem($info->menu_srl, 0, 'notice', '$user_lang->notice');
            $this->insertMenuItem($info->menu_srl, 0, 'levelup', '$user_lang->levelup');
            $this->insertMenuItem($info->menu_srl, 0, 'freeboard', '$user_lang->freeboard');

            // layout의 설정
            $oLayoutModel = &getModel('layout');
            $layout_args = $oLayoutModel->getLayout($info->layout_srl);
            $layout->colorset = 'white';

            // vid 형식일 경우
            if(isSiteID($domain)) $layout->index_url = getSiteUrl($domain, '');
            else $layout->index_url = 'http://'.$domain; 
            $layout->main_menu = $info->menu_srl;
            $layout_args->extra_vars = serialize($layout);

            $oLayoutController = &getAdminController('layout');
            $oLayoutController->updateLayout($layout_args);

            // 생성된 게시판/ 페이지들의 레이아웃 변경
            $menu_args->menu_srl = $info->menu_srl;
            $output = executeQueryArray('layout.getLayoutModules', $menu_args);
            $modules = array();
            foreach($info->module as $module_srl) $modules[] = $module_srl;
            $layout_module_args->layout_srl = $info->layout_srl;
            $layout_module_args->module_srls = implode(',',$modules);
            $output = executeQuery('layout.updateModuleLayout', $layout_module_args);

            // 홈페이지 등록
            $args->site_srl = $info->site_srl;
            $args->title = $info->title;
            $args->layout_srl = $info->layout_srl;
            $args->first_menu_srl = $info->menu_srl;
            $args->list_order = $info->site_srl * -1;
            $output = executeQuery('homepage.insertHomepage', $args);

            // site의 index_module_srl 을 변경
            $site_args->site_srl = $info->site_srl;
            $site_args->index_module_srl = $info->module->home_srl;
            $oModuleController->updateSite($site_args);

            // 기본그룹 추가
            $oMemberAdminController = &getAdminController('member');
            unset($args);
            $args->title = '$user_lang->default_group1';
            $args->is_default = 'Y';
            $args->is_admin = 'N';
            $args->site_srl = $info->site_srl;
            $oMemberAdminController->insertGroup($args);

            unset($args);
            $args->title = '$user_lang->default_group2';
            $args->is_default = 'N';
            $args->is_admin = 'N';
            $args->site_srl = $info->site_srl;
            $oMemberAdminController->insertGroup($args);

            unset($args);
            $args->title = '$user_lang->default_group3';
            $args->is_default = 'N';
            $args->is_admin = 'N';
            $args->site_srl = $info->site_srl;
            $oMemberAdminController->insertGroup($args);

            // 기본 애드온 On
            $oAddonController = &getAdminController('addon');
            $oAddonController->doInsert('autolink', $info->site_srl);
            $oAddonController->doInsert('counter', $info->site_srl);
            $oAddonController->doInsert('member_communication', $info->site_srl);
            $oAddonController->doInsert('member_extra_info', $info->site_srl);
            $oAddonController->doInsert('referer', $info->site_srl);
            $oAddonController->doInsert('resize_image', $info->site_srl);
            $oAddonController->doActivate('autolink', $info->site_srl);
            $oAddonController->doActivate('counter', $info->site_srl);
            $oAddonController->doActivate('member_communication', $info->site_srl);
            $oAddonController->doActivate('member_extra_info', $info->site_srl);
            $oAddonController->doActivate('referer', $info->site_srl);
            $oAddonController->doActivate('resize_image', $info->site_srl);
            $oAddonController->makeCacheFile($info->site_srl);

            // 기본 에디터 컴포넌트 On
            $oEditorController = &getAdminController('editor');
            $oEditorController->insertComponent('colorpicker_text',true, $info->site_srl);
            $oEditorController->insertComponent('colorpicker_bg',true, $info->site_srl);
            $oEditorController->insertComponent('emoticon',true, $info->site_srl);
            $oEditorController->insertComponent('url_link',true, $info->site_srl);
            $oEditorController->insertComponent('image_link',true, $info->site_srl);
            $oEditorController->insertComponent('multimedia_link',true, $info->site_srl);
            $oEditorController->insertComponent('quotation',true, $info->site_srl);
            $oEditorController->insertComponent('table_maker',true, $info->site_srl);
            $oEditorController->insertComponent('poll_maker',true, $info->site_srl);
            $oEditorController->insertComponent('image_gallery',true, $info->site_srl);

            // 메뉴 XML 파일 생성
            $oMenuAdminController = &getAdminController('menu');
            $oMenuAdminController->makeXmlFile($info->menu_srl, $info->site_srl);

            $this->add('site_srl', $info->site_srl);
            $this->add('url', getSiteUrl($info->domain, ''));
        }

        function makeBoard($site_srl, $mid, $browser_title, $layout_srl) {
            $args->site_srl = $site_srl;
            $args->module_srl = getNextSequence();
            $args->module = 'board';
            $args->mid = $mid;
            $args->browser_title = $browser_title;
            $args->is_default = 'N';
            $args->layout_srl = $layout_srl;
            $args->skin = 'xe_board';

            $oModuleController = &getController('module');
            $output = $oModuleController->insertModule($args);
            return $output->get('module_srl');
        }

        function makePage($site_srl, $mid, $browser_title, $layout_srl, $content) {
            $args->site_srl = $site_srl;
            $args->module_srl = getNextSequence();
            $args->module = 'page';
            $args->mid = $mid;
            $args->browser_title = $browser_title;
            $args->is_default = 'N';
            $args->layout_srl = $layout_srl;
            $args->content = $content;

            $oModuleController = &getController('module');
            $output = $oModuleController->insertModule($args);
            return $output->get('module_srl');
        }

        function makeMenu($site_srl, $title, $menu_title) {
            $args->site_srl = $site_srl;
            $args->title = $title.' - '.$menu_title;
            $args->menu_srl = getNextSequence();
            $args->listorder = $args->menu_srl * -1;

            $output = executeQuery('menu.insertMenu', $args);
            if(!$output->toBool()) return $output;

            return $args->menu_srl;
        }

        function makeLayout($site_srl, $title, $layout) {
            $args->site_srl = $site_srl;
            $args->layout_srl = getNextSequence();
            $args->layout = $layout;
            $args->title = $title;

            $oLayoutAdminController = &getAdminController('layout');
            $output = $oLayoutAdminController->insertLayout($args);
            if(!$output->toBool()) return $output;

            return $args->layout_srl;
        }

        function insertMenuItem($menu_srl, $parent_srl = 0, $mid, $name) {
            // 변수를 다시 정리 (form문의 column과 DB column이 달라서)
            $args->menu_srl = $menu_srl;
            $args->menu_item_srl = getNextSequence();
            $args->parent_srl = $parent_srl;
            $args->name = $name;
            $args->url = $mid;
            $args->open_window = 'N';
            $args->expand = 'N';
            $args->normal_btn = null;
            $args->hover_btn = null;
            $args->active_btn = null;
            $args->group_srls = null;
            $args->listorder = $args->menu_item_srl*-1;
            $output = executeQuery('menu.insertMenuItem', $args);
            return $args->menu_item_srl;
        }

        function getHomeContent() {
            return 
                '<img class="zbxe_widget_output" widget="content" skin="default" colorset="layout" content_type="document" list_type="normal" tab_type="none" option_view="title,regdate,nickname" show_browser_title="Y" show_comment_count="Y" show_trackback_count="Y" show_category="Y" show_icon="Y" order_target="list_order" order_type="desc" thumbnail_type="crop" page_count="2" duration_new="24" widgetstyle="simple" list_count="7" ws_colorset="layout" ws_title="$user_lang->view_total" ws_more_url="" ws_more_text="" style="float:left;width:100%"/>'.
                '<img class="zbxe_widget_output" widget="content" skin="default" colorset="layout" content_type="comment" list_type="normal" tab_type="none" option_view="title,regdate,nickname" show_browser_title="Y" show_comment_count="Y" show_trackback_count="Y" show_category="Y" show_icon="Y" order_target="list_order" order_type="desc" thumbnail_type="crop" page_count="2" duration_new="24" widgetstyle="simple" list_count="7" ws_colorset="layout" ws_title="$user_lang->view_comment" ws_more_url="" ws_more_text="" style="float:left;width:100%" />'.
                '<img class="zbxe_widget_output" widget="content" skin="default" colorset="layout" content_type="image" list_type="gallery" tab_type="none" option_view="title,regdate,nickname,thumbnail" show_browser_title="Y" show_comment_count="Y" show_trackback_count="Y" show_category="Y" show_icon="Y" order_target="list_order" order_type="desc" thumbnail_type="crop" thumbnail_width="100" thumbnail_height="75" list_count="10" page_count="1" cols_list_count="5" duration_new="24" content_cut_size="20" widgetstyle="simple" ws_colorset="layout" ws_title="$user_lang->cafe_album" ws_more_url="" ws_more_text="" style="float:left;width:100%"/>'.
                '';
        }

        function procHomepageAdminUpdateHomepage() {
            $oHomepageModel = &getModel('homepage');
            $oModuleController = &getController('module');

            // 카페이름, 접속방법, 카페관리자 지정
            $args = Context::gets('site_srl','title','homepage_admin');
            if(!$args->site_srl) return new Object(-1,'msg_invalid_request');

            if(Context::get('access_type')=='domain') $args->domain = Context::get('domain');
            else $args->domain = Context::get('vid');
            if(!$args->domain) return new Object(-1,'msg_invalid_request');

            $homepage_info = $oHomepageModel->getHomepageInfo($args->site_srl);
            if(!$homepage_info->site_srl) return new Object(-1,'msg_invalid_request');

            // 관리자 지정
            $admin_list = explode(',',$args->homepage_admin);
            $output = $oModuleController->insertSiteAdmin($args->site_srl, $admin_list);
            if(!$output->toBool()) return $output;

            // 카페이름 변경
            $output = executeQuery('homepage.updateHomepage', $args);
            if(!$output->toBool()) return false;

            // 도메인 변경
            $output = $oModuleController->updateSite($args);
            if(!$output->toBool()) return false;

            // 기본 레이아웃, 레이아웃 변경, 허용 서비스 변경
            $this->procHomepageAdminInsertConfig();

            $this->setMessage('success_updated');
        }

        function procHomepageAdminDeleteHomepage() {
            $site_srl = Context::get('site_srl');
            if(!$site_srl) return new Object(-1,'msg_invalid_request');

            $oHomepageModel = &getModel('homepage');
            $homepage_info = $oHomepageModel->getHomepageInfo($site_srl);
            if(!$homepage_info->site_srl) return new Object(-1,'msg_invalid_request');

            $args->site_srl = $site_srl;

            // 홈페이지 정보 삭제
            executeQuery('homepage.deleteHomepage', $args);

            // 사이트 정보 삭제
            executeQuery('module.deleteSite', $args);

            // 사이트 관리자 삭제
            executeQuery('module.deleteSiteAdmin', $args);

            // 회원 그룹 매핑 데이터 삭제
            executeQuery('member.deleteMemberGroup', $args);

            // 회원 그룹 삭제
            executeQuery('member.deleteSiteGroup', $args);

            // 메뉴 삭제
            $oMenuAdminController = &getAdminController('menu');
            $oMenuAdminController->deleteMenu($homepage_info->first_menu_srl);

            // 카운터 정보 삭제
            $oCounterController = &getController('counter');
            $oCounterController->deleteSiteCounterLogs($site_srl);

            // 애드온 삭제
            $oAddonController = &getController('addon');
            $oAddonController->removeAddonConfig($site_srl);

            // 에디터 컴포넌트 삭제
            $oEditorController = &getController('editor');
            $oEditorController->removeEditorConfig($site_srl);

            // 레이아웃 삭제
            Context::set('layout_srl', $homepage_info->layout_srl);
            $oLayoutAdminController = &getAdminController('layout');
            $oLayoutAdminController->procLayoutAdminDelete();

            // 게시판 & 페이지 삭제
            $oModuleModel = &getModel('module');
            $oModuleController =&getController('module');
            $mid_list = $oModuleModel->getMidList($args);
            foreach($mid_list as $key => $val) {
                $module_srl = $val->module_srl;
                $oModuleController->deleteModule($module_srl);
            }

            // 사용자 정의 언어 제거
            $lang_args->site_srl = $site_srl;
            $output = executeQuery('module.deleteLangs', $lang_args);
            $lang_supported = Context::get('lang_supported');
            foreach($lang_supported as $key => $val) {
                $lang_cache_file = _XE_PATH_.'files/cache/lang_defined/'.$site_srl.'.'.$key.'.php';
                FileHandler::removeFile($lang_cache_file);
            }


            $this->setMessage('success_deleted');
        }
        
        /**
         * @brief 다른 가상 사이트에서 모듈을 이동
         **/
        function procHomepageAdminImportModule() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            $oHomepageModel = &getModel('homepage');
            $oMenuAdminController = &getAdminController('menu');

            $module_srl = Context::get('import_module_srl');
            $site_srl = Context::get('site_srl');
            if(!$module_srl || !$site_srl) return new Object(-1,'msg_invalid_request');

            $site_module_info = $oModuleModel->getSiteInfo($site_srl);
            if(!$site_module_info->site_srl) return new Object(-1,'msg_invalid_request');

            $homepage_info = $oHomepageModel->getHomepageInfo($site_srl);

            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$module_info->module_srl) return new Object(-1,'msg_invalid_request');

            if($module_info->site_srl == $site_srl) return new Object(-1,'msg_same_site');

            // 대상 모듈의 site_srl을 변경
            $output = $oModuleController->updateModuleSite($module_srl, $site_srl, $homepage_info->layout_srl);
            if(!$output->toBool()) return $output;


            // 대상 모듈을 최하단 메뉴로 추가
            $args->menu_srl = $homepage_info->first_menu_srl;
            $args->menu_item_srl = getNextSequence();
            $args->parent_srl = 0;
            $args->name = $module_info->browser_title;
            $args->url = $module_info->mid;;
            $args->open_window = 'N';
            $args->expand = 'N';
            $args->normal_btn = '';
            $args->hover_btn = '';
            $args->active_btn = '';
            $args->group_srls = '';
            $args->listorder = -1*$args->menu_item_srl;
            $output = executeQuery('menu.insertMenuItem', $args);
            if(!$output->toBool()) return $output;

            // 캐시파일 재생성
            $xml_file = $oMenuAdminController->makeXmlFile($args->menu_srl);
        }

        /**
         * @brief 가상 사이트의 모듈을 기본 사이트로 이동
         **/
        function procHomepageAdminExportModule() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            $oHomepageModel = &getModel('homepage');
            $oMenuAdminController = &getAdminController('menu');

            $module_srl = Context::get('export_module_srl');
            if(!$module_srl) return new Object(-1,'msg_invalid_request');

            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$module_info->module_srl || !$module_info->site_srl) return new Object(-1,'msg_invalid_request');

            $site_srl = $module_info->site_srl;
            $site_module_info = $oModuleModel->getSiteInfo($site_srl);
            if(!$site_module_info->site_srl) return new Object(-1,'msg_invalid_request');

            $homepage_info = $oHomepageModel->getHomepageInfo($site_srl);

            // 대상 모듈의 site_srl을 변경
            $output = $oModuleController->updateModuleSite($module_srl, 0, '');
            if(!$output->toBool()) return $output;
        }
    }

?>
