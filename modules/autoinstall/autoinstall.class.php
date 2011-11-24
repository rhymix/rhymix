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
            $buff = FileHandler::getRemoteResource(_XE_DOWNLOAD_SERVER_, $body, 3, "POST", "application/xml");
            if(!$buff) return;
            $xml = new XmlParser();
            $xmlDoc = $xml->parse($buff);
            return $xmlDoc;
        }
    }

    class autoinstall extends ModuleObject {
		var $tmp_dir = './files/cache/autoinstall/';

		function autoinstall()
		{
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('autoinstall');
			if ($config->downloadServer != _XE_DOWNLOAD_SERVER_)
			{
				$this->stop('msg_not_match_server');
			}
		}

        /**
         * @brief for additional tasks required when installing
         **/
        function moduleInstall() {
			$oModuleController = &getController('module');

			$config->downloadServer = _XE_DOWNLOAD_SERVER_;
			$oModuleController->insertModuleConfig('autoinstall', $config);
        }

        /**
         * @brief method to check if installation is succeeded
         **/
        function checkUpdate() {
            $oDB =& DB::getInstance();
			$oModuleModel = &getModel('module');

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

			// 2011.08.08 add column 'list_order' in ai_remote_categories
			if (!$oDB->isColumnExists('ai_remote_categories', 'list_order'))	return true;

			// 2011.08.08 set _XE_DOWNLOAD_SERVER_ at module config
			$config = $oModuleModel->getModuleConfig('autoinstall');
			if (!isset($config->downloadServer))	return true;

            return false;
        }

        /**
         * @brief Execute update
         **/
        function moduleUpdate() {
            $oDB =& DB::getInstance();
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');

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

			// 2011.08.08 add column 'list_order' in 'ai_remote_categories
			if (!$oDB->isColumnExists('ai_remote_categories', 'list_order'))
			{
				$oDB->addColumn('ai_remote_categories', 'list_order', 'number', 11, null, true);
				$oDB->addIndex('ai_remote_categories', 'idx_list_order', array('list_order'));
			}

			// 2011. 08. 08 set _XE_DOWNLOAD_SERVER_ at module config
			$config = $oModuleModel->getModuleConfig('autoinstall');
			if (!isset($config->downloadServer)){
				$config->downloadServer = _XE_DOWNLOAD_SERVER_;
				$oModuleController->insertModuleConfig('autoinstall', $config);
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
