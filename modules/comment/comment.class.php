<?php
    /**
     * @class  comment
     * @author NHN (developers@xpressengine.com)
     * @brief comment module's high class
     **/

    require_once(_XE_PATH_.'modules/comment/comment.item.php');

    class comment extends ModuleObject {

        /**
         * @brief implemented if additional tasks are required when installing
         **/
        function moduleInstall() {
            // register the action forward (for using on the admin mode)
            $oModuleController = &getController('module');
            // 2007. 10. 17 add a trigger to delete comments together with posting deleted
            $oModuleController->insertTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after');
            // 2007. 10. 17 add a trigger to delete all of comments together with module deleted
            $oModuleController->insertTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after');
            // 2008. 02. 22 add comment setting when a new module added
            $oModuleController->insertTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before');

            return new Object();
        }

        /**
         * @brief method to check if installation is succeeded
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');
            // 2007. 10. 17 add a trigger to delete comments together with posting deleted
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after')) return true;
            // 2007. 10. 17 add a trigger to delete all of comments together with module deleted
            if(!$oModuleModel->getTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after')) return true;
            // 2007. 10. 23 add a column for recommendation votes or notification of the comments
            if(!$oDB->isColumnExists("comments","voted_count")) return true;
            if(!$oDB->isColumnExists("comments","notify_message")) return true;
            // 2008. 02. 22 add comment setting when a new module added
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before')) return true;
            // 2008. 05. 14 add a column for blamed count
            if(!$oDB->isColumnExists("comments", "blamed_count")) return true;
            if(!$oDB->isColumnExists("comment_voted_log", "point")) return true;

            return false;
        }

        /**
         * @brief Execute update
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            // 2007. 10. 17 add a trigger to delete comments together with posting deleted
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after'))
                $oModuleController->insertTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after');
            // 2007. 10. 17 add a trigger to delete all of comments together with module deleted
            if(!$oModuleModel->getTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after')) 
                $oModuleController->insertTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after');
            // 2007. 10. 23 add a column for recommendation votes or notification of the comments
            if(!$oDB->isColumnExists("comments","voted_count")) {
                $oDB->addColumn("comments","voted_count", "number","11");
                $oDB->addIndex("comments","idx_voted_count", array("voted_count"));
            }

            if(!$oDB->isColumnExists("comments","notify_message")) {
                $oDB->addColumn("comments","notify_message", "char","1");
            }
            // 2008. 02. 22 add comment setting when a new module added
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before');
            // 2008. 05. 14 add a column for blamed count
            if(!$oDB->isColumnExists("comments", "blamed_count")) {
                $oDB->addColumn('comments', 'blamed_count', 'number', 11, 0, true); 
                $oDB->addIndex('comments', 'idx_blamed_count', array('blamed_count'));
            }
            if(!$oDB->isColumnExists("comment_voted_log", "point"))
                $oDB->addColumn('comment_voted_log', 'point', 'number', 11, 0, true); 

            return new Object(0, 'success_updated');
        }

        /**
         * @brief Regenerate cache file
         **/
        function recompileCache() {
        }
    }
?>
