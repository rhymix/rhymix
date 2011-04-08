<?php
    /**
     * @class  syndication
     * @author NHN (developers@xpressengine.com)
     * @brief syndication module's high class
     **/

    define('SyndicationModule', 'M');
    define('SyndicationDocument', 'D');

    define('SyndicationInserted', 'I');
    define('SyndicationUpdated', 'U');
    define('SyndicationDeleted', 'D');

    class syndication extends ModuleObject {

        var $services = array(
            'Naver' => 'http://syndication.openapi.naver.com/ping/',
        );

        var $statuses = array(
            'Naver' => 'http://syndication.openapi.naver.com/status/?site=%s',
        );

        function moduleInstall() {
            $oModuleController = &getController('module');
            $oModuleController->insertTrigger('document.insertDocument', 'syndication', 'controller', 'triggerInsertDocument', 'after');
            $oModuleController->insertTrigger('document.updateDocument', 'syndication', 'controller', 'triggerUpdateDocument', 'after');
            $oModuleController->insertTrigger('document.deleteDocument', 'syndication', 'controller', 'triggerDeleteDocument', 'after');
            $oModuleController->insertTrigger('module.deleteModule', 'syndication', 'controller', 'triggerDeleteModule', 'after');

            $oModuleController->insertTrigger('document.moveDocumentToTrash', 'syndication', 'controller', 'triggerMoveDocumentToTrash', 'after');
            $oModuleController->insertTrigger('document.restoreTrash', 'syndication', 'controller', 'triggerRestoreTrash', 'after');

            $oAddonAdminModel = &getAdminModel('addon');
			if($oAddonAdminModel->getAddonInfoXml('catpcha')){
				$oAddonAdminController = &addonAdminController::getInstance();
				$oAddonAdminController->doActivate('catpcha');
				$oAddonAdminController->makeCacheFile();
			}
        }

        function checkUpdate() {
            $oModuleModel = &getModel('module');
            if(!$oModuleModel->getTrigger('document.moveDocumentToTrash', 'syndication', 'controller', 'triggerMoveDocumentToTrash', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.restoreTrash', 'syndication', 'controller', 'triggerRestoreTrash', 'after')) return true;

            return false;
        }

        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            if(!$oModuleModel->getTrigger('document.moveDocumentToTrash', 'syndication', 'controller', 'triggerMoveDocumentToTrash', 'after')){
				$oModuleController->insertTrigger('document.moveDocumentToTrash', 'syndication', 'controller', 'triggerMoveDocumentToTrash', 'after');
			}
            if(!$oModuleModel->getTrigger('document.restoreTrash', 'syndication', 'controller', 'triggerRestoreTrash', 'after')){
				$oModuleController->insertTrigger('document.restoreTrash', 'syndication', 'controller', 'triggerRestoreTrash', 'after');
			}

            $oAddonAdminModel = &getAdminModel('addon');
			if($oAddonAdminModel->getAddonInfoXml('catpcha')){
				$oAddonAdminController = &addonAdminController::getInstance();
				$oAddonAdminController->doActivate('catpcha');
				$oAddonAdminController->makeCacheFile();
			}

        }

        function recompileCache() {
        }
    }
?>
