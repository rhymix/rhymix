<?php
    /**
     * @class  page
     * @author NHN (developers@xpressengine.com)
     * @brief high class of the module page
     **/

    class page extends ModuleObject {

        /**
         * @brief Implement if additional tasks are necessary when installing
         **/
        function moduleInstall() {
            // page generated from the cache directory to use
            FileHandler::makeDir('./files/cache/page');

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
            return new Object(0,'success_updated');
        }

        /**
         * @brief Re-generate the cache file
         **/
        function recompileCache() {
            // Delete the cache file pages
            FileHandler::removeFilesInDir("./files/cache/page");
        }
    }
?>
