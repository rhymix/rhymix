<?php
    /**
     * @class  syndication
     * @author zero (skklove@gmail.com)
     * @brief  syndication 모듈의 high class
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

        function moduleInstall() {
            $oModuleController = &getController('module');
            $oModuleController->insertTrigger('document.insertDocument', 'syndication', 'controller', 'triggerInsertDocument', 'after');
            $oModuleController->insertTrigger('document.updateDocument', 'syndication', 'controller', 'triggerUpdateDocument', 'after');
            $oModuleController->insertTrigger('document.deleteDocument', 'syndication', 'controller', 'triggerDeleteDocument', 'after');
            $oModuleController->insertTrigger('module.deleteModule', 'syndication', 'controller', 'triggerDeleteModule', 'after');
        }

        function checkUpdate() {
            return false;
        }

        function moduleUpdate() {
        }

        function recompileCache() {
        }
    }
?>
