<?php
    /**
     * @class  opageAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view clas of the opage module
     **/

    class opageAdminView extends opage {

        var $module_srl = 0;
        var $list_count = 20;
        var $page_count = 10;

        /**
         * @brief Initialization
         **/
        function init() {
            // Pre-check if module_srl exists. Set module_info if exists
            $module_srl = Context::get('module_srl');
            // Create module model object
            $oModuleModel = &getModel('module');
            // Get a list of module categories
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);
			//Security
			$security = new Security();
			$security->encodeHTML('module_category..title');			

			// Get a template path (admin templates are collected on the tpl for opage)
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief Display a list of external pages
         **/
        function dispOpageAdminContent() {
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = executeQuery('opage.getOpageList', $args);
            // context setting to use a template
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('opage_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);			
			//Security
			$security = new Security();
			$security->encodeHTML('opage_list..');

			// Set a template file
            $this->setTemplateFile('index');
        }

        /**
         * @brief Form to add an external page
         **/
        function dispOpageAdminInsert() {
            // Get a list of groups
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);
            // Get a list of permissions from the module.xml
            $grant_list = $this->xml_info->grant;
            Context::set('grant_list', $grant_list);
            // Get module_srl
            $module_srl = Context::get('module_srl');
            // Get and set module information if module_srl exists
            if($module_srl) {
                $oModuleModel = &getModel('module');
				$columnList = array('module_srl', 'mid', 'module_category_srl', 'browser_title', 'layout_srl', 'use_mobile', 'mlayout_srl');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
                if($module_info->module_srl == $module_srl) Context::set('module_info',$module_info);
                else {
                    unset($module_info);
                    unset($module_srl);
                }
            }
            // Get a layout list
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layout_list);

            $mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
            Context::set('mlayout_list', $mobile_layout_list);
			//Security
			$security = new Security();
			$security->encodeHTML('module_info.');			
			$security->encodeHTML('layout_list..layout');
			$security->encodeHTML('layout_list..title');
			$security->encodeHTML('mlayout_list..layout');
			$security->encodeHTML('mlayout_list..title');						
			//group_list 및 grant는 사용되는 곳을 모르겠음.
			/*
			$security->encodeHTML('group_list..title');
			$security->encodeHTML('group_list..description');
			$security->encodeHTML('grant_list..');
			*/						

			// Set a template file
            $this->setTemplateFile('opage_insert');
        }


        /**
         * @brief Screen to delete an external page
         **/
        function dispOpageAdminDelete() {
            $module_srl = Context::get('module_srl');
            if(!$module_srl) return $this->dispContent();

            $oModuleModel = &getModel('module');
			$columnList = array('module_srl', 'mid', 'module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
            Context::set('module_info',$module_info);
			//Security
			$security = new Security();
			$security->encodeHTML('module_info.module');
			$security->encodeHTML('module_info.mid');
			$security->encodeHTML('module_info.browser_title');			

			// Set a template file
            $this->setTemplateFile('opage_delete');
        }

        /**
         * @brief Display a list of permissions
         **/
        function dispOpageAdminGrantInfo() {
			
            // Get module_srl
            $module_srl = Context::get('module_srl');
            // Get and set module information if module_srl exists
            if($module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if($module_info->module_srl == $module_srl) Context::set('module_info',$module_info);
                else {
                    unset($module_info);
                    unset($module_srl);
                }
            }

			$this->module_info = $module_info;
            // Call a page to set permission for common module
            $oModuleAdminModel = &getAdminModel('module');
            $grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
            Context::set('grant_content', $grant_content);

			//Security
			$security = new Security();
			$security->encodeHTML('module_info..');
			
            $this->setTemplateFile('grant_list');
        }
    }
?>
