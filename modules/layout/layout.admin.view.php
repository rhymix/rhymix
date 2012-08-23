<?php
    /**
     * @class  layoutAdminView
     * @author NHN (developers@xpressengine.com)
     * admin view class of the layout module
     **/

    class layoutAdminView extends layout {

        /**
         * Initialization
		 * @return void
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

		/**
		 * Display a installed layout list
		 * @return void
		 **/
		function dispLayoutAdminInstalledList() {
			$type = Context::get('type');
			$type = ($type != 'M') ? 'P' : 'M';

			// Set a layout list
			$oLayoutModel = &getModel('layout');
			$layout_list = $oLayoutModel->getDownloadedLayoutList($type, true);
			if(!is_array($layout_list))
			{
				$layout_list = array();
			}
			
			if($type == 'P')
			{
				// get Theme layout
				$oAdminModel = &getAdminModel('admin');
				$themeList = $oAdminModel->getThemeList();
				$themeLayoutList = array();
				foreach($themeList as $themeInfo){
					if(strpos($themeInfo->layout_info->name, '.') === false) continue;
					$themeLayoutList[] = $oLayoutModel->getLayoutInfo($themeInfo->layout_info->name, null, 'P');
				}
				$layout_list = array_merge($layout_list, $themeLayoutList);
				$layout_list[] = $oLayoutModel->getLayoutInfo('faceoff', null, 'P');
			}

			$pcLayoutCount = $oLayoutModel->getInstalledLayoutCount('P');
			$mobileLayoutCount = $oLayoutModel->getInstalledLayoutCount('M');
			Context::set('pcLayoutCount', $pcLayoutCount);
			Context::set('mobileLayoutCount', $mobileLayoutCount);
			$this->setTemplateFile('installed_layout_list');

			$security = new Security($layout_list);
			$layout_list = $security->encodeHTML('..', '..author..');
			
			//Security
			$security = new Security();
			$security->encodeHTML('layout_list..layout','layout_list..title');						
			
			foreach($layout_list as $no => $layout_info)
			{
				$layout_list[$no]->description = nl2br(trim($layout_info->description));
			}
			Context::set('layout_list', $layout_list);
		}

		/**
		 * Display list of pc layout all instance
		 * @return void|Object (void : success, Object : fail)
		 */
		function dispLayoutAdminAllInstanceList()
		{
			$type = Context::get('type');

			if (!in_array($type, array('P', 'M'))) $type = 'P';

			$oLayoutModel = &getModel('layout');
			
			$pcLayoutCount = $oLayoutModel->getInstalledLayoutCount('P');
			$mobileLayoutCount = $oLayoutModel->getInstalledLayoutCount('M');
			Context::set('pcLayoutCount', $pcLayoutCount);
			Context::set('mobileLayoutCount', $mobileLayoutCount);

			$columnList = array('layout_srl', 'layout', 'module_srl', 'title', 'regdate');
			$_layout_list = $oLayoutModel->getLayoutInstanceList(0, $type, null, $columnList);

			$layout_list = array();
			foreach($_layout_list as $item)
			{
				if(!$layout_list[$item->layout])
				{
					$layout_list[$item->layout] = array();
					$layout_info = $oLayoutModel->getLayoutInfo($item->layout, null, $type);
					if ($layout_info)
					{
						$layout_list[$item->layout]['title'] = $layout_info->title; 
					}
				}

				$layout_list[$item->layout][] = $item;
			}

			Context::set('layout_list', $layout_list);

			$this->setTemplateFile('layout_all_instance_list');

			$security = new Security();
			$security->encodeHTML('layout_list..');
		}

		/**
		 * Display list of pc layout instance
		 * @return void|Object (void : success, Object : fail)
		 */
		function dispLayoutAdminInstanceList()
		{
			$type = Context::get('type');
			$layout = Context::get('layout');

			if (!in_array($type, array('P', 'M'))) $type = 'P';
			if (!$layout) return $this->stop('msg_invalid_request');

			$oLayoutModel = &getModel('layout');
			$layout_info = $oLayoutModel->getLayoutInfo($layout, null, $type);
			if (!$layout_info) return $this->stop('msg_invalid_request');

			Context::set('layout_info', $layout_info);

			$columnList = array('layout_srl', 'layout', 'module_srl', 'title', 'regdate');
			$layout_list = $oLayoutModel->getLayoutInstanceList(0, $type, $layout, $columnList);
			Context::set('layout_list', $layout_list);

			$this->setTemplateFile('layout_instance_list');

			$security = new Security();
			$security->encodeHTML('layout_list..');
		}

        /**
         * Layout setting page
         * Once select a layout with empty value in the DB, then adjust values
		 * @return void|Object (void : success, Object : fail)
         **/
        function dispLayoutAdminInsert() {
			$oModel = &getModel('layout');
			$type = Context::get('type');
			if (!in_array($type, array('P', 'M'))) $type = 'P';
			
			//Security
			$security = new Security();
			$security->encodeHTML('layout_list..layout','layout_list..title');						

			// Get layout info
			$layout = Context::get('layout');
			if ($layout == 'faceoff') return $this->stop('not supported');

			$layout_info = $oModel->getLayoutInfo($layout, null, $type);
			if (!$layout_info) return $this->stop('msg_invalid_request');

			// get Menu list
			$oMenuAdminModel = &getAdminModel('menu');
			$menu_list = $oMenuAdminModel->getMenus();
			Context::set('menu_list', $menu_list);

			$security = new Security();
			$security->encodeHTML('menu_list..');

			$security = new Security($layout_info);
			$layout_info = $security->encodeHTML('.', 'author..', 'extra_var..', 'extra_var....');

			$layout_info->description = nl2br(trim($layout_info->description));
			if (!is_object($layout_info->extra_var)) $layout_info->extra_var = new StdClass();
			foreach($layout_info->extra_var as $var_name => $val)
			{
				if (isset($layout_info->{$var_name}->description))
					$layout_info->{$var_name}->description = nl2br(trim($val->description));
			}
			Context::set('selected_layout', $layout_info);

			$this->setTemplateFile('layout_modify');
        }

		/**
         * Insert Layout details
		 * @return void
         **/
        function dispLayoutAdminModify() {
            // Set the layout after getting layout information
            $layout_srl = Context::get('layout_srl');

			// Get layout information
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);

			// Error appears if there is no layout information is registered
            if(!$layout_info) return $this->stop('msg_invalid_request');

            // If faceoff, no need to display the path
            if($layout_info->type == 'faceoff') unset($layout_info->path);

            // Get a menu list
            $oMenuAdminModel = &getAdminModel('menu');
            $menu_list = $oMenuAdminModel->getMenus();
            Context::set('menu_list', $menu_list);

			$security = new Security();
			$security->encodeHTML('menu_list..');

			$security = new Security($layout_info);
			$layout_info = $security->encodeHTML('.', 'author..', 'extra_var..');

			$layout_info->description = nl2br(trim($layout_info->description));
			if (!is_object($layout_info->extra_var)) $layout_info->extra_var = new StdClass();
			foreach($layout_info->extra_var as $var_name => $val)
			{
				if (isset($layout_info->{$var_name}->description))
					$layout_info->{$var_name}->description = nl2br(trim($val->description));
			}
			Context::set('selected_layout', $layout_info);

			$this->setTemplateFile('layout_modify');
        }

        /**
         * The first page of the layout admin
		 * @deprecated
		 * @return void|Object (void : success, Object : fail)
         **/
        function dispLayoutAdminContent() {
			$path = Context::get('path');
			if (!$path) return $this->stop('msg_invalid_request');

            $oLayoutModel = &getModel('layout');
			$columnList = array('layout_srl', 'layout', 'module_srl', 'title', 'regdate');
            $layout_list = $oLayoutModel->getLayoutList(0, 'P', $columnList);
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('index');
        }

        /**
         * The first page of the mobile layout admin
		 * @deprecated
		 * @return void
         **/
		function dispLayoutAdminMobileContent() {
            $oLayoutModel = &getModel('layout');
			$columnList = array('layout_srl', 'layout', 'module_srl', 'title', 'regdate');
            $layout_list = $oLayoutModel->getLayoutList(0, 'M', $columnList);
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('mindex');

		}

        /**
         * Edit layout codes
		 * @return void
         **/
        function dispLayoutAdminEdit() {
            // Set the layout with its information
            $layout_srl = Context::get('layout_srl');
            // Get layout information
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);
            // Error appears if there is no layout information is registered
            if(!$layout_info) return $this->dispLayoutAdminInstalledList();

            // Get Layout Code
            $oLayoutModel = &getModel('layout');
            $layout_file = $oLayoutModel->getUserLayoutHtml($layout_info->layout_srl);
            if(!file_exists($layout_file)){
                // If faceoff
                if($oLayoutModel->useDefaultLayout($layout_info->layout_srl)){
                    $layout_file  = $oLayoutModel->getDefaultLayoutHtml($layout_info->layout);
                }else{
                    $layout_file = sprintf('%s%s', $layout_info->path, 'layout.html');
                }
            }

            $layout_css_file = $oLayoutModel->getUserLayoutCss($layout_info->layout_srl);
            if(file_exists($layout_css_file)){
                $layout_code_css = FileHandler::readFile($layout_css_file);
                Context::set('layout_code_css', $layout_code_css);
            }

            $layout_code = FileHandler::readFile($layout_file);
            Context::set('layout_code', $layout_code);

            // set User Images
            $layout_image_list = $oLayoutModel->getUserLayoutImageList($layout_info->layout_srl);
            Context::set('layout_image_list', $layout_image_list);

            $layout_image_path = $oLayoutModel->getUserLayoutImagePath($layout_info->layout_srl);
            Context::set('layout_image_path', $layout_image_path);
            // Set widget list
            $oWidgetModel = &getModel('widget');
            $widget_list = $oWidgetModel->getDownloadedWidgetList();
            Context::set('widget_list', $widget_list);

            $this->setTemplateFile('layout_edit');

			$security = new Security($layout_info);
			$layout_info = $security->encodeHTML('.', '.author..');
			Context::set('selected_layout', $layout_info);
			
			//Security
			$security = new Security();
			$security->encodeHTML('layout_list..');	
			$security->encodeHTML('layout_list..author..');	
			
			$security = new Security();
			$security->encodeHTML('layout_code_css', 'layout_code', 'widget_list..title');
        }

        /**
         * Preview a layout
		 * @return void|Object (void : success, Object : fail)
         **/
        function dispLayoutAdminPreview() {
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

        /**
         * Pop-up details of the layout(conf/info.xml)
		 * @deprecated
		 * @return void
         **/
        function dispLayoutAdminInfo() {
            // Get the layout information
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayoutInfo(Context::get('selected_layout'));
            Context::set('layout_info', $layout_info);
            // Set the layout to be pop-up
            $this->setLayoutFile('popup_layout');			
			
			$security = new Security();
			$security->encodeHTML('layout_list..');	
			$security->encodeHTML('layout_list..author..');				
			$security->encodeHTML('layout_list..history..');
			$security->encodeHTML('layout_list..history..author..');				
            // Set a template file
            $this->setTemplateFile('layout_detail_info');
        }

        /**
         * Modify admin layout of faceoff
		 * @deprecated
		 * @return void
         **/
        function dispLayoutAdminLayoutModify(){
            // Get layout_srl
            $current_module_info = Context::get('current_module_info');
            $layout_srl = $current_module_info->layout_srl;
            // Remove the remaining tmp files because of temporarily saving
            // This part needs to be modified
            $delete_tmp = Context::get('delete_tmp');
            if($delete_tmp =='Y'){
                $oLayoutAdminController = &getAdminController('layout');
                $oLayoutAdminController->deleteUserLayoutTempFile($layout_srl);
            }

            $oLayoutModel = &getModel('layout');
            // layout file is used as a temp.
            $oLayoutModel->setUseUserLayoutTemp();
            // Apply CSS in inline style
            $faceoffcss = $oLayoutModel->_getUserLayoutFaceOffCss($current_module_info->layout_srl);

            $css = FileHandler::readFile($faceoffcss);
            $match = null;
            preg_match_all('/([^\{]+)\{([^\}]*)\}/is',$css,$match);
            for($i=0,$c=count($match[1]);$i<$c;$i++) {
                $name = trim($match[1][$i]);
                $css = trim($match[2][$i]);
                if(!$css) continue;
                $css = str_replace('./images/',Context::getRequestUri().$oLayoutModel->getUserLayoutImagePath($layout_srl),$css);
                $style[] .= sprintf('"%s":"%s"',$name,$css);
            }

            if(count($style)) {
                $script = '<script type="text/javascript"> var faceOffStyle = {'.implode(',',$style).'}; </script>';
                Context::addHtmlHeader($script);
            }

            $oTemplate = &TemplateHandler::getInstance();
            Context::set('content', $oTemplate->compile($this->module_path.'tpl','about_faceoff'));
            // Change widget codes in Javascript mode
            $oWidgetController = &getController('widget');
            $oWidgetController->setWidgetCodeInJavascriptMode();
            // Set a template file
            $this->setTemplateFile('faceoff_layout_edit');
        }

        /**
         * display used images info for faceoff
		 * @deprecated
		 * @return void
         **/
        function dispLayoutAdminLayoutImageList(){
            $layout_srl = Context::get('layout_srl');
            $oLayoutModel = &getModel('layout');
            // Image List
            $layout_image_list = $oLayoutModel->getUserLayoutImageList($layout_srl);
            Context::set('layout_image_list',$layout_image_list);
            // Path
            $layout_image_path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
            Context::set('layout_image_path',$layout_image_path);

            $this->setLayoutFile('popup_layout');

            $this->setTemplateFile('layout_image_list');
        }

        /**
         * Copy layout instance
		 * @return void
         */
        function dispLayoutAdminCopyLayout(){
			$layoutSrl = Context::get('layout_srl');

			$oLayoutModel = &getModel('layout');
			$layout = $oLayoutModel->getLayout($layoutSrl);

            Context::set('layout', $layout);
            $this->setTemplateFile('copy_layout');
        }
    }
?>
