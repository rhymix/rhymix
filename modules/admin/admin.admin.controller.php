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

			header('Location: '.getUrl('module','admin','act',''));
        }
    }
?>
