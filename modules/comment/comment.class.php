<?php
    /**
     * @class  comment
     * @author zero (zero@nzeo.com)
     * @brief  comment 모듈의 high class
     **/

    require_once(_XE_PATH_.'modules/comment/comment.item.php');

    class comment extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('comment', 'view', 'dispCommentAdminList');
            $oModuleController->insertActionForward('comment', 'view', 'dispCommentAdminDeclared');

            // 2007. 10. 17 게시글이 삭제될때 댓글도 삭제되도록 trigger 등록
            $oModuleController->insertTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after');

            // 2007. 10. 17 모듈이 삭제될때 등록된 댓글도 모두 삭제하는 트리거 추가
            $oModuleController->insertTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after');

            // 2008. 02. 22 모듈의 추가 설정에서 댓글 추가 설정 추가
            $oModuleController->insertTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');

            // 2007. 10. 17 게시글이 삭제될때 댓글도 삭제되도록 trigger 등록
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after')) return true;

            // 2007. 10. 17 모듈이 삭제될때 등록된 댓글도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after')) return true;

            // 2007. 10. 23 댓글에도 추천/ 알림 기능을 위한 컬럼 추가
            if(!$oDB->isColumnExists("comments","voted_count")) return true;
            if(!$oDB->isColumnExists("comments","notify_message")) return true;

            if(!$oModuleModel->getActionForward('dispCommentAdminDeclared')) return true;

            // 2008. 02. 22 모듈의 추가 설정에서 댓글 추가 설정 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before')) return true;

            // 2008. 05. 14 blamed count 컬럼 추가
            if(!$oDB->isColumnExists("comments", "blamed_count")) return true;
            if(!$oDB->isColumnExists("comment_voted_log", "point")) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 2007. 10. 17 게시글이 삭제될때 댓글도 삭제되도록 trigger 등록
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after'))
                $oModuleController->insertTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after');

            // 2007. 10. 17 모듈이 삭제될때 등록된 댓글도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after')) 
                $oModuleController->insertTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after');

            // 2007. 10. 23 댓글에도 추천/ 알림 기능을 위한 컬럼 추가
            if(!$oDB->isColumnExists("comments","voted_count")) {
                $oDB->addColumn("comments","voted_count", "number","11");
                $oDB->addIndex("comments","idx_voted_count", array("voted_count"));
            }

            if(!$oDB->isColumnExists("comments","notify_message")) {
                $oDB->addColumn("comments","notify_message", "char","1");
            }

            if(!$oModuleModel->getActionForward('dispCommentAdminDeclared'))
                $oModuleController->insertActionForward('comment', 'view', 'dispCommentAdminDeclared');

            // 2008. 02. 22 모듈의 추가 설정에서 댓글 추가 설정 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before');

            // 2008. 05. 14 blamed count 컬럼 추가
            if(!$oDB->isColumnExists("comments", "blamed_count")) {
                $oDB->addColumn('comments', 'blamed_count', 'number', 11, 0, true); 
                $oDB->addIndex('comments', 'idx_blamed_count', array('blamed_count'));
            }
            if(!$oDB->isColumnExists("comment_voted_log", "point"))
                $oDB->addColumn('comment_voted_log', 'point', 'number', 11, 0, true); 

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
