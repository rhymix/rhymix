<?php
    /**
     * @class  communication 
     * @author NHN (developers@xpressengine.com)
     * communication module of the high class
     **/

    class communication extends ModuleObject {

        /**
         * Implement if additional tasks are necessary when installing
		 * @return Object
         **/
        function moduleInstall() {
            // Create a temporary file storage for one new private message notification
            FileHandler::makeDir('./files/member_extra_info/new_message_flags');
            return new Object();
        }

        /**
         * method to check if successfully installed.
		 * @return boolean true : need to update false : don't need to update
         **/
        function checkUpdate() {
            if(!is_dir("./files/member_extra_info/new_message_flags")) return true;
            return false;
        }

        /**
         * Update
		 * @return Object
         **/
        function moduleUpdate() {
            if(!is_dir("./files/member_extra_info/new_message_flags")) 
                FileHandler::makeDir('./files/member_extra_info/new_message_flags');
            return new Object(0, 'success_updated');
        }

        /**
         * Re-generate the cache file
		 * @return void
         **/
        function recompileCache() {
        }
    }
?>
