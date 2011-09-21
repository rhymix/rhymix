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
			$component_count = 0;
            $site_module_info = Context::get('site_module_info');
            $site_srl = (int)$site_module_info->site_srl;			
			
            // Get a type of component
            $oEditorModel = &getModel('editor');
			$oModuleModel = &getModel('module');
			$editor_config = $oModuleModel->getModuleConfig('editor');		

			//editor_config init
			if(!$editor_config->editor_height) $editor_config->editor_height = 400;
            if(!$editor_config->comment_editor_height) $editor_config->comment_editor_height = 100;
			if(!$editor_config->editor_skin) $editor_config->editor_skin = 'xpresseditor';
			if(!$editor_config->comment_editor_skin) $editor_config->comment_editor_skin = 'xpresseditor';
			if(!$editor_config->sel_editor_colorset) $editor_config->sel_editor_colorset= 'white';
			if(!$editor_config->sel_comment_editor_colorset) $editor_config->sel_comment_editor_colorset= 'white';
            
			$component_list = $oEditorModel->getComponentList(false, $site_srl, true);			
			$editor_skin_list = FileHandler::readDir(_XE_PATH_.'modules/editor/skins');
			
			$skin_info = $oModuleModel->loadSkinInfo($this->module_path,$editor_config->editor_skin);
			
			$contents = FileHandler::readDir(_XE_PATH_.'modules/editor/styles');
            for($i=0,$c=count($contents);$i<$c;$i++) {
                $style = $contents[$i];
                $info = $oModuleModel->loadSkinInfo($this->module_path,$style,'styles');
                $content_style_list[$style]->title = $info->title;
            }			
			
			// Get install info, update info, count
			$oAutoinstallModel = &getModel('autoinstall');			
            foreach($component_list as $component_name => $xml_info) {
				$component_count++;		    								
				$xml_info->path = './modules/editor/components/'.$xml_info->component_name;				
				$xml_info->delete_url = $oAutoinstallModel->getRemoveUrlByPath($xml_info->path);								
				$xml_info->package_srl = $oAutoinstallModel->getPackageSrlByPath($xml_info->path);
				if($xml_info->package_srl) $targetpackages[$xml_info->package_srl] = 0;
            }	
			
			if(is_array($targetpackages))	$packages = $oAutoinstallModel->getInstalledPackages(array_keys($targetpackages));			
			
			foreach($component_list as $component_name => $xml_info) {
				if($packages[$xml_info->package_srl])	$xml_info->need_update = $packages[$xml_info->package_srl]->need_update;
			}
			$editor_config_default = array( "editor_height" => "400", "comment_editor_height" => "100","content_font_size"=>"12");
			
			Context::set('editor_config', $editor_config);
			Context::set('editor_skin_list', $editor_skin_list);
			Context::set('editor_colorset_list', $skin_info->colorset);
			Context::set('content_style_list', $content_style_list);
			Context::set('component_list', $component_list);
			Context::set('component_count', $component_count);						
			Context::set('editor_config_default', $editor_config_default);
			 
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
