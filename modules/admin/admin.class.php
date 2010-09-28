<?php
    /**
     * @class  admin
     * @author NHN (developers@xpressengine.com)
     * @brief  base class of admin module 
     **/

    class admin extends ModuleObject {

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

            // remove compiled templates
            FileHandler::removeFilesInDir("./files/cache/template_compiled");

            // remove optimized files 
            FileHandler::removeFilesInDir("./files/cache/optimized");

            // remove js_filter_compiled files 
            FileHandler::removeFilesInDir("./files/cache/js_filter_compiled");

            // remove cached queries 
            FileHandler::removeFilesInDir("./files/cache/queries");

            // remove ./files/cache/news* files 
            $directory = dir(_XE_PATH_."files/cache/");
            while($entry = $directory->read()) {
                if(substr($entry,0,11)=='newest_news') FileHandler::removeFile("./files/cache/".$entry);
            }
            $directory->close();
        }
    }
?>
