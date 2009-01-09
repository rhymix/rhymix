<?php
    /**
     * @class  moduleAdminView
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 admin view class
     **/

    class moduleAdminView extends module {

        /**
         * @brief 초기화
         **/
        function init() {
            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 모듈 관리자 페이지
         **/
        function dispModuleAdminContent() {
            $this->dispModuleAdminList();
        }

        /**
         * @brief 모듈 목록 출력
         **/
        function dispModuleAdminList() {
            // 모듈 목록을 구해서 
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();
            Context::set('module_list', $module_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('module_list');
        }

        /**
         * @brief 모듈의 상세 정보(conf/info.xml)를 팝업 출력
         **/
        function dispModuleAdminInfo() {
            // 모듈 목록을 구해서 
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoXml(Context::get('selected_module'));
            Context::set('module_info', $module_info);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('module_info');
        }

        /**
         * @brief 모듈 카테고리 목록
         **/
        function dispModuleAdminCategory() {
            $module_category_srl = Context::get('module_category_srl');
            
            // 모듈 목록을 구해서 
            $oModuleModel = &getModel('module');

            // 선택된 카테고리가 있으면 해당 카테고리의 정보 수정 페이지로
            if($module_category_srl) {
                $selected_category  = $oModuleModel->getModuleCategory($module_category_srl);
                Context::set('selected_category', $selected_category);

                // 템플릿 파일 지정
                $this->setTemplateFile('category_update_form');

            // 아니면 전체 목록
            } else {
                $category_list = $oModuleModel->getModuleCategories();
                Context::set('category_list', $category_list);

                // 템플릿 파일 지정
                $this->setTemplateFile('category_list');
            }
        }

        /**
         * @brief 모듈 복사 기능
         **/
        function dispModuleAdminCopyModule() {
            // 복사하려는 대상 모듈을 구함
            $module_srl = Context::get('module_srl');

            // 해당 모듈의 정보를 구함
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            Context::set('module_info', $module_info);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('copy_module');
        }

        /**
         * @brief 모듈 선택기
         **/
        function dispModuleAdminSelectList() {
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

    }
?>
