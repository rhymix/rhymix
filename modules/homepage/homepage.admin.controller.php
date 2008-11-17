<?php
    /**
     * @class  homepageAdminController
     * @author zero (zero@nzeo.com)
     * @brief  homepage 모듈의 admin controller class
     **/

    class homepageAdminController extends homepage {

        function init() {
        }

        function procHomepageAdminInsertHomepage() {
            $title = Context::get('title');
            $domain = preg_replace('/^(http|https):\/\//i','',Context::get('domain'));
            if(!$title) return new Object(-1, 'msg_invalid_request');
            if(!$domain) return new Object(-1, 'msg_invalid_request');

            $output = $this->insertHomepage($title, $domain);
            return $output;
        }

        function insertHomepage($title, $domain) {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            $default_menus = Context::getLang('homepage_default_menus');

            $info->title = $title;
            $info->domain = $domain;

            // 도메인 검사
            $domain_info = $oModuleModel->getSiteInfoByDomain($domain);
            if($domain_info) return new Object(-1,'msg_already_registed_domain');

            // virtual site 생성
            $info->site_srl = $oModuleController->insertSite($domain, $info->first->home_srl);

            // 레이아웃 생성
            $info->layout_srl = $this->makeLayout($title,'xe_official');

            // 기본 게시판+페이지 생성
            $info->first->home_srl = $this->makePage($info->site_srl, 'home', $default_menus['first']['home'], $this->getHomeContent($default_menus), $content, $info->layout_srl);
            $info->first->notice_srl = $this->makeBoard($info->site_srl, 'notice', $default_menus['first']['notice'], $info->layout_srl);
            $info->first->download_srl = $this->makeBoard($info->site_srl, 'download', $default_menus['first']['download'], $info->layout_srl);
            $info->first->gallery_srl = $this->makeBoard($info->site_srl, 'gallery', $default_menus['first']['gallery'], $info->layout_srl);
            $info->first->community_srl = $this->makePage($info->site_srl, 'community', $default_menus['first']['community'], $content, $info->layout_srl);
            $info->first->freeboard_srl = $this->makeBoard($info->site_srl, 'freeboard', $default_menus['first']['freeboard'], $info->layout_srl);
            $info->first->humor_srl = $this->makeBoard($info->site_srl, 'humor', $default_menus['first']['humor'], $info->layout_srl);
            $info->first->qa_srl = $this->makeBoard($info->site_srl, 'qa', $default_menus['first']['qa'], $info->layout_srl);

            $info->second->profile = $this->makePage($info->site_srl, 'profile', $default_menus['second']['profile'], $content, $info->layout_srl);
            $info->second->rule = $this->makePage($info->site_srl, 'rule', $default_menus['second']['rule'], $content, $info->layout_srl);

            // 메뉴 생성
            $info->first_menu_srl = $this->makeMenu($title, $default_menus['menu']['first']);
            $info->second_menu_srl = $this->makeMenu($title, $default_menus['menu']['second']);

            // first menu 설정
            $item_srl = $this->insertMenuItem($info->first_menu_srl, 0, 'home', $default_menus['first']['home']);
            $this->insertMenuItem($info->first_menu_srl, $item_srl, 'notice', $default_menus['first']['notice']);
            $this->insertMenuItem($info->first_menu_srl, 0, 'download', $default_menus['first']['download']);
            $this->insertMenuItem($info->first_menu_srl, 0, 'gallery', $default_menus['first']['gallery']);
            $item_srl = $this->insertMenuItem($info->first_menu_srl, 0, 'community', $default_menus['first']['community']);
            $this->insertMenuItem($info->first_menu_srl, $item_srl, 'freeboard', $default_menus['first']['freeboard']);
            $this->insertMenuItem($info->first_menu_srl, $item_srl, 'humor', $default_menus['first']['humor']);
            $this->insertMenuItem($info->first_menu_srl, $item_srl, 'qa', $default_menus['first']['qa']);

            // second menu 설정
            $this->insertMenuItem($info->second_menu_srl, 0, 'profile', $default_menus['second']['profile']);
            $this->insertMenuItem($info->second_menu_srl, 0, 'rule', $default_menus['second']['rule']);

            // layout의 설정
            $oLayoutModel = &getModel('layout');
            $layout_args = $oLayoutModel->getLayout($info->layout_srl);

            $layout->colorset = 'default';
            if($domain) $layout->index_url = 'http://'.$domain; else $layout->index_url = Context::getRequestUri();
            $layout->main_menu = $info->first_menu_srl;
            $layout->bottom_menu = $info->second_menu_srl;

            $layout_args->extra_vars = serialize($layout);

            $oLayoutController = &getAdminController('layout');
            $oLayoutController->updateLayout($layout_args);

            // 생성된 게시판/ 페이지들의 레이아웃 변경
            $menu_args = null;
            $menu_args->menu_srl = $info->first_menu_srl;
            $output = executeQueryArray('layout.getLayoutModules', $menu_args);

            $menu_args->menu_srl = $info->second_menu_srl;
            $output = executeQueryArray('layout.getLayoutModules', $menu_args);

            $modules = array();
            foreach($info->first as $module_srl) $modules[] = $module_srl;
            foreach($info->second as $module_srl) $modules[] = $module_srl;
            $layout_module_args->layout_srl = $info->layout_srl;
            $layout_module_args->module_srls = implode(',',$modules);
            $output = executeQuery('layout.updateModuleLayout', $layout_module_args);

            // 메뉴 XML 파일 생성
            $oMenuAdminController = &getAdminController('menu');
            $oMenuAdminController->makeXmlFile($info->first_menu_srl);
            $oMenuAdminController->makeXmlFile($info->second_menu_srl);

            $args->site_srl = $info->site_srl;
            $args->title = $info->title;
            $args->layout_srl = $info->layout_srl;
            $args->first_menu_srl = $info->first_menu_srl;
            $args->second_menu_srl = $info->second_menu_srl;
            $args->list_order = $info->site_srl * -1;
            $output = executeQuery('homepage.insertHomepage', $args);

            // site의 index_module_srl 을 변경
            $site_args->site_srl = $info->site_srl;
            $site_args->index_module_srl = $info->first->home_srl;
            $oModuleController->updateSite($site_args);

            // 기본 그룹 (준회원, 정회원)을 추가
            $oMemberAdminController = &getAdminController('member');
            unset($args);
            $args->title = Context::getLang('default_group_1');
            $args->is_default = 'Y';
            $args->is_admin = 'N';
            $args->site_srl = $info->site_srl;
            $oMemberAdminController->insertGroup($args);

            unset($args);
            $args->title = Context::getLang('default_group_2');
            $args->is_default = 'N';
            $args->is_admin = 'N';
            $args->site_srl = $info->site_srl;
            $oMemberAdminController->insertGroup($args);

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

        function makePage($site_srl, $mid, $browser_title, $content, $layout_srl) {
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

        function makeMenu($title, $menu_title) {
            $args->title = $title.' - '.$menu_title;
            $args->menu_srl = getNextSequence();
            $args->listorder = $args->menu_srl * -1;

            $output = executeQuery('menu.insertMenu', $args);
            if(!$output->toBool()) return $output;

            return $args->menu_srl;
        }

        function makeLayout($title, $layout) {
            $args->layout_srl = getNextSequence();
            $args->layout = $layout;
            $args->title = $title;

            $output = executeQuery("layout.insertLayout", $args);
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

        function getHomeContent( $default_menus) {
            return '<div widget="widgetBox" style="border: 3px solid rgb(221, 221, 221); margin: 0pt; background-position: 0pt 50%; float: left; width: 500px; background-repeat: repeat; background-color: transparent; background-image: none;" widget_padding_left="8px" widget_padding_right="8px" widget_padding_top="8px" widget_padding_bottom="8px"><div><div><img style="border: 0px solid rgb(255, 255, 255); margin: 0pt; background-position: 0pt 50%; float: left; width: 482px; background-repeat: repeat; background-color: transparent; height: 200px;" widget="webzine" widget_sequence="'.getNextSequence().'" skin="notice_style" colorset="normal" widget_cache="5" order_target="list_order" order_type="desc" content_cut_size="400" thumbnail_type="crop" thumbnail_width="130" thumbnail_height="130" cols_list_count="1" rows_list_count="1" display_author="N" display_regdate="Y" display_readed_count="N" display_voted_count="N" mid_list="notice"  /><img style="border: 0px solid rgb(255, 255, 255); margin: 5px 0px 0px; background-position: 0pt 50%; float: left; width: 481px; background-repeat: repeat; height: 59px; background-color: transparent;" widget="newest_document" widget_sequence="'.getNextSequence().'" widget_cache="5" mid_list="notice" order_type="desc" order_target="list_order" skin="xe_official" colorset="white" list_count="3" duration_new="96" /><div class="clear"></div></div></div></div><div widget="widgetBox" style="border: 0px solid rgb(255, 255, 255); margin: 0pt; background-position: 0pt 50%; float: right; width: 252px; background-repeat: repeat; height: 797px; background-color: transparent;" widget_padding_left="0" widget_padding_right="0" widget_padding_top="0" widget_padding_bottom="0"><div><div><img widget="widgetContent" style="border: 3px solid rgb(221, 221, 221); margin: 0pt; background-position: 0pt 50%; float: left; width: 240px; background-repeat: repeat; height: 22px; background-color: transparent;" body="'.base64_encode($default_menus['first']['gallery']).'" widget_padding_left="5px" widget_padding_right="0" widget_padding_top="3px" widget_padding_bottom="0" /><img style="border: 0px solid rgb(255, 255, 255); margin: 5px 0pt 0pt; background-position: 0pt 50%; float: left; width: 246px; background-repeat: repeat; background-color: transparent;" widget="webzine" module_srl="0" widget_sequence="'.getNextSequence().'" skin="xe_official" colorset="normal" widget_cache="10" order_target="list_order" order_type="desc" subject_cut_size="8" content_cut_size="40" thumbnail_type="crop" thumbnail_width="70" thumbnail_height="70" cols_list_count="1" rows_list_count="2" display_author="N" display_regdate="N" display_readed_count="Y" display_voted_count="Y" mid_list="gallery"  /><img widget="widgetContent" style="border: 3px solid rgb(221, 221, 221); margin: 0pt; background-position: 0pt 50%; float: left; width: 240px; background-repeat: repeat; height: 22px; background-color: transparent;" body="'.base64_encode($default_menus['first']['download']).'" widget_padding_left="5px" widget_padding_right="0" widget_padding_top="3px" widget_padding_bottom="0" /><img style="border: 0px solid rgb(255, 255, 255); margin: 5px 0pt 0pt; background-position: 0pt 50%; float: left; width: 246px; background-repeat: repeat; background-color: transparent;" widget="webzine" module_srl="0" widget_sequence="'.getNextSequence().'" skin="xe_official" colorset="normal" widget_cache="10" order_target="list_order" order_type="desc" subject_cut_size="8" content_cut_size="40" thumbnail_type="crop" thumbnail_width="70" thumbnail_height="70" cols_list_count="1" rows_list_count="2" display_author="N" display_regdate="N" display_readed_count="Y" display_voted_count="Y" mid_list="download"  /><img widget="widgetContent" style="border: 3px solid rgb(221, 221, 221); margin: 5px 0pt 0pt; background-position: 0pt 50%; float: left; width: 240px; background-repeat: repeat; height: 22px; background-color: transparent; background-image: none;" body="'.base64_encode($default_menus['widget']['download_rank']).'" widget_padding_left="5px" widget_padding_right="0" widget_padding_top="3px" widget_padding_bottom="0" /><img style="border: 0px solid rgb(255, 255, 255); margin: 0pt; float: left; width: 246px; background-color: transparent; background-image: none; background-repeat: repeat; background-position: 0pt 0pt;" widget="rank_download" module_srl="85" skin="sz_xe" colorset="Box_000" widget_cache="10" widget_sequence=".getNextSequence()." list_count="5" attach_type="all" download="Y" mid_list="download" order_type="desc"  /><div class="clear"></div></div></div></div><div widget="widgetBox" style="border: 0px solid rgb(255, 255, 255); margin: 10px 0pt 0pt; background-position: 0pt 50%; float: left; width: 508px; background-repeat: repeat; height: 530px; background-color: transparent;" widget_padding_left="0" widget_padding_right="0" widget_padding_top="0" widget_padding_bottom="0"><div><div><img widget="widgetContent" style="border: 3px solid rgb(221, 221, 221); margin: 10px 0px 0px; background-position: 0pt 50%; float: left; background-image: none; width: 500px; background-repeat: repeat; height: 22px; background-color: transparent;" body="'.base64_encode($default_menus['first']['freeboard']).'" widget_padding_left="5px" widget_padding_right="0" widget_padding_top="3px" widget_padding_bottom="0" /><img style="border: 1px solid rgb(227, 227, 227); margin: 2px 0pt 0pt; background-position: 0pt 50%; float: left; width: 504px; background-repeat: repeat; background-color: transparent; height: 108px;" widget="newest_document" mid_list="freeboard" subject_cut_size="0" duration_new="96" list_count="5" order_type="desc" order_target="list_order" widget_cache="10" colorset="white" skin="xe_official" widget_sequence="'.getNextSequence().'" widget_padding_left="5px" widget_padding_bottom="5px" widget_padding_right="5px" widget_padding_top="5px"  /><div class="clear"></div><img widget="widgetContent" style="border: 3px solid rgb(221, 221, 221); margin: 10px 0px 0px; background-position: 0pt 50%; float: left; background-image: none; width: 500px; background-repeat: repeat; height: 22px; background-color: transparent;" body="'.base64_encode($default_menus['first']['humor']).'" widget_padding_left="5px" widget_padding_right="0" widget_padding_top="3px" widget_padding_bottom="0" /><img style="border: 1px solid rgb(227, 227, 227); margin: 2px 0pt 0pt; background-position: 0pt 50%; float: left; width: 504px; background-repeat: repeat; background-color: transparent; height: 108px;" widget="newest_document" mid_list="humor" subject_cut_size="0" duration_new="96" list_count="5" order_type="desc" order_target="list_order" widget_cache="10" colorset="white" skin="xe_official" widget_sequence="'.getNextSequence().'" widget_padding_left="5px" widget_padding_bottom="5px" widget_padding_right="5px" widget_padding_top="5px"  /><div class="clear"></div><img widget="widgetContent" style="border: 3px solid rgb(221, 221, 221); margin: 10px 0px 0px; background-position: 0pt 50%; float: left; background-image: none; width: 500px; background-repeat: repeat; height: 22px; background-color: transparent;" body="'.base64_encode($default_menus['first']['qa']).'" widget_padding_left="5px" widget_padding_right="0" widget_padding_top="3px" widget_padding_bottom="0" /><img style="border: 1px solid rgb(227, 227, 227); margin: 2px 0pt 0pt; background-position: 0pt 50%; float: left; width: 504px; background-repeat: repeat; background-color: transparent; height: 108px;" widget="newest_document" mid_list="qa" subject_cut_size="0" duration_new="96" list_count="5" order_type="desc" order_target="list_order" widget_cache="10" colorset="white" skin="xe_official" widget_sequence="'.getNextSequence().'" widget_padding_left="5px" widget_padding_bottom="5px" widget_padding_right="5px" widget_padding_top="5px"  /><div class="clear"></div></div></div></div>';
        }

        function procHomepageAdminUpdateHomepage() {
            $args = Context::gets('site_srl','title','domain','homepage_admin');
            if(!$args->site_srl) return new Object(-1,'msg_invalid_request');

            $oHomepageModel = &getModel('homepage');
            $homepage_info = $oHomepageModel->getHomepageInfo($args->site_srl);
            if(!$homepage_info->site_srl) return new Object(-1,'msg_invalid_request');

            $output = executeQuery('homepage.updateHomepageTitle', $args);
            if(!$output->toBool()) return $output;

            $oModuleController = &getController('module');
            $output = $oModuleController->updateSite($args);
            if(!$output->toBool()) return $output;

            $admin_list = explode(',',$args->homepage_admin);
            $output = $oModuleController->insertSiteAdmin($args->site_srl, $admin_list);
            if(!$output->toBool()) return $output;

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

            // 메뉴 삭제
            $oMenuAdminController = &getAdminController('menu');
            Context::set('menu_srl', $homepage_info->first_menu_srl);
            $oMenuAdminController->procMenuAdminDelete();
            Context::set('menu_srl', $homepage_info->second_menu_srl);
            $oMenuAdminController->procMenuAdminDelete();

            // 레이아웃 삭제
            Context::set('layout_srl', $homepage_info->layout_srl);
            $oLayoutAdminController = &getAdminController('layout');
            $oLayoutAdminController->procLayoutAdminDelete();

            // 게시판 & 페이지 삭제
            $oModuleModel = &getModel('module');
            $mid_list = $oModuleModel->getMidList($args);
            $oBoardAdminController = &getAdminController('board');
            $oPageAdminController = &getAdminController('page');
            foreach($mid_list as $key => $val) {
                Context::set('module_srl', $val->module_srl);
                if($val->module == 'page') {
                    $oPageAdminController->procPageAdminDelete();
                } elseif($val->module == 'board') {
                    $oBoardAdminController->procBoardAdminDeleteBoard();
                }
            }

            $this->setMessage('success_deleted');
        }
    }

?>
