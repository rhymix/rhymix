<?php
    /**
     * @class  editorAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief editor of the module admin controller class
     **/

    class editorAdminController extends editor {

        /**
         * @brief Initialization
         **/
        function init() {
        }
		
		/**
         * @brief 컴포넌트 사용설정, 목록 순서 변경
         **/	
		function procEditorAdminCheckUseListOrder(){
			$site_module_info = Context::get('site_module_info');
			$enables = Context::get('enables');			
			$component_names = Context::get('component_names');
			$unables = array_diff($component_names, $enables);
			$componentList = array();	
			
			foreach($enables as $component_name) {
				$componentList[$component_name] = 'Y';
			}
			foreach($unables as $component_name) {
				$componentList[$component_name] = 'N';
			}

			$output = $this->editorCheckUse($componentList,$site_module_info->site_srl);			
			if(!$output->toBool()) return new Object();
			
			$output = $this->editorListOrder($component_names,$site_module_info->site_srl);
			if(!$output->toBool()) return new Object();
			
			$oEditorController = &getController('editor');
            $oEditorController->removeCache($site_module_info->site_srl);
			$this->setRedirectUrl(Context::get('error_return_url'));
		}
		
		/**
         * @brief check use component
         **/	
		function editorCheckUse($componentList, $site_srl = 0){			
			$args->site_srl = $site_srl;
			
			foreach($componentList as $componentName => $value){
				$args->component_name = $componentName;				
				$args->enabled = $value;
				if($site_srl == 0) {
					$output = executeQuery('editor.updateComponent', $args);
				} else {
					$output = executeQuery('editor.updateSiteComponent', $args);
				}
			}
			if(!$output->toBool()) return new Object();
			
			unset($componentList);
			return $output;
		}
		
		/**
         * @brief list order componet
         **/
		function editorListOrder($component_names, $site_srl = 0){		
			$args->site_srl = $site_srl;
			$list_order_num = '30';
			if(is_array($component_names)) {			
				foreach($component_names as $name){
					$args->list_order = $list_order_num;
					$args->component_name = $name;
					if($site_srl == 0) {
						$output = executeQuery('editor.updateComponent', $args);					
					} else {
						$output = executeQuery('editor.updateSiteComponent', $args);					
					}

			
					if(!$output->toBool()) return new Object();
					$list_order_num++;
				}
			}	
			unset($component_names);
			return $output;
		}

        /**
         * @brief Set components
         **/
        function procEditorAdminSetupComponent() {
            $site_module_info = Context::get('site_module_info');

            $component_name = Context::get('component_name');
            $extra_vars = Context::getRequestVars();
            unset($extra_vars->component_name);
            unset($extra_vars->module);
            unset($extra_vars->act);
            unset($extra_vars->body);

            if($extra_vars->target_group) $extra_vars->target_group = explode('|@|', $extra_vars->target_group);
            if($extra_vars->mid_list) $extra_vars->mid_list = explode('|@|', $extra_vars->mid_list);

            $args->component_name = $component_name;
            $args->extra_vars = serialize($extra_vars);
            $args->site_srl = (int)$site_module_info->site_srl;

            if(!$args->site_srl) $output = executeQuery('editor.updateComponent', $args);
            else $output = executeQuery('editor.updateSiteComponent', $args);
            if(!$output->toBool()) return $output;

            $oEditorController = &getController('editor');
            $oEditorController->removeCache($args->site_srl);

            $this->setMessage('success_updated');
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				global $lang;
				htmlHeader();
				alertScript($lang->success_updated);
				reload(true);
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
        }
		
		/**
         * @brief Config components
         **/
		 
		function procEditorAdminGeneralConfig(){
			$oModuleController = &getController('module');
			$configVars = Context::getRequestVars();
			
			$config->editor_skin = $configVars->editor_skin;
			$config->editor_height = $configVars->editor_height;
			$config->comment_editor_skin = $configVars->comment_editor_skin;
			$config->comment_editor_height = $configVars->comment_editor_height;
			$config->content_style = $configVars->content_style;
			$config->content_font = $configVars->content_font;
			$config->content_font_size= $configVars->content_font_size.'px';
			$config->sel_editor_colorset= $configVars->sel_editor_colorset;
			$config->sel_comment_editor_colorset= $configVars->sel_comment_editor_colorset;
			
			$oModuleController->insertModuleConfig('editor',$config);
			$this->setRedirectUrl(Context::get('error_return_url'));
					
		}

        /**
         * @brief Add a component to DB
         **/
        function insertComponent($component_name, $enabled = false, $site_srl = 0) {
            if($enabled) $enabled = 'Y';
            else $enabled = 'N';

            $args->component_name = $component_name;
            $args->enabled = $enabled;
            $args->site_srl = $site_srl;
            // Check if the component exists
            if(!$site_srl) $output = executeQuery('editor.isComponentInserted', $args);
            else $output = executeQuery('editor.isSiteComponentInserted', $args);
            if($output->data->count) return new Object(-1, 'msg_component_is_not_founded');
            // Inert a component
            $args->list_order = getNextSequence();
            if(!$site_srl) $output = executeQuery('editor.insertComponent', $args);
            else $output = executeQuery('editor.insertSiteComponent', $args);

            $oEditorController = &getController('editor');
            $oEditorController->removeCache($site_srl);
            return $output;
        }
    }
?>
