<?php
    /**
     * @class  adminAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief  admin controller class of admin module
     **/

    class adminAdminController extends admin {
        /**
         * @brief initialization
         * @return none
         **/
        function init() {
            // forbit access if the user is not an administrator
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();
            if($logged_info->is_admin!='Y') return $this->stop("msg_is_not_administrator");
        }

        /**
         * @brief Regenerate all cache files
         * @return none
         **/
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

			// remove cache dir
			$tmp_cache_list = FileHandler::readDir('./files','/(^cache_[0-9]+)/');
			if($tmp_cache_list){
				foreach($tmp_cache_list as $tmp_dir){
					if($tmp_dir) FileHandler::removeDir('./files/'.$tmp_dir);
				}
			}

			$truncated = array();
			$oObjectCacheHandler = &CacheHandler::getInstance();
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

            $this->setMessage('success_updated');
        }

        /**
         * @brief Logout
         * @return none
         **/
        function procAdminLogout() {
            $oMemberController = &getController('member');
            $oMemberController->procMemberLogout();

			header('Location: '.getNotEncodedUrl('', 'module','admin'));
        }

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

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {                                                                                                                        
                $returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminTheme');
                header('location:'.$returnUrl);
                return;
            }   
            else return $output;
		}
    }
?>
