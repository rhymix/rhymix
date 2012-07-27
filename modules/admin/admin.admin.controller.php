<?php
	/**
	 * adminAdminController class
	 * admin controller class of admin module
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/admin
	 * @version 0.1
	 */
    class adminAdminController extends admin {
		/**
		 * initialization
		 * @return void
		 */
        function init() {
            // forbit access if the user is not an administrator
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();
            if($logged_info->is_admin!='Y') return $this->stop("msg_is_not_administrator");
        }

		/**
		 * Admin menu reset
		 * @return void
		 */
		function procAdminMenuReset(){
			$menuSrl = Context::get('menu_srl');
			if (!$menuSrl) return $this->stop('msg_invalid_request');

			$oMenuAdminController = &getAdminController('menu');
			$output = $oMenuAdminController->deleteMenu($menuSrl);
			if (!$output->toBool()) return $output;

			FileHandler::removeDir('./files/cache/menu/admin_lang/');

			$this->setRedirectUrl(Context::get('error_return_url'));
		}

		/**
		 * Regenerate all cache files
		 * @return void
		 */
        function procAdminRecompileCacheFile() {
			// rename cache dir
			$temp_cache_dir = './files/cache_'. time();
			FileHandler::rename('./files/cache', $temp_cache_dir);
			FileHandler::makeDir('./files/cache');

            // remove debug files
            FileHandler::removeFile(_XE_PATH_.'files/_debug_message.php');
            FileHandler::removeFile(_XE_PATH_.'files/_debug_db_query.php');
            FileHandler::removeFile(_XE_PATH_.'files/_db_slow_query.php');

            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();

            // call recompileCache for each module
            foreach($module_list as $module) {
                $oModule = null;
                $oModule = &getClass($module->module);
                if(method_exists($oModule, 'recompileCache')) $oModule->recompileCache();
            }

			// remove cache
			$truncated = array();
			$oObjectCacheHandler = &CacheHandler::getInstance('object');
			$oTemplateCacheHandler = &CacheHandler::getInstance('template');

			if($oObjectCacheHandler->isSupport()){
				$truncated[] = $oObjectCacheHandler->truncate();
			}

			if($oTemplateCacheHandler->isSupport()){
				$truncated[] = $oTemplateCacheHandler->truncate();
			}

			if(count($truncated) && in_array(false,$truncated)){
				return new Object(-1,'msg_self_restart_cache_engine');
			}


			// remove cache dir
			$tmp_cache_list = FileHandler::readDir('./files','/(^cache_[0-9]+)/');
			if($tmp_cache_list){
				foreach($tmp_cache_list as $tmp_dir){
					if($tmp_dir) FileHandler::removeDir('./files/'.$tmp_dir);
				}
			}

            // remove duplicate indexes (only for CUBRID)
            $db_type = &Context::getDBType();
            if($db_type == 'cubrid')
            {
                $db = &DB::getInstance();
                $db->deleteDuplicateIndexes();
            }

            $this->setMessage('success_updated');
        }

		/**
		 * Logout
		 * @return void
		 */
        function procAdminLogout() {
            $oMemberController = &getController('member');
            $oMemberController->procMemberLogout();

			header('Location: '.getNotEncodedUrl('', 'module','admin'));
        }

		/**
		 * Insert theme information
		 * @return void|object
		 */
		function procAdminInsertThemeInfo(){
			$vars = Context::getRequestVars();
			$theme_file = _XE_PATH_.'files/theme/theme_info.php';

			$theme_output = sprintf('$theme_info->theme=\'%s\';', $vars->themeItem);
			$theme_output = $theme_output.sprintf('$theme_info->layout=%s;', $vars->layout);

			$site_info = Context::get('site_module_info');

			$args->site_srl = $site_info->site_srl;
			$args->layout_srl = $vars->layout;
			// layout submit
			$output = executeQuery('layout.updateAllLayoutInSiteWithTheme', $args);
			if (!$output->toBool()) return $output;

			$skin_args->site_srl = $site_info->site_srl;

			foreach($vars as $key=>$val){
				$pos = strpos($key, '-skin');
				if ($pos === false) continue;
				if ($val != '__skin_none__'){
					$module = substr($key, 0, $pos);
					$theme_output = $theme_output.sprintf('$theme_info->skin_info[%s]=\'%s\';', $module, $val);
					$skin_args->skin = $val;
					$skin_args->module = $module;
					if ($module == 'page')
					{
						$article_output = executeQueryArray('page.getArticlePageSrls');
						if (count($article_output->data)>0){
							$article_module_srls = array();
							foreach($article_output->data as $val){
								$article_module_srls[] = $val->module_srl;
							}
							$skin_args->module_srls = implode(',', $article_module_srls);
						}
					}
					$skin_output = executeQuery('module.updateAllModuleSkinInSiteWithTheme', $skin_args);
					if (!$skin_output->toBool()) return $skin_output;

					$oModuleModel = &getModel('module');
					$module_config = $oModuleModel->getModuleConfig($module, $site_info->site_srl);
					$module_config->skin = $val;
					$oModuleController = &getController('module');
					$oModuleController->insertModuleConfig($module, $module_config, $site_info->site_srl);
				}
			}

            $theme_buff = sprintf(
                '<?php '.
                'if(!defined("__ZBXE__")) exit(); '.
				'%s'.
                '?>',
				$theme_output
            );
            // Save File
            FileHandler::writeFile($theme_file, $theme_buff);

			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminTheme');
			return $this->setRedirectUrl($returnUrl, $output);
		}

		/**
		 * Toggle favorite
		 * @return void
		 */
		function procAdminToggleFavorite()
		{
			$siteSrl = Context::get('site_srl');
			$moduleName = Context::get('module_name');

			// check favorite exists
			$oModel = &getAdminModel('admin');
			$output = $oModel->isExistsFavorite($siteSrl, $moduleName);
			if (!$output->toBool()) return $output;

			// if exists, delete favorite
			if ($output->get('result'))
			{
				$favoriteSrl = $output->get('favoriteSrl');
				$output = $this->_deleteFavorite($favoriteSrl);
				$result = 'off';
			}

			// if not exists, insert favorite
			else
			{
				$output = $this->_insertFavorite($siteSrl, $moduleName);
				$result = 'on';
			}

			if (!$output->toBool()) return $output;

			$this->add('result', $result);

			return $this->setRedirectUrl(Context::get('error_return_url'), $output);
		}

		/**
		 * Enviroment gathering agreement
		 * @return void
		 */
		function procAdminEnviromentGatheringAgreement()
		{
			$isAgree = Context::get('is_agree');
			if($isAgree == 'true') $_SESSION['enviroment_gather'] = 'Y';
			else $_SESSION['enviroment_gather'] = 'N';

			$redirectUrl = getUrl('', 'module', 'admin');
			$this->setRedirectUrl($redirectUrl);
		}

		/**
		 * Admin config update
		 * @return void
		 */
		function procAdminUpdateConfig()
		{
			$adminTitle = Context::get('adminTitle');
            $file = $_FILES['adminLogo'];

            $oModuleModel = &getModel('module');
            $oAdminConfig = $oModuleModel->getModuleConfig('admin');

			if($file['tmp_name'])
			{
				$target_path = 'files/attach/images/admin/';
				FileHandler::makeDir($target_path);

				// Get file information
				list($width, $height, $type, $attrs) = @getimagesize($file['tmp_name']);
				if($type == 3) $ext = 'png';
				elseif($type == 2) $ext = 'jpg';
				else $ext = 'gif';

				$target_filename = sprintf('%s%s.%s.%s', $target_path, 'adminLogo', date('YmdHis'), $ext);
				@move_uploaded_file($file['tmp_name'], $target_filename);

				$oAdminConfig->adminLogo = $target_filename;
			}
			if($adminTitle) $oAdminConfig->adminTitle = strip_tags($adminTitle);
			else unset($oAdminConfig->adminTitle);

			if($oAdminConfig)
			{
				$oModuleController = &getController('module');
				$oModuleController->insertModuleConfig('admin', $oAdminConfig);
			}

			$this->setMessage('success_updated', 'info');

			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminSetup');
			$this->setRedirectUrl($returnUrl);
		}

		/**
		 * Admin logo delete
		 * @return void
		 */
		function procAdminDeleteLogo()
		{
            $oModuleModel = &getModel('module');
            $oAdminConfig = $oModuleModel->getModuleConfig('admin');

            FileHandler::removeFile(_XE_PATH_.$oAdminConfig->adminLogo);
			unset($oAdminConfig->adminLogo);

			$oModuleController = &getController('module');
			$oModuleController->insertModuleConfig('admin', $oAdminConfig);

			$this->setMessage('success_deleted', 'info');

			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminSetup');
			$this->setRedirectUrl($returnUrl);
		}

		/**
		 * Insert favorite
		 * @return object query result
		 */
		function _insertFavorite($siteSrl, $module, $type = 'module')
		{
			$args->adminFavoriteSrl = getNextSequence();
			$args->site_srl = $siteSrl;
			$args->module = $module;
			$args->type = $type;
			$output = executeQuery('admin.insertFavorite', $args);
			return $output;
		}

		/**
		 * Delete favorite
		 * @return object query result
		 */
		function _deleteFavorite($favoriteSrl)
		{
			$args->admin_favorite_srl = $favoriteSrl;
			$output = executeQuery('admin.deleteFavorite', $args);
			return $output;
		}

		/**
		 * Delete all favorite
		 * @return object query result
		 */
		function _deleteAllFavorite()
		{
			$args = null;
			$output = executeQuery('admin.deleteAllFavorite', $args);
			return $output;
		}

		/**
		 * Remove admin icon
		 * @return object|void
		 */
		function procAdminRemoveIcons(){
			$iconname = Context::get('iconname');
			$file_exist = FileHandler::readFile(_XE_PATH_.'files/attach/xeicon/'.$iconname);
			if($file_exist) {
				@FileHandler::removeFile(_XE_PATH_.'files/attach/xeicon/'.$iconname);
			} else {
				return new Object(-1,'fail_to_delete');
			}
			$this->setMessage('success_deleted');
		}
    }
?>
