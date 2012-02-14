<?php
    /**
     * @class  layoutAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief admin controller class of the layout module
     **/

    class layoutAdminController extends layout {

        /**
         * @brief Initialization
         **/
        function init() {
        }

		// deprecated
        /**
         * @brief Create a new layout
         * Insert a title into "layouts" table in order to create a layout
         **/
        function procLayoutAdminInsert() {
			if(Context::get('layout') == 'faceoff') return $this->stop('not supported');

            // Get information to create a layout
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = (int)$site_module_info->site_srl;
            $args->layout_srl = getNextSequence();
            $args->layout = Context::get('layout');
            $args->title = Context::get('title');
			$args->layout_type = Context::get('_layout_type');
			if(!$args->layout_type) $args->layout_type = "P";

			// Insert into the DB
            $output = $this->insertLayout($args);
            if(!$output->toBool()) return $output;

			// initiate if it is faceoff layout
            $this->initLayout($args->layout_srl, $args->layout);

			// update layout info
			Context::set('layout_srl', $args->layout_srl);
			$output = $this->procLayoutAdminUpdate();
			if (!$output->toBool()) return $output;

			$this->setRedirectUrl(Context::get('success_return_url'));
        }

        // Insert layout information into the DB
        function insertLayout($args) {
            $output = executeQuery("layout.insertLayout", $args);
            return $output;
        }

        // Initiate if it is faceoff layout
        function initLayout($layout_srl, $layout_name){
            $oLayoutModel = &getModel('layout');
            // Import a sample layout if it is faceoff
            if($oLayoutModel->useDefaultLayout($layout_name)) {
                $this->importLayout($layout_srl, $this->module_path.'tpl/faceOff_sample.tar');
            // Remove a directory
            } else {
                FileHandler::removeDir($oLayoutModel->getUserLayoutPath($layout_srl));
            }
        }

        /**
         * @brief Update layout information
         * Apply a title of the new layout and extra vars
         **/
        function procLayoutAdminUpdate() {
            // Consider the rest of items as extra vars, except module, act, layout_srl, layout, and title  .. Some gurida ..
            $extra_vars = Context::getRequestVars();
            unset($extra_vars->module);
            unset($extra_vars->act);
            unset($extra_vars->layout_srl);
            unset($extra_vars->layout);
            unset($extra_vars->title);
            unset($extra_vars->apply_layout);
			unset($extra_vars->apply_mobile_view);

            $args = Context::gets('layout_srl','title');
            // Get layout information
            $oLayoutModel = &getModel('layout');
            $oMenuAdminModel = &getAdminModel('menu');
            $layout_info = $oLayoutModel->getLayout($args->layout_srl);
			if($layout_info->menu) {
	            $menus = get_object_vars($layout_info->menu);
			}
            if(count($menus) ) {
                foreach($menus as $menu_id => $val) {
                    $menu_srl = Context::get($menu_id);
                    if(!$menu_srl) continue;

                    $output = $oMenuAdminModel->getMenu($menu_srl);
                    $menu_srl_list[] = $menu_srl;
                    $menu_name_list[$menu_srl] = $output->title;

					$apply_layout = Context::get('apply_layout');
					$apply_mobile_view = Context::get('apply_mobile_view');

                    if($apply_layout=='Y' || $apply_mobile_view=='Y') {
                        $menu_args = null;
                        $menu_args->menu_srl = $menu_srl;
                        $menu_args->site_srl = $layout_info->site_srl;
                        $output = executeQueryArray('layout.getLayoutModules', $menu_args);
                        if($output->data) {
                            $modules = array();
                            for($i=0;$i<count($output->data);$i++) {
                                $modules[] = $output->data[$i]->module_srl;
                            }

                            if(count($modules)) {
                                $update_args->module_srls = implode(',',$modules);
								if($apply_layout == "Y") {
									$update_args->layout_srl = $args->layout_srl;
								}
								if($layout_info->layout_type == "M")
								{
									if(Context::get('apply_mobile_view') == "Y")
									{
										$update_args->use_mobile = "Y";
									}
									$output = executeQuery('layout.updateModuleMLayout', $update_args);
								}
								else
								{
									$output = executeQuery('layout.updateModuleLayout', $update_args);
								}
                            }
                        }
                    }
                }
            }
            // Separately handle if a type of extra_vars is an image
            if($layout_info->extra_var) {
                foreach($layout_info->extra_var as $name => $vars) {
                    if($vars->type!='image') continue;

                    $image_obj = $extra_vars->{$name};
                    $extra_vars->{$name} = $layout_info->extra_var->{$name}->value;
                    // Get a variable on a request to delete
                    $del_var = $extra_vars->{"del_".$name};
                    unset($extra_vars->{"del_".$name});
                    // Delete the old file if there is a request to delete or a new file is uploaded
                    if($del_var == 'Y' || $image_obj['tmp_name']) {
                        FileHandler::removeFile($extra_vars->{$name});
                        $extra_vars->{$name} = '';
                        if($del_var == 'Y' && !$image_obj['tmp_name']) continue;
                    }
                    // Ignore if the file is not successfully uploaded
                    if(!$image_obj['tmp_name'] || !is_uploaded_file($image_obj['tmp_name'])) continue;
                    // Ignore if the file is not an image (swf the paths ~)
                    if(!preg_match("/\.(jpg|jpeg|gif|png|swf)$/i", $image_obj['name'])) continue;
                    // Upload the file to a path
                    $path = sprintf("./files/attach/images/%s/", $args->layout_srl);
                    // Create a directory
                    if(!FileHandler::makeDir($path)) continue;

                    $filename = $path.$image_obj['name'];
                    // Move the file
                    if(!move_uploaded_file($image_obj['tmp_name'], $filename)) continue;

                    $extra_vars->{$name} = $filename;
                }
            }
            // Save header script into "config" of layout module
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            $layout_config->header_script = Context::get('header_script');
            $oModuleController->insertModulePartConfig('layout',$args->layout_srl,$layout_config);
            // Save a title of the menu
            $extra_vars->menu_name_list = $menu_name_list;
            // Variable setting for DB insert
            $args->extra_vars = serialize($extra_vars);

            $output = $this->updateLayout($args);
            if(!$output->toBool()) return $output;

			$this->setRedirectUrl(Context::get('error_return_url'));
			return new Object();
        }

        function updateLayout($args) {
            $output = executeQuery('layout.updateLayout', $args);
            if($output->toBool()) {
                $oLayoutModel = &getModel('layout');
                $cache_file = $oLayoutModel->getUserLayoutCache($args->layout_srl, Context::getLangType());
                FileHandler::removeFile($cache_file);
				//remove from cache
                $oCacheHandler = &CacheHandler::getInstance('object');
                if($oCacheHandler->isSupport())
                {
                	$cache_key = 'object:'.$args->layout_srl;
                	$oCacheHandler->delete($cache_key);
                }
            }
            return $output;
        }

        /**
         * @brief Delete Layout
         * Delete xml cache file too when deleting a layout
         **/
        function procLayoutAdminDelete() {
            $layout_srl = Context::get('layout_srl');
			$this->setRedirectUrl(Context::get('error_return_url'));
            return $this->deleteLayout($layout_srl);
        }

        function deleteLayout($layout_srl) {
            $oLayoutModel = &getModel('layout');

            $path = $oLayoutModel->getUserLayoutPath($layout_srl);
            FileHandler::removeDir($path);

            $layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
            if(file_exists($layout_file)) FileHandler::removeFile($layout_file);
            // Delete Layout
            $args->layout_srl = $layout_srl;
            $output = executeQuery("layout.deleteLayout", $args);
			//remove from cache
            $oCacheHandler = &CacheHandler::getInstance('object');
            if($oCacheHandler->isSupport())
            {
            	$cache_key = 'object:'.$layout_srl;
            	$oCacheHandler->delete($cache_key);
            }
            if(!$output->toBool()) return $output;

            return new Object(0,'success_deleted');
        }

        /**
         * @brief Adding Layout Code
         **/
        function procLayoutAdminCodeUpdate() {
			$mode = Context::get('mode');
			if ($mode == 'reset')
			{
				return $this->procLayoutAdminCodeReset();
			}

            $layout_srl = Context::get('layout_srl');
            $code = Context::get('code');
            $code_css   = Context::get('code_css');
			$is_post    = (Context::getRequestMethod() == 'POST');
            if(!$layout_srl || !$code || !$is_post)
			{
				return new Object(-1, 'msg_invalid_request');
			}

			$code = preg_replace('/<\?.*(\?>)?/sm', '', $code);

            $oLayoutModel = &getModel('layout');
            $layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
            FileHandler::writeFile($layout_file, $code);

            $layout_css_file = $oLayoutModel->getUserLayoutCss($layout_srl);
            FileHandler::writeFile($layout_css_file, $code_css);

			$this->setRedirectUrl(Context::get('error_return_url'));
			$this->setMessage('success_updated');
        }

        /**
         * @brief Reset layout code
         **/
        function procLayoutAdminCodeReset() {
            $layout_srl = Context::get('layout_srl');
            if(!$layout_srl) return new Object(-1, 'msg_invalid_request');

            // delete user layout file
            $oLayoutModel = &getModel('layout');
            $layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
            FileHandler::removeFile($layout_file);

            $info = $oLayoutModel->getLayout($layout_srl);

            // if face off delete, tmp file
            if($oLayoutModel->useDefaultLayout($info->layout)){
                $this->deleteUserLayoutTempFile($layout_srl);
                $faceoff_css = $oLayoutModel->getUserLayoutFaceOffCss($layout_srl);
                FileHandler::removeFile($faceoff_css);
            }

            $this->initLayout($layout_srl, $info->layout);
            $this->setMessage('success_reset');
			$this->setRedirectUrl(Context::get('error_return_url'));
        }


        /**
         * @brief Layout setting page -> Upload an image
         *
         **/
        function procLayoutAdminUserImageUpload(){
            if(!Context::isUploaded()) exit();

            $image = Context::get('user_layout_image');
            $layout_srl = Context::get('layout_srl');
            if(!is_uploaded_file($image['tmp_name'])) exit();

            $this->insertUserLayoutImage($layout_srl, $image);
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");

			$this->setRedirectUrl(Context::get('error_return_url'));
        }

        /**
         * @brief Layout setting page -> Upload an image
         *
         **/
        function insertUserLayoutImage($layout_srl,$source){
            $oLayoutModel = &getModel('layout');
            $path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
            if(!is_dir($path)) FileHandler::makeDir($path);

            $filename = strtolower($source['name']);
            if($filename != urlencode($filename)){
                $ext = substr(strrchr($filename,'.'),1);
                $filename = sprintf('%s.%s', md5($filename), $ext);
            }

            if(file_exists($path .'/'. $filename)) @unlink($path . $filename);
            if(!move_uploaded_file($source['tmp_name'], $path . $filename )) return false;
            return true;
        }


        /**
         * @brief Layout setting page -> Delete an image
         *
         **/
        function removeUserLayoutImage($layout_srl,$filename){
            $oLayoutModel = &getModel('layout');
            $path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
            @unlink($path . $filename);
        }

        /**
         * @brief Layout setting page -> Delete an image
         *
         **/
        function procLayoutAdminUserImageDelete(){
            $filename = Context::get('filename');
            $layout_srl = Context::get('layout_srl');
            $this->removeUserLayoutImage($layout_srl,$filename);
            $this->setMessage('success_deleted');
			$this->setRedirectUrl(Context::get('error_return_url'));
        }

		// deprecated
        /**
         * @brief Save layout configuration
         * save in "ini" format for faceoff
         **/
        function procLayoutAdminUserValueInsert(){
            $oModuleModel = &getModel('module');

            $mid = Context::get('mid');
            if(!$mid) return new Object(-1, 'msg_invalid_request');

            $site_module_info = Context::get('site_module_info');
			$columnList = array('layout_srl');
            $module_info = $oModuleModel->getModuleInfoByMid($mid, $site_module_info->site_srl, $columnList);
            $layout_srl = $module_info->layout_srl;
            if(!$layout_srl) return new Object(-1, 'msg_invalid_request');

            $oLayoutModel = &getModel('layout');

            // save tmp?
            $temp = Context::get('saveTemp');
            if($temp =='Y'){
                $oLayoutModel->setUseUserLayoutTemp();
            }else{
                // delete temp files
                $this->deleteUserLayoutTempFile($layout_srl);
            }

            $this->add('saveTemp',$temp);

            // write user layout
            $extension_obj = Context::gets('e1','e2','neck','knee');

            $file = $oLayoutModel->getUserLayoutHtml($layout_srl);
            $content = FileHandler::readFile($file);
            $content = $this->addExtension($layout_srl,$extension_obj,$content);
            FileHandler::writeFile($file,$content);

            // write faceoff.css
            $css = stripslashes(Context::get('css'));

            $css_file = $oLayoutModel->getUserLayoutFaceOffCss($layout_srl);
            FileHandler::writeFile($css_file,$css);

            // write ini
            $obj = Context::gets('type','align','column');
            $obj = (array)$obj;
            $src = $oLayoutModel->getUserLayoutIniConfig($layout_srl);
            foreach($obj as $key => $val) $src[$key] = $val;
            $this->insertUserLayoutValue($layout_srl,$src);
        }

        /**
         * @brief Layout setting, save "ini"
         *
         **/
        function insertUserLayoutValue($layout_srl,$arr){
            $oLayoutModel = &getModel('layout');
            $file = $oLayoutModel->getUserLayoutIni($layout_srl);
            FileHandler::writeIniFile($file, $arr);
        }

        function writeUserLayoutCss(){

        }

        /**
         * @brief Add the widget code for faceoff into user layout file
         *
         **/
        function addExtension($layout_srl,$arg,$content){
            $oLayoutModel = &getModel('layout');
             $reg = '/(<\!\-\- start\-e1 \-\->)(.*)(<\!\-\- end\-e1 \-\->)/i';
             $extension_content =  '\1' .stripslashes($arg->e1) . '\3';
             $content = preg_replace($reg,$extension_content,$content);

             $reg = '/(<\!\-\- start\-e2 \-\->)(.*)(<\!\-\- end\-e2 \-\->)/i';
             $extension_content =  '\1' .stripslashes($arg->e2) . '\3';
             $content = preg_replace($reg,$extension_content,$content);

             $reg = '/(<\!\-\- start\-neck \-\->)(.*)(<\!\-\- end\-neck \-\->)/i';
             $extension_content =  '\1' .stripslashes($arg->neck) . '\3';
             $content = preg_replace($reg,$extension_content,$content);

             $reg = '/(<\!\-\- start\-knee \-\->)(.*)(<\!\-\- end\-knee \-\->)/i';
             $extension_content =  '\1' .stripslashes($arg->knee) . '\3';
             $content = preg_replace($reg,$extension_content,$content);
            return $content;
        }


        /**
         * @brief Delete temp files for faceoff
         *
         **/
         function deleteUserLayoutTempFile($layout_srl){
             $oLayoutModel = &getModel('layout');
             $file_list = $oLayoutModel->getUserLayoutTempFileList($layout_srl);
             foreach($file_list as $key => $file){
                FileHandler::removeFile($file);
             }
         }

        /**
         * @brief faceoff export
         *
         **/
         function procLayoutAdminUserLayoutExport(){
            $layout_srl = Context::get('layout_srl');
            if(!$layout_srl) return new Object('-1','msg_invalid_request');

            require_once(_XE_PATH_.'libs/tar.class.php');
            $oLayoutModel = &getModel('layout');
			
			// Copy files to temp path
			$file_path = $oLayoutModel->getUserLayoutPath($layout_srl);
			$target_path = $oLayoutModel->getUserLayoutPath(0);
			FileHandler::copyDir($file_path, $target_path);

			// replace path and ini config
			$ini_config = $oLayoutModel->getUserLayoutIniConfig(0);
            $file_list = $oLayoutModel->getUserLayoutFileList($layout_srl);
			unset($file_list[2]);

			foreach($file_list as $file)
			{
				if (preg_match('/^images/', $file)) continue;

				// replace path
				$file = $target_path . $file;
				$content = FileHandler::readFile($file);
				$pattern = '/(http:\/\/[^ ]+)?(\.\/)?' . str_replace('/', '\/', (str_replace('./', '', $file_path))) . '/';
				if (basename($file) == 'faceoff.css' || basename($file) == 'layout.css')
					$content = preg_replace($pattern, '../', $content);
				else
					$content = preg_replace($pattern, './', $content);

				// replace ini config
				foreach($ini_config as $key => $value)
				{
					$content = str_replace('{$layout_info->faceoff_ini_config[\'' . $key . '\']}', $value, $content);
				}

				FileHandler::writeFile($file, $content);
			}

			// make info.xml
			$info_file = $target_path . 'conf/info.xml';
			FileHandler::copyFile('./modules/layout/faceoff/conf/info.xml', $info_file);
			$content = FileHandler::readFile($info_file);
			$content = str_replace('type="faceoff"', '', $content);
			FileHandler::writeFile($info_file, $content);
			$file_list[] = 'conf/info.xml';

			// make css file
			$css_file = $target_path . 'css/layout.css';
			FileHandler::copyFile('./modules/layout/faceoff/css/layout.css', $css_file);
			$content = FileHandler::readFile('./modules/layout/tpl/css/widget.css');
			FileHandler::writeFile($css_file, "\n" . $content, 'a');
			$content = FileHandler::readFile($target_path . 'faceoff.css');
			FileHandler::writeFile($css_file, "\n" . $content, 'a');
			$content = FileHandler::readFile($target_path . 'layout.css');
			FileHandler::writeFile($css_file, "\n" . $content, 'a');

			// css load
			$content = FileHandler::readFile($target_path . 'layout.html');
			$content = "<load target=\"css/layout.css\" />\n" . $content;
			FileHandler::writeFile($target_path . 'layout.html', $content);
			unset($file_list[3]);
			unset($file_list[1]);
			$file_list[] = 'css/layout.css';

            // Compress the files
            $tar = new tar();
            $user_layout_path = FileHandler::getRealPath($oLayoutModel->getUserLayoutPath(0));
            chdir($user_layout_path);
            foreach($file_list as $key => $file) $tar->addFile($file);

            $stream = $tar->toTarStream();
            $filename = 'faceoff_' . date('YmdHis') . '.tar';
            header("Cache-Control: ");
            header("Pragma: ");
            header("Content-Type: application/x-compressed");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
//            header("Content-Length: " .strlen($stream)); ?? why??
            header('Content-Disposition: attachment; filename="'. $filename .'"');
            header("Content-Transfer-Encoding: binary\n");
            echo $stream;
            // Close Context and then exit
            Context::close();

			// delete temp path
			FileHandler::removeDir($target_path);

            exit();
         }

		// deprecated
        /**
         * @brief faceoff import
         *
         **/
         function procLayoutAdminUserLayoutImport(){
			return $this->stop('not supported');

            // check upload
            if(!Context::isUploaded()) exit();
            $file = Context::get('file');
            if(!is_uploaded_file($file['tmp_name'])) exit();
            if(!preg_match('/\.(tar)$/i', $file['name'])) exit();

            $layout_srl = Context::get('layout_srl');
            if(!$layout_srl) exit();

            $oLayoutModel = &getModel('layout');
            $user_layout_path = FileHandler::getRealPath($oLayoutModel->getUserLayoutPath($layout_srl));
            if(!move_uploaded_file($file['tmp_name'], $user_layout_path . 'faceoff.tar')) exit();

            $this->importLayout($layout_srl, $user_layout_path.'faceoff.tar');

			$this->setRedirectUrl(Context::get('error_return_url'));
        }

        function importLayout($layout_srl, $source_file) {
            $oLayoutModel = &getModel('layout');
            $user_layout_path = FileHandler::getRealPath($oLayoutModel->getUserLayoutPath($layout_srl));
            $file_list = $oLayoutModel->getUserLayoutFileList($layout_srl);
            foreach($file_list as $key => $file){
                FileHandler::removeFile($user_layout_path . $file);
            }

            require_once(_XE_PATH_.'libs/tar.class.php');
            $image_path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
            FileHandler::makeDir($image_path);
            $tar = new tar();
            $tar->openTAR($source_file);
            // If layout.ini file does not exist
            if(!$tar->getFile('layout.ini')) return;

            $replace_path = getNumberingPath($layout_srl,3);
            foreach($tar->files as $key => $info) {
                FileHandler::writeFile($user_layout_path . $info['name'],str_replace('__LAYOUT_PATH__',$replace_path,$info['file']));
            }
            // Remove uploaded file
            FileHandler::removeFile($source_file);
         }
    }
?>
