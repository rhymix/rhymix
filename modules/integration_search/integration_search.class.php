<?php
    /**
     * @class  integration_search
     * @author NHN (developers@xpressengine.com)
     * @brief view class of the integration_search module
     **/

    class integration_search extends ModuleObject {

        /**
         * @brief Implement if additional tasks are necessary when installing
         **/
        function moduleInstall() {
            // Registered in action forward
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('integration_search', 'view', 'IS');

            return new Object();
        }

        /**
         * @brief a method to check if successfully installed
         **/
        function checkUpdate() {
            return false;
        }

        /**
         * @brief Execute update
         **/
        function moduleUpdate() {
            return new Object(0, 'success_updated');
        }

        /**
         * @brief Re-generate the cache file
         **/
        function recompileCache() {
        }
    }
?>
