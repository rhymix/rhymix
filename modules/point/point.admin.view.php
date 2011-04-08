<?php
    /**
     * @class  pointAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief The admin view class of the point module
     **/

    class pointAdminView extends point {

        /**
         * @brief Initialization
         **/
        function init() {
            // Get teh configuration information
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            // Set the configuration variable
            Context::set('config', $config);
            // Set the template path
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief Default configurations
         **/
        function dispPointAdminConfig() {
            // Get the list of level icons
            $level_icon_list = FileHandler::readDir("./modules/point/icons");
            Context::set('level_icon_list', $level_icon_list);
            // Get the list of groups
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            $selected_group_list = array();
            if(count($group_list)) {
                foreach($group_list as $key => $val) {
                    if($val->is_admin == 'Y' || $val->is_default == 'Y') continue;    
                    $selected_group_list[$key] = $val;
                }
            }
            Context::set('group_list', $selected_group_list);
            // Set the template
            $this->setTemplateFile('config');
        }

        /**
         * @brief Set per-module scores
         **/
        function dispPointAdminModuleConfig() {
            // Get a list of mid
            $oModuleModel = &getModel('module');
            $mid_list = $oModuleModel->getMidList();
            Context::set('mid_list', $mid_list);

            Context::set('module_config', $oModuleModel->getModulePartConfigs('point'));
            // Set the template
            $this->setTemplateFile('module_config');
        }

        /**
         * @brief Configure the functional act
         **/
        function dispPointAdminActConfig() {
            // Set the template
            $this->setTemplateFile('action_config');
        }

        /**
         * @brief Get a list of member points
         **/
        function dispPointAdminPointList() {
            $oPointModel = &getModel('point');

            $args->list_count = 20;
            $args->page = Context::get('page');

            $output = $oPointModel->getMemberList($args);
            // context::set for writing into a template 
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            // Create a member model object
            $oMemberModel = &getModel('member');
            // Get a list of groups
            $this->group_list = $oMemberModel->getGroups();
            Context::set('group_list', $this->group_list);
            // Set the template
            $this->setTemplateFile('member_list');
        }
    }
?>
