<?php
    /**
     * @class  trackback
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 high class
     **/

    class trackback extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('trackback', 'controller', 'trackback');

            // 2007. 10. 17 게시글이 삭제될때 엮인글도 삭제되도록 trigger 등록
            $oModuleController->insertTrigger('document.deleteDocument', 'trackback', 'controller', 'triggerDeleteDocumentTrackbacks', 'after');

            // 2007. 10. 17 모듈이 삭제될때 등록된 엮인글도 모두 삭제하는 트리거 추가
            $oModuleController->insertTrigger('module.deleteModule', 'trackback', 'controller', 'triggerDeleteModuleTrackbacks', 'after');

            // 2007. 10. 18 게시글 팝업메뉴에서 엮인글 발송 기능 추가 
            $oModuleController->insertTrigger('document.getDocumentMenu', 'trackback', 'controller', 'triggerSendTrackback', 'after');

            // 2007. 10. 19 모듈별 엮인글 받는 기능 추가
            $oModuleController->insertTrigger('module.dispAdditionSetup', 'trackback', 'view', 'triggerDispTrackbackAdditionSetup', 'before');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 2007. 10. 17 게시글이 삭제될때 댓글도 삭제되도록 trigger 등록
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'trackback', 'controller', 'triggerDeleteDocumentTrackbacks', 'after')) return true;

            // 2007. 10. 17 모듈이 삭제될때 등록된 엮인글도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'trackback', 'controller', 'triggerDeleteModuleTrackbacks', 'after')) return true;

            // 2007. 10. 18 게시글 팝업메뉴에서 엮인글 발송 기능 추가 
            if(!$oModuleModel->getTrigger('document.getDocumentMenu', 'trackback', 'controller', 'triggerSendTrackback', 'after')) return true;

            // 2007. 10. 19 모듈별 엮인글 받는 기능 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'trackback', 'view', 'triggerDispTrackbackAdditionSetup', 'before')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 2007. 10. 17 게시글이 삭제될때 댓글도 삭제되도록 trigger 등록
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'trackback', 'controller', 'triggerDeleteDocumentTrackbacks', 'after'))
                $oModuleController->insertTrigger('document.deleteDocument', 'trackback', 'controller', 'triggerDeleteDocumentTrackbacks', 'after');

            // 2007. 10. 17 모듈이 삭제될때 등록된 엮인글도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'trackback', 'controller', 'triggerDeleteModuleTrackbacks', 'after'))
                $oModuleController->insertTrigger('module.deleteModule', 'trackback', 'controller', 'triggerDeleteModuleTrackbacks', 'after');

            // 2007. 10. 18 게시글 팝업메뉴에서 엮인글 발송 기능 추가 
            if(!$oModuleModel->getTrigger('document.getDocumentMenu', 'trackback', 'controller', 'triggerSendTrackback', 'after'))
                $oModuleController->insertTrigger('document.getDocumentMenu', 'trackback', 'controller', 'triggerSendTrackback', 'after');

            // 2007. 10. 19 모듈별 엮인글 받는 기능 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'trackback', 'view', 'triggerDispTrackbackAdditionSetup', 'before')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'trackback', 'view', 'triggerDispTrackbackAdditionSetup', 'before');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }

    }
?>
