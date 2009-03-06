<?php
    /**
     * @class  moduleView
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 view class
     **/

    class moduleView extends module {

        /**
         * @brief 초기화
         **/
        function init() {
            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 스킨 정보 출력
         **/
        function dispModuleSkinInfo() {
            $selected_module = Context::get('selected_module');
            $skin = Context::get('skin');

            // 모듈/스킨 정보를 구함
            $module_path = sprintf("./modules/%s/", $selected_module);
            if(!is_dir($module_path)) $this->stop("msg_invalid_request");

            $skin_info_xml = sprintf("%sskins/%s/skin.xml", $module_path, $skin);
            if(!file_exists($skin_info_xml)) $this->stop("msg_invalid_request");

            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);
            Context::set('skin_info',$skin_info);

            $this->setLayoutFile("popup_layout");
            $this->setTemplateFile("skin_info");
        }

        /**
         * @brief 모듈 선택기
         **/
        function dispModuleSelectList() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_permitted');

            $oModuleModel = &getModel('module');

            // virtual site의 개수를 추출
            $output = executeQuery('module.getSiteCount');
            $site_count = $output->data->count;
            Context::set('site_count', $site_count);

            // 사이트 검색어 변수 설정
            $site_keyword = Context::get('site_keyword');

            // 사이트 검색어가 없으면 현재 가상 사이트의 정보를 설정
            $args = null;
            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y') {
                $query_id = 'module.getSiteModules';
                $module_category_exists = false;
                if(!$site_keyword) {
                    $site_module_info = Context::get('site_module_info');
                    if($site_module_info && $logged_info->is_admin != 'Y') {
                        $site_keyword = $site_module_info->domain;
                        $args->site_srl = (int)$site_module_info->site_srl;
                        Context::set('site_keyword', $site_keyword);
                    } else {
                        $query_id = 'module.getDefaultModules';
                        $args->site_srl = 0;
                        $module_category_exists = true;
                    }
                // 사이트 검색어가 있으면 해당 사이트(들)의 정보를 추출
                } else {
                    $args->site_keyword = $site_keyword;
                }
            } else {
                $query_id = 'module.getSiteModules';
                $site_module_info = Context::get('site_module_info');
                $args->site_srl = (int)$site_module_info->site_srl;
            }
            //if(is_null($args->site_srl)) $query_id = 'module.getDefaultModules';

            // 지정된 사이트(혹은 전체)의 module 목록을 구함
            $output = executeQueryArray($query_id, $args);
            $category_list = $mid_list = array();
            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    $module = trim($val->module);
                    if(!$module) continue;

                    $category = $val->category;
                    $obj = null;
                    $obj->module_srl = $val->module_srl;
                    $obj->browser_title = $val->browser_title;
                    $mid_list[$module]->list[$category][$val->mid] = $obj;
                }
            }

            $selected_module = Context::get('selected_module');
            if(count($mid_list)) {
                foreach($mid_list as $module => $val) {
                    if(!$selected_module) $selected_module = $module;
                    $xml_info = $oModuleModel->getModuleInfoXml($module);
                    $mid_list[$module]->title = $xml_info->title;
                }
            }

            Context::set('mid_list', $mid_list);
            Context::set('selected_module', $selected_module);
            Context::set('selected_mids', $mid_list[$selected_module]->list);
            Context::set('module_category_exists', $module_category_exists);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('module_selector');
        }


        // 파일 박스 보기
        function dispModuleFileBox(){
            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return new Object(-1, 'msg_not_permitted');

            $input_name = Context::get('input');

            if(!$input_name) return new Object(-1, 'msg_not_permitted');


            $addscript = sprintf('<script type="text/javascript">//<![CDATA[
                            var selected_filebox_input_name = "%s";
                          //]]></script>',$input_name);
            Context::addHtmlHeader($addscript);

            $oModuleModel = &getModel('module');
            $output = $oModuleModel->getModuleFileBoxList();
            Context::set('filebox_list', $output->data);

            $filter = Context::get('filter');
            if($filter) Context::set('arrfilter',explode(',',$filter));

            Context::set('page_navigation', $output->page_navigation);
            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('filebox_list');
        }

        // 파일 박스 등록화면
        function dispModuleFileBoxAdd(){
            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return new Object(-1, 'msg_not_permitted');

            $filter = Context::get('filter');
            if($filter) Context::set('arrfilter',explode(',',$filter));

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('filebox_add');
        }
    }
?>
