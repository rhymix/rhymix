<?php
    /**
     * @class  spamfilter
     * @author zero (zero@nzeo.com)
     * @brief  spamfilter 모듈의 high class
     **/

    class spamfilter extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('spamfilter', 'view', 'dispSpamfilterAdminConfig');
            $oModuleController->insertActionForward('spamfilter', 'view', 'dispSpamfilterAdminDeniedIPList');
            $oModuleController->insertActionForward('spamfilter', 'view', 'dispSpamfilterAdminDeniedWordList');
            $oModuleController->insertActionForward('spamfilter', 'controller', 'procSpamfilterAdminInsertConfig');
            $oModuleController->insertActionForward('spamfilter', 'controller', 'procSpamfilterAdminInsertDeniedIP');
            $oModuleController->insertActionForward('spamfilter', 'controller', 'procSpamfilterAdminDeleteDeniedIP');
            $oModuleController->insertActionForward('spamfilter', 'controller', 'procSpamfilterAdminInsertDeniedWord');
            $oModuleController->insertActionForward('spamfilter', 'controller', 'procSpamfilterAdminDeleteDeniedWord');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function moduleIsInstalled() {
            return new Object();
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }

    }
?>
