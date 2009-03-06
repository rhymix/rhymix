<?php
    /**
     * @class  poll
     * @author zero (zero@nzeo.com)
     * @brief  poll모듈의 high class
     **/

    class poll extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');

            // 기본 스킨 설정
            $oModuleController = &getController('module');
            $config->skin = 'default';
            $config->colorset = 'normal';
            $oModuleController->insertModuleConfig('poll', $config);

            // 2007. 10. 17 글/댓글의 삭제시 설문조사도 삭제
            $oModuleController->insertTrigger('document.insertDocument', 'poll', 'controller', 'triggerInsertDocumentPoll', 'after');
            $oModuleController->insertTrigger('comment.insertComment', 'poll', 'controller', 'triggerInsertCommentPoll', 'after');
            $oModuleController->insertTrigger('document.updateDocument', 'poll', 'controller', 'triggerUpdateDocumentPoll', 'after');
            $oModuleController->insertTrigger('comment.updateComment', 'poll', 'controller', 'triggerUpdateCommentPoll', 'after');
            $oModuleController->insertTrigger('document.deleteDocument', 'poll', 'controller', 'triggerDeleteDocumentPoll', 'after');
            $oModuleController->insertTrigger('comment.deleteComment', 'poll', 'controller', 'triggerDeleteCommentPoll', 'after');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 2007. 10. 17 글/댓글의 삭제시 설문조사도 삭제
            if(!$oModuleModel->getTrigger('document.insertDocument', 'poll', 'controller', 'triggerInsertDocumentPoll', 'after')) return true;
            if(!$oModuleModel->getTrigger('comment.insertComment', 'poll', 'controller', 'triggerInsertCommentPoll', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.updateDocument', 'poll', 'controller', 'triggerUpdateDocumentPoll', 'after')) return true;
            if(!$oModuleModel->getTrigger('comment.updateComment', 'poll', 'controller', 'triggerUpdateCommentPoll', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'poll', 'controller', 'triggerDeleteDocumentPoll', 'after')) return true;
            if(!$oModuleModel->getTrigger('comment.deleteComment', 'poll', 'controller', 'triggerDeleteCommentPoll', 'after')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 2007. 10. 17 글/댓글의 삭제시 설문조사도 삭제
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'poll', 'controller', 'triggerDeleteDocumentPoll', 'after'))
                $oModuleController->insertTrigger('document.deleteDocument', 'poll', 'controller', 'triggerDeleteDocumentPoll', 'after');
            if(!$oModuleModel->getTrigger('comment.deleteComment', 'poll', 'controller', 'triggerDeleteCommentPoll', 'after'))
                $oModuleController->insertTrigger('comment.deleteComment', 'poll', 'controller', 'triggerDeleteCommentPoll', 'after');

            // 2008. 04. 22 글/댓글의 추가기 설문조사의 연결
            if(!$oModuleModel->getTrigger('document.insertDocument', 'poll', 'controller', 'triggerInsertDocumentPoll', 'after')) 
                $oModuleController->insertTrigger('document.insertDocument', 'poll', 'controller', 'triggerInsertDocumentPoll', 'after');
            if(!$oModuleModel->getTrigger('comment.insertComment', 'poll', 'controller', 'triggerInsertCommentPoll', 'after')) 
                $oModuleController->insertTrigger('comment.insertComment', 'poll', 'controller', 'triggerInsertCommentPoll', 'after');
            if(!$oModuleModel->getTrigger('document.updateDocument', 'poll', 'controller', 'triggerUpdateDocumentPoll', 'after')) 
                $oModuleController->insertTrigger('document.updateDocument', 'poll', 'controller', 'triggerUpdateDocumentPoll', 'after');
            if(!$oModuleModel->getTrigger('comment.updateComment', 'poll', 'controller', 'triggerUpdateCommentPoll', 'after')) 
                $oModuleController->insertTrigger('comment.updateComment', 'poll', 'controller', 'triggerUpdateCommentPoll', 'after');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
