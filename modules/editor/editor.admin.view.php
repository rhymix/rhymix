<?php
    /**
     * @class  editorAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief editor admin view of the module class
     **/

    class editorAdminView extends editor {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Administrator Setting page
         * Settings to enable/disable editor component and other features
         **/
        function dispEditorAdminIndex() {
            $site_module_info = Context::get('site_module_info');
            $site_srl = (int)$site_module_info->site_srl;
            // Get a type of component
            $oEditorModel = &getModel('editor');
            $component_list = $oEditorModel->getComponentList(false, $site_srl, true);

            Context::set('component_list', $component_list);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('admin_index');
        }

        /**
         * @brief Component setup
         **/
        function dispEditorAdminSetupComponent() {
            $site_module_info = Context::get('site_module_info');
            $site_srl = (int)$site_module_info->site_srl;

            $component_name = Context::get('component_name');
            // Get information of the editor component
            $oEditorModel = &getModel('editor');
            $component = $oEditorModel->getComponent($component_name,$site_srl);
            Context::set('component', $component);
            // Get a group list to set a group
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups($site_srl);
            Context::set('group_list', $group_list);
            // Get a mid list
            $oModuleModel = &getModel('module');

            $args->site_srl = $site_srl;
			$columnList = array('module_srl', 'mid', 'module_category_srl', 'browser_title');
            $mid_list = $oModuleModel->getMidList($args, $columnList);
            // Combination of module_category and module
            if(!$args->site_srl) {
                // Get a list of module category
                $module_categories = $oModuleModel->getModuleCategories();

                if(!is_array($mid_list)) $mid_list = array($mid_list);
                foreach($mid_list as $module_srl => $module) {
                    if($module) $module_categories[$module->module_category_srl]->list[$module_srl] = $module; 
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
