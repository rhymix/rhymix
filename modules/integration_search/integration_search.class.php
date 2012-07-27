<?php
    /**
     * The view class of the integration_search module
	 *
     * @author NHN (developers@xpressengine.com)
     **/

    class integration_search extends ModuleObject {

        /**
         * Implement if additional tasks are necessary when installing
		 *
		 * @return Object
         **/
        function moduleInstall() {
            // Registered in action forward
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('integration_search', 'view', 'IS');

            return new Object();
        }

        /**
         * Check methoda whether successfully installed
		 *
		 * @return bool
         **/
        function checkUpdate() {
            return false;
        }

        /**
         * Execute update
		 *
		 * @return Object
         **/
        function moduleUpdate() {
            return new Object(0, 'success_updated');
        }

        /**
         * Re-generate the cache file
		 *
		 * @return void
         **/
        function recompileCache() {
        }
    }
?>
