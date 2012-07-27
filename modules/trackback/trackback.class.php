<?php
	/**
	 * trackback class
	 * trackback module's high class
	 *
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/trackback
	 * @version 0.1
	 */
    class trackback extends ModuleObject {
		/**
		 * Implement if additional tasks are necessary when installing
		 * @return Object
		 */
        function moduleInstall() {
            // Register action forward (to use in administrator mode)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('trackback', 'controller', 'trackback');
            // 2007. 10. 17 posts deleted and will be deleted when the trigger property Trackbacks
            $oModuleController->insertTrigger('document.deleteDocument', 'trackback', 'controller', 'triggerDeleteDocumentTrackbacks', 'after');
            // 2007. 10. 17 modules are deleted when you delete all registered triggers that add Trackbacks
            $oModuleController->insertTrigger('module.deleteModule', 'trackback', 'controller', 'triggerDeleteModuleTrackbacks', 'after');
            // 2007. 10. Yeokingeul sent from the popup menu features 18 additional posts
            $oModuleController->insertTrigger('document.getDocumentMenu', 'trackback', 'controller', 'triggerSendTrackback', 'after');
            // 2007. 10. The ability to receive 19 additional modular yeokingeul
            $oModuleController->insertTrigger('module.dispAdditionSetup', 'trackback', 'view', 'triggerDispTrackbackAdditionSetup', 'before');

            return new Object();
        }

		/**
		 * A method to check if successfully installed
		 * @return bool
		 */
        function checkUpdate() {
            $oModuleModel = &getModel('module');
            // 2007. 10. 17 posts deleted, even when the comments will be deleted trigger property
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'trackback', 'controller', 'triggerDeleteDocumentTrackbacks', 'after')) return true;
            // 2007. 10. 17 modules are deleted when you delete all registered triggers that add Trackbacks
            if(!$oModuleModel->getTrigger('module.deleteModule', 'trackback', 'controller', 'triggerDeleteModuleTrackbacks', 'after')) return true;
            // 2007. 10. Yeokingeul sent from the popup menu features 18 additional posts
            if(!$oModuleModel->getTrigger('document.getDocumentMenu', 'trackback', 'controller', 'triggerSendTrackback', 'after')) return true;
            // 2007. 10. The ability to receive 19 additional modular yeokingeul
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'trackback', 'view', 'triggerDispTrackbackAdditionSetup', 'before')) return true;

            return false;
        }

		/**
		 * Execute update
		 * @return Object
		 */
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            // 2007. 10. 17 posts deleted, even when the comments will be deleted trigger property
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'trackback', 'controller', 'triggerDeleteDocumentTrackbacks', 'after'))
                $oModuleController->insertTrigger('document.deleteDocument', 'trackback', 'controller', 'triggerDeleteDocumentTrackbacks', 'after');
            // 2007. 10. 17 modules are deleted when you delete all registered triggers that add Trackbacks
            if(!$oModuleModel->getTrigger('module.deleteModule', 'trackback', 'controller', 'triggerDeleteModuleTrackbacks', 'after'))
                $oModuleController->insertTrigger('module.deleteModule', 'trackback', 'controller', 'triggerDeleteModuleTrackbacks', 'after');
            // 2007. 10. Yeokingeul sent from the popup menu features 18 additional posts
            if(!$oModuleModel->getTrigger('document.getDocumentMenu', 'trackback', 'controller', 'triggerSendTrackback', 'after'))
                $oModuleController->insertTrigger('document.getDocumentMenu', 'trackback', 'controller', 'triggerSendTrackback', 'after');
            // 2007. 10. The ability to receive 19 additional modular yeokingeul
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'trackback', 'view', 'triggerDispTrackbackAdditionSetup', 'before')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'trackback', 'view', 'triggerDispTrackbackAdditionSetup', 'before');

            return new Object(0, 'success_updated');
        }

		/**
		 * Re-generate the cache file
		 * @return void
		 */
        function recompileCache() {
        }

    }
?>
