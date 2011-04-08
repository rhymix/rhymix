<?php
    /**
     * @class  autoinstall
     * @author NHN (developers@xpressengine.com)
     * @brief high class of the autoinstall module
     **/

    class XmlGenerater {
        function generate(&$params)
        {
            $xmlDoc = '<?xml version="1.0" encoding="utf-8" ?><methodCall><params>';
            if(!is_array($params)) return null;
            $params["module"] = "resourceapi";
            foreach($params as $key => $val)
            {
                $xmlDoc .= sprintf("<%s><![CDATA[%s]]></%s>", $key, $val, $key);
            }
            $xmlDoc .= "</params></methodCall>";
            return $xmlDoc;
        }

        function getXmlDoc(&$params)
        {
            $body = XmlGenerater::generate($params);
            $buff = FileHandler::getRemoteResource($this->uri, $body, 3, "POST", "application/xml");
            if(!$buff) return;
            $xml = new XmlParser();
            $xmlDoc = $xml->parse($buff);
            return $xmlDoc;
        }
    }

    class autoinstall extends ModuleObject {
        var $uri = "http://download.xpressengine.com/";
        var $original_site = "http://www.xpressengine.com/";
		var $tmp_dir = './files/cache/autoinstall/';

        /**
         * @brief for additional tasks required when installing
         **/
        function moduleInstall() {
        }

        /**
         * @brief method to check if installation is succeeded
         **/
        function checkUpdate() {
            $oDB =& DB::getInstance();
            if(!file_exists(FileHandler::getRealPath("./modules/autoinstall/schemas/autoinstall_installed_packages.xml"))  
                && $oDB->isTableExists("autoinstall_installed_packages"))
            {
                return true;
            }
            if(!file_exists(FileHandler::getRealPath("./modules/autoinstall/schemas/autoinstall_remote_categories.xml"))  
                && $oDB->isTableExists("autoinstall_remote_categories"))
            {
                return true;
            }

            return false;
        }

        /**
         * @brief Execute update
         **/
        function moduleUpdate() {
            $oDB =& DB::getInstance();
            if(!file_exists(FileHandler::getRealPath("./modules/autoinstall/schemas/autoinstall_installed_packages.xml"))  
                && $oDB->isTableExists("autoinstall_installed_packages"))
            {
                $oDB->dropTable("autoinstall_installed_packages");
            }
            if(!file_exists(FileHandler::getRealPath("./modules/autoinstall/schemas/autoinstall_remote_categories.xml"))  
                && $oDB->isTableExists("autoinstall_remote_categories"))
            {
                $oDB->dropTable("autoinstall_remote_categories");
            }
            return new Object(0, 'success_updated');
        }

        /**
         * @brief Re-generate the cache file
         **/
        function recompileCache() {
        }
    }
?>
