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
         * @brief 모듈 기본 설정 일괄 적용
         **/
        function dispModuleAdminModuleSetup() {
            $module_srls = Context::get('module_srls');

            $modules = explode(',',$module_srls);
            if(!count($modules)) if(!$module_srls) return new Object(-1,'msg_invalid_request');

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($modules[0]);

            // 모듈의 스킨 목록을 구함
            $skin_list = $oModuleModel->getSkins('./modules/'.$module_info->module);
            Context::set('skin_list',$skin_list);

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);

            // 모듈 카테고리 목록을 구함
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('module_setup');
        }

        /**
         * @brief 모듈 추가 설정 일괄 적용
         **/
        function dispModuleAdminModuleAdditionSetup() {
            $module_srls = Context::get('module_srls');

            $modules = explode(',',$module_srls);
            if(!count($modules)) if(!$module_srls) return new Object(-1,'msg_invalid_request');

            // content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
            $content = '';

            // 추가 설정을 위한 트리거 호출 
            // 게시판 모듈이지만 차후 다른 모듈에서의 사용도 고려하여 trigger 이름을 공용으로 사용할 수 있도록 하였음
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
            Context::set('setup_content', $content);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('module_addition_setup');
        }

        /**
         * @brief 모듈 권한 설정 일괄 적용
         **/
        function dispModuleAdminModuleGrantSetup() {
            $module_srls = Context::get('module_srls');

            $modules = explode(',',$module_srls);
            if(!count($modules)) if(!$module_srls) return new Object(-1,'msg_invalid_request');

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($modules[0]);
            $xml_info = $oModuleModel->getModuleActionXml($module_info->module);
            $source_grant_list = $xml_info->grant;

            // access, manager 권한은 가상 권한으로 설정
            $grant_list->access->title = Context::getLang('grant_access');
            $grant_list->access->default = 'guest';
            if(count($source_grant_list)) {
                foreach($source_grant_list as $key => $val) {
                    if(!$val->default) $val->default = 'guest';
                    if($val->default == 'root') $val->default = 'manager';
                    $grant_list->{$key} = $val;
                }
            }
            $grant_list->manager->title = Context::getLang('grant_manager');
            $grant_list->manager->default = 'manager';
            Context::set('grant_list', $grant_list);

            // 그룹을 가져옴
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups($module_info->site_srl);
            Context::set('group_list', $group_list);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('module_grant_setup');
        }

        /**
         * @brief 언어 코드
         **/
        function dispModuleAdminLangcode() {
            // 현재 사이트의 언어파일 가져오기
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = (int)$site_module_info->site_srl;
            $args->sort_index = 'name';
            $args->order_type = 'asc';
            $output = executeQueryArray('module.getLangList', $args);
            Context::set('lang_list', $output->data);

            // 현재 선택된 언어 가져오기
            $name = Context::get('name');
            if($name) {
                $oModuleAdminModel = &getAdminModel('module');
                Context::set('selected_lang', $oModuleAdminModel->getLangCode($args->site_srl,'$user_lang->'.$name));
            }

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('module_langcode');
        }

    }
?>
