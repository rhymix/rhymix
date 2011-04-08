<?php
    /**
     * @class  layoutAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view class of the layout module
     **/

    class layoutAdminView extends layout {

        /**
         * @brief Initialization
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief The first page of the layout admin
         **/
        function dispLayoutAdminContent() {
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('index');
        }

		function dispLayoutAdminMobileContent() {
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList(0, "M");
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('mindex');

		}

        /**
         * @brief Layout setting page
         * Once select a layout with empty value in the DB, then adjust values
         **/
        function dispLayoutAdminInsert() {
            // Set layout list
            $oLayoutModel = &getModel('layout');
			$layout_type = Context::get('layout_type');
            $layout_list = $oLayoutModel->getDownloadedLayoutList($layout_type);
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('insert_layout');
        }

        /**
         * @brief Insert Layout details
         **/
        function dispLayoutAdminModify() {
            // Set the layout after getting layout information
            $layout_srl = Context::get('layout_srl');
            // Get layout information
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);
            // Error appears if there is no layout information is registered
            if(!$layout_info) return $this->dispLayoutAdminContent();
            // If faceoff, no need to display the path
            if($layout_info->type == 'faceoff') unset($layout_info->path);
            Context::set('selected_layout', $layout_info);
            // Get a menu list
            $oMenuAdminModel = &getAdminModel('menu');
            $menu_list = $oMenuAdminModel->getMenus();
            Context::set('menu_list', $menu_list);

            $this->setTemplateFile('layout_modify');
        }

        /**
         * @brief Edit layout codes
         **/
        function dispLayoutAdminEdit() {
            // Set the layout with its information
            $layout_srl = Context::get('layout_srl');
            // Get layout information
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);
            // Error appears if there is no layout information is registered
            if(!$layout_info) return $this->dispLayoutAdminContent();
            Context::set('selected_layout', $layout_info);
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
        }

        /**
         * @brief Display a layout list
         **/
        function dispLayoutAdminDownloadedList() {
            // Set a layout list
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('downloaded_layout_list');
        }

		function dispLayoutAdminDownloadedMobileList() {
            // Set a layout list
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getDownloadedLayoutList(0, "M");
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('downloaded_mlayout_list');
		}

        /**
         * @brief Preview a layout
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
         * @brief Pop-up details of the layout(conf/info.xml)
         **/
        function dispLayoutAdminInfo() {
            // Get the layout information
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayoutInfo(Context::get('selected_layout'));
            Context::set('layout_info', $layout_info);
            // Set the layout to be pop-up
            $this->setLayoutFile('popup_layout');
            // Set a template file
            $this->setTemplateFile('layout_detail_info');
        }


        /**
         * @brief Modify admin layout of faceoff
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
    }
?>
