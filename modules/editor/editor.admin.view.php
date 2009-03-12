<?php
    /**
     * @class  editorAdminView
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 admin view 클래스
     **/

    class editorAdminView extends editor {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 설정 페이지
         * 에디터 컴포넌트의 on/off 및 설정을 담당
         **/
        function dispEditorAdminIndex() {
            $site_module_info = Context::get('site_module_info');
            $site_srl = (int)$site_module_info->site_srl;

            // 컴포넌트의 종류를 구해옴
            $oEditorModel = &getModel('editor');
            $component_list = $oEditorModel->getComponentList(false, $site_srl);

            Context::set('component_list', $component_list);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('admin_index');
        }

        /**
         * @brief 컴퍼넌트 setup
         **/
        function dispEditorAdminSetupComponent() {
            $site_module_info = Context::get('site_module_info');
            $site_srl = (int)$site_module_info->site_srl;

            $component_name = Context::get('component_name');

            // 에디터 컴포넌트의 정보를 구함
            $oEditorModel = &getModel('editor');
            $component = $oEditorModel->getComponent($component_name,$site_srl);
            Context::set('component', $component);

            // 그룹 설정을 위한 그룹 목록을 구함
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups($site_srl);
            Context::set('group_list', $group_list);

            // mid 목록을 가져옴
            $oModuleModel = &getModel('module');

            $args->site_srl = $site_srl;
            $mid_list = $oModuleModel->getMidList($args);

            // module_category와 module의 조합
            if(!$args->site_srl) {
                // 모듈 카테고리 목록을 구함
                $module_categories = $oModuleModel->getModuleCategories();

                foreach($mid_list as $module_srl => $module) {
                    $module_categories[$module->module_category_srl]->list[$module_srl] = $module; 
                }
            } else {
                $module_categories[0]->list = $mid_list;
            }

            Context::set('mid_list',$module_categories);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('setup_component');
            $this->setLayoutFile("popup_layout");
        }

    }
?>
