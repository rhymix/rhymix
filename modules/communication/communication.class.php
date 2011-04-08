<?php
    /**
     * @class  communication 
     * @author NHN (developers@xpressengine.com)
     * @brief communication module of the high class
     **/

    class communication extends ModuleObject {

        /**
         * @brief Implement if additional tasks are necessary when installing
         **/
        function moduleInstall() {
            // Create a temporary file storage for one new private message notification
            FileHandler::makeDir('./files/member_extra_info/new_message_flags');
            return new Object();
        }

        /**
         * @brief method to check if successfully installed.
         **/
        function checkUpdate() {
            if(!is_dir("./files/member_extra_info/new_message_flags")) return true;
            return false;
        }

        /**
         * @brief Update
         **/
        function moduleUpdate() {
            if(!is_dir("./files/member_extra_info/new_message_flags")) 
                FileHandler::makeDir('./files/member_extra_info/new_message_flags');
            return new Object(0, 'success_updated');
        }

        /**
         * @brief Re-generate the cache file
         **/
        function recompileCache() {
        }
    }
?>
