<?php
    /**
     * @class  tag
     * @author zero (zero@nzeo.com)
     * @brief  tag 모듈의 high class
     **/

    class tag extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            $oModuleController = &getController('module');

            // 2007. 10. 17 document.insertDocument, updateDocument, deleteDocument에 대한 trigger 등록
            $oModuleController->insertTrigger('document.insertDocument', 'tag', 'controller', 'triggerArrangeTag', 'before');
            $oModuleController->insertTrigger('document.insertDocument', 'tag', 'controller', 'triggerInsertTag', 'after');
            $oModuleController->insertTrigger('document.updateDocument', 'tag', 'controller', 'triggerArrangeTag', 'before');
            $oModuleController->insertTrigger('document.updateDocument', 'tag', 'controller', 'triggerInsertTag', 'after');
            $oModuleController->insertTrigger('document.deleteDocument', 'tag', 'controller', 'triggerDeleteTag', 'after');

            // 2007. 10. 17 모듈이 삭제될때 등록된 태그도 모두 삭제하는 트리거 추가
            $oModuleController->insertTrigger('module.deleteModule', 'tag', 'controller', 'triggerDeleteModuleTags', 'after');
            
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 2007. 10. 17 trigger 등록이 안되어 있으면 등록
            if(!$oModuleModel->getTrigger('document.insertDocument', 'tag', 'controller', 'triggerArrangeTag', 'before')) return true;
            if(!$oModuleModel->getTrigger('document.insertDocument', 'tag', 'controller', 'triggerInsertTag', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.updateDocument', 'tag', 'controller', 'triggerArrangeTag', 'before')) return true;
            if(!$oModuleModel->getTrigger('document.updateDocument', 'tag', 'controller', 'triggerInsertTag', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'tag', 'controller', 'triggerDeleteTag', 'after')) return true;

            // 2007. 10. 17 모듈이 삭제될때 등록된 태그도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'tag', 'controller', 'triggerDeleteModuleTags', 'after')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 2007. 10. 17 document.insertDocument, updateDocument, deleteDocument에 대한 trigger 등록
            if(!$oModuleModel->getTrigger('document.insertDocument', 'tag', 'controller', 'triggerArrangeTag', 'before')) 
                $oModuleController->insertTrigger('document.insertDocument', 'tag', 'controller', 'triggerArrangeTag', 'before');

            if(!$oModuleModel->getTrigger('document.insertDocument', 'tag', 'controller', 'triggerInsertTag', 'after')) 
                $oModuleController->insertTrigger('document.insertDocument', 'tag', 'controller', 'triggerInsertTag', 'after');

            if(!$oModuleModel->getTrigger('document.updateDocument', 'tag', 'controller', 'triggerArrangeTag', 'before')) 
                $oModuleController->insertTrigger('document.updateDocument', 'tag', 'controller', 'triggerArrangeTag', 'before');

            if(!$oModuleModel->getTrigger('document.updateDocument', 'tag', 'controller', 'triggerInsertTag', 'after')) 
                $oModuleController->insertTrigger('document.updateDocument', 'tag', 'controller', 'triggerInsertTag', 'after');

            if(!$oModuleModel->getTrigger('document.triggerDeleteTag', 'tag', 'controller', 'triggerDeleteTag', 'after')) 
                $oModuleController->insertTrigger('document.deleteDocument', 'tag', 'controller', 'triggerDeleteTag', 'after');

            // 2007. 10. 17 모듈이 삭제될때 등록된 태그도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'tag', 'controller', 'triggerDeleteModuleTags', 'after'))
                $oModuleController->insertTrigger('module.deleteModule', 'tag', 'controller', 'triggerDeleteModuleTags', 'after');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
