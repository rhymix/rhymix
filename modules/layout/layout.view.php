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
         * Preview a layout
		 * @return void|Object (void : success, Object : fail)
         **/
        function dispLayoutPreview() {
			// admin check
			// this act is admin view but in normal view because do not load admin css/js files
			$logged_info = Context::get('logged_info');
			if ($logged_info->is_admin != 'Y') return $this->stop('msg_invalid_request');

            $layout_srl = Context::get('layout_srl');
            $code = Context::get('code');

			$code = preg_replace('/<\?.*(\?>)?/Usm', '', $code);
			$code = preg_replace('/<script[\s]*language[\s]*=("|\')php("|\')[\s]*>.*<\/script>/Usm', '', $code);

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
