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
        function checkUpdate() 
		{
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('integration_search');

			if($config->skin)
			{
				$config_parse = explode('.', $config->skin);
				if (count($config_parse) > 1)
				{
					$template_path = sprintf('./themes/%s/modules/integration_search/', $config_parse[0]);
					if(is_dir($template_path)) return true;
				}
			}
            return false;
        }

        /**
         * Execute update
		 *
		 * @return Object
         **/
        function moduleUpdate() 
		{
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('message');

			if($config->skin)
			{
				$config_parse = explode('.', $config->skin);
				if (count($config_parse) > 1)
				{
					$template_path = sprintf('./themes/%s/modules/integration_search/', $config_parse[0]);
					if(is_dir($template_path))
					{
						$config->skin = implode('|@|', $config_parse);
						$oModuleController = &getController('module');
						$oModuleController->updateModuleConfig('integration_search', $config);
					}
				}
			}

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
