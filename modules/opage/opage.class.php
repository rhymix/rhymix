<?php
    /**
     * @class  opage
     * @author NHN (developers@xpressengine.com)
     * @brief high class of opage module
     **/

    class opage extends ModuleObject {

        /**
         * @brief Implement if additional tasks are necessary when installing
         **/
        function moduleInstall() {
            // Create cache directory to use in the opage
            FileHandler::makeDir('./files/cache/opage');

            return new Object();
        }

        /**
         * @brief a method to check if successfully installed
         **/
        function checkUpdate() {
            // Create a directory ditectly if no cache directory exists
            if(!is_dir('./files/cache/opage')) FileHandler::makeDir('./files/cache/opage');

            return false;
        }

        /**
         * @brief Update
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
