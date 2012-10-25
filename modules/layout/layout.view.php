<?php
    /**
     * @class  layoutView
     * @author NHN (developers@xpressengine.com)
     * admin view class of the layout module
     **/

    class layoutView extends layout {

        /**
         * Initialization
		 * @return void
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * Pop-up layout details(conf/info.xml)
		 * @return void
         **/
        function dispLayoutInfo() {
            // Get the layout information
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayoutInfo(Context::get('selected_layout'));
            if(!$layout_info) exit();
            Context::set('layout_info', $layout_info);
            // Set the layout to be pop-up
            $this->setLayoutFile('popup_layout');
            // Set a template file
            $this->setTemplateFile('layout_detail_info');
        }

		/**
		 * Preview a layout with module.
		 * 
		 * @return Object
		 **/
		public function dispLayoutPreviewWithModule()
		{
			// admin check
			// this act is admin view but in normal view because do not load admin css/js files
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')
			{
				return $this->stop('msg_invalid_request');
			}

			$layoutSrl = Context::get('layout_srl');
			$layoutVars = Context::get('layout_vars');
			$layoutVars = json_decode($layoutVars);

			$moduleSrl = Context::get('target_module_srl');
			$module = Context::get('module_name');
			$mid = Context::get('target_mid');
			$skin = Context::get('skin');
			$skinVars = Context::get('skin_vars');
			$skinVars = json_decode($skinVars);

			$skinType = Context::get('skin_type');
			$type = ($skinType == 'M') ? 'mobile' : 'view';

			if($module == 'ARTICLE')
			{
				$module = 'page';
				$page_type = 'ARTICLE';
				$document_srl = 0;
			}

			if($module)
			{
				$oModuleModel = getModel('module');
				$xml_info = $oModuleModel->getModuleActionXml($module);
				//create content
				if(!$mid && !$moduleSrl)
				{
					if($skin && !$module)
					{
						return $this->stop(-1, 'msg_invalid_request');
					}

					$oModule = ModuleHandler::getModuleInstance($module, $type);
					$oModule->setAct($xml_info->default_index_act);
					$module_info->module = $module;
					$module_info->module_type = $type;
					$module_info->page_type = $page_type;
					$module_info->document_srl= $document_srl;
					$oModule->setModuleInfo($this->module_info, $xml_info);
					$oModule->proc();
				}
				else
				{
					$oModuleHandler = new ModuleHandler($module, '', $mid, '', $moduleSrl);
					$oModuleHandler->act = '';
					$oModuleHandler->init();
					$oModule = $oModuleHandler->procModule();
				}

				if($oModule->toBool())
				{
					if($skin)
					{
						$skinDir = ($skinType == 'M') ? 'm.skins' : 'skins';
						$template_path = sprintf("%s%s/%s/",$oModule->module_path, $skinDir, $skin);
						$oModule->setTemplatePath($template_path);

						if(is_array($skinVars))
						{
							foreach($skinVars as $key => $val)
							{
								$oModule->module_info->{$key} = $val;
							}
						}
					}

					require_once("./classes/display/HTMLDisplayHandler.php");
					$handler = new HTMLDisplayHandler();
					$output = $handler->toDoc($oModule);
					Context::set('content', $output);
				}
				else
				{
					Context::set('content', Context::getLang('not_support_layout_preview'));
				}
			}
			else
			{
				Context::set('content', Context::getLang('layout_preview_content'));
			}

			if($layoutSrl)
			{
				if($layoutSrl == -1)
				{
					$site_srl = ($oModule) ? $oModule->module_info->site_srl : 0;
					$designInfoFile = sprintf(_XE_PATH_.'/files/site_design/design_%s.php', $site_srl);
					@include($designInfoFile);
					$layoutSrl = $designInfo->layout_srl;
				}

				$oLayoutModel = getModel('layout');
				$layoutInfo = $oLayoutModel->getLayout($layoutSrl);

				if(!$layoutInfo) 
				{
					return new Object(-1, 'msg_invalid_request');
				}

				// Set names and values of extra_vars to $layout_info
				if($layoutInfo->extra_var_count) 
				{
					foreach($layoutInfo->extra_var as $var_id => $val) 
					{
						$layoutInfo->{$var_id} = $val->value;
					}
				}

				if($layoutVars)
				{
					foreach($layoutVars as $key => $val) 
					{
						$layoutInfo->{$key} = $val;
					}
				}

				// menu in layout information becomes an argument for Context:: set
				if($layoutInfo->menu_count) 
				{
					foreach($layoutInfo->menu as $menu_id => $menu) 
					{
						if(file_exists($menu->php_file)) @include($menu->php_file);
						Context::set($menu_id, $menu);
					}
				}

				Context::set('layout_info', $layoutInfo);
			}

			// Compile
			$oTemplate = &TemplateHandler::getInstance();
			Context::clearHtmlHeader();
			if($layoutSrl)
			{
				$layout_path = $layoutInfo->path;
				$layout_file = 'layout';
				$oModuleModel = getModel('module');
                $part_config= $oModuleModel->getModulePartConfig('layout',$layoutSrl);
                Context::addHtmlHeader($part_config->header_script);
			}
			else
			{
				$layout_path = './common/tpl';
				$layout_file = 'default_layout';
			}
			$layout_tpl = $oTemplate->compile($layout_path, $layout_file);
			Context::set('layout','none');

			// Convert widgets and others
			$oContext = &Context::getInstance();
			Context::set('layout_tpl', $layout_tpl);
            $this->setTemplatePath($this->module_path.'tpl');
			$this->setTemplateFile('layout_preview');
		}
		/**
         * Preview a layout
		 * @return void|Object (void : success, Object : fail)
         **/
        function dispLayoutPreview() 
		{
			// admin check
			// this act is admin view but in normal view because do not load admin css/js files
			$logged_info = Context::get('logged_info');
			if ($logged_info->is_admin != 'Y') return $this->stop('msg_invalid_request');

            $layout_srl = Context::get('layout_srl');
            $code = Context::get('code');

            $code_css = Context::get('code_css');
            if(!$layout_srl || !$code) return new Object(-1, 'msg_invalid_request');
            // Get the layout information
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);
            if(!$layout_info) return new Object(-1, 'msg_invalid_request');
            // Separately handle the layout if its type is faceoff
            if($layout_info && $layout_info->type == 'faceoff') $oLayoutModel->doActivateFaceOff($layout_info);
            // Apply CSS directly
            Context::addHtmlHeader("<style type=\"text/css\" charset=\"UTF-8\">".$code_css."</style>");
            // Set names and values of extra_vars to $layout_info
            if($layout_info->extra_var_count) {
                foreach($layout_info->extra_var as $var_id => $val) {
                    $layout_info->{$var_id} = $val->value;
                }
            }
            // menu in layout information becomes an argument for Context:: set
            if($layout_info->menu_count) {
                foreach($layout_info->menu as $menu_id => $menu) {
                    if(file_exists($menu->php_file)) @include($menu->php_file);
                    Context::set($menu_id, $menu);
                }
            }

            Context::set('layout_info', $layout_info);
            Context::set('content', Context::getLang('layout_preview_content'));
            // Temporary save the codes
            $edited_layout_file = sprintf('./files/cache/layout/tmp.tpl');
            FileHandler::writeFile($edited_layout_file, $code);

            // Compile
            $oTemplate = &TemplateHandler::getInstance();

            $layout_path = $layout_info->path;
            $layout_file = 'layout';

            $layout_tpl = $oTemplate->compile($layout_path, $layout_file, $edited_layout_file);
            Context::set('layout','none');
            // Convert widgets and others
            $oContext = &Context::getInstance();
            Context::set('layout_tpl', $layout_tpl);
            // Delete Temporary Files
            FileHandler::removeFile($edited_layout_file);
            $this->setTemplateFile('layout_preview');
        }

    }
?>
