<?php
    /**
     * @class  adminAdminController
     * @author zero (zero@nzeo.com)
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
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();

            // remove debug files 
            FileHandler::removeFile(_XE_PATH_.'files/_debug_message.php');
            FileHandler::removeFile(_XE_PATH_.'files/_debug_db_query.php');
            FileHandler::removeFile(_XE_PATH_.'files/_db_slow_query.php');

            // call recompileCache for each module
            foreach($module_list as $module) {
                $oModule = null;
                $oModule = &getClass($module->module);
                if(method_exists($oModule, 'recompileCache')) $oModule->recompileCache();
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
        }
    }
?>
