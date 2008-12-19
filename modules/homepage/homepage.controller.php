<?php
    /**
     * @class  homepageController
     * @author zero (zero@nzeo.com)
     * @brief  homepage 모듈의 controller class
     **/

    class homepageController extends homepage {

        var $site_module_info = null;
        var $site_srl = null;
        var $homepage_info = null;
        var $selected_layout = null;

        function init() {
            $oModuleModel = &getModel('module');
            if(!$oModuleModel->isSiteAdmin()) return $this->stop('msg_not_permitted');

            // site_module_info값으로 홈페이지의 정보를 구함
            $this->site_module_info = Context::get('site_module_info');
            $this->site_srl = $this->site_module_info->site_srl;

            $oHomepageModel = &getModel('homepage');
            $this->homepage_info = $oHomepageModel->getHomepageInfo($this->site_srl);

            $oLayoutModel = &getModel('layout');
            $this->selected_layout = $oLayoutModel->getLayout($this->homepage_info->layout_srl);

        }

        function procHomepageChangeLayout() {
            $layout = Context::get('layout');
            if(!$layout || !is_dir(_XE_PATH_.'layouts/'.$layout)) return new Object(-1,'msg_invalid_request');

            $layout_srl = $this->selected_layout->layout_srl;

            if($layout == $this->selected_layout->layout) return;

            $oLayoutAdminController = &getAdminController('layout');
            $args->layout_srl = $layout_srl;
            $args->layout = $layout;
            $args->layout_path = '';
            return $oLayoutAdminController->updateLayout($args);
        }

        function procHomepageLayoutUpdate() {
            $layout_srl = Context::get('layout_srl');
            if(!$layout_srl || $layout_srl!=$this->selected_layout->layout_srl) exit();
            $oLayoutAdminController = &getAdminController('layout');
            $oLayoutAdminController->procLayoutAdminUpdate();

            $this->setLayoutPath( $oLayoutAdminController->getLayoutPath() );
            $this->setLayoutFile( $oLayoutAdminController->getLayoutFile() );
            $this->setTemplatePath( $oLayoutAdminController->getTemplatePath() );
            $this->setTemplateFile( $oLayoutAdminController->getTemplateFile() );
        }

        function procHomepageInsertMenuItem() {
            global $lang;

            // 기본 변수 체크
            $source_args = Context::getRequestVars();
            unset($source_args->body);
            unset($source_args->module);
            unset($source_args->act);
            unset($source_args->module_type);
            unset($source_args->module_id);
            unset($source_args->url);
            if($source_args->menu_open_window!="Y") $source_args->menu_open_window = "N";
            if($source_args->menu_expand !="Y") $source_args->menu_expand = "N";
            $source_args->group_srls = str_replace('|@|',',',$source_args->group_srls);
            $source_args->parent_srl = (int)$source_args->parent_srl;

            $module_type = Context::get('module_type');
            $browser_title = trim(Context::get('browser_title'));
            $url = trim(Context::get('url'));
            $module_id = trim(Context::get('module_id'));

            $oMenuAdminModel = &getAdminModel('menu');
            $oMenuAdminController = &getAdminController('menu');

            // 메뉴 이름 체크
            $lang_supported = Context::get('lang_supported');
            $name_inserted = false;
            foreach($lang_supported as $key => $val) {
                $menu_name[$key] = $source_args->{"menu_name_".strtolower($key )};
                if($menu_name[$key]) $name_inserted = true;
            }
            if(!$name_inserted) {
                return new Object(-1, sprintf($lang->filter->isnull, $lang->menu_name));
            }

            $mode = Context::get('mode');

            // module_type이 url이 아니면 게시판 또는 페이지를 생성한다
            if($module_type != 'url' && $mode == 'insert') {
                if(!$browser_title) return new Object(-1, sprintf($lang->filter->isnull, $lang->browser_title));
                if(!$module_id) return new Object(-1, sprintf($lang->filter->isnull, $lang->module_id));

                // 모듈이름을 검사
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByMid($module_id, $this->site_srl);
                if($module_info->mid == $module_id) return new Object(-1,'msg_module_name_exists');

                $oHomepageAdminController = &getAdminController('homepage');
                
                if($module_type == 'page') {
                    $oHomepageAdminController->makePage($this->site_srl, $module_id, $browser_title, '', $this->selected_layout->layout_srl);
                } else {
                    $oHomepageAdminController->makeBoard($this->site_srl, $module_id, $browser_title, $this->selected_layout->layout_srl);
                }

            }

            // 변수를 다시 정리 (form문의 column과 DB column이 달라서)
            $args->menu_srl = $source_args->menu_srl;
            $args->menu_item_srl = $source_args->menu_item_srl;
            $args->parent_srl = $source_args->parent_srl;
            $args->menu_srl = $source_args->menu_srl;
            $args->name = serialize($menu_name);
            if($module_type=='url') $args->url = 'http://'.preg_replace('/^(http|https):\/\//i','',$url);
            else $args->url = $module_id;
            $args->open_window = $source_args->menu_open_window;
            $args->expand = $source_args->menu_expand;
            $args->normal_btn = $source_args->normal_btn;
            $args->hover_btn = $source_args->hover_btn;
            $args->active_btn = $source_args->active_btn;
            $args->group_srls = $source_args->group_srls;

            switch($mode) {
                case 'insert' :
                        $args->menu_item_srl = getNextSequence();
                        $args->listorder = -1*$args->menu_item_srl;
                        $output = executeQuery('menu.insertMenuItem', $args);
                        if(!$output->toBool()) return $output;
                    break;
                case 'update' :
                        $source_menu_info = $oMenuAdminModel->getMenuItemInfo($args->menu_item_srl);
                        $output = executeQuery('menu.updateMenuItem', $args);
                        if(!$output->toBool()) return $output;

                        if($module_type != 'url') {
                            $oModuleModel = &getModel('module');
                            $module_info = $oModuleModel->getModuleInfoByMid($source_menu_info->url, $this->site_srl);
                            if($module_info->mid != $module_id || $module_info->browser_title != $browser_title) {
                                $module_info->browser_title = $browser_title;
                                $module_info->mid = $module_id;
                                $oModuleController = &getController('module');
                                $oModuleController->updateModule($module_info);
                            }
                        }
                    break;
                default :
                        return new Object(-1,'msg_invalid_request');
                    break;
            }

            // 해당 메뉴의 정보를 구함
            $menu_info = $oMenuAdminModel->getMenu($args->menu_srl);
            $menu_title = $menu_info->title;

            // XML 파일을 갱신하고 위치을 넘겨 받음
            $xml_file = $oMenuAdminController->makeXmlFile($args->menu_srl);

            $this->add('xml_file', $xml_file);
        }

        function procHomepageDeleteMenuItem() {
            $menu_item_srl = Context::get('menu_item_srl');
            if(!$menu_item_srl) return new Object(-1,'msg_invalid_request');

            $oMenuAdminModel = &getAdminModel('menu');
            $oMenuAdminController = &getAdminController('menu');

            $menu_info = $oMenuAdminModel->getMenuItemInfo($menu_item_srl);
            if(!$menu_info || $menu_info->menu_item_srl != $menu_item_srl) return new Object(-1,'msg_invalid_request');

            Context::set('menu_srl', $menu_info->menu_srl);
            $output = $oMenuAdminController->procMenuAdminDeleteItem();
            if(is_object($output) && !$output->toBool()) return $output;
            $this->add('xml_file', $oMenuAdminController->get('xml_file'));

            $mid = $menu_info->url;
            if(!preg_match('/^http/i',$mid)) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByMid($mid, $this->site_srl);
                if($module_info && $module_info->mid == $mid) {
                    $oModuleController = &getController('module');
                    $output = $oModuleController->deleteModule($module_info->module_srl, $this->site_srl);
                }
            }
        }

        function procHomepageMenuUploadButton() {
            $menu_srl = Context::get('menu_srl');
            $menu_item_srl = Context::get('menu_item_srl');
            $target = Context::get('target');
            $target_file = Context::get($target);

            // 필수 요건이 없거나 업로드된 파일이 아니면 오류 발생
            if(!$menu_srl || !$menu_item_srl || !$target_file || !is_uploaded_file($target_file['tmp_name']) || !preg_match('/\.(gif|jpeg|jpg|png)/i',$target_file['name'])) {
                Context::set('error_messge', Context::getLang('msg_invalid_request'));

            // 요건을 만족하고 업로드된 파일이면 지정된 위치로 이동
            } else {
                $tmp_arr = explode('.',$target_file['name']);
                $ext = $tmp_arr[count($tmp_arr)-1];

                $path = sprintf('./files/attach/menu_button/%d/', $menu_srl);
                $filename = sprintf('%s%d.%s.%s', $path, $menu_item_srl, $target, $ext);

                if(!is_dir($path)) FileHandler::makeDir($path);

                move_uploaded_file($target_file['tmp_name'], $filename);
                Context::set('filename', $filename);
            }

            $this->setTemplatePath('./modules/menu/tpl');
            $this->setTemplateFile('menu_file_uploaded');
        }

        function procHomepageDeleteButton() {
            $menu_srl = Context::get('menu_srl');
            $menu_item_srl = Context::get('menu_item_srl');
            $target = Context::get('target');
            $filename = Context::get('filename');
            FileHandler::removeFile($filename);

            $this->add('target', $target);
        }

        function procHomepageMenuItemMove() {
            $menu_srl = Context::get('menu_srl');
            $mode = Context::get('mode');
            $parent_srl = Context::get('parent_srl');
            $source_srl = Context::get('source_srl');
            $target_srl = Context::get('target_srl');

            if(!$menu_srl || !$mode || !$target_srl) return new Object(-1,'msg_invalid_request');
            $oMenuAdminController = &getAdminController('menu');
            $xml_file = $oMenuAdminController->moveMenuItem($menu_srl,$parent_srl,$source_srl,$target_srl,$mode);
            $this->add('xml_file', $xml_file);
        }

        function procHomepageInsertBoard() {
            $oBoardAdminController = &getAdminController('board');
            $output = $oBoardAdminController->procBoardAdminInsertBoard();
            if(is_object($output) && !$output->toBool()) return $output;
            $this->add('module_srl', $oBoardAdminController->get('module_srl'));
            $this->setMessage($oBoardAdminController->getMessage());
        }

        function procHomepageDeleteGroup() {
            $oMemberAdminController = &getAdminController('member');
            $group_srl = Context::get('group_srl');
            $output = $oMemberAdminController->deleteGroup($group_srl, $this->site_srl);
            if(!$output->toBool()) return $output;
        }

        function procHomepageInsertGroup() {
            $args->group_srl = Context::get('group_srl');
            $args->title = Context::get('title');
            $args->is_default = Context::get('is_default');
            if($args->is_default!='Y') $args->is_default = 'N';
            $args->description = Context::get('description');
            $args->site_srl = $this->site_srl;

            $oMemberAdminController = &getAdminController('member');
            if($args->group_srl) {
                $output = $oMemberAdminController->updateGroup($args);
            } else {
                $output = $oMemberAdminController->insertGroup($args);
            }
            if(!$output->toBool()) return $output;
        }

        function procHomepageUpdateMemberGroup() {
            if(!Context::get('cart')) return new Object();
            $args->site_srl = $this->site_srl;
            $args->member_srl = explode('|@|',Context::get('cart'));
            $args->group_srl = Context::get('group_srl');
            $oMemberController = &getController('member');
            return $oMemberController->replaceMemberGroup($args);
        }

        function procHomepageUpdateBoardSkin() {
            $oBoardAdminController = &getAdminController('board');
            $oBoardAdminController->procBoardAdminUpdateSkinInfo();

            $this->setLayoutPath($oBoardAdminController->getLayoutPath());
            $this->setLayoutFile($oBoardAdminController->getLayoutFile());
            $this->setTemplatePath($oBoardAdminController->getTemplatePath());
            $this->setTemplateFile($oBoardAdminController->getTemplateFile());
        }

        function procHomepageInsertBoardGrant() {
            $module_srl = Context::get('module_srl');

            // 현 모듈의 권한 목록을 가져옴
            $oModuleModel = &getModel('module');
            $xml_info = $oModuleModel->getModuleActionXml('board');
            $grant_list = $xml_info->grant;

            if(count($grant_list)) {
                foreach($grant_list as $key => $val) {
                    $group_srls = Context::get($key);
                    if($group_srls) $arr_grant[$key] = explode('|@|',$group_srls);
                }
                $grants = serialize($arr_grant);
            }

            $oModuleController = &getController('module');
            $oModuleController->updateModuleGrant($module_srl, $grants);

            $this->add('module_srl',Context::get('module_srl'));
            $this->setMessage('success_registed');
        }

        function procHomepageInsertPage() {
            $module_srl = Context::get('module_srl');

            // 현 모듈의 권한 목록을 가져옴
            $oModuleModel = &getModel('module');
            $xml_info = $oModuleModel->getModuleActionXml('page');
            $grant_list = $xml_info->grant;

            if(count($grant_list)) {
                foreach($grant_list as $key => $val) {
                    $group_srls = Context::get($key);
                    if($group_srls) $arr_grant[$key] = explode('|@|',$group_srls);
                }
                $grants = serialize($arr_grant);
            }

            $oModuleController = &getController('module');
            $oModuleController->updateModuleGrant($module_srl, $grants);

            $this->add("module_srl", $args->module_srl);
            $this->setMessage('success_registed');
        }

        function procHomepageChangeIndex() {
            $index_mid = Context::get('index_mid');
            if(!$index_mid) return new Object(-1,'msg_invalid_request');
            $args->index_module_srl = $index_mid;
            $args->domain = $this->homepage_info->domain;
            $args->site_srl= $this->site_srl;

            $oModuleController = &getController('module');
            $output = $oModuleController->updateSite($args);
            debugPrint($output);
            return $output;
        }
    }
?>
