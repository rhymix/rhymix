<?php
    /**
     * @class  admin
     * @author NHN (developers@xpressengine.com)
     * @brief  base class of admin module
     **/

    class adminlogging extends ModuleObject {
        /**
         * @brief install admin module
         * @return new Object
         **/
        function moduleInstall() {
            return new Object();
        }

        /**
         * @brief if update is necessary it returns true
         **/
        function checkUpdate() {
            return false;
        }

        /**
         * @brief update module
         * @return new Object
         **/
        function moduleUpdate() {
            return new Object();
        }

        /**
         * @brief regenerate cache file
         * @return none
         **/
        function recompileCache() {
        }
    }
?>
