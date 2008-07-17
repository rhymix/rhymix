<?php
    /**
     * @class  tccommentnotify 
     * @author haneul (haneul0318@gmail.com)
     * @brief  tccommentnotify module's class 
     **/

    class tccommentnotify extends ModuleObject {

        var $cachedir = "./files/cache/tccommentnotify/";
        var $cachefile = "shouldnotify";
        var $lockfile = "notify.lock";

        /**
         * @brief Install tccommentnotify module 
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('tccommentnotify', 'view', 'dispCommentNotifyAdminIndex');

            // notify를 위한 트리거 추가
            $oModuleController->insertTrigger('comment.insertComment', 'tccommentnotify', 'controller', 'triggerInsertComment', 'after');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');
            if(!$oModuleModel->getTrigger('comment.insertComment', 'tccommentnotify', 'controller', 'triggerInsertComment', 'after'))
            {
                return true;
            }

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            if(!$oModuleModel->getTrigger('comment.insertComment', 'tccommentnotify', 'controller', 'triggerInsertComment', 'after'))
            {
                $oModuleController->insertTrigger('comment.insertComment', 'tccommentnotify', 'controller', 'triggerInsertComment', 'after');
            }

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
